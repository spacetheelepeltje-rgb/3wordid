<?php
	if(isset($_POST['threewords'])) {
	$errorMessage = " threewords : " . $_POST['threewords'] . " feedback: " . $_POST['feedback'];
	$errorLevel = E_USER_WARNING; // You can use different levels like E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE

	trigger_error($errorMessage, $errorLevel);
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
   
</head>
<body>
<div class="container">	
	<a href='index.php'><img width=100 src="img/3wid_big.png"></a><br><br>
	Your feedback has been stored for our review. Thanks!<br>
	You can return to the index page by clicking the 3WID logo above.<br>
</div>	
 </body>
 </html> 

