-- 3WordID: remember-me tokens for "Stay logged in" / "Ingelogd blijven".
-- Used by login/email_login.php and bumperchat/login.php.
--
-- Written for MySQL 8.0. Run ONCE (phpMyAdmin, MySQL Workbench, or mysql).
-- Until this has run, the remember-me checkbox is shown but does nothing
-- (login still works as a normal browser session). If the CREATE gives
-- "Table already exists", this part is already there: skip it.
--
-- The cookie (3WIDREM) holds a random selector and a random validator.
-- Only a SHA-256 hash of the validator is stored here. Tokens last 30 days,
-- are rotated on each use, deleted on logout, and all of a user's tokens
-- are deleted when their password is changed (3wid_user_edit.php).

CREATE TABLE auth_remember_tokens (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT NOT NULL,
  selector        CHAR(32) NOT NULL,
  validator_hash  CHAR(64) NOT NULL,
  expires_at      DATETIME NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at    DATETIME NULL DEFAULT NULL,
  context         VARCHAR(32) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uq_selector (selector),
  KEY idx_user (user_id),
  KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check:
-- SHOW TABLES LIKE 'auth_remember_tokens';
-- SHOW COLUMNS FROM auth_remember_tokens;
