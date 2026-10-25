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
    header('Location: login/options.php?message=' . rawurlencode('Please log in.'));
    exit;
}
  
 $csrf_token =  bin2hex(random_bytes(32));
 
 $_SESSION['csrf_token'] = $csrf_token;

  if(isset($data['id'])) { 
	 db_3wordid_set_csrf_token($data['id'], $csrf_token);
	 
  } 

   // CSRF protection: the form must carry this session's action token
   if (!check_action_token($_POST['action_token'] ?? '')) {
       header('Location: 3wid_list.php?message=' . rawurlencode('Session expired, please try again.'));
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

    // The admin edited this from the admin pages: return to the owner's 3WordIDs
    if ((int)$data["id"] === 4 && ($_POST['from'] ?? '') === 'admin') {
        $updated = db_3wordid_get($id);
        if ($updated) {
            header('Location: 3wid_user_3wids.php?user_id=' . (int)$updated['user_id'] . '&message=' . rawurlencode('3WordID saved.'));
            exit;
        }
    }
    
    //error_log('update ' . $csrf_token . ' ' . $_SESSION['csrf_token']);
   // header('location:' . $client->createAuthUrl());
    
    //3wid_list.php?csrf_token=' .  $csrf_token);
    header('location:3wid_list.php');
    exit;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}



?>
