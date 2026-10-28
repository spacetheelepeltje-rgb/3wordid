<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
 
  $data = current_user_from_session($conn);
  
  if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_expired')));
    exit;
  }
  
 // error_log('post ' . json_encode($_POST) . ' get ' . json_encode($_GET) . ' session ' . json_encode($_SESSION));
  
try {
	
   $user_id = $data['id'];
   
   $threeword = $_POST["threeword"];
   
   $count = db_3wid_count_3wids($user_id);
   
   //error_log('count is ' . $count . ' credit is ' . $data['credit']);
   
   if($count > 2 && $data['credit']==0) { 
		error_log('not enough credit for more 3wordids');
   }

   $notification = (string) ($_POST["notification"] ?? '');
   
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

   $linkthru = $_POST["linkthru"];
   
   if(isset($_POST["linkthruflag"])) {
		$linkthruflag = 1;
		} else {
		$linkthruflag =0;  
   }
   


   // "Use forward URL" and "Link to chat" exclude each other (chat wins for a new 3WordID)
   [$linkthruflag, $emailform] = wid_exclusive_link_flags($linkthruflag, $emailform);

   // "Message with link": only when forward and chat are both off (they win)
   $link_title = (string) ($_POST["link_title"] ?? '');
   $msglink = (isset($_POST["msglinkflag"]) && $linkthruflag === 0 && $emailform === 0 && db_3wordid_has_link_title()) ? 1 : 0;

   // Not accepted: back to the form with the reason, and with what was typed
   $paying = user_is_paying($data);
   $error = wid_threeword_error((string) $threeword, $paying)
         ?? wid_note_error($clean_notification, $linkthruflag, $emailform)
         ?? wid_link_error($linkthruflag, $msglink, (string) $linkthru, $link_title);
   $valid_threeword = $error === null ? db_3wid_validate_type($threeword, $paying ? 1 : 0) : false;
   if (!$valid_threeword) {
       $_SESSION['add_form'] = [
           'error' => t_plain($error ?? 'js.max_three'),
           'threeword' => preg_match('/^.{0,80}/su', (string) $threeword, $m) ? $m[0] : '',
           'notification' => $notification,
           'linkthru' => (string) $linkthru,
           'linkthruflag' => $linkthruflag,
           'emailform' => $emailform,
           'msglinkflag' => $msglink,
           'link_title' => $link_title,
           'private' => $private,
       ];
       header('location:3wid_add.php');
       exit;
   }
   $threeword = $valid_threeword;

   // Call the function
   $new_id = db_3wordid_insert($user_id,$threeword, $clean_notification, $linkthru, $linkthruflag,$emailform,$private);
    
   if($new_id == FALSE) {
	   error_log('aborted insert');
	   } elseif ($msglink === 1) {
	   db_3wordid_set_link_title($new_id, $link_title);
	   }
    
   //header('location:' .   $loginUrl);
   header('location:3wid_list.php'); 
   exit;
   
   //3wid_list.php?csrf_token=' . $_POST['csrf_token']);
} catch (Exception $e) {
    echo t('common.error', ['msg' => $e->getMessage()]);
}



?>
