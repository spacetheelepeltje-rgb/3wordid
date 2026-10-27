# 3wordID roadmap

Guiding rule: keep it simple so it stays versatile, and store as little personal data as possible.

_Status as of 27 October 2026._

## Done
- [x] Security fixes: messages, delete, on/off and edit are owner-only, with CSRF protection, session-only login and a locked-down `.htaccess` (PR #1)
- [x] List page links built from each 3WordID's own words (PR #2)
- [x] Login only through the session, no tokens in links (PR #3)
- [x] `functions.php` cleanup, 30 unused functions removed (PR #4)
- [x] `ROADMAP.md` added (PR #6)
- [x] Admin tools: user-edit save crash fixed, each user's 3WordIDs page, admin 3WordID search, links between them; includes the admin links from PR #5 (PR #7)
- [x] Privacy quick fixes: `errors.php` and logs blocked, honest email-hash wording, law-enforcement retention text, notice on message forms that messages and IPs are stored, cookie banner with equal Accept and Reject so Google Analytics loads only after consent, self-hosted Inter font (PR #8)
- [x] Removed the last link to the old `index_2.php` (PR #9)
- [x] Accounts deactivated instead of deleted; user list shows each user's 3WordID count and last login; one-time SQL adds `last_login` (PR #10)
- [x] Session email: the login email is kept encrypted in the session only, with a "Send to my email" button on the list page (PR #12)
- [x] The email button stays hidden until the SMTP settings are present in `login/config.php` (PR #13)
- [x] Homepage text and SEO: typo fixes, meta description, canonical URL, Open Graph and Twitter card tags, social preview image (PR #14)
- [x] Homepage redesign: live example, 3-step how it works, concrete use cases, curated public list, trust strip and FAQ (PR #17, re-opened from #15)
- [x] Multi-language, English and Dutch, on the public pages: language from the browser, EN | NL switch, hreflang, one text file per language (PR #18)
- [x] Multi-language on all remaining pages: login, list, add and edit, signup, credit, consent and admin; logged-out users go to `login/options.php` (PR #19)

## In review
- [ ] Header and footer links: shared header and footer with Plans, Terms and Get started (PR #16, needs updating for the language switch)
- [ ] This roadmap update (PR #11)

## Next
| Item | Area | Effort | Notes |
|---|---|---|---|
| Add server-only files to GitHub | Clean-up | Small | `plans.php`, `3wid_show_qr.php`, `3wid_list_ips.php`, `3wid_signup_list.php` (branch `add-server-files`); `3wid_list_ips.php` needs an admin-only check and must not stay public |
| Fix `plans.php` | Privacy | Small | Still says emails are encrypted (they're hashed) and loads Google Fonts |
| Repoint QR no-hash redirect | Clean-up | Small | `3wid_show_qr.php` without a hash still forwards to `index_2.php`; point it to `index.php` |
| Make `errors.php` admin-only | Security | Small | Blocked through `.htaccess` for now (PR #8) |
| Provable consent | GDPR | Small | Store a timestamp and terms version when a user accepts |
| Contract as the legal basis | GDPR | Small | Account data is processed to deliver the service |
| Proper privacy page | GDPR | Small | Separate from Terms: what's stored, why, how long, law-enforcement policy |
| Self-service export and deactivation | GDPR | Medium | Users download their data and deactivate their account; data is never hard-deleted |
| Decide a retention period | GDPR | Small | Owner's decision, then state it on the privacy page |
| HTML markup in notifications, with an HTML editor | Notifications | Medium | Notifications are public, so unsanitised HTML would allow XSS and phishing. Sanitise on the server with HTML Purifier and an allowlist of tags (p, br, b, i, ul, ol, li, a with http/https only, h2, h3); never trust the editor's output. Candidate editors: Quill or TinyMCE, self-hosted, no third-party CDN, for privacy |
| Trust pages | Look and feel | Medium | About, Contact, Privacy and Pricing pages; shared header and footer links are in review (PR #16) |
| Finish translations | Languages | Small | Proofread the Dutch legal texts, translate the server footer text in `login/config.php`, translate the server-only pages |
| Remove old file-based pages | Clean-up | Small | `check.php`, `get_messages.php` etc. now break if still on the server |

## Longer term
| Item | Area | Effort | Notes |
|---|---|---|---|
| SMTP sending via own mail server | Email | Small | Parked. The email button stays hidden until the five SMTP settings are in `login/config.php`; next step is PHPMailer with the owner's mail server, plus SPF and DKIM for 3wordid.com |
| WhatsApp-style messages | Messaging | Large | Chat bubbles, one conversation per 3WordID, timestamps, read status, replies in thread |
| Easy payment funnel | Payments | Large | Free vs paid side by side, short checkout with a payment provider, account page, admin for paid accounts |
| Clear user admin | Admin | Medium | One admin area for users, plans, signups and blocks |
| Watertight email and phone storage | Privacy | Medium | Encrypt with sodium using a key outside the web folder and database, keep a hash for lookups, decrypt only to send opted-in mail; normalise phone numbers (+316...) before hashing |
| Apple and Facebook login | Login | Medium | Next to Google and email; store only the email address (see below) |
| Custom landing pages | Growth | Large | A branded index page per organisation, e.g. a museum |
| Mobile-first flow | Look and feel | Medium | The whole claim and manage flow works well on phones |
| Abuse protection | Security | Medium | Check links before saving, rate-limit lookups and messages, report link |

## What Apple login needs
1. Apple Developer Program membership (USD 99 per year).
2. A Services ID, a Sign in with Apple key (`.p8` file) and your team ID.
3. A verified domain and return URL on 3wordid.com.
4. Server-side verification of Apple's ID token in PHP.

Apple shares the email only on the first login and may give a private relay address, which fits the email-only goal. Sending mail to relay addresses needs the sending domain registered with Apple.

## First users to approach
- **Restaurants and cafés**: menus and table tents (the menu ID is already the second most viewed)
- **Museums and tourist spots**: cleaner labels, spoken links for audio tours
- **Podcasters and radio**: nobody remembers a URL said out loud
- **Event organisers and speakers**: links for slides and sign-ups, message box for questions
- **Tradespeople and small shops**: words on a van or card that lead to a message box
