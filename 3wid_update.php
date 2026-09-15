<?php
  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  // Generate Google Login URL
  $loginUrl = $client->createAuthUrl();
  
  $time = substr(time(),-4);

   if(isset( $_POST['csrf_token'])) { 
	$csrf_token =  $_POST['csrf_token']; 
 } else {
	header('location:' . $main_url);
 }

 $data = check_credentials($_SESSION,$_POST,$_GET);
 
 error_log('update data test ' . json_encode($data));
 
 error_log('update post ' . json_encode($_POST));
 
 if($data == NULL) {
	error_log('auth is null');
 }
  
 $csrf_token =  bin2hex(random_bytes(32));
 
 $_SESSION['csrf_token'] = $csrf_token;

  if(isset($data['id'])) { 
	 db_3wordid_set_csrf_token($data['id'], $csrf_token);
	 
  } 

   $id = $_POST["id"];
   
   $notification = $_POST["notification"];
   
   $pconfig = HTMLPurifier_Config::createDefault();
   $pconfig->set('HTML.Allowed', 'p,br,a[href],strong,em,ul,ol,li,div,span,h1,h2,h3,h4,h5,h6');
   $pconfig->set('HTML.SafeIframe', true);
   $purifier = new HTMLPurifier($pconfig);
   
   $clean_notification = $purifier->purify($notification);

   if (isset($_POST["emailform"])) {
	   $emailform = 1;   
	   } else {
	   $emailform = 0;
	}
	
	if (isset($_POST["private"])) {
	   $private = 1;   
	   } else {
	   $private = 0;
	}
	
	
   //$email = '';
   //$emailform = 0;	
	
   $linkthru = $_POST["linkthru"];
   
   if(isset($_POST["linkthruflag"])) {
		$linkthruflag = 1;
		} else {
		$linkthruflag = 0;  
   }
   
   if (filter_var($linkthru, FILTER_VALIDATE_URL)) {
		//echo("$url is a valid URL");
		} else {
		header('location:3wid_list.php');
	}
   
   //error_log( " id:". $id . " not:" . $notification . " linkthru:" . $linkthru . " linkthruflag:" . $linkthruflag. " email:" . $email . " emailform:" . $emailform);
   
// Example usage:
try {
	
    db_3wordid_update($id, $clean_notification, $linkthru, $linkthruflag, $emailform,$private);
    
    //error_log('update ' . $csrf_token . ' ' . $_SESSION['csrf_token']);
   // header('location:' . $client->createAuthUrl());
    
    //3wid_list.php?csrf_token=' .  $csrf_token);
    header('location:3wid_list.php');
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}



?>
