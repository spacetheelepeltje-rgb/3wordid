<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';


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
  
if($_SESSION['csrf_token'] != $_POST['csrf_token']) {
	header('location:' . $main_url);
 }
	  
 if(isset($_POST["id"])) {
	$id = $_POST["id"];
 } else {
	header('location:' . $main_url);
 }

   $mailmessage = $_POST["mailmessage"];
 
   $email = $_POST["email"];
   
   $title = $_POST["title"];
   
   $email = db_3wordid_validate_email($email);
   
   $new = db_3wordid_mailform_select($id, $email,'unread');
   
   error_log('new is ' . $new . ' email  is ' . $email);

   if($email && !$new) {
		
	try {
		
	error_log('inserting');

	   if(isset( $_POST["terms"])) {
			$terms = 1;
			} else {
			$terms =0;  
	   }
	   
	   error_log(json_encode($_POST));
	   error_log('inserting mailform message for 3wordid ' . $id . ' message ' . $mailmessage . ' email ' . $email . ' terms ' . $terms);

	   // Call the function
	   $new_id = db_3wordid_mailform_insert($id,$mailmessage, $email, $terms);
		
	   if($new_id == FALSE) {
		   error_log('aborted insert');
		   } 
		
	   header('location:index.php?message=Thank, you your message is being processed');
	} catch (Exception $e) {
		echo "Error: " . $e->getMessage();
	}	

} else {
		
		header('location:' . $main_url . '?message=You can only send new messages when the old one has been read');
}

?>
