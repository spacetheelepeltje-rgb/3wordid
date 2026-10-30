<?php
// Chat login for members: the three words of one 3WordID + the password its owner set.
// After login the member only sees that 3WordID's chat (3wid_chat.php).
// One generic error for every failure (unknown 3WordID, chat login off, wrong password),
// a lockout per 3WordID and per IP address (php/chat_login.php), CSRF token, new session id.

require_once 'php/functions.php';
require_once 'login/config.php';
require_once 'php/chat.php'; // chat_normalize_words()

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$ready = chat_login_ready();

// Already logged in to a chat: straight to it
if ($ready && chat_member_current() !== null) {
    header('Location: 3wid_chat.php');
    exit;
}

$error = '';
$note = '';
$words = '';
$lock_minutes = max(1, chat_login_limit('CHAT_LOGIN_LOCK_MINUTES', 15));

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $words = chat_normalize_words(chat_substr((string) ($_POST['words'] ?? ''), 0, 200));
    $password = substr((string) ($_POST['password'] ?? ''), 0, 1024);

    if (!check_action_token($_POST['action_token'] ?? '')) {
        $error = 'common.session_expired_retry';
    } else {
        $keys = chat_login_rate_keys($words);
        if (chat_login_locked($keys)) {
            // Locked: the password is not even checked, so trying more tells nothing
            $error = 'cl.err_locked';
            app_log('chat login: locked out', 'warn', true);
        } else {
            $row = ($words !== '' && $password !== '') ? chat_login_check($words, $password) : null;
            if ($row === null) {
                chat_login_record_failure($keys);
                $error = 'cl.err_generic';
                app_log('chat login: failed', 'info', true);
            } else {
                chat_login_clear_failures($keys);
                chat_member_start($row);
                app_log('chat login: ok for 3wid ' . (int) $row['id'], 'info', true);
                header('Location: 3wid_chat.php');
                exit;
            }
        }
    }
} elseif (isset($_GET['ended'])) {
    $note = 'cl.ended';
} elseif (isset($_GET['out'])) {
    $note = 'cl.logged_out';
}

$action_token = get_action_token();
$e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('cl.login_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="css/chat_login.css?v=1">
</head>
<body class="cl-body">
    <header class="cl-header">
        <a class="cl-brand" href="index.php"><img src="img/3wid_big.png" alt="">3WordID</a>
        <div class="cl-header-actions"><?php echo lang_switcher(); ?></div>
    </header>

    <main class="cl-wrap">
        <div class="cl-eyebrow"><?php echo t('cl.login_eyebrow'); ?></div>
        <h1><?php echo t('cl.login_h1'); ?></h1>
        <p class="cl-sub"><?php echo t('cl.login_sub'); ?></p>

        <?php if ($error !== ''): ?>
        <p class="cl-flash err" role="alert" id="clError"><?php echo t($error, ['n' => $lock_minutes]); ?></p>
        <?php elseif ($note !== ''): ?>
        <p class="cl-flash" role="status"><?php echo t($note); ?></p>
        <?php endif; ?>

        <div class="cl-card">
        <?php if (!$ready): ?>
            <p style="margin:0"><?php echo t('cl.unavailable'); ?></p>
        <?php else: ?>
            <form method="post" action="3wid_chat_login.php" autocomplete="off" novalidate>
                <label class="cl-field" for="words"><?php echo t('cl.words_label'); ?></label>
                <input class="cl-input" type="text" id="words" name="words" value="<?php echo $e($words); ?>"
                       placeholder="<?php echo t('common.placeholder_words'); ?>" autocomplete="off" autocapitalize="none"
                       autocorrect="off" spellcheck="false" maxlength="200" required<?php echo $error !== '' ? ' aria-describedby="clError"' : ''; ?>>
                <label class="cl-field" for="password"><?php echo t('cl.password_label'); ?></label>
                <input class="cl-input" type="password" id="password" name="password" autocomplete="current-password" maxlength="1024" required>
                <input type="hidden" name="action_token" value="<?php echo $e($action_token); ?>">
                <button type="submit" class="cl-btn cl-btn-primary full"><?php echo t('cl.login_btn'); ?></button>
            </form>
        <?php endif; ?>
        </div>

        <p class="cl-hint"><?php echo t('cl.forgot'); ?></p>
        <p class="cl-hint"><?php echo t_raw('cl.owner_link'); ?></p>
    </main>

    <footer class="cl-footer"><?php echo site_copyright(); ?></footer>
</body>
</html>
