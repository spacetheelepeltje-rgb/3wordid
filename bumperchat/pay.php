<?php
// Bumperchat: pay for a licence plate 3WordID after the free month or a paid year ends.
// One-off iDEAL via Mollie. The price is read from config at payment time and stored
// on the payment row. No automatic renewal.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/plate_billing.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$user = current_user_from_session($conn);
if (!$user) {
    $words = bumperchat_normalize_words((string) ($_GET['words'] ?? $_POST['words'] ?? ''));
    $next = 'pay.php' . ($words !== '' ? ('?words=' . rawurlencode($words)) : '');
    header('Location: login.php?next=' . rawurlencode($next));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$raw = (string) ($_POST['words'] ?? $_GET['words'] ?? '');
$words = bumperchat_normalize_words($raw);
$error = '';
$wid = null;

if ($words !== '') {
    $found = db_3wordid_get_threeword($words);
    if (is_array($found) && (int) ($found['user_id'] ?? 0) === (int) $user['id']) {
        $wid = $found;
    } else {
        $error = 'bc.pay_not_owner';
    }
}

$product = payments_product(BILLING_PRODUCT_PLATE);
$can_pay = $wid && billing_schema_ready() && payments_schema_ready() && payments_configured() && $product;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_pay) {
    if (!hash_equals((string) $_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'common.session_expired';
    } elseif (plate_access_state($wid) !== 'expired' && plate_access_state($wid) !== 'trial' && plate_access_state($wid) !== 'paid') {
        // unbilled plates (already-paying 3WordID accounts) are not charged here
        $error = 'bc.pay_not_owner';
    } else {
        bumperchat_mark_session($words);
        $started = payments_start(BILLING_PRODUCT_PLATE, (int) $user['id'], [
            'subject' => $words,
            'wid_id' => (int) $wid['id'],
            'return_path' => '/bumperchat/pay_return.php',
        ]);
        if (!empty($started['ok']) && !empty($started['checkout_url'])) {
            header('Location: ' . $started['checkout_url']);
            exit;
        }
        $error = $started['error'] === 'not_configured' || $started['error'] === 'public_url'
            ? 'bc.pay_not_configured'
            : 'bc.pay_failed';
    }
}

$state = $wid ? plate_access_state($wid) : '';
$days = $wid ? plate_days_remaining($wid) : null;
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('bc.pay_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=3">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.pay_h1'); ?></h1>
        <p class="bc-lede"><?php echo t_raw('bc.pay_lede'); ?></p>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($error); ?></div>
        <?php endif; ?>

        <?php if ($wid && $product): ?>
        <div class="bc-card">
            <h2><?php echo t('bc.pay_plate', ['words' => $words]); ?></h2>
            <?php if ($state === 'trial' && $days !== null): ?>
                <p class="bc-p"><?php echo t('bc.trial_days', ['n' => (int) $days]); ?></p>
            <?php elseif ($state === 'paid' && !empty($wid['paid_until'])): ?>
                <p class="bc-p"><?php echo t('bc.paid_until', ['date' => bc_e(substr((string) $wid['paid_until'], 0, 10))]); ?></p>
            <?php elseif ($state === 'expired'): ?>
                <p class="bc-p"><?php echo t('bc.expired_p'); ?></p>
            <?php endif; ?>
            <p class="bc-price"><?php echo t('bc.pay_price', ['amount' => $product['amount'], 'currency' => $product['currency']]); ?></p>
            <p class="bc-p"><?php echo t('bc.pay_no_renew'); ?></p>
            <?php if (payments_is_test_mode()): ?>
                <p class="bc-meta"><?php echo t('bc.pay_testmode'); ?></p>
            <?php endif; ?>

            <?php if ($can_pay): ?>
            <form method="post" action="pay.php">
                <input type="hidden" name="words" value="<?php echo bc_e($words); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo bc_e($_SESSION['csrf_token']); ?>">
                <button class="btn btn-primary bc-full" type="submit"><?php echo t('bc.pay_submit'); ?></button>
            </form>
            <?php elseif (!payments_configured() || !billing_schema_ready() || !payments_schema_ready()): ?>
                <p class="bc-flash" role="status"><?php echo t('bc.pay_not_configured'); ?></p>
            <?php endif; ?>
            <p class="bc-meta bc-terms"><?php echo t_raw('bc.pay_terms'); ?></p>
        </div>
        <?php elseif ($words === ''): ?>
            <p class="bc-p"><?php echo t('bc.err_words'); ?></p>
        <?php endif; ?>

        <p class="bc-back"><a href="chat.php"><?php echo t('bc.pay_back_chats'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
