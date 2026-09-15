<?php
 include 'php/functions.php';
 
 //validate data

  if (isset($_GET['threewords']) && isset($_GET['password']) && isset($_GET['email'])) {
	  
	 $threewords = $_GET['threewords'];
	 $password  =  $_GET['password'];
	 $email = $_GET['email'];

  } else {
	  
		header('location:index.php');
  }	  



  $sandbox_url ='https://www.sandbox.paypal.com';
  $production_url = 'https://www.paypal.com';
  //$test_url = $production_url;
  $test_url = $sandbox_url;
  
  if(file_exists('terms_and_conditions.html')) {
	
	$terms_conditions = file_get_contents('terms_and_conditions.html');
} else {
	$terms_conditions = "fail";
	}
	
	if(file_exists('terms_and_conditions.html')) {
	
	$algemene_voorwaarden = file_get_contents('algemene_voorwaarden.html');
} else {
	$algemene_voorwaarden = "fail";
	}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-TJWBJT8GJC"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-TJWBJT8GJC');
</script>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="3wid_1.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.21.0/jquery.validate.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/subscribe.css">
     <script>
  
	
    
    </script>
    <style>
        /* Modal background */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        /* Modal Content/Box */
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Close Button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
	 
	
	  <?php echo $algemene_voorwaarden; ?> 
	  
	  <?php echo $terms_conditions; ?>   

    <div class="container">
		  


        <a href='index.php'><img width=100 src="img/3wid_big.png"></a>  
            <section>
			<p>We are still in the 'Beta' phase so the payment system is evolving and terms and conditions may change.</p>
			<p>
			You can use our Bank account in Europe. <br>Pay <b>12,-</b> Euro to <br><br>
			NL73 RABO 0396 3092 16<br><br>
			Swift:RABONL2U<br><br>
			BIC:RABONL2UXXX (Croeselaan 18, 3521 CB Utrecht)<br><br>
			<b>Mention your 3wordID in the 'message' section</b><br>
			</p>
			<p>
			Dutch : U kunt zich op een 3wordID abonneren door geld over te maken, <br><br>
			12,- Euro per jaar via de bovenstaande rekening. Uw volgende betaling moet dan 12 maanden later
			plaatsvinden, dus het instellen van een automatische afschrijving is aan te bevelen.<br><br>
			Vermeld de 3wordID, de drie woorden, in de overschrijving en 'Ik ga akkoord met de algemene voorwaarden'. Open 
			de algemene voorwaarden hieronder.
			</p>
			
			
			 <button id="openModalBtn1">Algemene voorwaarden</button>

			<p>
          
        </section>
        <section>
      
		</div>
		
     <div id="myModal" class="modal">
		
	 </div>
        </section>
</div>   

  <script>
	document.addEventListener('DOMContentLoaded', function() {
		// Get the modal
		var modal = document.getElementById("myModal1");

		// Get the button that opens the modal
		var btn = document.getElementById("openModalBtn1");

		// Get the <span> element that closes the modal
		var span = document.getElementsByClassName("close")[0];

		// When the user clicks on the button, open the modal 
		btn.onclick = function() {
			event.preventDefault(); // Prevent form submission
			console.log('show modal');
			modal.style.display = "block";
		}

		// When the user clicks on <span> (x), close the modal
		span.onclick = function() {
			modal.style.display = "none";
		}

		// When the user clicks anywhere outside of the modal, close it
		window.onclick = function(event) {
			if (event.target == modal) {
				modal.style.display = "none";
			}
		}
});
  </script>
	
</body>
</html>
