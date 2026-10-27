<?php

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
	  
   $message = trim((string) ($_POST["message"] ?? ''));
 
   $threeword = (string) ($_POST["threeword"] ?? '');
   
   $title = trim((string) ($_POST["title"] ?? ''));
   
   $from_id = (int) ($_POST["from_id"] ?? 0);

   // Sent from the composer inside a conversation: go back there, not to the message form
   $to_conversation = (($_POST['return'] ?? '') === 'conv');

   // The conversation composer has no subject field: use the start of the message
   if ($title === '' && $message !== '') {
	   $title = chat_len($message) > 40 ? rtrim(chat_substr($message, 0, 39)) . '…' : $message;
	   $title = preg_replace('/\s+/u', ' ', $title);
   }
   
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

   // Unknown recipient, or the placeholder used for anonymous visitors (they have no
   // account, so a reply would never reach them): back to the form with a clear note
   // Also: a switched-off 3WordID, or an empty message.
   // There is no "one message until a reply" limit here: members can chat back and forth.
   $error = '';
   if (!$threeword_data) {
	   $error = 'chat.err_recipient';
   } elseif (chat_is_visitor_wid($threeword_data)) {
	   $error = 'chat.err_visitor';
   } elseif (isset($threeword_data['enabled']) && (int) $threeword_data['enabled'] !== 1) {
	   $error = 'chat.err_inactive';
   } elseif ($message === '') {
	   $error = 'chat.err_empty';
   }
   $conv_url = ($threeword_data && !chat_is_visitor_wid($threeword_data))
	   ? '3wid_list_messages.php?wid_id=' . $from_id . '&with=' . rawurlencode('w' . (int) $threeword_data['id'])
	   : '3wid_list_messages.php?wid_id=' . $from_id;
   if ($error !== '') {
	   $_SESSION['chat_error'] = $error;
	   header('location:' . ($to_conversation ? $conv_url : '3wid_messageform.php?threeword_id=' . $from_id));
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
		
	   if ($to_conversation) {
		   // Back to the same conversation, scrolled to the newest message
		   $_SESSION['chat_conv_sent'] = $from_id . '|w' . (int) $threeword_data['id'];
		   header('location:' . $conv_url);
		   exit;
	   }

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
