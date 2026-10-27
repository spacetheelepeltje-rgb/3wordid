<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$time = substr(time(), -4);

$client_ip = get_client_ip();

$data = current_user_from_session($conn);

if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
}

if ((int)$data['privacy_consent'] === 0) {
    header('Location: 3wid_privacy_consent.php');
    exit;
}

$message = isset($_GET['message']) ? $_GET['message'] : '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// token for the delete and enable/disable links (CSRF protection)
$action_token = get_action_token();

$id  = $data['id'];
$max = $data['max_3wids'] + 1;

$db_3wordid_list = db_3wordid_list($id);

// Message counts per 3WordID for the mail icons, in one go (see db_3wordid_mail_summary)
$mail_summary = db_3wordid_mail_summary($id);

// "Send to my email": POST with the action token, only the logged-in owner's
// own list, at most once per 5 minutes per session. Hidden until outgoing
// mail is configured in login/config.php (see email_sending_configured).
$email_configured = email_sending_configured();
$can_email_list = has_session_email() && $email_configured;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_list_email'])) {
    $last_sent = (int)($_SESSION['email_list_sent_at'] ?? 0);
    if (!check_action_token($_POST['action_token'] ?? '')) {
        $result = t_plain('list.email_expired');
    } elseif (!$email_configured) {
        $result = t_plain('list.email_unavailable');
    } elseif (!$can_email_list) {
        $result = t_plain('list.email_no_session');
    } elseif ($last_sent > 0 && time() - $last_sent < 300) {
        $result = t_plain('list.email_already');
    } elseif (send_3wid_list_email(get_session_email(), $db_3wordid_list)) {
        $_SESSION['email_list_sent_at'] = time();
        $result = t_plain('list.email_sent');
    } else {
        $result = t_plain('list.email_failed');
    }
    header('Location: 3wid_list.php?message=' . rawurlencode($result));
    exit;
}

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
    $title = t('list.one_only');
    $create_label = t('list.one_only');
} else {
    $pointer = '';
    $title = t('list.create_title', ['n' => ($max - $count_rows)]);
    $create_label = t('list.create');
}

$remaining = max(0, $max - $count_rows);
$reseller_code = $data["id"] . substr(trim($data["email"]), -5);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('list.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="/css/3wid_list.css?v=3" rel="stylesheet">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url; ?>" title="<?php echo t('nav.to_home'); ?>">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        
        <nav class="nav-links">
             <a href="plans.php"><?php echo t('nav.plans'); ?></a>               
            </nav>
        <div class="header-actions">
            <a href="https://wa.me/31644311561?text=From 3WordID.com : "
   target="_blank"
   rel="noopener noreferrer"
   class="btn btn-primary" style='background:#25D366;'>
  <?php echo t('nav.whatsapp_admin'); ?>
</a>

 

            <?php echo lang_switcher(); ?>
            <a class="btn btn-primary" href="3wid_add.php" title="<?php echo $title; ?>" <?php echo $pointer; ?>><?php echo $create_label; ?></a>
            
           
            
            
            <a href="login/logout.php" class="logout-icon" title="<?php echo t('nav.logout'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.logout'); ?>">
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
                <h1><?php echo t('list.h1'); ?></h1>
                <p class="lede"></p>
            </div>
        </div>

        <?php if ($message !== "") { ?>
        <div class="flash" id="helpertext"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <section class="stats">
            <div class="stat"><span><?php echo t('list.stat_active'); ?></span><strong><?php echo $count_rows; ?></strong></div>
            <div class="stat"><span><?php echo t('list.stat_remaining'); ?></span><strong><?php echo $remaining; ?></strong></div> <!-- needs to work -->
            <div class="stat"><span><?php echo t('list.stat_searches'); ?></span><strong><div id='totalviewcount'></div></strong></div>
        </section>

        <div class="toolbar">
            <a class="btn btn-ghost <?php echo $state; ?>" <?php echo $style; ?> href="3wid_messageform.php" title="<?php echo t('list.compose_title'); ?>"><?php echo t('list.compose'); ?></a>
            <?php if ($can_email_list && $count_rows > 0) { ?>
            <form method="post" action="3wid_list.php" style="margin:0">
                <input type="hidden" name="action_token" value="<?php echo htmlspecialchars($action_token, ENT_QUOTES, 'UTF-8'); ?>">
                <button class="btn btn-ghost" type="submit" name="send_list_email" value="1" title="<?php echo t('list.send_email_title'); ?>"><?php echo t('list.send_email'); ?></button>
            </form>
            <?php } ?>
        </div>

        <section class="panel">
        <?php
        if ($count_rows === 0) {
            echo '<div class="id-row"><div>' . t('list.none') . '</div></div>';
        }

		$count_total = 0;

        foreach ($db_3wordid_list as $db_3wordid_item) {
		
            // Mail icon: always clickable. Green when messages arrived for this 3WordID
            // (receiving works even without "Link to chat"), with a dot for unread ones.
            // With conversations it opens the chats, otherwise a new message from this 3WordID.
            $mail = $mail_summary[(int)$db_3wordid_item['id']] ?? array('in' => 0, 'unread' => 0, 'sent' => 0);
            if ($mail['in'] > 0 || $mail['sent'] > 0) {
                $mail_href = '3wid_list_messages.php?wid_id=' . (int)$db_3wordid_item['id'];
            } else {
                $mail_href = '3wid_messageform.php?threeword_id=' . (int)$db_3wordid_item['id'];
            }
            if ($mail['unread'] > 0) {
                $mail_label = t_n('list.mail_unread', $mail['unread'], ['words' => $db_3wordid_item['threeword']]);
            } elseif ($mail['in'] > 0) {
                $mail_label = t_n('list.mail_received', $mail['in'], ['words' => $db_3wordid_item['threeword']]);
            } elseif ($mail['sent'] > 0) {
                $mail_label = t('list.mail_chats', ['words' => $db_3wordid_item['threeword']]);
            } else {
                $mail_label = t('list.mail_send', ['words' => $db_3wordid_item['threeword']]);
            }
            $mail_class = ($mail['in'] > 0 ? ' has-mail' : '') . ($mail['unread'] > 0 ? ' has-unread' : '');
            //$url = "https://3wordid.com/index.php?threewords=" . urlencode($db_3wordid_item['threeword']);
            $threeword_url = implode('.', explode(' ', $db_3wordid_item['threeword']));
            $url = "https://3wordid.com/" .  $threeword_url;
            $enabled = ($db_3wordid_item['enabled'] == 1);
            $days = db_3wid_daysUntilNWeeksAfter($db_3wordid_item['creation_date'], 4);
            
            $count_total += $db_3wordid_item['views'];
        ?>
            <div class="id-row">
                <div>
                    <a href="3wid_forward.php?threewords=<?php echo $db_3wordid_item['threeword'] ?>" class="id-name <?php echo $enabled ? '' : 'grayed-out'; ?>" title="<?php echo t('list.row_title', ['id' => $db_3wordid_item['id'], 'views' => $db_3wordid_item['views'], 'created' => $db_3wordid_item['creation_date'], 'days' => $days]); ?>">
                        <?php echo htmlspecialchars($db_3wordid_item['threeword']); ?> 
                    </a>
                    <div class="id-meta">
                        <?php echo htmlspecialchars($threeword_url); ?>
                        · <?php echo t_n('home.views', (int)$db_3wordid_item['views']); ?>
                        
                        <span class="badge <?php echo $enabled ? 'badge-on' : 'badge-off'; ?>"><?php echo $enabled ? t('common.on') : t('common.off'); ?></span>
                    </div>
                </div>

                <div class="icon-row">
                    <a href="<?php echo $db_3wordid_item['linkthru']; ?>" class="icon-btn link-icon <?php if ($db_3wordid_item['linkthruflag'] != 1) { echo "disabled"; } ?>" title="<?php echo t('list.visit_title', ['url' => $db_3wordid_item['linkthru']]); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.link'); ?>">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                    </a>
                    <button class="copy-button" onclick="copyToClipboard(this)" aria-label="<?php echo t('list.copy_aria'); ?>">
                        <svg class="xcopy-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/>
                        </svg>
                        <span class="tooltip"><?php echo t('list.copy_for', ['words' => $db_3wordid_item['threeword']]); ?></span>
                    </button>
                    <span class="hidden-url"><?php echo htmlspecialchars("https://3wordid.com/" . $threeword_url); ?></span>
                </div>

                <div class="icon-row">
                    <a href="3wid_3wid.php?id=<?php echo (int)$db_3wordid_item['id']; ?>" class="icon-btn edit-icon" title="<?php echo t('list.edit_title'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.edit'); ?>">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <a href="3wid_toggle.php?id=<?php echo (int)$db_3wordid_item['id']; ?>&action_token=<?php echo $action_token; ?>" class="icon-btn toggle-icon <?php echo $enabled ? 'enabled' : 'disabled-toggle'; ?>" title="<?php echo $enabled ? t('list.disable_title') : t('list.enable_title'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.toggle'); ?>">
                            <path d="M12 2v10"></path>
                            <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                        </svg>
                    </a>
                    <a href="<?php echo $mail_href; ?>" class="icon-btn mail-icon<?php echo $mail_class; ?>" title="<?php echo $mail_label; ?>" aria-label="<?php echo $mail_label; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
                            <path d="M1 6l11 7 11-7"></path>
                        </svg>
                        <?php if ($mail['unread'] > 0) { ?><span class="mail-count" aria-hidden="true"><?php echo $mail['unread'] > 9 ? '9+' : (int)$mail['unread']; ?></span><?php } ?>
                    </a>
                    <a href="3wid_delete.php?id=<?php echo (int)$db_3wordid_item['id']; ?>&action_token=<?php echo $action_token; ?>"
					class="icon-btn trash-icon"
					title="<?php echo t('list.delete_title'); ?>"
					onclick="return confirmDelete(this);"
					data-threeword="<?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.delete'); ?>">
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
				<?php echo t_raw('list.pro'); ?>
                <!-- Reseller code: <code><?php echo htmlspecialchars($reseller_code); ?></code>
                -->
            </div>
            <div class="icon-row">
                
                <?php if ((int)$data['id'] === 4) { ?>
                <span class="admin-links">
                    <a href="3wid_list_ips.php">I</a>
                    <a href="3wid_user_list.php">U</a>
                    <a href="3wid_admin_3wids.php" title="<?php echo t('list.admin_all'); ?>">A</a>
                </span>
                <form class="admin-search" method="get" action="3wid_admin_3wids.php">
                    <input type="search" name="q" placeholder="<?php echo t('list.admin_find'); ?>" aria-label="<?php echo t('list.admin_find_aria'); ?>">
                    <button type="submit"><?php echo t('common.search'); ?></button>
                </form>
                <?php } ?>
            </div>
        </div>
    </main>
    <footer class="site-footer">
        <div><?php echo $footer; ?></div>
    </footer>
</body>
<script>
	function confirmDelete(link) {
		var name = link.getAttribute('data-threeword') || <?php echo json_encode(t_plain('list.confirm_delete_this')); ?>;
		return confirm(<?php echo json_encode(t_plain('list.confirm_delete')); ?>.replace('{name}', name));
	}
	
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
