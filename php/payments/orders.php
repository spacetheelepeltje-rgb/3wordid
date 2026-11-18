<?php
/**
 * Shared payment orders: create a Mollie payment for a product, record it,
 * and apply a 'paid' status (webhook or return page). Product key, amount
 * and period come from config at payment time and are stored on the row.
 *
 * Fulfillers are registered per product (see payments_register_fulfiller).
 * Bumperchat plates register in php/plate_billing.php.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mollie.php';

/** @var array<string, callable> */
function &payments_fulfillers(): array
{
    static $fns = [];
    return $fns;
}

function payments_register_fulfiller(string $product_key, callable $fn): void
{
    $fns = &payments_fulfillers();
    $fns[$product_key] = $fn;
}

function payments_schema_ready(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    $conn = get_db_conn();
    if (!$conn) {
        $ok = false;
        return $ok;
    }
    $r = @$conn->query("SHOW TABLES LIKE 'payments'");
    $ok = $r && $r->num_rows > 0;
    return $ok;
}

function payments_new_order_ref(): string
{
    return bin2hex(random_bytes(12));
}

/**
 * Insert a payment row and create the Mollie payment.
 * On success returns ['ok' => true, 'checkout_url' => ..., 'order_ref' => ..., 'payment' => row].
 */
function payments_start(string $product_key, int $user_id, array $opts = []): array
{
    $product = payments_product($product_key);
    if (!$product) {
        return ['ok' => false, 'error' => 'unknown_product'];
    }
    if (!payments_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }
    if (!payments_schema_ready()) {
        return ['ok' => false, 'error' => 'schema'];
    }

    $subject = (string) ($opts['subject'] ?? '');
    $wid_id = isset($opts['wid_id']) ? (int) $opts['wid_id'] : 0;
    $base = payments_public_base_url();
    if ($base === '' || !preg_match('#^https://#', $base)) {
        return ['ok' => false, 'error' => 'public_url'];
    }

    $order_ref = payments_new_order_ref();
    $amount = $product['amount'];
    $currency = $product['currency'];
    $return_path = (string) ($opts['return_path'] ?? '/payments/return.php');
    if (preg_match('/ref=$/', $return_path)) {
        $return_path .= rawurlencode($order_ref);
    } elseif (strpos($return_path, 'ref=') === false) {
        $return_path .= (strpos($return_path, '?') === false ? '?' : '&') . 'ref=' . rawurlencode($order_ref);
    }
    $redirectUrl = $base . $return_path;
    $webhookUrl = $base . '/payments/webhook.php';
    $description = payments_description($product, $subject !== '' ? $subject : $product_key);

    $conn = get_db_conn();
    $wid_db = $wid_id > 0 ? $wid_id : null;
    $subject_db = $subject !== '' ? $subject : null;
    $interval_unit = $product['interval'];
    $interval_count = (int) $product['interval_count'];
    $method = $product['method'];
    $status = 'open';
    $meta = json_encode([
        'product' => $product_key,
        'wid_id' => $wid_id,
        'subject' => $subject,
        'user_id' => $user_id,
    ]);

    $sql = "INSERT INTO payments (order_ref, user_id, wid_id, product_key, subject, amount, currency,
                interval_unit, interval_count, method, status, metadata_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('payments_start: prepare insert failed');
        return ['ok' => false, 'error' => 'db'];
    }
    $stmt->bind_param(
        'siisssssisss',
        $order_ref,
        $user_id,
        $wid_db,
        $product_key,
        $subject_db,
        $amount,
        $currency,
        $interval_unit,
        $interval_count,
        $method,
        $status,
        $meta
    );
    if (!$stmt->execute()) {
        $stmt->close();
        error_log('payments_start: insert failed');
        return ['ok' => false, 'error' => 'db'];
    }
    $local_id = (int) $conn->insert_id;
    $stmt->close();

    $created = mollie_create_payment([
        'amount' => $amount,
        'currency' => $currency,
        'description' => $description,
        'redirectUrl' => $redirectUrl,
        'webhookUrl' => $webhookUrl,
        'method' => $method,
        'metadata' => [
            'order_ref' => $order_ref,
            'product' => $product_key,
            'wid_id' => $wid_id,
            'subject' => $subject,
            'user_id' => $user_id,
        ],
    ]);
    if (!$created['ok'] || empty($created['data']['id']) || empty($created['data']['_links']['checkout']['href'])) {
        $st = $conn->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
        if ($st) {
            $st->bind_param('i', $local_id);
            $st->execute();
            $st->close();
        }
        return ['ok' => false, 'error' => 'mollie'];
    }

    $mollie_id = (string) $created['data']['id'];
    $checkout = (string) $created['data']['_links']['checkout']['href'];
    $mstatus = (string) ($created['data']['status'] ?? 'open');
    $st = $conn->prepare('UPDATE payments SET mollie_payment_id = ?, checkout_url = ?, status = ? WHERE id = ?');
    if (!$st) {
        error_log('payments_start: prepare update failed');
        return ['ok' => false, 'error' => 'db'];
    }
    $st->bind_param('sssi', $mollie_id, $checkout, $mstatus, $local_id);
    $st->execute();
    $st->close();

    return [
        'ok' => true,
        'error' => '',
        'checkout_url' => $checkout,
        'order_ref' => $order_ref,
        'mollie_payment_id' => $mollie_id,
    ];
}

function payments_by_ref(string $ref): ?array
{
    if (!preg_match('/^[a-f0-9]{24}$/', $ref)) {
        return null;
    }
    if (!payments_schema_ready()) {
        return null;
    }
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT * FROM payments WHERE order_ref = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $ref);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function payments_by_mollie_id(string $id): ?array
{
    if (!preg_match('/^tr_[A-Za-z0-9]+$/', $id)) {
        return null;
    }
    if (!payments_schema_ready()) {
        return null;
    }
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT * FROM payments WHERE mollie_payment_id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function payments_log_event(string $mollie_id, string $status): bool
{
    $conn = get_db_conn();
    $stmt = $conn->prepare('INSERT IGNORE INTO payment_events (mollie_payment_id, status_seen) VALUES (?, ?)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $mollie_id, $status);
    $stmt->execute();
    $new = $stmt->affected_rows > 0;
    $stmt->close();
    return $new;
}

/**
 * Fetch the payment from Mollie by id and, if paid, record it and run the
 * product fulfiller. Idempotent. Never uses a posted status.
 */
function payments_sync_mollie_id(string $mollie_id): ?array
{
    $got = mollie_get_payment($mollie_id);
    if (!$got['ok'] || empty($got['data']['id'])) {
        return null;
    }
    return payments_apply_mollie($got['data']);
}

function payments_sync_ref(string $ref): ?array
{
    $row = payments_by_ref($ref);
    if (!$row) {
        return null;
    }
    $mid = (string) ($row['mollie_payment_id'] ?? '');
    if ($mid === '') {
        return $row;
    }
    $synced = payments_sync_mollie_id($mid);
    return $synced ?: $row;
}

function payments_apply_mollie(array $mollie): ?array
{
    $mid = (string) ($mollie['id'] ?? '');
    $status = (string) ($mollie['status'] ?? '');
    if ($mid === '' || $status === '') {
        return null;
    }
    $row = payments_by_mollie_id($mid);
    if (!$row) {
        $ref = (string) ($mollie['metadata']['order_ref'] ?? '');
        if ($ref !== '') {
            $row = payments_by_ref($ref);
        }
    }
    if (!$row) {
        error_log('payments_apply_mollie: no local row for payment');
        return null;
    }

    payments_log_event($mid, $status);

    $conn = get_db_conn();
    $id = (int) $row['id'];
    if ((string) $row['status'] !== $status && $status !== 'paid') {
        $st = $conn->prepare('UPDATE payments SET status = ? WHERE id = ? AND status <> \'paid\'');
        if ($st) {
            $st->bind_param('si', $status, $id);
            $st->execute();
            $st->close();
            $row['status'] = $status;
        }
    }

    if ($status !== 'paid') {
        return payments_by_ref((string) $row['order_ref']) ?: $row;
    }

    if ((string) $row['status'] === 'paid' && !empty($row['paid_at'])) {
        return $row; // already fulfilled
    }

    $product = payments_product((string) $row['product_key']);
    if (!$product) {
        $product = [
            'key' => (string) $row['product_key'],
            'interval' => (string) ($row['interval_unit'] ?? 'year'),
            'interval_count' => (int) ($row['interval_count'] ?? 1),
            'amount' => (string) $row['amount'],
            'currency' => (string) $row['currency'],
        ];
    }

    $now = date('Y-m-d H:i:s');
    $from = $now;
    $fns = payments_fulfillers();
    $key = (string) $row['product_key'];
    $period_end = null;
    if (isset($fns[$key])) {
        $period_end = $fns[$key]($row, $product, $now);
    }
    if (!is_string($period_end) || $period_end === '') {
        $period_end = payments_period_end($from, $product);
    }

    $st = $conn->prepare(
        "UPDATE payments SET status = 'paid', paid_at = ?, period_start = ?, period_end = ? WHERE id = ? AND status <> 'paid'"
    );
    if ($st) {
        $st->bind_param('sssi', $now, $now, $period_end, $id);
        $st->execute();
        $st->close();
    }

    $mark = $conn->prepare(
        "UPDATE payment_events SET processed_at = ? WHERE mollie_payment_id = ? AND status_seen = 'paid' AND processed_at IS NULL"
    );
    if ($mark) {
        $mark->bind_param('ss', $now, $mid);
        $mark->execute();
        $mark->close();
    }

    if (function_exists('app_log')) {
        app_log('payment paid ref ' . $row['order_ref'] . ' product ' . $key, 'info', true);
    }

    return payments_by_ref((string) $row['order_ref']) ?: $row;
}

function payments_handle_webhook(string $mollie_id): bool
{
    $got = mollie_get_payment($mollie_id);
    if (!$got['ok'] || empty($got['data']['id'])) {
        throw new RuntimeException('mollie fetch failed');
    }
    $row = payments_apply_mollie($got['data']);
    return $row !== null;
}
