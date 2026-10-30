-- 3WordID: "Organization name" of an account (account settings page, 3wid_settings.php).
-- It is shown in the chat next to the 3WordIDs of that account, like the car make and colour.
-- Written for MySQL 8.0. Run ONCE (MySQL Workbench or the mysql command line).
-- Until this has run, the site works as before: the settings page has no Organization name
-- field and nothing extra is shown in the chat.
-- If a line gives "Duplicate column name", that column is already there: skip that line.
--
-- org_name   the organization name typed by the account (trimmed, at most 100 characters),
--            NULL = no organization name
--
-- google_users_deleted gets the same column, so the backup copy of a removed account keeps it
-- (tools/cleanup_empty_accounts.php copies every column that both tables have).

ALTER TABLE google_users
  ADD COLUMN org_name VARCHAR(100) NULL DEFAULT NULL;

ALTER TABLE google_users_deleted
  ADD COLUMN org_name VARCHAR(100) NULL DEFAULT NULL;

-- Check (each should list org_name):
-- SHOW COLUMNS FROM google_users LIKE 'org_name';
-- SHOW COLUMNS FROM google_users_deleted LIKE 'org_name';
