<?php
/**
 * One first-party session cookie:
 * HttpOnly, Secure (on HTTPS), SameSite=Lax
 *
 * Existing table column used: google_users.token
 */

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    session_name('3WIDSESS');

    session_set_cookie_params([
        'lifetime' => 0,      // browser session; use 60 * 60 * 24 * 14 for 14 days
        'path'     => '/',
        'domain'   => '',     // current host only
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
}

function establish_user_session(mysqli $conn, int $userId): string
{
    $rawToken = bin2hex(random_bytes(32));

    $stmt = $conn->prepare('UPDATE google_users SET token = ? WHERE id = ?');
    $stmt->bind_param('si', $rawToken, $userId);
    $stmt->execute();
    $stmt->close();

    record_last_login($conn, $userId);

    session_regenerate_id(true);

    $_SESSION['user_id']    = $userId;
    $_SESSION['user_token'] = $rawToken;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    unset($_SESSION['action_token']); // new action token after login

    return $rawToken;
}

/**
 * True when google_users has the given column. Cached per request.
 * Used so new columns (like last_login) are only used after the SQL
 * migration in sql/ has been run on the server.
 */
function google_users_has_column(mysqli $conn, string $column): bool
{
    static $cache = [];
    if (array_key_exists($column, $cache)) {
        return $cache[$column];
    }

    $has = false;
    try {
        $stmt = $conn->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
             LIMIT 1'
        );
        if ($stmt) {
            $table = 'google_users';
            $stmt->bind_param('ss', $table, $column);
            $stmt->execute();
            $has = (bool)$stmt->get_result()->fetch_row();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('google_users_has_column(' . $column . '): ' . $e->getMessage());
        $has = false;
    }

    $cache[$column] = $has;
    return $has;
}

/**
 * Stores the date and time of a successful login in google_users.last_login.
 * Does nothing (and never breaks the login) when the column does not exist yet.
 * Run sql/2026-10-25_add_last_login.sql once to add the column.
 */
function record_last_login(mysqli $conn, int $userId): void
{
    if (!google_users_has_column($conn, 'last_login')) {
        return;
    }

    try {
        $stmt = $conn->prepare('UPDATE google_users SET last_login = NOW() WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('record_last_login(' . $userId . '): ' . $e->getMessage());
    }
}

function current_user_from_session(mysqli $conn): ?array
{
    $token = $_SESSION['user_token'] ?? '';
    if ($token === '') {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM google_users WHERE token = ? LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || (int)$user['user_type'] === 9) {
        return null;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    return $user;
}

function destroy_app_session(?mysqli $conn = null): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_app_session();
    }

    $token = $_SESSION['user_token'] ?? '';
    if ($conn instanceof mysqli && $token !== '') {
        $blank = bin2hex(random_bytes(32));
        $stmt = $conn->prepare('UPDATE google_users SET token = ? WHERE token = ?');
        $stmt->bind_param('ss', $blank, $token);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION = [];

    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'] ?? '/',
        'domain'   => $params['domain'] ?? '',
        'secure'   => $params['secure'] ?? true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_destroy();
}
