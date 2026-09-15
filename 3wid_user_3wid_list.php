<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$time = substr(time(), -4);

$client_ip = get_client_ip();

//error_log(time() . ' ' . __FILE__ . ' ' . $client_ip);

//error_log('session csrf_token' . $_SESSION["csrf_token"]);

$data = check_credentials($_SESSION, $_POST, $_GET);

if($data["id"]!=4) {
	header('location:' . $main_url);
}

if (isset($_GET["id"])) {
    $id = $_GET["id"];
}

$csrf_token = bin2hex(random_bytes(32));

$db_3wordid_list = db_3wordid_list($id);

$nr_of_3wordid = count($db_3wordid_list);

if ($nr_of_3wordid == 0) {
    $state = 'disabled';
    $style = 'style="pointer-events: none"';
} else {
    $state = 'enabled';
    $style = '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/list.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>
    <header>
        <div class="top-right">
            <a href="login/logout.php" class="logout-icon" title="log out <?php echo $data['email']; ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Logout Icon">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </header>
    <main>
        <div class="logo">
            <center><a href='<?php echo $main_url; ?>' title="to 3wordID.com"><img width=100 src="img/3wid_big.png"></a><br>
            </center>
        </div>
        <div id="helpertext"><strong><?php echo $message; ?></strong></div><br>
		<?php
		if($data["id"]==4) {
		?>
		  <!-- Create New Row -->
        <div class="row">
            <span class="cell three-word-id">
            <a class="mail-icon <?php echo $state; ?>"  <?php echo $style; ?> href="3wid_messageform.php" title="You need to create a 3WordID to send messages to 3WordIDs">
                     <button class="buttons">Reseller</button>
                </a>
            
            </span>
            <span class="cell">
                <a class="mail-icon <?php echo $state; ?>"  <?php echo $style; ?> href="3wid_messageform.php" title="You need to create a 3WordID to send messages to 3WordIDs">
                     <button class="buttons">Message</button>
                </a>
            </span>
            <span class="cell actions">
                <?php
                if ($max - $count_rows == 0) {
                    $pointer = 'style="pointer-events: none;opacity: 0.5"';
                    $title = "You need to add credit for more 3WordIDs";
                    $table_text = "<b>Add credit for more 3WordIDs</b>";
                } else {
                    $pointer = '';
                    $title = "Create Three Word ID (" . ($max - $count_rows) . " in total)";
                    $table_text = "Credit";
                }
                ?>
                <a href="3wid_add.php?user_token=<?php echo $_SESSION["user_token"]; ?>" class="search-bar" <?php echo $pointer; ?> title="Create new three word ID">
                    <button class="buttons">Create 3WordID</button>
                </a>
            </span>
        </div>
        <?php
		}
		?>
         
       
        <!-- Header Row -->
        <div class="row header">
            <span class="cell three-word-id">Three Word ID</span>
            <span class="cell">Section</span>
            <span class="cell actions">Actions</span>
        </div>
        <!-- Data Rows -->
        <?php
        
        foreach ($db_3wordid_list as $db_3wordid_item) {
            $message_count = db_3wordid_count_messages($db_3wordid_item['id']);
            $state = ($message_count['count'] == 0) ? 'disabled' : '';
            
            $url = "https://3wordid.com/index.php?threewords=" . urlencode($db_3wordid_item['threeword']);
        ?>
            <div class="row">
                <span class="cell three-word-id">
                    <a href="3wid_forward.php?threewords=<?php echo $db_3wordid_item['threeword'] ?>" class="<?php if ($db_3wordid_item['enabled'] != 1) { echo "grayed-out"; } ?>" title="id : <?php echo $db_3wordid_item['id'] ?> views : <?php echo $db_3wordid_item['views']; ?>">
                        <strong><?php echo $db_3wordid_item['threeword'] ?></strong>
                    </a>
                </span>
                <span class="cell">
                    <a href="<?php echo $db_3wordid_item['linkthru']; ?>" class="link-icon <?php if ($db_3wordid_item['linkthruflag'] != 1) { echo "disabled"; } ?>" title="Visit Forward Site : <?php echo $db_3wordid_item['linkthru']; ?>">
                        <svg width="24" class="" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Link Icon">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                    </a>
                    <button class="copy-button" onclick="copyToClipboard(this)" aria-label="Copy URL to clipboard">
                        <svg class="xcopy-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/>
                        </svg>
                        <span class="tooltip">Copy URL for 3WordID : <?php echo htmlspecialchars($db_3wordid_item['threeword']); ?></span>
                    </button>
                    <span class="hidden-url"><?php echo htmlspecialchars($url); ?></span>
                </span>
                <span class="cell actions">
                    <a href="3wid_3wid.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="edit-icon" title="Edit Three Word ID">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Edit Icon">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <a href="3wid_toggle.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="toggle-icon <?php echo $db_3wordid_item['enabled'] ? 'enabled' : 'disabled'; ?>" title="<?php echo $db_3wordid_item['enabled'] ? 'Disable, 3wid is currently enabled' : 'Enable, 3wid is currently disabled'; ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-label="Toggle Icon">
                            <path d="M12 2v10"></path>
                            <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                        </svg>
                    </a>
                    <a href="3wid_list_messages.php?wid_id=<?php echo $db_3wordid_item['id']; ?>&threeword=<?php echo $db_3wordid_item['threeword']; ?>" class="mail-icon <?php echo $state; ?>" title="<?php echo $message_count['count']; ?> messages for this 3WordID">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
                            <path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
                            <path d="M1 6l11 7 11-7"></path>
                        </svg>
                    </a>
                    <a href="3wid_delete.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="trash-icon" title="Delete Three Word ID">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Delete Icon">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </a>
                </span>
            </div>
        <?php
        }
        ?>

    </main>
    <?php echo $footer; ?>
</body>
<script>
    function copyToClipboard(button) {
        const urlToCopy = button.nextElementSibling.textContent;
        navigator.clipboard.writeText(urlToCopy)
            .then(() => {
                const icon = button.querySelector('.xcopy-icon');
                icon.style.stroke = '#28a745';
                setTimeout(() => {
                    icon.style.stroke = '#333';
                }, 1000);
            })
            .catch(err => {
                console.error('Failed to copy: ', err);
            });
    }
</script>
</html>
