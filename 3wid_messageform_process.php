<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';

  error_log('messageform process page');
/*
 * 
 * CREATE TABLE 3wordid_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255),
    message TEXT,
    creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    terms INT CHECK (terms IN (0, 1)),
    ip_address VARCHAR(100),
    status VARCHAR(100),
    UNIQUE (id)
);

*/	 

	$data = check_credentials($_SESSION,$_POST,$_GET);
	
    //error_log('veriables ' . json_encode($_POST) . ' session ' . json_encode($_SESSION));
  
	if($data == NULL) {
	   header('location:' . $main_url);
    } 
	  
   $message = $_POST["message"];
 
   $threeword = $_POST["threeword"];
   
   $title = $_POST["title"];
   
   $from_id = (int) $_POST["from_id"];
   
    if(isset( $_POST["terms"])) {
			$terms = 1;
			} else {
			$terms =0;  
	}
   
   // message send to a three word id. The user of the ID is known. It has to show up in the ID's message row.
   
   //$new = db_3wordid_mailform_select($id, $email,'unread');
   
   //error_log('message is ' . $message);

   $threeword_data =  db_3wordid_get_threeword($threeword);

   if($threeword_data) { // valid recipient
		
	try {
		
		error_log('inserting');

	  
	   
	   //error_log(json_encode($_POST));
	   //error_log('inserting mailform message for 3wordid ' . $id . ' message ' . $mailmessage . ' email ' . $email . ' terms ' . $terms);
       //  ($from_id,$to_3wordid,$to_user_id,$title,$message, $terms)
	   // Call the function
	   $insert_result = db_3wordid_messageform_insert($data['id'],$threeword_data['id'],$from_id,$threeword_data['user_id'],$title,$message, $terms);
	   
	   //db_3wordid_update_google_csrf($data['id'],$_SESSION["csrf_token"]);
	   db_3wordid_set_csrf_token($data['id'],$_SESSION["csrf_token"]);
	   
	   //error_log('messages form process csrf_token ' . $_SESSION["csrf_token"]);
		
	   header('location:3wid_list.php?message=Thank, you your message to ' . $threeword_data['threeword'] . ' is being processed');
	   
	} catch (Exception $e) {
		error_log("Error: " . $e->getMessage());
	}	

} else {
		
		//header('location:' . $main_url . '?message=You can only send new messages when the old one has been read');
}

?>
