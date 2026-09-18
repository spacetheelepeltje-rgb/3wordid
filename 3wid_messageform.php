<?php

  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
  
  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  //error_log('messageform page');
  
  // Generate Google Login URL
  $loginUrl = $client->createAuthUrl();
  
  $data = check_credentials($_SESSION,$_POST,$_GET);
  
  if(!$data) {
      error_log('no user credentials');
      header('location:3wid_list.php');  
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

 if($user_threeword_rows == NULL || ($reply_to == '')) {
     header('location:3wid_list.php?message=Create a 3WordID first to send a message from');  
 }
 
?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send message — 3WordID</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
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
        line-height: 0.78;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: -0.04em;
        flex: 0 0 34px;
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
        height: 36px;
        padding: 0 14px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        font-family: inherit;
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
      .icon-btn svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
      }

      .page { padding-top: 28px; padding-bottom: 48px; }
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
      .panel {
        background: var(--white);
        border: 1px solid var(--card-line);
        border-radius: 16px;
        box-shadow: var(--shadow);
        padding: 22px;
        max-width: 720px;
      }
      .field { margin: 0 0 12px; }
      .field label.lbl {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--slate);
        margin: 0 0 6px;
      }
      .search-bar,
      select.search-bar,
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
      textarea.search-bar { min-height: 120px; resize: vertical; }
      .search-bar:focus {
        border-color: var(--navy);
      }
      .hint {
        margin: 14px 0 10px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.55;
      }
      .consent {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        color: var(--slate);
        font-size: 14px;
        margin: 0 0 16px;
      }
      .consent input { margin-top: 3px; }
      .buttons {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
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
      @media (max-width: 800px) {
        .header-inner, .page, .site-footer .footer-inner { padding-left: 24px; padding-right: 24px; }
        h1 { font-size: 32px; }
        .header-actions .btn-ghost span.full { display: none; }
        .panel { padding: 16px; }
      }
    </style>
</head>
<body>
    <header class="site-header">
      <div class="header-inner">
        <a class="brand" href="index.php">
          <span class="mark" aria-hidden="true"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
          <span>3WordID</span>
        </a>
        <div class="header-actions" id="userContainer">
          <a class="btn btn-ghost" href="3wid_list.php?user_token=<?php echo $data['token']; ?>">Back to list</a>
          <a class="btn btn-primary" href="3wid_list.php?user_token=<?php echo $data['token']; ?>">My IDs</a>
          <a href="<?php echo $loginUrl; ?>" class="icon-btn login-icon" title="Log in (not all functions work on mobile devices)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
              <path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
              <polyline points="8 7 13 12 8 17"></polyline>
              <line x1="13" y1="12" x2="1" y2="12"></line>
            </svg>
          </a>
        </div>
      </div>
    </header>

    <main class="page">
        <p class="eyebrow">Messages</p>
        <h1>Send a message</h1>
        <p class="subhead">You can send and receive messages from all the 3 Word IDs you define.</p>

        <div class="panel search-container">
            <form id="3widForm" action="3wid_messageform_process.php" method="post">
                <div class="field">
                  <label class="lbl" for="from_id">From</label>
                  <select class="search-bar" name="from_id" id="from_id">
                    <?php
                    
					   if (isset ($wid_id)) {
					   foreach( $user_threeword_rows as $select_row) {
						   
						   if( $select_row["id"] == $wid_id) {
								echo "<option selected value='" .  $select_row["id"] . "'>Send from : " .  $select_row["threeword"] . "</option>"; 
								
                           }
                           
					   }
					   
					   } else {
                       foreach( $user_threeword_rows as $select_row) {
                           echo "<option value='" .  $select_row["id"] . "'>Send from : " .  $select_row["threeword"] . "</option>"; 
                           }
                           
					   }
                     ?>
                  </select>
                </div>
                <div class="field">
                  <label class="lbl" for="threeword">Recipient</label>
                  <input type="text" name="threeword" id="threeword" class="search-bar" placeholder="first  ·  second  ·  third" value="<?php echo $reply_to; ?>">
                </div>
                <div class="field">
                  <label class="lbl" for="title">Title</label>
                  <input type="text" name="title" id="title" class="search-bar" placeholder="Enter a message title" value="">
                </div>
                <div class="field">
                  <label class="lbl" for="message">Message</label>
                  <textarea name="message" id="message" class="search-bar" placeholder="Enter your message here" rows="4"></textarea>
                </div>
                <p class="hint">You can only send one message until you get a reply. Your google login image will be shared with the recipient. By checking the below checkbox you consent in sharing your google user image and (re-)confirm you consent in us storing the identification data google shares with us when you log in to this website.</p>
                <label class="consent"><input type="checkbox" id="terms" name="terms">I consent with the above</label>
                <div class="buttons">
                    <button type="submit" id="submitBtn">Send</button>
                    <div id="helperText"></div>
                </div>
                <input type='hidden' name='csrf_token' value='<?php echo $data['csrf_token']; ?>'>
            </form>
        </div>
    </main>
    
</body>
<script src="js/3wid_messageform.js"></script>
<script>
    $('#threeword').trigger('keyup');
</script>	
</html>
