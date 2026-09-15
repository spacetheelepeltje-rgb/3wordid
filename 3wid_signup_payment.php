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
      
    </header>
   <main>
  <div class="logo">
    <a href='<?php echo $main_url;?>'><img width="100" src="img/3wid_big.png" alt="Logo"></a><br>
    <strong><?php echo $message; ?></strong>
  </div>
  <?php 
  // BANK TRANSFER
  if($payment_method=="Bank transfer" || $show) {  
  ?>	  
  <div id='bankdata'>
	 <b>Bank transfer</b><br><br>
    <center><img size="200" src="rabo_qr__jun_21_2025.png"></center><br><br>
    In Europe, you can this QR code to the payment link (goes to my personal account atm F. Rincker). You can us the 3WordID 'one year payment' on 3WordID to find the link as well. For actual bank transfer for larger nrs send an email to <a href="mailto:3wordid@climatebabes.com">us</a>.
  </div>
<?php
  }
 // OTHER
if($payment_method=="Other" or $show) { 
  ?>	  
  <div id='bankdata'>
	  Other Methods.
   </div>
<?php
  }

 // PAYPAL
if($payment_method=="Paypal" || $show) {
	  
  ?>	  
 <div id='bankdata'>
   <b>PayPal</b><br><br>
    <div id="paypal-button-container-P-1FG26942UU083052AM56O4OA"></div><br><br>
    You can use PayPal to subscribe to an annual fee of (<?php echo $price; ?>) Euro. Add the code to the payment (<?php echo $code; ?>).<br>
  </div>
<?php
  }

if($payment_method=="Patreon" || $show) {
  // PATREON  
  ?>	  
  <div id='bankdata'>
   <b>Patreon</b><br><br>	  
    Follow this link to sign up on Patreon : <a href="https://www.patreon.com/c/roboeconomics/membership">To Roboeconomics Patreon</a>
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
		  
        alert("Thank you for subscribing. You will see confirmation in your 3wordID profile soon ;-)"); // You can add optional success message for the subscriber here
      }
  }).render('#paypal-button-container-P-1FG26942UU083052AM56O4OA'); // Renders the PayPal button
</script>
</html>
