<?php
/**
 * Checks Bumperchat billing helpers that do not need the database or Mollie:
 * E.164 phone numbers, amount formatting, period extension, access state.
 * Run from the repo root:  php tools/test_plate_billing.php
 * Not needed on the web server. Exit code 1 when something differs.
 */

require_once __DIR__ . '/../php/notify.php';
require_once __DIR__ . '/../php/payments/config.php';

// plate_access_state lives in plate_billing.php, which also loads the payments
// module. Those files do not open the database at load time.
require_once __DIR__ . '/../php/plate_billing.php';

$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}

// --- E.164 ---
$ok = notify_phone_normalize('');
expect('empty phone ok', $ok['ok'] && $ok['phone'] === null, true);

$ok = notify_phone_normalize('+31612345678');
expect('nl mobile', $ok['ok'] && $ok['phone'] === '+31612345678', true);

$ok = notify_phone_normalize('00 31 6 1234 5678');
expect('00 prefix and spaces', $ok['ok'] && $ok['phone'] === '+31612345678', true);

$ok = notify_phone_normalize('+31 (6) 1234-5678');
expect('punctuation', $ok['ok'] && $ok['phone'] === '+31612345678', true);

$ok = notify_phone_normalize('0612345678');
expect('local 06 rejected', $ok['ok'], false);

$ok = notify_phone_normalize('+31');
expect('too short rejected', $ok['ok'], false);

$ok = notify_phone_normalize('++31612345678');
expect('double plus rejected', $ok['ok'], false);

$post = notify_read_claim_post(['notify_phone' => '+31612345678']);
expect('phone without consent', $post['ok'], false);
expect('consent error key', $post['error'], 'bc.sms_consent_needed');

$post = notify_read_claim_post(['notify_phone' => '+31612345678', 'notify_sms_consent' => '1']);
expect('phone with consent', $post['ok'] && $post['phone'] === '+31612345678' && $post['consent'] === true, true);

$post = notify_read_claim_post(['notify_sms_consent' => '1']);
expect('consent without phone', $post['ok'], false);

expect('sms not configured', notify_sms_configured(), false);
expect('e164 helper', notify_phone_is_e164('+31612345678'), true);
expect('e164 helper empty', notify_phone_is_e164(''), false);

// --- amounts and periods ---
expect('amount 7.95', payments_amount_value('7.95'), '7.95');
expect('amount 7,95', payments_amount_value('7,95'), '7.95');
expect('amount 8', payments_amount_value(8), '8.00');

$product = ['interval' => 'year', 'interval_count' => 1];
$from = '2026-11-18 13:00:00';
expect('period +1 year from future', payments_period_end('2027-11-18 13:00:00', $product, strtotime($from)), '2028-11-18 13:00:00');
expect('period +1 year from past uses now', payments_period_end('2020-01-01 00:00:00', $product, strtotime($from)), '2027-11-18 13:00:00');

$month = ['interval' => 'month', 'interval_count' => 1];
expect('period +1 month', payments_period_end('2026-11-18 13:00:00', $month, strtotime('2026-11-01 00:00:00')), '2026-12-18 13:00:00');

// --- access state (no database) ---
$now = strtotime('2026-11-18 12:00:00');
expect('unbilled empty', plate_access_state([], $now), 'unbilled');
expect('unbilled no product', plate_access_state(['trial_expires' => '2026-12-18 12:00:00'], $now), 'unbilled');

$trial = ['billing_product' => 'bumperchat_plate', 'trial_expires' => '2026-12-18 12:00:00', 'paid_until' => null];
expect('trial active', plate_access_state($trial, $now), 'trial');
expect('trial days', plate_days_remaining($trial, $now), 30);
expect('trial not expired', plate_billing_requires_payment($trial, $now), false);

$expired = ['billing_product' => 'bumperchat_plate', 'trial_expires' => '2026-11-18 11:00:00', 'paid_until' => null];
expect('trial expired', plate_access_state($expired, $now), 'expired');
expect('expired requires payment', plate_billing_requires_payment($expired, $now), true);

$paid = ['billing_product' => 'bumperchat_plate', 'trial_expires' => '2026-11-01 12:00:00', 'paid_until' => '2027-11-18 12:00:00'];
expect('paid active', plate_access_state($paid, $now), 'paid');
expect('paid days', plate_days_remaining($paid, $now), 365);
expect('paid no paywall', plate_billing_requires_payment($paid, $now), false);

$year_ended = ['billing_product' => 'bumperchat_plate', 'paid_until' => '2026-11-18 11:59:59'];
expect('paid year ended', plate_access_state($year_ended, $now), 'expired');

if ($fail) {
    echo "$fail failed\n";
    exit(1);
}
echo "ok\n";
exit(0);
