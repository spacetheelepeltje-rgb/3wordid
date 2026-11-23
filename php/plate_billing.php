<?php
/**
 * Bumperchat plate billing: one-month free trial, then a one-off yearly
 * iDEAL payment (product bumperchat_plate). Used by claim, chat and pay pages.
 */

require_once __DIR__ . '/payments/orders.php';
require_once __DIR__ . '/notify.php';

const BILLING_PRODUCT_PLATE = 'bumperchat_plate';
const BILLING_SOURCE_BUMPERCHAT = 'bumperchat';

function billing_schema_ready(): bool
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
    $r = @$conn->query("SHOW COLUMNS FROM 3wordid LIKE 'trial_expires'");
    $ok = $r && $r->num_rows > 0;
    return $ok;
}

/**
 * Access state for a 3WordID:
 *   unbilled — not a Bumperchat-billed plate (no paywall)
 *   trial    — free month still running
 *   paid     — paid period still running
 *   expired  — trial or paid period has ended
 */
function plate_access_state(?array $wid, ?int $now = null): string
{
    if (!$wid) {
        return 'unbilled';
    }
    $product = (string) ($wid['billing_product'] ?? '');
    if ($product === '') {
        return 'unbilled';
    }
    $now = $now ?? time();
    $paid = plate_time($wid['paid_until'] ?? null);
    if ($paid !== null && $paid > $now) {
        return 'paid';
    }
    $trial = plate_time($wid['trial_expires'] ?? null);
    if ($trial !== null && $trial > $now) {
        return 'trial';
    }
    return 'expired';
}

function plate_time($value): ?int
{
    if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
        return null;
    }
    $ts = strtotime((string) $value);
    return $ts === false ? null : $ts;
}

function plate_billing_requires_payment(?array $wid, ?int $now = null): bool
{
    return plate_access_state($wid, $now) === 'expired';
}

function plate_access_until(?array $wid): ?int
{
    $state = plate_access_state($wid);
    if ($state === 'paid') {
        return plate_time($wid['paid_until'] ?? null);
    }
    if ($state === 'trial') {
        return plate_time($wid['trial_expires'] ?? null);
    }
    return null;
}

/** Whole days remaining, at least 1 if still active and some time remains. */
function plate_days_remaining(?array $wid, ?int $now = null): ?int
{
    $until = plate_access_until($wid);
    if ($until === null) {
        return null;
    }
    $now = $now ?? time();
    if ($until <= $now) {
        return 0;
    }
    return (int) ceil(($until - $now) / 86400);
}

function plate_pay_url(string $words, string $prefix = ''): string
{
    return $prefix . 'pay.php?words=' . rawurlencode($words);
}

function plate_email_hash_for_user(array $user): string
{
    $plain = function_exists('get_session_email') ? get_session_email() : null;
    if (!is_string($plain) || $plain === '') {
        $plain = function_exists('email_store_plain') ? email_store_plain($user) : '';
    }
    if (is_string($plain) && $plain !== '') {
        return md5(strtolower(trim($plain)));
    }
    return (string) ($user['email'] ?? '');
}

function plate_trial_used_email(string $email_hash): bool
{
    if ($email_hash === '' || !billing_schema_ready()) {
        return false;
    }
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT id FROM billing_trials WHERE email_hash = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $email_hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool) $row;
}

function plate_trial_used_words(string $threeword): bool
{
    if ($threeword === '' || !billing_schema_ready()) {
        return false;
    }
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT id FROM billing_trials WHERE threeword = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $threeword);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool) $row;
}

/**
 * Claim a free plate 3WordID for a Bumperchat user.
 * Returns ['ok' => true, 'wid_id' => int, 'state' => trial|paid|expired|unbilled]
 * or ['ok' => false, 'error' => lang key].
 *
 * $notify: ['phone' => ?string, 'consent' => bool] already validated.
 *
 * Already-paying 3WordID accounts (user_type > 0) get the plate without a
 * Bumperchat trial or paywall (they can already create IDs with digits).
 * Everyone else gets one free month, once per plate and once per email; a
 * second claim is created already expired so they are sent to the pay page.
 */
function bumperchat_claim_plate(array $user, string $words, array $notify = []): array
{
    $words = function_exists('bumperchat_normalize_words')
        ? bumperchat_normalize_words($words)
        : strtolower(trim($words));
    if ($words === '') {
        return ['ok' => false, 'error' => 'bc.err_words'];
    }
    if (!billing_schema_ready()) {
        return ['ok' => false, 'error' => 'bc.err_schema'];
    }
    if (db_3wordid_exists($words)) {
        return ['ok' => false, 'error' => 'bc.err_taken'];
    }

    $user_id = (int) $user['id'];
    $rows = db_3wordid_list($user_id);
    $max = (int) ($user['max_3wids'] ?? 0) + 1;
    if (is_array($rows) && count($rows) >= $max) {
        return ['ok' => false, 'error' => 'bc.err_limit_ids'];
    }

    // Chat on, private, no forward URL. Digits are allowed: this is the Bumperchat plate claim.
    $new_id = db_3wordid_insert($user_id, $words, '', '', 0, 1, 1);
    if (!$new_id) {
        return ['ok' => false, 'error' => 'bc.err_claim_failed'];
    }
    $new_id = (int) $new_id;

    $phone = $notify['phone'] ?? null;
    $consent = !empty($notify['consent']) && is_string($phone) && $phone !== '';
    $consent_at = $consent ? date('Y-m-d H:i:s') : null;
    $consent_i = $consent ? 1 : 0;

    $paying = user_is_paying($user);
    $now = date('Y-m-d H:i:s');
    $source = BILLING_SOURCE_BUMPERCHAT;
    $product = $paying ? null : BILLING_PRODUCT_PLATE;
    $trial_start = null;
    $trial_expires = null;
    $grant_trial = false;

    if (!$paying) {
        $hash = plate_email_hash_for_user($user);
        $used = plate_trial_used_words($words) || ($hash !== '' && plate_trial_used_email($hash));
        if ($used) {
            // Already used the free month: reserve the plate and send them to pay.
            $trial_expires = $now;
        } else {
            $trial_start = $now;
            $trial_expires = date('Y-m-d H:i:s', strtotime('+1 month'));
            $grant_trial = true;
        }
    }

    $conn = get_db_conn();
    $sql = "UPDATE 3wordid
               SET claim_source = ?, billing_product = ?, trial_start = ?, trial_expires = ?,
                   notify_phone = ?, notify_sms_consent = ?, notify_sms_consent_at = ?
             WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('bumperchat_claim_plate: update prepare failed');
        return ['ok' => false, 'error' => 'bc.err_claim_failed'];
    }
    $stmt->bind_param(
        'sssssisii',
        $source,
        $product,
        $trial_start,
        $trial_expires,
        $phone,
        $consent_i,
        $consent_at,
        $new_id,
        $user_id
    );
    if (!$stmt->execute()) {
        $stmt->close();
        error_log('bumperchat_claim_plate: update failed');
        return ['ok' => false, 'error' => 'bc.err_claim_failed'];
    }
    $stmt->close();

    // A Bumperchat plate is made to message other accounts. The chat_open column defaults to 0
    // (closed: only your own 3WordIDs may message it, and it may only message out to your own),
    // and db_3wordid_insert() does not set it, so open it here. Without this a new plate
    // account got "closed" errors both when sending and when receiving (chat_access_error).
    if (function_exists('db_chat_open_set')) {
        db_chat_open_set($new_id, true);
    }
    // Store the optional mobile number encrypted (and clear the plaintext column).
    if (function_exists('plate_phone_store_save') && !empty($phone)) {
        plate_phone_store_save($conn, $new_id, is_string($phone) ? $phone : null);
    }

    if ($grant_trial) {
        $hash = plate_email_hash_for_user($user);
        $ins = $conn->prepare(
            'INSERT INTO billing_trials (email_hash, threeword, user_id, wid_id, source, product_key, started_at, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if ($ins) {
            $pkey = BILLING_PRODUCT_PLATE;
            $ins->bind_param('ssiissss', $hash, $words, $user_id, $new_id, $source, $pkey, $trial_start, $trial_expires);
            $ins->execute();
            $ins->close();
        }
    }

    $wid = db_3wordid_get($new_id) ?: ['id' => $new_id, 'billing_product' => $product, 'trial_expires' => $trial_expires, 'paid_until' => null];
    $state = plate_access_state($wid);
    // Waiting messages for this plate: move them into the normal inbox now,
    // even if the owner's trial is already used (they stay after a paywall).
    if (function_exists('pending_deliver_for_words')) {
        pending_deliver_for_words($words, $new_id, $user_id);
    }
    if (function_exists('app_log')) {
        app_log('bumperchat: claimed "' . $words . '" user ' . $user_id . ' state ' . $state, 'info', true);
    }
    return ['ok' => true, 'wid_id' => $new_id, 'state' => $state, 'error' => ''];
}

/** After a paid Bumperchat plate payment: extend paid_until, return the new end datetime. */
function plate_billing_fulfill(array $payment, array $product, string $now): string
{
    $wid_id = (int) ($payment['wid_id'] ?? 0);
    if ($wid_id <= 0) {
        return payments_period_end($now, $product);
    }
    $wid = db_3wordid_get($wid_id);
    if (!$wid) {
        return payments_period_end($now, $product);
    }
    $from = $now;
    foreach (['paid_until', 'trial_expires'] as $col) {
        $ts = plate_time($wid[$col] ?? null);
        if ($ts !== null && $ts > strtotime($from)) {
            $from = date('Y-m-d H:i:s', $ts);
        }
    }
    $end = payments_period_end($from, $product);
    $conn = get_db_conn();
    $st = $conn->prepare('UPDATE 3wordid SET paid_until = ? WHERE id = ?');
    if ($st) {
        $st->bind_param('si', $end, $wid_id);
        $st->execute();
        $st->close();
    }
    return $end;
}

payments_register_fulfiller(BILLING_PRODUCT_PLATE, 'plate_billing_fulfill');
