<?php

  require_once 'php/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
       <div class="top-right" id="userPortrait">
       <a href="login/login.php" class="login-icon" title="Log in">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>
    <img id="userPortrait" style="display:none;" alt="User Portrait">
</div>
    </header>
    <main>
      <div class="search-container">
			<center><a href='index_2.php'><img width=100 src="img/3wid_big.png"></a><br>            
            </center>  
            If you have not payed your 3wordID will expire or can be taken by another user<br><br>
            - Future localization of 3WordID will be implemented by country, city<br>
            - The subscription is for the right to create 3WordIDs, so is not per active 3WordID.<br>
            - We use Google Auth and encrypt your email so we can not use it to send you emails<br>
            - In using the site the user agrees with us storing google supplied data and whatever you submit<br>      
            - No rights can be derived from using this website 
            - If you desire to use larger nrs of 3WordIDs get in touch with us via email<br> 
            - Integrations or special connection to your systems just make a request<br>
            - We may alter these terms in any way at any time as we see fit and will notify you on login.<br>         
            - We reserve the right to delete, revoke or repurpose your 3WordID without explanation<br><br>
            <a href="mailto:3wordid@climatebabes.com">Our email address</a>
           
        </div>
        <div>
			 <br><br>
            <a href='index.php'>Back to Homepage</a>
		</div>	
    </main>
  <?php echo $footer; ?>
</body>
  
</html>

