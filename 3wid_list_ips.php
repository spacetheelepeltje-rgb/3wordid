<?php
require_once 'php/functions.php';
require_once 'login/config.php';

// Admin only (user id 4). This check must stay before any action handling below.
$data = check_credentials($_SESSION, $_POST, $_GET);
if (empty($data) || $data['id'] != 4) {
	header('location:login/options.php');
	die();
}

$action_token = get_action_token();

// Block and Delete log change data, so they only work as a POST with the action token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!check_action_token($_POST['action_token'] ?? '')) {
		header('location:3wid_list_ips.php?message=' . rawurlencode(t_plain('ip.expired')));
		die();
	}
	if (($_POST['action'] ?? '') === 'delete_log') {
		if (is_file('errors.php')) {
			unlink('errors.php');
		}
		error_log('new log ' . time());
	} elseif (isset($_POST['block_id'])) {
		db_3wid_ip_block((int) $_POST['block_id']);
	}
	header('location:3wid_list_ips.php');
	die();
}

$message = isset($_GET['message']) ? (string) $_GET['message'] : '';
$ips = db_3wid_list_ip();

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?php echo t('ip.title'); ?></title>
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
            <?php echo lang_switcher(); ?>
            <a href="login/logout.php" class="logout-icon" title="<?php echo t('nav.logout'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.logout'); ?>">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </header>
    <main>
        <div class="logo">
			 
           <a href='<?php echo $main_url; ?>' title="<?php echo t('admin.to_home'); ?>"><img width=100 src="img/3wid_big.png" alt="<?php echo t('icon.logo_3wid'); ?>"></a><br>
        
            <p><?php echo t('ip.top20'); ?></p>
            <?php if ($message !== '') { ?><p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
            <a href='https://3wordid.com/errors.php'><?php echo t('ip.error_log'); ?></a>
            <form method="post" action="3wid_list_ips.php" style="display:inline" onsubmit="return confirm(<?php echo htmlspecialchars(json_encode(t_plain('ip.confirm_delete_log'), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>);">
                <input type="hidden" name="action" value="delete_log">
                <input type="hidden" name="action_token" value="<?php echo htmlspecialchars($action_token, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit"><?php echo t('ip.delete_log'); ?></button>
            </form>
        </div>
        <div class="message-list">
            <div class="row header">
                <span class="id-header"><?php echo t('ip.col_id'); ?></span>               
                <span class="ip-header"><?php echo t('ip.col_ip'); ?></span>
                <span class="ip-header"><?php echo t('ip.col_counter'); ?></span>
                <span class="status-header"><?php echo t('ip.col_status'); ?></span>
            </div>
            <?php foreach ($ips as $ip): ?>
                <div class="row">
                    <div class="id"><?php echo htmlspecialchars($ip["id"]); ?></div>
                    <div class="ip"><?php echo htmlspecialchars($ip["ip_address"]); ?></div>
                    <div class="status"><?php echo htmlspecialchars($ip["counter"]); ?></div>
                    <div class="status"><?php echo (isset($ip["blocked"]) && $ip["blocked"] == 1) ? t('ip.blocked') : t('ip.ok'); ?>
                        <form method="post" action="3wid_list_ips.php" style="display:inline">
                            <input type="hidden" name="block_id" value="<?php echo (int) $ip["id"]; ?>">
                            <input type="hidden" name="action_token" value="<?php echo htmlspecialchars($action_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit"><?php echo t('ip.block'); ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <?php echo $footer; ?>
</body>
</html>
