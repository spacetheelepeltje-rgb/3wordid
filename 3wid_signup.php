<?php
require_once 'login/config.php';
require_once 'php/functions.php';

$data = check_credentials($_SESSION, $_POST, $_GET);

if ($data == NULL) {
    header('location:index.php?message=You need to Log in to subscribe');
    die('had to go');
}

$client_ip = get_client_ip();

error_log(time() . ' ' . __FILE__  . ' ' . $client_ip);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>  
<!DOCTYPE html>
<html lang="en">
<head>
	<?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Page - 3WordID.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .reseller-link {
            display: inline; /* Ensure the link stays inline */
            color: blue;
            text-decoration: none;
        }
    </style>    
</head>
<body>
    <header></header>
    <main>
        <div class="logo">       
            <a href='<?php echo $main_url;?>'>
                <img width=100 src="img/3wid_big.png" style="display: block; margin: 0 auto;">
            </a><br>         
        </div>
        
			<div class="search-container">	
			<strong style="margin-top: 10px;margin-bottom: 10px;">3WordID.com Subscription</strong><br><br>
			<p>
            Here you can subscribe to 3WordID.com so your chosen IDs do not expire. Cost is ~18 Euro/Year or ~$1.5 per month per 3WordID (Patreon is the 
            currenty preferred method). We will integrate with more common systems soon and you can always use a direct bank transfer.<br><br>
            If you are a <a class="reseller-link" href="3wid_reseller.php">reseller</a> you need to enter your code below (You can find the 
            code on the overview page when you log in). To resell you need to be subscribed yourself.<br><br>
            The terms as found on the <a href="https://3wordid.com/terms.php">terms page</a> apply.<br><br>
			</p>
            <form id="3widSignupForm" action="3wid_signup_process.php" method="post">
                <input type="text" id="email" name="email" class="search-bar" placeholder="Your Google Login email address" style="margin-bottom: 10px;" value="">
                <input type="text" id="reseller_code" name="reseller_code" class="search-bar" placeholder="Type the reseller code if applicable" style="margin-bottom: 10px;" value="">
                <textarea name="message" id="message" class="search-bar" placeholder="Remarks" rows="4"></textarea>        
                <label><input type="checkbox" id="terms" name="terms" style="margin-top: 10px;margin-bottom: 10px;"> I read the <a href="https://3wordid.com/terms.php">terms page</a> and agree with them</label><br> <br>    
                
                Select payment option<br><br>
                <select class="search-bar" name="payment_method">
					<option value="Paypall">Paypall</option>    
                    <option value="Patreon">Patreon</option>
                    <option value="Bank transfer">Bank transfer</option>
                    <option value="Other">Other</option>
                </select>   
                <div class="buttons" style="display: flex; align-items: center;">
                    <button type="submit" id="submitBtn">Subscribe</button>
                    <div style="margin-top: 0;" id="helperText"></div>
                </div>
                <input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>            
            </form>
        </div>
    </main>
    <?php echo $footer; ?>
</body>
<script src="js/3wid_signup.js"></script>
</html>
