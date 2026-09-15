<?php

  include 'php/functions.php';
  
  session_start();
  
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])) {
		
		//var_dump($_POST);
		
		$threewords = $_POST['threewords'];
		
		$hash =  gethash($threewords);
		
		$hash_file = './3wids/' . $hash[0];
		
		//echo $hash_file;
		
		//echo " xx" . file_exists($hash_file) . "xx  ";
		//echo $hash_file;
		
		if(file_exists($hash_file)) {
			
			$file_location = './new_password/' . date('dmy') . 'password_request_' . $threewords;
		
			$data = serialize($_POST);
		
			file_put_contents($file_location,$data);
			
			$message = "Your password request has been send. You can click on the logo above to return to the index page.";
			
		} else {
			
			$message = "You did not supply us with the right data. The 3WordID was not known.";
		}
		
		
		//$hash=md5($threewords);
		
		
		
	}	
  }	
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
<body>
<div class="container">	
	<a href='index.php'><img width=100 src="img/3wid_big.png"></a><br><br>
	<?=$message?>
</div>	
 </body>
 </html> 
