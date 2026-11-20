<?php
// Leave a message for a valid plate that has no claimed 3WordID yet.
// Logged-in members only; they send from one of their own 3WordIDs.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/chat.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$formats = bumperchat_formats();
$countries = $formats['countries'] ?? [];
$prefill = bumperchat_prefill_from_words((string) ($_GET['words'] ?? $_GET['threeword'] ?? ''));
$default_country = bumperchat_default_country();

$selected = strtoupper(trim((string) ($_GET['country'] ?? '')));
if ($selected === '' && !empty($prefill['country'])) {
    $selected = (string) $prefill['country'];
}
if ($selected === '') {
    $selected = $default_country;
}
$region = strtoupper(trim((string) ($_GET['region'] ?? ($prefill['region'] ?? ''))));
$plate = (string) ($_GET['plate'] ?? ($prefill['plate'] ?? ''));
$wanted_words = pending_normalize_words((string) ($_GET['words'] ?? $_GET['threeword'] ?? ''));

$chosen = null;
foreach ($countries as $c) {
    if ($c['code'] === $selected) {
        $chosen = $c;
        break;
    }
}
if ($chosen && $plate !== '' && function_exists('bumperchat_format_plate')) {
    $plate = bumperchat_format_plate($chosen, $plate, $region);
}

$show_words = '';
$plate_ok = false;
if ($chosen && $plate !== '') {
    $needs_region = !empty($chosen['regions']) && $region === '';
    if (!$needs_region) {
        $show_words = bumperchat_plate_to_words($chosen, $plate, $region);
        $plate_ok = ($show_words !== '');
    }
}
if ($show_words === '' && $wanted_words !== '') {
    $show_words = $wanted_words;
}

$login_next = 'pending_send.php';
$qn = [];
if ($selected !== '') { $qn['country'] = $selected; }
if ($region !== '') { $qn['region'] = $region; }
if ($plate !== '') { $qn['plate'] = $plate; }
if ($wanted_words !== '') { $qn['words'] = $wanted_words; }
if ($qn) {
    $login_next .= '?' . http_build_query($qn, '', '&', PHP_QUERY_RFC3986);
}

$user = current_user_from_session($conn);
if (!$user) {
    header('Location: login.php?next=' . rawurlencode($login_next));
    exit;
}
if ((int) ($user['privacy_consent'] ?? 0) !== 1) {
    header('Location: ../3wid_privacy_consent.php');
    exit;
}

$user_wids = db_3wid_get_3wids((int) $user['id']);
if (!$user_wids) {
    $need_wid = true;
} else {
    $need_wid = false;
}

$from_id = (int) ($_GET['from_id'] ?? 0);
$from_words = '';
if (!$need_wid) {
    $from_id_ok = false;
    foreach ($user_wids as $row) {
        if ($from_id === (int) $row['id']) {
            $from_id_ok = true;
            $from_words = (string) $row['threeword'];
            break;
        }
    }
    if (!$from_id_ok) {
        $from_id = (int) $user_wids[0]['id'];
        $from_words = (string) $user_wids[0]['threeword'];
    }
}

$example = '';
if ($chosen) {
    $example = function_exists('bumperchat_plate_example') ? bumperchat_plate_example($chosen) : (string) (($chosen['examples'][0]['plate'] ?? ''));
    $example = str_replace('|', ' ', $example);
}

$claimed = ($plate_ok && $show_words !== '') ? db_3wordid_get_threeword($show_words) : null;
$already = is_array($claimed) && !empty($claimed['id']);

$schema_ok = pending_schema_ready();
$used = 0;
$remaining = pending_max_per_plate();
if ($schema_ok && $plate_ok && $show_words !== '' && !$already) {
    $owns = false;
    $pconn = function_exists('get_db_conn') ? get_db_conn() : null;
    if ($pconn) {
        $used = pending_count_waiting($pconn, (int) $user['id'], $show_words);
        $pconn->close();
    }
    $remaining = pending_remaining($used);
}

$error = (string) ($_SESSION['pending_error'] ?? '');
$notice = (string) ($_SESSION['pending_notice'] ?? '');
if (isset($_SESSION['pending_remaining']) && is_int($_SESSION['pending_remaining'])) {
    $remaining = (int) $_SESSION['pending_remaining'];
}
unset($_SESSION['pending_error'], $_SESSION['pending_notice'], $_SESSION['pending_remaining']);
$draft = (string) ($_SESSION['chat_draft'] ?? '');
unset($_SESSION['chat_draft']);

$action_token = get_action_token();
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('pending.send_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=8">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('pending.send_h1'); ?></h1>
        <p class="bc-lead"><?php echo t('pending.send_sub', ['days' => (string) pending_ttl_days(), 'max' => (string) pending_max_per_plate()]); ?></p>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php
                if ($error === 'bc.plate_invalid' && $chosen) {
                    echo t('bc.plate_invalid', [
                        'country' => bumperchat_text($chosen['name']),
                        'example' => $example,
                    ]);
                } else {
                    echo t($error);
                }
            ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="bc-flash is-ok" role="status"><?php
                if ($notice === 'pending.sent') {
                    echo t('pending.sent', ['n' => (string) $remaining]);
                } else {
                    echo t($notice);
                }
            ?></div>
        <?php endif; ?>

        <?php if ($need_wid): ?>
            <div class="bc-card">
                <p class="bc-p"><?php echo t('pending.need_wid'); ?></p>
                <p><a class="btn btn-primary" href="claim.php"><?php echo t('bc.claim_link'); ?></a></p>
            </div>
        <?php elseif (!$schema_ok): ?>
            <div class="bc-card">
                <p class="bc-p"><?php echo t('pending.err_schema'); ?></p>
            </div>
        <?php elseif ($already): ?>
            <div class="bc-card bc-status is-taken" role="status">
                <h2><?php echo t('pending.already_claimed'); ?></h2>
                <p class="bc-p"><?php echo t('pending.already_claimed_p', ['words' => $show_words]); ?></p>
                <a class="btn btn-primary" href="../3wid_messageform.php?threeword_id=<?php echo (int) $from_id; ?>&amp;threeword=<?php echo rawurlencode($show_words); ?>"><?php echo t('bc.send_message'); ?></a>
                <?php echo bumperchat_dispute_link($show_words); ?>
            </div>
        <?php else: ?>
        <form class="bc-card bc-claim-form" method="post" action="pending_process.php" id="bcClaimForm" autocomplete="off">
            <label class="bc-label" for="bcCountry"><?php echo t('bc.country'); ?></label>
            <div class="bc-inline">
                <select id="bcCountry" name="country" class="bc-input">
                    <option value=""><?php echo t('bc.choose_country_ph'); ?></option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?php echo bc_e($c['code']); ?>"<?php echo $c['code'] === $selected ? ' selected' : ''; ?>><?php echo bc_e(bumperchat_text($c['name'])); ?> (<?php echo bc_e($c['strip']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($countries as $c):
                $is_on = ($chosen && $c['code'] === $selected);
            ?>
            <div class="bc-country-rules<?php echo $is_on ? ' is-on' : ''; ?>" data-country="<?php echo bc_e($c['code']); ?>"<?php echo $is_on ? '' : ' hidden'; ?>>
                <?php echo bumperchat_country_rules_html($c); ?>
            </div>
            <?php endforeach; ?>

            <div id="bcRegionWrap"<?php echo ($chosen && !empty($chosen['regions'])) ? '' : ' hidden'; ?>>
                <label class="bc-label" for="bcRegion"><?php echo t('bc.region'); ?></label>
                <select id="bcRegion" name="region" class="bc-input">
                    <option value=""><?php echo t('bc.choose_region_ph'); ?></option>
                    <?php if ($chosen && !empty($chosen['regions'])): ?>
                        <?php foreach ($chosen['regions'] as $code => $label): ?>
                        <option value="<?php echo bc_e($code); ?>"<?php echo $code === $region ? ' selected' : ''; ?>><?php echo bc_e($code . ' · ' . $label); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <label class="bc-label" for="bcPlate"><?php echo t('bc.plate'); ?></label>
            <p class="bc-plate-hint" id="bcPlateHint"><?php
                if ($chosen) {
                    echo t('bc.plate_format_inline', [
                        'format' => (string) ($chosen['format'] ?? ''),
                        'example' => $example,
                    ]);
                }
            ?></p>

            <div class="bc-plate-row">
                <?php echo bumperchat_plate_face_html($chosen ?: ['code' => 'NL', 'strip' => 'NL', 'examples' => [['plate' => 'AB-123-C']]], [
                    'value' => $plate,
                    'region' => $region,
                    'placeholder' => $example !== '' ? $example : t('bc.plate_ph'),
                ]); ?>
            </div>
            <noscript><p class="bc-meta"><?php echo t('bc.claim_nojs'); ?></p></noscript>
            <div class="bc-result" id="bcResult">
                <span class="bc-result-label"><?php echo t('bc.your_words'); ?></span>
                <output id="bcWords" class="bc-words<?php echo $plate_ok ? ' is-ok' : ($plate !== '' ? ' is-bad' : ''); ?>" for="bcPlate" aria-live="polite"><?php
                    if ($plate_ok) {
                        echo bc_e($show_words);
                    } elseif ($plate !== '' && $chosen) {
                        echo t('bc.plate_invalid', [
                            'country' => bumperchat_text($chosen['name']),
                            'example' => $example,
                        ]);
                    } else {
                        echo t('bc.js_type_plate');
                    }
                ?></output>
            </div>
            <p class="bc-suggest" id="bcSuggest" hidden></p>

            <?php if (count($user_wids) > 1): ?>
            <label class="bc-label" for="from_id"><?php echo t('pending.send_as'); ?></label>
            <select name="from_id" id="from_id" class="bc-input">
                <?php foreach ($user_wids as $row): ?>
                <option value="<?php echo (int) $row['id']; ?>"<?php echo (int) $row['id'] === $from_id ? ' selected' : ''; ?>><?php echo bc_e($row['threeword']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
            <input type="hidden" name="from_id" value="<?php echo (int) $from_id; ?>">
            <p class="bc-meta"><?php echo t('pending.send_as_one', ['words' => $from_words]); ?></p>
            <?php endif; ?>

            <label class="bc-label" for="bcPendingMsg"><?php echo t('pending.message_label'); ?></label>
            <textarea name="message" id="bcPendingMsg" class="bc-input bc-pending-msg" rows="5" required maxlength="5000" placeholder="<?php echo t('pending.message_ph'); ?>"><?php echo bc_e($draft); ?></textarea>

            <p class="bc-pending-remaining" id="bcPendingRemaining"><?php
                if ($plate_ok) {
                    echo t('pending.remaining', ['n' => (string) $remaining, 'max' => (string) pending_max_per_plate()]);
                } else {
                    echo t('pending.remaining_hint', ['max' => (string) pending_max_per_plate(), 'days' => (string) pending_ttl_days()]);
                }
            ?></p>

            <button class="btn btn-primary bc-next" type="submit" id="bcNext"><?php echo t('pending.send'); ?></button>
            <?php echo bumperchat_csrf_field(); ?>
            <input type="hidden" name="action_token" value="<?php echo bc_e($action_token); ?>">
            <input type="hidden" name="words" value="<?php echo bc_e($show_words); ?>">
        </form>
        <?php endif; ?>

        <p class="bc-back"><a href="chat.php"><?php echo t('pending.back_chats'); ?></a> · <a href="index.php"><?php echo t('bc.back_home'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
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
]); ?>
<script>
    window.BC_LANG = <?php echo json_encode(current_lang()); ?>;
    window.BC_FORMATS = <?php echo json_encode($formats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
    window.BC_CLAIM_FORM = true;
    window.BC_PENDING_FORM = true;
    window.BC_PENDING_REMAINING = <?php echo $already ? 0 : (int) $remaining; ?>;
    window.BC_DEFAULT_COUNTRY = <?php echo json_encode($selected); ?>;
</script>
<script src="../js/bumperchat.js?v=8"></script>
</body>
</html>
