<?php

require_once 'login/config.php';
require_once 'php/functions.php';

$data = current_user_from_session($conn);

if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

 if(isset($_GET["id"])) {
    $user_id = $_GET["id"];
 } else {
    //header('location:' . $main_url);
 }
 
 // Only paying users may use digits in a 3WordID (see user_is_paying())
 $paying = user_is_paying($data);

 // Sent back by 3wid_insert.php: show why, and keep what was typed
 $old = (isset($_SESSION['add_form']) && is_array($_SESSION['add_form'])) ? $_SESSION['add_form'] : [];
 unset($_SESSION['add_form']);
 $old_checked = function ($key, $default = false) use ($old) {
     $on = array_key_exists($key, $old) ? (int) $old[$key] === 1 : $default;
     return $on ? ' checked' : '';
 };


 // "Message with link" (only offered once the link_title column exists)
 $has_link_title = db_3wordid_has_link_title();
 $is_msglink = $has_link_title && (int) ($old['msglinkflag'] ?? 0) === 1;

?>  
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('add.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      :root {
        --navy: #0F1C3F;
        --navy-hover: #162756;
        --slate: #5B6478;
        --muted: #8A93A6;
        --line: #E6E3DC;
        --paper: #F7F6F3;
        --white: #FFFFFF;
        --gold: #B45309;
        --ok: #16794A;
        --danger: #9B1C1C;
        --shadow: 0 10px 30px rgba(15,28,63,.08);
        --chip: #EFECE6;
        --well: #F3F1EC;
        --row: #F0EEE8;
        --card-line: #ECEAE4;
      }
      * { box-sizing: border-box; }
      html, body { margin: 0; padding: 0; }
      body {
        font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
        background: var(--paper);
        color: var(--navy);
        min-height: 100vh;
      }
      a { color: var(--navy); text-decoration: none; }
      a:hover { color: var(--navy-hover); }

      .site-header {
        position: sticky;
        top: 0;
        z-index: 40;
        background: var(--paper);
        border-bottom: 1px solid var(--line);
      }
      .header-inner {
        max-width: 1100px;
        margin: 0 auto;
        padding: 14px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--navy);
        font-weight: 700;
        letter-spacing: -0.03em;
        font-size: 16px;
      }
      .mark {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: var(--navy);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        line-height: 0.75;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: -0.04em;
        flex-shrink: 0;
      }
      .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 10px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        padding: 9px 14px;
        cursor: pointer;
        border: 1px solid transparent;
        line-height: 1.2;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-hover); color: #fff; }
      .btn-ghost {
        background: var(--white);
        color: var(--navy);
        border-color: var(--line);
      }
      .btn-ghost:hover { background: var(--well); }
      .icon-btn {
        width: 36px;
        height: 36px;
        padding: 0;
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--white);
        color: var(--navy);
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }
      .icon-btn svg { stroke: currentColor; fill: none; stroke-width: 2; }

      .page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 28px 26px 64px;
      }
      .eyebrow {
        color: var(--gold);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin: 0 0 8px;
      }
      h1 {
        margin: 0 0 8px;
        font-size: clamp(32px, 5vw, 44px);
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1.05;
      }
      .subhead {
        margin: 0 0 24px;
        color: var(--slate);
        font-size: 16px;
        max-width: 640px;
      }

      .panel {
        background: var(--white);
        border: 1px solid var(--card-line);
        border-radius: 16px;
        box-shadow: var(--shadow);
        padding: 22px;
        max-width: 720px;
      }
      .field { margin-bottom: 16px; }
      .field label.lbl {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--slate);
        margin-bottom: 6px;
      }
      input[type="text"],
      textarea {
        width: 100%;
        background: var(--white);
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 12px 14px;
        font: 15px/1.4 Inter, system-ui, sans-serif;
        color: var(--navy);
        outline: none;
      }
      input[type="text"]:focus,
      textarea:focus {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(15,28,63,.08);
      }
      textarea { min-height: 110px; resize: vertical; }
      .checks {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin: 6px 0 18px;
      }
      .checks label {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 14px;
        color: var(--navy);
        font-weight: 500;
      }
      .checks input[type="checkbox"] {
        margin-top: 2px;
        accent-color: var(--navy);
      }
      .hint {
        font-size: 13px;
        color: var(--muted);
        margin: 0 0 18px;
        line-height: 1.5;
      }
      .later-note {
        font-size: 13px;
        color: var(--muted);
        line-height: 1.5;
        margin: -6px 0 18px;
        padding: 10px 12px;
        border-radius: 10px;
        background: #f6f4ef;
      }
      .hint a { color: var(--navy); text-decoration: underline; text-underline-offset: 2px; }
      .form-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
      }
      #helperText {
        font-size: 13px;
        color: var(--muted);
      }
      .field-hint {
        font-size: 13px;
        color: var(--muted);
        margin: 6px 0 0;
        line-height: 1.5;
      }
      .field-hint a { color: var(--navy); text-decoration: underline; text-underline-offset: 2px; }
      .field-error {
        font-size: 13px;
        font-weight: 600;
        color: var(--danger);
        margin: 6px 0 0;
        line-height: 1.5;
      }
      .field-error a { color: var(--danger); text-decoration: underline; text-underline-offset: 2px; }
      .field-error[hidden], .lbl .opt[hidden], .field[hidden] { display: none; }
      input[aria-invalid="true"], textarea[aria-invalid="true"] { border-color: var(--danger); }
      .lbl .opt { font-weight: 500; color: var(--muted); }
      .form-error {
        background: #FDF2F2;
        border: 1px solid #F3C7C7;
        color: var(--danger);
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 16px;
      }

      .site-footer {
        max-width: 1100px;
        margin: 0 auto;
        padding: 8px 26px 40px;
        color: var(--muted);
        font-size: 13px;
      }

      @media (max-width: 800px) {
        .header-inner, .page { padding-left: 20px; padding-right: 20px; }
        .header-actions .btn span.hide-sm { display: none; }
      }
    </style>
</head>
<body>
    <header class="site-header">
      <div class="header-inner">
        <a class="brand" href="<?php echo $main_url;?>">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        <div class="header-actions">
          <?php echo lang_switcher(); ?>
          <a class="btn btn-ghost" href="3wid_list.php"><?php echo t('nav.back_list'); ?></a>  
          </a>
        </div>
      </div>
    </header>

    <main class="page">
      <p class="eyebrow"><?php echo t('add.eyebrow'); ?></p>
      <h1><?php echo t('add.h1'); ?></h1>
      <p class="subhead"><?php echo t('add.subhead'); ?></p>

      <div class="panel">
        <form id="3widForm" action="3wid_insert.php" method="post" data-paying="<?php echo $paying ? '1' : '0'; ?>">
          <?php if (!empty($old['error'])) { ?>
          <p class="form-error" role="alert"><?php echo htmlspecialchars((string) $old['error']); ?></p>
          <?php } ?>
          <div class="field">
            <label class="lbl" for="threeword"><?php echo t('add.threeword'); ?></label>
            <input type="text" id="threeword" name="threeword" class="search-bar" placeholder="<?php echo t('common.placeholder_words'); ?>" value="<?php echo htmlspecialchars((string) ($old['threeword'] ?? '')); ?>" aria-describedby="threewordHint threewordError" autocomplete="off">
            <?php if (!$paying) { ?>
            <p class="field-hint" id="threewordHint"><?php echo t_raw('add.digits_paid'); ?></p>
            <?php } ?>
            <p class="field-error" id="threewordError" role="alert" hidden></p>
          </div>

          <div class="field">
            <label class="lbl" for="notification"><?php echo t('add.notification'); ?> <span class="opt" id="notificationOpt" hidden><?php echo t('add.optional'); ?></span></label>
            <textarea name="notification" id="notification" class="search-bar" placeholder="<?php echo t('add.notification_ph'); ?>" rows="4" aria-describedby="notificationError"><?php echo htmlspecialchars((string) ($old['notification'] ?? '')); ?></textarea>
            <p class="field-error" id="notificationError" role="alert" hidden></p>
          </div>

          <div class="field">
            <label class="lbl" for="linkthru" id="linkthruLabel" data-forward="<?php echo t('add.linkthru'); ?>" data-msglink="<?php echo t('add.link_url'); ?>"><?php echo $is_msglink ? t('add.link_url') : t('add.linkthru'); ?></label>
            <input type="text" name="linkthru" id="linkthru" class="search-bar" placeholder="<?php echo t('add.linkthru_ph'); ?>" value="<?php echo htmlspecialchars((string) ($old['linkthru'] ?? '')); ?>" aria-describedby="linkthruError">
            <p class="field-error" id="linkthruError" role="alert" hidden></p>
          </div>

          <?php if ($has_link_title) { ?>
          <div class="field" id="linkTitleField"<?php echo $is_msglink ? '' : ' hidden'; ?>>
            <label class="lbl" for="link_title"><?php echo t('add.link_title'); ?></label>
            <input type="text" name="link_title" id="link_title" class="search-bar" maxlength="255" placeholder="<?php echo t('add.link_title_ph'); ?>" value="<?php echo htmlspecialchars((string) ($old['link_title'] ?? '')); ?>" aria-describedby="linkTitleError">
            <p class="field-error" id="linkTitleError" role="alert" hidden></p>
          </div>
          <?php } ?>

          <div class="checks">
            <label><input type="checkbox" id="linkthruflag" name="linkthruflag"<?php echo $old_checked('linkthruflag'); ?>> <?php echo t('add.use_forward'); ?></label>
            <?php if ($has_link_title) { ?>
            <label><input type="checkbox" id="msglinkflag" name="msglinkflag"<?php echo $is_msglink ? ' checked' : ''; ?>> <?php echo t('add.use_msglink'); ?></label>
            <?php } ?>
            <label><input type="checkbox" id="private" name="private"<?php echo $old_checked('private', true); ?>> <?php echo t('add.private'); ?></label>
          </div>
          <?php // Kept simple: "Link to chat" / "Car chat" and open / closed are on the edit page, from the list ?>
          <p class="later-note" id="laterNote"><?php echo t('add.later_note'); ?></p>

          <div class="hint">
            <?php if($data['max_3wids']==0) {
              echo t_raw('add.subscribe_hint');
            }
            ?>
          </div>

          <div class="form-actions buttons">
            <button type="submit" id="submitBtn" class="btn btn-primary"><?php echo t('add.create'); ?></button>
            <div id="helperText"></div>
          </div>

          <input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>
          <input type='hidden' name='user_id' value='<?= $data["id"]; ?>'>
        </form>
      </div>
    </main>

    <div class="site-footer">
      <?php echo site_copyright(); ?>
    </div>

<?php echo i18n_js_all(); ?>
<script>
    // "Use forward URL" and "Message with link" exclude each other:
    // ticking one unticks the others
    (function () {
        var boxes = ['linkthruflag', 'msglinkflag']
            .map(function (id) { return document.getElementById(id); })
            .filter(function (el) { return el; });
        boxes.forEach(function (box) {
            box.addEventListener('change', function () {
                if (!box.checked) { return; }
                boxes.forEach(function (other) { if (other !== box) { other.checked = false; } });
            });
        });
    })();
</script>
<script src="js/3wid_add.js?v=2"></script>
<script>
    function handleGoogleLogin() {
        let userImageURL = 'path/to/user/image.jpg';
        if (userImageURL) {
            var el = document.getElementById('userPortrait');
            if (el) {
                el.src = userImageURL;
                el.style.display = 'block';
            }
        }
    }

    var loginIcon = document.querySelector('.login-icon');
    if (loginIcon) {
        loginIcon.addEventListener('click', function(e) {
            e.preventDefault();
            handleGoogleLogin();
            setTimeout(() => {
                window.location.href = '/login/options.php';
            }, 1000);
        });
    }
</script>
</html>
