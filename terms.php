<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$data = current_user_from_session($conn);
$loginUrl = '/login/options.php';
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('terms.title'); ?></title>
    <?php echo lang_head_tags(); ?>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
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
        /* phones: no WhatsApp button in the header (it took too much room) */
        @media (max-width: 720px) {
            .site-header .wa-btn { display: none; }
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
            <a href="https://x.com/climatebabes/status/1921113933592584660"><?php echo t('nav.docs'); ?></a>
            <a href="https://github.com/spacetheelepeltje-rgb/3wordid"><?php echo t('nav.github'); ?></a>
            <a href="terms.php"><?php echo t('nav.terms'); ?></a>
        </nav>
        <div class="header-actions">
			<a href="https://wa.me/31644311561?text=From 3WordID.com : "
   target="_blank"
   rel="noopener noreferrer"
   class="btn btn-primary wa-btn" style='background:#25D366;'>
  <?php echo t('nav.whatsapp'); ?>
</a>
<?php echo lang_switcher(); ?>
<a href="<?php echo $loginUrl; ?>" class="btn btn-primary"><?php echo t('nav.login'); ?></a>
        </div>
    </header>

    <main class="legal-wrap">
        <div class="eyebrow"><?php echo t('terms.eyebrow'); ?></div>
        <h1><?php echo t('terms.h1'); ?></h1>
        <p class="legal-lead">
            <?php echo t_raw('terms.lead'); ?>
        </p>
        <p class="legal-meta"><?php echo t('terms.meta'); ?></p>

        <div class="flash">
            <?php echo t_raw('terms.flash'); ?>
        </div>

        <div class="legal-card">
            <section>
                <h2><?php echo t('terms.s1_h'); ?></h2>
                <p><?php echo t_raw('terms.s1_p1'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s2_h'); ?></h2>
                <p><?php echo t_raw('terms.s2_p1'); ?></p>
                <ul>
                    <li><?php echo t('terms.s2_li1'); ?></li>
                    <li><?php echo t('terms.s2_li2'); ?></li>
                    <li><?php echo t('terms.s2_li3'); ?></li>
                </ul>
                <p><?php echo t_raw('terms.s2_p2'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s3_h'); ?></h2>
                <p><?php echo t_raw('terms.s3_p1'); ?></p>
                <p><?php echo t_raw('terms.s3_p2'); ?></p>
                <p><?php echo t_raw('terms.s3_p3'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s4_h'); ?></h2>
                <p><?php echo t_raw('terms.s4_p1'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s5_h'); ?></h2>
                <p><?php echo t_raw('terms.s5_p1'); ?></p>
                <p><?php echo t_raw('terms.s5_p2'); ?></p>
                <p><?php echo t_raw('terms.s5_p3'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s6_h'); ?></h2>
                <p><?php echo t_raw('terms.s6_p1'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s7_h'); ?></h2>
                <p><?php echo t_raw('terms.s7_p1'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s8_h'); ?></h2>
                <p><?php echo t_raw('terms.s8_p1'); ?></p>
                <p><?php echo t_raw('terms.s8_p2'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s9_h'); ?></h2>
                <p><?php echo t_raw('terms.s9_p1'); ?></p>
            </section>

            <section id="data">
                <h2><?php echo t('terms.s10_h'); ?></h2>
                <p><?php echo t_raw('terms.s10_p1'); ?></p>
                <p><?php echo t_raw('terms.s10_p2'); ?></p>
                <p><?php echo t_raw('terms.s10_p3'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s11_h'); ?></h2>
                <p><?php echo t_raw('terms.s11_p1'); ?></p>
                <p><?php echo t_raw('terms.s11_p2'); ?></p>
                <p><?php echo t_raw('terms.s11_p3'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s12_h'); ?></h2>
                <p><?php echo t_raw('terms.s12_p1'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s13_h'); ?></h2>
                <p><?php echo t_raw('terms.s13_p1'); ?></p>
            </section>

            <section>
                <h2><?php echo t('terms.s14_h'); ?></h2>
                <p><?php echo t_raw('terms.s14_p1'); ?></p>
                <p><?php echo t_raw('terms.s14_p2'); ?></p>
            </section>
        </div>

        <div class="legal-actions">
            <a class="btn btn-primary" href="<?php echo $main_url; ?>"><?php echo t('nav.back_home'); ?></a>
            <a class="btn-ghost" href="<?php echo $loginUrl; ?>"><?php echo t('nav.login_spaced'); ?></a>
        </div>
    </main>

    <footer class="site-footer">
        <div><?php echo $footer; ?></div>
        <div><?php echo t('terms.footer'); ?></div>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
