<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  // Must be logged in
  $data = current_user_from_session($conn);
  if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
  }
  
  error_log('message view page');

  if(isset($_GET["id"])) {
	$id = (int)$_GET["id"];	
  } else {
	header("location:3wid_list.php");
	exit;
  }
  
  // Only the owner of the receiving 3WordID (or the sender) may read the message
  $message_row = db_3wordid_message_for_user($id, $data['id']);
  if (!$message_row) {
    error_log('message view: user ' . $data['id'] . ' may not read message ' . $id);
    header("location:3wid_list.php");
    exit;
  }

  $action_token = get_action_token();


  require_once 'php/chat.php';

  // Owner of the receiving 3WordID (reads) or the sender (sees their own message)
  $is_owner = (bool) db_3wordid_owned_by($message_row['3wid_id'], $data['id']);
  $to_name = db_3wordid_threeword_name($message_row['3wid_id']);
  $dates = db_3wordid_message_dates(array($message_row['id']));
  $sent_at = $dates[(int) $message_row['id']] ?? '';
  $is_visitor = ((int) $message_row['from_id'] === CHAT_VISITOR_USER_ID);
  $from_name = $is_visitor ? '' : (string) db_3wordid_threeword_name($message_row['3wid_from_id'] ?? 0);
  // Sender 3WordID deleted since (moved to 3wordid_deleted): no name, no reply
  $from_deleted = (!$is_visitor && $from_name === '');
  // The owner opening the message has read it (only messages sent to the owner's own 3WordID)
  if ($is_owner) {
      db_3wordid_mark_read_ids($message_row['3wid_id'], array($message_row['id']));
  }
  // The owner sees who sent it (and goes back to that conversation); the sender sees the recipient
  if ($is_owner) {
      $conv_key = $is_visitor ? 'visitors' : ($from_deleted ? 'deleted' : ('w' . (int) ($message_row['3wid_from_id'] ?? 0)));
      $back_url = '3wid_list_messages.php?wid_id=' . (int) $message_row['3wid_id'] . '&with=' . rawurlencode($conv_key);
      $reply_url = ($is_visitor || $from_name === '') ? '' : '3wid_messageform.php?threeword_id=' . (int) $message_row['3wid_id'] . '&threeword=' . rawurlencode($from_name);
  } else {
      $back_url = '3wid_list.php';
      $reply_url = '';
  }
  // Photo of the other side (sender for the owner, recipient for the sender), when turned on
  $head_wid = $is_owner ? ($is_visitor ? 0 : (int) ($message_row['3wid_from_id'] ?? 0)) : (int) $message_row['3wid_id'];
  $photo_ids = $head_wid ? db_3wordid_photo_ids(array($head_wid)) : array();
  $head_name = $is_owner ? ($is_visitor ? t_plain('chat.visitor') : ($from_deleted ? t_plain('chat.deleted_sender') : $from_name)) : (string) $to_name;

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo t('msgview.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo chat_asset('css/chat.css'); ?>">
</head>
<body class="chat-body">
<?php
  // Back to the conversation (owner) or to My 3WordIDs (sender); the list button is always there
  echo chat_topbar(array(
      'back'    => $back_url === '3wid_list.php' ? chat_back_home(true) : array($back_url, t('tb.chats'), t('tb.back_chats', ['words' => $to_name])),
      'avatar'  => ($is_owner && ($is_visitor || $from_deleted)) ? chat_visitor_avatar() : chat_avatar_for($head_name, $head_wid, $photo_ids, '', true),
      'name'    => chat_e($head_name),
      // other party is a car chat 3WordID: its make + colour next to its plate (never your own car)
      'badge'   => (!$head_wid || ($is_owner && $from_deleted)) ? '' : car_topbar_badge(db_3wordid_get($head_wid) ?: null, array((int) $data['id'])),
      'sub'     => $is_owner ? t('chat.to_you', ['words' => $to_name]) : t('chat.sent_by_you'),
      'actions' => chat_tb_button('3wid_list.php', 'list', t_plain('tb.my_ids')),
  ));
?>
<div class="chat-app">

    <main class="chat-scroll" data-scroll="end">
        <?php if (chat_day_key($sent_at) !== ''): ?>
        <div class="pill-wrap"><span class="pill"><?php echo chat_e(chat_day_label($sent_at)); ?></span></div>
        <?php endif; ?>
        <div class="row <?php echo $is_owner ? 'in' : 'out'; ?> first">
            <div class="bubble">
                <?php if (!chat_title_redundant($message_row['title'], $message_row['message'])): ?><div class="b-title"><?php echo chat_e($message_row['title']); ?></div><?php endif; ?>
                <div class="b-text"><?php echo chat_text($message_row['message']); ?></div>
                <div class="b-meta">
                    <?php if (chat_clock($sent_at) !== ''): ?><time><?php echo chat_e(chat_clock($sent_at)); ?></time><?php endif; ?>
                    <?php if ($is_owner): ?>
                    <a class="del" title="<?php echo t('msgview.delete'); ?>" aria-label="<?php echo t('msgview.delete'); ?>"
                       href="3wid_message_delete.php?id=<?php echo (int) $message_row['id']; ?>&amp;action_token=<?php echo chat_e($action_token); ?>"
                       onclick="return confirm(<?php echo chat_e(json_encode(t_plain('chat.confirm_delete'), JSON_UNESCAPED_UNICODE)); ?>);"><?php echo chat_icon('trash'); ?></a>
                    <?php else: ?>
                    <?php echo chat_icon('tick'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php if ($is_owner && $is_visitor): ?>
    <div class="reply-bar"><p class="note" role="note"><?php echo t('chat.no_reply'); ?></p></div>
    <?php elseif ($is_owner && $from_deleted): ?>
    <div class="reply-bar"><p class="note" role="note"><?php echo t('chat.deleted_no_reply'); ?></p></div>
    <?php elseif ($reply_url !== ''): ?>
    <a class="reply-bar" href="<?php echo chat_e($reply_url); ?>" style="text-decoration:none">
        <span class="fake"><?php echo t('lm.reply', ['name' => $from_name]); ?></span>
        <span class="go" aria-hidden="true"><?php echo chat_icon('reply'); ?></span>
    </a>
    <?php endif; ?>
</div>
<script src="<?php echo chat_asset('js/chat.js'); ?>"></script>
</body>
</html>
