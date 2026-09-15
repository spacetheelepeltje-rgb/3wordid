<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  //var_dump($_POST);
  $client_ip = get_client_ip();

  if(isset($_POST['csrf_token'])) {
	if($_SESSION['csrf_token'] != $_POST['csrf_token']) {
	  header('location:' . $main_url);
	 }
  } else {
	  error_log('index page ip ' . $client_ip . ' on ' . check_mobile() . ' time ' . time() . ' POST >>' . json_encode($_POST) . '<<');
	  header('location:' . $main_url);
	  die('no CSRF Token');
  }	 

try {

   $payment_method = $_POST["payment_method"];
   $email = $_POST["email"];
   $reseller_code = $_POST["reseller_code"];
   if(isset($_POST["message"])) {
	$message = $_POST["message"];
   } else {
	$message = "";
   }
   
   error_log(json_encode($_POST));
   
   if(isset($_POST["terms"]) && $_POST["terms"]=="on") {
	   $terms = 1;  	   
	   } else {
       $terms = 0;  
   }
   
   if(!in_array($payment_method,['Paypal','Bank transfer','Patreon','Other'])) {
	   error_log('wrong payment method');
	   error_log(json_encode($_POST));
   }

    if (empty($email)) {
        $email = '';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		header('location:' .   $main_url);
        die("Invalid email: must be a valid email address");
    }
    
    $code = substr(md5($email),-4);

   // Call the function
   $new_user = db_3wordid_signup_insert($email, $reseller_code, $payment_method, $terms, 1, $message);

   //header('location:' . $main_url);
   //3wid_list.php?csrf_token=' . $_POST['csrf_token']);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

header('location:3wid_signup_payment.php?payment_method=' . $payment_method . '&message=Your signup has been processed&code='. $code);

?>
