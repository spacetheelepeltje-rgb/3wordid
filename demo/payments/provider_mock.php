<?php
/**
 * DEMO: stands in for the payment provider's HOSTED payment page (Mollie Checkout, Stripe
 * Checkout, the bank app for iDEAL | Wero, ...). Deliberately generic: no provider brand,
 * logo or look-alike UI. The three buttons simulate what the provider can send the customer
 * back with: paid, canceled or failed.
 *
 * In production the provider ALSO calls our webhook (server to server). Only the webhook
 * may switch a subscription on; the return URL just shows the customer a status page.
 */
require __DIR__ . '/_demo.php';

$id = (string)($_GET['order'] ?? '');
$order = $_SESSION['paydemo']['orders'][$id] ?? null;
$lang = pd_lang();
?><!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="data:,">
    <title><?php echo pd_t('pv.title'); ?></title>
    <link href="../../css/inter.css" rel="stylesheet">
    <link href="demo.css?v=1" rel="stylesheet">
</head>
<body class="provider-page">
    <div class="demo-strip" role="note">
        <strong><?php echo pd_t('demo.ribbon'); ?></strong>
        <span class="demo-strip-links"><a href="index.php"><?php echo pd_t('demo.start'); ?></a></span>
    </div>
    <main class="pv-wrap">
        <div class="pv-card">
            <div class="pv-head">
                <span class="pv-badge"><?php echo pd_t('pv.badge'); ?></span>
                <span class="pv-secure">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
                    <?php echo pd_t('pv.secure'); ?>
                </span>
            </div>
            <?php if (!$order) { ?>
            <p class="pv-explain"><?php echo pd_t('pv.not_found'); ?></p>
            <a class="pv-btn pv-btn-main" href="plans.php"><?php echo pd_t('co.back'); ?></a>
            <?php } else { ?>
            <dl class="pv-facts">
                <div><dt><?php echo pd_t('pv.merchant'); ?></dt><dd>3WordID</dd></div>
                <div><dt><?php echo pd_t('pv.amount'); ?></dt><dd class="pv-amount"><?php echo pd_e(pd_money((float)$order['total'], $order['currency'])); ?></dd></div>
                <div><dt><?php echo pd_t('pv.method'); ?></dt><dd><?php echo pd_t('m.' . $order['method']); ?></dd></div>
                <div><dt><?php echo pd_t('pv.ref'); ?></dt><dd><code><?php echo pd_e($order['id']); ?></code></dd></div>
            </dl>
            <p class="pv-explain"><?php echo pd_t('pv.explain'); ?></p>
            <div class="pv-actions">
                <a class="pv-btn pv-btn-main" id="simPaid" href="<?php echo pd_e(pd_url('return.php', ['order' => $order['id'], 'status' => 'paid'])); ?>"><?php echo pd_t('pv.success'); ?></a>
                <a class="pv-btn" id="simCancel" href="<?php echo pd_e(pd_url('return.php', ['order' => $order['id'], 'status' => 'canceled'])); ?>"><?php echo pd_t('pv.cancel'); ?></a>
                <a class="pv-btn pv-btn-bad" id="simFail" href="<?php echo pd_e(pd_url('return.php', ['order' => $order['id'], 'status' => 'failed'])); ?>"><?php echo pd_t('pv.fail'); ?></a>
            </div>
            <p class="pv-note"><?php echo pd_t('pv.webhook'); ?></p>
            <?php } ?>
        </div>
    </main>
</body>
</html>
