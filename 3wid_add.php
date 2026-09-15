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
            </center>  
        </div>
        <div class="search-container">
            <form id="3widForm" action="3wid_insert.php" method="post">
				<div style="margin-bottom: 4px;margin-left: 20px;"><a href="3wid_list.php">Back to list</a></div>
				<input type="text" id="threeword" name="threeword" class="search-bar" placeholder="Type your Three Word ID (not case sensitive)" style="margin-bottom: 10px;" value="">
                <textarea name="notification" id="notification" class="search-bar" placeholder="Enter your message here" rows="4"></textarea>
                <input type="text" name="linkthru" id="linkthru" class="search-bar" placeholder="Enter forward URL" style="margin-top: 10px;" value="">
                <!-- <input type="text" name="email" id="email" class="search-bar" placeholder="Enter linked email" style="margin-top: 10px;margin-bottom: 10px;" value=""> -->
                
              
                    &nbsp;<label><input  type="checkbox" id="linkthruflag" name="linkthruflag" style="margin-top: 10px;margin-bottom: 10px;"> Use forward URL</label><br>
                    &nbsp;<label><input  type="checkbox" id="emailform" name="emailform" style="margin-top: 10px;margin-bottom: 10px;"> Link 3WordID to message form (inactive)</label><br>  
                    &nbsp;<label><input  type="checkbox" id="private" name="private" style="margin-top: 10px;margin-bottom: 10px;" checked> Keep 3WordID private</label><br>
            
             
                <label><?php if($data['max_3wids']==0) {
					echo "You can subscribe to make sure your 3WordID is not taken by another user or deleted. It expires after two weeks. Subscription can be done via <a href='https://3wordid.com/3wid_signup.php'>The signup form</a>.";
					}
					?>
				</label>
                <div class="buttons" style="display: flex; align-items: center;">
					<button type="submit" id="submitBtn">Create</button>
					<div style="margin-top: 0;" id="helperText"></div>
				</div>
				<input type='hidden' name='user_token' value='<?php echo $_SESSION['user_token']; ?>'>
				<input type='hidden' id='subscribed' value='<?= $data["user_type"]; ?>'>
				<input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>
				<input type='hidden' name='user_id' value='<?= $data["id"]; ?>'>
				
            </form>
        </div>
    </main>
   <?php echo $footer; ?>
</body>
<script src="js/3wid_add.js"></script>
<script>
    function handleGoogleLogin() {
        let userImageURL = 'path/to/user/image.jpg';
        if (userImageURL) {
            document.getElementById('userPortrait').src = userImageURL;
            document.getElementById('userPortrait').style.display = 'block';
        }
    }

    document.querySelector('.login-icon').addEventListener('click', function(e) {
        e.preventDefault();
        handleGoogleLogin();
        setTimeout(() => {
            window.location.href = '/login/index.php';
        }, 1000);
    });
</script>
</html>
