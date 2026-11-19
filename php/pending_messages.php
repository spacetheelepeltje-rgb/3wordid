<?php
/**
 * Bumperchat: messages to unregistered licence plates.
 *
 * A logged-in member can leave a message for a valid plate (country + plate
 * → three words via bumperchat_plate_to_words) that has no claimed 3WordID
 * yet. Rows live in pending_messages until the plate is claimed or 120 days
 * pass. At most 3 waiting messages per sender per plate; expired rows do
 * not count towards that limit.
 *
 * When the plate is claimed (bumperchat_claim_plate), non-expired waiting
 * rows are copied into 3wordid_messages in original order with their
 * original created_at, then marked delivered. When the owner opens the
 * conversation, existing read tracking also sets status to read.
 *
 * Statuses: waiting, delivered, read, expired. After 120 days unclaimed,
 * the body is cleared and a minimal record is kept so the sender still
 * sees "expired".
 *
 * Cleanup is lazy (on sender/claim access). An optional cron script is
 * tools/cleanup_pending_messages.php; the site works without scheduling
 * it. Run sql/2026-11-19_pending_messages.sql once; until then the form
 * is shown but cannot store a message.
 *
 * This file does not open the database at load time and does not require
 * php/functions.php (to avoid circular includes).
 */

if (!defined('PENDING_MESSAGE_DAYS')) {
    define('PENDING_MESSAGE_DAYS', 120);
}
if (!defined('PENDING_MESSAGE_MAX')) {
    define('PENDING_MESSAGE_MAX', 3);
}

function pending_table(): string
{
    return 'pending_messages';
}

function pending_ttl_days(): int
{
    return max(1, (int) PENDING_MESSAGE_DAYS);
}

function pending_max_per_plate(): int
{
    return max(1, (int) PENDING_MESSAGE_MAX);
}

function pending_statuses(): array
{
    return ['waiting', 'delivered', 'read', 'expired'];
}

/** True when the pending_messages table exists. Cached after a real check. */
function pending_schema_ready(?mysqli $conn = null): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    $owns = false;
    if (!$conn instanceof mysqli && function_exists('get_db_conn')) {
        $conn = @get_db_conn();
        $owns = $conn instanceof mysqli;
    }
    if (!$conn instanceof mysqli) {
        return false;
    }
    $found = false;
    try {
        $table = pending_table();
        $stmt = $conn->prepare(
            'SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $table);
            $stmt->execute();
            $found = (bool) $stmt->get_result()->fetch_row();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('pending_schema_ready: ' . $e->getMessage());
        $found = false;
    }
    if ($owns) {
        $conn->close();
    }
    $ok = $found;
    return $ok;
}

function pending_datetime(int $ts): string
{
    return date('Y-m-d H:i:s', $ts);
}

function pending_ts($value): int
{
    if (is_int($value)) {
        return $value > 0 ? $value : 0;
    }
    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return 0;
    }
    $ts = strtotime($value);
    return $ts !== false ? $ts : 0;
}

function pending_expires_at(int $created_ts): string
{
    return pending_datetime((int) strtotime('+' . pending_ttl_days() . ' days', $created_ts));
}

function pending_normalize_words(string $words): string
{
    if (function_exists('bumperchat_normalize_words')) {
        return bumperchat_normalize_words($words);
    }
    if (function_exists('chat_normalize_words')) {
        return chat_normalize_words($words);
    }
    return strtolower(trim(preg_replace('/\s+/u', ' ', $words)));
}

function pending_key(string $words): string
{
    return 'p:' . pending_normalize_words($words);
}

function pending_is_expired_at(array $row, int $now): bool
{
    $status = (string) ($row['status'] ?? '');
    if ($status === 'expired') {
        return true;
    }
    if ($status !== 'waiting') {
        return false;
    }
    $exp = pending_ts($row['expires_at'] ?? 0);
    return $exp > 0 && $exp <= $now;
}

/**
 * Apply lazy expiry to one row. Waiting rows past expires_at become
 * expired and lose their message body. Other statuses are left alone.
 */
function pending_expire_row(array $row, int $now): array
{
    if (!pending_is_expired_at($row, $now) || (string) ($row['status'] ?? '') === 'expired') {
        $row['expired_now'] = false;
        return $row;
    }
    $when = pending_datetime($now);
    $row['status'] = 'expired';
    $row['message'] = null;
    $row['expired_at'] = $when;
    $row['status_changed_at'] = $when;
    $row['expired_now'] = true;
    return $row;
}

/** Expire every due row in a list. Returns [rows, changed_rows]. */
function pending_expire_list(array $rows, int $now): array
{
    $out = [];
    $changed = [];
    foreach ($rows as $row) {
        $next = pending_expire_row($row, $now);
        if (!empty($next['expired_now'])) {
            $changed[] = $next;
        }
        unset($next['expired_now']);
        $out[] = $next;
    }
    return [$out, $changed];
}

/**
 * How many of these rows (already expired-in-memory) still count towards
 * the 3-per-plate cap: only waiting. Expired / delivered / read do not.
 */
function pending_count_toward_limit(array $rows, int $sender_id, string $words, int $now): int
{
    $sender_id = (int) $sender_id;
    $words = pending_normalize_words($words);
    $n = 0;
    foreach ($rows as $row) {
        if ((int) ($row['sender_user_id'] ?? 0) !== $sender_id) {
            continue;
        }
        if (pending_normalize_words((string) ($row['threeword'] ?? '')) !== $words) {
            continue;
        }
        $row = pending_expire_row($row, $now);
        if ((string) ($row['status'] ?? '') === 'waiting') {
            $n++;
        }
    }
    return $n;
}

function pending_remaining(int $used): int
{
    return max(0, pending_max_per_plate() - max(0, $used));
}

function pending_can_send(int $used): bool
{
    return pending_remaining($used) > 0;
}

/**
 * Waiting, not-yet-expired rows for a plate, oldest first (created_at, then id).
 */
function pending_rows_for_delivery(array $rows, string $words, int $now): array
{
    $words = pending_normalize_words($words);
    $out = [];
    foreach ($rows as $row) {
        if (pending_normalize_words((string) ($row['threeword'] ?? '')) !== $words) {
            continue;
        }
        $row = pending_expire_row($row, $now);
        if ((string) ($row['status'] ?? '') === 'waiting') {
            $out[] = $row;
        }
    }
    usort($out, function ($a, $b) {
        $ta = pending_ts($a['created_at'] ?? 0);
        $tb = pending_ts($b['created_at'] ?? 0);
        if ($ta !== $tb) {
            return $ta <=> $tb;
        }
        return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
    });
    return $out;
}

/** Mark a waiting row delivered, linking it to the new 3wordid_messages id. */
function pending_apply_delivery(array $row, int $message_id, int $wid_id, int $now): array
{
    $when = pending_datetime($now);
    $row['status'] = 'delivered';
    $row['delivered_at'] = $when;
    $row['status_changed_at'] = $when;
    $row['delivered_message_id'] = $message_id > 0 ? $message_id : null;
    $row['delivered_wid_id'] = $wid_id > 0 ? $wid_id : null;
    return $row;
}

/** Mark a delivered row read (owner opened the conversation). */
function pending_apply_read(array $row, int $now): array
{
    if ((string) ($row['status'] ?? '') !== 'delivered') {
        return $row;
    }
    $when = pending_datetime($now);
    $row['status'] = 'read';
    $row['read_at'] = $when;
    $row['status_changed_at'] = $when;
    return $row;
}

/**
 * Group status for a set of pending rows about one plate, after expiry:
 * waiting if any still wait; else delivered if any unread-delivered;
 * else read if any read; else expired.
 */
function pending_group_status(array $rows): array
{
    $has = ['waiting' => false, 'delivered' => false, 'read' => false, 'expired' => false];
    $changed = ['waiting' => 0, 'delivered' => 0, 'read' => 0, 'expired' => 0];
    foreach ($rows as $row) {
        $st = (string) ($row['status'] ?? '');
        if (!isset($has[$st])) {
            continue;
        }
        $has[$st] = true;
        $ts = pending_ts($row['status_changed_at'] ?? $row['created_at'] ?? 0);
        if ($ts > $changed[$st]) {
            $changed[$st] = $ts;
        }
    }
    if ($has['waiting']) {
        return ['status' => 'waiting', 'status_changed_at' => pending_datetime($changed['waiting'])];
    }
    if ($has['delivered']) {
        return ['status' => 'delivered', 'status_changed_at' => pending_datetime($changed['delivered'])];
    }
    if ($has['read']) {
        return ['status' => 'read', 'status_changed_at' => pending_datetime($changed['read'])];
    }
    return ['status' => 'expired', 'status_changed_at' => pending_datetime($changed['expired'])];
}

function pending_item_from_row(array $row): array
{
    $st = (string) ($row['status'] ?? 'waiting');
    $body = $row['message'] ?? null;
    if ($st === 'expired' && ($body === null || $body === '')) {
        $body = '';
    }
    return [
        'id' => (int) ($row['id'] ?? 0),
        'title' => (string) ($row['title'] ?? ''),
        'message' => (string) $body,
        'mine' => true,
        'sent_at' => (string) ($row['created_at'] ?? ''),
        'read_at' => $st === 'read' ? (string) ($row['read_at'] ?? '') : ($st === 'delivered' ? null : ''),
        'pending_id' => (int) ($row['id'] ?? 0),
        'pending_status' => $st,
    ];
}

/**
 * Group a sender's pending rows into conversation-shaped arrays
 * (same keys as chat_conversations, plus pending_* fields).
 * $from_wid_id 0 = all of the sender's pending conversations.
 */
function pending_group_conversations(array $rows, int $sender_id, int $from_wid_id, int $now): array
{
    $sender_id = (int) $sender_id;
    $from_wid_id = (int) $from_wid_id;
    $by = [];
    foreach ($rows as $row) {
        if ((int) ($row['sender_user_id'] ?? 0) !== $sender_id) {
            continue;
        }
        if ($from_wid_id > 0 && (int) ($row['from_wid_id'] ?? 0) !== $from_wid_id) {
            continue;
        }
        $row = pending_expire_row($row, $now);
        unset($row['expired_now']);
        $words = pending_normalize_words((string) ($row['threeword'] ?? ''));
        if ($words === '') {
            continue;
        }
        $by[$words][] = $row;
    }
    $out = [];
    foreach ($by as $words => $group_rows) {
        usort($group_rows, function ($a, $b) {
            $ta = pending_ts($a['created_at'] ?? 0);
            $tb = pending_ts($b['created_at'] ?? 0);
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });
        $meta = pending_group_status($group_rows);
        $last = $group_rows[count($group_rows) - 1];
        $wid = 0;
        foreach ($group_rows as $r) {
            $d = (int) ($r['delivered_wid_id'] ?? 0);
            if ($d > 0) {
                $wid = $d;
            }
        }
        $items = array_map('pending_item_from_row', $group_rows);
        $name = pending_display_name($last, $words);
        $out[pending_key($words)] = [
            'key' => pending_key($words),
            'name' => $name,
            'words' => $words,
            'visitor' => false,
            'deleted' => false,
            'pending' => true,
            'pending_status' => $meta['status'],
            'status_changed_at' => $meta['status_changed_at'],
            'wid' => $wid,
            'from_wid_id' => (int) ($last['from_wid_id'] ?? 0),
            'country' => (string) ($last['country'] ?? ''),
            'region' => (string) ($last['region'] ?? ''),
            'plate' => (string) ($last['plate'] ?? ''),
            'unread' => 0,
            'items' => $items,
        ];
    }
    return $out;
}

function pending_display_name(array $row, string $words): string
{
    $plate = trim((string) ($row['plate'] ?? ''));
    $country = strtoupper(trim((string) ($row['country'] ?? '')));
    if ($plate !== '') {
        return $country !== '' ? ($country . ' · ' . $plate) : $plate;
    }
    return $words;
}

/**
 * Sort key bucket: 0 = delivered/read/expired (top, by status change),
 * 1 = ordinary conversations (middle, by last activity),
 * 2 = still waiting (bottom, by last activity).
 */
function pending_conversation_bucket(array $c): int
{
    $st = (string) ($c['pending_status'] ?? '');
    if (in_array($st, ['delivered', 'read', 'expired'], true)) {
        return 0;
    }
    if ($st === 'waiting') {
        return 2;
    }
    return 1;
}

function pending_conversation_time(array $c): int
{
    $st = (string) ($c['pending_status'] ?? '');
    if (in_array($st, ['delivered', 'read', 'expired'], true)) {
        $ts = pending_ts($c['status_changed_at'] ?? 0);
        if ($ts > 0) {
            return $ts;
        }
    }
    $items = $c['items'] ?? [];
    if ($items) {
        $last = $items[count($items) - 1];
        $ts = pending_ts($last['sent_at'] ?? 0);
        if ($ts > 0) {
            return $ts;
        }
        return (int) ($last['id'] ?? 0);
    }
    return 0;
}

function pending_sort_conversations(array $conversations): array
{
    uasort($conversations, function ($a, $b) {
        $ba = pending_conversation_bucket($a);
        $bb = pending_conversation_bucket($b);
        if ($ba !== $bb) {
            return $ba <=> $bb;
        }
        return pending_conversation_time($b) <=> pending_conversation_time($a);
    });
    return $conversations;
}

/**
 * Merge pending groups into a normal chat_conversations() list.
 * Delivered/read groups attach to the existing partner conversation
 * when the recipient 3WordID is known, so they are not listed twice.
 */
function pending_merge_conversations(array $normal, array $pending_groups): array
{
    foreach ($pending_groups as $g) {
        $wid = (int) ($g['wid'] ?? 0);
        $key = 'w' . $wid;
        if ($wid > 0 && isset($normal[$key])) {
            $normal[$key]['pending_status'] = $g['pending_status'];
            $normal[$key]['status_changed_at'] = $g['status_changed_at'];
            continue;
        }
        $normal[$g['key']] = $g;
    }
    return pending_sort_conversations($normal);
}

function pending_title_from_message(string $message): string
{
    if (function_exists('chat_title_from_message')) {
        return chat_title_from_message($message);
    }
    $message = trim($message);
    if (function_exists('mb_substr')) {
        $t = mb_substr($message, 0, 40);
        return mb_strlen($message) > 40 ? $t . '…' : $t;
    }
    return strlen($message) > 40 ? substr($message, 0, 40) . '…' : $message;
}

// ---------------------------------------------------------------------------
// Database helpers
// ---------------------------------------------------------------------------

function pending_save_expired_rows(mysqli $conn, array $changed): void
{
    if (!$changed) {
        return;
    }
    $stmt = $conn->prepare(
        'UPDATE pending_messages
            SET status = ?, message = NULL, expired_at = ?, status_changed_at = ?
          WHERE id = ? AND status = \'waiting\''
    );
    if (!$stmt) {
        return;
    }
    foreach ($changed as $row) {
        $status = 'expired';
        $expired_at = (string) ($row['expired_at'] ?? '');
        $changed_at = (string) ($row['status_changed_at'] ?? $expired_at);
        $id = (int) ($row['id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $stmt->bind_param('sssi', $status, $expired_at, $changed_at, $id);
        $stmt->execute();
    }
    $stmt->close();
}

/** Expire due waiting rows (optionally narrowed). Returns how many were expired. */
function pending_expire_due(?mysqli $conn = null, int $sender_id = 0, string $words = '', ?int $now = null): int
{
    if ($now === null) {
        $now = time();
    }
    $owns = false;
    if (!$conn instanceof mysqli && function_exists('get_db_conn')) {
        $conn = get_db_conn();
        $owns = $conn instanceof mysqli;
    }
    if (!$conn instanceof mysqli || !pending_schema_ready($conn)) {
        if ($owns && $conn instanceof mysqli) {
            $conn->close();
        }
        return 0;
    }
    $sql = 'SELECT * FROM pending_messages WHERE status = \'waiting\' AND expires_at <= ?';
    $types = 's';
    $when = pending_datetime($now);
    $params = [$when];
    if ($sender_id > 0) {
        $sql .= ' AND sender_user_id = ?';
        $types .= 'i';
        $params[] = $sender_id;
    }
    $words = pending_normalize_words($words);
    if ($words !== '') {
        $sql .= ' AND threeword = ?';
        $types .= 's';
        $params[] = $words;
    }
    $stmt = $conn->prepare($sql);
    $rows = [];
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();
    }
    [$expired_list, $changed] = pending_expire_list($rows, $now);
    unset($expired_list);
    pending_save_expired_rows($conn, $changed);
    if ($owns) {
        $conn->close();
    }
    return count($changed);
}

function pending_count_waiting(mysqli $conn, int $sender_id, string $words, ?int $now = null): int
{
    if ($now === null) {
        $now = time();
    }
    pending_expire_due($conn, $sender_id, $words, $now);
    $words = pending_normalize_words($words);
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS n FROM pending_messages
          WHERE sender_user_id = ? AND threeword = ? AND status = \'waiting\''
    );
    $n = 0;
    if ($stmt) {
        $stmt->bind_param('is', $sender_id, $words);
        if ($stmt->execute()) {
            $row = $stmt->get_result()->fetch_assoc();
            $n = (int) ($row['n'] ?? 0);
        }
        $stmt->close();
    }
    return $n;
}

/**
 * Recent pending messages, for the same rate limits as normal chat.
 * $where is a trusted SQL fragment using only indexed columns + created_at.
 */
function pending_count_recent(mysqli $conn, string $where, string $types, array $params, int $cap): int
{
    $cap = max(0, (int) $cap);
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS n FROM (
            SELECT 1 FROM pending_messages WHERE ' . $where . ' LIMIT ' . ($cap + 1) . '
         ) t'
    );
    $n = 0;
    if ($stmt) {
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if ($stmt->execute()) {
            $row = $stmt->get_result()->fetch_assoc();
            $n = (int) ($row['n'] ?? 0);
        }
        $stmt->close();
    }
    return $n;
}

/**
 * Rate-limit a pending send the same way as a normal member message.
 * Pair-per-hour is counted on this sender 3WordID (pending +, when possible,
 * already stored 3wordid_messages). Recipient-per-day is counted on the
 * plate words (pending waiting + delivered today).
 */
function pending_rate_error(mysqli $conn, int $from_wid, string $words, ?int $now = null): string
{
    if (!function_exists('msg_limit')) {
        return '';
    }
    $from_wid = (int) $from_wid;
    $words = pending_normalize_words($words);
    $max = msg_limit('MSG_LIMIT_PAIR_PER_HOUR', 20);
    if ($max > 0 && $from_wid > 0) {
        $n = pending_count_recent(
            $conn,
            'from_wid_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            'i',
            [$from_wid],
            $max
        );
        if (function_exists('db_3wordid_message_count_recent')) {
            $n += db_3wordid_message_count_recent(
                '3wid_from_id = ? AND creation_date > NOW() - INTERVAL 1 HOUR',
                'i',
                [$from_wid],
                $max
            );
        }
        if ($n >= $max) {
            return 'chat.err_rate_pair';
        }
    }
    $max = msg_limit('MSG_LIMIT_RECIPIENT_PER_DAY', 200);
    if ($max > 0 && $words !== '') {
        $n = pending_count_recent(
            $conn,
            'threeword = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)',
            's',
            [$words],
            $max
        );
        if ($n >= $max) {
            return 'chat.err_rate_recipient';
        }
    }
    return '';
}

/** Insert a waiting pending message. Returns ['ok'=>bool, 'error'=>key, 'id'=>int, 'remaining'=>int]. */
function pending_insert(array $data, ?int $now = null, ?mysqli $conn = null): array
{
    if ($now === null) {
        $now = time();
    }
    $sender_id = (int) ($data['sender_user_id'] ?? 0);
    $from_wid = (int) ($data['from_wid_id'] ?? 0);
    $words = pending_normalize_words((string) ($data['threeword'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));
    $title = (string) ($data['title'] ?? '');
    if ($title === '') {
        $title = pending_title_from_message($message);
    }
    if ($sender_id < 1 || $from_wid < 1 || $words === '' || $message === '') {
        return ['ok' => false, 'error' => 'chat.err_empty', 'id' => 0, 'remaining' => 0];
    }
    if (function_exists('mb_strlen') ? mb_strlen($message) > 5000 : strlen($message) > 5000) {
        $message = function_exists('mb_substr') ? mb_substr($message, 0, 5000) : substr($message, 0, 5000);
    }

    $owns = false;
    if (!$conn instanceof mysqli && function_exists('get_db_conn')) {
        $conn = get_db_conn();
        $owns = $conn instanceof mysqli;
    }
    if (!$conn instanceof mysqli || !pending_schema_ready($conn)) {
        if ($owns && $conn instanceof mysqli) {
            $conn->close();
        }
        return ['ok' => false, 'error' => 'pending.err_schema', 'id' => 0, 'remaining' => 0];
    }

    $used = pending_count_waiting($conn, $sender_id, $words, $now);
    if (!pending_can_send($used)) {
        if ($owns) {
            $conn->close();
        }
        return ['ok' => false, 'error' => 'pending.limit_reached', 'id' => 0, 'remaining' => 0];
    }
    $rate = pending_rate_error($conn, $from_wid, $words, $now);
    if ($rate !== '') {
        if ($owns) {
            $conn->close();
        }
        return ['ok' => false, 'error' => $rate, 'id' => 0, 'remaining' => pending_remaining($used)];
    }

    $country = strtoupper(substr((string) ($data['country'] ?? ''), 0, 8));
    $region = strtoupper(substr((string) ($data['region'] ?? ''), 0, 8));
    $plate = strtoupper(substr((string) ($data['plate'] ?? ''), 0, 32));
    $created = pending_datetime($now);
    $expires = pending_expires_at($now);
    $status = 'waiting';
    $stmt = $conn->prepare(
        'INSERT INTO pending_messages
            (threeword, sender_user_id, from_wid_id, country, region, plate, title, message,
             status, created_at, expires_at, status_changed_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        if ($owns) {
            $conn->close();
        }
        return ['ok' => false, 'error' => 'pending.err_schema', 'id' => 0, 'remaining' => pending_remaining($used)];
    }
    $stmt->bind_param(
        'siisssssssss',
        $words,
        $sender_id,
        $from_wid,
        $country,
        $region,
        $plate,
        $title,
        $message,
        $status,
        $created,
        $expires,
        $created
    );
    $ok = $stmt->execute();
    $id = $ok ? (int) $conn->insert_id : 0;
    if (!$ok) {
        error_log('pending_insert: ' . $stmt->error);
    }
    $stmt->close();
    if ($owns) {
        $conn->close();
    }
    $remaining = pending_remaining($used + ($ok ? 1 : 0));
    return ['ok' => $ok && $id > 0, 'error' => $ok ? '' : 'pending.err_schema', 'id' => $id, 'remaining' => $remaining];
}

function pending_insert_delivered_message(mysqli $conn, array $row, int $to_wid, int $to_user_id): int
{
    $from_id = (int) ($row['sender_user_id'] ?? 0);
    $from_wid = (int) ($row['from_wid_id'] ?? 0);
    $title = (string) ($row['title'] ?? '');
    $message = (string) ($row['message'] ?? '');
    $created = (string) ($row['created_at'] ?? '');
    $terms = 1;
    $has_date = !function_exists('db_3wordid_messages_has_column') || db_3wordid_messages_has_column('creation_date');
    if ($has_date && $created !== '') {
        $stmt = $conn->prepare(
            'INSERT INTO 3wordid_messages
                (from_id, 3wid_id, 3wid_from_id, to_id, title, message, terms, creation_date, read_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('iiiissis', $from_id, $to_wid, $from_wid, $to_user_id, $title, $message, $terms, $created);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO 3wordid_messages
                (from_id, 3wid_id, 3wid_from_id, to_id, title, message, terms)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('iiiissi', $from_id, $to_wid, $from_wid, $to_user_id, $title, $message, $terms);
    }
    $ok = $stmt->execute();
    $id = $ok ? (int) $conn->insert_id : 0;
    if (!$ok) {
        error_log('pending_insert_delivered_message: ' . $stmt->error);
    }
    $stmt->close();
    return $id;
}

/**
 * Deliver non-expired waiting messages for a newly claimed plate, in
 * original order, keeping original timestamps. Returns how many were delivered.
 */
function pending_deliver_for_words(string $words, int $to_wid, int $to_user_id, ?int $now = null, ?mysqli $conn = null): int
{
    if ($now === null) {
        $now = time();
    }
    $words = pending_normalize_words($words);
    $to_wid = (int) $to_wid;
    $to_user_id = (int) $to_user_id;
    if ($words === '' || $to_wid < 1 || $to_user_id < 1) {
        return 0;
    }
    $owns = false;
    if (!$conn instanceof mysqli && function_exists('get_db_conn')) {
        $conn = get_db_conn();
        $owns = $conn instanceof mysqli;
    }
    if (!$conn instanceof mysqli || !pending_schema_ready($conn)) {
        if ($owns && $conn instanceof mysqli) {
            $conn->close();
        }
        return 0;
    }

    pending_expire_due($conn, 0, $words, $now);

    $delivered = 0;
    $queue = [];
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'SELECT * FROM pending_messages
              WHERE threeword = ? AND status = \'waiting\'
              ORDER BY created_at ASC, id ASC
              FOR UPDATE'
        );
        if (!$stmt) {
            throw new RuntimeException('prepare failed');
        }
        $stmt->bind_param('s', $words);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        $queue = pending_rows_for_delivery($rows, $words, $now);
        $upd = $conn->prepare(
            'UPDATE pending_messages
                SET status = ?, delivered_at = ?, status_changed_at = ?,
                    delivered_message_id = ?, delivered_wid_id = ?
              WHERE id = ? AND status = \'waiting\''
        );
        if (!$upd) {
            throw new RuntimeException('update prepare failed');
        }
        $status = 'delivered';
        $when = pending_datetime($now);
        foreach ($queue as $row) {
            $msg_id = pending_insert_delivered_message($conn, $row, $to_wid, $to_user_id);
            if ($msg_id < 1) {
                throw new RuntimeException('insert message failed');
            }
            $id = (int) $row['id'];
            $upd->bind_param('sssiii', $status, $when, $when, $msg_id, $to_wid, $id);
            if (!$upd->execute()) {
                throw new RuntimeException('mark delivered failed');
            }
            $delivered++;
        }
        $upd->close();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('pending_deliver_for_words: ' . $e->getMessage());
        $delivered = 0;
    }

    if ($delivered > 0 && function_exists('notify_on_new_message')) {
        $from = (int) ($queue[0]['sender_user_id'] ?? 0);
        notify_on_new_message($to_wid, $from);
    }
    if ($owns) {
        $conn->close();
    }
    return $delivered;
}

/** After the owner marks messages read, set matching pending rows to read. */
function pending_mark_read_by_message_ids(array $ids, ?int $now = null, ?mysqli $conn = null): int
{
    if ($now === null) {
        $now = time();
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($i) {
        return $i > 0;
    })));
    if (!$ids) {
        return 0;
    }
    $owns = false;
    if (!$conn instanceof mysqli && function_exists('get_db_conn')) {
        $conn = get_db_conn();
        $owns = $conn instanceof mysqli;
    }
    if (!$conn instanceof mysqli || !pending_schema_ready($conn)) {
        if ($owns && $conn instanceof mysqli) {
            $conn->close();
        }
        return 0;
    }
    $when = pending_datetime($now);
    $in = implode(',', $ids);
    $ok = $conn->query(
        "UPDATE pending_messages
            SET status = 'read', read_at = '" . $conn->real_escape_string($when) . "',
                status_changed_at = '" . $conn->real_escape_string($when) . "'
          WHERE status = 'delivered' AND delivered_message_id IN (" . $in . ")"
    );
    $n = $ok ? (int) $conn->affected_rows : 0;
    if ($owns) {
        $conn->close();
    }
    return $n;
}

/** Sync delivered → read for a conversation pair the owner just opened. */
function pending_mark_read_for_pair(int $to_wid, int $from_wid, ?int $now = null, ?mysqli $conn = null): int
{
    $to_wid = (int) $to_wid;
    $from_wid = (int) $from_wid;
    if ($to_wid < 1 || $from_wid < 1) {
        return 0;
    }
    $owns = false;
    if (!$conn instanceof mysqli && function_exists('get_db_conn')) {
        $conn = get_db_conn();
        $owns = $conn instanceof mysqli;
    }
    if (!$conn instanceof mysqli || !pending_schema_ready($conn)) {
        if ($owns && $conn instanceof mysqli) {
            $conn->close();
        }
        return 0;
    }
    $ids = [];
    $stmt = $conn->prepare(
        'SELECT id FROM 3wordid_messages
          WHERE 3wid_id = ? AND 3wid_from_id = ? AND read_at IS NOT NULL'
    );
    if ($stmt) {
        $stmt->bind_param('ii', $to_wid, $from_wid);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $ids[] = (int) $row['id'];
            }
        }
        $stmt->close();
    }
    $n = pending_mark_read_by_message_ids($ids, $now, $conn);
    if ($owns) {
        $conn->close();
    }
    return $n;
}

/** Catch-up: if a delivered message already has read_at, mark the pending row read. */
function pending_sync_reads_for_sender(int $sender_id, ?int $now = null, ?mysqli $conn = null): int
{
    $sender_id = (int) $sender_id;
    if ($sender_id < 1) {
        return 0;
    }
    $owns = false;
    if (!$conn instanceof mysqli && function_exists('get_db_conn')) {
        $conn = get_db_conn();
        $owns = $conn instanceof mysqli;
    }
    if (!$conn instanceof mysqli || !pending_schema_ready($conn)) {
        if ($owns && $conn instanceof mysqli) {
            $conn->close();
        }
        return 0;
    }
    if ($now === null) {
        $now = time();
    }
    $ids = [];
    $stmt = $conn->prepare(
        'SELECT p.delivered_message_id AS id
           FROM pending_messages p
           INNER JOIN 3wordid_messages m ON m.id = p.delivered_message_id
          WHERE p.sender_user_id = ? AND p.status = \'delivered\'
            AND m.read_at IS NOT NULL AND p.delivered_message_id IS NOT NULL'
    );
    if ($stmt) {
        $stmt->bind_param('i', $sender_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $ids[] = (int) $row['id'];
            }
        }
        $stmt->close();
    }
    $n = pending_mark_read_by_message_ids($ids, $now, $conn);
    if ($owns) {
        $conn->close();
    }
    return $n;
}

function pending_fetch_for_sender(mysqli $conn, int $sender_id, int $from_wid_id = 0): array
{
    $sql = 'SELECT * FROM pending_messages WHERE sender_user_id = ?';
    $types = 'i';
    $params = [$sender_id];
    if ($from_wid_id > 0) {
        $sql .= ' AND from_wid_id = ?';
        $types .= 'i';
        $params[] = $from_wid_id;
    }
    $sql .= ' ORDER BY created_at ASC, id ASC';
    $stmt = $conn->prepare($sql);
    $rows = [];
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();
    }
    return $rows;
}

/**
 * Refresh expiry + read status, then return conversation groups for one
 * of the sender's 3WordIDs (or all of them when $from_wid_id is 0).
 */
function pending_conversations_for_sender(int $sender_id, int $from_wid_id = 0, ?int $now = null, ?mysqli $conn = null): array
{
    $sender_id = (int) $sender_id;
    if ($sender_id < 1) {
        return [];
    }
    if ($now === null) {
        $now = time();
    }
    $owns = false;
    if (!$conn instanceof mysqli && function_exists('get_db_conn')) {
        $conn = get_db_conn();
        $owns = $conn instanceof mysqli;
    }
    if (!$conn instanceof mysqli || !pending_schema_ready($conn)) {
        if ($owns && $conn instanceof mysqli) {
            $conn->close();
        }
        return [];
    }
    pending_expire_due($conn, $sender_id, '', $now);
    pending_sync_reads_for_sender($sender_id, $now, $conn);
    $rows = pending_fetch_for_sender($conn, $sender_id, $from_wid_id);
    [$rows] = pending_expire_list($rows, $now);
    $groups = pending_group_conversations($rows, $sender_id, $from_wid_id, $now);
    if ($owns) {
        $conn->close();
    }
    return $groups;
}

/** Resolve country + plate (or pre-validated words) into a pending target. */
function pending_resolve_target(array $post): array
{
    $empty = [
        'ok' => false,
        'error' => 'bc.err_country',
        'words' => '',
        'country' => '',
        'region' => '',
        'plate' => '',
    ];
    if (!function_exists('bumperchat_formats') || !function_exists('bumperchat_plate_to_words')) {
        $empty['error'] = 'pending.err_schema';
        return $empty;
    }
    $formats = bumperchat_formats();
    $countries = $formats['countries'] ?? [];
    $by = [];
    foreach ($countries as $c) {
        $by[$c['code']] = $c;
    }
    $country = strtoupper(trim((string) ($post['country'] ?? '')));
    $region = strtoupper(trim((string) ($post['region'] ?? '')));
    $plate = trim((string) ($post['plate'] ?? ''));
    if ($country === '' || !isset($by[$country])) {
        return $empty;
    }
    $entry = $by[$country];
    if (!empty($entry['regions']) && $region === '') {
        $empty['error'] = 'bc.err_region';
        $empty['country'] = $country;
        $empty['plate'] = $plate;
        return $empty;
    }
    $words = bumperchat_plate_to_words($entry, $plate, $region);
    if ($words === '') {
        $empty['error'] = 'bc.plate_invalid';
        $empty['country'] = $country;
        $empty['region'] = $region;
        $empty['plate'] = $plate;
        return $empty;
    }
    $expected = pending_normalize_words((string) ($post['words'] ?? $post['threeword'] ?? ''));
    if ($expected !== '' && $expected !== $words) {
        // Prefill words from a lookup: the plate must produce the same three words.
        // If the user changed the plate, the new words win (they picked a different car).
    }
    return [
        'ok' => true,
        'error' => '',
        'words' => $words,
        'country' => $country,
        'region' => $region,
        'plate' => function_exists('bumperchat_format_plate') ? bumperchat_format_plate($entry, $plate) : strtoupper($plate),
        'entry' => $entry,
    ];
}

/**
 * Cron / CLI: expire every due waiting row. Returns the number expired.
 * Safe to run often; only waiting rows with expires_at <= now are touched.
 */
function pending_cleanup_expired(?int $now = null, ?mysqli $conn = null): int
{
    return pending_expire_due($conn, 0, '', $now);
}
