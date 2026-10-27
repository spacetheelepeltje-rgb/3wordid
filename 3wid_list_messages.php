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

  // Messages I sent from this 3WordID (the other half of each conversation)
  $sent_rows = db_3wordid_list_sent($threeword_id, $data['id']);

  if($messages == NULL && !$sent_rows) {
    header('location:3wid_list.php'); 
    die(t_plain('lm.none')); 
  }
 
  require_once 'php/chat.php';

  // Message times: only available when the messages table has a creation_date column
  $all_ids = array_merge(array_column((array) $messages, 'id'), array_column($sent_rows, 'id'));
  $dates = db_3wordid_message_dates($all_ids);
  // Read receipts: only when the messages table has a read_at column
  $reads = db_3wordid_message_reads($all_ids);

  // Conversations, one per partner 3WordID, both directions, newest first (see php/chat.php)
  $conversations = chat_conversations((array) $messages, $dates, $sent_rows, $reads);
  // Senders who turned "Show my photo in chats" on (the owner here is always logged in)
  $photo_ids = db_3wordid_photo_ids(array_column($conversations, 'wid'));

  // Open conversation: the key must be one of the owner's own conversations
  $with = isset($_GET['with']) ? (string) $_GET['with'] : '';
  $open = ($with !== '' && isset($conversations[$with])) ? $conversations[$with] : null;

  // Opening a conversation marks the messages sent to me in it as read (this 3WordID is mine)
  if ($open !== null && $open['unread'] > 0) {
      db_3wordid_mark_read($threeword_id, $open['visitor'] ? CHAT_VISITOR_WID : $open['wid']);
  }

  // The reply just sent from the composer below (3wid_messageform_process.php), once
  $just_sent = false;
  if ($open !== null && !empty($_SESSION['chat_conv_sent']) && $_SESSION['chat_conv_sent'] === $threeword_id . '|' . $open['key']) {
      $just_sent = true;
  }
  unset($_SESSION['chat_conv_sent']);
  // A note from the process script (recipient gone, etc.), once
  $conv_error = '';
  if ($open !== null && !empty($_SESSION['chat_error']) && in_array($_SESSION['chat_error'], array('chat.err_visitor', 'chat.err_recipient', 'chat.err_inactive', 'chat.err_empty'), true)) {
      $conv_error = $_SESSION['chat_error'];
      unset($_SESSION['chat_error']);
  }

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
        <a class="back" href="3wid_messageform.php?threeword_id=<?php echo (int) $threeword_id; ?>" title="<?php echo t('chat.new_message'); ?>" aria-label="<?php echo t('chat.new_message'); ?>"><?php echo chat_icon('compose'); ?></a>
    </header>

    <main class="chat-scroll plain">
        <ul class="chat-list">
        <?php foreach ($conversations as $c):
            $last = $c['items'][count($c['items']) - 1];
            $label = $c['visitor'] ? t('chat.visitors') : chat_e($c['name']);
            $preview = chat_preview($last['message']);
            $show_title = !$last['mine'] && !chat_title_redundant($last['title'], $last['message']);
        ?>
            <li>
                <a class="chat-row<?php echo $c['unread'] > 0 ? ' unread' : ''; ?>" href="<?php echo chat_e($base_url . '&with=' . rawurlencode($c['key'])); ?>">
                    <?php echo $c['visitor'] ? chat_visitor_avatar() : chat_avatar_for($c['name'], $c['wid'], $photo_ids); ?>
                    <span class="cr-main">
                        <span class="cr-top">
                            <span class="cr-name"><?php echo $label; ?></span>
                            <span class="cr-time"><?php echo chat_e(chat_time_label($last['sent_at'])); ?></span>
                        </span>
                        <span class="cr-bottom">
                            <span class="cr-preview"><?php if ($last['mine']): ?><span class="cr-you"><?php echo t('chat.you_prefix'); ?></span> <?php endif; ?><?php if ($show_title): ?><b><?php echo chat_e(chat_preview($last['title'], 40)); ?></b> <?php endif; ?><?php echo chat_e($preview); ?></span>
                            <?php if ($c['unread'] > 0): ?><span class="cr-unread" role="img" title="<?php echo t_n('chat.unread', $c['unread']); ?>" aria-label="<?php echo t_n('chat.unread', $c['unread']); ?>"></span><?php endif; ?>
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
    $prev_day = null;
?>
    <header class="chat-header">
        <a class="back" href="<?php echo chat_e($base_url); ?>" aria-label="<?php echo t('common.back'); ?>"><?php echo chat_icon('back'); ?></a>
        <?php echo $is_visitor ? chat_visitor_avatar() : chat_avatar_for($open['name'], $open['wid'], $photo_ids, '', true); ?>
        <div class="who">
            <div class="name"><?php echo $label; ?></div>
            <div class="sub"><?php echo t('chat.your_wid', ['words' => $threeword]); ?></div>
        </div>
        <a class="back" href="<?php echo chat_e('3wid_chat_print.php?wid_id=' . (int) $threeword_id . '&with=' . rawurlencode($open['key'])); ?>" target="_blank" rel="noopener" title="<?php echo t('chat.print_title'); ?>" aria-label="<?php echo t('chat.print_title'); ?>"><?php echo chat_icon('print'); ?></a>
    </header>

    <main class="chat-scroll" data-scroll="end" data-refresh="15">
        <?php foreach ($open['items'] as $i => $m):
            $day = chat_day_key($m['sent_at']);
            $new_day = ($day !== '' && $day !== $prev_day);
            if ($new_day):
                $prev_day = $day; ?>
        <div class="pill-wrap"><span class="pill"><?php echo chat_e(chat_day_label($m['sent_at'])); ?></span></div>
        <?php endif; ?>
        <?php
            $side = $m['mine'] ? 'out' : 'in';
            $prev_m = $i > 0 ? $open['items'][$i - 1] : null;
            $first = ($i === 0 || $new_day || $prev_m['mine'] !== $m['mine']);
        ?>
        <div class="row <?php echo $side; ?><?php echo $first ? ' first' : ''; ?>" data-id="<?php echo (int) $m['id']; ?>">
            <div class="bubble">
                <?php if (!chat_title_redundant($m['title'], $m['message']) && !$m['mine']): ?><div class="b-title"><?php echo chat_e($m['title']); ?></div><?php endif; ?>
                <div class="b-text"><?php echo chat_text($m['message']); ?></div>
                <div class="b-meta">
                    <?php if (chat_clock($m['sent_at']) !== ''): ?><time><?php echo chat_e(chat_clock($m['sent_at'])); ?></time><?php endif; ?>
                    <?php if ($m['mine']): ?>
                    <?php echo (is_string($m['read_at']) && $m['read_at'] !== '') ? chat_icon('ticks') : chat_icon('tick'); ?>
                    <?php else: ?>
                    <a class="del" title="<?php echo t('common.delete'); ?>" aria-label="<?php echo t('common.delete'); ?>"
                       href="3wid_message_delete.php?id=<?php echo (int) $m['id']; ?>&amp;action_token=<?php echo chat_e($action_token); ?>"
                       onclick="return confirm(<?php echo chat_e(json_encode(t_plain('chat.confirm_delete'), JSON_UNESCAPED_UNICODE)); ?>);"><?php echo chat_icon('trash'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <footer class="chat-footer"><?php echo $footer; ?></footer>
    </main>

    <?php if (!$is_visitor): ?>
    <form class="composer conv" id="3widForm"<?php echo $just_sent ? ' data-sent="1"' : ''; ?> action="3wid_messageform_process.php" method="post">
        <?php if ($conv_error !== ''): ?><p class="c-note err" role="alert"><?php echo t($conv_error); ?></p><?php endif; ?>
        <input type="hidden" name="from_id" value="<?php echo (int) $threeword_id; ?>">
        <input type="hidden" name="threeword" value="<?php echo chat_e($open['name']); ?>">
        <input type="hidden" name="return" value="conv">
        <input type="hidden" name="title" value="">
        <div class="c-row">
            <div class="c-box">
                <textarea name="message" id="message" class="c-text" rows="1" required maxlength="5000" placeholder="<?php echo t('lm.reply', ['name' => $open['name']]); ?>" aria-label="<?php echo t('msg.message_label'); ?>"></textarea>
            </div>
            <button type="submit" id="submitBtn" class="send" aria-label="<?php echo t('common.send'); ?>" title="<?php echo t('common.send'); ?>"><?php echo chat_icon('send'); ?></button>
        </div>
        <input type="hidden" name="action_token" value="<?php echo chat_e($action_token); ?>">
    </form>
    <?php else: ?>
    <div class="reply-bar"><p class="note" role="note"><?php echo t('chat.no_reply'); ?></p></div>
    <?php endif; ?>
<?php endif; ?>
</div>
<script src="js/chat.js"></script>
</body>
</html>
