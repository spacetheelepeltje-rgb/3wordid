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

## Chat only, no schema change

A Bumperchat login is marked **in the session only**: `$_SESSION['bumperchat'] = ['words' => ..., 'at' => ...]`
(set by `claim.php` right after `establish_user_session()`). With that flag `3wid_list.php` redirects to
`bumperchat/chat.php`, so every existing "My 3WordIDs" / back link and every redirect to the list
(chat top bar, delete, toggle, consent) lands on the Bumperchat chats page instead. Any normal login
(Google, `/login/email_login.php`, register) clears the flag, so the same account logging in on 3wordid.com
gets the normal list. No new column, so nothing to add to `google_users_deleted`.

If a permanent "Bumperchat account" is wanted later (for example to always hide the list), add a column such as
`google_users.signup_source` **and the same column to `google_users_deleted`**.

## Decisions for the owner (v1)

1. **Plates with digits need a paid account** (existing rule, kept). Almost every plate has digits, so a new free
   Bumperchat account can NOT claim its plate yet: the account is created and `chat.php` says the plate needs a
   paid account (link to plans). Nothing is bypassed. Decide: free plate 3WordIDs for Bumperchat accounts?
2. **claim.php never creates the 3WordID.** Paying accounts get a "Create it" link to the existing add page
   (`3wid_add.php`); Car chat is then turned on from the edit page with the existing RDW + Dutch IP check.
   A one-click "claim and turn on Car chat" would be v2, once rule 1 is decided.
3. **Who owns a plate**: only Dutch plates are checked (RDW: exists yes/no). Nobody checks the claimer is the owner.
4. **Login**: email + password only on the Bumperchat pages. Google login works on 3wordid.com, but then lands on the
   normal list (not marked as Bumperchat).
5. **Canada prefix is `cdn`**, not `ca` (California's code). Cross-country plate collisions are possible, see PLATE_FORMATS.md.

## Upload

New: `bumperchat/` (all 5 files), `php/bumperchat.php`, `php/email_auth.php`, `js/bumperchat.js`, `css/bumperchat.css`.
Changed: `php/functions.php`, `php/session_boot.php`, `3wid_exists.php`, `3wid_list.php`, `index.php`,
`js/3wid_index_2.js`, `login/email_login.php`, `login/email_register.php`, `lang/en.php`, `lang/nl.php`,
`lang/fr.php`, `lang/es.php`. Not needed on the server: `docs/*`, `tools/test_bumperchat_plates.js`.
No SQL to run. Upload `php/functions.php` together with `php/bumperchat.php` and `php/email_auth.php`
(functions.php loads bumperchat.php; the login pages load email_auth.php).
