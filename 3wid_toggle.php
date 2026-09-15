<?php

   include 'php/functions.php';
   
   // Generate Google Login URL
   $loginUrl = $client->createAuthUrl();

   //$data = check_session();
   
   $data = check_credentials($_SESSION,$_POST,$_GET);
   
   //error_log('toggle page token ' . json_encode($_SESSION) . ' session id ' . session_id());
   //error_log('data is ' . json_encode($data)); 
   
   if($data == NULL) { 
	 header('location:' . $main_url);
   } 
   
   if(isset($_GET['id'])) { 
	   $id = $_GET['id']; 
	   } else {
	   header('location:3wid_list.php');   
	   }
  
    //error_log(json_encode($data));
    //error_log(json_encode($id));
   
	db_3wordid_toggle($id,$data['id']);
			
	//error_log('toggle 3wid with id ' . $id);

	header('location:3wid_list.php'); // . $loginUrl);
	
	//3wid_list.php');
		   
		  
	
