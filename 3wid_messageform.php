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
  if (!empty($_SESSION['chat_error']) && in_array($_SESSION['chat_error'], array('chat.err_visitor', 'chat.err_recipient', 'chat.err_inactive', 'chat.err_empty'), true)) {
      $error_key = $_SESSION['chat_error'];
  }
  unset($_SESSION['chat_error']);

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
  // Sender shown in the bar: the selected 3WordID, else the first one
  $from_words = $user_threeword_rows[0]['threeword'];
  foreach ($user_threeword_rows as $select_row) {
      if ((string) $select_row['id'] === (string) $wid_id) { $from_words = $select_row['threeword']; }
  }
  echo chat_topbar(array(
      'back'      => chat_back_home(true),
      'avatar'    => $reply_to !== '' ? chat_avatar_for($reply_to, $reply_wid, $photo_ids, '', true) : '<span class="avatar new" aria-hidden="true">' . chat_icon('compose') . '</span>',
      'name'      => $reply_to !== '' ? $reply_to_safe : t('chat.new_message'),
      'name_id'   => 'tbName',
      'sub'       => t('tb.chatting_as', ['words' => $from_words]),
      'sub_attrs' => 'id="tbSub" data-tpl="' . t('tb.chatting_as', ['words' => '{words}']) . '"',
  ));
?>
<div class="chat-app">

    <main class="chat-scroll" data-scroll="end">
        <div class="pill-wrap"><span class="pill info"><?php echo t('msg.hint1'); ?></span></div>
        <div class="pill-wrap"><span class="pill info"><?php echo t_raw('msg.hint2'); ?></span></div>

        <?php if ($error_key !== ''): ?>
        <div class="pill-wrap"><span class="pill info" role="alert"><?php echo t($error_key); ?></span></div>
        <?php endif; ?>

        <div class="setup">
            <div class="f">
                <label for="from_id"><?php echo t('msg.from'); ?></label>
                <select name="from_id" id="from_id" form="3widForm">
                    <?php foreach ($user_threeword_rows as $select_row): ?>
                    <option value="<?php echo (int) $select_row['id']; ?>" data-words="<?php echo chat_e($select_row['threeword']); ?>"<?php echo ((string) $select_row['id'] === (string) $wid_id) ? ' selected' : ''; ?>><?php echo t('msg.send_from', ['words' => $select_row['threeword']]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
                <input type="text" name="title" id="title" class="c-subject" maxlength="200" autocomplete="off" placeholder="<?php echo t('chat.subject_ph'); ?>" aria-label="<?php echo t('msg.title_label'); ?>">
                <textarea name="message" id="message" class="c-text" rows="1" placeholder="<?php echo t('chat.type_message'); ?>" aria-label="<?php echo t('msg.message_label'); ?>"></textarea>
            </div>
            <button type="submit" id="submitBtn" class="send" aria-label="<?php echo t('common.send'); ?>" title="<?php echo t('common.send'); ?>"><?php echo chat_icon('send'); ?></button>
        </div>
        <input type="hidden" name="action_token" value="<?php echo htmlspecialchars(get_action_token(), ENT_QUOTES, 'UTF-8'); ?>">
    </form>
</div>
<?php echo i18n_js([
    'ok_recipient'  => 'msg.js_ok_recipient',
    'bad_recipient' => 'msg.js_bad_recipient',
    'invalid'       => 'msg.js_invalid',
    'clicked'       => 'msg.js_clicked',
    'need_title'    => 'msg.js_need_title',
    'need_message'  => 'msg.js_need_message',
    'need_3wid'     => 'msg.js_need_3wid',
]); ?>
<script src="<?php echo chat_asset('js/3wid_messageform.js'); ?>"></script>
<script src="<?php echo chat_asset('js/chat.js'); ?>"></script>
<script>
    // Check the recipient right away when it is prefilled (reply)
    if ($('#threeword').val().trim() !== '') { $('#threeword').trigger('keyup'); }

    // Keep the top bar in step with the form: recipient name and "Chatting as"
    (function () {
        var name = document.getElementById('tbName'), sub = document.getElementById('tbSub');
        var to = document.getElementById('threeword'), from = document.getElementById('from_id');
        var empty = name ? name.textContent : '';
        function words(v) { return v.replace(/\s+/g, ' ').trim(); }
        function syncName() { if (name && to) { name.textContent = words(to.value) || empty; } }
        function syncSub() {
            if (!sub || !from || from.selectedIndex < 0) { return; }
            var opt = from.options[from.selectedIndex];
            var w = opt.getAttribute('data-words') || opt.text;
            sub.textContent = (sub.getAttribute('data-tpl') || '{words}').replace('{words}', w);
        }
        if (to) { to.addEventListener('input', syncName); syncName(); }
        if (from) { from.addEventListener('change', syncSub); syncSub(); }
    })();
</script>
</body>
</html>
