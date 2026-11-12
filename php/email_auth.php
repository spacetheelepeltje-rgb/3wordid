<?php
/*
 * Email + password accounts (google_users with auth_provider 'password').
 * The ONE place that creates and checks them; used by login/email_register.php,
 * login/email_login.php and bumperchat/claim.php.
 *
 * The caller checks its own CSRF token (and, for registration, botcheck_failed()) first.
 * Both functions return ['user_id' => int, 'error' => lang key or ''] and never start the
 * session themselves: on success the caller runs establish_user_session().
 * The email address is stored only as md5 (same as Google login), the password with
 * password_hash(). No verification email is sent (same as before).
 */

if (!defined('EMAIL_PW_MIN')) { define('EMAIL_PW_MIN', 10); }

/** New email + password account. Errors: reg.bad_email, reg.short, reg.mismatch, reg.exists, reg.google, reg.failed */
function email_auth_register($conn, string $email, string $password, string $confirm): array
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['user_id' => 0, 'error' => 'reg.bad_email'];
    }
    if (strlen($password) < EMAIL_PW_MIN) {
        return ['user_id' => 0, 'error' => 'reg.short'];
    }
    if ($password !== $confirm) {
        return ['user_id' => 0, 'error' => 'reg.mismatch'];
    }

    $email_hash = md5($email);
    $stmt = $conn->prepare('SELECT id, password_hash FROM google_users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email_hash);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing && !empty($existing['password_hash'])) {
        return ['user_id' => 0, 'error' => 'reg.exists'];
    }
    if ($existing) {
        return ['user_id' => 0, 'error' => 'reg.google'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));
    $provider = 'password';
    $verified = 0;
    $empty = '';

    $ins = $conn->prepare(
        'INSERT INTO google_users
            (email, password_hash, first_name, last_name, gender, full_name, picture, verifiedEmail, token, auth_provider)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->bind_param('sssssssiss', $email_hash, $hash, $empty, $empty, $empty, $empty, $empty, $verified, $token, $provider);

    if ($ins->execute()) {
        $user_id = (int) $ins->insert_id;
        $ins->close();
        return ['user_id' => $user_id, 'error' => ''];
    }
    return ['user_id' => 0, 'error' => 'reg.failed'];
}

/** Check email + password. Errors: elogin.invalid, elogin.google_account (account made with Google) */
function email_auth_login($conn, string $email, string $password): array
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        return ['user_id' => 0, 'error' => 'elogin.invalid'];
    }

    $email_hash = md5($email);
    $stmt = $conn->prepare('SELECT id, password_hash FROM google_users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email_hash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && empty($user['password_hash'])) {
        return ['user_id' => 0, 'error' => 'elogin.google_account'];
    }
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['user_id' => 0, 'error' => 'elogin.invalid'];
    }
    return ['user_id' => (int) $user['id'], 'error' => ''];
}
