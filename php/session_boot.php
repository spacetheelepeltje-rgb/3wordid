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

    session_regenerate_id(true);

    $_SESSION['user_id']    = $userId;
    $_SESSION['user_token'] = $rawToken;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return $rawToken;
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
