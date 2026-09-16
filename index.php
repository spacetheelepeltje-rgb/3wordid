<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  //require_once 'cookie_consent.php';
  
  //db_3wid_log_update('banaan');
 
  $client_ip = get_client_ip();
  
  error_log('index page ip ' . $client_ip . ' on ' . check_mobile());
  
  if(isset($_GET["hash"])) {
    header('location:3wid_show_qr.php?hash=' . $_GET["hash"]);
  }
 
    session_unset();
    session_destroy();
    session_start();

    db_3wid_log_ip($client_ip);	

    $loginUrl = $client->createAuthUrl();
    
    $data = check_auth();
    
    if($data) {
        $image_url = $data["picture"];
    } else {
        $image_url = "";
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    if(isset($_GET["message"])) {
        $helpertext = $_GET["message"]; 			
    } else {
        $helpertext = '<a href="https://x.com/climatebabes/status/1921113933592584660">What is this site? Explanation on X.com</a>';
    }
    
  if(check_mobile()=="mobile") {
        $helpertext = 'Log in top right to create you own 3WordID'; 
  }
    
    if(isset($_GET["threewords"])) {
        $threewords = sanitizeInput($_GET["threewords"]);
        header('location:3wid_forward.php?threewords=' . $threewords);
      } else {  
        $threewords = "";  
      }

 $db_3wordid_list = db_3wordid_list_recent();
 
 $loginUrl ='/login/options.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3WordID — Three words. One destination.</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@climatebabes">
    <meta name="twitter:title" content="3WordID - Your Unique Identity Solution">
    <meta name="twitter:description" content="Sign up for a 3WordID account to get a unique, easy-to-remember identity for all your online needs.">
    <meta name="twitter:image" content="https://3WordID.com/card_0926.png">
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
            <a href="#how-it-works">How it works</a>
            <a href="#use-cases">Use cases</a>
            <a href="3wid_reseller.php">Pricing</a>
            <a href="https://x.com/climatebabes/status/1921113933592584660">Docs</a>
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
            <div class="eyebrow">HUMAN-READABLE IDENTIFIERS</div>
            <h1>Three words.<br>One destination.</h1>
            <p class="subhead">Look up a URL, notification, or private message box using any three-word ID. Easy to say. Easy to remember.</p>
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
                <div class="search-hints">Try the thee word IDs listed below</div>
                <button type="button" id="createBtn" class="create-link" data-share-url="<?php echo $loginUrl; ?>">Create your own 3WordID →</button>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </form>
        </section>

        <section class="cards" id="use-cases">
            <article class="card">
                
                <h3>Find anything</h3>
                <p>Link any three words to a URL. Share a page, profile, file, or resource with a memorable ID anyone can look up.</p>
            </article>
            <article class="card">
                
                <h3>Leave a message</h3>
                <p>Each 3WordID can have a notification or private mailbox. Perfect for contact forms, inquiries, tips, or direct messages.</p>
            </article>
            <article class="card">
               
                <h3>Share without QR codes</h3>
                <p>Works anywhere words do — on signs, print, tickets, name badges, and more. No scanning. No app. No barriers.</p>
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
        <div><?php echo $footer; ?></div>
        <div>Messages are currently unencrypted — use accordingly.</div>
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
