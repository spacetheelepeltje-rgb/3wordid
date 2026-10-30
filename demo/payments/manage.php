<?php
/**
 * DEMO: "Manage subscription". Mollie has no ready-made customer portal, so in production
 * this is our own page calling the Mollie API:
 *   cancel        -> DELETE /v2/customers/{customerId}/subscriptions/{subscriptionId}
 *                    (no more SEPA Direct Debits; we keep the plan until current_period_end)
 *   undo cancel   -> create a new subscription with startDate = current_period_end
 *   change bank   -> a new iDEAL | Wero first payment (e.g. EUR 0.01) gives a new mandate;
 *                    then update the subscription's mandateId
 *   withdraw      -> refund the first payment (POST /v2/payments/{id}/refunds), cancel the
 *                    subscription, user back to free
 * Mollie's webhook then updates our database.
 *
 * "Withdraw from contract here": EU consumers must be able to withdraw online within the
 * 14-day period with a clearly labelled function and a confirm step (Directive (EU) 2023/2673,
 * applies from 19 June 2026). See docs/PAYMENTS.md.
 */
require __DIR__ . '/_demo.php';

pd_plans(); // load plans.json before any output, so a broken file shows the friendly error page

if (empty($_SESSION['paydemo_csrf'])) {
    $_SESSION['paydemo_csrf'] = bin2hex(random_bytes(16));
}
$sub = $_SESSION['paydemo']['sub'];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_array($sub)
    && hash_equals($_SESSION['paydemo_csrf'], (string)($_POST['csrf'] ?? ''))) {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'cancel') {
        $_SESSION['paydemo']['sub']['cancel_at_end'] = true;
        header('Location: manage.php?done=canceled');
        exit;
    }
    if ($action === 'resume') {
        $_SESSION['paydemo']['sub']['cancel_at_end'] = false;
        header('Location: manage.php?done=resumed');
        exit;
    }
    if ($action === 'simulate_renewal' && empty($sub['cancel_at_end'])) {
        // DEMO ONLY: pretend the renewal date has come. In production Mollie creates this
        // SEPA Direct Debit payment itself and calls our webhook (payment has a subscriptionId).
        $next = strtotime($sub['billing'] === 'month' ? '+1 month' : '+1 year', (int)$sub['renews']);
        $_SESSION['paydemo']['sub']['invoices'][] = [
            'no' => '3WID-' . date('Y', (int)$sub['renews']) . '-' . str_pad((string)random_int(1, 999), 4, '0', STR_PAD_LEFT),
            'date' => (int)$sub['renews'], 'total' => $sub['total'], 'method' => 'sepa',
        ];
        $_SESSION['paydemo']['sub']['renews'] = $next;
        header('Location: manage.php?done=renewed');
        exit;
    }
    if ($action === 'withdraw') {
        $_SESSION['paydemo']['sub'] = null;
        header('Location: index.php?note=withdrawn');
        exit;
    }
}

$done = (string)($_GET['done'] ?? '');
$confirm_withdraw = isset($_GET['withdraw']);

pd_head(pd_t('mg.title'), 'page-manage');
?>
    <main class="wrap narrow">
        <p class="back-link"><a href="index.php">← <?php echo pd_t('mg.back'); ?></a></p>
        <div class="page-head"><div><h1><?php echo pd_t('mg.h1'); ?></h1></div></div>
        <?php if (!is_array($sub)) { ?>
            <div class="flash"><?php echo pd_t('mg.none'); ?></div>
            <a class="btn btn-gold" href="plans.php"><?php echo pd_t('list.subscribe'); ?></a>
        <?php } else {
            $plan_name = html_entity_decode(pd_plan_name($sub['plan'], (int)$sub['qty']), ENT_QUOTES, 'UTF-8');
            $withdraw_until = (int)$sub['started'] + 14 * 86400;
            $can_withdraw = $sub['mode'] !== 'reverse' && time() < $withdraw_until;
        ?>
            <?php if ($done === 'canceled') { ?>
            <div class="flash" role="status"><?php echo pd_t('mg.canceled_flash', ['date' => pd_date((int)$sub['renews'])]); ?></div>
            <?php } elseif ($done === 'renewed') { ?>
            <div class="flash flash-ok" role="status"><?php echo pd_t('mg.renewed_flash', ['date' => pd_date((int)$sub['renews'])]); ?></div>
            <?php } elseif ($done === 'resumed') { ?>
            <div class="flash flash-ok" role="status"><?php echo pd_t('mg.resumed_flash'); ?></div>
            <?php } ?>
            <p class="muted small"><?php echo pd_t('mg.static'); ?></p>

            <?php if ($confirm_withdraw && $can_withdraw) { ?>
            <section class="card card-warn">
                <p><?php echo pd_t('mg.withdraw_q', ['plan' => $plan_name, 'amount' => pd_money((float)$sub['total'])]); ?></p>
                <form method="post" action="manage.php" class="btn-row">
                    <input type="hidden" name="csrf" value="<?php echo pd_e($_SESSION['paydemo_csrf']); ?>">
                    <input type="hidden" name="action" value="withdraw">
                    <button type="submit" class="btn btn-danger" id="confirmWithdraw"><?php echo pd_t('mg.withdraw_confirm'); ?></button>
                    <a class="btn btn-ghost" href="manage.php"><?php echo pd_t('mg.withdraw_keep'); ?></a>
                </form>
            </section>
            <?php } ?>

            <section class="card">
                <dl class="kv">
                    <div><dt><?php echo pd_t('mg.plan'); ?></dt><dd><?php echo pd_e($plan_name); ?> <?php echo pd_gold_lock(pd_t('list.gold_title')); ?></dd></div>
                    <div><dt><?php echo pd_t('mg.status'); ?></dt><dd><?php echo empty($sub['cancel_at_end']) ? '<span class="badge badge-on">' . pd_t('mg.active') . '</span>' : pd_t('mg.canceling', ['date' => pd_date((int)$sub['renews'])]); ?></dd></div>
                    <div><dt><?php echo pd_t('mg.renews'); ?></dt><dd id="nextRenewal"><?php echo empty($sub['cancel_at_end']) ? pd_e(pd_date((int)$sub['renews'])) . ' · ' . pd_e(pd_money((float)$sub['total'])) : pd_t('mg.no_renewal'); ?></dd></div>
                    <div><dt><?php echo pd_t('mg.method'); ?></dt><dd><?php echo empty($sub['cancel_at_end']) ? pd_t('m.sepa') . '<br><span class="nowrap">' . pd_t('mg.method_value', ['bank' => $sub['bank'], 'iban' => $sub['iban']]) . '</span>' : '—'; ?></dd></div>
                    <div><dt><?php echo pd_t('mg.first_paid'); ?></dt><dd><?php echo pd_t('mg.first_value', ['date' => pd_date((int)$sub['started'])]); ?></dd></div>
                </dl>
                <div class="btn-row">
                    <a class="btn btn-ghost" href="plans.php"><?php echo pd_t('mg.change_plan'); ?></a>
                    <button type="button" class="btn btn-ghost" data-demo="1" title="<?php echo pd_t('mg.change_method_title'); ?>"><?php echo pd_t('mg.change_method'); ?></button>
                    <form method="post" action="manage.php">
                        <input type="hidden" name="csrf" value="<?php echo pd_e($_SESSION['paydemo_csrf']); ?>">
                        <?php if (empty($sub['cancel_at_end'])) { ?>
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-ghost" id="cancelSub"><?php echo pd_t('mg.cancel'); ?></button>
                        <?php } else { ?>
                        <input type="hidden" name="action" value="resume">
                        <button type="submit" class="btn btn-ghost"><?php echo pd_t('mg.resume'); ?></button>
                        <?php } ?>
                    </form>
                </div>
                <?php if (empty($sub['cancel_at_end'])) { ?><p class="muted small"><?php echo pd_t('mg.cancel_hint', ['date' => pd_date((int)$sub['renews'])]); ?></p><?php } ?>
                <?php if ($can_withdraw && !$confirm_withdraw) { ?>
                <div class="withdraw">
                    <a class="btn btn-ghost btn-withdraw" href="manage.php?withdraw=1"><?php echo pd_t('mg.withdraw'); ?></a>
                    <p class="muted small"><?php echo pd_t('mg.withdraw_hint', ['date' => pd_date($withdraw_until)]); ?></p>
                </div>
                <?php } ?>
            </section>

            <?php if (empty($sub['cancel_at_end'])) { ?>
            <form method="post" action="manage.php" class="demo-sim">
                <input type="hidden" name="csrf" value="<?php echo pd_e($_SESSION['paydemo_csrf']); ?>">
                <input type="hidden" name="action" value="simulate_renewal">
                <span><?php echo pd_t('mg.sim_hint'); ?></span>
                <button type="submit" class="btn btn-ghost" id="simRenewal"><?php echo pd_t('mg.sim_renewal'); ?></button>
            </form>
            <?php } ?>

            <section class="card">
                <h2><?php echo pd_t('mg.invoices'); ?></h2>
                <div class="table-scroll">
                <table class="inv">
                    <thead><tr><th><?php echo pd_t('mg.inv_no'); ?></th><th><?php echo pd_t('mg.inv_date'); ?></th><th><?php echo pd_t('mg.inv_amount'); ?></th><th><?php echo pd_t('mg.inv_method'); ?></th><th></th></tr></thead>
                    <tbody>
                        <?php foreach (array_reverse($sub['invoices']) as $inv) { ?>
                        <tr>
                            <td><?php echo pd_e($inv['no']); ?></td>
                            <td><?php echo pd_e(pd_date((int)$inv['date'])); ?></td>
                            <td><?php echo pd_e(pd_money((float)$inv['total'])); ?></td>
                            <td><?php echo pd_t($inv['method'] === 'ideal' ? 'm.ideal' : 'm.sepa'); ?></td>
                            <td><button type="button" class="link-btn" data-demo="1" title="<?php echo pd_t('mg.inv_pdf_title'); ?>"><?php echo pd_t('mg.inv_pdf'); ?></button></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>
            </section>
        <?php } ?>
        <div class="demo-toast" id="demoToast" role="status" aria-live="polite" hidden><?php echo pd_t('demo.click'); ?></div>
    </main>
    <script>
    (function () {
        var toast = document.getElementById('demoToast');
        var timer = null;
        document.querySelectorAll('[data-demo]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                toast.hidden = false;
                clearTimeout(timer);
                timer = setTimeout(function () { toast.hidden = true; }, 1800);
            });
        });
    })();
    </script>
<?php
pd_foot();
