-- 3WordID: message rate limit (spam protection) - indexes, plus a column for anonymous visitors.
-- Run this ONCE on the database (for example in phpMyAdmin, SQL tab).
-- It is safe to run again: nothing changes if a column or index already exists.
-- Needs the creation_date column from sql/2026-10-27_two_way_chat.sql (run that one first).
-- Requires MariaDB 10.1.4+ for "IF NOT EXISTS" on indexes. On MySQL, use the alternative below.
--
-- The site works without this too: the limits then still count per 3WordID (slower without
-- idx_to_date), and anonymous visitors are counted per browser session instead of per IP.
--
-- sender_key: only for anonymous visitors. A keyed hash of the IP address (HMAC-SHA256, 32 hex
-- characters), never the IP itself; it is cleared after 2 days (the limit only looks at the
-- last hour) and is not copied to 3wordid_messages_deleted. Members get nothing new stored.

ALTER TABLE 3wordid_messages
  ADD COLUMN IF NOT EXISTS sender_key CHAR(32) NULL DEFAULT NULL,
  ADD INDEX IF NOT EXISTS idx_to_date (3wid_id, creation_date),
  ADD INDEX IF NOT EXISTS idx_sender_date (sender_key, creation_date);

-- Check (should list sender_key, and the two indexes):
-- SHOW COLUMNS FROM 3wordid_messages WHERE Field = 'sender_key';
-- SHOW INDEX FROM 3wordid_messages WHERE Key_name IN ('idx_to_date', 'idx_sender_date');

-- MySQL alternative (run only if the statement above gives a syntax error; skip a line whose
-- column or index already exists):
-- ALTER TABLE 3wordid_messages ADD COLUMN sender_key CHAR(32) NULL DEFAULT NULL;
-- CREATE INDEX idx_to_date ON 3wordid_messages (3wid_id, creation_date);
-- CREATE INDEX idx_sender_date ON 3wordid_messages (sender_key, creation_date);
