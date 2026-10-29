<?php
/**
 * DEMO: checkout. Order summary, billing details needed for VAT, and the only payment method:
 * iDEAL | Wero. "Pay" stores a fake order in the demo session and goes to provider_mock.php,
 * which stands in for the Mollie hosted checkout. No real payment call is made.
 *
 * In production (Mollie, see docs/PAYMENTS.md section 4) this page would:
 *   1. create or reuse a Mollie customer (Customers API) for the logged-in user,
 *   2. create the FIRST payment server to server: method=ideal, sequenceType=first,
 *      customerId, amount computed here (never taken from the browser), redirectUrl, webhookUrl,
 *   3. redirect to the payment's _links.checkout.href.
 * After that payment is paid, iDEAL gives a SEPA Direct Debit mandate, and the webhook creates
 * the Mollie subscription that collects the renewals.
 */
require __DIR__ . '/_demo.php';

if (empty($_SESSION['paydemo_csrf'])) {
    $_SESSION['paydemo_csrf'] = bin2hex(random_bytes(16));
}

$errors = [];
$failed = false;
$in = [
    'plan' => (string)($_GET['plan'] ?? ''),
    'billing' => (string)($_GET['billing'] ?? 'year'),
    'qty' => (int)($_GET['qty'] ?? 1),
    'email' => '', 'name' => '', 'company' => '', 'country' => '', 'vat_number' => '',
    'terms' => false, 'start_now' => false,
];

// Back from a failed payment: fill the form again from the demo order
if (isset($_GET['order']) && isset($_SESSION['paydemo']['orders'][$_GET['order']])) {
    $o = $_SESSION['paydemo']['orders'][$_GET['order']];
    foreach (['plan', 'billing', 'qty', 'email', 'name', 'company', 'country', 'vat_number'] as $k) {
        $in[$k] = $o[$k];
    }
    $failed = (($_GET['error'] ?? '') === 'failed');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['plan', 'billing', 'email', 'name', 'company', 'country', 'vat_number'] as $k) {
        $in[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $in['qty'] = (int)($_POST['qty'] ?? 1);
    $in['terms'] = isset($_POST['terms']);
    $in['start_now'] = isset($_POST['start_now']);
    if (!hash_equals($_SESSION['paydemo_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'co.err_plan';
    }
}

$in['billing'] = $in['billing'] === 'month' ? 'month' : 'year';
$plans = pd_plans();
$plan_ok = isset($plans[$in['plan']])
    && $in['qty'] >= $plans[$in['plan']]['qty_min'] && $in['qty'] <= $plans[$in['plan']]['qty_max']
    && pd_plan_price($in['plan'], $in['billing'], $in['qty']) !== null;

if (!$plan_ok) {
    pd_head(pd_t('co.title'), 'page-checkout');
    echo '<main class="wrap"><div class="flash">' . pd_t('co.err_plan') . '</div><a class="btn btn-primary" href="plans.php">' . pd_t('co.back') . '</a></main>';
    pd_foot();
    exit;
}

if ($in['country'] === '') {
    $in['country'] = PD_SELLER_COUNTRY;
}
$in['vat_number'] = strtoupper(preg_replace('/[\s.\-]/', '', $in['vat_number']));
$list = pd_plan_price($in['plan'], $in['billing'], $in['qty']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'co.err_email';
    }
    if ($in['name'] === '' || strlen($in['name']) > 240) {
        $errors[] = 'co.err_name';
    }
    if (!isset(pd_eu_countries()[$in['country']])) {
        $errors[] = 'co.err_country';
    }
    if ($in['vat_number'] !== '') {
        // Format check only. In production: check the number in VIES before applying reverse charge.
        $prefix = $in['country'] === 'GR' ? 'EL' : $in['country'];
        if (!preg_match('/^' . $prefix . '[0-9A-Z]{2,13}$/', $in['vat_number'])) {
            $errors[] = 'co.err_vat';
        }
        if ($in['company'] === '') {
            $errors[] = 'co.err_company';
        }
    } elseif (!$in['start_now']) {
        $errors[] = 'co.err_start';
    }
    if (!$in['terms']) {
        $errors[] = 'co.err_terms';
    }

    if (!$errors) {
        $tot = pd_totals($list, $in['country'], $in['vat_number']);
        $id = 'demo_' . bin2hex(random_bytes(6));
        // Keep the demo session small: only the last 10 orders
        $_SESSION['paydemo']['orders'] = array_slice($_SESSION['paydemo']['orders'], -9, null, true);
        $_SESSION['paydemo']['orders'][$id] = [
            'id' => $id, 'plan' => $in['plan'], 'qty' => $in['qty'], 'billing' => $in['billing'],
            'list' => $list, 'net' => $tot['net'], 'vat' => $tot['vat'], 'rate' => $tot['rate'],
            'total' => $tot['total'], 'mode' => $tot['mode'],
            'email' => $in['email'], 'name' => $in['name'], 'company' => $in['company'], 'country' => $in['country'],
            'vat_number' => $in['vat_number'], 'method' => 'ideal',
            'status' => 'open', 'created' => time(),
        ];
        header('Location: provider_mock.php?order=' . rawurlencode($id));
        exit;
    }
}

$tot = pd_totals($list, $in['country'], $in['vat_number']);
$renews = strtotime($in['billing'] === 'month' ? '+1 month' : '+1 year');
$countries = pd_eu_countries();
$plan_label = pd_plan_name($in['plan'], $in['qty']);

pd_head(pd_t('co.title'), 'page-checkout');
?>
    <main class="wrap">
        <p class="back-link"><a href="<?php echo pd_e(pd_url('plans.php', ['billing' => $in['billing']])); ?>">← <?php echo pd_t('co.back'); ?></a></p>
        <div class="page-head"><div><h1><?php echo pd_t('co.h1'); ?></h1></div></div>

        <?php if ($failed) { ?>
        <div class="flash flash-err" role="alert"><?php echo pd_t('co.failed'); ?></div>
        <?php } ?>
        <?php if ($errors) { ?>
        <div class="flash flash-err" role="alert">
            <?php echo pd_t('co.errors_title'); ?>
            <ul class="err-list"><?php foreach (array_unique($errors) as $e) { echo '<li>' . pd_t($e) . '</li>'; } ?></ul>
        </div>
        <?php } ?>

        <form class="checkout" method="post" action="checkout.php" novalidate>
            <input type="hidden" name="csrf" value="<?php echo pd_e($_SESSION['paydemo_csrf']); ?>">
            <input type="hidden" name="plan" value="<?php echo pd_e($in['plan']); ?>">
            <input type="hidden" name="billing" value="<?php echo pd_e($in['billing']); ?>">
            <input type="hidden" name="qty" value="<?php echo (int)$in['qty']; ?>">

            <aside class="card summary" aria-labelledby="sumTitle">
                <h2 id="sumTitle"><?php echo pd_t('co.summary'); ?></h2>
                <dl>
                    <div><dt><?php echo pd_t('co.plan'); ?></dt><dd><?php echo $plan_label; ?></dd></div>
                    <div><dt><?php echo pd_t('co.billing'); ?></dt><dd><?php echo pd_t('co.billing_' . $in['billing']); ?></dd></div>
                    <div><dt><?php echo pd_t('co.net'); ?></dt><dd id="sumNet"><?php echo pd_e(pd_money($tot['net'])); ?></dd></div>
                    <div><dt id="sumVatLabel"><?php echo $tot['mode'] === 'reverse' ? pd_t('co.reverse') : pd_t('co.vat', ['rate' => str_replace('.', pd_lang() === 'nl' ? ',' : '.', (string)$tot['rate']), 'country' => $in['country']]); ?></dt><dd id="sumVat"><?php echo pd_e(pd_money($tot['vat'])); ?></dd></div>
                    <div class="sum-total"><dt><?php echo pd_t('co.total'); ?></dt><dd id="sumTotal"><?php echo pd_e(pd_money($tot['total'])); ?></dd></div>
                </dl>
                <p class="muted small" id="reverseNote"<?php echo $tot['mode'] === 'reverse' ? '' : ' hidden'; ?>><?php echo pd_t('co.reverse_note'); ?></p>
                <p class="muted small" id="renewNote"><?php echo pd_t('co.renews', ['amount' => pd_money($tot['total']), 'date' => pd_date($renews), 'period' => html_entity_decode(pd_t('co.period_' . $in['billing']), ENT_QUOTES, 'UTF-8')]); ?></p>
                <?php if ($plans[$in['plan']]['proof']) { ?><p class="muted small"><?php echo pd_t('co.proof_note'); ?></p><?php } ?>
            </aside>

            <div class="checkout-main">
                <section class="card">
                    <h2><?php echo pd_t('co.details'); ?></h2>
                    <p class="muted small"><?php echo pd_t('co.details_hint'); ?></p>
                    <div class="fields">
                        <label class="field"><span><?php echo pd_t('co.email'); ?></span>
                            <input type="email" name="email" autocomplete="email" required value="<?php echo pd_e($in['email']); ?>"></label>
                        <label class="field"><span><?php echo pd_t('co.name'); ?></span>
                            <input type="text" name="name" autocomplete="name" required maxlength="120" value="<?php echo pd_e($in['name']); ?>"></label>
                        <label class="field"><span><?php echo pd_t('co.company'); ?></span>
                            <input type="text" name="company" autocomplete="organization" maxlength="120" value="<?php echo pd_e($in['company']); ?>"></label>
                        <label class="field"><span><?php echo pd_t('co.country'); ?></span>
                            <select name="country" id="country" autocomplete="country">
                                <?php foreach ($countries as $code => $name) { ?>
                                <option value="<?php echo $code; ?>"<?php echo $code === $in['country'] ? ' selected' : ''; ?>><?php echo pd_e($name); ?></option>
                                <?php } ?>
                            </select></label>
                        <label class="field field-wide"><span><?php echo pd_t('co.vat_number'); ?></span>
                            <input type="text" name="vat_number" id="vatNumber" maxlength="20" placeholder="NL123456789B01" value="<?php echo pd_e($in['vat_number']); ?>">
                            <small><?php echo pd_t('co.vat_hint'); ?></small></label>
                    </div>
                </section>

                <section class="card">
                    <h2><?php echo pd_t('co.method'); ?></h2>
                    <div class="method method-only" id="methodIdeal">
                        <span class="ideal-mark" aria-hidden="true">iD</span>
                        <span class="method-body">
                            <strong><?php echo pd_t('co.ideal'); ?></strong>
                            <small><?php echo pd_t('co.ideal_note'); ?></small>
                        </span>
                    </div>
                    <p class="muted small mandate-note"><?php echo pd_t('co.mandate_note'); ?></p>
                </section>

                <section class="card">
                    <label class="check"><input type="checkbox" name="terms"<?php echo $in['terms'] ? ' checked' : ''; ?>> <span><?php echo pd_raw('co.terms'); ?></span></label>
                    <label class="check"><input type="checkbox" name="start_now"<?php echo $in['start_now'] ? ' checked' : ''; ?>> <span><?php echo pd_t('co.start_now'); ?></span></label>
                    <button type="submit" class="btn btn-gold btn-pay" id="payBtn"><?php echo pd_t('co.pay', ['amount' => pd_money($tot['total'])]); ?></button>
                    <p class="muted small"><?php echo pd_t('co.pay_hint'); ?></p>
                </section>
            </div>
        </form>
    </main>
    <script>
    // Live update of the VAT lines when the country or VAT number changes (the server
    // computes the same again on submit; the browser's numbers are never trusted).
    (function () {
        var rates = <?php echo json_encode(pd_vat_rates()); ?>;
        var home = <?php echo json_encode(PD_SELLER_COUNTRY); ?>;
        var list = <?php echo json_encode($list); ?>;
        var nl = <?php echo json_encode(pd_lang() === 'nl'); ?>;
        var txtVat = <?php echo json_encode(html_entity_decode(pd_t('co.vat'), ENT_QUOTES, 'UTF-8')); ?>;
        var txtRev = <?php echo json_encode(html_entity_decode(pd_t('co.reverse'), ENT_QUOTES, 'UTF-8')); ?>;
        var txtPay = <?php echo json_encode(html_entity_decode(pd_t('co.pay'), ENT_QUOTES, 'UTF-8')); ?>;
        var txtRenew = <?php echo json_encode(html_entity_decode(pd_t('co.renews', ['date' => pd_date($renews), 'period' => html_entity_decode(pd_t('co.period_' . $in['billing']), ENT_QUOTES, 'UTF-8')]), ENT_QUOTES, 'UTF-8')); ?>;
        var country = document.getElementById('country');
        var vat = document.getElementById('vatNumber');
        function money(n) {
            var s = n.toFixed(2);
            if (nl) { return '€ ' + s.replace('.', ','); }
            return '€' + s;
        }
        function update() {
            var c = country.value;
            var hasVat = vat.value.replace(/[\s.\-]/g, '').length > 0;
            var net, v, total, label;
            if (hasVat && c !== home) {
                net = Math.round(list / (1 + rates[home] / 100) * 100) / 100;
                v = 0; total = net; label = txtRev;
            } else {
                var r = rates[c] || rates[home];
                net = Math.round(list / (1 + r / 100) * 100) / 100;
                v = Math.round((list - net) * 100) / 100; total = list;
                label = txtVat.replace('{rate}', nl ? String(r).replace('.', ',') : String(r)).replace('{country}', c);
            }
            document.getElementById('sumNet').textContent = money(net);
            document.getElementById('sumVat').textContent = money(v);
            document.getElementById('sumVatLabel').textContent = label;
            document.getElementById('sumTotal').textContent = money(total);
            document.getElementById('reverseNote').hidden = !(hasVat && c !== home);
            document.getElementById('payBtn').textContent = txtPay.replace('{amount}', money(total));
            document.getElementById('renewNote').textContent = txtRenew.replace('{amount}', money(total));
        }
        country.addEventListener('change', update);
        vat.addEventListener('input', update);
    })();
    </script>
<?php
pd_foot();
