<?php
// Bumperchat claim flow, step 3: register with email + password + optional mobile.
// No login form here: login stays in the top-right corner.
// A user who is already logged in is sent through to the claim (or chat) immediately.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/email_auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$user = current_user_from_session($conn);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$state = bumperchat_claim_state();
$country = bumperchat_country((string) ($state['country'] ?? ''));
$words = bumperchat_normalize_words((string) ($state['words'] ?? ''));
if (!$country || $words === '') {
    header('Location: claim.php');
    exit;
}

// Recheck that the words are still free (and still match the stored plate).
$recalc = bumperchat_plate_to_words($country, (string) ($state['plate'] ?? ''), (string) ($state['region'] ?? ''));
if ($recalc !== $words) {
    header('Location: claim.php');
    exit;
}

$error = '';
$email_value = (string) ($_POST['email'] ?? '');
if ($email_value === '' && function_exists('get_session_email')) {
    $email_value = (string) (get_session_email() ?? '');
}
$phone_value = (string) ($_POST['notify_phone'] ?? '');
$consent_value = !empty($_POST['notify_sms_consent']);

if ($user) {
    bumperchat_try_claim_and_redirect($user, $words, $_POST);
    if (!empty($GLOBALS['error'])) {
        $error = (string) $GLOBALS['error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$user) {
    $email_value = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!bumperchat_csrf_ok()) {
        $error = 'common.session_expired';
    } elseif (botcheck_failed('bumperchat_register') !== '') {
        $error = 'bot.rejected';
    } else {
        $result = email_auth_register($conn, $email_value, $password, (string) ($_POST['password_confirm'] ?? ''));
        if ($result['user_id'] > 0) {
            establish_user_session($conn, $result['user_id'], $email_value);
            bumperchat_mark_session($words);
            $user = current_user_from_session($conn);
            if ($user) {
                bumperchat_try_claim_and_redirect($user, $words, $_POST);
            }
        }
        if ($error === '') {
            $error = $result['error'] !== '' ? $result['error'] : (string) ($GLOBALS['error'] ?? '');
        }
    }
}

// Status recap (no new exists-check slot after a failed POST)
$status = db_3wordid_exists_status($words);
$exists = $status !== null;
$owned = false;
$owned_expired = false;
if ($exists && $user) {
    $own = db_3wordid_get_threeword($words);
    if (is_array($own) && (int) ($own['user_id'] ?? 0) === (int) $user['id']) {
        $owned = true;
        $owned_expired = plate_billing_requires_payment($own);
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
    <title><?php echo t('bc.reg_step_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=7">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.reg_step_h1'); ?></h1>
        <p class="bc-lede"><?php echo t('bc.reg_step_sub'); ?></p>

        <div class="bc-card bc-confirmed">
            <div class="bc-confirmed-head">
                <h2><?php echo t('bc.confirmed_plate'); ?></h2>
                <a class="bc-textlink" href="claim.php"><?php echo t('bc.edit_plate'); ?></a>
            </div>
            <?php echo bumperchat_plate_face_html($country, [
                'value' => bumperchat_format_plate($country, (string) ($state['plate'] ?? ''), (string) ($state['region'] ?? '')),
                'region' => (string) ($state['region'] ?? ''),
                'readonly' => true,
                'id' => 'bcPlateDone',
            ]); ?>
            <p class="bc-confirmed-meta"><?php echo t('bc.confirmed_words', ['words' => $words]); ?>
                · <?php echo bc_e(bumperchat_text($country['name'])); ?><?php
                $reg = (string) ($state['region'] ?? '');
                if ($reg !== '') {
                    echo ' · ' . bc_e($reg);
                }
            ?></p>
        </div>

        <div class="bc-card bc-status <?php echo $exists ? 'is-taken' : 'is-free'; ?>" role="status">
            <?php if ($owned): ?>
                <h2><?php echo t('bc.status_yours', ['words' => $words]); ?></h2>
                <p><?php echo t('bc.status_yours_p'); ?></p>
                <?php if ($owned_expired): ?>
                    <a class="btn btn-primary" href="pay.php?words=<?php echo rawurlencode($words); ?>"><?php echo t('bc.pay_now'); ?></a>
                <?php else: ?>
                    <a class="btn btn-primary" href="chat.php"><?php echo t('bc.my_chats'); ?></a>
                <?php endif; ?>
            <?php elseif ($exists): ?>
                <h2><?php echo t('bc.status_taken', ['words' => $words]); ?></h2>
                <p><?php echo t('bc.status_taken_p'); ?></p>
                <?php echo bumperchat_taken_actions($words, ['logged_in' => (bool) $user]); ?>
            <?php else: ?>
                <h2><?php echo t('bc.status_free', ['words' => $words]); ?></h2>
                <p><?php echo t('bc.status_free_p'); ?></p>
                <p class="bc-meta"><?php echo t('bc.words_from_plate', [
                    'plate' => (string) ($state['plate'] ?? ''),
                    'country' => bumperchat_text($country['name']),
                ]); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($error); ?></div>
        <?php endif; ?>

        <?php if (!$user && !$exists): ?>
        <form class="bc-card" method="post" action="claim_register.php" id="register" autocomplete="off">
            <h2><?php echo t('bc.reg_h'); ?></h2>
            <p class="bc-p"><?php echo t('bc.reg_p'); ?></p>
            <p class="bc-p"><?php echo t('bc.reg_login_hint'); ?></p>
            <label class="bc-label" for="reg_email"><?php echo t('login.email'); ?></label>
            <input id="reg_email" name="email" class="bc-input" type="email" required maxlength="255" placeholder="you@example.com" value="<?php echo bc_e($email_value); ?>">
            <label class="bc-label" for="reg_password"><?php echo t('login.password'); ?></label>
            <input id="reg_password" name="password" class="bc-input" type="password" required minlength="<?php echo (int) EMAIL_PW_MIN; ?>" autocomplete="new-password" placeholder="<?php echo t('reg.password_ph'); ?>">
            <p class="bc-meta"><?php echo t('reg.min10'); ?></p>
            <label class="bc-label" for="reg_confirm"><?php echo t('reg.confirm'); ?></label>
            <input id="reg_confirm" name="password_confirm" class="bc-input" type="password" required minlength="<?php echo (int) EMAIL_PW_MIN; ?>" autocomplete="new-password" placeholder="<?php echo t('reg.repeat_ph'); ?>">
            <?php echo bumperchat_notify_fields($phone_value, $consent_value); ?>
            <?php echo botcheck_fields('bumperchat_register'); ?>
            <?php echo bumperchat_csrf_field(); ?>
            <button class="btn btn-primary bc-full" type="submit"><?php echo t('reg.submit'); ?></button>
            <p class="bc-meta bc-terms"><?php echo t_raw('bc.reg_terms'); ?></p>
        </form>
        <?php elseif ($user && !$exists): ?>
        <form class="bc-card" method="post" action="claim_register.php" id="claim" autocomplete="off">
            <h2><?php echo t('bc.claim_now_h'); ?></h2>
            <p class="bc-p"><?php echo t('bc.claim_now_p'); ?></p>
            <?php echo bumperchat_notify_fields($phone_value, $consent_value); ?>
            <?php echo bumperchat_csrf_field(); ?>
            <button class="btn btn-primary bc-full" type="submit"><?php echo t('bc.claim_now'); ?></button>
            <p class="bc-meta bc-terms"><?php echo t_raw('bc.reg_terms'); ?></p>
        </form>
        <?php endif; ?>

        <p class="bc-back"><a href="claim.php"><?php echo t('bc.edit_plate'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
