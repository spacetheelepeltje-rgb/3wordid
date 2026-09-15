<?php


  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
  
  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  
  $client_ip = get_client_ip();
  
  error_log('external message page ip ' . $client_ip . ' on ' . check_mobile());
  
  // Generate Google Login URL
  $loginUrl = $client->createAuthUrl();
  
  //$data = check_credentials($_SESSION,$_POST,$_GET);
  
  //if(!$data) {
	  error_log('no user credentials');
	  //header('location:3wid_list.php');  
  //}
  
  //error_log('message form user ' . json_encode($data));
 
 if(isset($_GET["threeword"])) {
	 $reply_to = $_GET["threeword"];
 } else {
	 $reply_to='';
	 header('location:' . $main_url);
 }
 
 $row = db_3wordid_get_threeword($reply_to);
 
 //$user_threeword_rows = db_3wid_get_3wids($data["id"]);

 //if($user_threeword_rows == NULL) {
	 //header('location:3wid_list.php?message=Create a 3WordID first to send a message from');  
 //}
 
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
            <center>
                <a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br>         
            </center>
        </div>
        <?php if($row['notification']=="") { ?>
			<h2>Leave your message here</h2>
		<?php echo $row['notification']; } ?>
        
        
        <div style="margin-top: 12px;" class="search-container">
			<label><?php if($row['notification']!="") {
				echo $row['notification'];
				} ?>
			</label>	
            <form id="3widForm" action="3wid_messageform_ext_process.php" method="post">
                <input disabled type="text" name="" id="threeword" class="search-bar" placeholder="Enter recipient 3WordID" style="margin-top: 10px;" value="to : <?php echo $reply_to; ?>">
                <input type="text" name="title" id="title" class="search-bar" placeholder="Enter a message title" style="margin-top: 10px;" value="">
                <textarea name="message" id="message" class="search-bar" placeholder="Enter your message here. If you want a reply provide contact info or log in and create your own 3WordID" rows="4" style="margin-top: 10px;"></textarea>	
                <!-- <label><input type="hidden" id="terms" name="terms" style="margin-top: 10px;margin-bottom: 10px;">&nbsp;I consent with storage of the info I provide</label> -->
                <div class="buttons" style="display: flex; align-items: center;">
                    <button type="submit" id="submitBtn">Send</button>
                    <div style="margin-top: 0;" id="helperText"></div>
                </div>               
                <input type='hidden' name='threeword' value='<?php echo $reply_to; ?>'>      
            </form>
        </div>
    </main>
    </main>
    <?php echo $footer; ?>
</body>
<script src="js/3wid_messageform_ext.js"></script>
<script>
	$('#threeword').trigger('keyup');
</script>	
</html>
