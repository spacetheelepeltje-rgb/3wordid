<?php
/**
 * DEMO: stands in for Mollie's HOSTED checkout for an iDEAL | Wero first payment
 * (the customer picks a bank and confirms in the bank app). Generic look on purpose:
 * no Mollie or iDEAL/Wero logos or look-alike UI, only the text "Mollie (demo)".
 * The three buttons simulate what the customer can come back with: paid, canceled or failed.
 *
 * In production Mollie ALSO calls our webhook (server to server). Only the webhook may
 * switch a subscription on; the return URL just shows the customer a status page.
 */
require __DIR__ . '/_demo.php';

$id = (string)($_GET['order'] ?? '');
$order = $_SESSION['paydemo']['orders'][$id] ?? null;
$bank_error = isset($_GET['bank_error']);
$lang = pd_lang();
pd_plans(); // load plans.json before any output, so a broken file shows the friendly error page
?><!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="data:,">
    <title><?php echo pd_t('pv.title'); ?></title>
    <link href="../../css/inter.css" rel="stylesheet">
    <link href="demo.css?v=2" rel="stylesheet">
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
                <div><dt><?php echo pd_t('pv.desc'); ?></dt><dd><?php echo pd_plan_name($order['plan'], (int)$order['qty']); ?></dd></div>
                <div><dt><?php echo pd_t('pv.amount'); ?></dt><dd class="pv-amount"><?php echo pd_e(pd_money((float)$order['total'])); ?></dd></div>
                <div><dt><?php echo pd_t('pv.ref'); ?></dt><dd><code><?php echo pd_e($order['id']); ?></code></dd></div>
            </dl>
            <form method="get" action="return.php" class="pv-form">
                <input type="hidden" name="order" value="<?php echo pd_e($order['id']); ?>">
                <fieldset class="pv-banks">
                    <legend><?php echo pd_t('pv.choose_bank'); ?></legend>
                    <?php if ($bank_error) { ?><p class="pv-err" role="alert"><?php echo pd_t('pv.err_bank'); ?></p><?php } ?>
                    <?php foreach (pd_ideal_banks() as $i => $bank) { ?>
                    <label class="pv-bank"><input type="radio" name="bank" value="<?php echo pd_e($bank); ?>" required> <span><?php echo pd_e($bank); ?></span></label>
                    <?php } ?>
                </fieldset>
                <p class="pv-mandate"><?php echo pd_t('pv.mandate'); ?></p>
                <p class="pv-explain"><?php echo pd_t('pv.explain'); ?></p>
                <div class="pv-actions">
                    <button type="submit" class="pv-btn pv-btn-main" id="simPaid" name="status" value="paid"><?php echo pd_t('pv.success'); ?></button>
                    <button type="submit" class="pv-btn" id="simCancel" name="status" value="canceled" formnovalidate><?php echo pd_t('pv.cancel'); ?></button>
                    <button type="submit" class="pv-btn pv-btn-bad" id="simFail" name="status" value="failed" formnovalidate><?php echo pd_t('pv.fail'); ?></button>
                </div>
            </form>
            <p class="pv-note"><?php echo pd_t('pv.webhook'); ?></p>
            <?php } ?>
        </div>
    </main>
</body>
</html>
