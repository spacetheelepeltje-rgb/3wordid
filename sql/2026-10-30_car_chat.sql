-- 3WordID: "Car chat" (a Dutch licence plate 3WordID, checked in the RDW register) and the
-- open / closed setting for "Link to chat" and "Car chat".
-- Written for MySQL 8.0. Run ONCE (MySQL Workbench or the mysql command line).
-- Until this has run, the site works as before: no Car chat option, and every chat is open.
-- If a line gives "Duplicate column name", that column is already there: skip that line.
--
-- car_chat        1 = car chat is on (always together with emailform = 1, "Link to chat")
-- car_plate       the plate as checked at RDW, e.g. 1KBB00
-- car_make        make + model from RDW (merk + handelsbenaming), e.g. KIA PICANTO
-- car_colour      first colour from RDW (eerste_kleur), e.g. WIT
-- car_checked_at  when RDW was checked (when car chat was turned on)
-- chat_open       1 = open: any 3WordID / visitor can message it and it can message any 3WordID
--                 0 = closed: both ways only 3WordIDs of the same account (the default)
--                 Car chat is set to open when it is turned on (unless the owner picks closed).
--
-- 3wordid_deleted gets the same columns, so the copy of a deleted 3WordID keeps them.

ALTER TABLE 3wordid
  ADD COLUMN car_chat TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN car_plate VARCHAR(8) NULL DEFAULT NULL,
  ADD COLUMN car_make VARCHAR(100) NULL DEFAULT NULL,
  ADD COLUMN car_colour VARCHAR(50) NULL DEFAULT NULL,
  ADD COLUMN car_checked_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN chat_open TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE 3wordid_deleted
  ADD COLUMN car_chat TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN car_plate VARCHAR(8) NULL DEFAULT NULL,
  ADD COLUMN car_make VARCHAR(100) NULL DEFAULT NULL,
  ADD COLUMN car_colour VARCHAR(50) NULL DEFAULT NULL,
  ADD COLUMN car_checked_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN chat_open TINYINT(1) NOT NULL DEFAULT 0;

-- IMPORTANT, your choice: with the lines above, every EXISTING "Link to chat" 3WordID becomes
-- CLOSED at once: visitors and other accounts can no longer message it. To keep the existing
-- ones open as they are today (new ones still start closed), also run this line:
-- UPDATE 3wordid SET chat_open = 1 WHERE emailform = 1;
--
-- How many 3WordIDs this is about:
-- SELECT COUNT(*) FROM 3wordid WHERE emailform = 1;

-- Check (each should list the six new columns):
-- SHOW COLUMNS FROM 3wordid WHERE Field LIKE 'car%' OR Field = 'chat_open';
-- SHOW COLUMNS FROM 3wordid_deleted WHERE Field LIKE 'car%' OR Field = 'chat_open';
