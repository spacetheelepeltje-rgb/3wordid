<?php
// Bumperchat step 4: the chats of the logged-in user, one row per 3WordID, each opening the
// existing chat (3wid_list_messages.php). No list / edit / admin pages are linked for a
// Bumperchat login (3wid_list.php sends a Bumperchat session back here).
// Also shows what happened to the three words they came for (claim.php), with the existing
// rules: digits need a paid account, Car chat is turned on with the RDW + Dutch IP check.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/plate_billing.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$user = current_user_from_session($conn);
if (!$user) {
    header('Location: login.php?next=chat.php');
    exit;
}
// Same rule as 3wid_list.php: privacy consent first (that page comes back here afterwards)
if ((int) ($user['privacy_consent'] ?? 0) === 0) {
    header('Location: ../3wid_privacy_consent.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$is_bumperchat = !empty($_SESSION['bumperchat']);
$rows = db_3wordid_list((int) $user['id']);
$summary = db_3wordid_mail_summary((int) $user['id']);
$paying = user_is_paying($user);

// The three words they came for, and where they stand
$wanted = bumperchat_session_words();
$wanted_state = '';
if ($wanted !== '') {
    $mine = false;
    foreach ($rows as $r) {
        if (strtolower(trim($r['threeword'])) === $wanted) { $mine = true; break; }
    }
    if ($mine) {
        $wanted_state = 'mine';
    } elseif (db_3wordid_exists_status($wanted) !== null) {
        $wanted_state = 'taken';
    } else {
        // Still free: claim it on the claim page (trial starts there).
        $wanted_state = 'free';
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
    <title><?php echo t('bc.chat_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=5">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.chat_h1'); ?></h1>

        <?php if ($wanted_state !== ''): ?>
        <div class="bc-card bc-status <?php echo $wanted_state === 'mine' ? 'is-free' : 'is-taken'; ?>">
            <h2><?php echo t('bc.wanted_' . $wanted_state . '_h', ['words' => $wanted]); ?></h2>
            <p class="bc-p"><?php echo t_raw('bc.wanted_' . $wanted_state . '_p', ['words' => bc_e($wanted)]); ?></p>
            <?php if ($wanted_state === 'taken'): ?>
                <a class="btn btn-ghost" href="../3wid_forward.php?threewords=<?php echo rawurlencode($wanted); ?>"><?php echo t('bc.send_message'); ?></a>
            <?php elseif ($wanted_state === 'free'): ?>
                <a class="btn btn-ghost" href="claim.php?words=<?php echo rawurlencode($wanted); ?>"><?php echo t('bc.claim_now'); ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="bc-card">
            <h2><?php echo t('bc.your_chats'); ?></h2>
            <?php if (!$rows): ?>
                <p class="bc-p"><?php echo t('bc.no_ids'); ?></p>
            <?php else: ?>
            <ul class="bc-chat-list">
                <?php foreach ($rows as $r):
                    $m = $summary[(int) $r['id']] ?? ['in' => 0, 'unread' => 0, 'sent' => 0];
                    $has = ($m['in'] + $m['sent']) > 0;
                    $bill = plate_access_state($r);
                    $days = plate_days_remaining($r);
                    $expired = $bill === 'expired';
                ?>
                <li class="bc-chat-row">
                    <span class="bc-chat-name"><?php echo bc_e($r['threeword']); ?></span>
                    <?php if ((int) $r['enabled'] !== 1): ?><span class="bc-pill"><?php echo t('bc.off'); ?></span><?php endif; ?>
                    <?php if ($bill === 'trial' && $days !== null): ?><span class="bc-pill bc-trial"><?php echo t('bc.trial_days', ['n' => (int) $days]); ?></span><?php endif; ?>
                    <?php if ($bill === 'paid' && !empty($r['paid_until'])): ?><span class="bc-pill"><?php echo t('bc.paid_until', ['date' => bc_e(substr((string) $r['paid_until'], 0, 10))]); ?></span><?php endif; ?>
                    <?php if ($expired): ?><span class="bc-pill bc-unread"><?php echo t('bc.expired_h'); ?></span><?php endif; ?>
                    <?php if ($m['unread'] > 0 && !$expired): ?><span class="bc-pill bc-unread"><?php echo t('bc.unread', ['n' => (int) $m['unread']]); ?></span><?php endif; ?>
                    <?php if ($expired): ?>
                        <a class="btn btn-primary bc-open" href="pay.php?words=<?php echo rawurlencode($r['threeword']); ?>"><?php echo t('bc.pay_now'); ?></a>
                    <?php elseif ($has): ?>
                        <a class="btn btn-primary bc-open" href="../3wid_list_messages.php?wid_id=<?php echo (int) $r['id']; ?>"><?php echo t('bc.open_chat'); ?></a>
                    <?php else: ?>
                        <span class="bc-meta"><?php echo t('bc.no_messages'); ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <?php
        $pending_groups = function_exists('pending_conversations_for_sender')
            ? pending_conversations_for_sender((int) $user['id'])
            : [];
        if ($pending_groups && function_exists('pending_sort_conversations')) {
            $pending_groups = pending_sort_conversations($pending_groups);
        }
        if ($pending_groups):
        ?>
        <div class="bc-card">
            <h2><?php echo t('pending.section'); ?></h2>
            <p class="bc-p"><?php echo t('pending.section_p'); ?></p>
            <ul class="bc-chat-list">
                <?php foreach ($pending_groups as $c):
                    $last = $c['items'][count($c['items']) - 1];
                    $st = (string) ($c['pending_status'] ?? 'waiting');
                    $from_wid = (int) ($c['from_wid_id'] ?? 0);
                    $href = ($st === 'delivered' || $st === 'read') && $from_wid > 0 && (int) ($c['wid'] ?? 0) > 0
                        ? '../3wid_list_messages.php?wid_id=' . $from_wid . '&with=' . rawurlencode('w' . (int) $c['wid'])
                        : ($from_wid > 0
                            ? '../3wid_list_messages.php?wid_id=' . $from_wid . '&with=' . rawurlencode($c['key'])
                            : 'pending_send.php?words=' . rawurlencode((string) ($c['words'] ?? '')));
                    $preview = ($st === 'expired' && ($last['message'] ?? '') === '') ? t_plain('pending.expired_preview') : (string) ($last['message'] ?? '');
                    if (function_exists('chat_preview')) {
                        $preview = ($st === 'expired' && ($last['message'] ?? '') === '') ? t_plain('pending.expired_preview') : chat_preview($last['message'] ?? '');
                    }
                ?>
                <li class="bc-chat-row">
                    <span class="bc-chat-name"><?php echo bc_e($c['name']); ?></span>
                    <span class="bc-pill bc-pending-<?php echo bc_e($st); ?>"><?php echo t('pending.status_' . $st); ?></span>
                    <span class="bc-meta"><?php echo bc_e($preview); ?></span>
                    <a class="btn btn-primary bc-open" href="<?php echo bc_e($href); ?>"><?php echo t('pending.view'); ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form class="bc-card" method="post" action="../3wid_forward.php">
            <h2><?php echo t('bc.message_car_h'); ?></h2>
            <p class="bc-p"><?php echo t('bc.message_car_p'); ?></p>
            <div class="bc-inline">
                <input name="threewords" class="bc-input" type="text" maxlength="80" autocomplete="off" spellcheck="false" placeholder="<?php echo t('bc.placeholder'); ?>" aria-label="<?php echo t('bc.search_label'); ?>">
                <button class="btn btn-primary" type="submit"><?php echo t('bc.lookup'); ?></button>
            </div>
            <input type="hidden" name="from" value="bumperchat">
            <input type="hidden" name="csrf_token" value="<?php echo bc_e($_SESSION['csrf_token']); ?>">
            <p class="bc-meta"><a href="pending_send.php"><?php echo t('pending.leave_message'); ?></a> · <a href="index.php#formats"><?php echo t('bc.how_plate'); ?></a></p>
        </form>

        <?php if (!$is_bumperchat): ?>
        <p class="bc-back"><a href="../3wid_list.php"><?php echo t('tb.my_ids'); ?></a></p>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
