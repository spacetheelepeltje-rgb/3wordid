<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  // Must be logged in
  $data = current_user_from_session($conn);
  if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode('Please log in.'));
    exit;
  }

try {
	
   $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
  
   if($id==0) {
	    header('location:3wid_list.php');
	    exit;
	}

   // CSRF protection: the delete link carries the session action token
   $token = isset($_GET['action_token']) ? $_GET['action_token'] : '';
   if (!check_action_token($token)) {
       error_log('message delete: invalid action token for message ' . $id . ' user ' . $data['id']);
       header('location:3wid_list.php?message=' . rawurlencode('Session expired, please try again.'));
       exit;
   }

   // Only the owner of the 3WordID that received the message may delete it
   $message = db_3wordid_get_message($id);
   if (!$message || !db_3wordid_owned_by($message['3wid_id'], $data['id'])) {
       error_log('message delete: user ' . $data['id'] . ' may not delete message ' . $id);
       header('location:3wid_list.php');
       exit;
   }

    // Call the function
    db_message_delete($id);
    
    header('location:3wid_list_messages.php?wid_id=' . (int)$message['3wid_id']);
    exit;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

?>
