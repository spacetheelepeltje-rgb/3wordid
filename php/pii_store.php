<?php
/**
 * Email addresses and mobile numbers stored encrypted at rest.
 *
 * Encryption: libsodium secretbox (XSalsa20-Poly1305), a fresh random nonce
 * per value. Format in the database: "sbox:" + base64(nonce || ciphertext).
 * The same encryption key is used for email and phone.
 *
 * Email login/lookup uses a separate keyed hash (sodium_crypto_generichash)
 * of the lowercased, trimmed address, stored in google_users.email_lookup
 * and indexed. Phone numbers are not looked up, so they have no hash column.
 *
 * Keys live only in login/config.php (that file is gitignored, never commit a key):
 *
 *     define('EMAIL_ENC_KEY',  '...'); // base64 of 32 random bytes
 *     define('EMAIL_HASH_KEY', '...'); // base64 of 32 random bytes, different from ENC
 *
 * Generate each key once on the server (run twice, store each result separately):
 *
 *     php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
 *
 * If a key is missing or invalid: existing logins still work with the legacy
 * md5 value in google_users.email; no plaintext email or phone is written to
 * the new columns. An explicit save (edit / settings) returns the error
 * 'pii.keys_missing'. See sql/2026-11-23_encrypted_email_phone.sql and
 * tools/encrypt_stored_pii.php.
 */

if (!defined('PII_BOX_PREFIX')) {
    define('PII_BOX_PREFIX', 'sbox:');
}

/** 32-byte key from a base64 constant, or null when missing or invalid. */
function pii_decode_key(string $const): ?string
{
    if (!defined($const) || !function_exists('sodium_crypto_secretbox')) {
        return null;
    }
    $key = base64_decode((string) constant($const), true);
    if ($key === false || strlen($key) !== 32) {
        return null;
    }
    return $key;
}

function pii_enc_key(): ?string
{
    $key = pii_decode_key('EMAIL_ENC_KEY');
    if ($key === null) {
        return null;
    }
    if (defined('SODIUM_CRYPTO_SECRETBOX_KEYBYTES') && strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        return null;
    }
    return $key;
}

function pii_hash_key(): ?string
{
    $key = pii_decode_key('EMAIL_HASH_KEY');
    if ($key === null) {
        return null;
    }
    if (defined('SODIUM_CRYPTO_GENERICHASH_KEYBYTES') && strlen($key) !== SODIUM_CRYPTO_GENERICHASH_KEYBYTES) {
        return null;
    }
    return $key;
}

/** True when values can be encrypted and decrypted. */
function pii_enc_ready(): bool
{
    return pii_enc_key() !== null;
}

/** True when an email can be stored (encrypt + lookup hash). */
function pii_email_keys_ready(): bool
{
    return pii_enc_ready() && pii_hash_key() !== null;
}


/** Best-effort wipe of a key copy. Never throws: some PHP builds cannot wipe. */
function pii_memzero(?string &$buf): void
{
    if (!is_string($buf) || $buf === '') {
        return;
    }
    if (function_exists('sodium_memzero')) {
        try {
            sodium_memzero($buf);
            return;
        } catch (Throwable $e) {
            // e.g. sodium_compat cannot securely wipe memory from PHP
        }
    }
    $buf = str_repeat("\0", strlen($buf));
    $buf = '';
}

/**
 * Encrypt a UTF-8 string. Returns the stored form, or null when the key is
 * missing (never returns or stores plaintext).
 */
function pii_encrypt(string $plain): ?string
{
    $plain = (string) $plain;
    if ($plain === '') {
        return '';
    }
    $key = pii_enc_key();
    if ($key === null) {
        return null;
    }
    try {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $box = sodium_crypto_secretbox($plain, $nonce, $key);
        $out = PII_BOX_PREFIX . base64_encode($nonce . $box);
        pii_memzero($key);
        return $out;
    } catch (Throwable $e) {
        error_log('pii_encrypt: could not encrypt'); // never log the value
        return null;
    }
}

/**
 * Decrypt a value produced by pii_encrypt(). Returns null when the value is
 * empty, not in the encrypted form, or the key is missing / wrong.
 * Does not treat leftover plaintext as a successful decrypt.
 */
function pii_decrypt(?string $stored): ?string
{
    if (!is_string($stored) || $stored === '' || strpos($stored, PII_BOX_PREFIX) !== 0) {
        return null;
    }
    $key = pii_enc_key();
    if ($key === null) {
        return null;
    }
    $raw = base64_decode(substr($stored, strlen(PII_BOX_PREFIX)), true);
    if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return null;
    }
    try {
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $box = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($box, $nonce, $key);
        pii_memzero($key);
    } catch (Throwable $e) {
        return null;
    }
    if ($plain === false || $plain === '') {
        return null;
    }
    return $plain;
}

/** True when $stored looks like an encrypted pii_encrypt() value. */
function pii_is_boxed(?string $stored): bool
{
    return is_string($stored) && strpos($stored, PII_BOX_PREFIX) === 0;
}

function email_normalize(string $email): string
{
    return strtolower(trim($email));
}

/**
 * Keyed hash of a normalised email, 64 hex characters, or null if the hash
 * key is missing. Used only for lookup, never as a display value.
 */
function email_lookup_hash(string $email): ?string
{
    $email = email_normalize($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $key = pii_hash_key();
    if ($key === null || !function_exists('sodium_crypto_generichash')) {
        return null;
    }
    try {
        $hash = sodium_crypto_generichash($email, $key, 32);
        pii_memzero($key);
        return bin2hex($hash);
    } catch (Throwable $e) {
        error_log('email_lookup_hash: could not hash');
        return null;
    }
}

function email_md5(string $email): string
{
    return md5(email_normalize($email));
}

/** Values to write for an email change: enc, lookup, legacy md5. Or an error. */
function email_store_bundle(string $email): array
{
    $email = email_normalize($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'reg.bad_email'];
    }
    if (!pii_email_keys_ready()) {
        return ['ok' => false, 'error' => 'pii.keys_missing'];
    }
    $enc = pii_encrypt($email);
    $lookup = email_lookup_hash($email);
    if ($enc === null || $lookup === null) {
        return ['ok' => false, 'error' => 'pii.keys_missing'];
    }
    return [
        'ok' => true,
        'error' => '',
        'plain' => $email,
        'enc' => $enc,
        'lookup' => $lookup,
        'md5' => email_md5($email),
    ];
}

/** Decrypt the email on a google_users row (email_enc). */
function email_store_plain(?array $row): string
{
    if (!is_array($row)) {
        return '';
    }
    $plain = pii_decrypt((string) ($row['email_enc'] ?? ''));
    if (is_string($plain) && filter_var($plain, FILTER_VALIDATE_EMAIL)) {
        return $plain;
    }
    // A leftover readable address in the legacy column (contains @). Never return a hash.
    $legacy = trim((string) ($row['email'] ?? ''));
    if ($legacy !== '' && strpos($legacy, '@') !== false && filter_var(email_normalize($legacy), FILTER_VALIDATE_EMAIL)) {
        return email_normalize($legacy);
    }
    return '';
}

function email_store_schema_ready($conn): bool
{
    return function_exists('google_users_has_column')
        && $conn
        && google_users_has_column($conn, 'email_enc')
        && google_users_has_column($conn, 'email_lookup');
}

/**
 * Find a user id by email: keyed lookup first, then the legacy md5 column.
 */
function email_store_find_id($conn, string $email): int
{
    $email = email_normalize($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$conn) {
        return 0;
    }
    if (pii_email_keys_ready() && email_store_schema_ready($conn)) {
        $lookup = email_lookup_hash($email);
        if ($lookup) {
            $stmt = $conn->prepare('SELECT id FROM google_users WHERE email_lookup = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $lookup);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    return (int) $row['id'];
                }
            }
        }
    }
    $md5 = email_md5($email);
    $stmt = $conn->prepare('SELECT id FROM google_users WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $md5);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row['id'] : 0;
}

/**
 * Write encrypted email + lookup (+ keep the legacy md5 in `email`) for this user.
 * Used by an explicit save (edit / settings). Fails clearly when keys are missing
 * and never writes the plaintext address.
 */
function email_store_save($conn, int $user_id, string $email): array
{
    $bundle = email_store_bundle($email);
    if (!$bundle['ok']) {
        return $bundle;
    }
    if (!$conn || $user_id < 1) {
        return ['ok' => false, 'error' => 'pii.save_failed'];
    }
    if (!email_store_schema_ready($conn)) {
        return ['ok' => false, 'error' => 'pii.schema'];
    }
    $other = email_store_find_id($conn, $bundle['plain']);
    if ($other > 0 && $other !== $user_id) {
        return ['ok' => false, 'error' => 'reg.exists'];
    }
    $stmt = $conn->prepare('UPDATE google_users SET email = ?, email_enc = ?, email_lookup = ? WHERE id = ?');
    if (!$stmt) {
        return ['ok' => false, 'error' => 'pii.save_failed'];
    }
    $stmt->bind_param('sssi', $bundle['md5'], $bundle['enc'], $bundle['lookup'], $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        return ['ok' => false, 'error' => 'pii.save_failed'];
    }
    return $bundle;
}

/**
 * After a successful login or register, store the encrypted email when the
 * keys and columns are ready and it is not stored yet (or the hash is missing).
 * Silent when keys are missing: login must keep working. Never writes plaintext.
 */
function email_store_remember($conn, int $user_id, string $email): void
{
    if ($user_id < 1 || !$conn || !pii_email_keys_ready() || !email_store_schema_ready($conn)) {
        return;
    }
    $bundle = email_store_bundle($email);
    if (!$bundle['ok']) {
        return;
    }
    try {
        $stmt = $conn->prepare(
            'UPDATE google_users
                SET email_enc = ?, email_lookup = ?, email = ?
              WHERE id = ? AND (email_enc IS NULL OR email_enc = \'\' OR email_lookup IS NULL OR email_lookup = \'\')'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sssi', $bundle['enc'], $bundle['lookup'], $bundle['md5'], $user_id);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('email_store_remember: could not store');
    }
}

function user_phone_schema_ready($conn): bool
{
    return function_exists('google_users_has_column')
        && $conn
        && google_users_has_column($conn, 'notify_phone_enc');
}

/**
 * Plain E.164 number from a user or 3WordID row: decrypt notify_phone_enc, or
 * fall back to a leftover plaintext notify_phone (legacy, before encryption).
 */
function phone_store_plain(?array $row): string
{
    if (!is_array($row)) {
        return '';
    }
    $plain = pii_decrypt((string) ($row['notify_phone_enc'] ?? ''));
    if (is_string($plain) && $plain !== '') {
        return $plain;
    }
    $legacy = trim((string) ($row['notify_phone'] ?? ''));
    if ($legacy !== '' && !pii_is_boxed($legacy) && function_exists('notify_phone_is_e164') && notify_phone_is_e164($legacy)) {
        return $legacy;
    }
    return '';
}

/** Encrypt an E.164 number (or empty to clear). Explicit save: error if keys missing. */
function phone_store_bundle(?string $e164): array
{
    $e164 = is_string($e164) ? trim($e164) : '';
    if ($e164 === '') {
        return ['ok' => true, 'error' => '', 'plain' => '', 'enc' => null];
    }
    if (function_exists('notify_phone_is_e164') && !notify_phone_is_e164($e164)) {
        return ['ok' => false, 'error' => 'bc.phone_err'];
    }
    if (!pii_enc_ready()) {
        return ['ok' => false, 'error' => 'pii.keys_missing'];
    }
    $enc = pii_encrypt($e164);
    if ($enc === null) {
        return ['ok' => false, 'error' => 'pii.keys_missing'];
    }
    return ['ok' => true, 'error' => '', 'plain' => $e164, 'enc' => $enc];
}

/**
 * Save the account-level mobile number (encrypted). $phone is already normalised
 * or empty. $consent is kept only together with a number.
 */
function user_phone_store_save($conn, int $user_id, ?string $phone, bool $consent = false): array
{
    $bundle = phone_store_bundle($phone);
    if (!$bundle['ok']) {
        return $bundle;
    }
    if (!$conn || $user_id < 1) {
        return ['ok' => false, 'error' => 'pii.save_failed'];
    }
    if (!user_phone_schema_ready($conn)) {
        return ['ok' => false, 'error' => 'pii.schema'];
    }
    $enc = $bundle['enc'];
    $consent_i = ($enc && $consent) ? 1 : 0;
    $consent_at = $consent_i ? date('Y-m-d H:i:s') : null;
    $stmt = $conn->prepare(
        'UPDATE google_users
            SET notify_phone_enc = ?, notify_sms_consent = ?, notify_sms_consent_at = ?
          WHERE id = ?'
    );
    if (!$stmt) {
        // notify_sms_consent columns may be missing on an older database: try phone only
        $stmt = $conn->prepare('UPDATE google_users SET notify_phone_enc = ? WHERE id = ?');
        if (!$stmt) {
            return ['ok' => false, 'error' => 'pii.save_failed'];
        }
        $stmt->bind_param('si', $enc, $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? $bundle : ['ok' => false, 'error' => 'pii.save_failed'];
    }
    $stmt->bind_param('sisi', $enc, $consent_i, $consent_at, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? $bundle : ['ok' => false, 'error' => 'pii.save_failed'];
}

function plate_phone_schema_ready($conn = null): bool
{
    if (function_exists('billing_schema_ready') && function_exists('google_users_has_column') === false) {
        // fall through
    }
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = false;
    if (!function_exists('get_db_conn') && !$conn) {
        return false;
    }
    $c = $conn ?: get_db_conn();
    if (!$c) {
        return false;
    }
    try {
        $r = $c->query("SHOW COLUMNS FROM 3wordid LIKE 'notify_phone_enc'");
        $ready = $r && $r->num_rows > 0;
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/**
 * Persist an encrypted plate phone (and clear the legacy plaintext column).
 * Called from bumperchat_claim_plate after the usual UPDATE.
 */
function plate_phone_store_save($conn, int $wid_id, ?string $phone): array
{
    $bundle = phone_store_bundle($phone);
    if (!$bundle['ok']) {
        return $bundle;
    }
    if (!$conn || $wid_id < 1 || !plate_phone_schema_ready($conn)) {
        return $bundle['plain'] === '' ? $bundle : ['ok' => false, 'error' => 'pii.schema'];
    }
    $enc = $bundle['enc'];
    $stmt = $conn->prepare('UPDATE 3wordid SET notify_phone_enc = ?, notify_phone = NULL WHERE id = ?');
    if (!$stmt) {
        return ['ok' => false, 'error' => 'pii.save_failed'];
    }
    $stmt->bind_param('si', $enc, $wid_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? $bundle : ['ok' => false, 'error' => 'pii.save_failed'];
}

/**
 * Mark the Bumperchat Google extras form (phone, SMS, reseller, stay logged in) as done.
 */
function bumperchat_signup_details_mark($conn, int $user_id): void
{
    if ($user_id < 1 || !$conn || !function_exists('google_users_has_column') || !google_users_has_column($conn, 'signup_details_at')) {
        return;
    }
    try {
        $stmt = $conn->prepare('UPDATE google_users SET signup_details_at = ? WHERE id = ? AND (signup_details_at IS NULL OR signup_details_at = \'0000-00-00 00:00:00\')');
        if (!$stmt) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $stmt->bind_param('si', $now, $user_id);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('bumperchat_signup_details_mark: failed');
    }
}

/**
 * Whether a Bumperchat Google login should show the extras form.
 * New signups always; existing accounts only if they have never completed it
 * (no signup_details_at, no reseller, no stored phone).
 */
function bumperchat_google_details_needed(?array $user, bool $just_created = false): bool
{
    if (!is_array($user) || (int) ($user['id'] ?? 0) < 1) {
        return false;
    }
    if (!empty($user['signup_details_at']) && $user['signup_details_at'] !== '0000-00-00 00:00:00') {
        return false;
    }
    if ($just_created) {
        return true;
    }
    if ((int) ($user['reseller_id'] ?? 0) > 0) {
        return false;
    }
    if (phone_store_plain($user) !== '') {
        return false;
    }
    return true;
}
