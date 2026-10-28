<?php
/**
 * DEMO: "Manage subscription" (static mock). In production these actions go to the payment
 * provider (customer portal or API: cancel at period end, change plan, update payment method,
 * refund) and the provider's webhook updates our database.
 *
 * "Withdraw from contract here": EU consumers must be able to withdraw online within the
 * 14-day period with a clearly labelled function and a confirm step (Directive (EU) 2023/2673,
 * applies from 19 June 2026). See docs/PAYMENTS.md.
 */
require __DIR__ . '/_demo.php';

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
            $can_withdraw = $sub['region'] === 'eu' && $sub['mode'] !== 'reverse' && time() < $withdraw_until;
        ?>
            <?php if ($done === 'canceled') { ?>
            <div class="flash" role="status"><?php echo pd_t('mg.canceled_flash', ['date' => pd_date((int)$sub['renews'])]); ?></div>
            <?php } elseif ($done === 'resumed') { ?>
            <div class="flash flash-ok" role="status"><?php echo pd_t('mg.resumed_flash'); ?></div>
            <?php } ?>
            <p class="muted small"><?php echo pd_t('mg.static'); ?></p>

            <?php if ($confirm_withdraw && $can_withdraw) { ?>
            <section class="card card-warn">
                <p><?php echo pd_t('mg.withdraw_q', ['plan' => $plan_name, 'amount' => pd_money((float)$sub['total'], $sub['currency'])]); ?></p>
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
                    <div><dt><?php echo pd_t('mg.renews'); ?></dt><dd><?php echo empty($sub['cancel_at_end']) ? pd_e(pd_date((int)$sub['renews'])) . ' · ' . pd_e(pd_money((float)$sub['total'], $sub['currency'])) : pd_t('mg.no_renewal'); ?></dd></div>
                    <div><dt><?php echo pd_t('mg.method'); ?></dt><dd><?php echo pd_t('m.' . $sub['method']); ?></dd></div>
                </dl>
                <div class="btn-row">
                    <a class="btn btn-ghost" href="plans.php"><?php echo pd_t('mg.change_plan'); ?></a>
                    <button type="button" class="btn btn-ghost" data-demo="1"><?php echo pd_t('mg.change_method'); ?></button>
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

            <section class="card">
                <h2><?php echo pd_t('mg.invoices'); ?></h2>
                <div class="table-scroll">
                <table class="inv">
                    <thead><tr><th><?php echo pd_t('mg.inv_no'); ?></th><th><?php echo pd_t('mg.inv_date'); ?></th><th><?php echo pd_t('mg.inv_amount'); ?></th><th></th></tr></thead>
                    <tbody>
                        <tr>
                            <td><?php echo pd_e($sub['invoice']); ?></td>
                            <td><?php echo pd_e(pd_date((int)$sub['started'])); ?></td>
                            <td><?php echo pd_e(pd_money((float)$sub['total'], $sub['currency'])); ?></td>
                            <td><button type="button" class="link-btn" data-demo="1" title="<?php echo pd_t('mg.inv_pdf_title'); ?>"><?php echo pd_t('mg.inv_pdf'); ?></button></td>
                        </tr>
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
