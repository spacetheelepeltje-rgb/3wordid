<?php
require_once 'config.php';
require_once __DIR__ . '/../php/session_boot.php';
start_app_session();
require_once __DIR__ . '/../php/i18n.php'; // t(), current_lang(), lang_switcher()

if (current_user_from_session($conn)) {
    header('Location: ../3wid_list.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$_SESSION['oauth_state'] = bin2hex(random_bytes(16));
$client->setState($_SESSION['oauth_state']);
$googleUrl = $client->createAuthUrl();

$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <style>
        :root {
            --navy: #0F1C3F;
            --navy-hover: #162756;
            --slate: #5B6478;
            --muted: #8A93A6;
            --line: #E6E3DC;
            --paper: #F7F6F3;
            --white: #FFFFFF;
            --gold: #B45309;
            --shadow: 0 10px 30px rgba(15,28,63,.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, system-ui, sans-serif;
            background: var(--paper);
            color: var(--navy);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 28px;
            background: var(--paper);
            border-bottom: 1px solid var(--line);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--navy);
            font-weight: 800;
            letter-spacing: -0.04em;
        }
        .brand-mark {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--navy);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            line-height: 0.9;
            text-align: center;
        }
        .header-actions { display: flex; align-items: center; gap: 10px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 44px;
            padding: 0 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-primary { background: var(--navy); color: #fff; width: 100%; }
        .btn-primary:hover { background: var(--navy-hover); }
        .btn-ghost {
            background: var(--white);
            color: var(--navy);
            border-color: var(--line);
            width: 100%;
        }
        .btn-ghost:hover { background: #f3f1ec; }
        .wrap {
            width: 100%;
            max-width: 440px;
            margin: 48px auto 0;
            padding: 0 24px 48px;
            flex: 1;
        }
        .eyebrow {
            color: var(--gold);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 8px;
        }
        .sub {
            color: var(--slate);
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .flash {
            background: var(--white);
            border: 1px solid #ECEAE4;
            border-left: 4px solid var(--gold);
            border-radius: 14px;
            padding: 12px 14px;
            color: var(--slate);
            font-size: 14px;
            margin-bottom: 16px;
        }
        .card {
            background: var(--white);
            border: 1px solid #ECEAE4;
            border-radius: 16px;
            padding: 22px;
            box-shadow: var(--shadow);
        }
        .card + .card { margin-top: 14px; }
        .card h2 { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .card p {
            color: var(--slate);
            font-size: 13px;
            line-height: 1.45;
            margin-bottom: 16px;
        }
        .stack { display: flex; flex-direction: column; gap: 10px; }
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--muted);
            font-size: 12px;
            margin: 18px 0;
        }
        .divider:before,
        .divider:after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--line);
        }
        .hint {
            color: var(--muted);
            font-size: 13px;
            text-align: center;
            margin-top: 20px;
        }
        .hint a {
            color: var(--navy);
            font-weight: 600;
            text-decoration: none;
        }
        footer {
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            padding: 24px;
        }
        footer a {
            color: var(--muted);
            margin: 0 8px;
            text-decoration: none;
        }
        @media (max-width: 800px) {
            .site-header { padding: 14px 20px; }
            h1 { font-size: 28px; }
            .wrap { margin-top: 32px; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="../index.php">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        <div class="header-actions">
           <?php echo lang_switcher(); ?>
        </div>
    </header>

    <main class="wrap">
        <div class="eyebrow"><?php echo t('login.eyebrow'); ?></div>
        <h1><?php echo t('login.h1'); ?></h1>
        <p class="sub"><?php echo t('login.sub'); ?></p>

        <?php if ($message !== ''): ?>
            <div class="flash"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Google</h2>
            <p><?php echo t('login.google_p'); ?></p>
            <a class="btn btn-primary" href="<?php echo htmlspecialchars($googleUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo t('login.google_btn'); ?>
            </a>
        </div>

        <div class="divider"><?php echo t('login.or'); ?></div>

        <div class="card">
            <h2><?php echo t('login.email_h'); ?></h2>
            <p><?php echo t('login.email_p'); ?></p>
            <div class="stack">
                <a class="btn btn-ghost" href="email_login.php"><?php echo t('login.email_signin'); ?></a>
                <a class="btn btn-ghost" href="email_register.php"><?php echo t('login.email_register'); ?></a>
            </div>
        </div>

       
    </main>

    <footer>

    </footer>
</body>
</html>
