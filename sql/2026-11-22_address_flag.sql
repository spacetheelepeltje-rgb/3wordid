-- 3WordID: "This is an address" flag on a 3WordID (zip code + house number, etc.).
-- Written for MySQL 8.0. Run ONCE (MySQL Workbench or the mysql command line).
-- Until this has run, the site works as before: no checkbox on the create/edit form,
-- and the homepage search keeps the lock (or car) icon.
-- If a line gives "Duplicate column name", that column is already there: skip that line.
--
-- is_address   1 = the owner marked this 3WordID as an address
--              0 = not an address (the default)
--
-- 3wordid_deleted gets the same column, so the copy of a deleted 3WordID keeps it.

ALTER TABLE 3wordid
  ADD COLUMN is_address TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE 3wordid_deleted
  ADD COLUMN is_address TINYINT(1) NOT NULL DEFAULT 0;

-- Check (each should list is_address):
-- SHOW COLUMNS FROM 3wordid LIKE 'is_address';
-- SHOW COLUMNS FROM 3wordid_deleted LIKE 'is_address';
