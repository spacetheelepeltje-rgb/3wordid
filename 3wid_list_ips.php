<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$time = substr(time(), -4);

if(isset($_GET['action'])) {
	if($_GET['action']=='delete_log') {
		//rename('errors.php', '/image1.jpg');
		unlink('errors.php');
		header('location:3wid_list_ips.php');
		error_log('new log ' . time());
		die();
	}
}

if(isset($_GET['block_id'])) {
	$block_id =(int) $_GET['block_id'];
	db_3wid_ip_block($block_id);
	}	

	$ips = db_3wid_list_ip();

	// Generate Google Login URL
	$loginUrl = $client->createAuthUrl();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com - IP List</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_2.css">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            box-sizing: border-box;
        }

        main {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .message-list {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 10px;
            box-sizing: border-box;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
            gap: 10px;
            flex-wrap: nowrap;
            width: 100%;
        }

        .row.header {
            border-bottom: 2px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .id, .ip, .status {
            flex: 1;
            overflow: visible;
            white-space: nowrap;
            word-break: break-word;
            text-align: left;
        }

        .id-header, .ip-header, .status-header {
            flex: 1;
            padding: 10px 0;
            font-weight: bold;
            text-align: left;
        }

        .logo {
            margin: 20px 0;
            text-align: center;
        }

        .logo p {
            margin: 10px 0;
        }

        @media (max-width: 600px) {
            .id, .ip, .status {
                font-size: 14px;
            }
            .row {
                gap: 6px;
            }
            .message-list {
                padding: 0 5px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="top-right" id="userContainer">
            <a href="<?php echo $loginUrl; ?>" class="login-icon" title="Log in (not all functions work on mobile devices)">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
                    <path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
                    <polyline points="8 7 13 12 8 17"></polyline>
                    <line x1="13" y1="12" x2="1" y2="12"></line>
                </svg>
            </a>
        </div>
    </header>
    <main>
        <div class="logo">
			 
           <a href='<?php echo $main_url; ?>' title="to 3wordID.com"><img width=100 src="img/3wid_big.png"></a><br>
        
            <p>Top 20 IPs</p><a href='https://3wordid.com/errors.php'>Error Log</a> <a href='3wid_list_ips.php?action=delete_log'>Delete Error Log</a>
        </div>
        <div class="message-list">
            <div class="row header">
                <span class="id-header">ID</span>               
                <span class="ip-header">IP</span>
                <span class="ip-header">Counter</span>
                <span class="status-header">Status</span>
            </div>
            <?php foreach ($ips as $ip): ?>
                <div class="row">
                    <div class="id"><?php echo htmlspecialchars($ip["id"]); ?></div>
                    <div class="ip"><?php echo htmlspecialchars($ip["ip_address"]); ?></div>
                    <div class="status"><?php echo htmlspecialchars($ip["counter"]); ?></div>
                    <div class="status"><?php if(isset($ip["blocked"]) && $ip["blocked"]==1) { echo "Blocked"; } else { echo "Ok";}; ?> <a href="3wid_list_ips.php?block_id=<?php echo $ip["id"] ;?>"> Block</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <?php echo $footer; ?>
</body>
</html>
