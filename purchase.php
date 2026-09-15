<?php

  include 'php/functions.php';
  
  if(isset($_GET["threewords"])) {
	  
		$threewords = $_GET["threewords"];
	  } else {
		  
		$threewords = "";  
	  }
  
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
     /* General body and layout styles */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f5f5f5;
    height: 100vh;
    display: flex;
    justify-content: center; /* Center horizontally */
    align-items: center; /* Center vertically */
}

/* Container for the form, no box around it */
.container {
    text-align: center;
    width: 100%;
    max-width: 400px; /* Limit form width on large screens */
    padding: 20px;
}

/* Input and submit button styles */
.threefields input[type="text"], .threefields input[type="submit"] {
    width: calc(100% - 40px); /* Full width with margin space */
    max-width: 400px;
    padding: 10px;
    margin: 10px 20px; /* Consistent margin on both sides */
    font-size: 16px;
    border-radius: 20px;
    border: 1px solid #ccc;
    box-sizing: border-box; /* Ensure padding doesn’t affect width */
}

input[type="submit"] {
    background-color: #007bff;
    color: white;
    cursor: pointer;
}

input[type="submit"]:hover {
    background-color: #0056b3;
}

.helperText {
    margin-bottom: 15px;
    color: #555;
}

/* Mobile responsive adjustments */
@media (max-width: 600px) {
    .container {
        padding: 10px;
    }

    .threefields input[type="text"], .threefields input[type="submit"] {
        width: calc(100% - 40px); /* Consistent width and margin on mobile */
        margin: 10px 20px;
        font-size: 14px;
    }
}
.settings-icon {
    position: fixed;
    top: 10px;
    right: 10px;
    width: 40px;
    height: 40px;
    background-color: #007bff; /* Visible blue background */
    color: white; /* White color for the text/icon */
    font-size: 24px; /* Adjust font size */
    text-align: center;
    line-height: 40px; /* Center text vertically */
    border-radius: 50%; /* Circle shape */
    cursor: pointer;
    z-index: 1000; /* Ensure it stays on top */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional: add shadow for visibility */
}
.settings-icon:hover {
    background-color: #0056b3; /* Darker blue on hover */
}
</style>
</head>
<body>
    
    <div class="container">
		
        <h1><a href='index.php' title='3wordID.com'><img width=100 src="img/3wid_big.png"></a></h1>
        <section align='left'>
			<p>
            Welcom to the 3wordid.com shop. We now offer only a yearly 
            subscription for 12,- Euro. If you stop payment your 3wordID 
            is relinguished after one month. We reserve the right to change
            the service to optimally serve the most users. 
            </p>
            <p>
            To create a 3wordID <b>use your paypal email address when creating your 3wordID</b>. In Holland you 
            can contact us at <a href='mailto:info@climatebabes.com'>info@climatebabes.com</a> with title 
            "3wordID subscription" so we don't miss it!
            </p>
            <p>
			We keep no private data, and we do not encrypt our data. We will fully cooperate with law enforcement in case of questions about an account. 
			We reserve the right to block your IP if you overuse the service or delete your account if we discover it is used for malicious ends.	
			</p>	
        </section>
        <section>
            <form method="post" action="" name="formSearch" id="formSearch">
                <div class="threefields">
                    <input type="text" id="myThreeWordID" name="threewords" placeholder="Enter three words not case sensitive"  class="myInput" value="<?php echo $threewords; ?>" required maxlength="200">
                </div>
                
               <div class="helperText">
                  <input type="checkbox" name="terms" id="terms" unchecked>Agree with terms and conditions</p>
                </div> 
            </form>
        </section>
         <section>
           Annual payment via Paypal of 12 Euro
           <p>
			   
			 <div id="paypal-button-container-P-1FG26942UU083052AM56O4OA"></div>
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
		 </p>	   
        </section>
       
    </div>
    <!-- JavaScript to toggle profile menu -->
 
 <script src="js/form_checks_index.js"></script>
</body>

</html>
