<?php
/**
 * Encryption, lookup and edit-page roundtrip for email and phone at rest.
 * No database needed. Uses temporary test keys (never production keys).
 *
 * Requires the PHP sodium extension. In environments without it (such as a
 * static PHP build), paragonie/sodium_compat may be loaded from
 * tools/lib/sodium_compat or SODIUM_COMPAT_AUTOLOAD. Do not put production
 * keys in this file.
 *
 * Run from the repo root:  php tools/test_pii_store.php
 * Not needed on the web server. Exit code 1 when something differs.
 */

if (!function_exists('sodium_crypto_secretbox')) {
    $compat_candidates = [
        getenv('SODIUM_COMPAT_AUTOLOAD') ?: '',
        __DIR__ . '/lib/sodium_compat/autoload.php',
        '/tmp/sodium_compat-1.21.1/autoload.php',
    ];
    foreach ($compat_candidates as $compat) {
        if (is_string($compat) && $compat !== '' && is_file($compat)) {
            require_once $compat;
            break;
        }
    }
}
if (!function_exists('sodium_crypto_secretbox')) {
    fwrite(STDERR, "Need the PHP sodium extension (or paragonie/sodium_compat) to run this test.\n");
    exit(1);
}

if (!defined('EMAIL_ENC_KEY')) {
    define('EMAIL_ENC_KEY', base64_encode(random_bytes(32)));
}
if (!defined('EMAIL_HASH_KEY')) {
    // Must be a different key from ENC
    define('EMAIL_HASH_KEY', base64_encode(random_bytes(32)));
}

if (!function_exists('notify_phone_is_e164')) {
    function notify_phone_is_e164(string $phone): bool
    {
        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $phone);
    }
}

require_once __DIR__ . '/../php/pii_store.php';

$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}

expect('enc ready', pii_enc_ready(), true);
expect('email keys ready', pii_email_keys_ready(), true);

// --- email encrypt / decrypt ---
$plain = 'user@example.com';
$box = pii_encrypt($plain);
expect('email boxed prefix', is_string($box) && strpos($box, 'sbox:') === 0, true);
expect('ciphertext is not plaintext', $box !== $plain && strpos((string) $box, $plain) === false, true);
expect('email decrypt', pii_decrypt($box), $plain);
$box2 = pii_encrypt($plain);
expect('fresh nonce: two ciphertexts differ', $box !== $box2, true);
expect('both decrypt to the same email', pii_decrypt($box2), $plain);
expect('empty encrypt is empty', pii_encrypt(''), '');
expect('garbage decrypt is null', pii_decrypt('not-encrypted'), null);
expect('legacy plaintext is not treated as decrypt', pii_decrypt('user@example.com'), null);

// --- lookup hash ---
$h = email_lookup_hash('User@Example.com');
$h2 = email_lookup_hash(' user@example.com ');
expect('lookup is 64 hex', is_string($h) && preg_match('/^[0-9a-f]{64}$/', $h) === 1, true);
expect('lookup is case/space insensitive', $h, $h2);
expect('different emails differ', email_lookup_hash('other@example.com') !== $h, true);
expect('lookup is not the address', $h !== 'user@example.com', true);

$bundle = email_store_bundle('Name@Example.COM');
expect('bundle ok', $bundle['ok'] ?? false, true);
expect('bundle plain normalised', $bundle['plain'] ?? '', 'name@example.com');
expect('bundle md5 matches', $bundle['md5'] ?? '', md5('name@example.com'));
expect('bundle decrypts', pii_decrypt($bundle['enc']), 'name@example.com');
expect('bundle lookup', $bundle['lookup'] ?? '', email_lookup_hash('name@example.com'));
expect('bad email rejected', email_store_bundle('not-an-email')['ok'], false);

// --- phone encrypt / decrypt (same key, no lookup column) ---
$phone = '+31612345678';
$pbox = pii_encrypt($phone);
expect('phone boxed', pii_is_boxed($pbox), true);
expect('phone not stored as plaintext', strpos((string) $pbox, $phone) === false, true);
expect('phone decrypt', pii_decrypt($pbox), $phone);
$pb = phone_store_bundle($phone);
expect('phone bundle ok', $pb['ok'] ?? false, true);
expect('phone bundle decrypt', pii_decrypt($pb['enc']), $phone);
expect('empty phone bundle clears', phone_store_bundle('')['enc'], null);
expect('bad phone rejected', phone_store_bundle('0612345678')['ok'], false);

// phone_store_plain: encrypted wins, leftover E.164 is accepted, hashes are not
expect('plain from enc', phone_store_plain(['notify_phone_enc' => $pbox, 'notify_phone' => '+31999999999']), $phone);
expect('plain from legacy column', phone_store_plain(['notify_phone' => $phone]), $phone);
expect('plain ignores leftover hash-like text', phone_store_plain(['notify_phone' => 'abc123def']), '');

// email_store_plain: enc wins; leftover readable @ address; never return md5
$row = ['email_enc' => $bundle['enc'], 'email' => $bundle['md5']];
expect('email from enc, not md5', email_store_plain($row), 'name@example.com');
expect('email from leftover readable', email_store_plain(['email' => 'Old.Addr@Example.com']), 'old.addr@example.com');
expect('md5 is not shown as email', email_store_plain(['email' => md5('name@example.com')]), '');

// --- edit-page roundtrip (save a new address: re-encrypt, new lookup, decrypt equals new) ---
$before = email_store_bundle('one@example.com');
$after  = email_store_bundle('two@example.com');
expect('edit: old decrypt', pii_decrypt($before['enc']), 'one@example.com');
expect('edit: new decrypt', pii_decrypt($after['enc']), 'two@example.com');
expect('edit: lookup changed', $before['lookup'] !== $after['lookup'], true);
expect('edit: md5 changed', $before['md5'] !== $after['md5'], true);
expect('edit: ciphertext changed', $before['enc'] !== $after['enc'], true);
expect('edit: old lookup does not match new email', $before['lookup'] !== email_lookup_hash('two@example.com'), true);

$phone_before = phone_store_bundle('+31611111111');
$phone_after  = phone_store_bundle('+31622222222');
expect('edit phone: new decrypt', pii_decrypt($phone_after['enc']), '+31622222222');
expect('edit phone: ciphertext changed', $phone_before['enc'] !== $phone_after['enc'], true);


// --- keys missing: no plaintext writes ---
$root = realpath(__DIR__ . '/..');
$missing = shell_exec(
    escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg(
        'require ' . var_export($root . '/php/pii_store.php', true) . ';'
        . 'echo pii_enc_ready() ? "ready" : "missing";'
        . 'echo "\n";'
        . 'echo pii_encrypt("a@b.co") === null ? "noplain" : "leaked";'
        . 'echo "\n";'
        . '$b = email_store_bundle("a@b.co");'
        . 'echo (!empty($b["ok"]) ? "saved" : ($b["error"] ?? "err"));'
        . 'echo "\n";'
        . '$p = phone_store_bundle("+31612345678");'
        . 'echo (!empty($p["ok"]) ? "saved" : ($p["error"] ?? "err"));'
    )
);
$missing_lines = array_values(array_filter(explode("\n", (string) $missing), "strlen"));
expect("missing keys: not ready", $missing_lines[0] ?? "", "missing");
expect("missing keys: encrypt returns null, not plaintext", $missing_lines[1] ?? "", "noplain");
expect("missing keys: email bundle error", $missing_lines[2] ?? "", "pii.keys_missing");
expect("missing keys: phone bundle error", $missing_lines[3] ?? "", "pii.keys_missing");

// --- Google extras form: who should see it ---
expect('new Google signup needs the form', bumperchat_google_details_needed(['id' => 9], true), true);
expect('already completed: skip', bumperchat_google_details_needed(['id' => 9, 'signup_details_at' => '2026-11-23 12:00:00'], false), false);
expect('existing with reseller: skip', bumperchat_google_details_needed(['id' => 9, 'reseller_id' => 3], false), false);
expect('existing with phone: skip', bumperchat_google_details_needed(['id' => 9, 'notify_phone_enc' => $pbox], false), false);
expect('existing never completed: show', bumperchat_google_details_needed(['id' => 9], false), true);
expect('empty user: no', bumperchat_google_details_needed(null, true), false);

// --- pages and wiring (no redirects to the main site; edit pages show decrypted values) ---
$root = realpath(__DIR__ . '/..');
$welcome = file_get_contents($root . '/login/welcome.php');
expect('welcome Bumperchat success uses after_login_redirect', strpos($welcome, 'bumperchat_after_login_redirect(') !== false, true);
expect('welcome error/cancel stays in bumperchat/', strpos($welcome, '../bumperchat/login.php') !== false, true);
expect('welcome 3wordID Google still goes to the list page', strpos($welcome, "header('Location: ../3wid_list.php')") !== false, true);
$details = file_get_contents($root . '/bumperchat/google_details.php');
foreach (['notify_phone', 'notify_sms_consent', 'reseller_code', 'remember_me'] as $field) {
    expect("google_details has $field", strpos($details, $field) !== false, true);
}
expect('google_details has no main-site redirect', preg_match('#\.\./index\.php|\.\./3wid_list\.php|login/options\.php#', $details) !== 1, true);
$account = file_get_contents($root . '/bumperchat/account.php');
expect('account page decrypts email', strpos($account, 'email_store_plain') !== false && strpos($account, 'email_store_save') !== false, true);
expect('account page decrypts phone', strpos($account, 'phone_store_plain') !== false && strpos($account, 'user_phone_store_save') !== false, true);
$edit = file_get_contents($root . '/3wid_user_edit.php');
expect('user edit shows decrypted email', strpos($edit, 'email_store_plain($user)') !== false, true);
expect('user edit saves re-encrypted email', strpos($edit, 'email_store_save(') !== false, true);
expect('user edit shows decrypted phone', strpos($edit, 'phone_store_plain($user)') !== false, true);
expect('user edit does not fall back to the admin session email', strpos($edit, 'get_session_email()') === false, true);
$settings = file_get_contents($root . '/3wid_settings.php');
expect('settings shows and saves email', strpos($settings, "do === 'contact'") !== false && strpos($settings, 'email_store_save') !== false, true);
$list = file_get_contents($root . '/3wid_user_list.php');
expect('user list shows decrypted email, not the md5 column', strpos($list, 'email_store_plain($user)') !== false, true);

if (function_exists('bumperchat_safe_next')) {
    // loaded only if we also included bumperchat.php; skip otherwise
} else {
    // light check of the allow-list in source
    $bc = file_get_contents($root . '/php/bumperchat.php');
    expect('safe_next allows google_details', strpos($bc, 'google_details') !== false, true);
    expect('safe_next allows account', strpos($bc, '|account)') !== false || strpos($bc, 'google_details|account') !== false, true);
}

// --- missing SQL columns must not fatal a login ---
$boot = file_get_contents($root . '/php/session_boot.php');
expect('remember-me resume does not SELECT email_enc unless the column exists', (strpos($boot, 'google_users_has_column') !== false && strpos($boot, "'email_enc'") !== false), true);
expect('establish_user_session catches a failed email load', strpos($boot, 'could not load stored email') !== false, true);

class PiiTestConn
{
    public $queries = [];
    public function prepare($sql)
    {
        $this->queries[] = $sql;
        if (stripos($sql, 'email_lookup') !== false || stripos($sql, 'email_enc') !== false) {
            throw new Exception('Unknown column');
        }
        return false; // no real md5 lookup either
    }
}
$fake = new PiiTestConn();
$found = email_store_find_id($fake, 'user@example.com');
expect('find_id with missing columns returns 0, does not fatal', $found, 0);


if ($fail) {
    echo "$fail failed\n";
    exit(1);
}
echo "ok\n";
exit(0);
