<?php
/**
 * One-time migration: encrypt leftover readable emails and mobile numbers.
 *
 * Existing google_users.email values are md5 hashes and cannot be recovered.
 * Those accounts get an encrypted copy automatically on the next login.
 * This script only touches rows that still hold a readable email (contains @)
 * or a plaintext E.164 mobile number in notify_phone.
 *
 * Run from the repo root, on the server, AFTER:
 *   1. sql/2026-11-23_encrypted_email_phone.sql has been applied
 *   2. EMAIL_ENC_KEY and EMAIL_HASH_KEY are set in login/config.php
 *
 *     php tools/encrypt_stored_pii.php
 *
 * Default is a dry run (counts only). To write:
 *
 *     php tools/encrypt_stored_pii.php --apply
 *
 * Never prints an email or phone number. Not needed in the web root.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from the command line only.\n");
    exit(2);
}

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

$apply = in_array('--apply', $argv, true);

if (!function_exists('pii_email_keys_ready') || !pii_enc_ready()) {
    fwrite(STDERR, "EMAIL_ENC_KEY is missing or invalid in login/config.php.\n");
    fwrite(STDERR, "Generate a key: php -r \"echo base64_encode(random_bytes(32)), PHP_EOL;\"\n");
    exit(1);
}
if (!pii_email_keys_ready()) {
    fwrite(STDERR, "EMAIL_HASH_KEY is missing or invalid in login/config.php (needed for email lookup).\n");
    fwrite(STDERR, "Generate a second, different key with the same command.\n");
    exit(1);
}

$conn = get_db_conn();
if (!$conn || $conn->connect_error) {
    fwrite(STDERR, "Could not open the database.\n");
    exit(1);
}
if (!email_store_schema_ready($conn)) {
    fwrite(STDERR, "Columns are missing. Run sql/2026-11-23_encrypted_email_phone.sql first.\n");
    exit(1);
}

$stats = [
    'email_readable' => 0,
    'email_written' => 0,
    'phone_user' => 0,
    'phone_plate' => 0,
    'phone_written' => 0,
    'skipped' => 0,
];

function migrate_email_row(mysqli $conn, array $row, bool $apply, string $table, array &$stats): void
{
    $plain = email_store_plain($row);
    if ($plain === '') {
        return;
    }
    $stats['email_readable']++;
    if (!$apply) {
        return;
    }
    $saved = email_store_save($conn, (int) $row['id'], $plain);
    if (!empty($saved['ok'])) {
        $stats['email_written']++;
    } else {
        $stats['skipped']++;
        error_log('encrypt_stored_pii: email not written for id ' . (int) $row['id'] . ' (' . ($saved['error'] ?? '') . ')');
    }
}

function migrate_phone_table(mysqli $conn, string $table, string $id_col, bool $apply, array &$stats, string $kind): void
{
    $has = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE 'notify_phone_enc'");
    if (!$has || $has->num_rows < 1) {
        return;
    }
    $r = $conn->query("SELECT `{$id_col}` AS id, notify_phone, notify_phone_enc FROM `{$table}` WHERE notify_phone IS NOT NULL AND notify_phone != ''");
    if (!$r) {
        return;
    }
    while ($row = $r->fetch_assoc()) {
        if (pii_is_boxed((string) ($row['notify_phone_enc'] ?? ''))) {
            // Already encrypted: just clear leftover plaintext
            if ($apply && trim((string) $row['notify_phone']) !== '') {
                $u = $conn->prepare("UPDATE `{$table}` SET notify_phone = NULL WHERE `{$id_col}` = ?");
                if ($u) {
                    $id = (int) $row['id'];
                    $u->bind_param('i', $id);
                    $u->execute();
                    $u->close();
                }
            }
            continue;
        }
        $plain = phone_store_plain($row);
        if ($plain === '') {
            continue;
        }
        $stats[$kind]++;
        if (!$apply) {
            continue;
        }
        $bundle = phone_store_bundle($plain);
        if (empty($bundle['ok'])) {
            $stats['skipped']++;
            continue;
        }
        $u = $conn->prepare("UPDATE `{$table}` SET notify_phone_enc = ?, notify_phone = NULL WHERE `{$id_col}` = ?");
        if (!$u) {
            $stats['skipped']++;
            continue;
        }
        $id = (int) $row['id'];
        $u->bind_param('si', $bundle['enc'], $id);
        if ($u->execute()) {
            $stats['phone_written']++;
        } else {
            $stats['skipped']++;
        }
        $u->close();
    }
}

$res = $conn->query('SELECT id, email, email_enc, email_lookup FROM google_users');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        migrate_email_row($conn, $row, $apply, 'google_users', $stats);
    }
}

migrate_phone_table($conn, '3wordid', 'id', $apply, $stats, 'phone_plate');
// Account-level leftover plaintext is not expected (no old user phone column).

echo $apply ? "Applied.\n" : "Dry run (pass --apply to write).\n";
echo 'Readable emails found: ' . $stats['email_readable'] . ', written: ' . $stats['email_written'] . "\n";
echo 'Plain plate phones found: ' . $stats['phone_plate'] . ', phones written: ' . $stats['phone_written'] . "\n";
echo 'Skipped: ' . $stats['skipped'] . "\n";
echo "Accounts that only have an md5 email will be filled in on their next login.\n";
exit(0);
