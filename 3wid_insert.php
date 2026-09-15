<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $data = check_credentials($_SESSION, $_POST, $_GET);
  
  if ($data == NULL) {
    header('location:index.php?message=Login expired. Log in again.');
    die('had to go');
}
  
 // error_log('post ' . json_encode($_POST) . ' get ' . json_encode($_GET) . ' session ' . json_encode($_SESSION));
  
try {
	
   $user_id = $data['id'];
   
   $threeword = $_POST["threeword"];
   
   $count = db_3wid_count_3wids($user_id);
   
   //error_log('count is ' . $count . ' credit is ' . $data['credit']);
   
   if($count > 2 && $data['credit']==0) { 
		error_log('not enough credit for more 3wordids');
   }

   $threeword = db_3wid_validate_type($threeword,$data["user_type"]);// db_3wid_validate($threeword);
   
   if(!$threeword) {
	   //error_log($threeword);
	   header('location:3wid_list.php'); 
   }
   
   
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

   $linkthru = $_POST["linkthru"];
   
   if(isset($_POST["linkthruflag"])) {
		$linkthruflag = 1;
		} else {
		$linkthruflag =0;  
   }
   


   // Call the function
   $new_id = db_3wordid_insert($user_id,$threeword, $clean_notification, $linkthru, $linkthruflag,$emailform,$private);
    
   if($new_id == FALSE) {
	   error_log('aborted insert');
	   } 
    
   //header('location:' .   $loginUrl);
   header('location:3wid_list.php'); 
   
   //3wid_list.php?csrf_token=' . $_POST['csrf_token']);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}



?>
