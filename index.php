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
    $helpertext = '<a href="https://x.com/climatebabes/status/1921113933592584660">What is this site? Explanation on X.com</a>';
}

if (check_mobile() === 'mobile') {
    $helpertext = 'Log in top right to create your own 3WordID';
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
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3WordID — Three words. Every destination.</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="description" content="Link any three words to a URL, a notification or a private message box. Easy to remember, easy to share. Three words. Every destination.">
    <link rel="canonical" href="https://3wordid.com/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="3WordID">
    <meta property="og:title" content="3WordID: Three words. Every destination.">
    <meta property="og:description" content="Link any three words to a URL, a notification or a private message box. Easy to remember, easy to share.">
    <meta property="og:url" content="https://3wordid.com/">
    <meta property="og:image" content="https://3wordid.com/img/og-3wordid.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="3WordID: Three words. Every destination.">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@climatebabes">
    <meta name="twitter:title" content="3WordID: Three words. Every destination.">
    <meta name="twitter:description" content="Link any three words to a URL, a notification or a private message box. Easy to remember, easy to share.">
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
              <a href="plans.php">Subscription Plans</a> <a href="terms.php">Terms</a> <a href="https://x.com/climatebabes/status/1921113933592584660">Docs on X.com</a> <a href="https://github.com/spacetheelepeltje-rgb/3wordid">Open Source on Github</a>               
        </nav>
        
        <div class="header-actions" id="userContainer">
            <a href="<?php echo $loginUrl; ?>" class="btn btn-primary">Log in/Create ID</a>
            <!-- <a href="<?php echo $loginUrl; ?>" class="login-icon" title="Log in (not all functions work on mobile devices)">
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
            <div class="eyebrow">HUMAN-READABLE IDENTIFIERS <b>BETA version</b></div>
            <h1>Three words<br>Every destination</h1>
            <p class="subhead">Look up a URL, notification, or private message box using any three-word ID. Easy to communicate. Easy to remember.</p>
            <div class="helper-note" id="helperText"><?php echo $helpertext; ?></div>

            <form id="searchform" name="searchform" action="3wid_forward.php" method="POST">
                <div class="search-shell">
                    <div class="lock" id="searchLock" aria-hidden="true">
                        <svg id="lockIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path id="lockShackle" d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <input id="threewords" name="threewords" type="text" class="search-bar" placeholder="first  ·  second  ·  third" value="<?= htmlspecialchars($threewords); ?>" autocomplete="off">
                    <button type="submit" id="submitBtn">Lookup</button>
                </div>
                <div class="search-hints">Try one of the three-word IDs listed below</div>
                <div class="example-row">Try an example: <button type="button" id="tryExample" class="example-btn" data-words="davinci mona lisa">Try &ldquo;davinci mona lisa&rdquo;</button></div>
                <button type="button" id="createBtn" class="create-link" data-share-url="<?php echo $loginUrl; ?>">Create your own 3WordID →</button>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </form>
        </section>

        <section class="trust" aria-label="Why 3WordID">
            <div class="trust-item"><b>Free to start</b></div>
            <div class="trust-item"><b>No personal data needed</b> to look up a 3WordID</div>
            <div class="trust-item"><b>Open source</b> &middot; <a href="https://github.com/spacetheelepeltje-rgb/3wordid">code on GitHub</a></div>
        </section>

        <section class="how" id="how-it-works">
            <h2 class="section-title">How it works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-num">1</div>
                    <h3>Pick three words</h3>
                    <p>Choose any three words, for example <code>our delicious menu</code>.</p>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <h3>Link them</h3>
                    <p>Point them to a URL, a notification or a private message box. You can change the destination later.</p>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <h3>Share the words</h3>
                    <p>Say them, print them or type them. Anyone can look them up here.</p>
                </div>
            </div>
        </section>

        <section class="cards" id="use-cases">
            <article class="card">
                <div class="card-kicker">Museums</div>
                <h3>Labels without QR codes</h3>
                <p>Put three words on an exhibit label instead of a QR code. The label stays clean, visitors can read the words out loud, and you can still print a QR code that holds just the three words.</p>
                <div class="card-example">Example: <code>davinci mona lisa</code></div>
            </article>
            <article class="card">
                <div class="card-kicker">Restaurants</div>
                <h3>Your menu in three words</h3>
                <p>Print your restaurant's words on the table or the door. Guests type them and see today's menu. Change the link when the menu changes, the words stay the same.</p>
                <div class="card-example">Example: <code>our delicious menu</code></div>
            </article>
            <article class="card">
                <div class="card-kicker">Privacy</div>
                <h3>A private message box</h3>
                <p>Let people reach you without sharing your email address or phone number. Share three words and they can leave you a message. Messages stay in your account, and no personal data is needed to send one.</p>
            </article>
        </section>

        <section class="recent" id="public-3wordids">
            <h2>Currently public 3WordIDs</h2>
            <div class="recent-list" id="recentList">
<?php
                foreach ($featured_list as $db_3wordid_item) {
                ?>
                <div class="recent-row">
                    <a href="<?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <span class="views"><?php echo (int)$db_3wordid_item['views']; ?> views</span>
                </div>
                <?php
                }
                foreach ($more_list as $db_3wordid_item) {
                ?>
                <div class="recent-row recent-more">
                    <a href="<?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($db_3wordid_item['threeword'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <span class="views"><?php echo (int)$db_3wordid_item['views']; ?> views</span>
                </div>
                <?php
                }
?>
            </div>
<?php if (count($more_list) > 0) { ?>
            <button type="button" class="recent-toggle" id="recentToggle" aria-expanded="false" aria-controls="recentList">See more</button>
<?php } ?>
        </section>

        <section class="faq" id="faq">
            <h2 class="section-title">Questions</h2>
            <details>
                <summary>What if someone takes my words?</summary>
                <p>Each combination of three words belongs to one account at a time, first come, first served. Once you have registered yours, only you can change where they point. If your favourite words are already taken, try a variation.</p>
            </details>
            <details>
                <summary>Is it free?</summary>
                <p>Looking up a 3WordID is free and needs no account. Creating your own is free to start. If you need more 3WordIDs, see the <a href="plans.php">subscription plans</a>.</p>
            </details>
            <details>
                <summary>What's stored?</summary>
                <p>Your email address is stored only as a one-way hash, not as readable text. Messages sent through a message box and the IP addresses they come from are logged and retained, so the service can be held accountable and lawful requests can be answered. Accounts are deactivated, never deleted, and their data and logs are kept. Details are in the <a href="terms.php">terms</a>.</p>
            </details>
        </section>

    </main>
    <footer class="site-footer">
    </footer>
</body>
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
        this.textContent = open ? "Show less" : "See more";
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
