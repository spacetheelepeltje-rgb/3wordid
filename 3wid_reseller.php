<?php

  require_once 'php/functions.php';
  
  log_visit(__FILE__);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<style>
    main {
      display: flex;
      justify-content: center;
      padding: 20px;
    }
    .search-container {
      max-width: 600px;
      width: 100%;
    }
    </style>
</head>
<body>
    <header>
       <div class="top-right" id="userPortrait">
       <a href="login/login.php" class="login-icon" title="Log in">
	   <link rel="stylesheet" href="css/styles_3.css">	   
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>
    <img id="userPortrait" style="display:none;" alt="User Portrait">
</div>
    </header>
   <main>
   <div class="search-container-mid">
    <a href="index_2.php" class="logo-link">
      <img width="100" src="img/3wid_big.png" alt="3WordID Logo">
    </a>
    <h2>Become a Reseller of 3WordID.com</h2>
    <br><br>
	<ul>
	  <li>Log in and create a 3WordID</li>
	  <li>Find others that might want a 3WordID</li>
	  <li>Get them to <a href="https://3wordid.com/3wid_signup.php">sign</a> up and become a <a href="https://www.patreon.com/roboeconomics">patreon</a> using your code</li>
	  <li>When you sign up 20 people who also log in (so the email becomes available for matching) you get payout.</li>
	</ul>
	<br><br>
    <p>
      Every user that logs in is provided a reseller code. 
      You can help other people sign up for 3WordID.com and give you your reseller code. If they use it and pay their subscription, you will receive a kickback (20% payed out after 20 signups ~$48,-). If you sign up more than 20 at a time, you can get more. For large customers, send us an email at 
      <a href="mailto:3wordid@climatebabes.com">3wordid@climatebabes.com</a>.<br><br>
      More on marketing 3WordID <a href="https://x.com/climatebabes/status/1915321707327349114">here (X.com)</a>.
    </p>
  </div>
  
</main>
  <?php echo $footer; ?>
</body>
  
</html>

