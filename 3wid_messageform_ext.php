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

  // Switched off by its owner: no chat, the "not active at the moment" page instead
  if (is_array($row) && !empty($row['id']) && (int) ($row['enabled'] ?? 0) !== 1) {
      header('location:3wid_inactive.php?threewords=' . rawurlencode($row['threeword']));
      exit;
  }

  //$user_threeword_rows = db_3wid_get_3wids($data["id"]);

  //if($user_threeword_rows == NULL) {
      //header('location:3wid_list.php?message=Create a 3WordID first to send a message from');
  //}

  //error_log('messageform threeword found ' . json_encode($user_threeword_rows));

  $reply_to_safe = htmlspecialchars($reply_to, ENT_QUOTES, 'UTF-8');
  $notification = trim((string)($row['notification'] ?? ''));
  $has_note = $notification !== '';

  require_once 'php/chat.php';

  // Profile photo of this 3WordID (opt-in by its owner): only logged-in members see it.
  // Visitors get a hint to log in instead; the photo is not in this page at all for them.
  $wid_id = (is_array($row) && !chat_is_visitor_wid($row)) ? (int) $row['id'] : 0;
  $has_photo = $wid_id ? !empty(db_3wordid_photo_ids(array($wid_id))[$wid_id]) : false;
  // Logged in? Then "back" goes to your own 3WordIDs, otherwise to the homepage
  $viewer = current_user_from_session($conn);
  $photo_ids = ($has_photo && $viewer) ? array($wid_id => true) : array();

  // The message just sent (stored by 3wid_messageform_ext_process.php), shown once as a bubble
  $sent = null;
  if (!empty($_SESSION['chat_sent']) && is_array($_SESSION['chat_sent'])) {
      if (chat_lower((string)($_SESSION['chat_sent']['to'] ?? '')) === chat_lower((string)$reply_to)) {
          $sent = $_SESSION['chat_sent'];
      }
      unset($_SESSION['chat_sent']);
  }

  // Held back by the spam limit (3wid_messageform_ext_process.php): a note, and the typed text back, once
  $error_key = '';
  if (!empty($_SESSION['chat_error']) && in_array($_SESSION['chat_error'], array('chat.err_rate_visitor', 'chat.err_rate_recipient', 'chat.err_closed_in'), true)) {
      $error_key = $_SESSION['chat_error'];
  }
  unset($_SESSION['chat_error']);

  // Car chat: make and colour from RDW, stored when car chat was turned on (php/car_chat.php)
  $car_line = car_chat_on($row) ? car_summary($row) : '';
  // Closed chat: messages only from 3WordIDs of the same account, so no visitor composer here
  $is_closed = chat_is_closed($row);
  if ($is_closed && $error_key === 'chat.err_closed_in') {
      $error_key = ''; // the closed note below says the same
  }

  // What was typed when a message was held back (rate limit), put back in the box once
  $draft = (string) ($_SESSION['chat_draft'] ?? '');
  unset($_SESSION['chat_draft']);
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
    <link rel="stylesheet" href="<?php echo chat_asset('css/chat.css'); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="chat-body">
<?php
  echo chat_topbar(array(
      'back'    => chat_back_home((bool) $viewer, $main_url),
      'avatar'  => chat_avatar_for($reply_to, $wid_id, $photo_ids, '', true),
      'name'    => $reply_to_safe,
      'sub'     => t('tb.anonymous'),
      // Logged in: the list button to My 3WordIDs; visitors: "Login" top left, a home icon here
      'actions' => $viewer ? chat_tb_button('3wid_list.php', 'list', t_plain('tb.my_ids')) : chat_home_button(),
  ));
?>
<div class="chat-app">

    <main class="chat-scroll" data-scroll="end">
        <?php if ($has_photo && !$viewer): ?>
        <div class="pill-wrap"><span class="pill info"><a href="<?php echo chat_e($loginUrl); ?>"><?php echo t('chat.photo_login_hint'); ?></a></span></div>
        <?php endif; ?>
        <?php if ($car_line !== ''): ?>
        <div class="pill-wrap"><span class="pill car" title="<?php echo t('car.from_rdw'); ?>"><?php echo car_icon(18); ?><span><span class="car-label"><?php echo t('car.label'); ?></span> <strong><?php echo chat_e($car_line); ?></strong></span></span></div>
        <div class="pill-wrap"><span class="pill car-src"><?php echo t('car.from_rdw'); ?></span></div>
        <?php endif; ?>
        <?php if (!$is_closed): ?>
        <div class="pill-wrap"><span class="pill info"><?php echo t_raw('ext.hint1'); ?></span></div>
        <?php endif; ?>

        <?php if ($has_note): ?>
        <div class="row in first">
            <div class="bubble note"><div class="b-text html"><?php echo $notification; ?></div></div>
        </div>
        <?php endif; ?>

        <?php if ($is_closed): ?>
        <div class="pill-wrap"><span class="pill info closed-note" role="status"><?php echo t('access.closed_note'); ?></span></div>
        <?php else: ?>
        <div class="pill-wrap"><span class="pill info"><?php echo t_raw('ext.hint2', ['login' => $loginUrl]); ?></span></div>
        <?php endif; ?>

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

        <?php if ($error_key !== ''): ?>
        <div class="pill-wrap"><span class="pill info" role="alert"><?php echo t($error_key); ?></span></div>
        <?php endif; ?>

        <footer class="chat-footer"><?php echo $footer; ?></footer>
    </main>

    <?php if (!$is_closed): ?>
    <form class="composer" id="3widForm"<?php echo $sent ? ' data-sent="1"' : ''; ?> action="3wid_messageform_ext_process.php" method="post">
        <input type="hidden" id="threeword" value="<?php echo $reply_to_safe; ?>">
        <input type="hidden" name="threeword" value="<?php echo $reply_to_safe; ?>">
        <p class="c-note"><?php echo t('chat.ext_contact_hint'); ?></p>
        <p class="c-note c-status" id="helperText" role="status" aria-live="polite"></p>
        <div class="c-row">
            <div class="c-box">
                <textarea name="message" id="message" class="c-text" rows="1" placeholder="<?php echo t('chat.type_message'); ?>" aria-label="<?php echo t('msg.message_label'); ?>"><?php echo chat_e($draft); ?></textarea>
            </div>
            <button type="submit" id="submitBtn" class="send" aria-label="<?php echo t('common.send'); ?>" title="<?php echo t('common.send'); ?>"><?php echo chat_icon('send'); ?></button>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php echo i18n_js([
    'clicked'      => 'msg.js_clicked',
    'need_message' => 'msg.js_need_message',
    'need_3wid'    => 'msg.js_need_3wid',
]); ?>
<script src="<?php echo chat_asset('js/3wid_messageform_ext.js'); ?>"></script>
<script src="<?php echo chat_asset('js/chat.js'); ?>"></script>
</body>
</html>
