<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';


  //error_log('add page ip ' . get_client_ip() . ' time ' . time() . ' session  ' . json_encode($_SESSION) . ' session id ' . session_id() . ' user token ' . $_SESSION["user_token"] );

  if(isset($_GET['user_token'])) {
	  if(!isset($_SESSION['user_token'])) {
		  $_SESSION['user_token']=$_GET['user_token'];
		  }
	  }
  
  $data = check_credentials($_SESSION, $_POST, $_GET);

  if($data == NULL) {
	 error_log('add data is NULL '); 
	 header('location:' . $main_url);
  } 
 
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

 
 error_log('max_3wids' . $data['max_3wids']);

?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Page - 3WordID.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
		<!--
        <div class="top-right" id="userPortrait">
            <a href="#" class="login-icon" title="To Account page">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <img id="userPortrait" style="display:none;" alt="User Portrait">
        </div>
        -->
    </header>
    <main>
        <div class="logo">
            <center>
                <a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br>
                <h2>Privacy Consent Form</h2>  
                Please read and consent before proceeding       
            </center>  
        </div>
        <div class="search-container">
            <form id="3widForm" action="3wid_privacy_consent_process.php" method="post">
			<label>Data policy : To offer you the functionality of 3WordID we need to store some personal data supplied by Google 
			and by yourself. First name and last name, a link to your avatar and your email address (which we hash 
			so we can't actuall use it). Also messages you send and receive, as well as any data you leave in the 
			notification field of your 3WordIDs.
			<br><br>
			Functionality policy : If you check the box below you accept our service as it is offered. Functionality may be changed at any time 
			in any way in the best interest of the site. We will make our best effort to offer the services we promise.
			<br><br>
			We may from time to time reactivate this page to allow you to 
			reconfirm you agree with us storing your data. Of course we will only share any data with third parties 
			if we are legally obligated to, not for commercial purposes. If you consent in this please check the box below.
			</label><br><br>
			&nbsp;<label><input  type="checkbox" id="consent" name="consent" > I consent in the storage of my data as described in the data policy above and agree with the functionality policy above</label><br><br>  
             
               
                <div class="buttons" style="display: flex; align-items: center;">
					<button type="submit" id="submitBtn">Submit</button>
					<div style="margin-top: 0;" id="helperText"></div>
				</div>
				<input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>
				<input type='hidden' name='user_id' value='<?= $data["id"]; ?>'>
				
            </form>
        </div>
    </main>
   <?php echo $footer; ?>
</body>
<script src="js/3wid_privacy_consent.js"></script>
</html>
