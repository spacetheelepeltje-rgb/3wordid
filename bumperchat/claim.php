<?php
// Bumperchat step 2 + 3: type the three words (for example a licence plate), see if that
// 3WordID is free, then create an email + password account or log in, and claim the plate.
//
//   GET  ?words=<three words>   check (same yes/no lookup and limit as the search box)
//   POST do=register            new account (php/email_auth.php), then claim if the plate is free
//   POST do=login               email + password login; then the claim form (phone + consent)
//   POST do=claim               logged-in user claims a free plate (optional phone + SMS consent)
//
// A new Bumperchat plate gets one month free, then a one-off iDEAL payment (see pay.php).
// After login: bumperchat/chat.php, session marked as a Bumperchat login (php/bumperchat.php).

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/email_auth.php';
require_once __DIR__ . '/../php/plate_billing.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$user = current_user_from_session($conn);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$error = '';
$email_value = '';
$open_form = '';   // 'register' or 'login': the form that was sent (keeps the email in it)
$phone_value = (string) ($_POST['notify_phone'] ?? '');
$consent_value = !empty($_POST['notify_sms_consent']);

// The words: from the form (POST) or the link (GET ?words= or ?threewords=)
$raw_words = (string) ($_POST['words'] ?? $_GET['words'] ?? $_GET['threewords'] ?? '');
$words = bumperchat_normalize_words($raw_words);

/**
 * After a successful register or an explicit claim, create the plate when it is free.
 * Redirects to chat (trial/paid/unbilled) or the pay page (already used the free month).
 */
function bumperchat_try_claim_and_redirect(array $user, string $words): void
{
    if ($words === '' || db_3wordid_exists($words)) {
        header('Location: chat.php');
        exit;
    }
    $notify = notify_read_claim_post($_POST);
    if (!$notify['ok']) {
        $GLOBALS['error'] = $notify['error'];
        $GLOBALS['user'] = $user;
        return;
    }
    $result = bumperchat_claim_plate($user, $words, $notify);
    if (!$result['ok']) {
        $GLOBALS['error'] = $result['error'];
        $GLOBALS['user'] = $user;
        return;
    }
    bumperchat_mark_session($words);
    if (($result['state'] ?? '') === 'expired') {
        header('Location: pay.php?words=' . rawurlencode($words));
        exit;
    }
    header('Location: chat.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = (string) ($_POST['do'] ?? '');
    if ($do === 'claim') {
        if (!$user) {
            header('Location: claim.php' . ($words !== '' ? ('?words=' . rawurlencode($words)) : '') . '#login');
            exit;
        }
        if (!hash_equals((string) $_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
            $error = 'common.session_expired';
        } else {
            bumperchat_mark_session($words);
            bumperchat_try_claim_and_redirect($user, $words);
        }
    } elseif (!$user) {
        $open_form = $do === 'register' ? 'register' : 'login';
        $email_value = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!hash_equals((string) $_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
            $error = 'common.session_expired';
        } elseif ($do === 'register' && botcheck_failed('bumperchat_register') !== '') {
            $error = 'bot.rejected';
        } else {
            $result = $do === 'register'
                ? email_auth_register($conn, $email_value, $password, (string) ($_POST['password_confirm'] ?? ''))
                : email_auth_login($conn, $email_value, $password);
            if ($result['user_id'] > 0) {
                establish_user_session($conn, $result['user_id'], $email_value);
                bumperchat_mark_session($words);
                $user = current_user_from_session($conn);
                if ($do === 'register' && $user) {
                    // Phone + consent are on the register form: claim now when the plate is free.
                    bumperchat_try_claim_and_redirect($user, $words);
                } else {
                    // Login: show the claim form (phone + consent) on this page when the plate is free.
                    header('Location: claim.php' . ($words !== '' ? ('?words=' . rawurlencode($words)) : ''));
                    exit;
                }
            }
            $error = $result['error'];
        }
    }
}

// The check (only on a page view, not again after a failed login)
$check = null;   // null = nothing to show, else ['exists' => bool, 'paying' => bool, 'plate' => ?bool]
$check_error = '';
if ($raw_words !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($words === '') {
        $check_error = 'bc.err_words';
    } elseif (!exists_check_allowed()) {
        $check_error = 'bc.err_limit';
    } else {
        $status = db_3wordid_exists_status($words);
        $check = [
            'exists' => $status !== null,
            'paying' => (bool) ($status['paying'] ?? false),
            'plate'  => car_plate_pattern($words) ? car_plate_known($words) : null,
            'digits' => (bool) preg_match('/[0-9]/', $words),
        ];
    }
} elseif ($words !== '' && $check === null) {
    // After a failed POST, still show the last check without consuming a new exists slot
    $status = db_3wordid_exists_status($words);
    $check = [
        'exists' => $status !== null,
        'paying' => (bool) ($status['paying'] ?? false),
        'plate'  => car_plate_pattern($words) ? car_plate_known($words) : null,
        'digits' => (bool) preg_match('/[0-9]/', $words),
    ];
}

$owned = false;
$owned_wid = null;
if ($check && $check['exists'] && $user) {
    $own = db_3wordid_get_threeword($words);
    if (is_array($own) && (int) ($own['user_id'] ?? 0) === (int) $user['id']) {
        $owned = true;
        $owned_wid = $own;
    }
}

$can_claim = $user && $check && !$check['exists'] && $words !== '';
$owned_expired = $owned && plate_billing_requires_payment($owned_wid);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('bc.claim_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=2">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.claim_h1'); ?></h1>
        <p class="bc-lede"><?php echo t_raw('bc.claim_sub'); ?></p>

        <form class="bc-card bc-check" method="get" action="claim.php">
            <label class="bc-label" for="words"><?php echo t('bc.claim_label'); ?></label>
            <div class="bc-inline">
                <input id="words" name="words" class="bc-input" type="text" maxlength="80" autocomplete="off" spellcheck="false" placeholder="<?php echo t('bc.placeholder'); ?>" value="<?php echo bc_e($words !== '' ? $words : $raw_words); ?>">
                <button class="btn btn-primary" type="submit"><?php echo t('bc.check'); ?></button>
            </div>
            <p class="bc-meta"><?php echo t_raw('bc.claim_hint'); ?></p>
        </form>

        <?php if ($check_error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($check_error); ?></div>
        <?php endif; ?>

        <?php if ($check): ?>
        <div class="bc-card bc-status <?php echo $check['exists'] ? 'is-taken' : 'is-free'; ?>" role="status">
            <?php if ($check['exists']): ?>
                <h2><?php echo $owned ? t('bc.status_yours', ['words' => $words]) : t('bc.status_taken', ['words' => $words]); ?></h2>
                <p><?php echo $owned ? t('bc.status_yours_p') : t('bc.status_taken_p'); ?></p>
                <?php if ($owned_expired): ?>
                    <a class="btn btn-primary" href="pay.php?words=<?php echo rawurlencode($words); ?>"><?php echo t('bc.pay_now'); ?></a>
                <?php else: ?>
                    <a class="btn btn-ghost" href="../3wid_forward.php?threewords=<?php echo rawurlencode($words); ?>"><?php echo t('bc.send_message'); ?></a>
                <?php endif; ?>
            <?php else: ?>
                <h2><?php echo t('bc.status_free', ['words' => $words]); ?></h2>
                <p><?php echo t('bc.status_free_p'); ?></p>
            <?php endif; ?>
            <ul class="bc-facts">
                <?php if ($check['plate'] === true): ?><li class="ok"><?php echo t('bc.fact_rdw_yes'); ?></li><?php endif; ?>
                <?php if ($check['plate'] === false): ?><li><?php echo t('bc.fact_rdw_no'); ?></li><?php endif; ?>
                <?php if (!$check['exists'] && $check['digits']): ?><li><?php echo t_raw('bc.fact_digits'); ?></li><?php endif; ?>
                <?php if (!$check['exists'] && $check['plate'] === true): ?><li><?php echo t('bc.fact_carchat'); ?></li><?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($error); ?></div>
        <?php endif; ?>

        <?php if ($can_claim): ?>
        <form class="bc-card" method="post" action="claim.php" id="claim" autocomplete="off">
            <h2><?php echo t('bc.claim_now_h'); ?></h2>
            <p class="bc-p"><?php echo t('bc.claim_now_p'); ?></p>
            <?php echo bumperchat_notify_fields($phone_value, $consent_value); ?>
            <input type="hidden" name="do" value="claim">
            <input type="hidden" name="words" value="<?php echo bc_e($words); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo bc_e($_SESSION['csrf_token']); ?>">
            <button class="btn btn-primary bc-full" type="submit"><?php echo t('bc.claim_now'); ?></button>
            <p class="bc-meta bc-terms"><?php echo t_raw('bc.reg_terms'); ?></p>
        </form>
        <?php elseif ($user): ?>
        <div class="bc-card">
            <h2><?php echo t('bc.logged_in_h'); ?></h2>
            <p class="bc-p"><?php echo t('bc.logged_in_p'); ?></p>
            <a class="btn btn-primary" href="chat.php"><?php echo t('bc.my_chats'); ?></a>
        </div>
        <?php else: ?>
        <div class="bc-two">
            <form class="bc-card" method="post" action="claim.php" id="register" autocomplete="off">
                <h2><?php echo t('bc.reg_h'); ?></h2>
                <p class="bc-p"><?php echo t('bc.reg_p'); ?></p>
                <label class="bc-label" for="reg_email"><?php echo t('login.email'); ?></label>
                <input id="reg_email" name="email" class="bc-input" type="email" required maxlength="255" placeholder="you@example.com" value="<?php echo $open_form === 'register' ? bc_e($email_value) : ''; ?>">
                <label class="bc-label" for="reg_password"><?php echo t('login.password'); ?></label>
                <input id="reg_password" name="password" class="bc-input" type="password" required minlength="<?php echo (int) EMAIL_PW_MIN; ?>" autocomplete="new-password" placeholder="<?php echo t('reg.password_ph'); ?>">
                <p class="bc-meta"><?php echo t('reg.min10'); ?></p>
                <label class="bc-label" for="reg_confirm"><?php echo t('reg.confirm'); ?></label>
                <input id="reg_confirm" name="password_confirm" class="bc-input" type="password" required minlength="<?php echo (int) EMAIL_PW_MIN; ?>" autocomplete="new-password" placeholder="<?php echo t('reg.repeat_ph'); ?>">
                <?php echo bumperchat_notify_fields($open_form === 'register' ? $phone_value : '', $open_form === 'register' && $consent_value); ?>
                <?php echo botcheck_fields('bumperchat_register'); ?>
                <input type="hidden" name="do" value="register">
                <input type="hidden" name="words" value="<?php echo bc_e($words); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo bc_e($_SESSION['csrf_token']); ?>">
                <button class="btn btn-primary bc-full" type="submit"><?php echo t('reg.submit'); ?></button>
                <p class="bc-meta bc-terms"><?php echo t_raw('bc.reg_terms'); ?></p>
            </form>

            <form class="bc-card" method="post" action="claim.php" id="login" autocomplete="on">
                <h2><?php echo t('bc.login_h'); ?></h2>
                <p class="bc-p"><?php echo t('bc.login_p'); ?></p>
                <label class="bc-label" for="login_email"><?php echo t('login.email'); ?></label>
                <input id="login_email" name="email" class="bc-input" type="email" required maxlength="255" placeholder="you@example.com" autocomplete="username" value="<?php echo $open_form === 'login' ? bc_e($email_value) : ''; ?>">
                <label class="bc-label" for="login_password"><?php echo t('login.password'); ?></label>
                <input id="login_password" name="password" class="bc-input" type="password" required autocomplete="current-password" placeholder="<?php echo t('elogin.password_ph'); ?>">
                <input type="hidden" name="do" value="login">
                <input type="hidden" name="words" value="<?php echo bc_e($words); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo bc_e($_SESSION['csrf_token']); ?>">
                <button class="btn btn-primary bc-full" type="submit"><?php echo t('elogin.submit'); ?></button>
            </form>
        </div>
        <?php endif; ?>

        <p class="bc-back"><a href="index.php#formats"><?php echo t('bc.back_formats'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
