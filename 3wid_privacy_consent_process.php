<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';


  //error_log('add page ip ' . get_client_ip() . ' time ' . time() . ' session  ' . json_encode($_SESSION) . ' session id ' . session_id() . ' user token ' . $_SESSION["user_token"] );

  if(isset($_GET['user_token'])) {
	  if(!isset($_SESSION['user_token'])) {
		  $_SESSION['user_token']=$_GET['user_token'];
		  }
	  }
  
  $data = check_credentials($_SESSION, $_POST, $_GET);

  if($data == NULL) {
	 error_log('add data is NULL '); 
	 header('location:' . $main_url);
  }
  
  db_3wordid_set_user_consent($data["id"]);
 
  
   header('location:3wid_list.php');
