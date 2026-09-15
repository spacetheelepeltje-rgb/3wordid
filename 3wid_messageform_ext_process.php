<?php

  // external user ID is 25 
  // 

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

   //error_log('message ext veriables ' . json_encode($_POST) . ' session ' . json_encode($_GET));
   
   if(isset($_POST["message"])) {

   $message = $_POST["message"];

   $title = $_POST["title"];
   
   $threeword = $_POST["threeword"];

   $threeword_val = db_3wid_validate($threeword);
   
   $threeword_data =  db_3wordid_get_threeword($threeword_val);

   //error_log($threeword_val . ' ' . ' ' . $threeword . ' ' . json_encode($threeword_data));

   if($threeword_data) { // valid recipient
		
	try {
		
		//error_log('inserting');

	   //error_log('threeword data found ' . json_encode($threeword_data));
	   //error_log('inserting mailform message for 3wordid ' . $id . ' message ' . $mailmessage . ' email ' . $email . ' terms ' . $terms);
       //  ($from_id,$to_3wordid,$to_user_id,$title,$message, $terms)
	   // Call the function
	   //               db_3wordid_messageform_insert($from_id,$to_3wordid,$from_3wid_id,$to_user_id,$title,$message, $terms)  
	   $insert_result = db_3wordid_messageform_insert(25,$threeword_data['id'],178,$threeword_data['user_id'],$title,$message, 0);
	   
	   //db_3wordid_update_google_csrf($data['id'],$_SESSION["csrf_token"]);
	   //db_3wordid_set_csrf_token($data['id'],$_SESSION["csrf_token"]);
	   
	   //error_log('messages form process csrf_token ' . $_SESSION["csrf_token"]);
		
	   header('location:index.php?message=Thank, you your message to ' . $threeword_data['threeword'] . ' is being processed');
	   
	} catch (Exception $e) {
		error_log("Error: " . $e->getMessage());
	}	

} else {
		
		header('location:' . $main_url . '?message=You can only send new messages when the old one has been read');
}

}

?>
