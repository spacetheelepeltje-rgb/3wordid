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
  $back_url = $is_owner ? '3wid_list_messages.php?wid_id=' . (int) $message_row['3wid_id'] : '3wid_list.php';

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo t('msgview.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="css/chat.css">
</head>
<body class="chat-body">
<div class="chat-app">
    <header class="chat-header">
        <a class="back" href="<?php echo chat_e($back_url); ?>" aria-label="<?php echo t('common.back'); ?>"><?php echo chat_icon('back'); ?></a>
        <?php echo chat_avatar($to_name); ?>
        <div class="who">
            <div class="name"><?php echo chat_e($to_name); ?></div>
            <div class="sub"><?php echo $is_owner ? t('chat.received') : t('chat.sent_by_you'); ?></div>
        </div>
    </header>

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
</div>
<script src="js/chat.js"></script>
</body>
</html>
