<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';


  //error_log('add page ip ' . get_client_ip() . ' time ' . time() . ' session  ' . json_encode($_SESSION) . ' session id ' . session_id() . ' user token ' . $_SESSION["user_token"] );

  // The login token is only taken from the session, never from the URL.
  
 $data = current_user_from_session($conn);
    if ($data == NULL) {
        header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
        exit;
    }
 
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));


?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('pc.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
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
            <a href="terms.php"><?php echo t('nav.terms'); ?></a>
            <a href="https://x.com/climatebabes/status/1921113933592584660"><?php echo t('nav.github'); ?></a>
        </nav>
        <div class="header-actions" id="userContainer">
         <?php echo lang_switcher(); ?>
        </div>
    </header>

    <main class="consent-wrap">
        <div class="eyebrow"><?php echo t('pc.eyebrow'); ?></div>
        <h1><?php echo t('pc.h1'); ?></h1>
        <p class="consent-lead">
            <?php echo t('pc.lead'); ?>
        </p>

        <div class="flash">
            <?php echo t('pc.flash'); ?>
        </div>

        <div class="search-container consent-card">
            <form id="3widForm" action="3wid_privacy_consent_process.php" method="post">

                <div class="policy-block">
                    <h2><?php echo t('pc.data_h'); ?></h2>
                    <p>
                        <?php echo t('pc.data_p1'); ?>
                    </p>
                    <ul>
                        <li><?php echo t('pc.li1'); ?></li>
                        <li><?php echo t('pc.li2'); ?></li>
                        <li><?php echo t('pc.li3'); ?></li>
                        <li><?php echo t('pc.li4'); ?></li>
                        <li><?php echo t('pc.li5'); ?></li>
                        <li><?php echo t('pc.li6'); ?></li>
                    </ul>
                    <p>
                        <?php echo t('pc.data_p2'); ?>
                    </p>
                    <p>
                        <?php echo t('pc.data_p3'); ?>
                    </p>
                </div>

                <div class="policy-block">
                    <h2><?php echo t('pc.func_h'); ?></h2>
                    <p>
                        <?php echo t('pc.func_p1'); ?>
                    </p>
                    <p>
                        <?php echo t('pc.func_p2'); ?>
                    </p>
                    <p>
                        <?php echo t_raw('pc.func_p3'); ?>
                    </p>
                </div>

                <div class="check-row">
                    <input type="checkbox" id="consent" name="consent">
                    <label for="consent">
                        <?php echo t('pc.consent'); ?>
                    </label>
                </div>

                <div class="buttons">
                    <button type="submit" id="submitBtn"><?php echo t('pc.submit'); ?></button>
                    <div id="helperText"></div>
                </div>

                <p class="consent-meta">
                    <?php echo t('pc.meta'); ?>
                </p>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="user_id" value="<?= $data["id"]; ?>">
            </form>
        </div>
    </main>

    <footer class="site-footer">
        <div><?php echo $footer; ?></div>
        <div><?php echo t('pc.footer'); ?></div>
        <?php echo site_copyright(); ?>
    </footer>
</body>
<?php echo i18n_js_all(); ?>
<script src="js/3wid_privacy_consent.js"></script>
</html>
