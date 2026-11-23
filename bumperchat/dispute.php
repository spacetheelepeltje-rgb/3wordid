<?php
// Bumperchat ownership dispute: information only. No form, upload, payment, or storage.
// Visitors start by email; we reply with KYC instructions. Do not ask for ID documents here.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$user = current_user_from_session($conn);

function bc_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$words = bumperchat_normalize_words((string) ($_GET['words'] ?? ''));
$contact = defined('SITE_CONTACT_EMAIL') ? (string) SITE_CONTACT_EMAIL : '';
if (!filter_var($contact, FILTER_VALIDATE_EMAIL)) {
    $contact = '';
}

$mailto = '';
if ($contact !== '') {
    if ($words !== '') {
        $subject = t_plain('bc.dispute_subject', ['words' => $words]);
        $body = t_plain('bc.dispute_body', ['words' => $words]);
    } else {
        $subject = t_plain('bc.dispute_subject_generic');
        $body = t_plain('bc.dispute_body_generic');
    }
    $mailto = 'mailto:' . $contact
        . '?subject=' . rawurlencode($subject)
        . '&body=' . rawurlencode($body);
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('bc.dispute_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newstyle.css">
    <link rel="stylesheet" href="../css/bumperchat.css?v=10">
</head>
<body class="bc-body">
    <?php echo bumperchat_header($user); ?>

    <main class="bc-wrap bc-wrap-narrow">
        <div class="bc-eyebrow">Bumperchat.com</div>
        <h1 class="bc-h1"><?php echo t('bc.dispute_h1'); ?></h1>
        <?php if ($words !== ''): ?>
            <p class="bc-lede"><?php echo t('bc.dispute_lede', ['words' => $words]); ?></p>
        <?php else: ?>
            <p class="bc-lede"><?php echo t('bc.dispute_lede_generic'); ?></p>
        <?php endif; ?>

        <div class="bc-card">
            <p class="bc-p"><?php echo t('bc.dispute_p1'); ?></p>
            <p class="bc-p"><?php echo t('bc.dispute_p2'); ?></p>
            <p class="bc-p"><?php echo t('bc.dispute_p3'); ?></p>
            <?php if ($mailto !== ''): ?>
                <p><a class="btn btn-primary" href="<?php echo bc_e($mailto); ?>"><?php
                    echo $words !== '' ? t('bc.dispute_mail', ['words' => $words]) : t('bc.dispute_mail_generic');
                ?></a></p>
            <?php endif; ?>
            <p class="bc-meta"><?php echo t_raw('bc.dispute_terms'); ?></p>
        </div>

        <p class="bc-back"><a href="<?php echo $words !== '' ? 'claim.php?words=' . rawurlencode($words) : 'claim.php'; ?>"><?php echo t('bc.back_claim'); ?></a></p>
    </main>

    <footer class="site-footer">
        <span><?php echo bumperchat_footer_links(); ?></span>
        <?php echo site_copyright(); ?>
    </footer>
</body>
</html>
