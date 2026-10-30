<?php
/**
 * Account settings (3wid_settings.php): settings of the whole account, not of one 3WordID.
 * Now: Organization name. It is shown in the chat next to the 3WordIDs of this account, in the
 * same places as the car make and colour of a car chat (conversations list and chat top bar),
 * for the people chatting with this account, never for the account itself.
 * Later: single user / admin (organisation, paid) and payment details, each as another section.
 *
 * Needs sql/2026-10-30_org_name.sql (google_users.org_name and google_users_deleted.org_name).
 * Until that has run, org_name_ready() is false: the field is not shown and nothing changes.
 */

if (!defined('ORG_NAME_MAX')) { define('ORG_NAME_MAX', 100); }

/** True when the SQL for the organization name has run. Cached per request. */
function org_name_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = false;
    $conn = get_db_conn();
    if (!$conn || $conn->connect_error) {
        return $ready;
    }
    try {
        $ready = ($conn->query('SELECT org_name FROM google_users LIMIT 1') !== false);
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/**
 * Cleans a typed organization name: trimmed, control characters removed, runs of spaces made
 * one space. Returns [clean name, error key]; '' as name means "no organization name".
 */
function org_name_clean($raw): array
{
    $s = is_string($raw) ? $raw : '';
    if (!mb_check_encoding($s, 'UTF-8')) {
        return array('', 'acct.org_invalid');
    }
    $s = (string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', $s);
    $s = trim((string) preg_replace('/\s+/u', ' ', $s));
    if (mb_strlen($s, 'UTF-8') > ORG_NAME_MAX) {
        return array($s, 'acct.org_long');
    }
    return array($s, '');
}

/** Organization name of an account, '' when none (or the SQL has not run). Cached per request. */
function db_org_name_get($user_id): string
{
    static $cache = array();
    $user_id = (int) $user_id;
    if ($user_id < 1 || !org_name_ready()) {
        return '';
    }
    if (array_key_exists($user_id, $cache)) {
        return $cache[$user_id];
    }
    $name = '';
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT org_name FROM google_users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $name = trim((string) ($row['org_name'] ?? ''));
        $stmt->close();
    }
    $cache[$user_id] = $name;
    return $name;
}

/** Saves (or with '' clears) the organization name of an account. */
function db_org_name_set($user_id, string $name): bool
{
    $user_id = (int) $user_id;
    if ($user_id < 1 || !org_name_ready()) {
        return false;
    }
    $value = ($name === '') ? null : $name;
    $conn = get_db_conn();
    $stmt = $conn->prepare('UPDATE google_users SET org_name = ? WHERE id = ?');
    $ok = false;
    if ($stmt) {
        $stmt->bind_param('si', $value, $user_id);
        $ok = $stmt->execute();
        $stmt->close();
    }
    return $ok;
}

/** Name of the account holder for the settings page: full_name, else first + last name. */
function account_holder_name($user): string
{
    if (!is_array($user)) {
        return '';
    }
    $full = trim((string) ($user['full_name'] ?? ''));
    if ($full === '') {
        $full = trim(trim((string) ($user['first_name'] ?? '')) . ' ' . trim((string) ($user['last_name'] ?? '')));
    }
    if ($full === '') {
        $full = trim((string) ($user['name'] ?? ''));
    }
    return $full;
}

/** Account type for the settings page (plain text): empty / 0 = Free, 2 = Paid, else "Type n". */
function account_type_label($user): string
{
    $type = is_array($user) ? ($user['user_type'] ?? null) : null;
    if ($type === null || $type === '' || (int) $type === 0) {
        return t_plain('acct.type_free');
    }
    if ((int) $type === 2) {
        return t_plain('acct.type_paid');
    }
    return t_plain('acct.type_other', ['n' => (int) $type]);
}

function org_icon(int $size = 14): string
{
    return '<svg class="org-ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10"/><path d="M3 21h18"/><path d="M8 7h4M8 11h4M8 15h4"/></svg>';
}

/**
 * Organization name of the account that owns 3WordID $row, as a badge next to its words.
 * '' when that account has no organization name, or when it is one of the accounts in
 * $not_for (the viewer, or the account they chat as): same rule as the car badge.
 * $class: 'tb-org' (chat top bar) or 'cr-org' (conversations list).
 */
function org_badge($row, array $not_for = array(), string $class = 'tb-org'): string
{
    if (!is_array($row)) {
        return '';
    }
    $owner = (int) ($row['user_id'] ?? 0);
    if ($owner < 1) {
        return '';
    }
    foreach ($not_for as $uid) {
        if ((int) $uid > 0 && (int) $uid === $owner) {
            return '';
        }
    }
    $name = db_org_name_get($owner);
    if ($name === '') {
        return '';
    }
    $label = t('acct.org_badge_label');
    $safe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    return '<span class="' . ($class === 'cr-org' ? 'cr-org' : 'tb-org') . '" title="' . $label . ' ' . $safe . '">' . org_icon(14)
        . '<span class="tb-sr">' . $label . ' </span><span class="tb-org-text">' . $safe . '</span></span>';
}

/**
 * Everything shown next to a sender's 3WordID in the conversations list: car make + colour
 * (car chat) first, then the organization name of its account. '' when there is nothing.
 */
function sender_list_badges($row, array $not_for = array()): string
{
    return car_list_badge($row, $not_for) . org_badge($row, $not_for, 'cr-org');
}

/**
 * The same for the chat top bar. Both badges go in one wrapper, so on a phone they share
 * one small line under the words instead of taking two.
 */
function sender_topbar_badges($row, array $not_for = array()): string
{
    $html = car_topbar_badge($row, $not_for) . org_badge($row, $not_for, 'tb-org');
    return $html === '' ? '' : '<span class="tb-badges">' . $html . '</span>';
}

/* ------------------------------------------------------------------------------------------
 * Account & payment history (read-only)
 *
 * There is no payments / invoices table yet (checked against the schema of 2026-10-28).
 * What exists and belongs to an account:
 *   - google_users.created_at                 when the account was made
 *   - 3wordid_signup (email = md5 of the address, the same hash as google_users.email):
 *     subscription requests from 3wid_signup.php (date, payment method, status; no amount)
 * When real payments are stored (for example a Mollie payments table, see docs/PAYMENTS.md),
 * add them in account_payments(): the page then lists them and the "No payments yet" text goes.
 * Every entry: ['date' => 'Y-m-d H:i:s', 'what' => plain text, 'amount' => plain text or '',
 *               'status' => plain text or '', 'kind' => 'account' | 'request' | 'payment']
 * ---------------------------------------------------------------------------------------- */

/** Real payments of an account. None are stored yet, so always [] for now. */
function account_payments($user): array
{
    return array();
}

/** Subscription requests (3wordid_signup) sent with the e-mail address of this account. */
function account_signup_requests($user): array
{
    $hash = is_array($user) ? (string) ($user['email'] ?? '') : '';
    if ($hash === '') {
        return array();
    }
    $out = array();
    try {
        $conn = get_db_conn();
        $stmt = $conn->prepare('SELECT creation_datetime, payment_method, status FROM 3wordid_signup WHERE email = ? ORDER BY id DESC LIMIT 50');
        if ($stmt) {
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) {
                $method = trim((string) ($r['payment_method'] ?? ''));
                $status = (int) ($r['status'] ?? 0);
                $out[] = array(
                    'date'   => (string) ($r['creation_datetime'] ?? ''),
                    'what'   => $method !== '' ? t_plain('acct.h_request_method', ['method' => $method]) : t_plain('acct.h_request'),
                    'amount' => '',
                    'status' => $status === 1 ? t_plain('acct.h_status_received') : t_plain('acct.h_status_n', ['n' => $status]),
                    'kind'   => 'request',
                );
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('account_signup_requests: ' . $e->getMessage());
    }
    return $out;
}

/** Everything for the history list, newest first. */
function account_history($user): array
{
    $items = array_merge(account_payments($user), account_signup_requests($user));
    $created = is_array($user) ? (string) ($user['created_at'] ?? '') : '';
    if ($created !== '') {
        $items[] = array('date' => $created, 'what' => t_plain('acct.h_created'), 'amount' => '', 'status' => '', 'kind' => 'account');
    }
    usort($items, function ($a, $b) { return strcmp((string) $b['date'], (string) $a['date']); });
    return $items;
}

/** 'Y-m-d H:i:s' as 30-10-2026 16:05 (plain text), '' when empty or not a date. */
function account_date($value, bool $with_time = true): string
{
    $value = trim((string) $value);
    if ($value === '' || strpos($value, '0000-00-00') === 0) {
        return '';
    }
    $ts = strtotime($value);
    return $ts === false ? '' : date($with_time ? 'd-m-Y H:i' : 'd-m-Y', $ts);
}
