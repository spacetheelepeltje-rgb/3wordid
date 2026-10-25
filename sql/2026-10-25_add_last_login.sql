-- 3WordID: store the date and time of each user's most recent login.
-- Run this ONCE on the database (for example in phpMyAdmin, SQL tab).
-- It is safe to run again: nothing changes if the column already exists.
-- Requires MariaDB 10.0.2+ for "IF NOT EXISTS". On MySQL, use the
-- second statement instead (and skip it if the column already exists).

ALTER TABLE google_users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL DEFAULT NULL;

-- MySQL alternative (run only if the line above gives a syntax error):
-- ALTER TABLE google_users ADD COLUMN last_login DATETIME NULL DEFAULT NULL;
