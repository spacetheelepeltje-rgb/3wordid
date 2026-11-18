# Bumperchat (first version)

`bumperchat.com` redirects (Plesk, 301) to `https://www.3wordid.com/bumperchat/index.php`.
Bumperchat is a second front door to 3WordID for licence plates: **chat only**, no list or admin pages.

## Pages

| File | What it does |
|---|---|
| `bumperchat/index.php` | Banner "Bumperchat.com", the homepage search box (same `js/3wid_index_2.js` + `3wid_exists.php`, so the lock / car icon and RDW yes/no work the same), four onboarding step cards, and the plate → three words lookup (country dropdown + converter + searchable list of all 27 EU countries, US and Canada). |
| `bumperchat/claim.php` | Type the three words, see if the 3WordID is free (same lookup and the same per-session limit as the search box), then create an account (email + password) or log in. |
| `bumperchat/chat.php` | "My chats": the user's 3WordIDs with their message counts; "Open chat" opens the existing chat (`3wid_list_messages.php`). Shows the status of the plate they came for and a box to message another car. |
| `bumperchat/logout.php` | Log out, back to the Bumperchat page. |
| `bumperchat/plate_formats.json` | The plate rules (see `docs/PLATE_FORMATS.md`). |

## What is reused (no copied logic)

- **Accounts**: `php/email_auth.php` now holds the email + password checks and the `INSERT INTO google_users`
  that were inside `login/email_register.php` / `login/email_login.php`. Those two pages and `claim.php` call
  the same functions: md5 email, `password_hash()`, at least 10 characters, same error texts. No verification
  email is sent (same as before). `claim.php` uses the same bot check (`botcheck_fields()` / `botcheck_failed()`)
  and CSRF token, and `establish_user_session()` for the login itself.
- **Exists check**: `db_3wordid_exists_status()`; the per-session limit moved from `3wid_exists.php` into
  `exists_check_allowed()` (php/functions.php) so the search box and `claim.php` share one counter.
- **RDW**: `car_plate_pattern()` / `car_plate_known()`: yes/no only, never make or colour.
- **Rules for the 3WordID itself**: `wid_threeword_error()` / `user_is_paying()` (digits = paid account).
- **Privacy consent**: `chat.php` sends users without consent to `3wid_privacy_consent.php` (same as `3wid_list.php`).

## Chat only, session flag (schema was added later for billing)

A Bumperchat login is marked **in the session only**: `$_SESSION['bumperchat'] = ['words' => ..., 'at' => ...]`
(set by `claim.php` right after `establish_user_session()`). With that flag `3wid_list.php` redirects to
`bumperchat/chat.php`, so every existing "My 3WordIDs" / back link and every redirect to the list
(chat top bar, delete, toggle, consent) lands on the Bumperchat chats page instead. Any normal login
(Google, `/login/email_login.php`, register) clears the flag, so the same account logging in on 3wordid.com
gets the normal list. No new column, so nothing to add to `google_users_deleted`.

If a permanent "Bumperchat account" is wanted later (for example to always hide the list), add a column such as
`google_users.signup_source` **and the same column to `google_users_deleted`**.

## Decisions for the owner (v1)

1. **Bumperchat plates now get one month free, then EUR 7.95 per year** (one-off iDEAL). See the section below.
   The old “digits need a paid 3WordID account first” rule is no longer a blocker on this path.
2. **claim.php now creates the plate 3WordID** (with the trial). Car chat is still turned on from the edit page
   with the existing RDW + Dutch IP check; one-click Car chat is still later work.
3. **Who owns a plate**: only Dutch plates are checked (RDW: exists yes/no). Nobody checks the claimer is the owner.
4. **Login**: email + password only on the Bumperchat pages. Google login works on 3wordid.com, but then lands on the
   normal list (not marked as Bumperchat).
5. **Canada prefix is `cdn`**, not `ca` (California's code). Cross-country plate collisions are possible, see PLATE_FORMATS.md.

## Upload

New: `bumperchat/` (all 5 files), `php/bumperchat.php`, `php/email_auth.php`, `js/bumperchat.js`, `css/bumperchat.css`.
Changed: `php/functions.php`, `php/session_boot.php`, `3wid_exists.php`, `3wid_list.php`, `index.php`,
`js/3wid_index_2.js`, `login/email_login.php`, `login/email_register.php`, `lang/en.php`, `lang/nl.php`,
`lang/fr.php`, `lang/es.php`. Not needed on the server: `docs/*`, `tools/test_bumperchat_plates.js`.
SQL to run once: `sql/2026-11-18_bumperchat_trial_payments.sql`. Upload `php/functions.php` together with
`php/bumperchat.php`, `php/email_auth.php`, `php/plate_billing.php`, `php/notify.php` and `php/payments/`.

## Trial, payment and SMS consent (added 18 November 2026)

Licence plates claimed through the Bumperchat homepage (`claim.php`) are now
created there (no detour via `3wid_add.php`).

- **One month free**, from the claim date. Shown as remaining days on
  `bumperchat/chat.php`.
- After that, **EUR 7.95 per year**, one-off iDEAL via Mollie (`bumperchat/pay.php`).
  No automatic renewal. Setup: `docs/MOLLIE.md`. Terms: `terms.php#bumperchat`.
- One free trial per plate and per email (table `billing_trials`). A second
  claim is reserved but already expired, so the user is sent to the pay page.
- Existing 3WordIDs are not put on this trial (the migration leaves them
  unchanged). Already-paying 3WordID accounts (`user_type > 0`) can claim a
  Bumperchat plate without this trial or the EUR 7.95 paywall.
- When the trial or a paid year ends, the owner is sent to the pay page.
  Messages are kept; chat access for that plate is paused until payment.
- Optional mobile number (E.164, e.g. `+31612345678`) and an explicit SMS
  consent checkbox are stored on the plate. Sending is not built yet; the hook
  is `notify_on_new_message()` in `php/notify.php`.

SQL to run once: `sql/2026-11-18_bumperchat_trial_payments.sql`.
Config (gitignored): copy `login/payments.config.example.php` to
`login/payments.config.php` on the server.

## Onboarding cards on the homepage

The four step cards are in `bumperchat/index.php`, in the section
`#how-it-works`, inside the clearly marked `<!-- EDIT THE ONBOARDING CARDS HERE -->`
block (around the `<div class="steps bc-steps">`). Their wording comes from
`lang/*.php` keys `bc.step1_title` / `_text` / `_link` through `bc.step4_title`
/ `_text`. This work does not change those card texts; edit them yourself if
you want the trial or the price mentioned on the homepage.
