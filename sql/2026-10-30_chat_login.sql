-- 3WordID: "Chat login" - a member logs in with the three words of one 3WordID and a
-- password set by the owner (admin), and sees only that 3WordID's chat.
-- Written for MySQL 8.0. Run ONCE in phpMyAdmin (SQL tab).
-- Until this has run, the site works as before and "Chat login" is not shown anywhere.
-- If a line gives "Duplicate column name", that column is already there: skip that line.
--
-- chat_login       1 = members may log in with this 3WordID and its password
-- chat_pw_hash     password_hash() of the chat password (never the password itself)
-- chat_pw_version  goes up by one on every password change or switch off; a member session
--                  that remembers an older number is logged out at once
--
-- 3wordid_deleted gets the same columns, so the backup copy of a deleted 3WordID keeps
-- matching columns (the password hash itself is not kept there: it is cleared on delete).

ALTER TABLE 3wordid
  ADD COLUMN chat_login TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN chat_pw_hash VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN chat_pw_version INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE 3wordid_deleted
  ADD COLUMN chat_login TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN chat_pw_hash VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN chat_pw_version INT UNSIGNED NOT NULL DEFAULT 0;

-- Failed chat logins, for the lockout (5 wrong tries per 3WordID, 20 per IP address, in
-- 15 minutes). rate_key is a keyed hash (HMAC-SHA256) of the typed three words or of the
-- IP address, never the words or the IP themselves. Rows older than a day are removed
-- automatically by the login page.
CREATE TABLE IF NOT EXISTS 3wordid_chat_login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rate_key CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_key_time (rate_key, created_at),
  KEY idx_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Check (each should list the three new columns, and the table should exist):
-- SHOW COLUMNS FROM 3wordid LIKE 'chat%';
-- SHOW COLUMNS FROM 3wordid_deleted LIKE 'chat%';
-- SHOW TABLES LIKE '3wordid_chat_login_attempts';
