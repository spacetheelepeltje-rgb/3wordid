<?php
// Delete chat messages. Nothing is really removed: db_message_delete() moves the message to
// 3wordid_messages_deleted (see php/message_delete.php). It is then gone for both sides.
//
//   GET  ?id=<message>&action_token=..        one message (the bin icon on a bubble)
//   POST do=message id=<message>               the same as a form
//   POST do=conversation wid_id=<my 3WordID> with=<conversation key>
//        every message of that conversation: received by this 3WordID and sent from it

  require_once 'php/functions.php';
  require_once 'login/config.php';

  // Must be logged in
  $data = current_user_from_session($conn);
  if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
  }
  $user_id = (int) $data['id'];

try {

   $is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');
   $do = $is_post ? (string) ($_POST['do'] ?? 'message') : 'message';

   // CSRF protection: the link / form carries the session action token
   $token = $is_post ? (string) ($_POST['action_token'] ?? '') : (string) ($_GET['action_token'] ?? '');
   if (!check_action_token($token)) {
       error_log('message delete: invalid action token, user ' . $user_id);
       header('location:3wid_list.php?message=' . rawurlencode(t_plain('common.session_expired_retry')));
       exit;
   }

   if ($do === 'conversation') {
       if (!$is_post) {
           header('location:3wid_list.php');
           exit;
       }
       require_once 'php/chat.php';
       $wid_id = (int) ($_POST['wid_id'] ?? 0);
       $with = (string) ($_POST['with'] ?? '');
       // Only a conversation of one of my own 3WordIDs, built the same way as 3wid_list_messages.php
       if (!db_3wordid_owned_by($wid_id, $user_id)) {
           error_log('conversation delete: user ' . $user_id . ' does not own 3wid ' . $wid_id);
           header('location:3wid_list.php');
           exit;
       }
       $messages = (array) db_3wordid_list_messages($wid_id);
       $sent_rows = db_3wordid_list_sent($wid_id, $user_id);
       $conversations = chat_conversations($messages, array(), $sent_rows, array());
       if ($with === '' || !isset($conversations[$with])) {
           header('location:3wid_list_messages.php?wid_id=' . $wid_id);
           exit;
       }
       $n = 0;
       foreach ($conversations[$with]['items'] as $it) {
           $row = db_message_row((int) $it['id']);
           if (message_delete_role($row, $user_id) !== '') {
               db_message_delete((int) $row['id']);
               $n++;
           }
       }
       error_log('conversation delete: user ' . $user_id . ' 3wid ' . $wid_id . ' ' . $with . ': ' . $n . ' messages');
       header('location:3wid_list_messages.php?wid_id=' . $wid_id);
       exit;
   }

   $id = $is_post ? (int) ($_POST['id'] ?? 0) : (int) ($_GET['id'] ?? 0);
   if ($id < 1) {
       header('location:3wid_list.php');
       exit;
   }

   // The owner of the 3WordID that received it, or the account that sent it (always)
   $row = db_message_row($id);
   $role = message_delete_role($row, $user_id);
   if ($role === '') {
       error_log('message delete: user ' . $user_id . ' may not delete message ' . $id);
       header('location:3wid_list.php');
       exit;
   }

   db_message_delete($id);

   header('location:' . message_delete_return_url($row, $role, $user_id));
   exit;

} catch (Exception $e) {
    echo t('common.error', ['msg' => $e->getMessage()]);
}
