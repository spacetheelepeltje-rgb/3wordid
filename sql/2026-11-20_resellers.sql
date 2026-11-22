-- 3WordID / Bumperchat: optional reseller codes for referral attribution.
-- Run this on the live database (for example in phpMyAdmin, SQL tab).
-- Safe to run again where MySQL allows it. See notes below.
--
-- This is a NEW table named `resellers`. It is not the older kickback
-- list in `3wordid_resellers` (used by 3wid_signup / 3wid_reseller.php).
-- Do not mix the two.
--
-- Idempotent:
--   * CREATE TABLE IF NOT EXISTS is always safe.
--   * ADD COLUMN IF NOT EXISTS needs MySQL 8.0.12+ or MariaDB 10.0.2+.
--     If your server rejects IF NOT EXISTS, use the commented alternatives
--     and skip any statement that errors with "Duplicate column name".
--   * Indexes are added only when the name is missing (prepared statements).
--
-- Codes are stored uppercase. The unique index is case-insensitive because
-- the table uses utf8mb4_unicode_ci. Allowed pattern (enforced in PHP):
-- 3–20 characters, A–Z, 0–9 and hyphen.

CREATE TABLE IF NOT EXISTS resellers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(120) NOT NULL,
  contact VARCHAR(255) NULL DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_resellers_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account attribution: set only once (first valid code wins). PHP never
-- overwrites a non-NULL value. Also added on the deleted-users backup table
-- so a later restore keeps the same columns.
ALTER TABLE google_users
  ADD COLUMN IF NOT EXISTS reseller_id INT UNSIGNED NULL DEFAULT NULL;

ALTER TABLE google_users_deleted
  ADD COLUMN IF NOT EXISTS reseller_id INT UNSIGNED NULL DEFAULT NULL;

-- Each payment/order can be attributed (from the user record, or from a
-- code entered on the pay page if the user had none yet).
ALTER TABLE payments
  ADD COLUMN IF NOT EXISTS reseller_id INT UNSIGNED NULL DEFAULT NULL;

-- MySQL alternative if ADD COLUMN IF NOT EXISTS is not supported:
-- ALTER TABLE google_users ADD COLUMN reseller_id INT UNSIGNED NULL DEFAULT NULL;
-- ALTER TABLE google_users_deleted ADD COLUMN reseller_id INT UNSIGNED NULL DEFAULT NULL;
-- ALTER TABLE payments ADD COLUMN reseller_id INT UNSIGNED NULL DEFAULT NULL;

-- Indexes (skip when the name already exists).
SET @idx := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'google_users'
    AND index_name = 'idx_google_users_reseller'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE google_users ADD INDEX idx_google_users_reseller (reseller_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'payments'
    AND index_name = 'idx_payments_reseller'
);
SET @sql := IF(@idx = 0, 'ALTER TABLE payments ADD INDEX idx_payments_reseller (reseller_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
