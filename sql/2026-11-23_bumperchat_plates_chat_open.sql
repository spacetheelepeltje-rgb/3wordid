-- Bumperchat plates claimed before this fix were created with chat_open = 0 (closed), which
-- blocked them from messaging other accounts and from being messaged (chat_access_error).
-- New claims are opened by bumperchat_claim_plate(). Run ONCE to open the plates that already exist.
--
-- Only plates that were never edited since they were claimed (update_date within 2 s of creation_date) are
-- touched, so a plate whose owner deliberately chose "Closed" on the edit page stays closed.
-- Check first:  SELECT id, threeword, chat_open, creation_date, update_date FROM 3wordid
--               WHERE claim_source = 'bumperchat' AND chat_open = 0;
UPDATE 3wordid
   SET chat_open = 1
 WHERE claim_source = 'bumperchat'
   AND emailform = 1
   AND chat_open = 0
   AND ABS(TIMESTAMPDIFF(SECOND, creation_date, update_date)) <= 2;
