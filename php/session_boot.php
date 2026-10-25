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

function establish_user_session(mysqli $conn, int $userId, ?string $plainEmail = null): string
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

    // Keep the email only in this session, encrypted (see set_session_email).
    // Never breaks the login: without SESSION_EMAIL_KEY this does nothing.
    if ($plainEmail !== null) {
        set_session_email($plainEmail);
    }

    return $rawToken;
}

/*
 * Session email
 * -------------
 * The database only holds a one-way hash of the email address. To let a
 * logged-in user ask for something by email ("Send to my email"), the plain
 * email is kept in $_SESSION during the session only, encrypted with
 * libsodium (secretbox, random nonce). It is never written to the database,
 * logs or error output, and it is gone when the user logs out or the session ends.
 *
 * Setup: add this line to login/config.php (that file is not in the repo):
 *
 *     define('SESSION_EMAIL_KEY', 'PASTE_BASE64_KEY_HERE');
 *
 * Generate a key once on the server with:
 *
 *     php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
 *
 * Without a valid key the feature is simply hidden and login works as before.
 */

/** Returns the 32-byte key from SESSION_EMAIL_KEY, or null when missing or invalid. */
function session_email_key(): ?string
{
    if (!defined('SESSION_EMAIL_KEY') || !function_exists('sodium_crypto_secretbox')) {
        return null;
    }
    $key = base64_decode((string)SESSION_EMAIL_KEY, true);
    if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        return null;
    }
    return $key;
}

/** Stores the email encrypted in the session. Returns false (silently) when not possible. */
function set_session_email(string $email): bool
{
    unset($_SESSION['email_enc']);

    $email = strtolower(trim($email));
    $key = session_email_key();
    if ($key === null || session_status() !== PHP_SESSION_ACTIVE
        || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    try {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $box = sodium_crypto_secretbox($email, $nonce, $key);
        $_SESSION['email_enc'] = base64_encode($nonce . $box);
        sodium_memzero($key);
        return true;
    } catch (Throwable $e) {
        error_log('set_session_email: could not encrypt'); // never log the email
        return false;
    }
}

/** Returns the decrypted email of the logged-in user, or null. */
function get_session_email(): ?string
{
    $stored = $_SESSION['email_enc'] ?? '';
    $key = session_email_key();
    if (!is_string($stored) || $stored === '' || $key === null) {
        return null;
    }

    $raw = base64_decode($stored, true);
    if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return null;
    }

    try {
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $box = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $email = sodium_crypto_secretbox_open($box, $nonce, $key);
        sodium_memzero($key);
    } catch (Throwable $e) {
        return null;
    }

    if ($email === false || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    return $email;
}

function has_session_email(): bool
{
    return get_session_email() !== null;
}

function clear_session_email(): void
{
    unset($_SESSION['email_enc'], $_SESSION['email_list_sent_at']);
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

    clear_session_email();
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
