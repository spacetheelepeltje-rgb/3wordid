<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  error_log('credit page');
   
  $data = check_auth();
 
  if($data == NULL) {
		$data = check_session();
  }
   
?>  
<!DOCTYPE html>
<html lang="en">
<head>
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
        <div class="top-right" id="userPortrait">
            <a href="#" class="login-icon" title="To Account page">
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
    <a href='<?php echo $main_url;?>'><img width="100" src="img/3wid_big.png" alt="Logo"></a><br>
  </div>

  <div id='bankdata'>
	<?php 
	if($data != NULL) {
	?>	 
    <strong>Your current credit is <?php echo $data["credit"]; ?> €</strong><br><br>
    <?php
	}
	?>
    If you have not paid, your 3wordID can be deleted or taken by another user. It expires after two weeks.<br><br>
    You can opt for a <a href="https://www.patreon.com/roboeconomics">subscription via Patreon</a>. Then your credit here will remain zero. It takes a day or so to process.<br><br>
    To add credit, you currently can use bank transfer, but more options will be made available. Of course, an invoice is also possible for larger accounts.<br><br>
    Send an email to <a href="mailto:3wordid@climatebabes.com">our email address</a> to request pricing. One 3WordId costs $10/Year, but discounts for larger numbers are possible.
  </div>

  <div class="search-container-mid">
    <br>
    PayPal<br><br>
    <div id="paypal-button-container-P-1FG26942UU083052AM56O4OA"></div><br><br>
    You can use PayPal to subscribe to an annual fee of 12 Euro (admin handling added). Just make sure your name is the same as with the Google account. If you want to help us out, send an email to <a href='mailto:3wordid@climatebabes.com'>3wordid@climatebabes.com</a> with your name and email so we can make sure the transaction is processed correctly (Paypal does not hand us a good identifier unless we pay more). We are working on integrating with PayPal, so for the time being, it would help to send your transaction details to ensure we process it correctly.<br>
 
    <br>
    QR Code<br><br>
    <img size="200" src="rabo_qr__jun_21_2025.png">
    In Europe, you can also use this QR code to find a payment link. You can us the 3WordID 'one year payment' to find it as well. Make sure you send us an <a href="mailto:3wordid@climatebabes.com">email</a> too. We will make a form here soon to make all this easier.
    
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
            window.location.href = '/login/index.php';
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
		  
        alert("Thank you for subscribing. You will see confirmation in your 3wordID profile soon ;-)"); // You can add optional success message for the subscriber here
      }
  }).render('#paypal-button-container-P-1FG26942UU083052AM56O4OA'); // Renders the PayPal button
</script>
</html>
