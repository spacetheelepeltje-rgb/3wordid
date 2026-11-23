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
    header('Location: ../3wid_privacy_consent.php?from=bumperchat');
    exit;
}

// Chat view is only for accounts that already claimed a licence plate.
if (function_exists('bumperchat_user_has_plate') && !bumperchat_user_has_plate($user)) {
    bumperchat_set_notice('bc.first_register_plate');
    header('Location: claim.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

// Plate-only accounts (and Bumperchat logins) live here; chat.php never links to the main-site list.
bumperchat_mark_session(bumperchat_session_words());

// "Message a car": same formatter / validator / country list as claim.php and pending_send.php
$formats = bumperchat_formats();
$countries = $formats['countries'] ?? [];
$country_explicit = strtoupper(trim((string) ($_GET['country'] ?? ''))) !== '';
$selected = strtoupper(trim((string) ($_GET['country'] ?? '')));
if ($selected === '' || !bumperchat_country($selected)) {
    $selected = bumperchat_remembered_country();
    $country_explicit = false;
}
bumperchat_remember_country($selected);
$chosen = bumperchat_country($selected);
$region = strtoupper(trim((string) ($_GET['region'] ?? '')));
$plate = (string) ($_GET['plate'] ?? '');
if ($chosen && $plate !== '') {
    $plate = bumperchat_format_plate($chosen, $plate, $region);
}
$chat_plate_error = (string) ($_SESSION['bc_chat_error'] ?? '');
unset($_SESSION['bc_chat_error']);
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
    <link rel="stylesheet" href="../css/bumperchat.css?v=10">
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
                <a class="btn btn-ghost" href="../3wid_forward.php?from=bumperchat&amp;threewords=<?php echo rawurlencode($wanted); ?>"><?php echo t('bc.send_message'); ?></a>
                <?php echo bumperchat_dispute_link($wanted); ?>
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

        <form class="bc-card bc-claim-form bc-chatform" method="get" action="pending_send.php" id="bcClaimForm" autocomplete="off">
            <h2><?php echo t('bc.message_car_h'); ?></h2>
            <p class="bc-p"><?php echo t('bc.message_car_p'); ?></p>
            <?php if ($chat_plate_error !== ''): ?><div class="bc-flash" role="alert"><?php echo t($chat_plate_error); ?></div><?php endif; ?>

            <div class="bc-plate-row bc-chat-plate-row">
                <div class="bc-country-pick">
                    <button type="button" class="bc-country-btn" id="bcCountryBtn" aria-haspopup="true" aria-expanded="false" aria-controls="bcCountryPop" title="<?php echo bc_e(t_plain('bc.country_btn')); ?>" aria-label="<?php echo bc_e(t_plain('bc.country_btn')); ?>">
                        <?php echo bumperchat_globe_icon(18); ?>
                        <span class="bc-country-cc" id="bcCountryBtnCc"><?php echo bc_e($chosen ? ($chosen['strip'] ?? $chosen['code']) : ''); ?></span>
                    </button>
                    <div class="bc-country-pop" id="bcCountryPop" hidden>
                        <label class="bc-label" for="bcCountry"><?php echo t('bc.country'); ?></label>
                        <select id="bcCountry" name="country" class="bc-input">
                            <?php foreach ($countries as $c): ?>
                            <option value="<?php echo bc_e($c['code']); ?>"<?php echo $c['code'] === $selected ? ' selected' : ''; ?>><?php echo bc_e(bumperchat_text($c['name'])); ?> (<?php echo bc_e($c['strip']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div id="bcRegionWrap"<?php echo ($chosen && !empty($chosen['regions'])) ? '' : ' hidden'; ?>>
                            <label class="bc-label" for="bcRegion"><?php echo t('bc.region'); ?></label>
                            <select id="bcRegion" name="region" class="bc-input">
                                <option value=""><?php echo t('bc.choose_region_ph'); ?></option>
                                <?php if ($chosen && !empty($chosen['regions'])): foreach ($chosen['regions'] as $code => $label): ?>
                                <option value="<?php echo bc_e($code); ?>"<?php echo $code === $region ? ' selected' : ''; ?>><?php echo bc_e($code . ' · ' . $label); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php echo bumperchat_plate_face_html($chosen ?: ['code' => 'NL', 'strip' => 'NL', 'examples' => [['plate' => 'AB-123-C']]], [
                    'value' => $plate,
                    'region' => $region,
                ]); ?>
                <button class="btn btn-primary bc-next" type="submit" id="bcNext"><?php echo t('bc.lookup'); ?></button>
            </div>
            <div class="bc-result" id="bcResult">
                <span class="bc-result-label"><?php echo t('bc.your_words'); ?></span>
                <output id="bcWords" class="bc-words" for="bcPlate" aria-live="polite"><?php echo t('bc.js_type_plate'); ?></output>
            </div>
            <p class="bc-suggest" id="bcSuggest" hidden></p>
            <noscript><p class="bc-meta"><?php echo t('bc.claim_nojs'); ?></p></noscript>
            <p class="bc-meta"><a href="pending_send.php"><?php echo t('pending.leave_message'); ?></a> · <a href="index.php#formats"><?php echo t('bc.how_plate'); ?></a> · <a href="help.php"><?php echo t('bc.help_link'); ?></a></p>
        </form>

    </main>

    <footer class="site-footer">
        <span><?php echo bumperchat_footer_links(); ?></span>
        <?php echo site_copyright(); ?>
    </footer>
<?php echo i18n_js([
    'bc_example'        => 'bc.js_example',
    'bc_type_plate'     => 'bc.js_type_plate',
    'bc_no_fit'         => 'bc.js_no_fit',
    'bc_plate_invalid'  => 'bc.plate_invalid',
    'bc_format_inline'  => 'bc.plate_format_inline',
    'bc_plate_prefix_hint' => 'bc.plate_prefix_hint',
    'bc_suggest'        => 'bc.suggest_country',
    'bc_choose_region'  => 'bc.choose_region_ph',
    'bc_err_region'     => 'bc.err_region',
    'bc_country_btn'    => 'bc.country_btn',
]); ?>
    <script>
    window.BC_FORMATS = <?php echo json_encode($formats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
    window.BC_LANG = <?php echo json_encode(current_lang()); ?>;
    window.BC_PENDING_FORM = true;   // validate only: the message itself is typed on the next page
    window.BC_CHAT_FORM = true;      // compact country icon + dropdown
    window.BC_COUNTRY_EXPLICIT = <?php echo json_encode($country_explicit || $plate !== ''); ?>;
    </script>
    <script src="../js/bumperchat.js?v=12"></script>
</body>
</html>
