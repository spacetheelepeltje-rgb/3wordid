<?php

session_start();

include 'php/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])) {

if(isset($_POST["threewordto"]) && $_POST["threewordto"] != "") {
	
	$threewords = $_POST["threewords"];
	$threewordto = $_POST["threewordto"];
    $message = $_POST["message"];
    $password = $_POST["password"];
	$status = "";
	
	$hashfrom  = gethash($threewords);
	$hashto  = gethash($threewordto);
	
	$password_check = validate_password($threewords,$password);
	
	if(is_array($password_check) == False) {
		
		//echo "password ok";
		
		$messagecontent = getmessagehash($hashto[0]);
		
		$blocked = $messagecontent['blocked'];
		$messages = $messagecontent['messages'];
		
		//var_dump($messagecontent);
		
		if(!array_search($threewords,$blocked)) {
			
			//echo "user" . $threewords . " not blocked";
			
			$time_date = date('d-m-Y H:i:s');

			$messages[$threewords] = array('message'=>$message,'timesend'=>$time_date);
			
			$newcontent = array('blocked'=>$blocked,'messages'=>$messages);

			setmessagehash($hashto[0],$newcontent);
			
			$status = "message send";
		
		} else {

		 $status = "this user can not be contacted";
		
		}

		
    }		
	
	
	$header_url = 'location:./public_notification.php?threewords=' . $threewords . '&hash=' . $hashfrom[0] . '&status=' . $status;
		
	header($header_url);

	exit();
}

// new account

if(isset($_POST["threewords"]) && $_POST["threewords"] != "") {
	
	$threewords = $_POST["threewords"];
	
	$notification = $_POST["notification"];
	
	$xdotcom = $_POST["xdotcom"];
	
	$linkthru = $_POST["linkthru"];
	
	$linkthruflag = $_POST["linkthruflag"];
	
	$email = $_POST["email"];
	
	$url_validation = check_url($linkthru);
	
	if ($url_validation =='invalid') {
		$linkthru = "";
		$linkthruflag = 0;
	} else {
		$linkthru = $url_validation;
	}
	
	if ($linkthru == "") {
		$linkthruflag = 0;
		}
	
	$cleaned_xdotcom = preg_replace('/[^a-zA-Z_]/', '', $xdotcom);
	
	$password = $_POST["password"];
	
	$hash = gethash($threewords);
	
	$tostore = getcontenthash($hash[0]);
	
	if(isset($tostore["subscribed"])) { 
		$subscribed = $tostore["subscribed"]; } 
	else {
		$subscribed = "no";
	}
	
	if($tostore['password'] == $password) {
		
		error_log("process.php password known");
		  
		setcontent($hash[0],$notification,$password,$cleaned_xdotcom,$linkthru,$linkthruflag,$email,$subscribed);
		
		logcontent($threewords,$hash[0],$notification,$password,$cleaned_xdotcom,$linkthru,$email,'update');	

	} else {

		if($tostore['password'] == "") {
			
			error_log("process.php password new");

			setcontent($hash[0],$notification,$password,$cleaned_xdotcom,$linkthru,$linkthruflag,$email,$subscribed);
			
			logcontent($threewords,$hash[0],$notification,$password,$cleaned_xdotcom,$linkthru,$email,'create');	
			
			setuserdata($hash[0],$email);
		
			
		}
		

		
   }
	   
        //var_dump($_POST);
	    
	    $header_url = 'location:./public_notification.php?threewords=' . $threewords . '&hash=' . $hash[0];
		
	    header($header_url);
	    
	    exit();
	    
	}
} else {
	
	echo "Wrong csrf token!";
	
	}
}
