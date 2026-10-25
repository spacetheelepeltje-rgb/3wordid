<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';


  //error_log('add page ip ' . get_client_ip() . ' time ' . time() . ' session  ' . json_encode($_SESSION) . ' session id ' . session_id() . ' user token ' . $_SESSION["user_token"] );

  // The login token is only taken from the session, never from the URL.
  $data = current_user_from_session($conn);

  if($data == NULL) {
	 error_log('add data is NULL '); 
	 header('location:' . $main_url);
	 exit;
  }
  
  db_3wordid_set_user_consent($data["id"]);
 
  
   header('location:3wid_list.php');
   exit;
