<?php
// Bumperchat step 2 + 3: type the three words (for example a licence plate), see if that
// 3WordID is free, then create an email + password account or log in.
//
//   GET  ?words=<three words>   check (same yes/no lookup and limit as the search box)
//   POST do=register            new account (php/email_auth.php, same as login/email_register.php)
//   POST do=login               email + password login (php/email_auth.php)
//
// After login: bumperchat/chat.php, and the session is marked as a Bumperchat login
// (no list / admin pages, see php/bumperchat.php). This page never creates the 3WordID
// itself: the existing rules (digits = paid account, Car chat = RDW + Dutch IP on the edit
// page) still decide that. See docs/BUMPERCHAT.md.

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

$error = '';
$email_value = '';
$open_form = '';   // 'register' or 'login': the form that was sent (keeps the email in it)

// The words: from the form (POST) or the link (GET ?words= or ?threewords=)
$raw_words = (string) ($_POST['words'] ?? $_GET['words'] ?? $_GET['threewords'] ?? '');
$words = bumperchat_normalize_words($raw_words);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$user) {
    $do = (string) ($_POST['do'] ?? '');
    $open_form = $do === 'register' ? 'register' : 'login';
    $email_value = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!hash_equals((string) $_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'common.session_expired';
    } elseif ($do === 'register' && botcheck_failed('bumperchat_register') !== '') {
        // Honeypot filled in, sent too fast, or an old/forged form (logged with the IP)
        $error = 'bot.rejected';
    } else {
        $result = $do === 'register'
            ? email_auth_register($conn, $email_value, $password, (string) ($_POST['password_confirm'] ?? ''))
            : email_auth_login($conn, $email_value, $password);
        if ($result['user_id'] > 0) {
            establish_user_session($conn, $result['user_id'], $email_value);
            bumperchat_mark_session($words);
            app_log('bumperchat: ' . ($do === 'register' ? 'account created' : 'login') . ' user ' . (int) $result['user_id'], 'info', true);
            header('Location: chat.php');
            exit;
        }
        $error = $result['error'];
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
            // Dutch plate in the RDW register? yes / no only (null = not a plate pattern or RDW unknown)
            'plate'  => car_plate_pattern($words) ? car_plate_known($words) : null,
            'digits' => (bool) preg_match('/[0-9]/', $words),
        ];
    }
}
$owned = false;
if ($check && $check['exists'] && $user) {
    $own = db_3wordid_get_threeword($words);
    $owned = is_array($own) && (int) ($own['user_id'] ?? 0) === (int) $user['id'];
}
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
    <link rel="stylesheet" href="../css/bumperchat.css?v=1">
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
                <a class="btn btn-ghost" href="../3wid_forward.php?threewords=<?php echo rawurlencode($words); ?>"><?php echo t('bc.send_message'); ?></a>
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

        <?php if ($user): ?>
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
        <span><a href="../terms.php"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
