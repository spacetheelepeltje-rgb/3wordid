<?php


require_once 'php/functions.php';
require_once 'login/config.php';

$client_ip = get_client_ip();
error_log('index page ip ' . $client_ip . ' on ' . check_mobile());

if (isset($_GET['hash'])) {
    header('Location: 3wid_show_qr.php?hash=' . rawurlencode($_GET['hash']));
    exit;
}

db_3wid_log_ip($client_ip);

$data = current_user_from_session($conn);

$image_url = $data['picture'] ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$loginUrl = '/login/options.php';

if (isset($_GET['message'])) {
    $helpertext = htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8');
} else {
    $helpertext = t_raw('home.helper_x');
}

if (check_mobile() === 'mobile') {
    $helpertext = t('home.helper_mobile');
}

if (isset($_GET['threewords'])) {
    $threewords = sanitizeInput($_GET['threewords']);
    header('Location: 3wid_forward.php?threewords=' . rawurlencode($threewords));
    exit;
}

$threewords = '';
$db_3wordid_list = db_3wordid_list_recent();

// Homepage list of public 3WordIDs. Easy to edit:
// - $featured_threewords: shown first, in this order (only if the 3WordID is public and enabled).
// - $hidden_threewords: never shown on the homepage (test or private-looking entries).
// - Every other public 3WordID sits behind the "See more" button.
$featured_threewords = [
    'our delicious menu',
    'davinci mona lisa',
    'fondation louis vuitton',
    'stock growth calculator',
    'student debt calculator',
    'italian verb list',
    'guitar fretboard trainer',
    'latte cafe wifi',
];
$hidden_threewords = [
    'she is pretty',
    'king of injustice',
    'demo of notification',
    'my personal notification',
    'message is external',
    'greencheck messages form',
];

$by_words = [];
foreach ($db_3wordid_list as $item) {
    $by_words[strtolower(trim($item['threeword']))] = $item;
}
$featured_list = [];
foreach ($featured_threewords as $w) {
    if (isset($by_words[$w])) {
        $featured_list[] = $by_words[$w];
        unset($by_words[$w]);
    }
}
foreach ($hidden_threewords as $w) {
    unset($by_words[$w]);
}
$more_list = array_values($by_words);


 
 $loginUrl ='/login/options.php';
 $helpertext ='';
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('home.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="description" content="<?php echo t('home.meta_desc'); ?>">
    <?php echo lang_head_tags(); ?>
    <meta property="og:locale" content="<?php echo i18n_og_locale(current_lang()); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="3WordID">
    <meta property="og:title" content="<?php echo t('home.og_title'); ?>">
    <meta property="og:description" content="<?php echo t('home.og_desc'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars(lang_url_absolute(current_lang()), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="https://3wordid.com/img/og-3wordid.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo t('home.og_title'); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@climatebabes">
    <meta name="twitter:title" content="<?php echo t('home.og_title'); ?>">
    <meta name="twitter:description" content="<?php echo t('home.og_desc'); ?>">
    <meta name="twitter:image" content="https://3wordid.com/img/og-3wordid.png">
    <link rel="stylesheet" href="css/newstyle.css">
    <link rel="stylesheet" href="css/home.css">
    <style>
        .lock.is-found {
            color: #16a34a;
            background: #ecfdf3;
        }
        .lock.is-disabled {
            color: #d97706;
            background: #fff7ed;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url;?>">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        <nav class="nav-links">
              <a href="plans.php"><?php echo t('nav.plans'); ?></a> <a href="terms.php"><?php echo t('nav.terms'); ?></a> <a href="https://x.com/climatebabes/status/1921113933592584660"><?php echo t('nav.docs'); ?></a> <a href="https://github.com/spacetheelepeltje-rgb/3wordid"><?php echo t('nav.github'); ?></a>               
        </nav>
        
        <div class="header-actions" id="userContainer">
            <?php echo lang_switcher(); ?>
            <a href="<?php echo $loginUrl; ?>" class="btn btn-primary"><?php echo t('nav.login'); ?></a>
            <!-- <a href="<?php echo $loginUrl; ?>" class="login-icon" title="<?php echo t('nav.login_title'); ?>">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
                <path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
                <polyline points="8 7 13 12 8 17"></polyline>
                <line x1="13" y1="12" x2="1" y2="12"></line>
              </svg> -->
            </a>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="eyebrow"><?php echo t_raw('home.eyebrow'); ?></div>
            <h1><?php echo t_raw('home.h1'); ?></h1>
            <p class="subhead"><?php echo t('home.subhead'); ?></p>
            <div class="helper-note" id="helperText"><?php echo $helpertext; ?></div>

            <form id="searchform" name="searchform" action="3wid_forward.php" method="POST">
                <div class="search-shell">
                    <div class="lock" id="searchLock" aria-hidden="true">
                        <svg id="lockIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path id="lockShackle" d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <input id="threewords" name="threewords" type="text" class="search-bar" placeholder="<?php echo t('common.placeholder_words'); ?>" value="<?= htmlspecialchars($threewords); ?>" autocomplete="off">
                    <button type="submit" id="submitBtn"><?php echo t('home.lookup'); ?></button>
                </div>
                <div class="example-row"><?php echo t('home.try_label'); ?> <button type="button" id="tryExample" class="example-btn" data-words="davinci mona lisa"><?php echo t('home.try_btn'); ?></button></div>
                <button type="button" id="createBtn" class="create-link" data-share-url="<?php echo $loginUrl; ?>"><?php echo t('home.create'); ?></button>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </form>
        </section>

        <section class="trust" aria-label="<?php echo t('home.trust_aria'); ?>">
            <div class="trust-item"><?php echo t_raw('home.trust1'); ?></div>
            <div class="trust-item"><?php echo t_raw('home.trust2'); ?></div>
            <div class="trust-item"><?php echo t_raw('home.trust3'); ?></div>
        </section>

        <section class="how" id="how-it-works">
            <h2 class="section-title"><?php echo t('home.how_title'); ?></h2>
            <div class="steps">
                <div class="step">
                    <div class="step-num">1</div>
                    <h3><?php echo t('home.step1_title'); ?></h3>
                    <p><?php echo t_raw('home.step1_text'); ?></p>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <h3><?php echo t('home.step2_title'); ?></h3>
                    <p><?php echo t('home.step2_text'); ?></p>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <h3><?php echo t('home.step3_title'); ?></h3>
                    <p><?php echo t('home.step3_text'); ?></p>
                </div>
            </div>
        </section>

        <section class="cards" id="use-cases">
            <article class="card">
                <div class="card-kicker"><?php echo t('home.card1_kicker'); ?></div>
                <h3><?php echo t('home.card1_title'); ?></h3>
                <p><?php echo t('home.card1_text'); ?></p>
                <div class="card-example"><?php echo t('home.example'); ?> <code>davinci mona lisa</code></div>
            </article>
            <article class="card">
                <div class="card-kicker"><?php echo t('home.card2_kicker'); ?></div>
                <h3><?php echo t('home.card2_title'); ?></h3>
                <p><?php echo t('home.card2_text'); ?></p>
                <div class="card-example"><?php echo t('home.example'); ?> <code>our delicious menu</code></div>
            </article>
            <article class="card">
                <div class="card-kicker"><?php echo t('home.card3_kicker'); ?></div>
                <h3><?php echo t('home.card3_title'); ?></h3>
                <p><?php echo t('home.card3_text'); ?></p>
            </article>
        </section>

        <section class="recent" id="public-3wordids">
            <h2><?php echo t('home.public_title'); ?></h2>
            <div class="recent-list" id="recentList">
<?php
                foreach ($featured_list as $db_3wordid_item) {
                ?>
                <div class="recent-row">
                    <a href="<?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <span class="views"><?php echo t_n('home.views', (int)$db_3wordid_item['views']); ?></span>
                </div>
                <?php
                }
                foreach ($more_list as $db_3wordid_item) {
                ?>
                <div class="recent-row recent-more">
                    <a href="<?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <span class="views"><?php echo t_n('home.views', (int)$db_3wordid_item['views']); ?></span>
                </div>
                <?php
                }
?>
            </div>
<?php if (count($more_list) > 0) { ?>
            <button type="button" class="recent-toggle" id="recentToggle" aria-expanded="false" aria-controls="recentList"><?php echo t('home.see_more'); ?></button>
<?php } ?>
        </section>

        <section class="faq" id="faq">
            <h2 class="section-title"><?php echo t('home.faq_title'); ?></h2>
            <details>
                <summary><?php echo t('home.faq1_q'); ?></summary>
                <p><?php echo t_raw('home.faq1_a'); ?></p>
            </details>
            <details>
                <summary><?php echo t('home.faq2_q'); ?></summary>
                <p><?php echo t_raw('home.faq2_a'); ?></p>
            </details>
            <details>
                <summary><?php echo t('home.faq3_q'); ?></summary>
                <p><?php echo t_raw('home.faq3_a'); ?></p>
            </details>
        </section>

    </main>
    <footer class="site-footer">
        <?php echo site_copyright(); ?>
    </footer>
</body>
<?php echo i18n_js([
    'exists'    => 'home.js_exists',
    'disabled'  => 'home.js_disabled',
    'missing'   => 'home.js_missing',
    'see_more'  => 'home.see_more',
    'show_less' => 'home.show_less',
]); ?>
<script src="js/3wid_index_2.js"></script>
<script>
    function handleGoogleLogin() {
        //window.location.href = '<?php echo $loginUrl; ?>';
    }

    document.querySelector('.login-icon')?.addEventListener('click', function(e) {
        e.preventDefault();
        handleGoogleLogin();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const userPortrait = document.getElementById('userPortrait');
        if (userPortrait && '<?php echo $image_url; ?>') {
            userPortrait.style.display = 'block';
        }
    });
    
    document.getElementById("tryExample").addEventListener("click", function() {
      var input = document.getElementById("threewords");
      input.value = this.getAttribute("data-words");
      input.focus();
      if (window.jQuery) { window.jQuery(input).trigger("keyup"); }
    });

    var recentToggle = document.getElementById("recentToggle");
    if (recentToggle) {
      recentToggle.addEventListener("click", function() {
        var list = document.getElementById("recentList");
        var open = list.classList.toggle("is-expanded");
        this.textContent = open ? window.T3.show_less : window.T3.see_more;
        this.setAttribute("aria-expanded", open ? "true" : "false");
      });
    }

    document.getElementById("threewords").addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
      event.preventDefault();
      document.getElementById("searchform").submit();
    }
  });
</script>    
</html>
