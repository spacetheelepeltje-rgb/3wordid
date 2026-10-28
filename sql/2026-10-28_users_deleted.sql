-- 3WordID: backup table for tools/cleanup_empty_accounts.php (removing stale empty accounts).
-- Written for the live server: MySQL 8.0 (checked against the schema of 2026-10-28).
-- Run ONCE in phpMyAdmin (SQL tab). Running it a second time gives "Duplicate column" or
-- "Can't DROP" errors: harmless, the table is already there.
--
-- Nothing needs to be added to google_users: it already has created_at (account creation
-- date, filled automatically) and last_login.

-- Backup table: same columns as google_users, plus when and why the account was removed.
CREATE TABLE IF NOT EXISTS google_users_deleted LIKE google_users;

-- google_users has UNIQUE keys on email and token. In the backup they would make a second
-- removal of the same email address fail, and the cleanup stores token as '' (login secrets
-- are not kept), so drop them there. Plain lookups by email stay possible.
ALTER TABLE google_users_deleted
  DROP INDEX `email`,
  DROP INDEX `token`,
  ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN delete_reason VARCHAR(64) NULL DEFAULT NULL,
  ADD INDEX idx_email (email);

-- Read-only check: accounts the cleanup will look at (older than 30 days, no 3WordID,
-- not the admin). The dry run of the script shows per account what it would do.
-- SELECT id, created_at, last_login, credit FROM google_users u
--   WHERE u.id <> 4 AND u.created_at < NOW() - INTERVAL 30 DAY
--     AND NOT EXISTS (SELECT 1 FROM 3wordid w WHERE w.user_id = u.id);
