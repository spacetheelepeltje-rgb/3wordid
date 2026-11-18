<?php
// Bumperchat email + password login (top-right corner). No login form in the claim flow.
// After login, return to the claim flow or to chat. Optional "stay logged in" (30 days).

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/email_auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$next = bumperchat_safe_next((string) ($_POST['next'] ?? $_GET['next'] ?? ''), '');
if ($next === '' || $next === 'login.php') {
    $state = bumperchat_claim_state();
    $next = bumperchat_normalize_words((string) ($state['words'] ?? '')) !== ''
        ? 'claim_register.php'
        : ((string) ($state['country'] ?? '') !== '' ? 'claim_plate.php' : 'chat.php');
}

$user = current_user_from_session($conn);
if ($user) {
    bumperchat_after_login_redirect($user, $next);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$error = '';
$email_value = '';
$remember_value = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_value = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $remember_value = !empty($_POST['remember_me']);
    if (!bumperchat_csrf_ok()) {
        $error = 'common.session_expired';
    } else {
        $result = email_auth_login($conn, $email_value, $password);
        if ($result['user_id'] > 0) {
            establish_user_session($conn, $result['user_id'], $email_value);
            $words = bumperchat_normalize_words((string) (bumperchat_claim_state()['words'] ?? ''));
            bumperchat_mark_session($words);
            if ($remember_value) {
                remember_issue($conn, $result['user_id'], 'bumperchat');
            }
            $user = current_user_from_session($conn);
            if ($user) {
                bumperchat_after_login_redirect($user, $next);
            }
        }
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('bc.login_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=3">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap bc-wrap-narrow">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.login_h'); ?></h1>
        <p class="bc-lede"><?php echo t('bc.login_p'); ?></p>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($error); ?></div>
        <?php endif; ?>

        <form class="bc-card" method="post" action="login.php" autocomplete="on">
            <label class="bc-label" for="login_email"><?php echo t('login.email'); ?></label>
            <input id="login_email" name="email" class="bc-input" type="email" required maxlength="255" placeholder="you@example.com" autocomplete="username" value="<?php echo bc_e($email_value); ?>">
            <label class="bc-label" for="login_password"><?php echo t('login.password'); ?></label>
            <input id="login_password" name="password" class="bc-input" type="password" required autocomplete="current-password" placeholder="<?php echo t('elogin.password_ph'); ?>">
            <label class="bc-check-row">
                <input type="checkbox" name="remember_me" value="1"<?php echo $remember_value ? ' checked' : ''; ?>>
                <span><?php echo t('login.remember'); ?><br><span class="bc-meta" style="margin:4px 0 0"><?php echo t('login.remember_hint'); ?></span></span>
            </label>
            <input type="hidden" name="next" value="<?php echo bc_e($next); ?>">
            <?php echo bumperchat_csrf_field(); ?>
            <button class="btn btn-primary bc-full" type="submit"><?php echo t('elogin.submit'); ?></button>
        </form>

        <p class="bc-back"><a href="claim.php"><?php echo t('bc.login_claim_hint'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
