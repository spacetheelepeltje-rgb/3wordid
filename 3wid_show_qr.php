<?php
  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  //error_log(json_encode(db_3wordid_get_threeword('banana turbo one')));

   error_log('3wid_show_qr.php');

   if(!isset($_GET["hash"])) {
	 error_log('hash not set ' . $_GET["hash"]);
	 header('location:' . $main_url);
   }
   
   $word_row = db_3wordid_get_hash($_GET["hash"]);
   
   error_log(json_encode($word_row));
   
   if($word_row['enabled']!=1) {
	    $location = 'location:index_2.php';
		header($location);
		die('no match for threeword');   
   }
   
   if($word_row == NULL) {
		$location = 'location:' . $main_url . '?threewords=' . $threewords;
		header($location);
		die('no match for threeword');   
   }

   if($word_row["linkthruflag"]==1) {
	    error_log('linkthruflag:' . $word_row["linkthruflag"]);
	    $location = 'location:' . $word_row["linkthru"];
		header($location);
		die('sent do' . $location);
   }
   
   //if($word_row["emailform"]==1) {
//	   error_log('linkthruflag:' . $word_row["emailform"]);
//	    $location = 'location:3wid_emailform.php?user_id=' . $word_row["user_id"] . "&csrf_token=" . $_SESSION['csrf_token'];
//		header($location);
//		die('sent do' . $location);
  // }	
    
	$location = "location:3wid_notification.php?id=" . $word_row["id"];
	header($location);
	die('sent do' . $location);
	
?>
