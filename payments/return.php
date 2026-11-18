<?php
/**
 * Generic payment return (shared). Bumperchat uses bumperchat/pay_return.php,
 * which sends the visitor here-compatible status page in the Bumperchat chrome.
 * This file exists so later 3WordID tiers can share the same return URL shape:
 *   /payments/return.php?ref=...
 */
require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/plate_billing.php';

$ref = (string) ($_GET['ref'] ?? '');
$product = '';
if (preg_match('/^[a-f0-9]{24}$/', $ref)) {
    $row = payments_sync_ref($ref);
    $product = (string) (($row['product_key'] ?? ''));
}
if ($product === BILLING_PRODUCT_PLATE || $product === '') {
    $q = $ref !== '' ? ('?ref=' . rawurlencode($ref)) : '';
    header('Location: ../bumperchat/pay_return.php' . $q);
    exit;
}
// Later: send other products to a 3WordID account return page.
header('Location: ../bumperchat/pay_return.php' . ($ref !== '' ? ('?ref=' . rawurlencode($ref)) : ''));
exit;
