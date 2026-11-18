<?php
// Bumperchat claim flow, step 1: choose the country of the plate.
// On choice the formatting rules and examples for that country are shown
// (client-side, and also after a GET submit so it works without JavaScript).
// Continue stores the country in the session and goes to claim_plate.php.

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

$formats = bumperchat_formats();
$countries = $formats['countries'];
$default_country = ['nl' => 'NL', 'fr' => 'FR', 'es' => 'ES'][current_lang()] ?? 'NL';

$state = bumperchat_claim_state();
$selected = strtoupper(trim((string) (
    $_POST['country']
    ?? $_GET['country']
    ?? ($state['country'] ?? '')
)));
if ($selected === '') {
    $selected = $default_country;
}
$chosen = bumperchat_country($selected);
if (!$chosen) {
    $selected = '';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bumperchat_csrf_ok()) {
        $error = 'common.session_expired';
    } else {
        $posted = bumperchat_country((string) ($_POST['country'] ?? ''));
        if (!$posted) {
            $error = 'bc.err_country';
        } else {
            $region = strtoupper(trim((string) ($_POST['region'] ?? '')));
            if (!empty($posted['regions']) && $region !== '' && !isset($posted['regions'][$region])) {
                $region = '';
            }
            bumperchat_claim_state_set([
                'country' => $posted['code'],
                'region'  => $region,
                // A new country means the previous plate/words may no longer apply.
                'plate'   => '',
                'words'   => '',
            ]);
            header('Location: claim_plate.php');
            exit;
        }
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
    <title><?php echo t('bc.choose_country_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=3">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <?php echo bumperchat_flow_steps(1); ?>
        <h1 class="bc-h1"><?php echo t('bc.choose_country_h1'); ?></h1>
        <p class="bc-lede"><?php echo t('bc.choose_country_sub'); ?></p>

        <?php if ($error !== ''): ?>
            <div class="bc-flash" role="alert"><?php echo t($error); ?></div>
        <?php endif; ?>

        <form class="bc-card" id="bcCountryForm" method="get" action="claim.php">
            <label class="bc-label" for="bcCountry"><?php echo t('bc.country'); ?></label>
            <div class="bc-inline">
                <select id="bcCountry" name="country" class="bc-input">
                    <option value=""><?php echo t('bc.choose_country_ph'); ?></option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?php echo bc_e($c['code']); ?>"<?php echo $c['code'] === $selected ? ' selected' : ''; ?>><?php echo bc_e(bumperchat_text($c['name'])); ?> (<?php echo bc_e($c['strip']); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-ghost" type="submit" id="bcShowRules"><?php echo t('bc.show_rules'); ?></button>
            </div>
            <p class="bc-meta"><?php echo t('bc.choose_country_hint'); ?></p>
        </form>

        <?php foreach ($countries as $c):
            $is_on = ($c['code'] === $selected);
        ?>
        <div class="bc-card bc-country-rules<?php echo $is_on ? ' is-on' : ''; ?>" data-country="<?php echo bc_e($c['code']); ?>"<?php echo $is_on ? '' : ' hidden'; ?>>
            <?php echo bumperchat_country_rules_html($c); ?>
            <?php if (!empty($c['regions'])): ?>
                <p class="bc-meta"><?php echo t('bc.region_later'); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <form class="bc-continue" method="post" action="claim.php" id="bcCountryContinue">
            <input type="hidden" name="country" id="bcCountryHidden" value="<?php echo bc_e($selected); ?>">
            <?php echo bumperchat_csrf_field(); ?>
            <button class="btn btn-primary bc-full" type="submit" id="bcContinue"<?php echo $chosen ? '' : ' disabled'; ?>><?php echo t('bc.continue'); ?></button>
        </form>

        <p class="bc-back"><a href="index.php"><?php echo t('bc.back_home'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php#bumperchat"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>
<script>
(function () {
    var select = document.getElementById('bcCountry');
    var hidden = document.getElementById('bcCountryHidden');
    var cont = document.getElementById('bcContinue');
    var showBtn = document.getElementById('bcShowRules');
    if (showBtn) showBtn.hidden = true; // JS is available: rules appear on change
    function reveal() {
        var code = select ? select.value : '';
        if (hidden) hidden.value = code;
        if (cont) cont.disabled = !code;
        document.querySelectorAll('.bc-country-rules').forEach(function (el) {
            var on = el.getAttribute('data-country') === code && code !== '';
            el.hidden = !on;
            el.classList.toggle('is-on', on);
        });
    }
    if (select) {
        select.addEventListener('change', reveal);
        reveal();
    }
})();
</script>
</body>
</html>
