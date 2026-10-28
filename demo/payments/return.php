<?php
/**
 * DEMO: the return URL the customer lands on after the provider's payment page.
 *
 *   status=paid      -> subscription "active", back to the list with a green banner
 *   status=canceled  -> back to the plans page with a note
 *   status=failed    -> back to checkout (form filled in again) with a message
 *
 * !!! PRODUCTION: never trust ?status= (anyone can type it). The return page must look up the
 * payment at the provider by our own order id (or wait for it) and only SHOW the result.
 * The subscription is switched on by the provider's webhook (webhook.php, server to server,
 * signature checked / payment re-fetched from the API), which updates the database:
 * subscriptions + payments tables and google_users.user_type / max_3wids.
 * See docs/PAYMENTS.md, "Technical integration plan".
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
    // DEMO ONLY: in production this happens in the webhook, not here.
    $period = $order['billing'] === 'monthly' ? '+1 month' : '+1 year';
    $_SESSION['paydemo']['orders'][$id]['status'] = 'paid';
    $_SESSION['paydemo']['sub'] = [
        'plan' => $order['plan'],
        'qty' => $order['qty'],
        'billing' => $order['billing'],
        'currency' => $order['currency'],
        'total' => $order['total'],
        'net' => $order['net'],
        'vat' => $order['vat'],
        'method' => $order['method'],
        'mode' => $order['mode'],
        'region' => $order['region'],
        'started' => time(),
        'renews' => strtotime($period),
        'cancel_at_end' => false,
        'invoice' => '3WID-' . date('Y') . '-' . str_pad((string)random_int(1, 999), 4, '0', STR_PAD_LEFT),
    ];
    header('Location: index.php?welcome=1');
    exit;
}

if ($status === 'canceled') {
    $_SESSION['paydemo']['orders'][$id]['status'] = 'canceled';
    header('Location: ' . pd_url('plans.php', ['region' => $order['region'], 'billing' => $order['billing'], 'note' => 'canceled']));
    exit;
}

// failed (or anything unknown)
$_SESSION['paydemo']['orders'][$id]['status'] = 'failed';
header('Location: ' . pd_url('checkout.php', ['order' => $id, 'error' => 'failed']));
exit;
