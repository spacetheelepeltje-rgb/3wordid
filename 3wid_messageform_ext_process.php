<?php

  // external user ID is 25 
  // 

  require_once 'php/functions.php';
  require_once 'login/config.php';
  require_once 'php/chat.php';

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

   $message = trim((string) $_POST["message"]);

   // There is no subject field: the title is always the start of the message
   // (a "title" sent by an old, cached form is ignored)
   $title = chat_title_from_message($message);
   
   $threeword = (string) ($_POST["threeword"] ?? '');

   $threeword_val = db_3wid_validate($threeword);
   
   $threeword_data =  db_3wordid_get_threeword($threeword_val);

   //error_log($threeword_val . ' ' . ' ' . $threeword . ' ' . json_encode($threeword_data));

   // The visitor placeholder itself is not a real recipient
   if (chat_is_visitor_wid($threeword_data)) {
	   $threeword_data = null;
   }

   // Switched off by its owner: nothing is stored, the "not active at the moment" page instead
   if ($threeword_data && (int) ($threeword_data['enabled'] ?? 0) !== 1) {
	   header('location:3wid_inactive.php?threewords=' . rawurlencode($threeword_data['threeword']));
	   exit;
   }

   // An empty message is never stored (the form checks this too): back to the form
   if ($threeword_data && $message === '') {
	   header('location:3wid_messageform_ext.php?threeword=' . rawurlencode($threeword_data['threeword']));
	   exit;
   }

   // Spam protection for visitors (limits in php/functions.php): back to the form with a note
   $visitor_key = '';
   if ($threeword_data) {
	   $visitor_key = msg_visitor_key();
	   $rate_error = db_3wordid_message_rate_error((int) $threeword_data['id'], 0, $visitor_key);
	   if ($rate_error !== '') {
		   $_SESSION['chat_error'] = $rate_error;
		   $_SESSION['chat_draft'] = $message;
		   header('location:3wid_messageform_ext.php?threeword=' . rawurlencode($threeword_data['threeword']));
		   exit;
	   }
   }

   if($threeword_data) { // valid recipient
		
	try {
		
		//error_log('inserting');

	   //error_log('threeword data found ' . json_encode($threeword_data));
	   //error_log('inserting mailform message for 3wordid ' . $id . ' message ' . $mailmessage . ' email ' . $email . ' terms ' . $terms);
       //  ($from_id,$to_3wordid,$to_user_id,$title,$message, $terms)
	   // Call the function
	   //               db_3wordid_messageform_insert($from_id,$to_3wordid,$from_3wid_id,$to_user_id,$title,$message, $terms)  
	   $insert_result = db_3wordid_messageform_insert(CHAT_VISITOR_USER_ID,$threeword_data['id'],CHAT_VISITOR_WID,$threeword_data['user_id'],$title,$message, 0, $visitor_key);
	   db_3wordid_message_rate_after_visitor();
	   
	   //db_3wordid_update_google_csrf($data['id'],$_SESSION["csrf_token"]);
	   //db_3wordid_set_csrf_token($data['id'],$_SESSION["csrf_token"]);
	   
	   //error_log('messages form process csrf_token ' . $_SESSION["csrf_token"]);
		
	   // Show the sent message as a chat bubble on the message page (read once there)
	   $_SESSION['chat_sent'] = array('to' => $threeword_data['threeword'], 'title' => (string) $title, 'message' => (string) $message, 'time' => time());
	   header('location:3wid_messageform_ext.php?threeword=' . rawurlencode($threeword_data['threeword']));
	   
	} catch (Exception $e) {
		error_log("Error: " . $e->getMessage());
	}	

} else {
		
		header('location:' . $main_url . '?message=' . rawurlencode(t_plain('ext.bad_recipient')));
}

}

?>
