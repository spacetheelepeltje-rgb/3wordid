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
     header('location:3wid_list.php?message=Create a 3WordID first to send a message from');  
     exit;
 }
 
?>  
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('msg.title'); ?></title>
    <link rel="icon" type="image/png" href="3wid_1.png">
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
          <?php echo lang_switcher(); ?>
          <a class="btn btn-ghost" href="3wid_list.php"><?php echo t('nav.back_list'); ?></a>
          <a class="btn btn-primary" href="3wid_list.php"><?php echo t('nav.my_ids'); ?></a>
          <a href="<?php echo $loginUrl; ?>" class="icon-btn login-icon" title="<?php echo t('nav.login_title'); ?>">
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
        <p class="eyebrow"><?php echo t('msg.eyebrow'); ?></p>
        <h1><?php echo t('msg.h1'); ?></h1>
        <p class="subhead"><?php echo t('msg.subhead'); ?></p>

        <div class="panel search-container">
            <form id="3widForm" action="3wid_messageform_process.php" method="post">
                <div class="field">
                  <label class="lbl" for="from_id"><?php echo t('msg.from'); ?></label>
                  <select class="search-bar" name="from_id" id="from_id">
                    <?php
                    
					  /* if (isset ($wid_id)) {
					   foreach( $user_threeword_rows as $select_row) {
						   
						   if( $select_row["id"] == $wid_id) {
								echo "<option selected value='" .  $select_row["id"] . "'>xSend from : " .  $select_row["threeword"] . "</option>"; 
								
                           }
                           
					   }
					   
					   } else {
						*/   
                       foreach( $user_threeword_rows as $select_row) {
						   
						   if($select_row["id"] == $wid_id ) { 
							   $selected ="selected";
							   } else {
							   $selected ="";
							   }
						   
                           echo "<option value='" .  $select_row["id"] . "' " . $selected . ">" . t('msg.send_from', ['words' => $select_row["threeword"]]) . "</option>"; 
                           
                           }
                           
					   //}
                     ?>
                  </select>
                </div>
                <div class="field">
                  <label class="lbl" for="threeword"><?php echo t('msg.recipient'); ?></label>
                  <input type="text" name="threeword" id="threeword" class="search-bar" placeholder="<?php echo t('common.placeholder_words'); ?>" value="<?php echo $reply_to; ?>">
                </div>
                <div class="field">
                  <label class="lbl" for="title"><?php echo t('msg.title_label'); ?></label>
                  <input type="text" name="title" id="title" class="search-bar" placeholder="<?php echo t('msg.title_ph'); ?>" value="">
                </div>
                <div class="field">
                  <label class="lbl" for="message"><?php echo t('msg.message_label'); ?></label>
                  <textarea name="message" id="message" class="search-bar" placeholder="<?php echo t('msg.message_ph'); ?>" rows="4"></textarea>
                </div>
                <p class="hint"><?php echo t('msg.hint1'); ?></p>
                <label class="consent"><input type="checkbox" id="terms" name="terms"><?php echo t('msg.consent'); ?></label>
                <p class="hint"><?php echo t_raw('msg.hint2'); ?></p>
                <div class="buttons">
                    <button type="submit" id="submitBtn"><?php echo t('common.send'); ?></button>
                    <div id="helperText"></div>
                </div>
                <input type='hidden' name='action_token' value='<?php echo htmlspecialchars(get_action_token(), ENT_QUOTES, 'UTF-8'); ?>'>
            </form>
        </div>
    </main>
    
</body>
<?php echo i18n_js([
    'ok_recipient'  => 'msg.js_ok_recipient',
    'bad_recipient' => 'msg.js_bad_recipient',
    'invalid'       => 'msg.js_invalid',
    'clicked'       => 'msg.js_clicked',
    'need_title'    => 'msg.js_need_title',
    'need_message'  => 'msg.js_need_message',
    'need_3wid'     => 'msg.js_need_3wid',
    'need_terms'    => 'msg.js_need_terms',
]); ?>
<script src="js/3wid_messageform.js"></script>
<script>
    $('#threeword').trigger('keyup');
</script>	
</html>
