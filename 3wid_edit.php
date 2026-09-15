<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  error_log('edit page');
  
  if(isset($_SESSION['user_token'])) {
  
  // checking if user is already exists in database
  $sql = "SELECT * FROM google_users WHERE token ='{$_SESSION['user_token']}'";
  $result = mysqli_query($conn, $sql);
	  if (mysqli_num_rows($result) > 0) {
		// user is exists
		$userinfo = mysqli_fetch_assoc($result);
	  }

 } else {
	 
	//header('location:index_2.php'); 
	
 }
 
 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

 if(isset($_GET["id"])) {
	$id = $_GET["id"];
 } else {
	//header('location:' . $main_url);
 }
 
 db_3wordid_get($id);

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
           <a href="#" class="logout-icon">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Logout Icon">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
        <polyline points="16 17 21 12 16 7"></polyline>
        <line x1="21" y1="12" x2="9" y2="12"></line>
    </svg>
</a>
            <img id="userPortrait" style="display:none;" alt="User Portrait">
        </div>
    </header>
    <main>
        <div class="logo">
            <center>
                <a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br>   
                <div id="helperText">Enter your Three Word ID and Forward URL</div><br>
            </center>  
        </div>
        <div class="search-container">
            <form id="linkForm" action="/submit" method="post">
                <div class="form-field">
                    <label for="threeWordId">Three Word ID</label>
                    <input type="text" id="threeWordId" name="threeWordId" class="search-bar" placeholder="Enter three words">
                </div>
                <div class="form-field" style="margin-top: 20px;">
                    <label for="forwardUrl">Forward URL</label>
                    <input type="text" id="forwardUrl" name="forwardUrl" class="search-bar" placeholder="Enter destination URL">
                </div>
                <div class="buttons" style="margin-top: 20px;">
                    <button type="submit" id="submitBtn">Update</button>
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
<script src="js/form_checks_index_2.js"></script>
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
