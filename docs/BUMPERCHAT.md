# Bumperchat (first version)

`bumperchat.com` redirects (Plesk, 301) to `https://www.3wordid.com/bumperchat/index.php`.
Bumperchat is a second front door to 3WordID for licence plates: **chat only**, no list or admin pages.

## Pages

| File | What it does |
|---|---|
| `bumperchat/index.php` | Banner "Bumperchat.com", the homepage search box (same `js/3wid_index_2.js` + `3wid_exists.php`, so the lock / car icon and RDW yes/no work the same), four onboarding step cards, and the plate → three words lookup (country dropdown + converter + searchable list of all 27 EU countries, US and Canada). The country dropdown is preselected with `bumperchat_default_country()` (see below). |
| `bumperchat/claim.php` | Country dropdown and a large licence-plate input on one page. Rules and an example stay visible. The plate is auto-formatted (uppercase, dashes/spaces) and validated live; Next stays off until the plate fits the country (the server checks again on submit). A logged-in user is then claimed immediately; otherwise they go to registration. Works without JavaScript. |
| `bumperchat/claim_plate.php` | Redirects to `claim.php`, keeping country, plate, region and words query parameters (old links). |
| `bumperchat/claim_register.php` | Register with email + password and an optional mobile number (E.164 + SMS consent). The confirmed plate is shown read-only, with an Edit link back to `claim.php`. No login form here. A user who is already logged in skips this step and the plate is claimed immediately. After registration the user is logged in and sent to chat (or to pay if the free month was already used). |
| `bumperchat/login.php` | Top-right email + password login, with an optional "Stay logged in" checkbox. After login, return to the claim flow or to chat. |
| `bumperchat/chat.php` | "My chats": the user's 3WordIDs with their message counts; "Open chat" opens the existing chat (`3wid_list_messages.php`). Shows the status of the plate they came for and a box to message another car. |
| `bumperchat/logout.php` | Log out (also deletes the remember-me cookie), back to the Bumperchat page. |
| `bumperchat/plate_formats.json` | The plate rules (see `docs/PLATE_FORMATS.md`). |

## What is reused (no copied logic)

- **Accounts**: `php/email_auth.php` holds the email + password checks and the `INSERT INTO google_users`
  used by `login/email_register.php`, `login/email_login.php`, `bumperchat/login.php` and
  `bumperchat/claim_register.php`. md5 email, `password_hash()`, at least 10 characters, same error texts.
  No verification email is sent. Registration uses the same bot check (`botcheck_fields()` /
  `botcheck_failed()`) and CSRF token, and `establish_user_session()` for the login itself.
- **Exists check**: `db_3wordid_exists_status()`; the per-session limit lives in
  `exists_check_allowed()` (php/functions.php) so the search box and the claim flow share one counter.
- **Plate validation and formatting**: `bumperchat_plate_to_words()` / `bumperchat_format_plate()` in
  `php/bumperchat.php` match `plateToWords()` / `formatPlate()` in `js/bumperchat.js`. Tests in `tools/`
  check them against `plate_formats.json`.
- **RDW**: `car_plate_pattern()` / `car_plate_known()`: yes/no only, never make or colour.
- **Rules for the 3WordID itself**: `wid_threeword_error()` / `user_is_paying()` (digits = paid account).
- **Privacy consent**: `chat.php` sends users without consent to `3wid_privacy_consent.php` (same as `3wid_list.php`).

## Chat only, session flag (schema was added later for billing)

A Bumperchat login is marked **in the session only**: `$_SESSION['bumperchat'] = ['words' => ..., 'at' => ...]`
(set by `bumperchat/login.php` and `claim_register.php` right after `establish_user_session()`). With that
flag `3wid_list.php` redirects to `bumperchat/chat.php`, so every existing "My 3WordIDs" / back link and
every redirect to the list (chat top bar, delete, toggle, consent) lands on the Bumperchat chats page
instead. Any normal login (Google, `/login/email_login.php`, register) clears the flag, so the same
account logging in on 3wordid.com gets the normal list. No new column, so nothing to add to
`google_users_deleted`. A remember-me cookie issued from `bumperchat/login.php` restores this flag
when the session is gone (its `context` column is `bumperchat`).

If a permanent "Bumperchat account" is wanted later (for example to always hide the list), add a column such as
`google_users.signup_source` **and the same column to `google_users_deleted`**.

## Decisions for the owner (v1)

1. **Bumperchat plates now get one month free, then EUR 7.95 per year** (one-off iDEAL). See the section below.
   The old “digits need a paid 3WordID account first” rule is no longer a blocker on this path.
2. **claim.php now creates the plate 3WordID** (with the trial). Car chat is still turned on from the edit page
   with the existing RDW + Dutch IP check; one-click Car chat is still later work.
3. **Who owns a plate**: only Dutch plates are checked (RDW: exists yes/no). Nobody checks the claimer is the owner.
4. **Login**: email + password only on the Bumperchat pages (top-right corner, `bumperchat/login.php`).
   There is no login form inside the claim flow. Google login works on 3wordid.com, but then lands on the
   normal list (not marked as Bumperchat). Optional "Stay logged in" is a 30-day remember-me cookie,
   also on `/login/email_login.php`. The PHP session cookie stays a browser session; the remember-me
   cookie starts a new session when the visitor comes back.
5. **Canada prefix is `cdn`**, not `ca` (California's code). Cross-country plate collisions are possible, see PLATE_FORMATS.md.

## Upload

Upload every file in the list below. A missing include is enough for a blank HTTP 500
(that is what happened on `claim.php` after the trial/payment work if
`php/plate_billing.php`, `php/notify.php` or `php/payments/` did not go up).

**New (this change):**
- `bumperchat/claim_plate.php`
- `bumperchat/claim_register.php`
- `bumperchat/login.php`
- `php/remember.php`
- `sql/2026-11-18_auth_remember_tokens.sql` (run once; see below)

**Changed (this change):**
- `bumperchat/index.php`
- `bumperchat/claim.php` (country + plate on one page)
- `bumperchat/chat.php`
- `bumperchat/pay.php`
- `bumperchat/pay_return.php`
- `php/bumperchat.php`
- `php/session_boot.php`
- `login/email_login.php`
- `3wid_user_edit.php`
- `3wid_list.php`
- `js/bumperchat.js`
- `css/bumperchat.css`
- `lang/en.php`, `lang/nl.php`, `lang/fr.php`, `lang/es.php`

**Also required on the server if they are not there yet** (trial, Mollie, phone; from PR #64):
- `bumperchat/logout.php`, `bumperchat/plate_formats.json`
- `php/functions.php`, `php/email_auth.php`, `php/plate_billing.php`, `php/notify.php`
- `php/payments/config.php`, `php/payments/mollie.php`, `php/payments/orders.php`
- `payments/webhook.php` (and the other files in `payments/` if you use live payments)
- `login/payments.config.example.php` (copy to `login/payments.config.php` on the server; do not put secrets in the repo)
- `sql/2026-11-18_bumperchat_trial_payments.sql` (run once if you have not already)

Not needed on the web server: `docs/*`, `tools/*`.

SQL to run once, in this order if neither has been run:
1. `sql/2026-11-18_bumperchat_trial_payments.sql` (trial + payments + phone; skip if already run)
2. `sql/2026-11-18_auth_remember_tokens.sql` (stay logged in; until this runs, the checkbox is shown but login stays a browser session)

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
/ `_text`.

Two strings on the landing page were changed on request:
- `bc.step3_title` (onboarding card 3 title): "Add your email and a password" /
  "Voeg je e-mail en een wachtwoord toe" is now "Register" / "Registreer"
  (FR "S’inscrire", ES "Registrarse"). The card body (`bc.step3_text`) was left as it is.
- `bc.claim_link` (hero button under the search box, not a card): "Claim this plate" /
  "Claim dit kenteken" is now "This is my license plate!" / "Dit is mijn kenteken!"
  (FR "Voici ma plaque !", ES "¡Esta es mi matrícula!").
  The similar heading `bc.claim_h1` / `bc.claim_title` ("Claim your plate" /
  "Claim je kenteken") is the old claim-page title and was left as it is; the new
  flow pages have their own titles ("Choose country", and so on).

The "Log in" link on card 3 (`bc.step3_link`) now goes to `login.php` (top-right
login), not into a login form on the claim page. Edit the other card texts yourself
if you want the trial or the price mentioned on the homepage.

## Stay logged in (added 18 November 2026)

`login/email_login.php` and `bumperchat/login.php` have an optional "Stay logged in"
/ "Ingelogd blijven" checkbox. It uses a separate cookie `3WIDREM` (HttpOnly,
Secure on HTTPS, SameSite=Lax, 30 days) with a random selector + validator. Only
the SHA-256 hash of the validator is stored in `auth_remember_tokens`. The token
is rotated on each use, deleted on logout, and all of a user's tokens are deleted
when their password is changed in `3wid_user_edit.php`.

The PHP session cookie (`3WIDSESS`) stays a browser session (`lifetime` 0). When
that session is gone, `current_user_from_session()` calls `remember_resume_session()`
in `php/remember.php` (loaded from `php/session_boot.php`) and
`establish_user_session()` starts a fresh session. Until
`sql/2026-11-18_auth_remember_tokens.sql` has been run, the checkbox does nothing
harmful: login still works as a normal session.

Google login does not set this cookie (there is no checkbox on that screen).
PHP 8 is required (`str_starts_with` is already used in `php/notify.php`; the new
code uses typed arguments and `hash_equals`).

## Default country (IP, headers, language)

The homepage converter and `claim.php` preselect a country with
`bumperchat_default_country()`. The visitor can always change it. A code is
used only when it exists in `plate_formats.json`. The lookup is **server-side
only**: the IP is never sent to a third-party API from the browser, and it is
not stored or logged.

Order:

1. **Server headers that already contain a country** (no extra lookup):
   `CF-IPCountry` (`HTTP_CF_IPCOUNTRY`), `GEOIP_COUNTRY_CODE`,
   `MM_COUNTRY_CODE` (and the `HTTP_*` / `GEOIP2_*` aliases). Cloudflare’s
   `XX` (unknown) and `T1` (Tor) are ignored.
2. **Local IP-to-country database**, if configured: the existing
   `GEOIP_COUNTRY_DB` path in `login/config.php` (pure-PHP MaxMind/DB-IP
   `.mmdb` reader in `php/geoip.php`, same as car chat) or the legacy PHP
   `geoip` extension. See `docs/CAR_CHAT.md` for how to install a monthly
   DB-IP or GeoLite2 Country file. That file is not in the repo.
3. **Accept-Language**: a region subtag first (`nl-NL` → NL, `nl-BE` → BE,
   `en-US` → US), otherwise a language code that is also a country in the
   plate list (`de` → DE).
4. **NL**.

### What works on a Plesk / Apache host

- **If the site is behind Cloudflare** (common with Plesk): `CF-IPCountry` is
  set automatically. This is the easiest option and needs no server setup.
- **If there is no Cloudflare**: the most reliable option on a normal Plesk
  box is the **local `.mmdb` file** already documented for car chat. In
  `login/config.php` (not in the repo) add:
  `define('GEOIP_COUNTRY_DB', '/home/USER/geoip/country.mmdb');`
  and place a DB-IP “IP to Country Lite” or MaxMind GeoLite2 Country file
  there. No Apache module and no Composer package are required.
- **Optional Apache modules** on Plesk: `mod_maxminddb` or `mod_geoip` can
  set `MM_COUNTRY_CODE` / `GEOIP_COUNTRY_CODE` for every request. That needs
  the module installed by the host and a country database on disk. Only do
  this if you already use those modules.
- **Without any of the above**, the dropdown falls back to the browser
  language region, then to the Netherlands. That still works; it is just
  less precise for visitors abroad who use an English browser.

Incoming `?words=` (or `?country=` / `?plate=`) from the homepage converter
or an old link still prefill the form. When the three words start with a
country or US/CA region prefix (`e 1234 bcd`, `tx abc 1234`), that country
is preselected and the rest is shown as the plate. A three-word plate
without a prefix (`ab 123 c`) is filled into the plate field of the default
country, not treated as a Canadian province.

