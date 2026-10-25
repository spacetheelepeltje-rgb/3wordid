# 3wordID roadmap

Guiding rule: keep it simple so it stays versatile, and store as little personal data as possible.

_Status as of 25 October 2026._

## Done
- [x] Security fixes: messages, delete, on/off and edit are owner-only, with CSRF protection and a locked-down `.htaccess` (PR #1)
- [x] List page links built from each 3WordID's own words (PR #2)
- [x] Login only through the session, no tokens in links (PR #3)
- [x] `functions.php` cleanup, 30 unused functions removed (PR #4)
- [ ] Admin links visible again, only for the admin account (PR #5, in review)

## Next
| Item | Area | Effort | Notes |
|---|---|---|---|
| Homepage that explains itself | Look and feel | Small | One clear promise, 3-step how-it-works, real examples, fix typos and hidden helper text |
| Trust pages and footer | Look and feel | Medium | About, Contact, Privacy (separate from Terms), Pricing, linked on every page |
| Lock down `errors.php` | Security | Small | The error log is public and shows visitor IP addresses |
| Check server-only admin pages | Security | Small | Add `3wid_list_ips.php`, `3wid_signup_list.php`, `login/index.php` to GitHub for review |
| Remove old file-based pages | Clean-up | Small | `check.php`, `get_messages.php` etc. now break if still on the server |

## Longer term
| Item | Area | Effort | Notes |
|---|---|---|---|
| WhatsApp-style messages | Messaging | Large | Chat bubbles, one conversation per 3WordID, timestamps, read status, replies in thread |
| Easy payment funnel | Payments | Large | Free vs paid side by side, short checkout with a payment provider, account page to manage the plan |
| Clear user admin | Admin | Medium | One admin area for users, plans, signups and blocks |
| Watertight email and phone storage | Privacy | Medium | Encrypt with sodium using a key outside the web folder and database, keep a hash for lookups, decrypt only to send opted-in mail; normalise phone numbers (+316...) before hashing |
| Apple and Facebook login | Login | Medium | Next to Google and email; only request the email address |
| Custom landing pages | Growth | Large | A branded index page per organisation, e.g. a museum |
| Mobile-first flow | Look and feel | Medium | The whole claim and manage flow works well on phones |
| Abuse protection | Security | Medium | Check links before saving, rate-limit lookups and messages, report link |

## First users to approach
- **Restaurants and cafés**: menus and table tents (the menu ID is already the second most viewed)
- **Museums and tourist spots**: cleaner labels, spoken links for audio tours
- **Podcasters and radio**: nobody remembers a URL said out loud
- **Event organisers and speakers**: links for slides and sign-ups, message box for questions
- **Tradespeople and small shops**: words on a van or card that lead to a message box
