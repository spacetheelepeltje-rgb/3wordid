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

  // Message times: only available when the messages table has a creation_date column
  $dates = db_3wordid_message_dates(array_column($messages, 'id'));

  // Conversations, one per sender 3WordID, newest first (see php/chat.php)
  $conversations = chat_conversations($messages, $dates);

  // Open conversation: the key must be one of the owner's own conversations
  $with = isset($_GET['with']) ? (string) $_GET['with'] : '';
  $open = ($with !== '' && isset($conversations[$with])) ? $conversations[$with] : null;

  $base_url = '3wid_list_messages.php?wid_id=' . (int) $threeword_id;
  $threeword_safe = chat_e($threeword);
  $total = count($conversations);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo t('lm.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="css/chat.css">
</head>
<body class="chat-body">
<div class="chat-app">
<?php if ($open === null): ?>
    <header class="chat-header">
        <a class="back" href="3wid_list.php" aria-label="<?php echo t('nav.back_list'); ?>"><?php echo chat_icon('back'); ?></a>
        <?php echo chat_avatar($threeword); ?>
        <div class="who">
            <div class="name"><?php echo $threeword_safe; ?></div>
            <div class="sub"><?php echo t_n('chat.conversations', $total); ?></div>
        </div>
    </header>

    <main class="chat-scroll plain">
        <ul class="chat-list">
        <?php foreach ($conversations as $c):
            $last = $c['items'][count($c['items']) - 1];
            $label = $c['visitor'] ? t('chat.visitors') : chat_e($c['name']);
            $preview = chat_preview($last['message']);
            $show_title = !chat_title_redundant($last['title'], $last['message']);
        ?>
            <li>
                <a class="chat-row" href="<?php echo chat_e($base_url . '&with=' . rawurlencode($c['key'])); ?>">
                    <?php echo $c['visitor'] ? chat_visitor_avatar() : chat_avatar($c['name']); ?>
                    <span class="cr-main">
                        <span class="cr-top">
                            <span class="cr-name"><?php echo $label; ?></span>
                            <span class="cr-time"><?php echo chat_e(chat_time_label($last['sent_at'])); ?></span>
                        </span>
                        <span class="cr-bottom">
                            <span class="cr-preview"><?php if ($show_title): ?><b><?php echo chat_e(chat_preview($last['title'], 40)); ?></b> <?php endif; ?><?php echo chat_e($preview); ?></span>
                            <?php if (count($c['items']) > 1): ?><span class="cr-count"><?php echo count($c['items']); ?></span><?php endif; ?>
                        </span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    </main>

<?php else:
    $is_visitor = $open['visitor'];
    $label = $is_visitor ? t('chat.visitors') : chat_e($open['name']);
    $reply_url = '3wid_messageform.php?threeword_id=' . (int) $threeword_id . '&threeword=' . rawurlencode($open['name']);
    $prev_day = null;
?>
    <header class="chat-header">
        <a class="back" href="<?php echo chat_e($base_url); ?>" aria-label="<?php echo t('common.back'); ?>"><?php echo chat_icon('back'); ?></a>
        <?php echo $is_visitor ? chat_visitor_avatar() : chat_avatar($open['name']); ?>
        <div class="who">
            <div class="name"><?php echo $label; ?></div>
            <div class="sub"><?php echo t('chat.to_you', ['words' => $threeword]); ?></div>
        </div>
        <a class="back" href="<?php echo chat_e('3wid_chat_print.php?wid_id=' . (int) $threeword_id . '&with=' . rawurlencode($open['key'])); ?>" target="_blank" rel="noopener" title="<?php echo t('chat.print_title'); ?>" aria-label="<?php echo t('chat.print_title'); ?>"><?php echo chat_icon('print'); ?></a>
    </header>

    <main class="chat-scroll" data-scroll="end">
        <?php foreach ($open['items'] as $i => $m):
            $day = chat_day_key($m['sent_at']);
            $new_day = ($day !== '' && $day !== $prev_day);
            if ($new_day):
                $prev_day = $day; ?>
        <div class="pill-wrap"><span class="pill"><?php echo chat_e(chat_day_label($m['sent_at'])); ?></span></div>
        <?php endif; ?>
        <div class="row in<?php echo ($i === 0 || $new_day) ? ' first' : ''; ?>">
            <div class="bubble">
                <?php if (!chat_title_redundant($m['title'], $m['message'])): ?><div class="b-title"><?php echo chat_e($m['title']); ?></div><?php endif; ?>
                <div class="b-text"><?php echo chat_text($m['message']); ?></div>
                <div class="b-meta">
                    <?php if (chat_clock($m['sent_at']) !== ''): ?><time><?php echo chat_e(chat_clock($m['sent_at'])); ?></time><?php endif; ?>
                    <a class="del" title="<?php echo t('common.delete'); ?>" aria-label="<?php echo t('common.delete'); ?>"
                       href="3wid_message_delete.php?id=<?php echo (int) $m['id']; ?>&amp;action_token=<?php echo chat_e($action_token); ?>"
                       onclick="return confirm(<?php echo chat_e(json_encode(t_plain('chat.confirm_delete'), JSON_UNESCAPED_UNICODE)); ?>);"><?php echo chat_icon('trash'); ?></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <footer class="chat-footer"><?php echo $footer; ?></footer>
    </main>

    <?php if (!$is_visitor): ?>
    <a class="reply-bar" href="<?php echo chat_e($reply_url); ?>" style="text-decoration:none">
        <span class="fake"><?php echo t('lm.reply', ['name' => $open['name']]); ?></span>
        <span class="go" aria-hidden="true"><?php echo chat_icon('reply'); ?></span>
    </a>
    <?php else: ?>
    <div class="reply-bar"><span class="fake static"><?php echo t('chat.no_reply'); ?></span></div>
    <?php endif; ?>
<?php endif; ?>
</div>
<script src="js/chat.js"></script>
</body>
</html>
