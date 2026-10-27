<?php
require_once 'login/config.php';
require_once 'php/functions.php';

$data = current_user_from_session(get_db_conn());

if ($data == NULL) {
    header('location:index.php?message=' . rawurlencode(t_plain('signup.need_login')));
    die('had to go');
}

$client_ip = get_client_ip();

error_log(time() . ' ' . __FILE__  . ' ' . $client_ip);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>  
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
	<?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('signup.title'); ?></title>
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
    <header><div style="text-align:right;padding:8px 16px;"><?php echo lang_switcher(); ?></div></header>
    <main>
        <div class="logo">       
            <a href='<?php echo $main_url;?>'>
                <img width=100 src="img/3wid_big.png" style="display: block; margin: 0 auto;">
            </a><br>         
        </div>
        
			<div class="search-container">	
			<strong style="margin-top: 10px;margin-bottom: 10px;"><?php echo t('signup.h'); ?></strong><br><br>
			<p>
            <?php echo t_raw('signup.p1'); ?><br><br>
            <?php echo t_raw('signup.p2'); ?><br><br>
            <?php echo t_raw('signup.p3'); ?><br><br>
			</p>
            <form id="3widSignupForm" action="3wid_signup_process.php" method="post">
                <input type="text" id="email" name="email" class="search-bar" placeholder="<?php echo t('signup.email_ph'); ?>" style="margin-bottom: 10px;" value="">
                <input type="text" id="reseller_code" name="reseller_code" class="search-bar" placeholder="<?php echo t('signup.reseller_ph'); ?>" style="margin-bottom: 10px;" value="">
                <textarea name="message" id="message" class="search-bar" placeholder="<?php echo t('signup.remarks_ph'); ?>" rows="4"></textarea>        
                <label><input type="checkbox" id="terms" name="terms" style="margin-top: 10px;margin-bottom: 10px;"> <?php echo t_raw('signup.agree'); ?></label><br> <br>    
                
                <?php echo t('signup.select_payment'); ?><br><br>
                <select class="search-bar" name="payment_method">
					<option value="Paypall">Paypall</option>    
                    <option value="Patreon">Patreon</option>
                    <option value="Bank transfer"><?php echo t('signup.pay_bank'); ?></option>
                    <option value="Other"><?php echo t('signup.pay_other'); ?></option>
                </select>   
                <div class="buttons" style="display: flex; align-items: center;">
                    <button type="submit" id="submitBtn"><?php echo t('signup.submit'); ?></button>
                    <div style="margin-top: 0;" id="helperText"></div>
                </div>
                <input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>            
            </form>
        </div>
    </main>
    <?php echo $footer; ?>
</body>
<?php echo i18n_js_all(); ?>
<script src="js/3wid_signup.js"></script>
</html>
