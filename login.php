<?php


  session_start();
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  
  include 'php/functions.php';
  
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
<script async src="https://www.googletagmanager.com/gtag/js?id=G-TJWBJT8GJC"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-TJWBJT8GJC');
</script>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
   <style>
  
   
   </style>
</head>
<body>
   
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
        <?php echo $recent; ?>
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
    </script>
</html>
