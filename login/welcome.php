<?php

require_once 'config.php';

function check_spam_emails($email) {
	
	$domain = strstr($email, '@'); 
	$domain = ltrim($domain, '@');
	
	if(in_array($domain,['automisly.org'])) {
		
		return true;
		
		} else {
			
		return false;	
		
		}
}	

if (isset($_GET['code'])) {
	
  //error_log('welcome touched');
  	
  if($_GET['authuser']=="0") header("Location: ../index.php");	
	
  $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
  $client->setAccessToken($token['access_token']);

  // get profile info
  $google_oauth = new Google_Service_Oauth2($client);
  $google_account_info = $google_oauth->userinfo->get();
  
  $userinfo = [
    'email' => md5($google_account_info['email']),
    'first_name' => $google_account_info['givenName'],
    'last_name' => $google_account_info['familyName'],
    'gender' => $google_account_info['gender'],
    'full_name' => $google_account_info['name'],
    'picture' => $google_account_info['picture'],
    'verifiedEmail' => $google_account_info['verifiedEmail'],
    'token' => $google_account_info['id'],
  ];
  
  error_log('check ' . $google_account_info['email']);
  
  if(check_spam_emails($google_account_info['email'])) {
		
		error_log('spam abuse ' . $google_account_info['email']);
		
		header('location:../index.php?message=Sowwy your email is on the spam abuse list!');
		
		die('stop here');
		 
   }

  // checking if user is already exists in database
  $sql = "SELECT * FROM google_users WHERE email ='{$userinfo['email']}'";
  $result = mysqli_query($conn, $sql);
  
  if (mysqli_num_rows($result) > 0) {
    // user is exists
    $userinfo = mysqli_fetch_assoc($result);
    $token = $userinfo['token'];
    
  } else {
	  
	try { 	 

    // user is not exists
    $sql = "INSERT INTO google_users (email, first_name, last_name, gender, full_name, picture, verifiedEmail, token) VALUES ('{$userinfo['email']}', '{$userinfo['first_name']}', '{$userinfo['last_name']}', '{$userinfo['gender']}', '{$userinfo['full_name']}', '{$userinfo['picture']}', '{$userinfo['verifiedEmail']}', '{$userinfo['token']}')";
    $result = mysqli_query($conn, $sql);
    
    $token = $userinfo['token'];
    
	} catch (Exception $e) {
		error_log('user insert failed error message ' . $e);
		error_log(json_encode($userinfo));
	}
    
  }

  //error_log('setting user token 1 ' . $token);
  // save user data into session
  $_SESSION['user_token'] = $token;
  
  header("Location: ../3wid_list.php");
  
} else {
	
  //echo "<!-- user token set " . $_SESSION['user_token'] . " -->";		

  if (!isset($_SESSION['user_token'])) {
    header("Location: ../index.php");
    die();
  }

  // checking if user is already exists in database
  $sql = "SELECT * FROM google_users WHERE token ='{$_SESSION['user_token']}'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result) > 0) {
    // user is exists
    $userinfo = mysqli_fetch_assoc($result);
  }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .google-btn {
            background-color: #4285f4;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .google-btn:hover {
            background-color: #357abd;
        }
        .user-profile {
            display: flex;
            align-items: center;
            flex-direction: column;
            gap: 10px;
        }
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="login-container" x>
        <?php
  
        if (isset($_SESSION['user_data'])) {
            $userInfo = $_SESSION['user_data'];
        ?>
            <div class="user-profile">
                <img src="<?php echo $userInfo['picture']; ?>" alt="Profile" class="user-avatar">
                <h3>Welcome, <?php echo $userInfo['full_name']; ?></h3>
                <form action="logout.php" method="POST">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        <?php
        } else {
        ?>
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <button type="submit" class="google-btn">Login with Google</button>
            </form>
        <?php
        }
        ?>
    </div>
</body>
</html>
