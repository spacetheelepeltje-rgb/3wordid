<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  error_log('credit page');
   
  $data = current_user_from_session($conn);
if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
}
   
?>  
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('credit.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
		/* Center all main content sections */
main {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.logo, #bankdata, .search-container-mid {
  max-width: 600px; /* Adjust as needed */
  width: 100%;
  margin: 0 auto; /* Centers the block horizontally */
  padding: 20px;
}

/* Optional: Style for PayPal button container */
#paypal-button-container-P-1FG26942UU083052AM56O4OA {
  display: inline-block; /* Ensures the PayPal button is centered */
}
</style>
</head>
<body>
    <header>
        <div class="top-right" id="userPortrait">
            <?php echo lang_switcher(); ?>
            <a href="#" class="login-icon" title="<?php echo t('credit.account_title'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.login'); ?>">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <img id="userPortrait" style="display:none;" alt="<?php echo t('icon.portrait'); ?>">
        </div>
    </header>
   <main>
  <div class="logo">
    <a href='<?php echo $main_url;?>'><img width="100" src="img/3wid_big.png" alt="<?php echo t('icon.logo'); ?>"></a><br>
  </div>

  <div id='bankdata'>
	<?php 
	if($data != NULL) {
	?>	 
    <strong><?php echo t('credit.current', ['amount' => $data["credit"]]); ?></strong><br><br>
    <?php
	}
	?>
    <?php echo t_raw('credit.p1'); ?><br><br>
    <?php echo t_raw('credit.p2'); ?><br><br>
    <?php echo t_raw('credit.p3'); ?><br><br>
    <?php echo t_raw('credit.p4'); ?>
  </div>

  <div class="search-container-mid">
    <br>
    PayPal<br><br>
    <div id="paypal-button-container-P-1FG26942UU083052AM56O4OA"></div><br><br>
    <?php echo t_raw('credit.paypal'); ?><br>
 
    <br>
    QR Code<br><br>
    <img size="200" src="rabo_qr__jun_21_2025.png">
    <?php echo t_raw('credit.qr'); ?>
    
  </div>
</main>
    <?php echo $footer; ?>
</body>
<script src="js/3wid_add.js"></script>
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
            window.location.href = '/login/options.php';
        }, 1000);
    });
</script>

<script src="https://www.paypal.com/sdk/js?client-id=Ac5eOpu4IFKpLujHDgAbDZVcvZyeVa42fs-5B3I6oiviSrJHwPJmMIPK0qKvYOUTGD7500l8ZsbKJoTq&vault=true&intent=subscription" data-sdk-integration-source="button-factory"></script>
<script>
  paypal.Buttons({
      style: {
          shape: 'rect',
          color: 'gold',
          layout: 'vertical',
          label: 'subscribe'
      },
      createSubscription: function(data, actions) {
        return actions.subscription.create({
          /* Creates the subscription */
          plan_id: 'P-1FG26942UU083052AM56O4OA'
        });
      },
      onApprove: function(data, actions) {
		  
        alert(<?php echo json_encode(t_plain('credit.thanks')); ?>); // You can add optional success message for the subscriber here
      }
  }).render('#paypal-button-container-P-1FG26942UU083052AM56O4OA'); // Renders the PayPal button
</script>
</html>
