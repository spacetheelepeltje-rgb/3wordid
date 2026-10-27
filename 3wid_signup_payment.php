<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  error_log('signup payment');
  
  $price = 12;
  $show = 0;
  
  if(isset($_GET["message"])) {
	  $message = $_GET["message"];
	  }
	  
  if(isset($_GET["payment_method"])) {
	  $payment_method = $_GET["payment_method"];
	  }	  

  if(isset($_GET["code"])) {
	  $code = $_GET["code"];
	  }
    
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
    <header><div style="text-align:right;padding:8px 16px;"><?php echo lang_switcher(); ?></div></header>
   <main>
  <div class="logo">
    <a href='<?php echo $main_url;?>'><img width="100" src="img/3wid_big.png" alt="<?php echo t('icon.logo'); ?>"></a><br>
    <strong><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></strong>
  </div>
  <?php 
  // BANK TRANSFER
  if($payment_method=="Bank transfer" || $show) {  
  ?>	  
  <div id='bankdata'>
	 <b><?php echo t('signup.pay_bank'); ?></b><br><br>
    <center><img size="200" src="rabo_qr__jun_21_2025.png"></center><br><br>
    <?php echo t_raw('pay.bank'); ?>
  </div>
<?php
  }
 // OTHER
if($payment_method=="Other" or $show) { 
  ?>	  
  <div id='bankdata'>
	  <?php echo t('pay.other'); ?>
   </div>
<?php
  }

 // PAYPAL
if($payment_method=="Paypal" || $show) {
	  
  ?>	  
 <div id='bankdata'>
   <b>PayPal</b><br><br>
    <div id="paypal-button-container-P-1FG26942UU083052AM56O4OA"></div><br><br>
    <?php echo t('pay.paypal', ['price' => $price, 'code' => $code]); ?><br>
  </div>
<?php
  }

if($payment_method=="Patreon" || $show) {
  // PATREON  
  ?>	  
  <div id='bankdata'>
   <b>Patreon</b><br><br>	  
    <?php echo t_raw('pay.patreon'); ?>
  </div>
  <?php
  }
?>
  
</main>
    <?php echo $footer; ?>
</body>

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
