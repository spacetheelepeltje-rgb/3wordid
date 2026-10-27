<?php

include_once( __DIR__ . '/../login/config.php');
require_once __DIR__ . '/session_boot.php';
start_app_session();
require_once __DIR__ . '/i18n.php'; // t(), current_lang() ... for every page

//error_log('path is ' . __DIR__);

//error_log('begin of functions config is '. $config_check . ' session ' . json_encode($_SESSION));

$session_path = ini_get('session.save_path');

$email_consent = 'We collect and store email addresses to provide our services and communicate with you. By checking below checkbox, you consent to the collection and use of your email address as outlined in our Privacy Policy. If you do not agree, please refrain from using this site or contact us to opt out.';

$email_consent_encrypt = 'We use Google Authentication with your consent. Your email address is stored as a one-way hash, not as readable text. We use the hash to recognise you when you log in. The email address you log in with is used only to send what you request; it is held encrypted only during your session and is not stored in readable form. Messages, IP addresses and account data are logged and kept, also after an account is deactivated, so we can respond to lawful requests from law enforcement.';

// Google Analytics, only after the visitor clicks "Accept" in the cookie banner.
// Every page that echoes $google_stats gets the banner. Nothing is sent to Google
// and no _ga cookies are set before Accept. The choice is stored in this browser
// (localStorage key "3wid_cookie_consent") and can be changed with the
// "Cookie settings" link that is added to the page footer, or with any link to
// "#cookie-settings" (for example on terms.php).
$google_stats = str_replace('__CC_TEXTS__', i18n_js_object([
  'aria' => 'cookie.aria', 'text' => 'cookie.text', 'more' => 'cookie.more',
  'reject' => 'cookie.reject', 'accept' => 'cookie.accept', 'settings' => 'cookie.settings',
]), <<<'HTML'
<style>
  #cc-banner{position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;max-width:560px;margin:0 auto;
    background:#fff;color:#0f1c3f;border:1px solid #e6e3dc;border-radius:14px;box-shadow:0 10px 30px rgba(15,28,63,.15);
    padding:18px 20px;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;font-size:14px;line-height:1.5}
  #cc-banner p{margin:0 0 12px}
  #cc-banner a{color:#0f1c3f}
  #cc-banner .cc-buttons{display:flex;gap:10px;flex-wrap:wrap}
  #cc-banner button{flex:1 1 140px;min-height:44px;padding:10px 16px;border-radius:10px;border:1px solid #0f1c3f;
    background:#fff;color:#0f1c3f;font:inherit;font-weight:600;cursor:pointer}
  #cc-banner button:hover,#cc-banner button:focus{background:#0f1c3f;color:#fff}
  .cc-settings-link{display:inline-block;margin:8px;color:#8a93a6;font-size:13px}
  .cc-settings-wrap{text-align:center;padding:12px}
</style>
<script>
(function () {
  var CC = __CC_TEXTS__;
  var KEY = "3wid_cookie_consent";
  var GA_ID = "G-TJWBJT8GJC";
  var loaded = false;

  function getChoice() {
    try { return localStorage.getItem(KEY); } catch (e) { return null; }
  }
  function setChoice(v) {
    try { localStorage.setItem(KEY, v); } catch (e) {}
  }
  function loadGa() {
    if (loaded) { return; }
    loaded = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { dataLayer.push(arguments); };
    gtag("js", new Date());
    gtag("config", GA_ID);
    var s = document.createElement("script");
    s.async = true;
    s.src = "https://www.googletagmanager.com/gtag/js?id=" + GA_ID;
    document.head.appendChild(s);
  }
  function removeGaCookies() {
    var host = location.hostname.replace(/^www\./, "");
    document.cookie.split(";").forEach(function (c) {
      var name = c.split("=")[0].trim();
      if (name === "_ga" || name.indexOf("_ga_") === 0 || name === "_gid" || name === "_gat") {
        var past = "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
        document.cookie = name + past;
        document.cookie = name + past + "; domain=." + host;
        document.cookie = name + past + "; domain=" + location.hostname;
      }
    });
  }
  function hideBanner() {
    var b = document.getElementById("cc-banner");
    if (b) { b.parentNode.removeChild(b); }
  }
  function showBanner() {
    if (document.getElementById("cc-banner")) { return; }
    var b = document.createElement("div");
    b.id = "cc-banner";
    b.setAttribute("role", "dialog");
    b.setAttribute("aria-label", CC.aria);
    b.innerHTML =
      "<p>" + CC.text + " " +
      "<a href=\"/terms.php#data\">" + CC.more + "</a></p>" +
      "<div class=\"cc-buttons\">" +
      "<button type=\"button\" id=\"cc-reject\">" + CC.reject + "</button>" +
      "<button type=\"button\" id=\"cc-accept\">" + CC.accept + "</button>" +
      "</div>";
    document.body.appendChild(b);
    document.getElementById("cc-accept").onclick = function () {
      setChoice("accepted"); hideBanner(); loadGa();
    };
    document.getElementById("cc-reject").onclick = function () {
      var wasLoaded = loaded;
      setChoice("rejected"); hideBanner(); removeGaCookies();
      if (wasLoaded) { location.reload(); }
    };
  }
  window.threewidCookieSettings = function () { showBanner(); };

  function addSettingsLink() {
    if (document.querySelector(".cc-settings-link")) { return; }
    var a = document.createElement("a");
    a.href = "#cookie-settings";
    a.className = "cc-settings-link";
    a.textContent = CC.settings;
    var footer = document.querySelector("footer");
    if (footer) {
      footer.appendChild(a);
    } else {
      var wrap = document.createElement("div");
      wrap.className = "cc-settings-wrap";
      wrap.appendChild(a);
      document.body.appendChild(wrap);
    }
  }

  document.addEventListener("click", function (e) {
    var t = e.target;
    while (t && t.tagName !== "A") { t = t.parentNode; }
    if (t && t.getAttribute && /#cookie-settings$/.test(t.getAttribute("href") || "")) {
      e.preventDefault();
      showBanner();
    }
  });

  var choice = getChoice();
  if (choice === "accepted") { loadGa(); }
  function onReady() {
    addSettingsLink();
    if (choice !== "accepted" && choice !== "rejected") { showBanner(); }
    if (location.hash === "#cookie-settings") { showBanner(); }
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onReady);
  } else {
    onReady();
  }
})();
</script>
HTML
);


function get_db_conn() {
	
	$conn = new mysqli(SERVER, WRITEUSER, WRITEPASSWORD, DATABASE);
	
	return $conn;
	
	}
	
// Function to update user credits
function db_3wid_update_credit_usertype($user_id, $amount, $max_3wids, $usertype) {
	
	$usertype = (int) $usertype;
	
	$conn = get_db_conn();
    // Start transaction
    $conn->begin_transaction();

    try {
        // Validate new credits (e.g., must be non-negative)
        if ( $amount < 0) {
            throw new Exception("Credits cannot be negative.");
        }

        // Prepare the UPDATE statement
        $stmt = $conn->prepare("UPDATE google_users SET credit = ?,user_type=?,max_3wids=? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }

        // Bind parameters (credits as float or int, user_id as int)
        $stmt->bind_param("diii",  $amount,$usertype,$max_3wids,$user_id);

		error_log("Attempting to update user ID: $user_id");
		error_log("Query: UPDATE google_users SET credit = $amount, user_type = $usertype, max_3wids = $max_3wids WHERE id = $user_id");
        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Check if any rows were affected
        if ($stmt->affected_rows === 0) {
            throw new Exception("No user found with ID: $user_id");
        }

        // Commit the transaction
        $conn->commit();
        error_log("Credits updated successfully! User ID: $user_id, New Credits:   $amount");

        // Close statement
        $stmt->close();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
    }
}		

// Function to update user credits
function db_3wid_update_credit($user_id, $amount) {
	
	$conn = get_db_conn();
    // Start transaction
    $conn->begin_transaction();

    try {
        // Validate new credits (e.g., must be non-negative)
        if ( $amount < 0) {
            throw new Exception("Credits cannot be negative.");
        }

        // Prepare the UPDATE statement
        $stmt = $conn->prepare("UPDATE google_users SET credit = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }

        // Bind parameters (credits as float or int, user_id as int)
        $stmt->bind_param("di",  $amount,$user_id);

        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Check if any rows were affected
        if ($stmt->affected_rows === 0) {
            throw new Exception("No user found with ID: $user_id");
        }

        // Commit the transaction
        $conn->commit();
        error_log("Credits updated successfully! User ID: $user_id, New Credits:   $amount");

        // Close statement
        $stmt->close();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
    }
}	
	
function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}	

function db_3wordid_count_messages($wid_id) {
	
	// Create connection
    $conn = get_db_conn();
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Prepare the SQL statement
    $sql = "SELECT count(*) as count FROM 3wordid_messages WHERE 3wid_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind the ID parameter
    $stmt->bind_param("i", $wid_id); // "i" indicates integer type
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch the data
    $rows = $result->fetch_assoc();
    
     // Clean up
    $stmt->close();
    $conn->close();
	
	return $rows;
	
	}

function db_3wordid_list_messages($wid_id) {
	
	// Create connection
    $conn = get_db_conn();
    
    $wid_id = (int) $wid_id;
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Prepare the SQL statement
    $sql = "SELECT m.id as id,w.threeword as threeword, m.from_id as from_id, m.title as title, m.message as message,u.picture as picture FROM 3wordid_messages as m, google_users as u,3wordid as w WHERE m.3wid_id = ? and w.id=m.3wid_from_id and m.from_id=u.id ORDER BY m.id DESC";
    $stmt = $conn->prepare($sql);
    
    error_log('query ' .  $sql);
    
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind the ID parameter
    $stmt->bind_param("i", $wid_id); // "i" indicates integer type
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch the data
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    // Clean up
    $stmt->close();
    $conn->close();
    
    // Return the result or null if no record found
    return $data ? $data : null;
	
	
}	

function db_3wordid_get_message($message_id) {
	
	// Create connection
    $conn = get_db_conn();
    
    $wid_id = (int) $wid_id;
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Prepare the SQL statement
    $sql = "SELECT m.id as id, m.3wid_id as 3wid_id, m.from_id as from_id, m.title as title, m.message as message,u.picture as picture FROM 3wordid_messages as m, google_users as u WHERE m.id = ? and m.from_id=u.id";
    $stmt = $conn->prepare($sql);
    
    error_log('query ' .  $sql);
    
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind the ID parameter
    $stmt->bind_param("i", $message_id); // "i" indicates integer type
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch the data
    $data = $result->fetch_assoc();
    
    // Clean up
    $stmt->close();
    $conn->close();
    
    // Return the result or null if no record found
    return $data ? $data : null;
	
	
}	

function db_3wordid_mailform_select($wid_id, $email, $status)  {
	
// Create MySQLi connection
    $conn = get_db_conn();

// Prepare the query
$stmt = $conn->prepare("
    SELECT * 
    FROM 3wordid_messages 
    WHERE 3wid_id = ? 
    AND email = ? 
    AND status = ?
    LIMIT 1
");

// Check if prepare failed
if ($stmt === false) {
    die("Prepare failed: " . $mysqli->error);
}

// Bind parameters (i for integer, s for string)
$stmt->bind_param("iss", $wid_id, $email,$status);

// Execute the query
if ($stmt->execute()) {
    // Get the result
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

	error_log('mailform_select row is ' . json_encode($row));

    // Check if a record was found
    if ($row) {
		error_log('row found ' . json_encode($row));
        return $row;
    } else {
        return false;
    }
} else {
   error_log("db_3wordid_mailform_select Execute failed: " . $stmt->error);
}

// Close statement and connection
$stmt->close();
$mysqli->close();
	
}

function db_3wordid_messageform_insert($from_id,$to_3wordid,$from_3wid_id,$to_user_id,$title,$message, $terms) {
	
    $conn = get_db_conn();

    // Check connection
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
        return false;
    }

    // Prepare the SQL INSERT statement
    $sql = "INSERT INTO 3wordid_messages (from_id, 3wid_id,3wid_from_id,to_id, title, message, terms) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    
    // Prepare the statement
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        $conn->close();
        return false;
    }

    // Bind parameters to prevent SQL injection
    $stmt->bind_param("iiiissi", $from_id, $to_3wordid,$from_3wid_id,$to_user_id, $title, $message, $terms);

    // Execute the statement
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return true; // Success
    } else {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false; // Failure
    }
}

function db_3wordid_hasThreeAlphabeticNumeric($string) {
    // Match exactly three alphanumeric parts separated by spaces
    $pattern = '/^[a-zA-Z0-9]+\s+[a-zA-Z0-9]+\s+[a-zA-Z0-9]+$/';
    return preg_match($pattern, $string) === 1;
}
	
function db_3wordid_get_threeword($threeword) {
	
	$conn = get_db_conn();
	
	$check = db_3wordid_hasThreeAlphabeticNumeric($threeword);
	
	//error_log($check);
	
	if($check) {

		$query = "SELECT * FROM 3wordid WHERE threeword = ?";

		//echo $query; // For debugging, remove in production

		$stmt = $conn->prepare($query);

		if ($stmt === false) {
			die("Prepare failed: " . $conn->error); // Add error checking
		}

		// Bind the parameter (user_id is likely an integer, so use "i")
		$stmt->bind_param("s", $threeword); // Corrected from $threewords to $user_id

		// Execute the query
		$stmt->execute();

		// Get the result
		$result = $stmt->get_result();

		// Fetch the row as an associative array
		$row = $result->fetch_assoc(); // Changed $rows to $row (singular, as it's one row)

		// Close the statement and result
		$stmt->close();
		
		return $row;
	
	} 
	
	//error_log('failed check ' . $threeword);
	return false;
	

	}	


function db_3wordid_get_hash($hash) {
	
	$conn = get_db_conn();
	
	if(!empty($hash) && ctype_alnum($hash)) {

	$query = "SELECT * FROM 3wordid WHERE hash = ?";

	//echo $query; // For debugging, remove in production

	$stmt = $conn->prepare($query);

	if ($stmt === false) {
		die("Prepare failed: " . $conn->error); // Add error checking
	}

	// Bind the parameter (user_id is likely an integer, so use "i")
	$stmt->bind_param("s", $hash); // Corrected from $threewords to $user_id

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();

	// Fetch the row as an associative array
	$row = $result->fetch_assoc(); // Changed $rows to $row (singular, as it's one row)

	// Close the statement and result
	$stmt->close();
	
	return $row;
	
	} else {
		return false;
	}
	
	}

function sanitizeInput($data) {
	
	//error_log('before ' . $data);
	
	if(strpos($data,'+') != NULL) {
		$data = urldecode($data);
	}
	
	$data = htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
	
	//error_log('after ' . $data);
	
    return $data;
}

function store_paypal_data ($post_data) {
	
	$threewords = $post_data['threewords'];
	echo $threewords;
	// does it exist?
	$hash = gethash(trim($threewords)); 
	echo $hash;
	if(doeshashexist($hash)) {
		echo "existing account";
		// store data
		// update account data with flag
		
	} else {
		// no password in this case what to do?
		// send email to email address?
		// store data
		// update account data with flag
		// 
		
    }	
	
	
	}	
	
function makehash($string) {
	
	$magickey = md5("zappaincaroads");
	
	return  md5($string . $magickey);
	
	}


function gethash($threewords) {
	
	$threewords = strtolower($threewords);
	
	$threewords = preg_replace('/[^a-zA-Z ]/', '', $threewords);

	$threewords_asteriks = str_replace(' ','*', $threewords); // replace spaces

	$md5_words = makehash($threewords_asteriks);
	
	return array($md5_words,$threewords);

}

function doeshashexist($hash) {
	
		$filename = './3wids/' . $hash;
	
		if(file_exists($filename)) return True;
	    
	    return False;		
		
	}	

function check_auth() {
	
	if(isset($_SESSION["user_token"])) {
		
    //error_log("User token set " . $_SESSION["user_token"]);

	$token = $_SESSION['user_token'];
	
	$conn = get_db_conn();
	
	$conn->real_escape_string($token);		
	
	$query = "SELECT * FROM google_users WHERE token =? ";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("s", $token);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();
	
	// Fetch the row as an associative array
	$row = $result->fetch_assoc();
	
	return $row;
	
		
    } else {
		
		return false;	
	}

	}

function db_3wordid_reseller_codes_status($status) {
	
	// get reseller tokens by status
	
    // Database connection (adjust with your credentials)
    $conn = get_db_conn();
    
    // Check connection
    if ($mysqli->connect_error) {
        echo "Connection failed: " . $mysqli->connect_error;
        return false;
    }
    
    // Prepare the select statement
    $stmt = $mysqli->prepare("SELECT * FROM 3wordid_resellers WHERE status = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $mysqli->error;
        $mysqli->close();
        return false;
    }
    
    // Bind the status parameter
    $stmt->bind_param("i", $status);
    
    // Execute the query
    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error;
        $stmt->close();
        $mysqli->close();
        return false;
    }
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch rows
    $rows = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            echo "ID: {$row['id']}, User ID: {$row['user_id']}, Code: {$row['code']}, Status: {$row['status']}, Creation Date: {$row['creation_date']}\n";
        }
    } else {
        echo "No rows found with status {$status}.\n";
    }
    
    // Clean up
    $stmt->close();
    $mysqli->close();
    
    return $rows; // Return the rows for further processing if needed
}

function db_3wordid_reseller_codes_reseller($user_id) {
	
	// get reseller tokens by status
	
    // Database connection (adjust with your credentials)
    $conn = get_db_conn();
    
    // Check connection
    if ($mysqli->connect_error) {
        echo "Connection failed: " . $mysqli->connect_error;
        return false;
    }
    
    // Prepare the select statement
    $stmt = $mysqli->prepare("SELECT * FROM 3wordid_resellers WHERE user_id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $mysqli->error;
        $mysqli->close();
        return false;
    }
    
    // Bind the status parameter
    $stmt->bind_param("i", $user_id);
    
    // Execute the query
    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error;
        $stmt->close();
        $mysqli->close();
        return false;
    }
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch rows
    $rows = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            echo "ID: {$row['id']}, User ID: {$row['user_id']}, Code: {$row['code']}, Status: {$row['status']}, Creation Date: {$row['creation_date']}\n";
        }
    } else {
        echo "No rows found with status {$status}.\n";
    }
    
    // Clean up
    $stmt->close();
    $mysqli->close();
    
    return $rows; // Return the rows for further processing if needed
}


function db_3wordid_reseller_set_status($id, $status) {
    // Database connection (adjust with your credentials)
    // change reseller token status by token id
    $conn = get_db_conn();
    
    // Check connection
    if ($conn->connect_error) {
        echo "Connection failed: " . $mysqli->connect_error;
        return false;
    }
    
    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE 3wordid_resellers SET status = ? WHERE id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $mysqli->error;
        $mysqli->close();
        return false;
    }
    
    // Bind parameters
    $stmt->bind_param("ii", $status, $id);
    
    // Execute the update
    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error;
        $stmt->close();
        $conn->close();
        return false;
    }
    
    // Check if any rows were affected
    $affected_rows = $stmt->affected_rows;
    
    // Clean up
    $stmt->close();
    $mysqli->close();
    
    // Return true if a row was updated, false if no rows were affected
    return $affected_rows > 0;
}

function db_3wordid_reseller_gen_codes($user_id, $number_codes) {
	
	// generate tokens with a user id (reseller ID)
	
    // Database connection (adjust with your credentials)
    $conn = get_db_conn();
    
   // Check connection
    if ($mysqli->connect_error) {
        echo "Connection failed: " . $mysqli->connect_error;
        return false;
    }
    
    // Prepare the insert statement
    $stmt = $mysqli->prepare("INSERT INTO 3wordid_resellers (user_id, code, status, creation_date) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo "Prepare failed: " . $mysqli->error;
        $mysqli->close();
        return false;
    }
    
    // Bind parameters
    $status = 0; // Default status (adjust as needed)
    $creation_date = date('Y-m-d H:i:s');
    $stmt->bind_param("isis", $user_id, $code, $status, $creation_date);
    
    // Generate and insert the specified number of codes
    for ($i = 0; $i < $number_codes; $i++) {
        // Generate a random 5-digit code
        $code = sprintf("%05d", mt_rand(0, 99999));
        
        // Execute the insert
        if (!$stmt->execute()) {
            echo "Execute failed: " . $stmt->error;
            $stmt->close();
            $mysqli->close();
            return false;
        }
    }
    
    // Clean up
    $stmt->close();
    $mysqli->close();
    
    return true;
}
	
function db_3wordid_get_google_email($email) {
	// email is md5 hashed so this is the required input

	if($email == '') return;
	
	$pattern = '/^[0-9a-f]{32}$/i';

	if (preg_match($pattern, $email)) {
		
	} else {
		return null;
	}
	
	$conn = get_db_conn();
		
	$query = "SELECT * FROM google_users WHERE email =? ";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("s", $email);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();
	
	// Fetch the row as an associative array
	$row = $result->fetch_assoc();
	
	return $row;
	
	}		
	
function db_3wordid_get_google_user($id) {
	
	$id = (int) $id;
	
	if($id == NULL) return;
	
	error_log('google user id is ' . $id);
	
	$conn = get_db_conn();
		
	$query = "SELECT * FROM google_users WHERE id =? ";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("s", $id);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();
	
	// Fetch the row as an associative array
	$row = $result->fetch_assoc();
	
	return $row;
	
	}	
	
function db_3wordid_get_google_users($page,$page_size,$order) {
	
	$conn = get_db_conn();

    $page = (int) $page;
    $page_size = (int) $page_size;
    
    if(!in_array($order,['ASC','DESC'])) return false;

	$limit_low = $page*$page_size;

	// Prepare and execute query (LIMIT offset, number of rows)
	// One query: every user plus how many 3WordIDs they own (a subquery that
	// only runs for the users on this page) and, once the column exists,
	// their last login. The 3WordIDs count is never a separate query per row.
	$last_login = google_users_has_column($conn, 'last_login') ? 'u.last_login' : 'NULL';
	$query = "SELECT u.*,
	                 (SELECT COUNT(*) FROM 3wordid w WHERE w.user_id = u.id) AS nr_3wids,
	                 " . $last_login . " AS last_login_at
	          FROM google_users u
	          ORDER BY u.id " . $order . "
	          LIMIT " . $limit_low . "," . $page_size;
	$stmt = $conn->prepare($query);
	$stmt->execute();
	$result = $stmt->get_result();

	// Fetch results
	$users = [];
	while ($row = $result->fetch_assoc()) {
		$users[] = $row;
	}

	return $users;
	
	}		
	
function check_csrf_token($csrf_token) {
	
	if($csrf_token != NULL){
		
	error_log('finding user with csrf_token ' . $csrf_token);	
		
	$conn = get_db_conn();
	
	$conn->real_escape_string($csrf_token);		
	
	$query = "SELECT * FROM google_users WHERE csrf_token =? ";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("s", $csrf_token);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();
	
	// Fetch the row as an associative array
	$row = $result->fetch_assoc();
	
	return $row;
	
		
    } else {
		
		return false;	
	}

	}	
	
function db_3wid_log_credit_mutation($user_id,$change,$update_string) {
	// prints a line to a log file which 
	// user id, credit amount, json of update query	


	}	
	
function check_expiration($user_id) {
	
	// select 3wid of user id
	// 


	}				
	
// Returns the logged in user (row from google_users) or false.
// Login is taken from the session only (the same check as
// current_user_from_session in php/session_boot.php). A csrf_token in the
// address bar or in a form is NOT accepted as a login any more.
// The parameters are kept so older callers keep working; $post and $get are ignored.
function check_credentials($session = null, $post = null, $get = null) {

	if (session_status() !== PHP_SESSION_ACTIVE) {
		start_app_session();
	}

	$user = current_user_from_session(get_db_conn());

	// current_user_from_session already refuses blocked users (user_type 9)
	return $user ? $user : false;
}	
	
function db_3wid_log_update($json) {
	// prints a line to a log file which 
	// user id, credit amount, json of update query	
	file_put_contents('logging/update_log.html',$json . '<br>');


	}
	
function db_3wid_log_login($json) {
	// prints a line to a log file which 
	// user id, credit amount, json of update query	
	file_put_contents('logging/login_log.html',$json . '<br>');

	}			


function calculate_cost($yearly_base,$nr_of_threewords,$nr_of_threewords_enabled) {


	}

function check_mobile() {
	
	if(isset($_SERVER['HTTP_USER_AGENT'])) {

	$userAgent = $_SERVER['HTTP_USER_AGENT'];

	if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
		return "mobile";
	} else {
		return "desktop";
	}
	
	} else {
		return "mobile";	
	}
	
	}

function check_session() {
	
	$session_id = session_id();
	
	$conn = get_db_conn();
	
	//$conn->real_escape_string($token);		
	
	$query = "SELECT * FROM google_users WHERE session_id =? ";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("s", $session_id);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();
	
	// Fetch the row as an associative array
	$row = $result->fetch_assoc();
	
	return $row;

	}	

function set_csrf_token($user_id,$csrf_token) {


	}

function db_3wordid_get_user($id,$user_id) {
	
	error_log("get data for 3wid id " . $id . " and user " . $user_id);
	
	$conn = get_db_conn();
	
	$query = "SELECT * FROM 3wordid WHERE id=? AND user_id=? ";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("ii", $id,$user_id);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();
	
	// Fetch the row as an associative array
	$row = $result->fetch_assoc();
	
	return $row;


	}

function db_3wordid_get($id) {
	
	//error_log("get data for id " . $id);
	
	$conn = get_db_conn();
	
	$query = "SELECT * FROM 3wordid WHERE id=? ";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("i", $id);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();
	
	// Fetch the row as an associative array
	$row = $result->fetch_assoc();
	
	return $row;


	}

function db_3wordid_toggle($id, $user_id) {
    // Fetch current row
    $row = db_3wordid_get($id);
    if (!$row) {
        error_log("No row found for id: " . $id);
        return false;
    }
    
    error_log("Starting toggle - threeword:" . $row["threeword"] . " enabled:" . $row["enabled"] . " id:" . $row["id"] . " user_id:" . $row['user_id']);
    
    // Get database connection
    $conn = get_db_conn();
    if (!$conn) {
        error_log("Failed to get database connection");
        return false;
    }
    
    // Determine new value
    $new_enabled = ($row["enabled"] == 0) ? 1 : 0;
    
    $query = "UPDATE 3wordid SET enabled = ? WHERE id = ?";
    error_log("Query prepared: " . $query . " | new_enabled: " . $new_enabled . " | id: " . $id);
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " | Error code: " . $conn->errno);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("ii", $new_enabled, $id);
    
    $success = $stmt->execute();
    if ($success === false) {
        error_log("Execute failed: " . $stmt->error . " | Error code: " . $stmt->errno);
    } else {
        $affected_rows = $stmt->affected_rows;
        error_log("Execute success: true | Affected rows: " . $affected_rows);
    }
    
    // Verify the update
    $check_query = "SELECT enabled FROM 3wordid WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->bind_result($current_enabled);
    $check_stmt->fetch();
    error_log("Post-update check - id: " . $id . " enabled: " . $current_enabled);
    
    $stmt->close();
    $check_stmt->close();
    $conn->close();
    
    return $success ? $id : false;
}

function log_visit($file) {
	
	error_log($file . ' ip ' . get_client_ip() . ' on ' . check_mobile());
	
}

function db_3wordid_list_top_10() {

	$conn = get_db_conn();

	$query = "SELECT id,threeword,linkthru,linkthruflag,notification,enabled,views FROM 3wordid WHERE enabled=1 ORDER BY views DESC LIMIT 10;";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	//$stmt->bind_param("i", $id);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();

	// Fetch the row as an associative array
	$rows = $result->fetch_all(MYSQLI_ASSOC);
	
	return $rows;
	
	
	}

function db_3wordid_list_recent() {
		
	$conn = get_db_conn();
	
	//$query = "SELECT JSON_ARRAYAGG(JSON_OBJECT(id,threeword,linkthru,linkthruflag)) as json_result FROM 3wordid WHERE user_id= ?";
	
	//$query = "SELECT DISTINCT id,threeword,linkthru,views,update_date FROM 3wordid WHERE enabled=1 and private=0 ORDER BY update_date DESC LIMIT 20;";

    $query = "SELECT DISTINCT id,threeword,linkthru,views,update_date FROM 3wordid WHERE enabled=1 and private=0 ORDER BY views DESC LIMIT 40;";


	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	//$stmt->bind_param("ii", 1,1);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();

	// Fetch the row as an associative array
	$rows = $result->fetch_all(MYSQLI_ASSOC);
	
	return $rows;
	
	
	}

function db_3wid_daysUntilNWeeksAfter($dateTimeString, $weeks) {
    // Create DateTime object for the supplied date
    $baseDate = new DateTime($dateTimeString);
    
    // Add n weeks to the supplied date
    $baseDate->modify("+$weeks weeks");
    
    // Create DateTime object for current time
    $currentDate = new DateTime();
    $currentDate->setTimestamp(time());
    
    // Calculate the difference
    $interval = $currentDate->diff($baseDate);
    
    // Return days (positive if future, negative if past)
    return $interval->days * ($interval->invert ? -1 : 1);
}	
	
function db_3wid_daysSinceDate($dateTimeString) {
    $suppliedDate = new DateTime($dateTimeString);
    $currentDate = new DateTime();
    $currentDate->setTimestamp(time());
    
    $interval = $currentDate->diff($suppliedDate);
    return $interval->days * ($interval->invert ? 1 : -1);
}	
	
function db_3wid_addTwoWeeks($dateTimeString) {
    $date = new DateTime($dateTimeString);
    $date->modify('+2 weeks');
    return $date->format('Y-m-d H:i:s');
}

function db_3wid_daysUntilDate($dateTimeString) {
    $targetDate = new DateTime($dateTimeString);
    $currentDate = new DateTime();
    $currentDate->setTimestamp(time());
    
    $interval = $currentDate->diff($targetDate);
    return $interval->days * ($interval->invert ? -1 : 1);
}	

function db_3wordid_list($user_id) {
		
	$conn = get_db_conn();
	
	//$query = "SELECT JSON_ARRAYAGG(JSON_OBJECT(id,threeword,linkthru,linkthruflag)) as json_result FROM 3wordid WHERE user_id= ?";
	
	$query = "SELECT id,threeword,linkthru,linkthruflag,notification,enabled,views,creation_date FROM 3wordid WHERE user_id=? ORDER BY threeword ASC";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("i", $user_id);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();

	// Fetch the row as an associative array
	$rows = $result->fetch_all(MYSQLI_ASSOC);
	
	return $rows;
	
	
	}
	
function db_message_backup($id) {
	
	$id = (int) $id;

    if(gettype($id)!='integer') {
		die("user_id must be a int " . $id . " " . gettype($id));
	}
	
	 // Database connection
    $conn = get_db_conn();

    // Prepare the DELETE query
    $query = "INSERT INTO 3wordid_messages_deleted (SELECT * FROM 3wordid_messages WHERE id = ?)";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind the parameter (id is an integer)
    $stmt->bind_param("i", $id);

    // Construct the actual query for debugging
    $debug_query = "DELETE FROM 3wordid WHERE id = " . $id;
    error_log("Executed query: " . $debug_query);  // Logs to error log
    // echo "Executed query: " . $debug_query;     // Uncomment for screen output (debugging only)

    // Execute the statement
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows; // Number of deleted rows
        $stmt->close();

        if ($affected_rows > 0) {
            return true; // Success, record was deleted
        } else {
            return false; // No record found with that ID
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }


	}	

function db_3wordid_backup($id) {
	
	error_log('starting backup');
	
	$id = (int) $id;

    if(gettype($id)!='integer') {
		die("user_id must be a int " . $id . " " . gettype($id));
	}
	
	 // Database connection
    $conn = get_db_conn();

    // Prepare the DELETE query
    $query = "INSERT INTO 3wordid_deleted (SELECT * FROM 3wordid WHERE id = ?)";

	error_log('query ' . $query);
    // Prepare the statement
    $stmt = $conn->prepare($query);
    
    error_log('prepare success');

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
    }

    // Bind the parameter (id is an integer)
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
		
        $affected_rows = $stmt->affected_rows; // Number of deleted rows
        
        $stmt->close();
		
		error_log('backup success');

        if ($affected_rows > 0) {
            return true; // Success, record was deleted
        } else {
            return false; // No record found with that ID
        }
        
    } else {
		error_log('backup failed');
        $error = $stmt->error;
        $stmt->close();
        error_log("Execute failed: " . $error);
    }


	}


function db_3wordid_delete($id) {
	
	$id = (int) $id;

    if(gettype($id)!='integer') {
		die("user_id must be a int " . $id . " " . gettype($id));
	}
	
	db_3wordid_backup($id);
	
	 // Database connection
    $conn = get_db_conn();

    // Prepare the DELETE query
    $query = "DELETE FROM 3wordid WHERE id = ?";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind the parameter (id is an integer)
    $stmt->bind_param("i", $id);

    // Construct the actual query for debugging
    $debug_query = "DELETE FROM 3wordid WHERE id = " . $id;
    
    error_log("Executed query: " . $debug_query);  // Logs to error log
  
    // Execute the statement
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows; // Number of deleted rows
        $stmt->close();

        if ($affected_rows > 0) {
            return true; // Success, record was deleted
        } else {
            return false; // No record found with that ID
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }
	
	
	}	

function db_message_delete($id) {
	
	$id = (int) $id;

    if(gettype($id)!='integer') {
		die("user_id must be a int " . $id . " " . gettype($id));
	}
	
	db_message_backup($id);
	
	 // Database connection
    $conn = get_db_conn();

    // Prepare the DELETE query
    $query = "DELETE FROM 3wordid_messages WHERE id = ?";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind the parameter (id is an integer)
    $stmt->bind_param("i", $id);

    // Construct the actual query for debugging
    $debug_query = "DELETE FROM 3wordid_messages WHERE id = " . $id;
    error_log("Executed query: " . $debug_query);  // Logs to error log
    // echo "Executed query: " . $debug_query;     // Uncomment for screen output (debugging only)

    // Execute the statement
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows; // Number of deleted rows
        $stmt->close();

        if ($affected_rows > 0) {
            return true; // Success, record was deleted
        } else {
            return false; // No record found with that ID
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }
	
	
	}	
	
function db_3wordid_exists($threeword) {
    // Validation: threeword must be a string
    if (!is_string($threeword)) {
        //error_log("Invalid threeword: must be a string");
    }
    
    // Database connection
    $conn = get_db_conn();

    // Prepare the SELECT query to check existence
    $query = "SELECT COUNT(*) as count FROM 3wordid WHERE threeword = ?";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind the parameter (threeword is a string)
    $stmt->bind_param("s", $threeword);

    // Construct the actual query for debugging
    $debug_query = "SELECT COUNT(*) as count FROM 3wordid WHERE threeword = '" . $conn->real_escape_string($threeword) . "'";
    error_log("Executed query: " . $debug_query);  // Logs to error log
    // echo "Executed query: " . $debug_query;     // Uncomment for screen output (debugging only)

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    error_log('count is ' . $row['count']);    

    // Execute the statement
    if ($row['count'] > 0) {
        // Get the result
        $stmt->close();
        error_log('threeword exists ' . $threeword);
        return true; // Returns true if threeword exists, false if not
    } else {
		error_log('threeword new ' . $threeword);
        $error = $stmt->error;
        $stmt->close();
        error_log("Execute failed: " . $error);
        return false;
    }
}	

function db_3wordid_validate_email($email) {
	
		//error_log('db_3wordid_validate_email start');
	
		if (empty($email)) {
			$email = '';
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		}
		
		//error_log('db_3wordid_validate_email end');
		
		return $email;
		
	}
	
function db_3wordid_insert($user_id, $threeword, $notification, $linkthru, $linkthruflag, $emailform, $private) {
    // Validation
    // threeword must be string

	$user_id = (int) $user_id;
	
	// check nr of 3wids allowed
	// check 

    if(gettype($user_id)!='integer') {
		error_log("user_id must be a int " . $user_id . " " . gettype($user_id));
		die();
	}
    
    if (!is_string($threeword)) {
        //error_log("Invalid threeword: must be a string");
        // die();
    }
    
    $check = db_3wordid_exists($threeword);
    
    error_log('3wid exists ' . $check);
    
    if(db_3wordid_exists($threeword)) {
		error_log('returning to 3wid_insert.php');
		return false;
	} else {
		error_log('3wid new ' . $threeword);
	}

    // emailform must be integer
    if (gettype($emailform) != 'integer') {
        die("emailform must be an int");
    }
    
    if (gettype($private) != 'integer') {
        die("private must be an int");
    }
    
    // linkthruflag must be integer
    if (gettype($linkthruflag) != 'integer') {
        die("linkthruflag must be an int");
    }
    
    // notification must be string
    if (!is_string($notification)) {
        die("Invalid notification: must be a string");
    }

    // linkthru must be string and valid URL with https
    if (!is_string($linkthru)) {
        die("Invalid linkthru: must be a string");
    }
    
    // Normalize linkthru URL
    if (empty($linkthru)) {
        $linkthru = '';
    } else {
        // Add https if no protocol specified
        if (!preg_match('#^https?://#', $linkthru)) {
            $linkthru = 'https://' . $linkthru;
        }
        // Replace http with https
        $linkthru = str_replace('http://', 'https://', $linkthru);
        
        // Validate URL
        if (!filter_var($linkthru, FILTER_VALIDATE_URL) || 
            !preg_match('#^https://#', $linkthru)) {
            die("Invalid linkthru: must be a valid URL starting with https://");
        }
    }

    // email must be string and valid email
    //if (!is_string($email)) {
    //   die("Invalid email: must be a string");
    //}
    //if (empty($email)) {
    //    $email = '';
    //} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //    die("Invalid email: must be a valid email address");
     // }
    
    $creation_date =date('Y-m-d H:i:s'); // Current date and time
    $update_date = date('Y-m-d H:i:s');   // Current date and time for example

	$hash = makehash($threeword);

    // Database operations
    $conn = get_db_conn();    
    
    // Prepare the INSERT query
    $query = "INSERT INTO 3wordid (user_id,threeword, linkthru, linkthruflag, notification, creation_date,update_date, hash, emailform,private) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

    // Prepare the statement
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Construct the actual query for debugging
    $debug_query = "INSERT INTO 3wordid SET " .
        "threeword = " . "'" . $conn->real_escape_string($threeword) . "', " .
        "linkthru = " . "'" . $conn->real_escape_string($linkthru) . "', " .
        "linkthruflag = " . $linkthruflag . ", " .
        "notification = " . "'" . $conn->real_escape_string($notification) . "', " .
        "emailform = " . $emailform . " , " .
        "private = " . $private . " ;";

    // Log or output the query (choose one)
    error_log("Executed query: " . $debug_query);  // Logs to error log
    // echo "Executed query: " . $debug_query;     // Outputs to screen (for debugging only)

    $stmt->bind_param("ississssii", 
        $user_id,
        $threeword,
        $linkthru, 
        $linkthruflag, 
        $notification, 
        $creation_date,
        $update_date, 
        $hash,
        $emailform,
        $private
    );

    // Execute the statement
    if ($stmt->execute()) {
        $new_id = $conn->insert_id; // Get the last inserted ID
        $stmt->close();
        return $new_id; // Return the new record ID
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }
}	

function db_3wid_validate_type($threeword,$user_type){
	
	$threeword = trim(strtolower($threeword));
	
    // Split by spaces
    $parts = preg_split('/\s+/', $threeword);
    
    // Check if there are exactly 3 parts
    if (count($parts) !== 3) {
        return false;
    }
    
    if($user_type == 0) {
    // Check each part is only alphabetic
		foreach ($parts as $part) {
			if (!ctype_alpha($part) || empty($part)) {
				return false;
			}
		}
	} 
	
	if($user_type > 0) {
    // Check each part is only alphabetic
		foreach ($parts as $part) {
			if (!ctype_alnum($part) || empty($part)) {
				return false;
			}
		}
	} 
	
	return $threeword;
	}

function db_3wid_validate($threeword){
	
	$threeword = trim(strtolower($threeword));
	
    // Split by spaces
    $parts = preg_split('/\s+/', $threeword);
    
    // Check if there are exactly 3 parts
    if (count($parts) !== 3) {
        return false;
    }
    
    // Check each part is only alphabetic
    foreach ($parts as $part) {
        if (!ctype_alnum($part) || empty($part)) {
            return false;
        }
    }
	
	return $threeword;
	}
	
function db_3wordid_check_csrf_token($id, $csrf_token) {

    // notification must be string
    if (!is_string($csrf_token)) {
        error_log("invalid crsf token " . $csrf_token);
        return;
    }

    // Database operations
    $conn = get_db_conn();    
    
    // Prepare the UPDATE query
    $query = "SELECT * FROM google_users
              WHERE csrf_token = ?   
              ";

    // Prepare the statement
    $stmt = $conn->prepare($query);
      
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", 
        $csrf_token
    );

    // Execute the statement
    if ($stmt->execute()) {
        $stmt->close();
        return true; // Success
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }
    
}	

function db_3wordid_update_google_usertype($id,$usertype) {
	
	$usertype = (int) $usertype;
	$id = (int) $id;
	
    // Database operations
    $conn = get_db_conn();    
    
    // Prepare the UPDATE query
    $query = "UPDATE google_users
              SET user_type = ?   
              WHERE id = ?";
              
    // Prepare the statement
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", 
        $usertype,
        $id
    );

    // Execute the statement
    if ($stmt->execute()) {
        $stmt->close();
        return true; // Success
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }	
    
}

function db_3wordid_get_signups($status) {
	
			
		try {
			// Create connection
			$conn = get_db_conn();  


			// Prepare the query
			$stmt = $conn->prepare("SELECT * FROM 3wordid_signup WHERE status = ? ORDER by id DESC;");

			// Bind parameter
			$status = (int) $status;
			
			$stmt->bind_param("i", $status);

			// Execute the query
			$stmt->execute();

			// Get result
			$result = $stmt->get_result();

			// Fetch all rows as associative array
			$items = $result->fetch_all(MYSQLI_ASSOC);

			// Close statement and connection
			$stmt->close();
			$conn->close();

			// Return or use the array
			//error_log(json_encode($items));
		return $items;

		} catch (Exception $e) {
			echo error_log('function signup ' . $e->getMessage());
		}
	
	
	}

function db_3wordid_signup_insert($email, $reseller_code, $payment_method, $terms_agreed, $status,$message) {
	
	//($email, $reseller_code, $payment_method, $terms,1, $message)
	
	$mysqli = get_db_conn();
	
	error_log('message  is ' . $message);

	// hash email 	
	$email = md5($email);   
    // Prepare the SQL statement to prevent SQL injection
    $stmt = $mysqli->prepare("INSERT INTO 3wordid_signup (email, reseller_code, payment_method, terms_agreed, status,message) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Bind parameters: 'sssss' indicates all are strings (or integers cast as strings)
    $stmt->bind_param("ssssis", $email, $reseller_code, $payment_method, $terms_agreed, $status,$message);
    
    // Execute the statement
    if ($stmt->execute()) {
        $result = $mysqli->insert_id;
    } else {
        $result = NULL;
        error_log('insert failed');
    }
    
    // Close the statement
    $stmt->close();
    
    return $result;
}
	
function db_3wordid_set_csrf_token($id, $csrf_token) {
    // Validation
    // ID must be integer
    
  
    // notification must be string
    if (!is_string($csrf_token)) {
        error_log("invalid csrf token " . $csrf_token);
        return;
    }

    // Database operations
    $conn = get_db_conn();    
    
    // Prepare the UPDATE query
    $query = "UPDATE google_users
              SET csrf_token = ?   
              WHERE id = ?";
              
    //error_log($query . " $id " . $id . " csrf_token" . $csrf_token);          

    // Prepare the statement
    $stmt = $conn->prepare($query);


    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", 
        $csrf_token,
        $id
    );

    // Execute the statement
    if ($stmt->execute()) {
        $stmt->close();
        return true; // Success
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }
}	


function db_3wordid_update_views($id) {	
	
	$id = (int) $id;
	
	$conn = get_db_conn();
	
   // Example: Increment views for a specific row (e.g., id = 1)

	$sql = "UPDATE `3wordid` SET views = views + 1 WHERE id = ?";

	// Prepare the statement
	$stmt = $conn->prepare($sql);
	if ($stmt === false) {
		die("Prepare failed: " . $conn->error);
	}

	// Bind the ID parameter
	$stmt->bind_param('i', $id); // 'i' for integer

	// Execute the query
	if ($stmt->execute()) {
		error_log('updating views for 3wordid ' . $id);
	} else {
		error_log("Error incrementing views for id " . $id . " error " . $stmt->error);
	}

	// Close statement and connection
	$stmt->close();
	$conn->close();	
	
}	
function db_3wordid_update($id, $notification, $linkthru, $linkthruflag, $emailform,$private) {
    // Validation
    // ID must be integer
    
    $update_array = ['time'=>time(),'id'=>$id,'notification'=>$notification,'linkthru'=>$linkthru,'linkthruflag'=>$linkthruflag,'emailform'=>$emailform,'private'=>$private];
    
    db_3wid_log_update(json_encode($update_array));
    
    //error_log("linkthruflag :" .  $linkthruflag);

	if(gettype($emailform)!='integer') {
		error_log("emailform must be a int");
		return;
	}
	 
	if(gettype($linkthruflag)!='integer') {
		error_log("linkthruflag must be a int");
		return;
	}
	
    if(gettype($private)!='integer') {
		error_log("private must be a int");
		return;
	}

    // notification must be string
    if (!is_string($notification)) {
        error_log("Invalid notification: must be a string");
        return;
    }

    // linkthru must be string and valid URL with https
    if (!is_string($linkthru)) {
        error_log("Invalid linkthru: must be a string");
        return;
    }
    
    // Normalize linkthru URL
    if (empty($linkthru)) {
        $linkthru = '';
    } else {
        // Add https if no protocol specified
        if (!preg_match('#^https?://#', $linkthru)) {
            $linkthru = 'https://' . $linkthru;
        }
        // Replace http with https
        $linkthru = str_replace('http://', 'https://', $linkthru);
        
        // Validate URL
        if (!filter_var($linkthru, FILTER_VALIDATE_URL) || 
            !preg_match('#^https://#', $linkthru)) {
            die("Invalid linkthru: must be a valid URL starting with https://");
        }
    }

    // email must be string and valid email
    //if (!is_string($email)) {
    //    die("Invalid email: must be a string");
    //}
    //if (empty($email)) {
    //    $email = '';
    //} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //    die("Invalid email: must be a valid email address");
    //}

    // Database operations
    $conn = get_db_conn();    
    
    // Prepare the UPDATE query
    $query = "UPDATE 3wordid 
              SET linkthru = ?, 
                  linkthruflag = ?,       
                  notification = ?, 
                  emailform = ?,
                  private = ?,
                  update_date =?      
              WHERE id = ?";

    // Prepare the statement
    $stmt = $conn->prepare($query);
    
    $update_date = date('Y-m-d H:i:s');
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }


    $stmt->bind_param("sissisi", 
        $linkthru, 
        $linkthruflag, 
        $notification, 
        $emailform,
        $private,
        $update_date,
        $id
    );

    // Execute the statement
    if ($stmt->execute()) {
        $stmt->close();
        return true; // Success
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }
}

function db_3wordid_set_user_consent($id) {
    // Validation
    // ID must be integer

	if(gettype($id)!='integer') {
		error_log("consent must be a int");
		return;
	}

    $conn = get_db_conn();    
    
    // Prepare the UPDATE query
    $query = "UPDATE google_users 
              SET privacy_consent = 1      
              WHERE id = ?";

    // Prepare the statement
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }


    $stmt->bind_param("i", 
        $id
    );

    // Execute the statement
    if ($stmt->execute()) {
        $stmt->close();
        return true; // Success
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }
}

function db_3wid_get_3wids($user_id) {
	
	if($user_id == NULL) return;
	
	$user_id = (int)$user_id;

	// Create connection
	$conn = get_db_conn();

	// Check connection
	if ($conn->connect_error) {
		error_log("Connection failed: " . $conn->connect_error);
	}

	// Prepare and execute query
	$sql = "SELECT id,threeword FROM 3wordid WHERE user_id = ? ORDER BY id ASC";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $user_id); // 's' for string, use 'i' if user_id is integer
	$stmt->execute();
	$result = $stmt->get_result();
	$rows = $result->fetch_all(MYSQLI_ASSOC);
	
	// Close connection
	$stmt->close();
	$conn->close();

	return $rows;
	
	}	


function db_3wid_count_3wids($user_id) {
	
	if($user_id == NULL) return;
	
	$user_id = (int)$user_id;

	// Create connection
	$conn = get_db_conn();

	// Check connection
	if ($conn->connect_error) {
		error_log("Connection failed: " . $conn->connect_error);
	}

	// Prepare and execute query
	$sql = "SELECT COUNT(*) as total FROM 3wordid WHERE user_id = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $user_id); // 's' for string, use 'i' if user_id is integer
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	
	// Close connection
	$stmt->close();
	$conn->close();

	error_log(json_encode($row));

	return $row['total'];
	
	}	
	
function db_3wid_add_credit($user_id,$sum_to_add){

	  $row = db_3wid_get_credit($user_id);
  
	  error_log(json_encode($row));
	  
	  $amount = $row['credit'];
	  
	  $sum_to_add = 10;
	  
	  $new_amount = $sum_to_add + $amount;
	  
	  db_3wid_update_credit($user_id, $new_amount);
	
	}
	
function db_3wid_get_credit($user_id){
	
	$conn = get_db_conn();
	
	$query = "SELECT credit FROM google_users WHERE id=? ";

	$stmt = $conn->prepare($query);

	// Bind the parameter to prevent SQL injection
	$stmt->bind_param("i", $user_id);

	// Execute the query
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();
	
	// Fetch the row as an associative array
	$row = $result->fetch_assoc();
	
	return $row;
	
	}	

function db_3wid_log_ip($ip) {
	
	$conn = get_db_conn();

	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	// Value to insert
	$value = $ip; // Replace with your input value

	// Check if value exists
	$query = "SELECT counter,blocked FROM 3wordid_sessions WHERE ip_address = ?";
	$stmt = $conn->prepare($query);
	$stmt->bind_param("s", $value);
	$stmt->execute();
	$result = $stmt->get_result();
	
	$block_check = $result->fetch_assoc();

	if ($result->num_rows > 0) {
		if($block_check['blocked']==1) {
			die('Your account has been suspended.');
		}
		
		if($block_check['counter']>200) {
			error_log('high ip ' . json_encode($_REQUEST));
		}
		$query = "UPDATE 3wordid_sessions SET counter = counter + 1 WHERE ip_address = ?";
		$stmt = $conn->prepare($query);
		$stmt->bind_param("s", $value);
		$stmt->execute();
		//echo "Counter incremented for value: $value";
	} else {
		
		// Value doesn't exist, insert new record
		$query = "INSERT INTO 3wordid_sessions (ip_address, counter) VALUES (?, 1)";
		$stmt = $conn->prepare($query);
		$stmt->bind_param("s", $value);
		$stmt->execute();
		//echo "New value inserted: $value";
	}

	// Close connection
	$stmt->close();
	$conn->close();
	
	
	}
	
function db_3wid_list_ip() {
	
	$conn = get_db_conn();

	// Check if connection is valid
	if (!$conn || $conn->connect_error) {
		die("Connection failed: " . ($conn ? $conn->connect_error : "Invalid connection object"));
	}

	// Query to get top 20 rows
	$query = "SELECT id,ip_address, counter FROM `3wordid_sessions` WHERE blocked IS NULL ORDER BY counter DESC LIMIT 20";
	$stmt = $conn->prepare($query);

	// Check if prepare failed
	if (!$stmt) {
		die("Prepare failed: " . $conn->error);
	}

	// Execute query
	if (!$stmt->execute()) {
		die("Execute failed: " . $stmt->error);
	}

	// Get result
	$result = $stmt->get_result();
	$rows = $result->fetch_all(MYSQLI_ASSOC);

	/* Output or process results
	if ($rows) {
		foreach ($rows as $row) {
			echo "IP: {$row['ip_address']}, Counter: {$row['counter']}<br>";
		}
	} else {
		echo "No results found.";
	}
	*/

	// Close statement and connection
	$stmt->close();
	$conn->close();
	
	return $rows;

	}	

function db_3wid_ip_block($ip) {
	
	$conn = get_db_conn();

	// Check if connection is valid
	if (!$conn || $conn->connect_error) {
		die("Connection failed: " . ($conn ? $conn->connect_error : "Invalid connection object"));
	}

	// Query to get top 20 rows
	$query = "UPDATE `3wordid_sessions` SET blocked=1 where id=?";
	$stmt = $conn->prepare($query);
	$stmt->bind_param("i", $ip);
	$stmt->execute();
	

	// Check if prepare failed
	if (!$stmt) {
		die("Prepare failed: " . $conn->error);
	}

	// Execute query
	if (!$stmt->execute()) {
		die("Execute failed: " . $stmt->error);
	}

	// Close statement and connection
	$stmt->close();
	$conn->close();
	
	return true;

	}	

function db_3wordid_create($conn, $threeword, $user_id, $linkthru = null, $linkthruflag = null, $notification = null, $email = null, $update_date = null, $hash = null) {
    
     $conn = get_db_conn();
    
    // Prepare the INSERT query
    $query = "INSERT INTO 3wordid (threeword, linkthru, linkthruflag, user_id, notification, email, update_date, hash) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare the statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind the parameters
    // 's' for string (threeword, linkthru, notification, email, hash)
    // 'i' for integer (linkthruflag, user_id)
    // 's' for datetime (update_date, but can be null)
    $stmt->bind_param("ssiiisss", 
        $threeword, 
        $linkthru, 
        $linkthruflag, 
        $user_id, 
        $notification, 
        $email, 
        $update_date, 
        $hash
    );

    // Execute the statement
    if ($stmt->execute()) {
        $new_id = $conn->insert_id; // Get the auto-incremented ID of the new row
        $stmt->close();
        return $new_id; // Return the new row's ID
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error);
    }
}


//error_log('end of functions ' . json_encode($_SESSION) . ' session id ' . session_id());


// ---------------------------------------------------------------------------
// Security helpers
// ---------------------------------------------------------------------------

// Per-session "action token" (CSRF protection). Put it in every link or form
// that changes data (delete, enable/disable) and check it on the server side.
// Kept separate from csrf_token, which is also stored in google_users.
function get_action_token() {
	if (empty($_SESSION['action_token']) || !is_string($_SESSION['action_token'])) {
		$_SESSION['action_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['action_token'];
}

function check_action_token($token) {
	if (!is_string($token) || $token === '') {
		return false;
	}
	if (empty($_SESSION['action_token']) || !is_string($_SESSION['action_token'])) {
		return false;
	}
	return hash_equals($_SESSION['action_token'], $token);
}

// Returns the 3wordid row when 3wordid $id belongs to user $user_id, otherwise false.
function db_3wordid_owned_by($id, $user_id) {
	$id = (int) $id;
	$user_id = (int) $user_id;
	if ($id < 1 || $user_id < 1) {
		return false;
	}
	$row = db_3wordid_get_user($id, $user_id);
	return $row ? $row : false;
}

// Returns the message row when the logged in user may see it: the user owns the
// 3WordID the message was sent to, or the user sent the message. Otherwise false.
function db_3wordid_message_for_user($message_id, $user_id) {
	$message_id = (int) $message_id;
	$user_id = (int) $user_id;
	if ($message_id < 1 || $user_id < 1) {
		return false;
	}
	$message = db_3wordid_get_message($message_id);
	if (!$message) {
		return false;
	}
	if ((int)$message['from_id'] === $user_id) {
		return $message;
	}
	if (db_3wordid_owned_by($message['3wid_id'], $user_id)) {
		return $message;
	}
	return false;
}

// ---------------------------------------------------------------------------
// Admin helpers (only call these after checking the admin user id)
// ---------------------------------------------------------------------------

// Search all 3WordIDs with their owner. $q matches the three words (spaces or
// dots), the owner's name, or the owner's user id. An empty $q lists everything.
// Returns up to $page_size + 1 rows so the caller can tell if there is a next page.
function db_3wordid_admin_search($q, $page, $page_size) {
	$q = trim((string) $q);
	$page = max(0, (int) $page);
	$page_size = max(1, (int) $page_size);
	$offset = $page * $page_size;
	$fetch = $page_size + 1;

	$conn = get_db_conn();

	$select = "SELECT w.id, w.threeword, w.linkthru, w.linkthruflag, w.enabled, w.views, w.creation_date, w.user_id,
	                  u.full_name AS owner_name
	           FROM 3wordid w
	           LEFT JOIN google_users u ON u.id = w.user_id";

	if ($q === '') {
		$stmt = $conn->prepare($select . " ORDER BY w.id DESC LIMIT ?, ?");
		$stmt->bind_param("ii", $offset, $fetch);
	} else {
		$words = preg_replace('/[\s.]+/', ' ', $q);
		$like_words = '%' . addcslashes($words, '%_\\') . '%';
		$like_name = '%' . addcslashes($q, '%_\\') . '%';
		$user_id = ctype_digit(ltrim($q, '#')) ? (int) ltrim($q, '#') : -1;
		$stmt = $conn->prepare($select . "
			WHERE w.threeword LIKE ? OR u.full_name LIKE ? OR w.user_id = ?
			ORDER BY w.id DESC LIMIT ?, ?");
		$stmt->bind_param("ssiii", $like_words, $like_name, $user_id, $offset, $fetch);
	}

	$stmt->execute();
	$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();
	return $rows;
}


// True only when outgoing mail settings are present in login/config.php.
// config.php is not in the repo; add these lines there when SMTP sending is
// set up (example values, use your own; never commit real ones):
//   define('SMTP_HOST', 'smtp.example.com');
//   define('SMTP_PORT', 587);
//   define('SMTP_USER', 'user@example.com');
//   define('SMTP_PASS', 'your-password');
//   define('SMTP_FROM', 'noreply@3wordid.com');
// Until all five are defined and non-empty, email features stay hidden.
function email_sending_configured() {
	foreach (array('SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_FROM') as $name) {
		if (!defined($name) || trim((string) constant($name)) === '') {
			return false;
		}
	}
	return true;
}


// Emails the logged-in user a plain-text list of their 3WordIDs (words plus target).
// $email comes from get_session_email(); it is never stored or logged.
// Returns true when PHP mail() accepted the message.
function send_3wid_list_email($email, $rows) {
	if (!email_sending_configured()) {
		return false;
	}
	if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
		return false;
	}

	$lines = array();
	$lines[] = t_plain('mail.intro');
	$lines[] = '';
	if (count($rows) === 0) {
		$lines[] = t_plain('mail.none');
	}
	foreach ($rows as $row) {
		$words = trim((string) $row['threeword']);
		$url = 'https://3wordid.com/' . implode('.', explode(' ', $words));
		if ((int) $row['linkthruflag'] === 1 && trim((string) $row['linkthru']) !== '') {
			$target = t_plain('mail.link') . ' ' . trim((string) $row['linkthru']);
		} elseif (trim((string) $row['notification']) !== '') {
			$target = t_plain('mail.notification') . ' ' . trim((string) $row['notification']);
		} else {
			$target = t_plain('mail.message_box');
		}
		$state = ((int) $row['enabled'] === 1) ? '' : ' ' . t_plain('mail.off');
		$lines[] = $words . $state . ' - ' . $url;
		$lines[] = '  ' . $target;
		$lines[] = '';
	}
	$lines[] = t_plain('mail.footer1');
	$lines[] = t_plain('mail.footer2');

	$body = str_replace("\n", "\r\n", implode("\n", $lines));
	$subject = t_plain('mail.subject');
	if (preg_match('/[^\x20-\x7E]/', $subject)) {
		$subject = '=?UTF-8?B?' . base64_encode($subject) . '?='; // non-ASCII subject (e.g. Dutch)
	}
	$headers = "From: 3WordID <noreply@3wordid.com>\r\n"
		. "Reply-To: noreply@3wordid.com\r\n"
		. "MIME-Version: 1.0\r\n"
		. "Content-Type: text/plain; charset=UTF-8\r\n"
		. "Content-Transfer-Encoding: 8bit";

	return @mail($email, $subject, $body, $headers, '-fnoreply@3wordid.com');
}


// ---- Chat view helpers (see php/chat.php) ----

// Does the table have this column? Used so the chat view can show times when the
// messages table has a creation_date column, without breaking when it does not.
function db_3wordid_messages_has_column($column) {
	static $cache = array();
	$column = (string) $column;
	if (!preg_match('/^[a-z_]+$/', $column)) {
		return false;
	}
	if (array_key_exists($column, $cache)) {
		return $cache[$column];
	}
	$cache[$column] = false;
	$conn = get_db_conn();
	if ($conn && !$conn->connect_error) {
		$result = $conn->query("SHOW COLUMNS FROM 3wordid_messages LIKE '" . $conn->real_escape_string($column) . "'");
		if ($result) {
			$cache[$column] = ($result->num_rows > 0);
			$result->free();
		}
		$conn->close();
	}
	return $cache[$column];
}

// Returns [message id => 'Y-m-d H:i:s'] for the given message ids. Returns an empty
// array when the messages table has no creation_date column (no schema change needed).
function db_3wordid_message_dates($ids) {
	$ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids), function ($i) { return $i > 0; })));
	if (!$ids || !db_3wordid_messages_has_column('creation_date')) {
		return array();
	}
	$conn = get_db_conn();
	if (!$conn || $conn->connect_error) {
		return array();
	}
	// ids are integers (cast above), so the list is safe to inline
	$result = $conn->query("SELECT id, creation_date FROM 3wordid_messages WHERE id IN (" . implode(',', $ids) . ")");
	$dates = array();
	if ($result) {
		while ($row = $result->fetch_assoc()) {
			if (!empty($row['creation_date'])) {
				$dates[(int) $row['id']] = (string) $row['creation_date'];
			}
		}
		$result->free();
	}
	$conn->close();
	return $dates;
}

// Name (three words) of a 3WordID by id, or '' when not found.
function db_3wordid_threeword_name($id) {
	$id = (int) $id;
	if ($id < 1) {
		return '';
	}
	$conn = get_db_conn();
	if (!$conn || $conn->connect_error) {
		return '';
	}
	$stmt = $conn->prepare("SELECT threeword FROM 3wordid WHERE id = ? LIMIT 1");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc();
	$stmt->close();
	$conn->close();
	return $row ? (string) $row['threeword'] : '';
}

// Messages the logged-in user sent from one of their 3WordIDs ($from_wid) to another
// 3WordID ($to_wid), oldest first. Used for the "You:" lines of the printable chat.
function db_3wordid_messages_from_to($user_id, $from_wid, $to_wid) {
	$user_id = (int) $user_id;
	$from_wid = (int) $from_wid;
	$to_wid = (int) $to_wid;
	if ($user_id < 1 || $from_wid < 1 || $to_wid < 1) {
		return array();
	}
	$conn = get_db_conn();
	if (!$conn || $conn->connect_error) {
		return array();
	}
	$stmt = $conn->prepare("SELECT id, title, message FROM 3wordid_messages WHERE from_id = ? AND 3wid_from_id = ? AND 3wid_id = ? ORDER BY id ASC");
	if ($stmt === false) {
		$conn->close();
		return array();
	}
	$stmt->bind_param("iii", $user_id, $from_wid, $to_wid);
	$stmt->execute();
	$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();
	$conn->close();
	return $rows ? $rows : array();
}
