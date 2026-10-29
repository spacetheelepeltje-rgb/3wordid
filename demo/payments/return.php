<?php
/**
 * DEMO: the redirectUrl the customer lands on after the Mollie checkout.
 *
 *   status=paid      -> subscription "active", back to the list with a green banner
 *   status=canceled  -> back to the plans page with a note
 *   status=failed    -> back to checkout (form filled in again) with a message
 *
 * !!! PRODUCTION: never trust ?status= (anyone can type it). The return page looks up the
 * payment by our own order ref (GET /v2/payments/{id} or our payments table) and only SHOWS
 * the result. The webhook (subscribe_webhook.php) is the only place that switches anything on:
 * on the paid first payment it reads the mandate (SEPA Direct Debit, created from the iDEAL
 * payment) and creates the Mollie subscription with startDate = today + 1 period.
 * See docs/PAYMENTS.md, section 4.
 */
require __DIR__ . '/_demo.php';

$id = (string)($_GET['order'] ?? '');
$status = (string)($_GET['status'] ?? '');
$order = $_SESSION['paydemo']['orders'][$id] ?? null;

if (!$order) {
    header('Location: plans.php');
    exit;
}

if ($status === 'paid') {
    $bank = (string)($_GET['bank'] ?? '');
    if (!in_array($bank, pd_ideal_banks(), true)) {
        header('Location: ' . pd_url('provider_mock.php', ['order' => $id, 'bank_error' => 1]));
        exit;
    }
    // DEMO ONLY: in production this happens in the webhook, not here.
    $period = $order['billing'] === 'month' ? '+1 month' : '+1 year';
    $invoice = '3WID-' . date('Y') . '-' . str_pad((string)random_int(1, 999), 4, '0', STR_PAD_LEFT);
    $_SESSION['paydemo']['orders'][$id]['status'] = 'paid';
    $_SESSION['paydemo']['sub'] = [
        'plan' => $order['plan'],
        'qty' => $order['qty'],
        'billing' => $order['billing'],
        'total' => $order['total'],
        'net' => $order['net'],
        'vat' => $order['vat'],
        'mode' => $order['mode'],
        'bank' => $bank,
        // Fake, masked IBAN for the mandate (Mollie returns the real consumer account in the mandate details)
        'iban' => 'NL·· ' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $bank) . 'XXXX', 0, 4)) . ' ···· ' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT),
        'started' => time(),
        'renews' => strtotime($period),
        'cancel_at_end' => false,
        'invoices' => [
            ['no' => $invoice, 'date' => time(), 'total' => $order['total'], 'method' => 'ideal'],
        ],
    ];
    header('Location: index.php?welcome=1');
    exit;
}

if ($status === 'canceled') {
    $_SESSION['paydemo']['orders'][$id]['status'] = 'canceled';
    header('Location: ' . pd_url('plans.php', ['billing' => $order['billing'], 'note' => 'canceled']));
    exit;
}

// failed (or anything unknown)
$_SESSION['paydemo']['orders'][$id]['status'] = 'failed';
header('Location: ' . pd_url('checkout.php', ['order' => $id, 'error' => 'failed']));
exit;
