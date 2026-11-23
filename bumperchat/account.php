<?php
// Bumperchat account page: show and edit the email address and mobile number
// (stored encrypted). Stays inside /bumperchat/.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$user = current_user_from_session($conn);
if (!$user) {
    header('Location: login.php?next=account.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$notice = '';
$email_value = email_store_plain($user);
if ($email_value === '' && function_exists('get_session_email')) {
    $email_value = (string) (get_session_email() ?? '');
}
$phone_value = phone_store_plain($user);
$consent_value = (int) ($user['notify_sms_consent'] ?? 0) === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_value = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone_value = (string) ($_POST['notify_phone'] ?? '');
    $consent_value = !empty($_POST['notify_sms_consent']);
    if (!bumperchat_csrf_ok()) {
        $error = 'common.session_expired';
    } else {
        $saved = email_store_save($conn, (int) $user['id'], $email_value);
        if (!$saved['ok']) {
            $error = $saved['error'] !== '' ? $saved['error'] : 'pii.save_failed';
        } else {
            set_session_email($saved['plain']);
            $notify = function_exists('notify_read_claim_post') ? notify_read_claim_post($_POST) : ['ok' => true, 'phone' => null, 'consent' => false, 'error' => ''];
            if (!$notify['ok']) {
                $error = $notify['error'];
            } else {
                $ps = user_phone_store_save($conn, (int) $user['id'], $notify['phone'], !empty($notify['consent']));
                if (!$ps['ok']) {
                    $error = $ps['error'] !== '' ? $ps['error'] : 'pii.save_failed';
                }
            }
        }
        if ($error === '') {
            $notice = 'pii.saved';
            $user = current_user_from_session($conn) ?: $user;
            $email_value = email_store_plain($user) ?: $email_value;
            $phone_value = phone_store_plain($user);
            $consent_value = (int) ($user['notify_sms_consent'] ?? 0) === 1;
        }
    }
}

$keys_ok = pii_email_keys_ready();
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('bc.account_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=10">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap bc-wrap-narrow">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.account_h'); ?></h1>
        <p class="bc-lede"><?php echo t('bc.account_p'); ?></p>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($error); ?></div>
        <?php elseif ($notice !== ''): ?>
            <div class="bc-flash is-ok" role="status"><?php echo t($notice); ?></div>
        <?php endif; ?>

        <?php if (!$keys_ok && $email_value === ''): ?>
            <div class="bc-flash" role="status"><?php echo t('pii.unavailable'); ?></div>
        <?php endif; ?>

        <form class="bc-card" method="post" action="account.php" autocomplete="on">
            <label class="bc-label" for="acct_email"><?php echo t('login.email'); ?></label>
            <input id="acct_email" name="email" class="bc-input" type="email" required maxlength="255" autocomplete="email" value="<?php echo bc_e($email_value); ?>"<?php echo $keys_ok ? '' : ' readonly'; ?>>
            <?php echo bumperchat_notify_fields($phone_value, $consent_value); ?>
            <?php echo bumperchat_csrf_field(); ?>
            <button class="btn btn-primary bc-full" type="submit"<?php echo $keys_ok ? '' : ' disabled'; ?>><?php echo t('acct.save'); ?></button>
        </form>

        <p class="bc-back"><a href="chat.php"><?php echo t('bch.back_chats'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><?php echo bumperchat_footer_links(); ?></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
