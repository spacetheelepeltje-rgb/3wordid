<?php
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');

  require_once 'php/functions.php';
  require_once 'login/config.php';
  require_once __DIR__ . '/php/session_boot.php';
  
  start_app_session();
  $client_ip = get_client_ip();
  error_log('external message page ip ' . $client_ip . ' on ' . check_mobile());

  $loginUrl = '/login/options.php'; // same login page as index.php

  //$data = check_credentials($_SESSION,$_POST,$_GET);

  //if(!$data) {
      error_log('no user credentials');
      //header('location:3wid_list.php');
  //}

  //error_log('message form user ' . json_encode($data));

  if (isset($_GET["threeword"])) {
      $reply_to = $_GET["threeword"];
  } else {
      $reply_to = '';
      header('location:' . $main_url);
      exit;
  }

  $row = db_3wordid_get_threeword($reply_to);

  //$user_threeword_rows = db_3wid_get_3wids($data["id"]);

  //if($user_threeword_rows == NULL) {
      //header('location:3wid_list.php?message=Create a 3WordID first to send a message from');
  //}

  //error_log('messageform threeword found ' . json_encode($user_threeword_rows));

  $reply_to_safe = htmlspecialchars($reply_to, ENT_QUOTES, 'UTF-8');
  $notification = trim((string)($row['notification'] ?? ''));
  $has_note = $notification !== '';

  require_once 'php/chat.php';

  // The message just sent (stored by 3wid_messageform_ext_process.php), shown once as a bubble
  $sent = null;
  if (!empty($_SESSION['chat_sent']) && is_array($_SESSION['chat_sent'])) {
      if (chat_lower((string)($_SESSION['chat_sent']['to'] ?? '')) === chat_lower((string)$reply_to)) {
          $sent = $_SESSION['chat_sent'];
      }
      unset($_SESSION['chat_sent']);
  }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo t('ext.title'); ?></title>
    <?php echo lang_head_tags(); ?>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="css/chat.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="chat-body">
<div class="chat-app">
    <header class="chat-header">
        <a class="back" href="<?php echo chat_e($main_url); ?>" aria-label="<?php echo t('common.back'); ?>"><?php echo chat_icon('back'); ?></a>
        <?php echo chat_avatar($reply_to); ?>
        <div class="who">
            <div class="name"><?php echo $reply_to_safe; ?></div>
            <div class="sub" id="helperText"><?php echo t('chat.message_box'); ?></div>
        </div>
        <a class="hbtn" href="<?php echo chat_e($loginUrl); ?>"><?php echo t('nav.login'); ?></a>
    </header>

    <main class="chat-scroll" data-scroll="end">
        <div class="chat-tools"><?php echo lang_switcher(); ?></div>
        <div class="pill-wrap"><span class="pill info"><?php echo t_raw('ext.hint1'); ?></span></div>

        <?php if ($has_note): ?>
        <div class="row in first">
            <div class="bubble note"><div class="b-text html"><?php echo $notification; ?></div></div>
        </div>
        <?php endif; ?>

        <div class="pill-wrap"><span class="pill info"><?php echo t_raw('ext.hint2', ['login' => $loginUrl]); ?></span></div>

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

        <footer class="chat-footer"><?php echo $footer; ?></footer>
    </main>

    <form class="composer" id="3widForm" action="3wid_messageform_ext_process.php" method="post">
        <input type="hidden" id="threeword" value="<?php echo $reply_to_safe; ?>">
        <input type="hidden" name="threeword" value="<?php echo $reply_to_safe; ?>">
        <p class="c-note"><?php echo t('chat.ext_contact_hint'); ?></p>
        <div class="c-row">
            <div class="c-box">
                <input type="text" name="title" id="title" class="c-subject" maxlength="200" autocomplete="off" placeholder="<?php echo t('chat.subject_ph'); ?>" aria-label="<?php echo t('msg.title_label'); ?>">
                <textarea name="message" id="message" class="c-text" rows="1" placeholder="<?php echo t('chat.type_message'); ?>" aria-label="<?php echo t('msg.message_label'); ?>"></textarea>
            </div>
            <button type="submit" id="submitBtn" class="send" aria-label="<?php echo t('common.send'); ?>" title="<?php echo t('common.send'); ?>"><?php echo chat_icon('send'); ?></button>
        </div>
    </form>
</div>
<?php echo i18n_js([
    'clicked'      => 'msg.js_clicked',
    'need_title'   => 'msg.js_need_title',
    'need_message' => 'msg.js_need_message',
    'need_3wid'    => 'msg.js_need_3wid',
]); ?>
<script src="js/3wid_messageform_ext.js"></script>
<script src="js/chat.js"></script>
</body>
</html>
