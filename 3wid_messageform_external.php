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
 
 $user_threeword_rows = db_3wid_get_3wids($data["id"]);

 if($user_threeword_rows == NULL) {
	 header('location:3wid_list.php?message=Create a 3WordID first to send a message from');  
 }
 
 //error_log('messageform threeword found ' . json_encode($user_threeword_rows));

?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Page - 3WordID.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
		<div class="top-right" id="userContainer">
      
            <a href="<?php echo $loginUrl; ?>" class="login-icon" title="Log in (not all functions work on mobile devices)">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
				<path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
				<polyline points="8 7 13 12 8 17"></polyline>
				<line x1="13" y1="12" x2="1" y2="12"></line>
			  </svg>
            </a>
       
		</div>
    </header>
     <main>
        <div class="logo">
            <!--<center>
                <a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br>         
            </center>  -->
        </div>
        <div style="margin-bottom: 20px;margin-left: 20px;"><a href="3wid_list.php?user_token=<?php echo $data['token']; ?>">Back to list</a><br>
        <div style="margin-top: 12px;" class="search-container">
            <form id="3widForm" action="3wid_messageform_process.php" method="post">
				<select class="search-bar" name="from_id">
				  <?php
				     foreach( $user_threeword_rows as $select_row) {
						 echo "<option value='" .  $select_row["id"] . "'>Send from : " .  $select_row["threeword"] . "</option>"; 
						 }
				   ?>
				</select>   
                <input type="text" name="threeword" id="threeword" class="search-bar" placeholder="Enter recipient 3WordID" style="margin-top: 10px;" value="<?php echo $reply_to; ?>">
                <input type="text" name="title" id="title" class="search-bar" placeholder="Enter a message title" style="margin-top: 10px;" value="">
                <textarea name="message" id="message" class="search-bar" placeholder="Enter your message here" rows="4" style="margin-top: 10px;"></textarea>
				<p  style="margin-top: 10px;">You can only send one message until you get a reply. Your google login image will be shared with the recipient. By checking the below checkbox you consent in sharing your google user image and (re-)confirm you consent in us storing the identification data google shares with us when you log in to this website.</p>
                &nbsp;<label><input type="checkbox" id="terms" name="terms" style="margin-top: 10px;margin-bottom: 10px;">&nbsp;I consent with the above</label>
                <div class="buttons" style="display: flex; align-items: center;">
                    <button type="submit" id="submitBtn">Send</button>
                    <div style="margin-top: 0;" id="helperText"></div>
                </div>               
                
                <input type='hidden' name='csrf_token' value='<?php echo $data['csrf_token']; ?>'>
              
            </form>
        </div>
    </main>
    </main>
    <footer>
        <div class="footer-links">
            <a href="#">About</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
        </div>
    </footer>
</body>
<script src="js/3wid_messageform.js"></script>
<script>
	$('#threeword').trigger('keyup');
</script>	
</html>
