-- 3WordID: "Message with link" - the text of the link shown below the message.
-- Run this ONCE on the database (for example in phpMyAdmin, SQL tab). MySQL 8.
-- The link itself uses the existing linkthru column; only the title is new.
-- Until this has run, the site works as before and the "Message with link" option is not shown.
--
-- 3wordid_deleted gets the same column, so a deleted "Message with link" keeps its title
-- in the backup copy. (Deleting also works if you only run the first line.)
-- If a line gives "Duplicate column name 'link_title'", that column is already there: skip it.

ALTER TABLE 3wordid ADD COLUMN link_title VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE 3wordid_deleted ADD COLUMN link_title VARCHAR(255) NULL DEFAULT NULL;

-- Check (both should show link_title):
-- SHOW COLUMNS FROM 3wordid LIKE 'link_title';
-- SHOW COLUMNS FROM 3wordid_deleted LIKE 'link_title';
