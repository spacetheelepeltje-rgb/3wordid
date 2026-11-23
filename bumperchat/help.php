<?php
// Bumperchat help: one public page that explains every step. Texts live in lang/*.php (bch.*).
// Stays inside /bumperchat/: no link to the main 3WordID homepage, list or login.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

header('X-Robots-Tag: noindex, nofollow');

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$user = current_user_from_session($conn);

$days = function_exists('pending_ttl_days') ? (string) pending_ttl_days() : '120';
$max = function_exists('pending_max_per_plate') ? (string) pending_max_per_plate() : '3';

$sections = [
    'login' => ['bch.login_h', ['bch.login_p1', 'bch.login_p2', 'bch.login_p3']],
    'message' => ['bch.msg_h', ['bch.msg_p1', 'bch.msg_claimed', 'bch.msg_unclaimed', 'bch.msg_p2']],
    'country' => ['bch.country_h', ['bch.country_p1', 'bch.country_p2', 'bch.country_p3']],
    'claim' => ['bch.claim_h', ['bch.claim_p1', 'bch.claim_p2', 'bch.claim_p3']],
    'chat' => ['bch.chat_h', ['bch.chat_p1', 'bch.chat_p2']],
    'delete' => ['bch.del_h', ['bch.del_p1', 'bch.del_p2']],
];
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('bch.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=10">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap bc-help">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bch.h1'); ?></h1>
        <p class="bc-lede"><?php echo t('bch.lede'); ?></p>

        <nav class="bc-card bc-help-toc" aria-label="<?php echo bc_e(t_plain('bch.toc')); ?>">
            <h2><?php echo t('bch.toc'); ?></h2>
            <ol>
                <?php foreach ($sections as $id => $s): ?>
                <li><a href="#<?php echo bc_e($id); ?>"><?php echo preg_replace('/^\d+\.\s*/', '', t($s[0])); ?></a></li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <?php foreach ($sections as $id => $s): ?>
        <section class="bc-card" id="<?php echo bc_e($id); ?>">
            <h2><?php echo t($s[0]); ?></h2>
            <?php foreach ($s[1] as $key): ?>
            <p class="bc-p"><?php echo t($key, ['days' => $days, 'max' => $max]); ?></p>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>

        <p class="bc-back">
            <?php if ($user): ?><a href="chat.php"><?php echo t('bch.back_chats'); ?></a> · <?php endif; ?>
            <a href="index.php"><?php echo t('bch.back_home'); ?></a> ·
            <a href="../terms.php?from=bumperchat#bumperchat"><?php echo t('bch.terms'); ?></a>
        </p>
    </main>

    <footer class="site-footer">
        <span><?php echo bumperchat_footer_links(); ?></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
