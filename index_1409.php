<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  //require_once 'cookie_consent.php';
  
  //db_3wid_log_update('banaan');
 
  /*
   * composer require stefangabos/zebra_session
   * 
   * use Zebra_Session;
	$session = new Zebra_Session('mysql:host=localhost;dbname=test', 'root', 'password');
	$session->set('key', 'value');
	echo $session->get('key');

	*/
  
  $client_ip = get_client_ip();
  
  //if('209.198.140.207' != $client_ip) {
  error_log('index page ip ' . $client_ip . ' on ' . check_mobile());
  //}
  // . ' session  ' . json_encode($_SESSION) . ' session id ' . session_id() );
  //error_log('check mobile ' . check_mobile());
  
  if(isset($_GET["hash"])) {
	header('location:3wid_show_qr.php?hash=' . $_GET["hash"]);
  }
 
	 // Destroy any existing session to ensure fresh login
	session_unset();
	session_destroy();
	session_start();

	// Log/count IP to database (optional)
    db_3wid_log_ip($client_ip);	

	//$stmt = $conn->prepare("INSERT INTO 3wordid_sessions (ip_address) VALUES (?)");
	//$stmt->execute([$client_ip]);

	// Generate Google Login URL
	$loginUrl = $client->createAuthUrl();
	
	//error_log('loginurl' . $loginUrl);
	
	$data = check_auth();
	
	if($data) {
		$image_url = $data["picture"];
	} else {
		$image_url = "";
	}
	
	//error_log(json_encode($data));
  
    // form token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    if(isset($_GET["message"])) {
		$helpertext = $_GET["message"]; 			
	} else {
		$helpertext = '<a href="https://x.com/climatebabes/status/1921113933592584660">What is this site? Explanation on X.com</a>'; //To create a 3WordID login top right or click the "Create" button.'; 
	}
	
  if(check_mobile()=="mobile") {
		$helpertext = 'Log in top right to create you own 3WordID'; 
  }
	
	
  //  htaccess
    if(isset($_GET["threewords"])) {
		$threewords = sanitizeInput($_GET["threewords"]);
		//error_log('index threewords = ' . $threewords);
		header('location:3wid_forward.php?threewords=' . $threewords);
	  } else {  
		$threewords = "";  
	  }
// <br>Currently in beta testing. Data may be deleted. <a href="mailto:3wordid@climatebabes.com">Contact us</a> for info or service requests.<br>

 $db_3wordid_list = db_3wordid_list_recent();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_3.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@climatebabes"> <!-- Replace with your X handle -->
    <meta name="twitter:title" content="3WordID - Your Unique Identity Solution">
    <meta name="twitter:description" content="Sign up for a 3WordID account to get a unique, easy-to-remember identity for all your online needs.">
    <meta name="twitter:image" content="https://3WordID.com/img/3wid_big.png"> <!-- Full URL to your image -->
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
            <center><a href="<?php echo $main_url;?>"><img width="100" src="img/3wid_big.png" alt="3wordid logo"></a><br><div id="helperText"><?php echo $helpertext; ?></div></center>
        </div>
      <form id="searchform" name="searchform" action="3wid_forward.php" method="POST">
		<div class="search-container">
        <input id="threewords" name="threewords" type="text" class="search-bar" placeholder="Enter the three word ID to lookup" value="<?= htmlspecialchars($threewords); ?>">
        <div class="buttons">
            <button type="submit" id="submitBtn">Search</button>  
            <button type="button" id="createBtn" data-share-url="<?php echo $loginUrl; ?>">Create you own! Try this!</button><br>
        </div>
		</div>
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"><br><br>
</form>
<h3>Using three words you can find an URL, notification or message box! You can even message between three word IDs, but atm not encrypted, so our logs show what you message.</h3><br>
 <table>
		   
           <thead>
                <tr>
                    <th>Currently Public 3WordIDs <br></th>
                 
                </tr>
            </thead>
<?php
				$count_rows = 0;
				foreach($db_3wordid_list as $db_3wordid_item) {
					$count_rows++;
					
				?>
                <tr>
                    <td><?php echo $db_3wordid_item['views']; ?> <a href="<?php echo $db_3wordid_item['threeword'] . '">' . $db_3wordid_item['threeword']; ?></a></td>
                   
                   <!-- <td>
                        <a style="text-decoration: none;" <?php echo $count_rows; ?> href="<?php echo $db_3wordid_item['linkthru']; ?> " class="link-icon" title="Visit Forward Site : <?php echo $db_3wordid_item['linkthru']; ?>">
                        
                        </a>
                    </td>
                   -->
                </tr>
                <?php
				}
?>
  </tbody>
        </table>
<!-- <div class="search-container-mid">
	  <a href="<?php echo $loginUrl; ?>">Log in to sign up</a><br>
	  <a href="https://3wordid.com/3wid_top_10.php">Current top 10</a><br>
	  <a href="https://3wordid.com/3wid_reseller.php">Become Reseller</a><br><br>
 </div>
 -->
    </main>
    <?php echo $footer; ?>
</body>
<script src="js/3wid_index_2.js"></script>
<script>
    function handleGoogleLogin() {
        // Redirect to Google login URL
        window.location.href = '<?php echo $loginUrl; ?>';
    }

    // Attach the login handler to the click event
    document.querySelector('.login-icon')?.addEventListener('click', function(e) {
        e.preventDefault();
        handleGoogleLogin();
    });

    // Ensure portrait is shown if image_url exists
    document.addEventListener('DOMContentLoaded', function() {
        const userPortrait = document.getElementById('userPortrait');
        if (userPortrait && '<?php echo $image_url; ?>') {
            userPortrait.style.display = 'block';
        }
    });
    
    document.getElementById("threewords").addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
      event.preventDefault(); // Prevent default behavior (e.g., newline in textarea)
      document.getElementById("searchform").submit(); // Submit the form
    }
  });
</script>    
</html>
