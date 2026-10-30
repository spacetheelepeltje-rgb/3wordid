<?php
  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  // Generate Google Login URL
  $loginUrl = $client->createAuthUrl();
  
  $time = substr(time(),-4);

   if(isset( $_POST['csrf_token'])) { 
	$csrf_token =  $_POST['csrf_token']; 
 } else {
	header('location:' . $main_url);
	exit;
 }

$data = current_user_from_session($conn);

if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
}
  
 $csrf_token =  bin2hex(random_bytes(32));
 
 $_SESSION['csrf_token'] = $csrf_token;

  if(isset($data['id'])) { 
	 db_3wordid_set_csrf_token($data['id'], $csrf_token);
	 
  } 

   // CSRF protection: the form must carry this session's action token
   if (!check_action_token($_POST['action_token'] ?? '')) {
       header('Location: 3wid_list.php?message=' . rawurlencode(t_plain('common.session_expired_retry')));
       exit;
   }

   $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

   // Only the owner of the 3WordID (or the admin, same rule as 3wid_3wid.php) may update it
   if ($data["id"] != 4 && !db_3wordid_owned_by($id, $data["id"])) {
       error_log('update: user ' . $data['id'] . ' does not own 3wid ' . $id);
       header('location:3wid_list.php');
       exit;
   }
   
   $notification = $_POST["notification"];
   
   $pconfig = HTMLPurifier_Config::createDefault();
   $pconfig->set('HTML.Allowed', 'p,br,a[href],strong,em,ul,ol,li,div,span,h1,h2,h3,h4,h5,h6');
   $pconfig->set('HTML.SafeIframe', true);
   $purifier = new HTMLPurifier($pconfig);
   
   $clean_notification = $purifier->purify($notification);

   if (isset($_POST["emailform"])) {
	   $emailform = 1;   
	   } else {
	   $emailform = 0;
	}
	
	if (isset($_POST["private"])) {
	   $private = 1;   
	   } else {
	   $private = 0;
	}
	
	
   //$email = '';
   //$emailform = 0;	
	
   $linkthru = $_POST["linkthru"];
   
   if(isset($_POST["linkthruflag"])) {
		$linkthruflag = 1;
		} else {
		$linkthruflag = 0;  
   }
   
   $old_row = db_3wordid_get($id) ?: null;

   // "Car chat" is a kind of "Link to chat" (only once the car chat SQL has run). Both ticked
   // (no JavaScript): keep the one just switched on.
   $car_ready = car_chat_ready();
   $was_car = $car_ready && car_chat_on($old_row);
   $carchat = ($car_ready && isset($_POST['car_chat'])) ? 1 : 0;
   if ($carchat === 1 && $emailform === 1 && $was_car) {
       $carchat = 0; // "Link to chat" was just ticked on a car chat
   }
   if ($carchat === 1) {
       $emailform = 1;
   }

   // "Use forward URL" and "Link to chat" exclude each other: keep the one just switched on
   [$linkthruflag, $emailform] = wid_exclusive_link_flags($linkthruflag, $emailform, $old_row);
   if ($emailform !== 1) {
       $carchat = 0;
   }

   // Open or closed chat. Defaults when a chat is switched on and the choice was not touched:
   // Car chat open, Link to chat closed. Otherwise what the form sent, or what was saved.
   $was_chat = is_array($old_row) && (int) ($old_row['emailform'] ?? 0) === 1;
   $chat_open = $car_ready ? (int) ($old_row['chat_open'] ?? 0) : 1;
   $access = (string) ($_POST['chat_access'] ?? '');
   $touched = (($_POST['chat_access_touched'] ?? '') === '1');
   if ($car_ready && $emailform === 1) {
       if (!$touched && ($carchat !== ($was_car ? 1 : 0) || !$was_chat)) {
           $chat_open = $carchat === 1 ? 1 : 0;
       } elseif ($access === 'open' || $access === 'closed') {
           $chat_open = $access === 'open' ? 1 : 0;
       }
   }

   // "Message with link": only when forward and chat are both off (they win)
   $link_title = (string) ($_POST["link_title"] ?? '');
   $msglink = (isset($_POST["msglinkflag"]) && $linkthruflag === 0 && $emailform === 0 && db_3wordid_has_link_title()) ? 1 : 0;

   // The Message field is required only when the 3WordID shows it (no forward, no chat);
   // forward and "Message with link" need a valid URL, "Message with link" also a link title
   $note_error = wid_note_error($clean_notification, $linkthruflag, $emailform)
              ?? wid_link_error($linkthruflag, $msglink, (string) $linkthru, $link_title);
   if ($note_error !== null) {
       $_SESSION['edit_form_error'] = [
           'id' => $id,
           'error' => t_plain($note_error),
           // what was sent, so the form shows that again instead of the saved values
           'notification' => $notification,
           'linkthru' => (string) $linkthru,
           'linkthruflag' => $linkthruflag,
           'emailform' => $emailform,
           'msglinkflag' => $msglink,
           'link_title' => $link_title,
           'private' => $private,
           'car_chat' => $carchat,
           'chat_open' => $chat_open,
       ];
       $from = (($_POST['from'] ?? '') === 'admin') ? '&from=admin' : '';
       header('Location: 3wid_3wid.php?id=' . $id . $from);
       exit;
   }

   // Car chat just switched on: the three words must be a Dutch plate in the RDW register,
   // and the request must come from a Dutch IP address (php/car_chat.php). Nothing is saved
   // when that fails.
   $car_info = null;
   if ($carchat === 1 && !$was_car) {
       $car_info = car_chat_enable_check((string) ($old_row['threeword'] ?? ''));
       if ($car_info['error'] !== '') {
           $_SESSION['edit_form_error'] = [
               'id' => $id,
               'error' => t_plain($car_info['error']),
               'notification' => $notification,
               'linkthru' => (string) $linkthru,
               'linkthruflag' => $linkthruflag,
               'emailform' => $emailform,
               'msglinkflag' => $msglink,
               'link_title' => $link_title,
               'private' => $private,
               'car_chat' => 1,
               'chat_open' => $chat_open,
               'car_error' => 1,
           ];
           $from = (($_POST['from'] ?? '') === 'admin') ? '&from=admin' : '';
           header('Location: 3wid_3wid.php?id=' . $id . $from);
           exit;
       }
   }

   if (filter_var($linkthru, FILTER_VALIDATE_URL)) {
		//echo("$url is a valid URL");
		} else {
		// no exit here on purpose: empty or "example.com" style URLs are
		// normalised and validated in db_3wordid_update()
		header('location:3wid_list.php');
	}
   
   //error_log( " id:". $id . " not:" . $notification . " linkthru:" . $linkthru . " linkthruflag:" . $linkthruflag. " email:" . $email . " emailform:" . $emailform);
   
// Example usage:
try {
	
    db_3wordid_update($id, $clean_notification, $linkthru, $linkthruflag, $emailform,$private);

    // Car chat (RDW details stored now, not looked up on every view) and open / closed
    if ($car_ready) {
        if ($carchat === 1 && $car_info !== null) {
            db_car_chat_save($id, true, $car_info['plate'], $car_info['make'], $car_info['colour']);
        } elseif ($carchat === 0 && $was_car) {
            db_car_chat_save($id, false);
        }
        if ($emailform === 1) {
            db_chat_open_set($id, $chat_open === 1);
        }
    }

    // Link title: kept only for "Message with link", cleared for the other options
    db_3wordid_set_link_title($id, $msglink === 1 ? $link_title : '');

    // "Show my photo in chats" (opt-in, off by default). Only when the edit form sent it;
    // does nothing until the show_photo column exists.
    // Consent is the owner's own choice: the admin cannot switch it for someone else.
    if (($_POST['show_photo_form'] ?? '') === '1' && db_3wordid_owned_by($id, $data['id'])) {
        db_3wordid_set_show_photo($id, isset($_POST['show_photo']));
    }

    // The admin edited this from the admin pages: return to the owner's 3WordIDs
    if ((int)$data["id"] === 4 && ($_POST['from'] ?? '') === 'admin') {
        $updated = db_3wordid_get($id);
        if ($updated) {
            header('Location: 3wid_user_3wids.php?user_id=' . (int)$updated['user_id'] . '&message=' . rawurlencode(t_plain('edit.saved')));
            exit;
        }
    }
    
    //error_log('update ' . $csrf_token . ' ' . $_SESSION['csrf_token']);
   // header('location:' . $client->createAuthUrl());
    
    //3wid_list.php?csrf_token=' .  $csrf_token);
    header('location:3wid_list.php');
    exit;
    
} catch (Exception $e) {
    echo t('common.error', ['msg' => $e->getMessage()]);
}



?>
