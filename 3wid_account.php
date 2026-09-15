<?php



  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  session_start();
  
  error_log('list page');
  
  if(isset($_SESSION['user_token'])) {
  
  // checking if user is already exists in database
  $sql = "SELECT * FROM google_users WHERE token ='{$_SESSION['user_token']}'";
  $result = mysqli_query($conn, $sql);
	  if (mysqli_num_rows($result) > 0) {
		// user is exists
		$userinfo = mysqli_fetch_assoc($result);
	  }
  
  echo "<!--";
  var_dump($userinfo);
  echo "-->";
  
 } else {
	 
	header('location:' . $main_url); 
	
 }

?>   
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            position: relative;
            margin-bottom: 20px;
        }
       
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        .icon {
            display: inline-block;
            vertical-align: middle;
        }
        a {
            color: #000;
            text-decoration: none;
        }
        footer {
            margin-top: 20px;
            text-align: center;
        }
        .footer-links a {
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <header>
        <div class="top-right">
            <a href="login/logout.php" class="logout-icon">
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
			<center><a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br>   
            </center>  
        </div>
        <table class="user-account">
            <thead>
                <tr>
                    <th colspan="2">User Account</th>
                </tr>
            </thead>
            <tbody>
				<tr>
                    <td>User Name</td>
                    <td><?php echo $userinfo["first_name"] . " " . $userinfo["last_name"]; ?></td>
                </tr>
                <tr>
                    <td>User Google Email</td>
                    <td><?php echo $userinfo["email"]; ?></td>
                </tr>
                <tr>
                    <td>User Credits</td>
                    <td>$50.00</td>
                </tr>
                 <tr>
                    <td>Add credit via bank transfer</td>
                    <td>NL73RABO-blahblah-216 F. Rincker</td>
                </tr>
            </tbody>
        </table>
    </main>
    <footer>
        <div class="footer-links">
            <a href="#">About</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
        </div>
    </footer>
</body>
</html>
