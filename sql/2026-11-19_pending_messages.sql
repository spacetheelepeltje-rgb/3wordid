-- 3WordID / Bumperchat: messages to unregistered licence plates.
-- Used when a logged-in member leaves a message for a valid plate that has
-- no claimed 3WordID yet. Delivered into 3wordid_messages when the plate is
-- claimed. See php/pending_messages.php.
--
-- Written for MySQL 8.0 / MariaDB 10.4+. Run ONCE (phpMyAdmin, MySQL
-- Workbench, or mysql). If the CREATE gives "Table already exists", this
-- part is already there: skip it.
-- Until this has run, the "leave a message" form is shown but cannot store
-- a pending message (a clear note is displayed).
--
-- Waiting rows expire 120 days after created_at. Expired rows keep a
-- minimal record (status, timestamps, words) so the sender can see
-- "expired"; the message body is cleared. Expired rows do not count
-- towards the 3-message-per-sender-per-plate limit.
--
-- Cleanup: php/pending_messages.php expires due rows lazily on access.
-- Optional cron (you decide whether to schedule it):
--   php tools/cleanup_pending_messages.php

CREATE TABLE pending_messages (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  threeword             VARCHAR(80)  NOT NULL,
  sender_user_id        INT          NOT NULL,
  from_wid_id           INT          NULL DEFAULT NULL,
  country               VARCHAR(8)   NOT NULL DEFAULT '',
  region                VARCHAR(8)   NOT NULL DEFAULT '',
  plate                 VARCHAR(32)  NOT NULL DEFAULT '',
  title                 VARCHAR(255) NOT NULL DEFAULT '',
  message               TEXT         NULL,
  status                VARCHAR(16)  NOT NULL DEFAULT 'waiting',
  created_at            DATETIME     NOT NULL,
  expires_at            DATETIME     NOT NULL,
  status_changed_at     DATETIME     NOT NULL,
  delivered_at          DATETIME     NULL DEFAULT NULL,
  read_at               DATETIME     NULL DEFAULT NULL,
  expired_at            DATETIME     NULL DEFAULT NULL,
  delivered_message_id  INT          NULL DEFAULT NULL,
  delivered_wid_id      INT          NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_words_status (threeword, status),
  KEY idx_sender_words_status (sender_user_id, threeword, status),
  KEY idx_sender_status (sender_user_id, status),
  KEY idx_from_wid (from_wid_id),
  KEY idx_expires (expires_at, status),
  KEY idx_delivered_msg (delivered_message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check:
-- SHOW TABLES LIKE 'pending_messages';
-- SHOW COLUMNS FROM pending_messages;
