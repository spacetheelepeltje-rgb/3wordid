<?php

  include 'php/functions.php';
  session_start();
  
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  
  if(isset($_SESSION['user_token'])) {
	  $logged_in = 1;
	  } else {
      
      $logged_in = 0;
      }
  

  
  $recent = "";
  //getfiverecent();
  
  if(isset($_GET["threewords"])) {
	    $words = urldecode($_GET['threewords']);
        $words = str_replace('+', ' ', $words);
		$threewords = sanitizeInput($_GET["threewords"]);
	  } else {
		  
		$threewords = "";  
	  }
	  
  $wids = count(scandir('./3wids'))-2;	
  
  if ($wids > 2000) {exit();}  
  
  
  
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<!-- Google tag (gtag.js) -->
<?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
   <style>
  .google-login {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
}

.material-icons {
    font-size: 24px; /* Adjust size as needed */
    color: #ffffff; /* Google's blue */
}

.user-portrait {
    width: 40px; /* Size of the portrait */
    height: 40px;
    border-radius: 50%; /* Makes it circular */
    margin-left: 10px; /* Space from the icon */
}
   
   </style>
</head>
<body>
<?php
  if($logged_in) { ?>	
<div class="settings-icon" onclick="location.href='/login/index.php';" title="login with Google">
<?php } else { ?>
<div class="settings-icon" onclick="location.href='/login/logout.php';" title="log out">	
<?php }?>	
    <i class="material-icons" title="Sign in with Google">login</i>
    <img id="userPortrait" class="user-portrait" src="" alt="User Portrait" style="display:none;">
</div>
    <div class="container">
		<a href='index.php'><img width=100 src="img/3wid_big.png"></a>     
		<h2>3WordID : Find links and info with only three words!</h2>
		  
        <section>
            <form method="post" action="" name="formSearch" id="formSearch">
                <div class="threefields">
					
                    <input type="text" id="myThreeWordID" name="threewords" placeholder="Enter three words not case sensitive"  class="myInput" value="<?php echo $threewords; ?>" required maxlength="200">
                </div>
                
               
                  <input type="checkbox" name="nolinkthru" id="noLinkThru" unchecked> No redirect 
                 
                 
                <div class="threefields">
                    <input type="submit" value="Search/Create" name="SearchLogin" id="submitBtn">
                    <div id="helperText"></div> 
                    
                    <br>
					<font color=grey>Try:</font><br>
                    <a href='My personal notification'>"My personal notification"</a><br>
                    <a href='Prince of Peace'>"Prince of Peace"</a><br>
                    <a href='Our Delicious Menu'>"Our Delicious Menu"</a><br>
                    <a href='Blond Cabin Girl'>"Blond Cabin Girl"</a><br>
                    
                    <br>
                    You can also use https://3WordID.com/three words here <br><br>
                    <a href='feedback.php'>Leave Feedback</a><br>
                  
                    </font>
                    <br><font color='lightgrey'>
						  (copyright F. Rincker 2025, The Hague)<br><br>
						
						<?php echo $wids; ?></font>
                </div>
                <input type="hidden" name="linkthruflag" value="<? =$linkthru ?>">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <!-- to public_notification.php or create.php -->
            </form>
        </section>
        <?php echo $recent; ?><a href='index_2.php'>new site</a>
    </div>
    <!-- JavaScript to toggle profile menu -->
 
 <script src="js/form_checks_index.js"></script>
</body>
 <script>
        document.addEventListener('DOMContentLoaded', function() {
   
					 // Get the input element by its ID
			let input = document.getElementById('myThreeWordID');
			
			// If the element exists, focus on it
			if (input) {
				input.focus();
			}
		});
		
function handleGoogleLogin() {
		// Here you would implement Google Sign-In functionality
		// This is a mock function to show how you might show the portrait
		let userImageURL = 'path/to/user/image.jpg'; // This should come from Google API or user data
		if (userImageURL) {
			document.getElementById('userPortrait').src = userImageURL;
			document.getElementById('userPortrait').style.display = 'block';
		}
}

// Attach the login handler to the click event
document.querySelector('.google-login').addEventListener('click', function(e) {
    // Prevent the default action of changing the page
    e.preventDefault();
    handleGoogleLogin(); // Your login logic
    // Optionally, after login, redirect or handle as needed
    setTimeout(() => {
        window.location.href = '/login/index.php';
    }, 1000); // Delay for demonstration, remove or adjust in real use
});
    </script>
</html>
