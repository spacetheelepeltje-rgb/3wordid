-- 3WordID: needed for tools/cleanup_empty_accounts.php (removing stale empty accounts).
-- Run ONCE in phpMyAdmin (SQL tab). Safe to run again. MariaDB 10.0.2+ for "IF NOT EXISTS";
-- MySQL alternatives are at the bottom.
--
-- Check first which columns google_users already has:
--   SHOW COLUMNS FROM google_users;
-- If it already has an account creation date column (creation_date, created, date_created,
-- registration_date or signup_date), the cleanup uses that one and step 1 changes nothing that
-- the cleanup looks at.

-- 1. Account creation date. New accounts get the current date and time automatically.
--    Existing accounts get the moment this runs, so none of them is older than 30 days yet:
--    the first removals happen 30 days from now at the earliest (unless step 3 is run).
ALTER TABLE google_users
  ADD COLUMN IF NOT EXISTS created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

-- 2. Backup table, same columns as google_users plus when and why it was removed.
--    The cleanup copies named columns into it (login token, session, CSRF token and password
--    hash are stored empty).
CREATE TABLE IF NOT EXISTS google_users_deleted LIKE google_users;
ALTER TABLE google_users_deleted
  ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS delete_reason VARCHAR(64) NULL DEFAULT NULL;

-- 3. OPTIONAL: an account that last logged in on a certain date existed on that date, so that
--    date is a safe "at least this old" for existing accounts. Run only right after step 1
--    added created_at, and only if you want existing accounts to count from their last login.
-- UPDATE google_users SET created_at = last_login
--   WHERE last_login IS NOT NULL AND last_login < created_at;

-- Read-only check: how many accounts the cleanup would look at (older than 30 days, no 3WordID).
-- SELECT COUNT(*) FROM google_users u
--   WHERE u.id <> 4 AND u.created_at < NOW() - INTERVAL 30 DAY
--     AND NOT EXISTS (SELECT 1 FROM 3wordid w WHERE w.user_id = u.id);

-- MySQL alternatives (only if the statements above give a syntax error):
-- ALTER TABLE google_users ADD COLUMN created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP;
-- CREATE TABLE IF NOT EXISTS google_users_deleted LIKE google_users;
-- ALTER TABLE google_users_deleted ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL;
-- ALTER TABLE google_users_deleted ADD COLUMN delete_reason VARCHAR(64) NULL DEFAULT NULL;
