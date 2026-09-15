<?php

  include 'php/functions.php';
  
  session_start();
  
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  
  $threewords = $_GET['threewords'];
   
?>  
  <html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
   <style>
  
   
   </style>
</head>
<div class="container">
  <a href='index.php'><img width=100 src="img/3wid_big.png"></a>
 
				<h2>Set new password</h2>
				If you enter the data below we will process it and enable your new password.<br>
			    This may take a day at the moment. We can you your bank account digits for <br>
			    validation.<br><br>
			     <form method="post" action="process_password.php" name="formSearch" id="formSearch">
                <div class="threefields">
					My 3WordID<br>
                    <input type="text" id="myThreeWordID" name="disabled"  class="myInput" value="<?=$threewords?>" disabled><br>
                    My email adress<br>
                    <input type="text" id="myEmail" name="email"   class="password" value="" required maxlength="200"><br>
                    My new password<br>
                    <input type="text" id="myNewPassword" name="password"   class="password" value="" required maxlength="200"><br>
                    Last 4 digits of my bank account<br>
                    <input type="text" id="myKey" name="key"   pattern="[0-9]*" class="key" value="" required maxlength="4"><br>
                </div>
                <div class="threefields">
                    <input type="submit" value="Request" name="SearchLogin" id="submitBtn">
                    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" id="threewords" name="threewords" value="<?=$threewords?>">
                   
                    
                    
			
                </div>

    </form>
    
 </div>   

	 <script>
function validateEmail() {
  const email = document.getElementById('myEmail').value;
  // Here you can add more specific checks
  if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email)) {
    document.getElementById('submitBtn').disabled = true;
  } else {
	  document.getElementById('submitBtn').disabled = false;
  }
  return true;
}

 $(document).ready(function() {
	 document.getElementById('submitBtn').disabled = true;
	 });
</script>
 
 
 
 </body>
 </html> 
