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


 
 $loginUrl ='/login/options.php';
 $helpertext ='';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta http-equiv="refresh" content="60">
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
                <button type="button" id="createBtn" class="create-link" data-share-url="<?php echo $loginUrl; ?>">Create your own 3WordID →</button>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </form>
        </section>

        <section class="cards" id="use-cases">
            <article class="card">
                
                <h3>Three-word aliases</h3>
                <p>Link any three words to any URL. Share the words and you share the profile, file or resource. Easy to remember, easy to communicate.</p>
            </article>
            <article class="card">
                
                <h3>Three-word message box</h3>
                <p>Create a private message box linked to three words. The words can also be a number, an alias or a license plate.</p>
            </article>
            <article class="card">
               
                <h3>Better than QR codes</h3>
                <p>Use three words instead of QR codes, or create a QR code based on three words, which helps prevent tampering. Museums can get a cleaner look on their labels, and menu cards can just show the restaurant name.</p>
            </article>
        </section>

        <section class="recent" id="how-it-works">
            <h2>Currently public 3WordIDs</h2>
            <div class="recent-list">
<?php
                foreach($db_3wordid_list as $db_3wordid_item) {
                ?>
                <div class="recent-row">
                    <a href="<?php echo $db_3wordid_item['threeword']; ?>"><?php echo $db_3wordid_item['threeword']; ?></a>
                    <span class="views"><?php echo $db_3wordid_item['views']; ?> views</span>
                </div>
                <?php
                }
?>
            </div>
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
    
    document.getElementById("threewords").addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
      event.preventDefault();
      document.getElementById("searchform").submit();
    }
  });
</script>    
</html>
