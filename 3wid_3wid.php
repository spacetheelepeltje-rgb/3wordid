<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';
  
  $data = current_user_from_session($conn);
	if ($data == NULL) {
		header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
		exit;
	}

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if ($id < 1) {
		header('Location: 3wid_list.php');
		exit;
	}
  
  
 
 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

 if(isset($_GET["id"])) {
    $id = $_GET["id"];
 } else {
    //header('location:' . $main_url);
 }
 
 if($data["id"]==4) {
        $row = db_3wordid_get($id);
     } else {
        $row = db_3wordid_get_user($id,$data["id"]);
    }
 
 if($row == NULL) {
     header('location:' . $main_url . "?message=" . rawurlencode(t_plain('edit.wrong_user')));
     die('fool');
     }

  $threeword_url = implode('.', explode(' ', $row["threeword"]));

  // Sent back by 3wid_update.php (e.g. empty Message field): show why, once
  $form_error = '';
  if (isset($_SESSION['edit_form_error']) && is_array($_SESSION['edit_form_error'])) {
      if ((int) ($_SESSION['edit_form_error']['id'] ?? 0) === (int) $row['id']) {
          $form_error = (string) ($_SESSION['edit_form_error']['error'] ?? '');
          foreach (['notification', 'linkthru', 'linkthruflag', 'emailform', 'private', 'link_title', 'msglinkflag'] as $k) {
              if (array_key_exists($k, $_SESSION['edit_form_error'])) {
                  $row[$k] = $_SESSION['edit_form_error'][$k];
              }
          }
      }
      unset($_SESSION['edit_form_error']);
  }

  // "Message with link" (only offered once the link_title column exists)
  $has_link_title = db_3wordid_has_link_title();
  $is_msglink = $has_link_title && (array_key_exists('msglinkflag', $row)
      ? ((int) $row['msglinkflag'] === 1 && (int) $row['linkthruflag'] !== 1 && (int) $row['emailform'] !== 1)
      : wid_is_msglink($row));

  // CSRF token for the update form
  $action_token = get_action_token();

  // Chat login (php/chat_login.php): only the owner of this 3WordID can manage it
  $chat_login_row = null;
  if ((int) $data['id'] === (int) $row['user_id'] && chat_login_ready() && chat_login_allowed($data)) {
      $chat_login_row = db_chat_login_get((int) $row['id']);
  }

  // The admin came from the admin pages: go back to that user's 3WordIDs
  $from_admin = ((int)$data["id"] === 4 && ($_GET['from'] ?? '') === 'admin');
  if ($from_admin) {
      $back_url = '3wid_user_3wids.php?user_id=' . (int)$row['user_id'];
      $back_label = t_plain('edit.back_user');
  } else {
      $back_url = '3wid_list.php';
      $back_label = t_plain('nav.back_list');
  }

?>  
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('edit.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      :root {
        --navy: #0F1C3F;
        --navy-2: #162756;
        --slate: #5B6478;
        --muted: #8A93A6;
        --line: #E6E3DC;
        --paper: #F7F6F3;
        --white: #FFFFFF;
        --gold: #B45309;
        --shadow: 0 10px 30px rgba(15,28,63,.08);
        --chip: #EFECE6;
        --well: #F3F1EC;
      }
      * { box-sizing: border-box; }
      html, body { margin: 0; padding: 0; }
      body {
        font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
        background: var(--paper);
        color: var(--navy);
        min-height: 100vh;
      }
      a { color: var(--navy); }
      .site-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 28px;
        background: rgba(247,246,243,.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--line);
        position: sticky;
        top: 0;
        z-index: 20;
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--navy);
        font-weight: 800;
        letter-spacing: -.03em;
        font-size: 20px;
      }
      .brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: var(--navy);
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
      }
      .header-actions { display: flex; align-items: center; gap: 10px; }
      .btn {
        border: 0;
        cursor: pointer;
        border-radius: 10px;
        font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 14px;
        font-size: 14px;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-2); }
      .btn-ghost { background: #fff; color: var(--navy); border: 1px solid var(--line); }
      .login-icon { color: var(--navy); display: inline-flex; padding: 6px; }
      .wrap { max-width: 720px; margin: 0 auto; padding: 28px 24px 64px; }
      .eyebrow {
        color: var(--gold);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .14em;
        margin-bottom: 8px;
      }
      h1 {
        margin: 0 0 8px;
        font-size: clamp(32px, 5vw, 44px);
        letter-spacing: -.04em;
        font-weight: 800;
      }
      .lede { margin: 0 0 22px; color: var(--slate); font-size: 16px; }
      .panel {
        background: var(--white);
        border: 1px solid #ECEAE4;
        border-radius: 16px;
        box-shadow: var(--shadow);
        padding: 22px;
      }
      .id-box {
        background: var(--well);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 18px;
      }
      .id-box strong { display: block; font-size: 18px; }
      .id-box code {
        display: inline-block;
        margin-top: 6px;
        background: var(--chip);
        border-radius: 6px;
        padding: 2px 6px;
        font-family: inherit;
        font-weight: 600;
        font-size: 13px;
        color: var(--slate);
      }
      .meta { color: var(--muted); font-size: 13px; margin: 10px 0 16px; }
      .qr-links { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
      label.field {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--slate);
        margin: 0 0 8px;
      }
      .search-bar, textarea.search-bar {
        width: 100%;
        border: 1px solid var(--line);
        background: #fff;
        border-radius: 10px;
        padding: 12px 14px;
        font: inherit;
        color: var(--navy);
        outline: none;
      }
      textarea.search-bar { resize: vertical; min-height: 110px; }
      .search-bar:focus, textarea.search-bar:focus { border-color: var(--navy); }
      .checks { display: grid; gap: 10px; margin: 16px 0 20px; }
      .checks label {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--navy);
        font-size: 14px;
      }
      label.field .opt { font-weight: 500; color: var(--muted); }
      label.field .opt[hidden] { display: none; }
      .link-title-field[hidden] { display: none; }
      .form-error {
        background: #FDF2F2;
        border: 1px solid #F3C7C7;
        color: #9B1C1C;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 16px;
      }
      .form-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
      }
      #helperText { color: var(--slate); font-size: 13px; }
      .site-footer {
        border-top: 1px solid var(--line);
        padding: 18px 28px 28px;
        color: var(--muted);
        font-size: 13px;
      }
      @media (max-width: 800px) {
        .wrap { padding-top: 20px; }
      }
      /* Chat settings link next to "Link to chat" (key: grey = chat login off, green = on) */
      .check-with-link { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 14px; }
      .chat-settings-link { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border: 1px solid var(--line); border-radius: 999px; background: #fff; color: var(--slate); font-size: 13px; font-weight: 600; text-decoration: none; }
      .chat-settings-link:hover { border-color: var(--navy); color: var(--navy); }
      .chat-settings-link .cl-key { color: var(--muted); }
      .chat-settings-link.cl-on { border-color: #9fd3b6; background: #e8f6ee; color: #14623C; }
      .chat-settings-link.cl-on .cl-key { color: #16794a; }
      .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url;?>">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        <div class="header-actions" id="userPortrait">
            <?php echo lang_switcher(); ?>
            <a class="btn btn-ghost" href="<?php echo htmlspecialchars($back_url); ?>"><?php echo htmlspecialchars($back_label); ?></a>
            <button type="submit" form="3widForm" id="submitBtn" class="btn btn-primary"><?php echo t('edit.update'); ?></button>           
        </div>
    </header>

    <main class="wrap">
        
        <h1><?php echo t('edit.h1'); ?></h1>
        <p class="lede"><?php echo t('edit.lede'); ?></p>

        <div class="panel search-container">
            <form id="3widForm" action="3wid_update.php" method="post">
                <div class="id-box">
                    <strong><?php echo htmlspecialchars($row["threeword"]); ?></strong>
                    <code><?php echo t('edit.url_is', ['url' => 'www.3WordID.com/' . $threeword_url]); ?></code>
                </div>

                <input disabled type="text" name="threeword" class="search-bar" placeholder="<?php echo t('edit.threeword_ph'); ?>" value="<?= htmlspecialchars($row["threeword"]); ?>" style="display:none;">

                <div class="meta">hash # <?php echo htmlspecialchars($row["hash"]); ?></div>
                <div class="qr-links">
                    <a class="btn btn-ghost" href="3wid_gen_qr.php?csrf_token=<?php echo $_SESSION['csrf_token'];?>&hash=<?= $row["hash"]; ?>" title='so https://www.3wordid.com/index.php?hash=<?= $row["hash"]; ?>'><?php echo t('edit.qr_hash'); ?></a>
                    <a class="btn btn-ghost" href="3wid_gen_qr_threeword.php?csrf_token=<?php echo $_SESSION['csrf_token'];?>&threeword=<?= $row["threeword"]; ?>" title='so https://www.3wordid.com/index.php?threeword=<?= $row["threeword"]; ?>'><?php echo t('edit.qr_readable'); ?></a>
                </div>

                <?php if (!empty($form_error)) { ?>
                <p class="form-error" role="alert"><?php echo htmlspecialchars($form_error); ?></p>
                <?php } ?>
                <label class="field" for="notification"><?php echo t('add.notification'); ?> <span class="opt" id="notificationOpt" hidden><?php echo t('add.optional'); ?></span></label>
                <textarea name="notification" id="notification" class="search-bar" placeholder="<?php echo t('add.notification_ph'); ?>" rows="4"><?= htmlspecialchars((string)$row["notification"]); ?></textarea>

                <label class="field" for="linkthru" id="linkthruLabel" style="margin-top:16px;" data-forward="<?php echo t('add.linkthru'); ?>" data-msglink="<?php echo t('add.link_url'); ?>"><?php echo $is_msglink ? t('add.link_url') : t('add.linkthru'); ?></label>
                <input type="text" name="linkthru" id="linkthru" class="search-bar" placeholder="<?php echo t('edit.linkthru_ph'); ?>" value="<?= htmlspecialchars((string)$row["linkthru"]); ?>">

                <?php if ($has_link_title) { ?>
                <div class="link-title-field" id="linkTitleField"<?php echo $is_msglink ? '' : ' hidden'; ?>>
                    <label class="field" for="link_title" style="margin-top:16px;"><?php echo t('add.link_title'); ?></label>
                    <input type="text" name="link_title" id="link_title" class="search-bar" maxlength="255" placeholder="<?php echo t('add.link_title_ph'); ?>" value="<?= htmlspecialchars((string) ($row["link_title"] ?? '')); ?>">
                </div>
                <?php } ?>

                <div class="checks">
                    <label><input <?php echo ($row["linkthruflag"] == 1 && $row["emailform"] != 1) ? 'checked' : ''; ?> type="checkbox" id="linkthruflag" name="linkthruflag"> <?php echo t('add.use_forward'); ?></label>
                    <?php if ($has_link_title) { ?>
                    <label><input <?php echo $is_msglink ? 'checked' : ''; ?> type="checkbox" id="msglinkflag" name="msglinkflag"> <?php echo t('add.use_msglink'); ?></label>
                    <?php } ?>
                    <?php if ($chat_login_row) { $cl_on = ((int) $chat_login_row['chat_login'] === 1); $cl_title = $cl_on ? t('cl.status_on') : t('cl.status_off'); ?>
                    <div class="check-with-link">
                        <label><input <?php echo $row["emailform"]== 1 ?'checked':''; ?> type="checkbox" id="emailform" name="emailform"> <?php echo t('edit.link_form'); ?></label>
                        <a class="chat-settings-link<?php echo $cl_on ? ' cl-on' : ''; ?>" href="3wid_chat_settings.php?id=<?php echo (int) $row['id']; ?>" title="<?php echo $cl_title; ?>"><?php echo chat_login_key_icon(15); ?><span><?php echo t('cl.edit_btn'); ?></span><span class="visually-hidden"> (<?php echo $cl_title; ?>)</span></a>
                    </div>
                    <?php } else { ?>
                    <label><input <?php echo $row["emailform"]== 1 ?'checked':''; ?> type="checkbox" id="emailform" name="emailform"> <?php echo t('edit.link_form'); ?></label>
                    <?php } ?>
                    <label><input <?php echo $row["private"]== 1 ?'checked':''; ?> type="checkbox" id="private" name="private"> <?php echo t('edit.private'); ?></label>
                </div>

                <div class="checks photo-opt">
                    <label><input <?php echo ((int) ($row["show_photo"] ?? 0) === 1) ? 'checked' : ''; ?> type="checkbox" id="show_photo" name="show_photo"<?php echo ((int) $data['id'] !== (int) $row['user_id']) ? ' disabled' : ''; ?> aria-describedby="show_photo_help"> <?php echo t('edit.show_photo'); ?></label>
                    <p class="meta" id="show_photo_help" style="margin:2px 0 0 26px;"><?php echo t('edit.show_photo_help'); ?><?php if ((int) $data['id'] === (int) $row['user_id'] && trim((string) ($data['picture'] ?? '')) === '') { echo ' ' . t('edit.show_photo_none'); } ?></p>
                    <input type="hidden" name="show_photo_form" value="1">
                </div>

                <div class="form-actions buttons">
                    <button type="submit" class="btn btn-primary"><?php echo t('edit.update'); ?></button>
                    <a class="btn btn-ghost" href="<?php echo htmlspecialchars($back_url); ?>"><?php echo htmlspecialchars($back_label); ?></a>
                    <div id="helperText"></div>
                </div>

                <input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>
                <input type='hidden' name='id' value='<?= (int)$row["id"]; ?>'>
                <input type='hidden' name='action_token' value='<?php echo htmlspecialchars($action_token); ?>'>
                <?php if ($from_admin) { ?>
                <input type='hidden' name='from' value='admin'>
                <?php } ?>
            </form>
        </div>
    </main>
     <footer class="site-footer">
        <div><?php echo $footer; ?></div>
        <?php echo site_copyright(); ?>
    </footer>
</body>
<?php echo i18n_js_all(); ?>
<script>
    // "Use forward URL", "Forward URL with message" and "Link to chat" exclude each other:
    // ticking one unticks the others
    (function () {
        var boxes = ['linkthruflag', 'msglinkflag', 'emailform']
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
<script src="js/3wid_3wid.js"></script>
<script>
    function handleGoogleLogin() {
        let userImageURL = 'path/to/user/image.jpg';
        if (userImageURL) {
            document.getElementById('userPortrait').src = userImageURL;
            document.getElementById('userPortrait').style.display = 'block';
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
