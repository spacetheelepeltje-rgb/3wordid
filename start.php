<?php

  include 'php/functions_2.0.php';
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
	  

  

  
  
  
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
 echo $google_analytics_script; 
?>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <style>


.login-icon {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #007BFF; /* Blue background */
    padding: 10px;
    border-radius: 50%; /* Makes the background perfectly circular */
    z-index: 1000; /* Ensures it's above other elements */
}

.login-icon a {
    display: block;
    color: white; /* White foreground for the icon */
}

.login-icon i {
    font-size: 30px; /* Adjust size as needed */
}
	</style>	
    <title>3wordid.com</title>
</head>
<body>
   <div class="login-icon">
        <a href="login/login.php" title="Sign in with Google">
            <i class="material-icons">login</i>
        </a>
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
               </div>
                <input type="hidden" name="linkthruflag" value="<? =$linkthru ?>">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <!-- to public_notification.php or create.php -->
            </form>
        </section>
        <?php echo $recent; ?>
    </div>
    <!-- JavaScript to toggle profile menu -->
<script src="js/form_checks_start.js"></script>
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
		let userImageURL = 'user image'; // This should come from Google API or user data
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
