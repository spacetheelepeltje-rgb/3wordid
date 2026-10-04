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

  //$loginUrl = $client->createAuthUrl();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a message — 3WordID</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --divider: #F0EEE8;
            --card-line: #ECEAE4;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
            background: var(--paper);
            color: var(--navy);
            font-size: 16px;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
        .header-inner,
        .page,
        .site-footer .footer-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 16px 26px;
        }
        .header-inner {
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
            flex: 0 0 auto;
        }
        .mark {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--navy);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 34px;
        }
        .mark img { width: 34px; height: 34px; object-fit: cover; display: block; }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-left: auto;
            margin-right: 12px;
        }
        .nav-links a {
            color: var(--slate);
            font-size: 14px;
            font-weight: 500;
        }
        .nav-links a:hover { color: var(--navy); }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            padding: 0 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
        }
        .btn-primary,
        #submitBtn {
            background: var(--navy);
            color: #fff;
            border-color: var(--navy);
        }
        .btn-primary:hover,
        #submitBtn:hover {
            background: var(--navy-hover);
            border-color: var(--navy-hover);
            color: #fff;
        }
        .btn-ghost {
            background: var(--white);
            color: var(--navy);
            border-color: var(--line);
        }
        .btn-ghost:hover { background: var(--well); }
        .icon-btn,
        .login-icon {
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
        .icon-btn svg,
        .login-icon svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .page {
            padding-top: 28px;
            padding-bottom: 48px;
            flex: 1;
            width: 100%;
        }
        .eyebrow {
            color: var(--gold);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin: 0 0 8px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 40px;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.1;
            color: var(--navy);
        }
        .subhead {
            margin: 0 0 24px;
            color: var(--slate);
            font-size: 16px;
            max-width: 920px;
        }
        .flash {
            background: var(--white);
            border: 1px solid var(--card-line);
            border-left: 4px solid var(--gold);
            border-radius: 14px;
            padding: 12px 14px;
            color: var(--slate);
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 16px;
            max-width: 720px;
        }
        .panel {
            background: var(--white);
            border: 1px solid var(--card-line);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 22px;
            max-width: 720px;
        }
        .field { margin: 0 0 12px; }
        .field label,
        label.lbl {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--slate);
            margin: 0 0 6px;
        }
        .search-bar,
        input.search-bar,
        textarea.search-bar {
            width: 100%;
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 11px 12px;
            font: 500 15px/1.4 Inter, system-ui, sans-serif;
            color: var(--navy);
            outline: none;
            box-shadow: var(--shadow);
        }
        textarea.search-bar { min-height: 140px; resize: vertical; }
        .search-bar:focus { border-color: var(--navy); }
        input:disabled,
        .search-bar:disabled {
            background: var(--well);
            color: var(--slate);
            box-shadow: none;
        }
        .hint {
            margin: 14px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
            max-width: 720px;
        }
        .hint a { color: var(--navy); font-weight: 600; }
        .buttons {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 4px;
        }
        #submitBtn {
            height: 40px;
            padding: 0 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        #helperText {
            color: var(--muted);
            font-size: 13px;
        }
        .site-footer {
            color: var(--muted);
            font-size: 13px;
            border-top: 1px solid var(--line);
        }
        .site-footer a { color: var(--muted); margin: 0 8px; }
        .site-footer a:hover { color: var(--navy); }
        @media (max-width: 800px) {
            .header-inner, .page, .site-footer .footer-inner { padding-left: 24px; padding-right: 24px; }
            h1 { font-size: 32px; }
            .nav-links { display: none; }
            .header-actions .btn-primary { display: none; }
            .panel { padding: 16px; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?php echo $main_url; ?>">
                <span class="mark" aria-hidden="true"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
                <span>3WordID</span>
            </a>
            <nav class="nav-links">
                <a href="https://x.com/climatebabes/status/1921113933592584660">Docs on X.com</a>
                <a href="https://github.com/spacetheelepeltje-rgb/3wordid">Open Source on Github</a>
            </nav>
            <div class="header-actions" id="userContainer">
                <a href="<?php echo $loginUrl; ?>" class="btn btn-primary">Log in/Create ID</a>
            </div>
        </div>
    </header>

    <main class="page">
        <p class="eyebrow">Message form</p>
        <h1><?php echo $has_note ? 'Leave a message' : 'Leave your message here'; ?></h1>
        <?php if (!$has_note): ?>
        <p class="subhead">Send a note to <strong><?php echo $reply_to_safe; ?></strong>. Add contact details or log in and send a direct message if you want a reply.</p>
        <?php endif; ?>
        <?php if ($has_note): ?>
        <div class="flash"><?php echo $notification; ?></div>
        <?php endif; ?>

        <div class="panel search-container">
            <form id="3widForm" action="3wid_messageform_ext_process.php" method="post">
                <div class="field">
                    <label class="lbl" for="threeword">Recipient</label>
                    <input disabled type="text" name="" id="threeword" class="search-bar" placeholder="first  ·  second  ·  third" value="to : <?php echo $reply_to_safe; ?>">
                </div>
                <div class="field">
                    <label class="lbl" for="title">Title</label>
                    <input type="text" name="title" id="title" class="search-bar" placeholder="Enter a message title" value="">
                </div>
                <div class="field">
                    <label class="lbl" for="message">Message</label>
                    <textarea name="message" id="message" class="search-bar" placeholder="Enter your message here. If you want a reply provide contact info or log in and create your own 3WordID" rows="4"></textarea>
                </div>
                <div class="buttons">
                    <button type="submit" id="submitBtn" class="btn btn-primary">Send</button>
                    <div id="helperText"></div>
                </div>
                <input type="hidden" name="threeword" value="<?php echo $reply_to_safe; ?>">
            </form>
        </div>

        <p class="hint">Want a direct reply? <a href="<?php echo $loginUrl; ?>">Log in and create your own 3WordID</a></p>
    </main>

    <footer class="site-footer">
        <div class="footer-inner"><?php echo $footer; ?></div>
    </footer>
</body>
<script src="js/3wid_messageform_ext.js"></script>
<script>
    $('#threeword').trigger('keyup');
</script>
</html>
