<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';


  //error_log('add page ip ' . get_client_ip() . ' time ' . time() . ' session  ' . json_encode($_SESSION) . ' session id ' . session_id() . ' user token ' . $_SESSION["user_token"] );

  if(isset($_GET['user_token'])) {
      if(!isset($_SESSION['user_token'])) {
          $_SESSION['user_token']=$_GET['user_token'];
          }
      }
  
  $data = check_auth();
  
  //error_log('data auth ' . json_encode($data));
 
 if($data == NULL) {
    error_log('auth is null');
    $data = check_session();
 }
  
  //error_log('sesse ' . json_encode($sess));
  

   
   //error_log('add page token ' . json_encode($_SESSION) . ' session id ' . session_id()); 
   
  if($data == NULL) {
     error_log('add data is NULL '); 
     header('location:' . $main_url);
  } 
 
 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

 if(isset($_GET["id"])) {
    $user_id = $_GET["id"];
 } else {
    //header('location:' . $main_url);
 }
 
 error_log('max_3wids' . $data['max_3wids']);

?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create ID — 3WordID</title>
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
          <a class="btn btn-ghost" href="3wid_list.php">Back to list</a>  
          </a>
        </div>
      </div>
    </header>

    <main class="page">
      <p class="eyebrow">New record</p>
      <h1>Create 3WordID</h1>
      <p class="subhead">Choose three words, an optional message, and how this ID should behave.</p>

      <div class="panel">
        <form id="3widForm" action="3wid_insert.php" method="post">
          <div class="field">
            <label class="lbl" for="threeword">Three Word ID</label>
            <input type="text" id="threeword" name="threeword" class="search-bar" placeholder="first  ·  second  ·  third" value="">
          </div>

          <div class="field">
            <label class="lbl" for="notification">Notification</label>
            <textarea name="notification" id="notification" class="search-bar" placeholder="Enter your message here" rows="4"></textarea>
          </div>

          <div class="field">
            <label class="lbl" for="linkthru">Forward URL</label>
            <input type="text" name="linkthru" id="linkthru" class="search-bar" placeholder="Enter forward URL" value="">
          </div>

          <div class="checks">
            <label><input type="checkbox" id="linkthruflag" name="linkthruflag"> Use forward URL</label>
            <label><input type="checkbox" id="emailform" name="emailform"> Link 3WordID to message form (don't forget to activate it)</label>
            <label><input type="checkbox" id="private" name="private" checked> Keep 3WordID private (won't be published anywhere)</label>
          </div>

          <div class="hint">
            <?php if($data['max_3wids']==0) {
              echo "You can subscribe to make sure your 3WordID is not taken by another user or deleted. It expires after two weeks. Subscription can be done via <a href='https://3wordid.com/3wid_signup.php'>the signup form</a>.";
            }
            ?>
          </div>

          <div class="form-actions buttons">
            <button type="submit" id="submitBtn" class="btn btn-primary">Create</button>
            <div id="helperText"></div>
          </div>

          <input type='hidden' name='user_token' value='<?php echo $_SESSION['user_token']; ?>'>
          <input type='hidden' id='subscribed' value='<?= $data["user_type"]; ?>'>
          <input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>
          <input type='hidden' name='user_id' value='<?= $data["id"]; ?>'>
        </form>
      </div>
    </main>

    <div class="site-footer">
      
    </div>

<script src="js/3wid_add.js"></script>
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
                window.location.href = '/login/index.php';
            }, 1000);
        });
    }
</script>
</html>
