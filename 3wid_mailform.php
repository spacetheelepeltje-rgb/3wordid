<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  error_log('mailform page');

 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

 if(isset($_GET["id"])) {
	$id = $_GET["id"];
 } else {
	header('location:' . $main_url);
 }
 
 $row = db_3wordid_get($id);

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
        <div class="top-right" id="userPortrait">
            <a href="#" class="login-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <img id="userPortrait" style="display:none;" alt="User Portrait">
        </div>
    </header>
     <main>
        <div class="logo">
            <center>
                <a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br>         
            </center>  
        </div>
        <div class="search-container">
            <form id="3widForm" action="3wid_mailform_process.php" method="post">
				<div style="margin-bottom: 12px;margin-left: 20px;"><h2><?= $row["threeword"]; ?></h2></div>
                <textarea name="mailmessage" id="mailmessage" class="search-bar" placeholder="Enter your message here" rows="4"></textarea>
                <input type="text" name="title" id="title" class="search-bar" placeholder="Enter a message title" style="margin-top: 10px;" value="">
                <input type="text" name="email" id="email" class="search-bar" placeholder="Enter your email address" style="margin-top: 10px;" value="">
              <!--  <input type="text" name="email" id="email" class="search-bar" placeholder="Enter linked email" style="margin-top: 10px;margin-bottom: 10px;" value="<?= $row["email"]; ?>"> -->
                
                    <div style="margin-top: 10px;" id="email_consent"><?php echo $email_consent; ?></div>
                    &nbsp;<label><input type="checkbox" id="terms" name="terms" style="margin-top: 10px;margin-bottom: 10px;">&nbsp;agree with terms</label><br>
                   <!-- &nbsp;<label><input <?php echo $row["emailform"]== 1 ?'checked':''; ?> type="checkbox" id="emailform" name="emailform" > Use form and send to this email address</label> -->
             
                
                <div class="buttons" style="display: flex; align-items: center;">
                    <button type="submit" id="submitBtn">Send</button>
                    <div style="margin-top: 0;" id="helperText"></div>
                </div>               
                
                <input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>
                <input type='hidden' name='id' value='<?= $row["id"]; ?>'>         
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
<script src="js/3wid_mailform.js"></script>
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
