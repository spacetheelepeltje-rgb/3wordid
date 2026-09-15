<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  // Generate Google Login URL
  $loginUrl = $client->createAuthUrl();
  
  //$data = check_auth();
  $data = check_session();
  
  if($_SESSION['csrf_token'] != $_GET['csrf_token']) {
	  header('location:' . $main_url);
	  }

try {
	
   error_log('starting delete');
	
   $id = $_GET["id"];
  
   if($id==NULL or $id==0) {
	   
	    header('location:3wid_list.php');
	    
	} else {	
   

    // Call the function
    db_3wordid_delete($id);
    
    //header('location:' . $loginUrl);
    header('location:3wid_list.php'); 
    //3wid_list.php');
    
	}
	
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

/*
This is a basic notification you can leave by sharing three words, very simple. You can try this site for free (limited period), just log in with Google and you can create one 3WordID. We are working on a mailform, so you can give your employees a 3WordID (to be anonymous) which you can then manage see who gets messages and forward them. This can protect workers in hospitals for example.

You can leave an email address if  you want : <a href="mailto:3wordid@climatebabes.com">email address</a>
<br />
Thanks for checking this site out, if you know of potential users, tell them about this! ;-) !
*/
?>
