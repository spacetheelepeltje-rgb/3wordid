-- 3WordID: two-way chat (send time, read receipts, faster conversation lookups).
-- Run this ONCE on the database (for example in phpMyAdmin, SQL tab).
-- It is safe to run again: nothing changes if a column or index already exists.
-- Requires MariaDB 10.1.4+ for "IF NOT EXISTS" on indexes. On MySQL, use the
-- alternative below instead (and skip any line whose column/index already exists).
-- The site works without these columns too (no times, no read ticks, no unread dots).
-- Note: if creation_date is new, existing messages get today's date/time as their send time.

ALTER TABLE 3wordid_messages
  ADD COLUMN IF NOT EXISTS creation_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS read_at DATETIME NULL DEFAULT NULL,
  ADD INDEX IF NOT EXISTS idx_pair (3wid_id, 3wid_from_id),
  ADD INDEX IF NOT EXISTS idx_from (3wid_from_id);

-- Deleted messages are copied here first: keep the same columns.
ALTER TABLE 3wordid_messages_deleted
  ADD COLUMN IF NOT EXISTS creation_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS read_at DATETIME NULL DEFAULT NULL;

-- Messages that are already there count as read (otherwise every old message shows as unread).
-- Only the FIRST time: running it again later would mark new unread messages as read.
UPDATE 3wordid_messages SET read_at = NOW() WHERE read_at IS NULL;

-- Check (should list creation_date and read_at):
-- SHOW COLUMNS FROM 3wordid_messages WHERE Field IN ('creation_date','read_at');

-- MySQL alternative (run only if the statements above give a syntax error):
-- ALTER TABLE 3wordid_messages ADD COLUMN creation_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
-- ALTER TABLE 3wordid_messages ADD COLUMN read_at DATETIME NULL DEFAULT NULL;
-- ALTER TABLE 3wordid_messages ADD INDEX idx_pair (3wid_id, 3wid_from_id);
-- ALTER TABLE 3wordid_messages ADD INDEX idx_from (3wid_from_id);
-- ALTER TABLE 3wordid_messages_deleted ADD COLUMN creation_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
-- ALTER TABLE 3wordid_messages_deleted ADD COLUMN read_at DATETIME NULL DEFAULT NULL;
-- UPDATE 3wordid_messages SET read_at = NOW() WHERE read_at IS NULL;
