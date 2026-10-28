<?php

  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
  
  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $data = current_user_from_session($conn);
  
  if(!$data) {
      error_log('no user credentials');
      header('location:3wid_list.php');  
      exit;
  }

  // Logged-in users accept the privacy terms once (3wid_privacy_consent.php), so this
  // form has no consent checkbox of its own. Make sure that consent is really there.
  if ((int) ($data['privacy_consent'] ?? 0) !== 1) {
      header('Location: 3wid_privacy_consent.php');
      exit;
  }
  
  //error_log('message form user ' . json_encode($data));
 
 if(isset($_GET["threeword"])) {
     $reply_to = $_GET["threeword"];
 } else {
     $reply_to='';
 }
 
  if(isset($_GET["threeword_id"])) {
	  
     $wid_id = $_GET["threeword_id"];
     
 } else {
	 
     $wid_id = '';
     
 }
 
 $user_threeword_rows = db_3wid_get_3wids($data["id"]);
 
 if($user_threeword_rows == NULL) {
     header('location:3wid_list.php?message=' . rawurlencode(t_plain('mp.create_first')));  
     exit;
 }
 

  require_once 'php/chat.php';

  // A note from 3wid_messageform_process.php (unknown recipient / visitor), shown once
  $error_key = '';
  if (!empty($_SESSION['chat_error']) && in_array($_SESSION['chat_error'], array('chat.err_visitor', 'chat.err_recipient', 'chat.err_inactive', 'chat.err_empty', 'chat.err_rate_pair', 'chat.err_rate_recipient'), true)) {
      $error_key = $_SESSION['chat_error'];
  }
  unset($_SESSION['chat_error']);

  // What was typed when a message was held back (rate limit), put back in the box once
  $draft = (string) ($_SESSION['chat_draft'] ?? '');
  unset($_SESSION['chat_draft']);

  // Never prefill the visitor placeholder as a recipient: visitors cannot get replies
  $reply_row = $reply_to !== '' ? db_3wordid_get_threeword($reply_to) : null;
  if ($reply_to !== '' && chat_is_visitor_wid($reply_row)) {
      $reply_to = '';
      $reply_row = null;
      $error_key = 'chat.err_visitor';
  }

  // Profile photo of the addressed 3WordID (only when its owner turned it on)
  $reply_wid = $reply_row ? (int) $reply_row['id'] : 0;
  $photo_ids = $reply_wid ? db_3wordid_photo_ids(array($reply_wid)) : array();

  $reply_to_safe = chat_e($reply_to);

  // The message just sent (stored by 3wid_messageform_process.php), shown once as a bubble
  $sent = null;
  if (!empty($_SESSION['chat_sent']) && is_array($_SESSION['chat_sent'])) {
      $sent = $_SESSION['chat_sent'];
      unset($_SESSION['chat_sent']);
  }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo t('msg.title'); ?></title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo chat_asset('css/chat.css'); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="chat-body">
<?php
  // Sender: the selected 3WordID, else the first one. It is shown in the bar as
  // "Chatting as <words>"; with more than one 3WordID the words are a small menu there
  // (no separate "Send from" field in the form, that took a lot of room on phones).
  $from_id = (int) $user_threeword_rows[0]['id'];
  $from_words = $user_threeword_rows[0]['threeword'];
  foreach ($user_threeword_rows as $select_row) {
      if ((string) $select_row['id'] === (string) $wid_id) { $from_id = (int) $select_row['id']; $from_words = $select_row['threeword']; }
  }
  if (count($user_threeword_rows) > 1) {
      $from_html = '<select name="from_id" id="from_id" form="3widForm" class="tb-from" aria-label="' . t('msg.from') . '">';
      foreach ($user_threeword_rows as $select_row) {
          $from_html .= '<option value="' . (int) $select_row['id'] . '"' . ((int) $select_row['id'] === $from_id ? ' selected' : '') . '>' . chat_e($select_row['threeword']) . '</option>';
      }
      $from_html .= '</select>';
  } else {
      $from_html = chat_e($from_words);
  }
  // "Chatting as" in its own span: on a narrow screen that text is shortened, not the menu
  $sub_parts = explode('%%FROM%%', t('tb.chatting_as', ['words' => '%%FROM%%']), 2);
  $sub = ($sub_parts[0] !== '' ? '<span class="tb-sub-pre">' . $sub_parts[0] . '</span>' : '') . $from_html
       . (isset($sub_parts[1]) && $sub_parts[1] !== '' ? '<span class="tb-sub-pre">' . $sub_parts[1] . '</span>' : '');

  // Started from "Compose message" (no recipient yet): no pencil icon in the bar,
  // you are already writing a new message. The list button is always there.
  echo chat_topbar(array(
      'back'      => chat_back_home(true),
      'avatar'    => $reply_to !== '' ? chat_avatar_for($reply_to, $reply_wid, $photo_ids, '', true) : '',
      'name'      => $reply_to !== '' ? $reply_to_safe : t('chat.new_message'),
      'name_id'   => 'tbName',
      'sub'       => $sub,
      'sub_attrs' => 'class="tb-sub tb-sub-from"',
      'actions'   => chat_tb_button('3wid_list.php', 'list', t_plain('tb.my_ids')),
  ));
?>
<div class="chat-app">

    <main class="chat-scroll" data-scroll="end">
        <div class="pill-wrap"><span class="pill info"><?php echo t_raw('msg.hint2'); ?></span></div>

        <?php if ($error_key !== ''): ?>
        <div class="pill-wrap"><span class="pill info" role="alert"><?php echo t($error_key); ?></span></div>
        <?php endif; ?>

        <div class="setup">
            <div class="f">
                <label for="threeword"><?php echo t('msg.recipient'); ?></label>
                <input type="text" name="threeword" id="threeword" form="3widForm" autocomplete="off" autocapitalize="none" placeholder="<?php echo t('common.placeholder_words'); ?>" value="<?php echo $reply_to_safe; ?>">
            </div>
            <div class="f-status" id="helperText" role="status" aria-live="polite"></div>
        </div>

        <?php if ($sent): ?>
        <div class="pill-wrap"><span class="pill"><?php echo t('chat.today'); ?></span></div>
        <div class="row out first">
            <div class="bubble">
                <?php if (!chat_title_redundant($sent['title'], $sent['message'])): ?><div class="b-title"><?php echo chat_e($sent['title']); ?></div><?php endif; ?>
                <div class="b-text"><?php echo chat_text($sent['message']); ?></div>
                <div class="b-meta"><time><?php echo chat_e(date('H:i', (int) $sent['time'])); ?></time> <?php echo chat_icon('tick'); ?></div>
            </div>
        </div>
        <div class="pill-wrap"><span class="pill ok"><?php echo t('mp.thanks_to', ['name' => $sent['to']]); ?></span></div>
        <?php endif; ?>
    </main>

    <form class="composer" id="3widForm"<?php echo $sent ? ' data-sent="1"' : ''; ?> action="3wid_messageform_process.php" method="post">
        <div class="c-row">
            <div class="c-box">
                <textarea name="message" id="message" class="c-text" rows="1" placeholder="<?php echo t('chat.type_message'); ?>" aria-label="<?php echo t('msg.message_label'); ?>"><?php echo chat_e($draft); ?></textarea>
            </div>
            <button type="submit" id="submitBtn" class="send" aria-label="<?php echo t('common.send'); ?>" title="<?php echo t('common.send'); ?>"><?php echo chat_icon('send'); ?></button>
        </div>
        <?php if (count($user_threeword_rows) === 1): ?><input type="hidden" name="from_id" value="<?php echo $from_id; ?>"><?php endif; ?>
        <input type="hidden" name="action_token" value="<?php echo htmlspecialchars(get_action_token(), ENT_QUOTES, 'UTF-8'); ?>">
    </form>
</div>
<?php echo i18n_js([
    'ok_recipient'  => 'msg.js_ok_recipient',
    'bad_recipient' => 'msg.js_bad_recipient',
    'invalid'       => 'msg.js_invalid',
    'clicked'       => 'msg.js_clicked',
    'need_message'  => 'msg.js_need_message',
    'need_3wid'     => 'msg.js_need_3wid',
    'checking'      => 'msg.js_checking',
    'check_later'   => 'msg.js_check_later',
]); ?>
<script src="<?php echo chat_asset('js/3wid_messageform.js'); ?>"></script>
<script src="<?php echo chat_asset('js/chat.js'); ?>"></script>
<script>
    // Check the recipient right away (prefilled for a reply); Send waits for that check
    checkRecipient();

    // Keep the name in the top bar in step with the recipient field
    (function () {
        var name = document.getElementById('tbName'), to = document.getElementById('threeword');
        var empty = name ? name.textContent : '';
        function syncName() { if (name && to) { name.textContent = to.value.replace(/\s+/g, ' ').trim() || empty; } }
        if (to) { to.addEventListener('input', syncName); syncName(); }
    })();
</script>
</body>
</html>
