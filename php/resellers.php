<?php
/**
 * Optional reseller-code attribution (site-owner view of who brought a user).
 *
 * Distinct from the older 3wordid_resellers kickback list. This module uses
 * the `resellers` table and nullable reseller_id columns on google_users
 * and payments. See docs/RESELLERS.md.
 *
 * Server-side validation is authoritative. An unknown or inactive code is
 * ignored and never blocks registration or payment.
 */

const RESELLER_COOKIE = '3WIDREF';
const RESELLER_COOKIE_DAYS = 30;
const RESELLER_SESSION_KEY = 'reseller_code';

/** Uppercase, trim, strip inner spaces. Empty string if the format is not valid. */
function reseller_normalize_code(string $raw): string
{
    $code = strtoupper(trim($raw));
    $code = preg_replace('/\s+/', '', $code) ?? '';
    return reseller_code_valid_format($code) ? $code : '';
}

function reseller_code_valid_format(string $code): bool
{
    return (bool) preg_match('/^[A-Z0-9-]{3,20}$/', $code);
}

/**
 * First valid reseller on the user wins; a later code is ignored.
 * Null or 0 means "none".
 */
function reseller_first_wins(?int $existing, ?int $incoming): ?int
{
    if ($existing !== null && $existing > 0) {
        return $existing;
    }
    if ($incoming !== null && $incoming > 0) {
        return $incoming;
    }
    return null;
}

/** Payment uses the user's reseller, or a newly entered one if the user had none. */
function reseller_payment_attribution(?int $user_reseller_id, ?int $entered_reseller_id): ?int
{
    return reseller_first_wins($user_reseller_id, $entered_reseller_id);
}

/**
 * Resolve a typed or recalled code.
 * @return array{id:?int,code:string,unknown:bool}
 */
function reseller_resolve_code(string $raw): array
{
    $trimmed = trim($raw);
    $code = reseller_normalize_code($raw);
    if ($code === '') {
        return ['id' => null, 'code' => '', 'unknown' => $trimmed !== ''];
    }
    $row = reseller_lookup_active($code);
    if (!$row) {
        return ['id' => null, 'code' => $code, 'unknown' => true];
    }
    return ['id' => (int) $row['id'], 'code' => $code, 'unknown' => false];
}

/** @return array{id:int,code:string,name?:string}|null */
function reseller_lookup_active(string $code): ?array
{
    if (isset($GLOBALS['_reseller_lookup']) && is_callable($GLOBALS['_reseller_lookup'])) {
        $row = ($GLOBALS['_reseller_lookup'])($code);
        return is_array($row) ? $row : null;
    }
    if (!reseller_schema_ready()) {
        return null;
    }
    $conn = function_exists('get_db_conn') ? get_db_conn() : null;
    if (!$conn) {
        return null;
    }
    $stmt = $conn->prepare('SELECT id, code, name FROM resellers WHERE code = ? AND active = 1 LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }
    return [
        'id' => (int) $row['id'],
        'code' => (string) $row['code'],
        'name' => (string) ($row['name'] ?? ''),
    ];
}

function reseller_schema_ready(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    if (isset($GLOBALS['_reseller_schema_ready'])) {
        $ok = (bool) $GLOBALS['_reseller_schema_ready'];
        return $ok;
    }
    $conn = function_exists('get_db_conn') ? get_db_conn() : null;
    if (!$conn) {
        $ok = false;
        return $ok;
    }
    $r = @$conn->query("SHOW TABLES LIKE 'resellers'");
    if (!$r || $r->num_rows === 0) {
        $ok = false;
        return $ok;
    }
    $c = @$conn->query("SHOW COLUMNS FROM google_users LIKE 'reseller_id'");
    $ok = $c && $c->num_rows > 0;
    return $ok;
}

function reseller_payments_column_ready(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    if (isset($GLOBALS['_reseller_payments_column_ready'])) {
        $ok = (bool) $GLOBALS['_reseller_payments_column_ready'];
        return $ok;
    }
    $conn = function_exists('get_db_conn') ? get_db_conn() : null;
    if (!$conn) {
        $ok = false;
        return $ok;
    }
    $c = @$conn->query("SHOW COLUMNS FROM payments LIKE 'reseller_id'");
    $ok = $c && $c->num_rows > 0;
    return $ok;
}

/** Capture ?ref=CODE into the session and a 30-day first-party cookie. */
function reseller_capture_ref(): void
{
    if (!isset($_GET['ref'])) {
        return;
    }
    $code = reseller_normalize_code((string) $_GET['ref']);
    if ($code === '') {
        return;
    }
    reseller_remember($code);
}

function reseller_remember(string $code): void
{
    $code = reseller_normalize_code($code);
    if ($code === '') {
        return;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[RESELLER_SESSION_KEY] = $code;
    }
    reseller_set_ref_cookie($code);
}

function reseller_set_ref_cookie(string $code): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    $expires = time() + (RESELLER_COOKIE_DAYS * 86400);
    setcookie(RESELLER_COOKIE, $code, reseller_cookie_options($expires));
}

function reseller_cookie_options(int $expires): array
{
    $secure = function_exists('request_is_https')
        ? request_is_https()
        : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    return [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

/** Posted field, then session, then the 30-day cookie. */
function reseller_remembered_code(): string
{
    $posted = reseller_normalize_code((string) ($_POST['reseller_code'] ?? ''));
    if ($posted !== '') {
        return $posted;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        $from_session = reseller_normalize_code((string) ($_SESSION[RESELLER_SESSION_KEY] ?? ''));
        if ($from_session !== '') {
            return $from_session;
        }
    }
    return reseller_normalize_code((string) ($_COOKIE[RESELLER_COOKIE] ?? ''));
}

function reseller_user_reseller_id(int $user_id): ?int
{
    if ($user_id <= 0 || !reseller_schema_ready()) {
        return null;
    }
    $conn = function_exists('get_db_conn') ? get_db_conn() : null;
    if (!$conn) {
        return null;
    }
    $stmt = $conn->prepare('SELECT reseller_id FROM google_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $id = isset($row['reseller_id']) ? (int) $row['reseller_id'] : 0;
    return $id > 0 ? $id : null;
}

/** Set reseller_id only when the column is still empty. */
function reseller_set_user_reseller(int $user_id, int $reseller_id): bool
{
    if ($user_id <= 0 || $reseller_id <= 0 || !reseller_schema_ready()) {
        return false;
    }
    $conn = function_exists('get_db_conn') ? get_db_conn() : null;
    if (!$conn) {
        return false;
    }
    $stmt = $conn->prepare('UPDATE google_users SET reseller_id = ? WHERE id = ? AND reseller_id IS NULL');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ii', $reseller_id, $user_id);
    $ok = $stmt->execute();
    $changed = $ok && $stmt->affected_rows > 0;
    $stmt->close();
    return $changed;
}

/**
 * Attach a code to the user if they have none yet. Unknown codes are not stored.
 * @return array{id:?int,code:string,unknown:bool,applied:bool}
 */
function reseller_apply_to_user(int $user_id, string $raw): array
{
    $resolved = reseller_resolve_code($raw);
    $existing = $user_id > 0 ? reseller_user_reseller_id($user_id) : null;
    $chosen = reseller_first_wins($existing, $resolved['id']);
    $applied = false;
    if ($user_id > 0 && $chosen !== null && ($existing === null || $existing <= 0)) {
        $applied = reseller_set_user_reseller($user_id, $chosen);
    }
    $resolved['id'] = $chosen;
    $resolved['applied'] = $applied;
    return $resolved;
}

/**
 * Optional "Have a reseller code?" field. $opts: code, unknown, id, class.
 */
function reseller_field_html(array $opts = []): string
{
    $code = (string) ($opts['code'] ?? reseller_remembered_code());
    if ($code !== '') {
        $norm = reseller_normalize_code($code);
        $code = $norm !== '' ? $norm : strtoupper(trim($code));
    }
    $unknown = !empty($opts['unknown']);
    $id = (string) ($opts['id'] ?? 'reseller_code');
    $class = (string) ($opts['class'] ?? 'reseller-opt bc-reseller');
    $open = ($code !== '' || $unknown) ? ' open' : '';
    $esc = static function (string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };
    $tr = static function (string $key, string $fallback) use ($esc): string {
        if (function_exists('t_plain')) {
            return $esc(t_plain($key));
        }
        return $esc($fallback);
    };
    $label = $tr('reseller.opt_label', 'Reseller code');
    $have = $tr('reseller.opt_have', 'Have a reseller code?');
    $hint = $tr('reseller.opt_hint', 'Optional.');
    $ph = $tr('reseller.opt_ph', 'YOUR-CODE');
    $unknown_txt = $tr('reseller.opt_unknown', 'Unknown reseller code');
    $note = $unknown
        ? '<p class="reseller-note bc-reseller-note" role="status">' . $unknown_txt . '</p>'
        : '';
    return '<details class="' . $esc($class) . '"' . $open . '>'
        . '<summary>' . $have . '</summary>'
        . '<label class="bc-label" for="' . $esc($id) . '">' . $label . '</label>'
        . '<input id="' . $esc($id) . '" name="reseller_code" class="bc-input" type="text" '
        . 'maxlength="20" autocapitalize="characters" spellcheck="false" autocomplete="off" '
        . 'placeholder="' . $ph . '" value="' . $esc($code) . '">'
        . '<p class="bc-meta reseller-hint">' . $hint . '</p>'
        . $note
        . '</details>';
}
