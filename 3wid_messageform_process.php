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

	// Login comes from the session only (no csrf_token login any more)
	$data = current_user_from_session(get_db_conn());
	
    //error_log('veriables ' . json_encode($_POST) . ' session ' . json_encode($_SESSION));
  
	if($data == NULL) {
	   header('location:' . $main_url);
	   exit;
    } 

	// Consent is given once at signup (3wid_privacy_consent.php); no message without it
	if ((int) ($data['privacy_consent'] ?? 0) !== 1) {
	   header('Location: 3wid_privacy_consent.php');
	   exit;
	}

	// The form must come from our own message page (CSRF protection)
	if (!check_action_token($_POST['action_token'] ?? '')) {
	   error_log('messageform process: missing or wrong action token for user ' . $data['id']);
	   header('location:3wid_list.php?message=' . rawurlencode(t_plain('mp.session')));
	   exit;
	}
	  
   $message = $_POST["message"];
 
   $threeword = $_POST["threeword"];
   
   $title = $_POST["title"];
   
   $from_id = (int) $_POST["from_id"];
   
   // The form no longer has its own checkbox: the user's privacy consent (checked above) counts
   $terms = 1;
   
   // message send to a three word id. The user of the ID is known. It has to show up in the ID's message row.
   
   //$new = db_3wordid_mailform_select($id, $email,'unread');
   
   //error_log('message is ' . $message);

   $threeword_data =  db_3wordid_get_threeword($threeword);

   // The 3WordID to send from has to be one of the user's own
   if(!db_3wordid_owned_by($from_id, $data['id'])) {
	   error_log('messageform process: user ' . $data['id'] . ' tried to send from 3wid ' . $from_id);
	   header('location:3wid_list.php?message=' . rawurlencode(t_plain('mp.session')));
	   exit;
   }

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
		
	   // Show the sent message as a chat bubble on the message page (read once there)
	   $_SESSION['chat_sent'] = array('to' => $threeword_data['threeword'], 'title' => (string) $title, 'message' => (string) $message, 'time' => time());
	   header('location:3wid_messageform.php?threeword_id=' . $from_id . '&threeword=' . rawurlencode($threeword_data['threeword']));
	   
	} catch (Exception $e) {
		error_log("Error: " . $e->getMessage());
	}	

} else {
		
		//header('location:' . $main_url . '?message=You can only send new messages when the old one has been read');
}

?>
