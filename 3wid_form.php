<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  error_log('form page');
  
   $data = check_auth();
   
   if($data == NULL) { 
	 //header('location:index_2.php');
   } 
 
 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

 if(isset($_GET["id"])) {
	$id = $_GET["id"];
 } else {
	//header('location:' . $main_url);
 }
 
 $row = db_3wordid_get($id);
 
 echo "<!--";
 
 var_dump($row);
 
 echo "-->";

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
            <form id="messageForm" action="/submit" method="post">
				<input type="text" name="title" class="search-bar" placeholder="Message title" style="margin-top: 10px;" value="">
				<textarea name="description" class="search-bar" placeholder="Enter your message here" rows="4" style="margin-top: 10px;"></textarea>
				<input type="text" name="name" class="search-bar" placeholder="Your name" style="margin-top: 10px;" value="">
                <input type="text" name="mobile" class="search-bar" placeholder="Your mobile phone nr" style="margin-top: 10px;" value="">
                <input type="text" name="email" class="search-bar" placeholder="Email reply address" style="margin-top: 10px;" value="">
                <div class="buttons">
                    <button type="submit" id="submitBtn">Send</button>
                </div>
            </form>
        </div>
    </main>
    <footer>
        <div class="footer-links">
            <a href="#">About</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
        </div>
    </footer>
</body>
<script src="js/3wid_3wid.js"></script>
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
