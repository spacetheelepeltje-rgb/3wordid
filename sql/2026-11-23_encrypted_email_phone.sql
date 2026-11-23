-- Encrypted storage for account emails and mobile numbers (Bumperchat / 3WordID).
-- Written for MySQL 8.0. Run ONCE (phpMyAdmin, MySQL Workbench, or mysql).
-- If a line gives "Duplicate column name" or "Duplicate key name", that part is
-- already there: skip that line.
--
-- google_users.email stays as the legacy md5 of the address (login fallback).
-- Existing md5 values cannot be turned back into an address: the encrypted
-- copy and the lookup hash are filled in on the next login (when the person
-- types or Google returns the address), or by tools/encrypt_stored_pii.php
-- for any leftover readable values.
--
-- Keys are NOT in this file. Add EMAIL_ENC_KEY and EMAIL_HASH_KEY to the
-- server-only login/config.php (see login/email_keys.example.php).
-- google_users_deleted and 3wordid_deleted get the same columns.

-- Account email (encrypted) + keyed lookup hash
ALTER TABLE google_users
  ADD COLUMN email_enc TEXT NULL DEFAULT NULL,
  ADD COLUMN email_lookup CHAR(64) NULL DEFAULT NULL,
  ADD COLUMN signup_details_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN notify_phone_enc TEXT NULL DEFAULT NULL,
  ADD COLUMN notify_sms_consent TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN notify_sms_consent_at DATETIME NULL DEFAULT NULL;

ALTER TABLE google_users_deleted
  ADD COLUMN email_enc TEXT NULL DEFAULT NULL,
  ADD COLUMN email_lookup CHAR(64) NULL DEFAULT NULL,
  ADD COLUMN signup_details_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN notify_phone_enc TEXT NULL DEFAULT NULL,
  ADD COLUMN notify_sms_consent TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN notify_sms_consent_at DATETIME NULL DEFAULT NULL;

-- Lookup index (not unique: a NULL lookup is allowed for old rows not yet backfilled)
ALTER TABLE google_users
  ADD INDEX idx_google_users_email_lookup (email_lookup);

-- Plate mobile numbers (encrypted). The old notify_phone VARCHAR(20) column
-- is too short for a ciphertext; it is cleared after a number is moved.
ALTER TABLE 3wordid
  ADD COLUMN notify_phone_enc TEXT NULL DEFAULT NULL;

ALTER TABLE 3wordid_deleted
  ADD COLUMN notify_phone_enc TEXT NULL DEFAULT NULL;

-- Check:
-- SHOW COLUMNS FROM google_users WHERE Field IN ('email_enc','email_lookup','signup_details_at','notify_phone_enc');
-- SHOW COLUMNS FROM 3wordid LIKE 'notify_phone_enc';
-- SHOW INDEX FROM google_users WHERE Key_name = 'idx_google_users_email_lookup';
