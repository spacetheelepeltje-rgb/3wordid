-- 3WordID / Bumperchat: one-month free trial for plates claimed via the Bumperchat
-- homepage, then a one-off iDEAL payment (Mollie) for a paid year.
-- Also stores an optional E.164 mobile number + SMS-notification consent
-- (sending is not built yet).
--
-- Written for MySQL 8.0. Run ONCE (phpMyAdmin, MySQL Workbench, or mysql).
-- Until this has run, Bumperchat works as before: claim.php will not create a
-- plate or start a trial, and the pay page will say the database is not ready.
-- If a line gives "Duplicate column name" or "Table already exists", that part
-- is already there: skip that line.
--
-- Existing 3WordIDs are left unchanged (trial_*, paid_until and claim_source
-- stay NULL). They are not put on a Bumperchat trial and are not sent to the
-- pay page. Only plates claimed through Bumperchat after this migration are
-- billed this way.
--
-- 3wordid_deleted gets the same new columns, so a deleted plate keeps them.
-- billing_trials survives deletion, so the same plate or the same email cannot
-- get a second free month.

-- Plate billing and optional SMS notify fields
ALTER TABLE 3wordid
  ADD COLUMN claim_source VARCHAR(32) NULL DEFAULT NULL,
  ADD COLUMN billing_product VARCHAR(40) NULL DEFAULT NULL,
  ADD COLUMN trial_start DATETIME NULL DEFAULT NULL,
  ADD COLUMN trial_expires DATETIME NULL DEFAULT NULL,
  ADD COLUMN paid_until DATETIME NULL DEFAULT NULL,
  ADD COLUMN notify_phone VARCHAR(20) NULL DEFAULT NULL,
  ADD COLUMN notify_sms_consent TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN notify_sms_consent_at DATETIME NULL DEFAULT NULL;

ALTER TABLE 3wordid_deleted
  ADD COLUMN claim_source VARCHAR(32) NULL DEFAULT NULL,
  ADD COLUMN billing_product VARCHAR(40) NULL DEFAULT NULL,
  ADD COLUMN trial_start DATETIME NULL DEFAULT NULL,
  ADD COLUMN trial_expires DATETIME NULL DEFAULT NULL,
  ADD COLUMN paid_until DATETIME NULL DEFAULT NULL,
  ADD COLUMN notify_phone VARCHAR(20) NULL DEFAULT NULL,
  ADD COLUMN notify_sms_consent TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN notify_sms_consent_at DATETIME NULL DEFAULT NULL;

-- One free trial per plate (threeword) and ideally per email (hash, same as google_users.email)
CREATE TABLE billing_trials (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email_hash    CHAR(32) NULL DEFAULT NULL,
  threeword     VARCHAR(80) NOT NULL,
  user_id       INT NOT NULL,
  wid_id        INT NULL DEFAULT NULL,
  source        VARCHAR(32) NOT NULL DEFAULT 'bumperchat',
  product_key   VARCHAR(40) NOT NULL DEFAULT 'bumperchat_plate',
  started_at    DATETIME NOT NULL,
  expires_at    DATETIME NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_email_hash (email_hash),
  KEY idx_threeword (threeword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One-off payments for any product (Bumperchat plate now; 3WordID tiers later).
-- amount / currency / period are copied from config at payment time, so a paid
-- year keeps the price that was paid even if the listed price later changes.
CREATE TABLE payments (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_ref           CHAR(24) NOT NULL,
  user_id             INT NOT NULL,
  wid_id              INT NULL DEFAULT NULL,
  product_key         VARCHAR(40) NOT NULL,
  subject             VARCHAR(80) NULL DEFAULT NULL,
  amount              VARCHAR(16) NOT NULL,
  currency            CHAR(3) NOT NULL DEFAULT 'EUR',
  interval_unit       VARCHAR(16) NOT NULL DEFAULT 'year',
  interval_count      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  method              VARCHAR(16) NOT NULL DEFAULT 'ideal',
  mollie_payment_id   VARCHAR(64) NULL DEFAULT NULL,
  checkout_url        VARCHAR(512) NULL DEFAULT NULL,
  status              VARCHAR(32) NOT NULL DEFAULT 'open',
  paid_at             DATETIME NULL DEFAULT NULL,
  period_start        DATETIME NULL DEFAULT NULL,
  period_end          DATETIME NULL DEFAULT NULL,
  metadata_json       TEXT NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_order_ref (order_ref),
  UNIQUE KEY uq_mollie_payment (mollie_payment_id),
  KEY idx_user (user_id),
  KEY idx_wid (wid_id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook log (idempotency and debugging). Status is taken from Mollie, never from the POST body.
CREATE TABLE payment_events (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mollie_payment_id   VARCHAR(64) NOT NULL,
  status_seen         VARCHAR(32) NOT NULL,
  received_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at        DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_event (mollie_payment_id, status_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check:
-- SHOW COLUMNS FROM 3wordid WHERE Field IN ('claim_source','billing_product','trial_start','trial_expires','paid_until','notify_phone','notify_sms_consent','notify_sms_consent_at');
-- SHOW TABLES LIKE 'payments';
-- SHOW TABLES LIKE 'billing_trials';
-- SHOW TABLES LIKE 'payment_events';
