<?php
/*
 * Chat login
 * ----------
 * An owner (admin: a normal account with Google or email login) can turn on "Chat login"
 * for one of their 3WordIDs and give it a password. A member then logs in on
 * 3wid_chat_login.php with the three words and that password, and sees ONLY that
 * 3WordID's chat (3wid_chat.php). Only the owner can set or change the password.
 *
 * Member session ($_SESSION['chat_member']): ['wid' => 3wordid id, 'owner' => user id,
 * 'v' => chat_pw_version at login, 'at' => login time]. It never holds user_token, and
 * current_user_from_session() (php/session_boot.php) refuses any session that has
 * chat_member, so every owner page treats a member as "not logged in".
 *
 * Every member request checks the 3WordID again (chat_member_current): chat login still
 * on, same owner, same chat_pw_version. Changing the password or switching chat login off
 * raises chat_pw_version, so an active member session ends on its next request.
 *
 * Needs sql/2026-10-30_chat_login.sql. Until that has run, chat_login_ready() is false
 * and the feature is hidden everywhere.
 */

// Lockout settings (override in login/config.php with define(), 0 switches a limit off)
//   CHAT_LOGIN_MAX_FAILS_ID   wrong tries for one 3WordID before a lockout   (5)
//   CHAT_LOGIN_MAX_FAILS_IP   wrong tries from one IP address (any 3WordID)  (20; a school
//                             often shares one IP address, so this is higher)
//   CHAT_LOGIN_LOCK_MINUTES   window and lockout time in minutes             (15)
if (!defined('CHAT_PW_MIN')) { define('CHAT_PW_MIN', 8); }
if (!defined('CHAT_PW_MAX')) { define('CHAT_PW_MAX', 128); }

/**
 * May this account use Chat login for its 3WordIDs?
 * The ONE place that decides it. For now every logged-in owner may (returns true).
 * Later, account settings get a "single user / admin" switch and admin will be a paid
 * option: then check that here, for example user_is_paying($user) && admin mode on.
 */
function chat_login_allowed($user): bool
{
    return is_array($user) && (int) ($user['id'] ?? 0) > 0 && (int) ($user['user_type'] ?? 0) !== 9;
}

/** True when the SQL for chat login has run (columns on 3wordid + attempts table). Cached. */
function chat_login_ready(): bool
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
        $a = $conn->query('SELECT chat_login, chat_pw_hash, chat_pw_version FROM 3wordid LIMIT 1');
        $b = $conn->query('SELECT id, rate_key, created_at FROM 3wordid_chat_login_attempts LIMIT 1');
        $ready = ($a !== false && $b !== false);
    } catch (Throwable $e) {
        $ready = false;
    }
    $conn->close();
    return $ready;
}

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------

/** The 3WordID row with chat login fields, by id. null when not found. */
function db_chat_login_get($wid_id): ?array
{
    $wid_id = (int) $wid_id;
    if ($wid_id < 1) {
        return null;
    }
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT id, threeword, user_id, enabled, chat_login, chat_pw_hash, chat_pw_version FROM 3wordid WHERE id = ? LIMIT 1');
    if (!$stmt) {
        $conn->close();
        return null;
    }
    $stmt->bind_param('i', $wid_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}

/** Same, by the (normalized) three words. */
function db_chat_login_by_words(string $words): ?array
{
    if (!db_3wordid_hasThreeAlphabeticNumeric($words)) {
        return null;
    }
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT id, threeword, user_id, enabled, chat_login, chat_pw_hash, chat_pw_version FROM 3wordid WHERE threeword = ? LIMIT 1');
    if (!$stmt) {
        $conn->close();
        return null;
    }
    $stmt->bind_param('s', $words);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}

/** The owner account of a 3WordID (only what chat_login_allowed needs). null when gone. */
function db_chat_login_owner(int $user_id): ?array
{
    if ($user_id < 1) {
        return null;
    }
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT id, user_type FROM google_users WHERE id = ? LIMIT 1');
    $row = null;
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
    $conn->close();
    return $row;
}

/** [3wordid id => true] for the owner's 3WordIDs that have chat login on (list page). */
function db_chat_login_map($user_id): array
{
    $user_id = (int) $user_id;
    if ($user_id < 1 || !chat_login_ready()) {
        return array();
    }
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT id FROM 3wordid WHERE user_id = ? AND chat_login = 1');
    $out = array();
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $out[(int) $r['id']] = true;
        }
        $stmt->close();
    }
    $conn->close();
    return $out;
}

/** Set a new password (and turn chat login on). Ends any member session. Owner check included. */
function db_chat_login_set_password($wid_id, $user_id, string $hash): bool
{
    $wid_id = (int) $wid_id;
    $user_id = (int) $user_id;
    $conn = get_db_conn();
    $stmt = $conn->prepare('UPDATE 3wordid SET chat_pw_hash = ?, chat_login = 1, chat_pw_version = chat_pw_version + 1 WHERE id = ? AND user_id = ?');
    $ok = false;
    if ($stmt) {
        $stmt->bind_param('sii', $hash, $wid_id, $user_id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
    }
    $conn->close();
    return $ok;
}

/** Turn chat login on (only when a password is set) or off. Ends any member session. */
function db_chat_login_set_on($wid_id, $user_id, bool $on): bool
{
    $wid_id = (int) $wid_id;
    $user_id = (int) $user_id;
    $flag = $on ? 1 : 0;
    $conn = get_db_conn();
    $sql = $on
        ? 'UPDATE 3wordid SET chat_login = ?, chat_pw_version = chat_pw_version + 1 WHERE id = ? AND user_id = ? AND chat_pw_hash IS NOT NULL'
        : 'UPDATE 3wordid SET chat_login = ?, chat_pw_version = chat_pw_version + 1 WHERE id = ? AND user_id = ?';
    $stmt = $conn->prepare($sql);
    $ok = false;
    if ($stmt) {
        $stmt->bind_param('iii', $flag, $wid_id, $user_id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
    }
    $conn->close();
    return $ok;
}

// ---------------------------------------------------------------------------
// Failed attempts and lockout
// ---------------------------------------------------------------------------

function chat_login_limit(string $name, int $default): int
{
    return defined($name) ? max(0, (int) constant($name)) : $default;
}

/** Keyed hash for the attempts table: never the IP or the words themselves. */
function chat_login_rate_key(string $kind, string $value): string
{
    $salt = defined('MSG_RATE_SALT') ? (string) MSG_RATE_SALT
        : ((defined('WRITEPASSWORD') ? WRITEPASSWORD : '') . '|' . (defined('DATABASE') ? DATABASE : '') . '|3wid-rate');
    return hash_hmac('sha256', 'chatlogin|' . $kind . '|' . $value, $salt);
}

/** The two keys of one login try: ['id' => key for the typed words, 'ip' => key for the IP]. */
function chat_login_rate_keys(string $words): array
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    // IPv6: count per /64 (same as msg_visitor_key)
    if (strpos($ip, ':') !== false) {
        $bin = @inet_pton($ip);
        if ($bin !== false && strlen($bin) === 16) {
            $ip = bin2hex(substr($bin, 0, 8)) . '::/64';
        }
    }
    return array('id' => chat_login_rate_key('id', $words), 'ip' => chat_login_rate_key('ip', $ip));
}

function db_chat_login_fail_count(string $key, int $minutes): int
{
    $since = date('Y-m-d H:i:s', time() - $minutes * 60);
    $conn = get_db_conn();
    $stmt = $conn->prepare('SELECT COUNT(*) AS n FROM 3wordid_chat_login_attempts WHERE rate_key = ? AND created_at > ?');
    $n = 0;
    if ($stmt) {
        $stmt->bind_param('ss', $key, $since);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $n = (int) ($row['n'] ?? 0);
        $stmt->close();
    }
    $conn->close();
    return $n;
}

/** True when the typed words or this IP address are locked out right now. */
function chat_login_locked(array $keys): bool
{
    $minutes = max(1, chat_login_limit('CHAT_LOGIN_LOCK_MINUTES', 15));
    $max_id = chat_login_limit('CHAT_LOGIN_MAX_FAILS_ID', 5);
    $max_ip = chat_login_limit('CHAT_LOGIN_MAX_FAILS_IP', 20);
    if ($max_id > 0 && db_chat_login_fail_count($keys['id'], $minutes) >= $max_id) {
        return true;
    }
    if ($max_ip > 0 && db_chat_login_fail_count($keys['ip'], $minutes) >= $max_ip) {
        return true;
    }
    return false;
}

/** Store one failed try (both keys) and forget tries older than a day. */
function chat_login_record_failure(array $keys): void
{
    $now = date('Y-m-d H:i:s');
    $old = date('Y-m-d H:i:s', time() - 86400);
    $conn = get_db_conn();
    $stmt = $conn->prepare('INSERT INTO 3wordid_chat_login_attempts (rate_key, created_at) VALUES (?, ?), (?, ?)');
    if ($stmt) {
        $stmt->bind_param('ssss', $keys['id'], $now, $keys['ip'], $now);
        $stmt->execute();
        $stmt->close();
    }
    $stmt = $conn->prepare('DELETE FROM 3wordid_chat_login_attempts WHERE created_at < ?');
    if ($stmt) {
        $stmt->bind_param('s', $old);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

/** After a good login: the 3WordID's own counter starts again (the IP counter stays). */
function chat_login_clear_failures(array $keys): void
{
    $conn = get_db_conn();
    $stmt = $conn->prepare('DELETE FROM 3wordid_chat_login_attempts WHERE rate_key = ?');
    if ($stmt) {
        $stmt->bind_param('s', $keys['id']);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

// ---------------------------------------------------------------------------
// Checking a login and the member session
// ---------------------------------------------------------------------------

/** Is this row open for a member login right now? (the owner account is checked too) */
function chat_login_row_open($row): bool
{
    if (!is_array($row) || (int) ($row['chat_login'] ?? 0) !== 1 || (string) ($row['chat_pw_hash'] ?? '') === '') {
        return false;
    }
    return chat_login_allowed(db_chat_login_owner((int) $row['user_id']));
}

/**
 * Check words + password. Returns the 3WordID row, or null. Always does one password_verify,
 * also for unknown words, so the answer takes about as long either way.
 */
function chat_login_check(string $words, string $password): ?array
{
    static $dummy = null;
    $row = db_chat_login_by_words($words);
    $hash = ($row && (string) ($row['chat_pw_hash'] ?? '') !== '') ? (string) $row['chat_pw_hash'] : '';
    if ($hash === '') {
        if ($dummy === null) {
            $dummy = password_hash('not-a-real-password-' . bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
        }
        password_verify($password, $dummy);
        return null;
    }
    $ok = password_verify($password, $hash);
    if (!$ok || !chat_login_row_open($row)) {
        return null;
    }
    return $row;
}

/** Start a fresh member session for this 3WordID (new session id, nothing of an old login kept). */
function chat_member_start(array $row): void
{
    $lang = $_SESSION['lang'] ?? null; // keep the chosen language, nothing else
    $_SESSION = array();
    session_regenerate_id(true);
    if (is_string($lang)) {
        $_SESSION['lang'] = $lang;
    }
    $_SESSION['chat_member'] = array(
        'wid'   => (int) $row['id'],
        'owner' => (int) $row['user_id'],
        'v'     => (int) $row['chat_pw_version'],
        'at'    => time(),
    );
    $_SESSION['action_token'] = bin2hex(random_bytes(32));
}

/** True when this session is a member (chat login) session, valid or not. */
function chat_member_in_session(): bool
{
    return !empty($_SESSION['chat_member']) && is_array($_SESSION['chat_member']);
}

/**
 * The 3WordID row of the logged-in member, or null. Checks on every request that chat login
 * is still on, the password version is the same and the owner is the same; otherwise the
 * member session is ended.
 */
function chat_member_current(): ?array
{
    if (!chat_member_in_session() || !chat_login_ready()) {
        return null;
    }
    $s = $_SESSION['chat_member'];
    $row = db_chat_login_get((int) ($s['wid'] ?? 0));
    if (!$row
        || (int) $row['user_id'] !== (int) ($s['owner'] ?? -1)
        || (int) $row['chat_pw_version'] !== (int) ($s['v'] ?? -1)
        || !chat_login_row_open($row)) {
        chat_member_end();
        return null;
    }
    return $row;
}

/** Page guard for member pages: the row, or a redirect to the chat login page. */
function chat_member_require(): array
{
    $was_member = chat_member_in_session();
    $row = chat_member_current();
    if ($row === null) {
        header('Cache-Control: no-store');
        header('Location: 3wid_chat_login.php' . ($was_member ? '?ended=1' : ''));
        exit;
    }
    return $row;
}

/** End the member session (logout). */
function chat_member_end(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_app_session();
    }
    $lang = $_SESSION['lang'] ?? null;
    $_SESSION = array();
    $params = session_get_cookie_params();
    setcookie(session_name(), '', array(
        'expires'  => time() - 3600,
        'path'     => $params['path'] ?? '/',
        'domain'   => $params['domain'] ?? '',
        'secure'   => $params['secure'] ?? true,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_destroy();
    // A fresh, empty session for the rest of this request (login form token, notes)
    start_app_session();
    session_regenerate_id(true);
    if (is_string($lang)) {
        $_SESSION['lang'] = $lang;
    }
}

/** Check a new password from the admin form. Returns '' or the lang key of the problem. */
function chat_password_error(string $pw, string $confirm): string
{
    $len = function_exists('mb_strlen') ? mb_strlen($pw, 'UTF-8') : strlen($pw);
    if ($len < CHAT_PW_MIN) {
        return 'cl.pw_short';
    }
    if ($len > CHAT_PW_MAX) {
        return 'cl.pw_long';
    }
    if (!hash_equals($pw, $confirm)) {
        return 'cl.pw_mismatch';
    }
    return '';
}
