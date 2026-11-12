<?php
// Bumperchat.com landing page (bumperchat.com redirects here): the 3WordID search box,
// onboarding steps and the "licence plate -> three words" lookup (rules in plate_formats.json).
// The search box is the homepage one (js/3wid_index_2.js, 3wid_exists.php, 3wid_forward.php).

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

$user = current_user_from_session($conn);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$formats = bumperchat_formats();
$countries = $formats['countries'];
// Default country in the converter: the visitor's language, else the Netherlands
$default_country = ['nl' => 'NL', 'fr' => 'FR', 'es' => 'ES'][current_lang()] ?? 'NL';

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('bc.title'); ?></title>
    <meta name="description" content="<?php echo t('bc.meta_desc'); ?>">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <?php echo lang_head_tags(); ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/home.css?v=2">
    <link rel="stylesheet" href="../css/bumperchat.css?v=1">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main>
        <section class="hero bc-hero">
            <div class="bc-banner" role="banner"><?php echo bumperchat_car_icon(22); ?><span>Bumperchat.com</span></div>
            <h1><?php echo t('bc.h1'); ?></h1>
            <p class="subhead"><?php echo t('bc.subhead'); ?></p>
            <div class="helper-note" id="helperText"><?php echo t('bc.helper'); ?></div>

            <form id="searchform" name="searchform" action="../3wid_forward.php" method="POST">
                <div class="search-shell">
                    <div class="lock" id="searchLock" aria-hidden="true">
                        <svg id="lockIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path id="lockShackle" d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <svg class="car-ico" id="carIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M5 17H3v-5l2-5h14l2 5v5h-2"></path><path d="M3 12h18"></path><circle cx="7.5" cy="17" r="2"></circle><circle cx="16.5" cy="17" r="2"></circle><path d="M9.5 17h5"></path>
                        </svg>
                    </div>
                    <input id="threewords" name="threewords" type="text" class="search-bar" placeholder="<?php echo t('bc.placeholder'); ?>" autocomplete="off" aria-label="<?php echo t('bc.search_label'); ?>">
                    <button type="submit" id="submitBtn"><?php echo t('bc.lookup'); ?></button>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo bc_e($_SESSION['csrf_token']); ?>">
            </form>
            <div class="bc-hero-links">
                <a class="btn btn-ghost" id="bcClaimLink" href="claim.php"><?php echo t('bc.claim_link'); ?></a>
                <a class="bc-textlink" href="#formats"><?php echo t('bc.how_plate'); ?></a>
            </div>
        </section>

        <section class="how bc-how" id="how-it-works">
            <h2 class="section-title"><?php echo t('bc.steps_title'); ?></h2>
            <div class="steps bc-steps">
                <div class="step">
                    <div class="step-num">1</div>
                    <h3><?php echo t('bc.step1_title'); ?></h3>
                    <p><?php echo t_raw('bc.step1_text'); ?></p>
                    <a class="bc-step-link" href="#formats"><?php echo t('bc.step1_link'); ?></a>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <h3><?php echo t('bc.step2_title'); ?></h3>
                    <p><?php echo t('bc.step2_text'); ?></p>
                    <a class="bc-step-link" href="claim.php"><?php echo t('bc.step2_link'); ?></a>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <h3><?php echo t('bc.step3_title'); ?></h3>
                    <p><?php echo t('bc.step3_text'); ?></p>
                    <a class="bc-step-link" href="claim.php#login"><?php echo t('bc.step3_link'); ?></a>
                </div>
                <div class="step">
                    <div class="step-num">4</div>
                    <h3><?php echo t('bc.step4_title'); ?></h3>
                    <p><?php echo t('bc.step4_text'); ?></p>
                </div>
            </div>
        </section>

        <section class="bc-formats" id="formats">
            <h2 class="section-title"><?php echo t('bc.formats_title'); ?></h2>
            <p class="bc-lede"><?php echo t('bc.formats_lede'); ?></p>

            <div class="bc-card" id="bcConverter">
                <div class="bc-grid">
                    <div>
                        <label class="bc-label" for="bcCountry"><?php echo t('bc.country'); ?></label>
                        <select id="bcCountry" class="bc-input">
                            <?php foreach ($countries as $c): ?>
                            <option value="<?php echo bc_e($c['code']); ?>"<?php echo $c['code'] === $default_country ? ' selected' : ''; ?>><?php echo bc_e(bumperchat_text($c['name'])); ?> (<?php echo bc_e($c['strip']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="bcRegionWrap" hidden>
                        <label class="bc-label" for="bcRegion"><?php echo t('bc.region'); ?></label>
                        <select id="bcRegion" class="bc-input"></select>
                    </div>
                    <div>
                        <label class="bc-label" for="bcPlate"><?php echo t('bc.plate'); ?></label>
                        <input id="bcPlate" class="bc-input bc-plate" type="text" maxlength="20" autocomplete="off" spellcheck="false" placeholder="<?php echo t('bc.plate_ph'); ?>">
                    </div>
                </div>
                <p class="bc-rule" id="bcRule"></p>
                <div class="bc-result">
                    <span class="bc-result-label"><?php echo t('bc.your_words'); ?></span>
                    <output id="bcWords" class="bc-words" for="bcPlate" aria-live="polite"></output>
                    <a class="btn btn-primary" id="bcUse" href="claim.php" data-claim="claim.php" hidden><?php echo t('bc.use_words'); ?></a>
                </div>
            </div>

            <div class="bc-card bc-list-card">
                <label class="bc-label" for="bcFilter"><?php echo t('bc.filter'); ?></label>
                <input id="bcFilter" class="bc-input" type="search" autocomplete="off" placeholder="<?php echo t('bc.filter_ph'); ?>">
                <ul class="bc-format-list">
                    <?php foreach ($countries as $c):
                        $name = bumperchat_text($c['name']);
                        $search = strtolower($name . ' ' . ($c['name']['en'] ?? '') . ' ' . $c['code'] . ' ' . $c['strip']);
                    ?>
                    <li class="bc-format-row" data-search="<?php echo bc_e($search); ?>">
                        <div class="bc-format-head">
                            <span class="bc-strip"><?php echo bc_e($c['strip']); ?></span>
                            <strong><?php echo bc_e($name); ?></strong>
                            <code class="bc-format"><?php echo bc_e($c['format']); ?></code>
                            <?php if (!empty($c['uncertain'])): ?><span class="bc-pill" title="<?php echo t('bc.uncertain_title'); ?>"><?php echo t('bc.uncertain'); ?></span><?php endif; ?>
                            <button type="button" class="bc-pick" data-pick="<?php echo bc_e($c['code']); ?>"><?php echo t('bc.pick'); ?></button>
                        </div>
                        <p class="bc-format-rule"><?php echo bc_e(bumperchat_text($c['rule'])); ?></p>
                        <p class="bc-format-ex">
                            <?php foreach ($c['examples'] as $ex):
                                $parts = explode('|', $ex['plate'], 2);
                                $shown = count($parts) === 2 ? $parts[0] . ' · ' . $parts[1] : $ex['plate'];
                            ?>
                            <span class="bc-ex"><span class="bc-ex-plate"><?php echo bc_e($shown); ?></span> → <code><?php echo bc_e($ex['words']); ?></code></span>
                            <?php endforeach; ?>
                        </p>
                        <?php if (!empty($c['note'])): ?><p class="bc-format-note"><?php echo bc_e(bumperchat_text($c['note'])); ?></p><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <p class="bc-none" id="bcNone" hidden><?php echo t('bc.filter_none'); ?></p>
                <p class="bc-disclaimer"><?php echo t('bc.formats_disclaimer'); ?></p>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <span><a href="../terms.php"><?php echo t('nav.terms'); ?></a> · <a href="../index.php">3WordID.com</a></span>
        <?php echo site_copyright(); ?>
    </footer>

<?php echo i18n_js([
    'exists'        => 'bc.js_exists',
    'paying'        => 'home.js_paying',
    'plate'         => 'home.js_plate',
    'missing'       => 'bc.js_missing',
    'bc_example'    => 'bc.js_example',
    'bc_type_plate' => 'bc.js_type_plate',
    'bc_no_fit'     => 'bc.js_no_fit',
]); ?>
<script>
    window.T3_EXISTS_URL = '../3wid_exists.php';
    window.BC_LANG = <?php echo json_encode(current_lang()); ?>;
    window.BC_FORMATS = <?php echo json_encode($formats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="../js/3wid_index_2.js?v=5"></script>
<script src="../js/bumperchat.js?v=1"></script>
<script>
    // "Claim" link follows what is typed in the search box
    (function () {
        var input = document.getElementById('threewords');
        var link = document.getElementById('bcClaimLink');
        function sync() {
            var w = input.value.trim().replace(/\s+/g, ' ');
            link.setAttribute('href', w ? 'claim.php?words=' + encodeURIComponent(w) : 'claim.php');
        }
        input.addEventListener('input', sync);
        input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('searchform').submit(); }
        });
        sync();
    })();
</script>
</body>
</html>
