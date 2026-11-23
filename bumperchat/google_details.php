<?php
// After a Bumperchat Google login: optional mobile number + SMS consent, reseller
// code, and "Stay logged in". Same fields as email registration on this path.
// Stays inside /bumperchat/. Shown for new Google signups, and for existing
// Google accounts that have never completed this step.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$next = bumperchat_safe_next((string) ($_POST['next'] ?? $_GET['next'] ?? ''), 'chat.php');

$user = current_user_from_session($conn);
if (!$user) {
    header('Location: login.php?next=' . rawurlencode('google_details.php' . ($next !== 'chat.php' ? '?next=' . rawurlencode($next) : '')));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$phone_value = (string) ($_POST['notify_phone'] ?? phone_store_plain($user));
$consent_value = !empty($_POST['notify_sms_consent']) || (!$_POST && (int) ($user['notify_sms_consent'] ?? 0) === 1);
$remember_value = !empty($_POST['remember_me']);
$reseller_unknown = false;
$reseller_code = reseller_remembered_code();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_code = (string) ($_POST['reseller_code'] ?? '');
    if ($posted_code !== '') {
        $reseller_code = reseller_normalize_code($posted_code);
        if ($reseller_code === '') {
            $reseller_code = strtoupper(trim($posted_code));
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bumperchat_csrf_ok()) {
        $error = 'common.session_expired';
    } else {
        $notify = function_exists('notify_read_claim_post') ? notify_read_claim_post($_POST) : ['ok' => true, 'phone' => null, 'consent' => false, 'error' => ''];
        if (!$notify['ok']) {
            $error = $notify['error'];
            $phone_value = (string) ($_POST['notify_phone'] ?? '');
        } else {
            $saved = user_phone_store_save($conn, (int) $user['id'], $notify['phone'], !empty($notify['consent']));
            if (!$saved['ok'] && ($notify['phone'] || !empty($notify['consent']))) {
                $error = $saved['error'] !== '' ? $saved['error'] : 'pii.save_failed';
            }
        }
        if ($error === '') {
            $applied = reseller_apply_to_user((int) $user['id'], (string) ($_POST['reseller_code'] ?? $reseller_code));
            $reseller_unknown = !empty($applied['unknown']);
        }
        if ($error === '') {
            if ($remember_value) {
                remember_issue($conn, (int) $user['id'], 'bumperchat');
            }
            bumperchat_signup_details_mark($conn, (int) $user['id']);
            bumperchat_claim_state_set(['notify_post' => [
                'notify_phone' => (string) ($notify['phone'] ?? ''),
                'notify_sms_consent' => !empty($notify['consent']) ? '1' : '',
            ]]);
            $user = current_user_from_session($conn) ?: $user;
            bumperchat_after_login_redirect($user, $next);
        }
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
    <title><?php echo t('bc.google_details_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=10">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap bc-wrap-narrow">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.google_details_h'); ?></h1>
        <p class="bc-lede"><?php echo t('bc.google_details_p'); ?></p>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($error); ?></div>
        <?php endif; ?>

        <form class="bc-card" method="post" action="google_details.php" autocomplete="on">
            <?php echo bumperchat_notify_fields($phone_value, $consent_value); ?>
            <?php echo reseller_field_html(['code' => $reseller_code, 'unknown' => $reseller_unknown, 'id' => 'google_reseller']); ?>
            <label class="bc-check-row">
                <input type="checkbox" name="remember_me" value="1"<?php echo $remember_value ? ' checked' : ''; ?>>
                <span><?php echo t('login.remember'); ?><br><span class="bc-meta" style="margin:4px 0 0"><?php echo t('login.remember_hint'); ?></span></span>
            </label>
            <input type="hidden" name="next" value="<?php echo bc_e($next); ?>">
            <?php echo bumperchat_csrf_field(); ?>
            <button class="btn btn-primary bc-full" type="submit"><?php echo t('bc.google_details_submit'); ?></button>
            <p class="bc-meta"><?php echo t('bc.google_details_skip'); ?></p>
        </form>
    </main>

    <footer class="site-footer">
        <span><?php echo bumperchat_footer_links(); ?></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
