<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $data = current_user_from_session($conn);
 
	if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
	}
  
 
  
  if(isset($_GET['wid_id'])) {  
    $threeword_id = $_GET['wid_id'];  
  } else {
    //header('location:' . $main_url);
  }
  
  if(isset($_GET['threeword'])) {  
    $threeword = $_GET['threeword'];  
  } else {
    //header('location:' . $main_url);
  }

  //error_log('list messages 3wid ' . $threeword_id . ' ' . json_encode($data));
  
  $threeword_id = isset($threeword_id) ? (int) $threeword_id : 0;

  // Only the owner of this 3WordID may read its messages
  $owned_3wid = db_3wordid_owned_by($threeword_id, $data['id']);
  if (!$owned_3wid) {
    error_log('list messages: user ' . $data['id'] . ' does not own 3wid ' . $threeword_id);
    header('location:3wid_list.php');
    exit;
  }

  // use the name from the database, not from the link
  $threeword = $owned_3wid['threeword'];

  $action_token = get_action_token();

  $messages = db_3wordid_list_messages($threeword_id);

  //error_log('messages found ' . json_encode($messages));

  if($messages == NULL ) {
    header('location:3wid_list.php'); 
    die(t_plain('lm.none')); 
  }
 
  require_once 'php/chat.php';

  // Same conversation as in 3wid_list_messages.php: the key must be one of the owner's own
  $dates = db_3wordid_message_dates(array_column($messages, 'id'));
  $conversations = chat_conversations($messages, $dates);
  $with = isset($_GET['with']) ? (string) $_GET['with'] : '';
  if ($with === '' || !isset($conversations[$with])) {
    header('location:3wid_list_messages.php?wid_id=' . (int) $threeword_id);
    exit;
  }
  $open = $conversations[$with];

  // Lines: the partner's messages, plus the owner's own messages to the partner (if any)
  $lines = array();
  foreach ($open['items'] as $m) {
    $lines[] = array('id' => (int) $m['id'], 'mine' => false, 'title' => $m['title'], 'message' => $m['message']);
  }
  if (!$open['visitor']) {
    $partner = db_3wordid_get_threeword($open['name']);
    if ($partner && !empty($partner['id'])) {
      foreach (db_3wordid_messages_from_to($data['id'], $threeword_id, $partner['id']) as $m) {
        $lines[] = array('id' => (int) $m['id'], 'mine' => true, 'title' => $m['title'], 'message' => $m['message']);
      }
    }
  }
  // chronological: ids go up with time
  usort($lines, function ($a, $b) { return $a['id'] <=> $b['id']; });
  $line_dates = db_3wordid_message_dates(array_column($lines, 'id'));

  $partner_label = $open['visitor'] ? t_plain('chat.print_visitor') : $open['name'];
  $back_url = '3wid_list_messages.php?wid_id=' . (int) $threeword_id . '&with=' . rawurlencode($open['key']);

  header('X-Robots-Tag: noindex, nofollow');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('chat.print_doc_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <style>
        body { margin: 0; background: #fff; color: #000; font: 15px/1.5 Georgia, "Times New Roman", serif; }
        .sheet { max-width: 760px; margin: 0 auto; padding: 24px 20px 48px; }
        .tools { display: flex; gap: 12px; align-items: center; margin-bottom: 20px; font: 14px/1.4 system-ui, sans-serif; }
        .tools a { color: #000; }
        .tools button { font: inherit; padding: 6px 14px; border: 1px solid #000; background: #fff; color: #000; border-radius: 4px; cursor: pointer; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { margin: 0; font-size: 14px; }
        hr { border: 0; border-top: 1px solid #000; margin: 14px 0 12px; }
        .line { margin: 0 0 8px; }
        .who { font-weight: bold; }
        .when { color: #000; }
        .subj { font-style: italic; }
        @media print {
            .tools { display: none; }
            .sheet { padding: 0; max-width: none; }
            body { font-size: 12pt; }
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="tools">
        <button type="button" onclick="window.print()"><?php echo t('chat.print_button'); ?></button>
        <a href="<?php echo chat_e($back_url); ?>"><?php echo t('common.back'); ?></a>
    </div>

    <h1><?php echo $open['visitor'] ? t('chat.print_heading_visitors') : t('chat.print_heading', ['name' => $open['name']]); ?></h1>
    <p class="meta"><?php echo t('chat.print_to', ['words' => $threeword]); ?></p>
    <p class="meta"><?php echo t('chat.print_date', ['date' => chat_date_text(time())]); ?></p>
    <hr>

    <?php foreach ($lines as $l):
        $when = isset($line_dates[$l['id']]) ? chat_date_text(chat_ts($line_dates[$l['id']])) . ' ' . chat_clock($line_dates[$l['id']]) : '';
        $who = $l['mine'] ? t_plain('chat.print_you') : $partner_label;
    ?>
    <p class="line"><span class="who"><?php echo t('chat.print_label', ['name' => $who]); ?></span>
        <?php if ($when !== ''): ?><span class="when">[<?php echo chat_e($when); ?>]</span><?php endif; ?>
        <?php if (trim((string) $l['title']) !== '' && !chat_title_redundant($l['title'], $l['message'])): ?><span class="subj"><?php echo chat_e($l['title']); ?></span> —<?php endif; ?>
        <?php echo chat_text($l['message']); ?></p>
    <?php endforeach; ?>
</div>
</body>
</html>
