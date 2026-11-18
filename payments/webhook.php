<?php
/**
 * Mollie webhook (shared). Mollie POSTs id=tr_... when a payment changes.
 * The status in the POST body is ignored: this script fetches the payment
 * from the Mollie API by id and only then updates the database.
 *
 * Must be publicly reachable over HTTPS. No login, no HTML.
 */
require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/plate_billing.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$id = (string) ($_POST['id'] ?? '');
if (!preg_match('/^tr_[A-Za-z0-9]+$/', $id)) {
    http_response_code(400);
    echo 'bad id';
    exit;
}

try {
    payments_handle_webhook($id);
    http_response_code(200);
    echo 'ok';
} catch (Throwable $e) {
    error_log('payments webhook: ' . $e->getMessage());
    http_response_code(500);
    echo 'error';
}
