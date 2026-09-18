<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$time = substr(time(), -4);

$client_ip = get_client_ip();

$data = check_credentials($_SESSION, $_POST, $_GET);

if ($data == NULL) {
    header('location:index.php?message=Could not log you in');
    die('had to go');
}

if($data['privacy_consent']==0) {
    header('location:3wid_privacy_consent.php');
    die('had to go');
}

$message = "";

if (isset($_GET["message"])) {
    $message = $_GET["message"];
}

$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
$_SESSION['token'] = $data["token"];
$id = $data['id'];
$max = $data['max_3wids'] + 1;

if ($id != NULL) {
    db_3wordid_set_csrf_token($id, $csrf_token);
}

$db_3wordid_list = db_3wordid_list($id);

// $id contains the user id

$nr_of_3wordid = count($db_3wordid_list);

if ($nr_of_3wordid == 0) {
    $state = 'disabled';
    $style = 'style="pointer-events: none"';
} else {
    $state = 'enabled';
    $style = '';
}

$count_rows = count($db_3wordid_list);

if ($max - $count_rows == 0) {
    $pointer = 'style="pointer-events: none;opacity: 0.5"';
    $title = "Unsubscribed users can create only one 3WID";
    $create_label = "Unsubscribed users can create only one 3WID";
} else {
    $pointer = '';
    $title = "Create Three Word ID (" . ($max - $count_rows) . " remaining)";
    $create_label = "Create 3WordID";
}

$remaining = max(0, $max - $count_rows);
$reseller_code = $data["id"] . substr(trim($data["email"]), -5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3WordID — Dashboard</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      :root {
        --navy: #0f1c3f;
        --navy-2: #162756;
        --slate: #5b6478;
        --muted: #8a93a6;
        --line: #e6e3dc;
        --paper: #f7f6f3;
        --white: #ffffff;
        --gold: #b45309;
        --ok: #16794a;
        --danger: #9b1c1c;
        --shadow: 0 10px 30px rgba(15, 28, 63, 0.08);
      }
      * { box-sizing: border-box; }
      html, body { margin: 0; padding: 0; }
      body {
        font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
        background: var(--paper);
        color: var(--navy);
        min-height: 100vh;
      }
      a { color: var(--navy); }
      .site-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 28px;
        background: rgba(247,246,243,0.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--line);
        position: sticky;
        top: 0;
        z-index: 20;
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--navy);
        font-weight: 800;
        letter-spacing: -0.03em;
        font-size: 20px;
      }
      .brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: var(--navy);
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
      }
      .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .btn {
        border: 0;
        cursor: pointer;
        border-radius: 10px;
        font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 14px;
        font-size: 14px;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-2); }
      .btn-ghost {
        background: #fff;
        color: var(--navy);
        border: 1px solid var(--line);
      }
      .btn[style*="pointer-events: none"] { cursor: not-allowed; }
      .logout-icon { color: var(--navy); display: inline-flex; padding: 6px; }
      .wrap { max-width: 1100px; margin: 0 auto; padding: 28px 24px 64px; }
      .page-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
      }
      h1 { margin: 0 0 6px; font-size: 32px; letter-spacing: -0.04em; }
      .lede { margin: 0; color: var(--slate); }
      .flash {
        background: #fff;
        border: 1px solid #eceae4;
        border-left: 4px solid var(--gold);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 18px;
        font-weight: 600;
      }
      .stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 18px;
      }
      .stat {
        background: #fff;
        border: 1px solid #eceae4;
        border-radius: 14px;
        padding: 16px;
      }
      .stat span { display: block; color: var(--muted); font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
      .stat strong { font-size: 22px; }
      .toolbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 16px;
      }
      .panel {
        background: #fff;
        border: 1px solid #eceae4;
        border-radius: 16px;
        box-shadow: var(--shadow);
        overflow: hidden;
      }
      .id-row {
        display: grid;
        grid-template-columns: minmax(180px, 1.3fr) minmax(140px, .8fr) minmax(180px, 1fr);
        gap: 12px;
        align-items: center;
        padding: 16px 18px;
        border-top: 1px solid #f0eee8;
      }
      .id-row:first-child { border-top: 0; }
      .id-name { font-weight: 700; text-decoration: none; display: block; }
      .id-name.grayed-out { color: var(--muted); text-decoration: line-through; }
      .id-meta { color: var(--muted); font-size: 12px; margin-top: 4px; }
      .badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 700;
        margin-left: 8px;
      }
      .badge-on { background: #e8f6ee; color: var(--ok); }
      .badge-off { background: #f3f1ec; color: var(--muted); }
      .icon-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
      .icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 1px solid var(--line);
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--navy);
        text-decoration: none;
        position: relative;
      }
      .icon-btn.disabled, .mail-icon.disabled { opacity: .35; pointer-events: none; }
      .icon-btn.enabled { color: var(--ok); }
      .icon-btn.disabled-toggle { color: var(--muted); }
      .trash-icon:hover { color: var(--danger); border-color: #e8c4c4; }
      .copy-button {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 1px solid var(--line);
        background: #fff;
        cursor: pointer;
        position: relative;
        color: var(--navy);
      }
      .tooltip {
        display: none;
        position: absolute;
        bottom: 42px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--navy);
        color: #fff;
        font-size: 11px;
        padding: 6px 8px;
        border-radius: 6px;
        white-space: nowrap;
        z-index: 5;
      }
      .copy-button:hover .tooltip { display: block; }
      .hidden-url { display: none; }
      .foot-card {
        margin-top: 16px;
        background: #fff;
        border: 1px solid #eceae4;
        border-radius: 16px;
        padding: 16px 18px;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        align-items: center;
      }
      .reseller { color: var(--slate); font-size: 14px; }
      .reseller code { background: #efece6; padding: 2px 6px; border-radius: 6px; font-family: inherit; font-weight: 700; }
      .admin-links a { margin-right: 10px; font-weight: 600; text-decoration: none; }
      .site-footer {
        border-top: 1px solid var(--line);
        padding: 18px 28px 28px;
        color: var(--muted);
        font-size: 13px;
      }
      @media (max-width: 800px) {
        .stats, .id-row { grid-template-columns: 1fr; }
        .page-head { align-items: flex-start; }
      }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url; ?>" title="to 3wordID.com">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        <div class="header-actions">
            
            <a class="btn btn-primary" href="3wid_add.php?user_token=<?php echo $_SESSION["user_token"]; ?>" title="<?php echo $title; ?>" <?php echo $pointer; ?>><?php echo $create_label; ?></a>
            <a href="login/logout.php" class="logout-icon" title="log out">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Logout Icon">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </header>

    <main class="wrap">
        <div class="page-head">
            <div>
                <h1>Your 3WordIDs</h1>
                <p class="lede"></p>
            </div>
        </div>

        <?php if ($message !== "") { ?>
        <div class="flash" id="helpertext"><?php echo $message; ?></div>
        <?php } ?>

        <section class="stats">
            <div class="stat"><span>Active IDs</span><strong><?php echo $count_rows; ?></strong></div>
            <div class="stat"><span>Remaining</span><strong><?php echo $remaining; ?></strong></div> <!-- needs to work -->
            <div class="stat"><span>Total searches</span><strong><div id='totalviewcount'></div></strong></div>
        </section>

        <div class="toolbar">
            <a class="btn btn-ghost <?php echo $state; ?>" <?php echo $style; ?> href="3wid_messageform.php" title="You need to create a 3WordID to send messages to 3WordIDs">Compose Message to other 3WID</a>
        </div>

        <section class="panel">
        <?php
        if ($count_rows === 0) {
            echo '<div class="id-row"><div>No 3WordIDs yet. Top right corner. </div></div>';
        }

		$count_total = 0;

        foreach ($db_3wordid_list as $db_3wordid_item) {
		
            $message_count = db_3wordid_count_messages($db_3wordid_item['id']);
            $msg_state = ($message_count['count'] == 0) ? 'disabled' : '';
            $url = "https://3wordid.com/index.php?threewords=" . urlencode($db_3wordid_item['threeword']);
            $threeword_url = implode('.', explode(' ', $db_3wordid_item["threeword"]));
            $enabled = ($db_3wordid_item['enabled'] == 1);
            $days = db_3wid_daysUntilNWeeksAfter($db_3wordid_item['creation_date'], 4);
            
            $count_total += $db_3wordid_item['views'];
        ?>
            <div class="id-row">
                <div>
                    <a href="3wid_forward.php?threewords=<?php echo $db_3wordid_item['threeword'] ?>" class="id-name <?php echo $enabled ? '' : 'grayed-out'; ?>" title="id : <?php echo $db_3wordid_item['id'] ?> views : <?php echo $db_3wordid_item['views']; ?> created : <?php echo $db_3wordid_item['creation_date']; ?> days to expire : <?php echo $days; ?>">
                        <?php echo htmlspecialchars($db_3wordid_item['threeword']); ?>
                    </a>
                    <div class="id-meta">
                        <?php echo htmlspecialchars($threeword_url); ?>
                        · <?php echo (int)$db_3wordid_item['views']; ?> views
                        
                        <span class="badge <?php echo $enabled ? 'badge-on' : 'badge-off'; ?>"><?php echo $enabled ? 'On' : 'Off'; ?></span>
                    </div>
                </div>

                <div class="icon-row">
                    <a href="<?php echo $db_3wordid_item['linkthru']; ?>" class="icon-btn link-icon <?php if ($db_3wordid_item['linkthruflag'] != 1) { echo "disabled"; } ?>" title="Visit Forward Site : <?php echo $db_3wordid_item['linkthru']; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Link Icon">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                    </a>
                    <button class="copy-button" onclick="copyToClipboard(this)" aria-label="Copy URL to clipboard">
                        <svg class="xcopy-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/>
                        </svg>
                        <span class="tooltip">Copy URL for <?php echo htmlspecialchars($db_3wordid_item['threeword']); ?></span>
                    </button>
                    <span class="hidden-url"><?php echo htmlspecialchars($url); ?></span>
                </div>

                <div class="icon-row">
                    <a href="3wid_3wid.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="icon-btn edit-icon" title="Edit Three Word ID">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Edit Icon">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <a href="3wid_toggle.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="icon-btn toggle-icon <?php echo $enabled ? 'enabled' : 'disabled-toggle'; ?>" title="<?php echo $enabled ? 'Disable, 3wid is currently enabled' : 'Enable, 3wid is currently disabled'; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Toggle Icon">
                            <path d="M12 2v10"></path>
                            <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                        </svg>
                    </a>
                    <a href="3wid_list_messages.php?uid=<?php echo $id; ?>&wid_id=<?php echo $db_3wordid_item['id']; ?>&threeword=<?php echo $db_3wordid_item['threeword']; ?>" class="icon-btn mail-icon <?php echo $msg_state; ?>" title="<?php echo $message_count['count']; ?> messages for this 3WordID">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
                            <path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
                            <path d="M1 6l11 7 11-7"></path>
                        </svg>
                    </a>
                    <a href="3wid_delete.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="icon-btn trash-icon" title="Delete Three Word ID">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Delete Icon">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </a>
                </div>
            </div>
        <?php } ?>
        </section>

        <div class="foot-card">
            <div class="reseller">
				If you want to use this app professionally contact our administrator at <a href='mailto:frits@rincker.nl'>frits@rincker.nl</a> we are working on our subscription process.
                <!-- Reseller code: <code><?php echo htmlspecialchars($reseller_code); ?></code>
                -->
            </div>
            <!-- <div class="icon-row">
                
                <?php if ($data['id'] == '4') { ?>
                <span class="admin-links">
                    <a href="3wid_list_ips.php">I</a>
                    <a href="3wid_signup_list.php">S</a>
                    <a href="3wid_user_list.php?csrf_token=<?php echo $_SESSION['csrf_token']; ?>">U</a>
                </span>
                <?php } ?>
            </div>
            -->
        </div>
    </main>
    <footer class="site-footer"></footer>
</body>
<script>
    function copyToClipboard(button) {
        const urlToCopy = button.nextElementSibling.textContent;
        navigator.clipboard.writeText(urlToCopy)
            .then(() => {
                const icon = button.querySelector('.xcopy-icon');
                icon.style.stroke = '#16794a';
                setTimeout(() => {
                    icon.style.stroke = '#0f1c3f';
                }, 1000);
            })
            .catch(err => {
                console.error('Failed to copy: ', err);
            });
    }
    
    $(document).ready(function () {
		$('#totalviewcount').text(<?php echo json_encode($count_total); ?>);
	});
    
</script>
</html>
