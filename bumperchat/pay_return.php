<?php
// Bumperchat: back from Mollie iDEAL. Shows the status of the payment.
// The webhook (or this page, if the webhook is delayed) fetches the status
// from Mollie by id; the query string is not trusted as proof of payment.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/plate_billing.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$user = current_user_from_session($conn);
if (!$user) {
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $next = 'pay_return.php' . ($qs !== '' ? ('?' . $qs) : '');
    header('Location: login.php?next=' . rawurlencode(bumperchat_safe_next($next, 'chat.php')));
    exit;
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$ref = (string) ($_GET['ref'] ?? '');
$row = $ref !== '' ? payments_sync_ref($ref) : null;
if ($row && (int) ($row['user_id'] ?? 0) !== (int) $user['id']) {
    $row = null;
}

$status = $row ? (string) $row['status'] : '';
$words = $row ? (string) ($row['subject'] ?? '') : '';
$is_paid = $status === 'paid';
$key = 'bc.pay_return_pending';
if ($is_paid) {
    $key = 'bc.pay_return_paid';
} elseif (in_array($status, ['canceled', 'expired'], true)) {
    $key = 'bc.pay_return_canceled';
} elseif (in_array($status, ['failed'], true)) {
    $key = 'bc.pay_return_failed';
} elseif ($status === 'open' || $status === 'pending') {
    $key = 'bc.pay_return_pending';
} elseif (!$row) {
    $key = 'bc.pay_return_pending';
}

if ($words !== '') {
    bumperchat_mark_session($words);
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('bc.pay_return_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=4">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.pay_return_title'); ?></h1>

        <div class="bc-card bc-status <?php echo $is_paid ? 'is-free' : 'is-taken'; ?>" role="status">
            <h2><?php echo $words !== '' ? t($key, ['words' => $words]) : t($key, ['words' => '']); ?></h2>
            <?php if ($is_paid && !empty($row['period_end'])): ?>
                <p class="bc-p"><?php echo t('bc.paid_until', ['date' => bc_e(substr((string) $row['period_end'], 0, 10))]); ?></p>
            <?php endif; ?>
            <?php if (!$is_paid && $words !== ''): ?>
                <a class="btn btn-primary" href="pay.php?words=<?php echo rawurlencode($words); ?>"><?php echo t('bc.pay_now'); ?></a>
            <?php endif; ?>
        </div>

        <p class="bc-back"><a href="chat.php"><?php echo t('bc.pay_back_chats'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
