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
            --shadow: 0 10px 30px rgba(15,28,63,.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, system-ui, sans-serif;
            background: var(--paper);
            color: var(--navy);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 28px;
            background: var(--paper);
            border-bottom: 1px solid var(--line);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--navy);
            font-weight: 800;
            letter-spacing: -0.04em;
        }
        .brand-mark {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--navy);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .brand-mark img { width: 34px; height: 34px; object-fit: cover; }
        .header-actions { display: flex; align-items: center; gap: 10px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 44px;
            padding: 0 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            font-family: inherit;
        }
        .btn-primary { background: var(--navy); color: #fff; }
        .btn-primary:hover { background: var(--navy-hover); }
        .btn-ghost {
            background: var(--white);
            color: var(--navy);
            border-color: var(--line);
        }
        .btn-ghost:hover { background: #f3f1ec; }
        .login-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: var(--white);
            color: var(--navy);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .wrap {
            width: 100%;
            max-width: 560px;
            margin: 48px auto 0;
            padding: 0 24px 48px;
            flex: 1;
        }
        .eyebrow {
            color: var(--gold);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 8px;
        }
        .sub {
            color: var(--slate);
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .flash {
            background: var(--white);
            border: 1px solid #ECEAE4;
            border-left: 4px solid var(--gold);
            border-radius: 14px;
            padding: 12px 14px;
            color: var(--slate);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .card {
            background: var(--white);
            border: 1px solid #ECEAE4;
            border-radius: 16px;
            padding: 22px;
            box-shadow: var(--shadow);
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 6px;
        }
        input[type="text"],
        textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px;
            font: inherit;
            margin-bottom: 14px;
            background: var(--white);
            color: var(--navy);
        }
        input[type="text"] { height: 44px; padding-top: 0; padding-bottom: 0; }
        input:focus,
        textarea:focus {
            outline: 2px solid var(--navy);
            outline-offset: 1px;
        }
        input:disabled {
            background: #F3F1EC;
            color: var(--slate);
        }
        textarea { min-height: 140px; resize: vertical; }
        .buttons {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 4px;
        }
        #helperText {
            color: var(--muted);
            font-size: 13px;
        }
        .hint {
            color: var(--muted);
            font-size: 13px;
            margin-top: 16px;
        }
        .hint a { color: var(--navy); font-weight: 600; text-decoration: none; }
        footer {
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            padding: 24px;
        }
        footer a { color: var(--muted); margin: 0 8px; text-decoration: none; }
        @media (max-width: 800px) {
            .site-header { padding: 14px 20px; }
            h1 { font-size: 28px; }
            .wrap { margin-top: 32px; }
            .header-actions .btn-primary { display: none; }
        }
    </style>
</head>
<body>
     <header class="site-header">
        <a class="brand" href="<?php echo $main_url;?>">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        <nav class="nav-links">
            <a href="https://x.com/climatebabes/status/1921113933592584660">Docs on X.com</a> <a href="https://github.com/spacetheelepeltje-rgb/3wordid">Open Source on Github</a>               
        </nav>
        <div class="header-actions" id="userContainer">
            <a href="<?php echo $loginUrl; ?>" class="btn btn-primary">Log in/Create ID</a>
            <!-- <a href="<?php echo $loginUrl; ?>" class="login-icon" title="Log in (not all functions work on mobile devices)">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
                <path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
                <polyline points="8 7 13 12 8 17"></polyline>
                <line x1="13" y1="12" x2="1" y2="12"></line>
              </svg> -->
            </a>
        </div>
    </header>


    <main class="wrap">
        <div class="eyebrow">Message Form</div>
        <h1><?php echo $has_note ? 'Leave a message' : 'Leave your message here'; ?></h1>
        <?php if (!$has_note): ?>           
        <p class="sub">Send a note to <strong><?php echo $reply_to_safe; ?></strong>. Add contact details or log in and send a direct message if you want a reply.</p>
        <?php endif; ?>
        <div class="flash"><?php echo $notification; ?></div>

        <div class="card search-container">
            <form id="3widForm" action="3wid_messageform_ext_process.php" method="post">
                <label for="threeword">Recipient</label>
                <input disabled type="text" name="" id="threeword" class="search-bar" placeholder="Enter recipient 3WordID" value="to : <?php echo $reply_to_safe; ?>">

                <label for="title">Title</label>
                <input type="text" name="title" id="title" class="search-bar" placeholder="Enter a message title" value="">

                <label for="message">Message</label>
                <textarea name="message" id="message" class="search-bar" placeholder="Enter your message here. If you want a reply provide contact info or log in and create your own 3WordID" rows="4"></textarea>

                <div class="buttons">
                    <button type="submit" id="submitBtn" class="btn btn-primary">Send</button>
                    <div id="helperText"></div>
                </div>

                <input type="hidden" name="threeword" value="<?php echo $reply_to_safe; ?>">
            </form>
        </div>

        <p class="hint">Want a receive a direct reply? <a href="<?php echo $loginUrl; ?>">Log in and create your own 3WordID!</a></p>
    </main>

    <footer>
        <?php echo $footer; ?>
    </footer>
</body>
<script src="js/3wid_messageform_ext.js"></script>
<script>
    $('#threeword').trigger('keyup');
</script>
</html>
