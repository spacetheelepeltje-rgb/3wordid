<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  // Generate Google Login URL
  $loginUrl = $client->createAuthUrl();
  
  //$data = check_auth();
  $data = check_session();
  
  //if($_SESSION['csrf_token'] != $_GET['csrf_token']) {
  //	  header('location:' . $main_url);
  //   }

try {
	
   $id = $_GET["id"];
  
   if($id==NULL or $id==0) {
	    header('location:3wid_list_messages.php');
	} else {
   

    // Call the function
    db_message_delete($id);
    
    //header('location:' . $loginUrl);
    header('location:3wid_list_messages.php'); 
    //3wid_list.php');
    
	}
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}



?>
