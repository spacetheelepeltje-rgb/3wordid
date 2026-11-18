<?php
/**
 * Remember-me ("stay logged in") for email login, site-wide.
 *
 * Cookie 3WIDREM holds selector:validator (hex). Only the SHA-256 hash of the
 * validator is stored, in auth_remember_tokens. The cookie is HttpOnly,
 * Secure (on HTTPS), SameSite=Lax, and lasts 30 days. The token is rotated
 * on each use, deleted on logout, and all of a user's tokens are dropped
 * when their password is changed.
 *
 * The PHP session cookie stays a browser session (lifetime 0). When that
 * session is gone, current_user_from_session() calls remember_resume_session()
 * and establish_user_session() starts a fresh one. Run
 * sql/2026-11-18_auth_remember_tokens.sql once; until then this does nothing.
 *
 * Needs PHP 8 (hash_equals, typed arguments; the rest of the repo also uses
 * str_starts_with in php/notify.php).
 */

function remember_cookie_name(): string
{
    return '3WIDREM';
}

function remember_lifetime(): int
{
    return 60 * 60 * 24 * 30;
}

function remember_table(): string
{
    return 'auth_remember_tokens';
}

/** True when the remember-me table exists. Cached per request. Never throws. */
function remember_schema_ready(?mysqli $conn = null): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    $ok = false;
    if (!$conn instanceof mysqli) {
        return $ok;
    }
    try {
        $table = remember_table();
        $stmt = $conn->prepare(
            'SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $table);
            $stmt->execute();
            $ok = (bool) $stmt->get_result()->fetch_row();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('remember_schema_ready: ' . $e->getMessage());
        $ok = false;
    }
    return $ok;
}

function remember_new_token(): array
{
    return [
        'selector'  => bin2hex(random_bytes(16)),
        'validator' => bin2hex(random_bytes(32)),
    ];
}

function remember_validator_hash(string $validator): string
{
    return hash('sha256', $validator);
}

function remember_cookie_value(string $selector, string $validator): string
{
    return $selector . ':' . $validator;
}

/** @return array{selector:string,validator:string}|null */
function remember_parse_cookie(?string $raw): ?array
{
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    if (!preg_match('/^([0-9a-f]{32}):([0-9a-f]{64})$/', $raw, $m)) {
        return null;
    }
    return ['selector' => $m[1], 'validator' => $m[2]];
}

function remember_cookie_options(int $expires): array
{
    return [
        'expires'  => $expires,
        'path'     => '/',
        'domain'   => '',
        'secure'   => function_exists('request_is_https') ? request_is_https() : false,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function remember_set_cookie(string $selector, string $validator, ?int $expires = null): void
{
    if ($expires === null) {
        $expires = time() + remember_lifetime();
    }
    setcookie(
        remember_cookie_name(),
        remember_cookie_value($selector, $validator),
        remember_cookie_options($expires)
    );
}

function remember_clear_cookie(): void
{
    $name = remember_cookie_name();
    if (!isset($_COOKIE[$name]) && (empty($_COOKIE) || !array_key_exists($name, $_COOKIE))) {
        // Still expire any cookie the browser may hold.
    }
    setcookie($name, '', remember_cookie_options(time() - 3600));
    unset($_COOKIE[$name]);
}

/**
 * Issue a remember-me cookie for this browser after a successful login.
 * $context: '' (normal site) or 'bumperchat' (restore the Bumperchat session flag).
 */
function remember_issue(mysqli $conn, int $userId, string $context = ''): bool
{
    if ($userId < 1 || !remember_schema_ready($conn)) {
        return false;
    }
    $context = $context === 'bumperchat' ? 'bumperchat' : '';
    $token = remember_new_token();
    $hash = remember_validator_hash($token['validator']);
    $expires = date('Y-m-d H:i:s', time() + remember_lifetime());

    try {
        remember_purge_expired($conn, $userId);
        $ins = $conn->prepare(
            'INSERT INTO auth_remember_tokens (user_id, selector, validator_hash, expires_at, context)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$ins) {
            return false;
        }
        $ins->bind_param('issss', $userId, $token['selector'], $hash, $expires, $context);
        $ok = $ins->execute();
        $ins->close();
        if (!$ok) {
            return false;
        }
        remember_cap_user_tokens($conn, $userId, 10);
        remember_set_cookie($token['selector'], $token['validator']);
        return true;
    } catch (Throwable $e) {
        error_log('remember_issue: ' . $e->getMessage());
        return false;
    }
}

function remember_purge_expired(mysqli $conn, int $userId = 0): void
{
    try {
        if ($userId > 0) {
            $stmt = $conn->prepare('DELETE FROM auth_remember_tokens WHERE user_id = ? AND expires_at < NOW()');
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $conn->query('DELETE FROM auth_remember_tokens WHERE expires_at < NOW()');
        }
    } catch (Throwable $e) {
        error_log('remember_purge_expired: ' . $e->getMessage());
    }
}

/** Keep at most $max tokens per user (oldest first). */
function remember_cap_user_tokens(mysqli $conn, int $userId, int $max = 10): void
{
    if ($userId < 1 || $max < 1) {
        return;
    }
    try {
        $sql = 'DELETE FROM auth_remember_tokens
                 WHERE user_id = ? AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM auth_remember_tokens
                         WHERE user_id = ?
                         ORDER BY created_at DESC, id DESC
                         LIMIT ?
                    ) kept
                 )';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iii', $userId, $userId, $max);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('remember_cap_user_tokens: ' . $e->getMessage());
    }
}

/** Delete every remember-me token for this user (password change). */
function remember_revoke_user(mysqli $conn, int $userId): void
{
    if ($userId < 1 || !remember_schema_ready($conn)) {
        return;
    }
    try {
        $stmt = $conn->prepare('DELETE FROM auth_remember_tokens WHERE user_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('remember_revoke_user: ' . $e->getMessage());
    }
}

/** Delete the token in this browser's cookie (logout). */
function remember_revoke_current(mysqli $conn): void
{
    $parsed = remember_parse_cookie($_COOKIE[remember_cookie_name()] ?? null);
    remember_clear_cookie();
    if (!$parsed || !remember_schema_ready($conn)) {
        return;
    }
    try {
        $stmt = $conn->prepare('DELETE FROM auth_remember_tokens WHERE selector = ?');
        if ($stmt) {
            $stmt->bind_param('s', $parsed['selector']);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('remember_revoke_current: ' . $e->getMessage());
    }
}

/**
 * If there is no live session but a valid remember-me cookie, start a session
 * with establish_user_session() and rotate the token. Returns the user row
 * or null. Safe to call when the table has not been created yet.
 */
function remember_resume_session(mysqli $conn): ?array
{
    $parsed = remember_parse_cookie($_COOKIE[remember_cookie_name()] ?? null);
    if (!$parsed) {
        if (isset($_COOKIE[remember_cookie_name()])) {
            remember_clear_cookie();
        }
        return null;
    }
    if (!remember_schema_ready($conn)) {
        return null;
    }

    try {
        $stmt = $conn->prepare(
            'SELECT id, user_id, validator_hash, expires_at, context
               FROM auth_remember_tokens
              WHERE selector = ?
              LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $parsed['selector']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('remember_resume_session: ' . $e->getMessage());
        return null;
    }

    if (!$row) {
        remember_clear_cookie();
        return null;
    }

    $expires = strtotime((string) $row['expires_at']);
    $hashOk = hash_equals((string) $row['validator_hash'], remember_validator_hash($parsed['validator']));
    if (!$hashOk || $expires === false || $expires < time()) {
        // Wrong validator (possible stolen old cookie) or expired: drop this row.
        try {
            $del = $conn->prepare('DELETE FROM auth_remember_tokens WHERE id = ?');
            if ($del) {
                $id = (int) $row['id'];
                $del->bind_param('i', $id);
                $del->execute();
                $del->close();
            }
        } catch (Throwable $e) {
            error_log('remember_resume_session delete: ' . $e->getMessage());
        }
        remember_clear_cookie();
        return null;
    }

    $userId = (int) $row['user_id'];
    $context = (string) ($row['context'] ?? '');

    // Rotate selector + validator so a stolen cookie works only once.
    $fresh = remember_new_token();
    $newHash = remember_validator_hash($fresh['validator']);
    $newExpires = date('Y-m-d H:i:s', time() + remember_lifetime());
    try {
        $upd = $conn->prepare(
            'UPDATE auth_remember_tokens
                SET selector = ?, validator_hash = ?, expires_at = ?, last_used_at = NOW()
              WHERE id = ?'
        );
        if ($upd) {
            $id = (int) $row['id'];
            $upd->bind_param('sssi', $fresh['selector'], $newHash, $newExpires, $id);
            $upd->execute();
            $upd->close();
        }
        remember_set_cookie($fresh['selector'], $fresh['validator']);
    } catch (Throwable $e) {
        error_log('remember_resume_session rotate: ' . $e->getMessage());
        remember_clear_cookie();
        return null;
    }

    if (!function_exists('establish_user_session')) {
        return null;
    }
    establish_user_session($conn, $userId);
    if ($context === 'bumperchat') {
        $_SESSION['bumperchat'] = ['words' => '', 'at' => time()];
    }

    // Load the user the same way a normal session would (skip deleted type 9).
    $stmt = $conn->prepare('SELECT * FROM google_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user || (int) $user['user_type'] === 9) {
        remember_revoke_user($conn, $userId);
        remember_clear_cookie();
        return null;
    }
    $_SESSION['user_id'] = (int) $user['id'];
    return $user;
}
