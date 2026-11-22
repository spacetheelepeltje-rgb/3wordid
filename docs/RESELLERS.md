# Optional reseller codes

This feature lets the site owner see which reseller brought in a user who
registers or pays. There is no discount and no price change. A code is
optional: typo, unknown and inactive codes are ignored and never block
signup or payment.

This is **not** the older kickback list (`3wordid_resellers`, used by
`3wid_signup.php` / `3wid_reseller.php`). The new table is named `resellers`.
Do not mix the two.

## What to run and upload

1. Run `sql/2026-11-20_resellers.sql` once on the database (phpMyAdmin SQL tab
   is fine). It is idempotent where MySQL allows it; see comments in the file.
2. Upload the changed PHP, CSS, language and terms files listed in the
   pull request. Nothing here changes hosting or `.htaccess`.
3. Do not merge or deploy from this document; the operator merges when ready.

Until the SQL has run, registration and payment work as before. Codes are
simply not stored.

## How a code is collected

- Visitors can type an optional **Reseller code** on:
  - `bumperchat/claim_register.php` (Bumperchat registration, and on the
    logged-in claim form if the account has no code yet)
  - `bumperchat/pay.php` (if the account has no code yet)
  - `login/email_register.php` (general 3WordID registration)
- The field sits behind “Have a reseller code?” and stays closed unless a
  code is already known (typed, `?ref=`, session or cookie).
- Any Bumperchat page — and the rest of the site, because capture runs after
  the shared session boot — accepts `?ref=CODE` (for example
  `https://bumperchat.com/index.php?ref=PARTNER-1`). A valid code is stored
  in the session and in a first-party cookie `3WIDREF` for 30 days
  (`HttpOnly`, `Secure` when HTTPS, `SameSite=Lax`, `path=/`), then prefilled
  on the form.
- Codes are normalised to uppercase and trimmed. Allowed pattern: 3–20
  characters, `A–Z`, `0–9` and hyphen. Payment return URLs also use `ref=`
  for a 24-character order reference; that value is longer than 20 characters,
  so it is ignored here and is not treated as a reseller code.

## Attribution rules

- Server-side lookup is authoritative. JavaScript is not required.
- An unknown or inactive code shows the inline note “Unknown reseller code”
  (language keys in EN/NL/FR/ES) and is **not** stored. Signup and payment
  continue.
- On registration, `google_users.reseller_id` is set only when it is still
  empty. The first valid code wins; later codes are ignored.
- On payment creation, `payments.reseller_id` is taken from the user record,
  or from a code entered on the pay page (which also sets the user’s reseller
  if that was still empty). Nothing client-side is trusted beyond the code
  string itself.

## Admin

Open **`3wid_resellers.php`** (admin only, same check as the other admin
pages: user id 4). Links are in the admin header and as **R** on
`3wid_list.php`.

The page lists each reseller’s code, name, contact, active flag, how many
users it brought in, and the count and total EUR of payments with
`status = 'paid'`. You can add a reseller and toggle active. Forms use the
same CSRF token as the other admin pages.

To insert a reseller by SQL instead:

```sql
INSERT INTO resellers (code, name, contact, active)
VALUES ('PARTNER-1', 'Partner name', 'optional@email', 1);
```

Deactivate with `UPDATE resellers SET active = 0 WHERE code = 'PARTNER-1';`.
Inactive codes are treated like unknown codes: they are not stored, and they
do not block the visitor.

## Privacy

Terms §10 mentions that an optional reseller code may be stored (and remembered
in the `3WIDREF` cookie for 30 days) to attribute the referral.

## Tests

From the repo root (not needed on the web server):

```bash
php tools/test_resellers.php
php tools/test_plate_billing.php
php tools/test_bumperchat_flow.php
php tools/test_pending_messages.php
node tools/test_bumperchat_plates.js
php tools/check_lang.php
```
