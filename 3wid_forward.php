<?php
  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $threewords="";
  
  $client_ip = get_client_ip();

  if(isset($_POST["threewords"])) {
	  $threewords = trim($_POST["threewords"]);
	} elseif(isset($_GET["threewords"])) {
		 $threewords = $_GET["threewords"]; 
	} else { 
	     header('location:' . $main_url); 
	     die('sent do mainurl'); 
	     
	}
	
  if(trim($threewords)=="") {
		error_log('empty threewords from ip ' . $client_ip);	
		header('location:' . $main_url); 
		die('sent do mainurl');   
   }
	
   $valid = 1;

   if(strlen($threewords) > 80)  {
	   $valid = 0;
   }

   $threewords = preg_replace('/\s+/x', ' ', $threewords); 

   $exploded = explode(' ',$threewords);

   if(count($exploded) != 3)  {
	   $valid = 0;
	   //error_log('wrong 3wordid ' . $threewords);	
	   header('location:' . $main_url); 
	   die('sent do mainurl');   
   }

   $word_row = db_3wordid_get_threeword($threewords);

   if($word_row == NULL || $word_row['id']==0) {   
	    error_log('threewords ' . $threewords . ' not found. ip ' . $client_ip);	
		header('location:' . $main_url);
		die('sent do mainurl');   
   }
   
   db_3wordid_update_views($word_row['id']);
   

   if(isset($word_row['enabled'])) {
	   if($word_row == NULL) {
			$location = 'location:' . $main_url . '?threewords=' . $threewords;
			header($location);
			die('no match for threeword');   
		}
		
	   if($word_row["emailform"]==1) {
			//error_log('linkthruflag:' . $word_row["linkthruflag"]);
			$location = 'location:3wid_messageform_ext.php?threeword=' . $threewords;
			header($location);
			die('sent do' . $location);
	   }	

	   if($word_row["linkthruflag"]==1) {
			//error_log('linkthruflag:' . $word_row["linkthruflag"]);
			$location = 'location:' . $word_row["linkthru"];
			header($location);
			die('sent do' . $location);
	   }
   
			$location = "location:3wid_notification.php?id=" . $word_row["id"];
		
			header($location);
			die('sent do' . $location);
	   }
   
       error_log('could not forward ip ' . $ip . ' data ' . json_encode($_REQUEST)); 
	   header('location:' . $main_url);  
   

	
?>
