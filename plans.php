<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$data = current_user_from_session($conn);
$loginUrl = '/login/options.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions — 3WordID</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/newstyle.css">
    <style>
        .legal-wrap {
            max-width: 920px;
            margin: 0 auto;
            padding: 48px 24px 72px;
        }
        .legal-wrap .eyebrow {
            text-align: left;
            margin-bottom: 10px;
        }
        .legal-wrap h1 {
            font-size: clamp(32px, 5vw, 48px);
            margin-bottom: 10px;
        }
        .legal-lead {
            color: var(--slate);
            font-size: 16px;
            line-height: 1.55;
            max-width: 720px;
            margin: 0 0 22px;
        }
        .legal-meta {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 22px;
        }
        .flash {
            background: #fff;
            border: 1px solid #eceae4;
            border-left: 4px solid var(--gold);
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow: var(--shadow);
            margin-bottom: 22px;
            color: var(--navy);
            font-size: 15px;
            line-height: 1.5;
            font-weight: 500;
        }
        .legal-card {
            background: #fff;
            border: 1px solid #eceae4;
            border-radius: 16px;
            padding: 28px 28px 8px;
            box-shadow: 0 1px 0 rgba(15,28,63,0.03);
        }
        .legal-card h2 {
            font-size: 16px;
            letter-spacing: -0.02em;
            margin: 0 0 8px;
        }
        .legal-card section {
            padding-bottom: 22px;
            margin-bottom: 18px;
            border-bottom: 1px solid #f0eee8;
        }
        .legal-card section:last-child {
            border-bottom: 0;
        }
        .legal-card p,
        .legal-card li {
            color: var(--slate);
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 10px;
        }
        .legal-card ul {
            margin: 0 0 10px 18px;
            padding: 0;
        }
        .legal-card strong { color: var(--navy); font-weight: 600; }
        .legal-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }
        .btn-ghost {
            background: #fff;
            color: var(--navy);
            border: 1px solid var(--line);
            padding: 10px 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-ghost:hover { background: #f3f1ec; }
        @media (max-width: 800px) {
            .legal-card { padding: 20px 16px 4px; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url; ?>">
            <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
            3WordID
        </a>
        <nav class="nav-links">
            <a href="https://x.com/climatebabes/status/1921113933592584660">Docs on X.com</a>
            <a href="https://github.com/spacetheelepeltje-rgb/3wordid">Open Source on Github</a>
            <a href="terms.php">Terms</a>
        </nav>
        <div class="header-actions">
			<a href="https://wa.me/31644311561?text=From 3WordID.com : "
   target="_blank"
   rel="noopener noreferrer"
   class="btn btn-primary" style='background:#25D366;'>
  Chat on WhatsApp
</a>
<a href="<?php echo $loginUrl; ?>" class="btn btn-primary">Log in/Create ID</a>
        </div>
    </header>

    <main class="legal-wrap">
        <div class="eyebrow">PLANS</div>
        <h1>Subscription plans</h1>
        <p class="legal-lead">
            3WordID is a lookup tool. It links three words to a URL, a notification, or a message box. That is the whole service. Use 
            it only on that basis. Please check our <a href='terms.php'>Terms and Conditions</a>. As a startup we do not accept any liability or abuse of the service.</p>
        <p class="legal-meta">Effective 23 September 2026. Replaces any earlier terms page.</p>

       

        <div class="legal-card">
            <section>
                <h2>1. Individual 3WID 1-5</h2>
                <p>
                    You can have one (1) 3WID for one year by paying $10 and sending us an email with your details. You need to mention your login email because we encrypt it at this time. Payment details will be returned to you.
                </p>
            </section>

            <section>
                <h2>2. Group 3WID 10 for $60</h2>
               <p>
                    You can have 10 3WID for one year by paying $30 and sending us an email with your details. You need to mention your login email because we encrypt it at this time. Payment details will be returned to you.
                </p>
            </section>
            
            <section>
                <h2>2. Group 3WID 100 for $500</h2>
                <p>
                    You can have 100 3WID for one year by paying $500 and sending us an email with your details. You need to mention your login email because we encrypt it at this time. Payment details will be returned to you.
                </p>
            </section>

			<section>
                <h2>3. Group 3WID 20 for $20 for Musea and Galleries</h2>
                <p>
                    You can have 20 3WID for one year by paying $20 and sending us an email with your details. You need to show you are a gallery or museum. You can have multiples of course so 40/40 etc. You need to mention your login email because we encrypt it at this time. Payment details will be returned to you.
                </p>
            </section>      
        </div>
        <div class="legal-actions">
            <a class="btn btn-primary" href="<?php echo $main_url; ?>">Back to homepage</a>
            <a class="btn-ghost" href="<?php echo $loginUrl; ?>">Log in / Create ID</a>
        </div>
    </main>
    <footer class="site-footer">
        <div><?php echo $footer; ?></div>
        <div>No rights can be derived from use of this site. Service provided as is.</div>
       
    </footer>
</body>
</html>
