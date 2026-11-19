<?php
// Bumperchat claim flow, step 2: enter the plate number and validate it
// against the country chosen in step 1 (same rules as js/bumperchat.js).

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$user = current_user_from_session($conn);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$state = bumperchat_claim_state();
$country_code = strtoupper(trim((string) (
    $_POST['country']
    ?? $_GET['country']
    ?? ($state['country'] ?? '')
)));
$country = bumperchat_country($country_code);
if (!$country) {
    header('Location: claim.php');
    exit;
}

$region = strtoupper(trim((string) (
    $_POST['region']
    ?? $_GET['region']
    ?? ($state['region'] ?? '')
)));
if (!empty($country['regions']) && $region !== '' && !isset($country['regions'][$region])) {
    $region = '';
}

$plate = (string) (
    $_POST['plate']
    ?? $_GET['plate']
    ?? ($state['plate'] ?? '')
);

$error = '';
$check = null;
$check_error = '';
$words = '';
$owned = false;
$owned_wid = null;
$owned_expired = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bumperchat_csrf_ok()) {
        $error = 'common.session_expired';
    } else {
        $words = bumperchat_plate_to_words($country, $plate, $region);
        if ($words === '') {
            $error = 'bc.plate_invalid';
        } elseif (!empty($country['regions']) && $region === '') {
            $error = 'bc.err_region';
        } elseif (!exists_check_allowed()) {
            $check_error = 'bc.err_limit';
        } else {
            bumperchat_claim_state_set([
                'country' => $country['code'],
                'region'  => $region,
                'plate'   => $plate,
                'words'   => $words,
            ]);
            $status = db_3wordid_exists_status($words);
            $exists = $status !== null;
            if ($exists && $user) {
                $own = db_3wordid_get_threeword($words);
                if (is_array($own) && (int) ($own['user_id'] ?? 0) === (int) $user['id']) {
                    $owned = true;
                    $owned_wid = $own;
                    $owned_expired = plate_billing_requires_payment($owned_wid);
                    if ($owned_expired) {
                        header('Location: pay.php?words=' . rawurlencode($words));
                        exit;
                    }
                    bumperchat_mark_session($words);
                    bumperchat_claim_state_clear();
                    header('Location: chat.php');
                    exit;
                }
            }
            if (!$exists && $user) {
                // Logged-in users skip registration and claim immediately (phone optional, omitted here).
                bumperchat_try_claim_and_redirect($user, $words);
                // If claim failed, fall through and show the error.
                if (!empty($GLOBALS['error'])) {
                    $error = (string) $GLOBALS['error'];
                }
            } elseif (!$exists) {
                header('Location: claim_register.php');
                exit;
            } else {
                $check = [
                    'exists' => true,
                    'paying' => (bool) ($status['paying'] ?? false),
                    'plate'  => car_plate_pattern($words) ? car_plate_known($words) : null,
                    'digits' => (bool) preg_match('/[0-9]/', $words),
                ];
            }
        }
    }
}

$show_words = $words !== '' ? $words : bumperchat_plate_to_words($country, $plate, $region);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('bc.plate_step_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=3">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <?php echo bumperchat_flow_steps(2); ?>
        <h1 class="bc-h1"><?php echo t('bc.plate_step_h1'); ?></h1>
        <p class="bc-lede"><?php echo t('bc.plate_step_sub', ['country' => bumperchat_text($country['name'])]); ?></p>

        <div class="bc-card bc-country-rules is-on">
            <?php echo bumperchat_country_rules_html($country); ?>
        </div>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo $error === 'bc.plate_invalid'
                ? t('bc.plate_invalid', ['country' => bumperchat_text($country['name'])])
                : t($error); ?></div>
        <?php endif; ?>
        <?php if ($check_error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($check_error); ?></div>
        <?php endif; ?>

        <form class="bc-card" method="post" action="claim_plate.php" id="bcClaimPlate" autocomplete="off">
            <input type="hidden" name="country" value="<?php echo bc_e($country['code']); ?>">
            <?php if (!empty($country['regions'])): ?>
                <label class="bc-label" for="bcRegion"><?php echo t('bc.region'); ?></label>
                <select id="bcRegion" name="region" class="bc-input" required>
                    <option value=""><?php echo t('bc.choose_region_ph'); ?></option>
                    <?php foreach ($country['regions'] as $code => $label): ?>
                    <option value="<?php echo bc_e($code); ?>"<?php echo $code === $region ? ' selected' : ''; ?>><?php echo bc_e($code . ' · ' . $label); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <label class="bc-label" for="bcPlate"><?php echo t('bc.plate'); ?></label>
            <input id="bcPlate" name="plate" class="bc-input bc-plate" type="text" maxlength="20" required autocomplete="off" spellcheck="false" placeholder="<?php echo t('bc.plate_ph'); ?>" value="<?php echo bc_e($plate); ?>">
            <p class="bc-meta"><?php echo t('bc.plate_step_hint'); ?></p>
            <div class="bc-result">
                <span class="bc-result-label"><?php echo t('bc.your_words'); ?></span>
                <output id="bcWords" class="bc-words<?php echo $show_words !== '' ? ' is-ok' : ''; ?>" for="bcPlate" aria-live="polite"><?php
                    echo $show_words !== '' ? bc_e($show_words) : t('bc.js_type_plate');
                ?></output>
            </div>
            <?php echo bumperchat_csrf_field(); ?>
            <button class="btn btn-primary bc-full" type="submit" id="bcPlateContinue"><?php echo t('bc.check'); ?></button>
        </form>

        <?php if ($check && !empty($check['exists'])): ?>
        <div class="bc-card bc-status is-taken" role="status">
            <h2><?php echo t('bc.status_taken', ['words' => $show_words]); ?></h2>
            <p><?php echo t('bc.status_taken_p'); ?></p>
            <a class="btn btn-ghost" href="../3wid_forward.php?threewords=<?php echo rawurlencode($show_words); ?>"><?php echo t('bc.send_message'); ?></a>
            <ul class="bc-facts">
                <?php if ($check['plate'] === true): ?><li class="ok"><?php echo t('bc.fact_rdw_yes'); ?></li><?php endif; ?>
                <?php if ($check['plate'] === false): ?><li><?php echo t('bc.fact_rdw_no'); ?></li><?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <p class="bc-back"><a href="claim.php"><?php echo t('bc.back_country'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
<?php echo i18n_js([
    'bc_example'    => 'bc.js_example',
    'bc_type_plate' => 'bc.js_type_plate',
    'bc_no_fit'     => 'bc.js_no_fit',
]); ?>
<script>
    window.BC_LANG = <?php echo json_encode(current_lang()); ?>;
    window.BC_FORMATS = <?php echo json_encode(['countries' => [$country]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
    window.BC_CLAIM_PLATE = true;
</script>
<script src="../js/bumperchat.js?v=2"></script>
</body>
</html>
