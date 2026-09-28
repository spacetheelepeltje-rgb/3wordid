<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';


  //error_log('add page ip ' . get_client_ip() . ' time ' . time() . ' session  ' . json_encode($_SESSION) . ' session id ' . session_id() . ' user token ' . $_SESSION["user_token"] );

  if(isset($_GET['user_token'])) {
      if(!isset($_SESSION['user_token'])) {
          $_SESSION['user_token']=$_GET['user_token'];
          }
      }
  
 $data = current_user_from_session($conn);
    if ($data == NULL) {
        header('Location: login/options.php?message=' . rawurlencode('Please log in.'));
        exit;
    }
 
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy consent — 3WordID</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/newstyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .consent-wrap {
            max-width: 720px;
            margin: 0 auto;
            padding: 48px 24px 72px;
        }
        .consent-wrap .eyebrow {
            text-align: left;
            margin-bottom: 10px;
        }
        .consent-wrap h1 {
            font-size: clamp(28px, 4.5vw, 40px);
            margin-bottom: 10px;
        }
        .consent-lead {
            color: var(--slate);
            font-size: 16px;
            line-height: 1.55;
            margin: 0 0 22px;
        }
        .flash {
            background: #fff;
            border: 1px solid #eceae4;
            border-left: 4px solid var(--gold);
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow: var(--shadow);
            margin-bottom: 18px;
            color: var(--navy);
            font-size: 15px;
            line-height: 1.5;
            font-weight: 500;
        }
        .consent-card {
            background: #fff;
            border: 1px solid #eceae4;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 0 rgba(15,28,63,0.03);
        }
        .consent-card h2 {
            font-size: 15px;
            margin: 0 0 8px;
        }
        .consent-card p,
        .consent-card li {
            color: var(--slate);
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 10px;
        }
        .consent-card ul {
            margin: 0 0 16px 18px;
            padding: 0;
        }
        .policy-block {
            padding-bottom: 16px;
            margin-bottom: 16px;
            border-bottom: 1px solid #f0eee8;
        }
        .policy-block:last-of-type {
            border-bottom: 0;
            margin-bottom: 8px;
            padding-bottom: 8px;
        }
        .check-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 14px;
            background: #f3f1ec;
            border-radius: 12px;
            margin: 8px 0 18px;
        }
        .check-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            flex: 0 0 auto;
            accent-color: #0f1c3f;
        }
        .check-row label {
            color: var(--navy);
            font-size: 14px;
            line-height: 1.5;
            font-weight: 500;
            cursor: pointer;
        }
        .buttons {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        #submitBtn {
            background: var(--navy);
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 12px 18px;
            font-weight: 650;
            cursor: pointer;
            font-family: inherit;
        }
        #submitBtn:hover { background: var(--navy-2); }
        #helperText {
            color: #9b1c1c;
            font-size: 13px;
            font-weight: 500;
        }
        .consent-meta {
            margin-top: 14px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }
        .consent-meta a { color: var(--navy); }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url; ?>">
            <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
            3WordID
        </a>
        <nav class="nav-links">
            <a href="terms.php">Terms</a>
            <a href="https://github.com/spacetheelepeltje-rgb/3wordid">Open Source on Github</a>
        </nav>
        <div class="header-actions" id="userContainer">
         
        </div>
    </header>

    <main class="consent-wrap">
        <div class="eyebrow">REQUIRED</div>
        <h1>Privacy and service consent</h1>
        <p class="consent-lead">
            Read this before you continue. Checking the box accepts the service as is and allows storage of the data listed below.
        </p>

        <div class="flash">
            3WordID only links three words to a URL, a notification, or a message box.
            You accept the site as is. No rights can be derived from any interaction with this site.
        </div>

        <div class="search-container consent-card">
            <form id="3widForm" action="3wid_privacy_consent_process.php" method="post">

                <div class="policy-block">
                    <h2>Data policy</h2>
                    <p>
                        To run the lookup tool we store personal data supplied by Google and data you submit:
                    </p>
                    <ul>
                        <li>first name and last name;</li>
                        <li>a link to your avatar;</li>
                        <li>your email address, stored hashed, so we cannot use it to email you;</li>
                        <li>messages you send and receive;</li>
                        <li>text you put in the notification field of a 3WordID;</li>
                        <li>the 3WordIDs you create and related lookup data.</li>
                    </ul>
                    <p>
                        We share this data with third parties only if we are legally obligated to, or as needed to host and process the service — not to sell it.
                        We may show this page again so you can reconfirm.
                    </p>
                </div>

                <div class="policy-block">
                    <h2>Functionality policy</h2>
                    <p>
                        If you check the box you accept the service as it is offered.
                        Features may change or stop at any time in the interest of the site.
                        We will make a reasonable effort to offer what we describe. That is not a warranty.
                    </p>
                    <p>
                        Paid accounts overrule free accounts when reserving a 3WordID.
                        3WordIDs may be localized by country, city, or other locale.
                        The site owner may terminate a 3WordID at any time if it deems that necessary.
                        Unpaid IDs may expire or be taken by another user.
                        Messages are currently unencrypted — use them accordingly.
                    </p>
                    <p>
                        The full terms are on the <a href="terms.php">terms page</a>. Those terms apply to this consent.
                    </p>
                </div>

                <div class="check-row">
                    <input type="checkbox" id="consent" name="consent">
                    <label for="consent">
                        I consent to storage of my data as described in the data policy above, and I agree with the functionality policy above, including use of the site as is.
                    </label>
                </div>

                <div class="buttons">
                    <button type="submit" id="submitBtn">Submit</button>
                    <div id="helperText"></div>
                </div>

                <p class="consent-meta">
                    You can leave this page without checking the box. The account will not be enabled for the service until you consent.
                </p>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="user_id" value="<?= $data["id"]; ?>">
            </form>
        </div>
    </main>

    <footer class="site-footer">
        <div><?php echo $footer; ?></div>
        <div>No rights can be derived from use of this site. Service provided as is.</div>
    </footer>
</body>
<script src="js/3wid_privacy_consent.js"></script>
</html>
