<?php
// Bumperchat claim: choose the country and type the plate on one page.
// The country's rules stay visible. The plate field is formatted as the user
// types, validated live (and again on the server), and Next stays off until
// the plate fits. A logged-in user then claims immediately; otherwise they
// go to claim_register.php. Works without JavaScript (Next is then always
// available and the server validates).

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$user = current_user_from_session($conn);

// "Try another plate": drop the stored claim and show an empty form.
if (isset($_GET['new'])) {
    bumperchat_claim_state_clear();
    header('Location: claim.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$formats = bumperchat_formats();
$countries = $formats['countries'];
$default_country = bumperchat_remembered_country();
$state = bumperchat_claim_state();

$prefill = ['country' => '', 'region' => '', 'plate' => '', 'words' => ''];
$raw_words = (string) ($_POST['words'] ?? $_GET['words'] ?? $_GET['threewords'] ?? ($state['words'] ?? ''));
if ($raw_words !== '') {
    $prefill = bumperchat_prefill_from_words($raw_words);
}

$country_explicit = strtoupper(trim((string) ($_POST['country'] ?? $_GET['country'] ?? ''))) !== '';
$selected = strtoupper(trim((string) ($_POST['country'] ?? $_GET['country'] ?? '')));
if ($selected === '' && $prefill['country'] !== '') {
    $selected = $prefill['country'];
}
if ($selected === '' && !empty($state['country'])) {
    $selected = strtoupper((string) $state['country']);
}
if ($selected === '') {
    $selected = $default_country;
}
$chosen = bumperchat_country($selected);
if (!$chosen) {
    $selected = $default_country;
    $chosen = bumperchat_country($selected);
}
if ($country_explicit || !empty($state['country']) || $prefill['country'] !== '') {
    bumperchat_remember_country($selected); // same country on the home converter, chat view and leave-a-message
}

$region = strtoupper(trim((string) ($_POST['region'] ?? $_GET['region'] ?? '')));
if ($region === '' && $prefill['region'] !== '' && $prefill['country'] === $selected) {
    $region = $prefill['region'];
}
if ($region === '' && !empty($state['region']) && (string) ($state['country'] ?? '') === $selected) {
    $region = strtoupper((string) $state['region']);
}
if ($chosen && !empty($chosen['regions']) && $region !== '' && !isset($chosen['regions'][$region])) {
    $region = '';
}

$plate = (string) ($_POST['plate'] ?? $_GET['plate'] ?? '');
if ($plate === '' && $prefill['plate'] !== '') {
    $plate = $prefill['plate'];
}
if ($plate === '' && !empty($state['plate']) && (string) ($state['country'] ?? '') === $selected) {
    $plate = (string) $state['plate'];
}
if ($chosen && $plate !== '') {
    $plate = bumperchat_format_plate($chosen, $plate, $region);
}

$error = '';
$check = null;
$check_error = '';
$words = '';

$is_next = ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['do'] ?? 'next') === 'next');

if (!$country_explicit && !$is_next && $plate !== '' && function_exists('bumperchat_pick_country_for_plate')) {
    $pick = bumperchat_pick_country_for_plate($plate, $region, $selected);
    if ($pick !== '' && $pick !== $selected) {
        $selected = $pick;
        $chosen = bumperchat_country($selected);
        if ($chosen && empty($chosen['regions'])) {
            $region = '';
        }
        if ($chosen && $plate !== '') {
            $plate = bumperchat_format_plate($chosen, $plate, $region);
        }
    }
}

if ($is_next) {
    if (!bumperchat_csrf_ok()) {
        $error = 'common.session_expired';
    } elseif (!$chosen) {
        $error = 'bc.err_country';
    } else {
        $words = bumperchat_plate_to_words($chosen, $plate, $region);
        if ($words === '') {
            $error = 'bc.plate_invalid';
        } elseif (!empty($chosen['regions']) && $region === '') {
            $error = 'bc.err_region';
        } elseif (!exists_check_allowed()) {
            $check_error = 'bc.err_limit';
        } else {
            bumperchat_claim_state_set([
                'country' => $chosen['code'],
                'region'  => $region,
                'plate'   => $plate,
                'words'   => $words,
            ]);
            $status = db_3wordid_exists_status($words);
            $exists = $status !== null;
            if ($exists && $user) {
                $own = db_3wordid_get_threeword($words);
                if (is_array($own) && (int) ($own['user_id'] ?? 0) === (int) $user['id']) {
                    if (plate_billing_requires_payment($own)) {
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
                bumperchat_try_claim_and_redirect($user, $words);
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

$show_words = ($chosen && $plate !== '') ? bumperchat_plate_to_words($chosen, $plate, $region) : '';
$plate_ok = $show_words !== '';
$example = $chosen ? bumperchat_plate_example($chosen) : '';

// Login return and prefilled GET: show the taken card, or send the owner to chat/pay.
if ($check === null && $error === '' && $plate_ok && !$is_next && function_exists('db_3wordid_exists_status')) {
    $status = db_3wordid_exists_status($show_words);
    if ($status !== null) {
        if ($user) {
            $own = db_3wordid_get_threeword($show_words);
            if (is_array($own) && (int) ($own['user_id'] ?? 0) === (int) $user['id']) {
                if (plate_billing_requires_payment($own)) {
                    header('Location: pay.php?words=' . rawurlencode($show_words));
                    exit;
                }
                bumperchat_mark_session($show_words);
                bumperchat_claim_state_clear();
                header('Location: chat.php');
                exit;
            }
        }
        $check = [
            'exists' => true,
            'paying' => (bool) ($status['paying'] ?? false),
            'plate'  => car_plate_pattern($show_words) ? car_plate_known($show_words) : null,
            'digits' => (bool) preg_match('/[0-9]/', $show_words),
        ];
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
    <title><?php echo t('bc.claim_page_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=10">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.claim_page_h1'); ?></h1>
        <p class="bc-lede"><?php echo t('bc.claim_page_sub'); ?></p>

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
        <?php if ($check_error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($check_error); ?></div>
        <?php endif; ?>

        <form class="bc-card bc-claim-form" method="post" action="claim.php" id="bcClaimForm" autocomplete="off">
            <label class="bc-label" for="bcCountry"><?php echo t('bc.country'); ?></label>
            <div class="bc-inline">
                <select id="bcCountry" name="country" class="bc-input">
                    <option value=""><?php echo t('bc.choose_country_ph'); ?></option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?php echo bc_e($c['code']); ?>"<?php echo $c['code'] === $selected ? ' selected' : ''; ?>><?php echo bc_e(bumperchat_text($c['name'])); ?> (<?php echo bc_e($c['strip']); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-ghost" type="submit" name="do" value="show" id="bcShowCountry"><?php echo t('bc.show_rules'); ?></button>
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
                <button class="btn btn-primary bc-next" type="submit" name="do" value="next" id="bcNext"><?php echo t('bc.next'); ?></button>
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
            <div id="bcTakenActions" class="bc-taken-actions" hidden></div>
            <?php echo bumperchat_csrf_field(); ?>
        </form>

        <?php if ($check && !empty($check['exists'])): ?>
        <div class="bc-card bc-status is-taken" id="bcTakenCard" data-words="<?php echo bc_e($show_words); ?>" role="status">
            <h2><?php echo t('bc.status_taken', ['words' => $show_words]); ?></h2>
            <p><?php echo t('bc.status_taken_p'); ?></p>
            <?php echo bumperchat_taken_actions($show_words, ['logged_in' => (bool) $user]); ?>
        </div>
        <?php endif; ?>

        <p class="bc-back"><a href="index.php"><?php echo t('bc.back_home'); ?></a></p>
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
    'bc_taken'          => 'bc.status_taken',
    'bc_taken_p'        => 'bc.status_taken_p',
    'bc_send_to_plate'  => 'bc.send_to_plate',
    'bc_taken_login'    => 'bc.taken_login',
    'bc_dispute_link'   => 'bc.dispute_link',
    'bc_try_another'    => 'bc.try_another',
]); ?>
<script>
    window.BC_LANG = <?php echo json_encode(current_lang()); ?>;
    window.BC_FORMATS = <?php echo json_encode($formats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
    window.BC_CLAIM_FORM = true;
    window.BC_LOGGED_IN = <?php echo $user ? 'true' : 'false'; ?>;
    window.BC_DEFAULT_COUNTRY = <?php echo json_encode($default_country); ?>;
    window.BC_COUNTRY_EXPLICIT = <?php echo json_encode($country_explicit || $prefill['country'] !== '' || !empty($state['country'])); ?>;
</script>
<script src="../js/bumperchat.js?v=12"></script>
</body>
</html>
