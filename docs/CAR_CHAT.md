# Car chat and open / closed chats

## What it does

- **Car chat** is an option on the edit page of a 3WordID, next to "Link to chat". It is a
  "Link to chat" whose three words are a Dutch licence plate, for example `1 kbb 00` (plate
  `1-KBB-00`). When the owner ticks it and saves, the server checks two things:
  1. the plate exists in the RDW open data register of Dutch vehicles
     (`https://opendata.rdw.nl/resource/m9d7-ebf2.json?kenteken=1KBB00`), and
  2. the owner is on a Dutch IP address.
  If the plate is not found: "Invalid Dutch licence plate". If the IP address is not Dutch:
  "Car chat can only be turned on from the Netherlands". Nothing is saved in that case.
- The car's make (merk + handelsbenaming) and colour (eerste_kleur) are stored at that moment
  and shown on the 3WordID's chat page. RDW is not asked again on every view.
- **Open / closed** (for "Link to chat" and "Car chat"):
  - Open: any 3WordID and any visitor can message it, and it can message any 3WordID.
  - Closed: both ways only 3WordIDs of the same account. Visitors and other accounts get a
    clear refusal and the chat page shows no message box for visitors.
  - Defaults: Car chat open, Link to chat closed.
  This is checked on the server for every message: `3wid_messageform_process.php` (logged-in
  owners), `3wid_messageform_ext_process.php` (visitors) and `3wid_chat.php` (chat login members).

Nothing changes before `sql/2026-10-30_car_chat.sql` has run.

## Server setup (once)

### 1. Country database (needed: without it car chat is refused)

The Dutch IP check uses a country database file on the server itself, so no visitor IP
address is sent to another company. Use one of these free files (same `.mmdb` format):

- **DB-IP "IP to Country Lite"** - no account needed, licence CC BY 4.0 (mention
  "IP geolocation by DB-IP" somewhere, for example on the terms page).
  Download: `https://download.db-ip.com/free/dbip-country-lite-YYYY-MM.mmdb.gz`
  (YYYY-MM = this month, e.g. `2026-10`), unzip it.
- **MaxMind GeoLite2 Country** - free account and licence key at maxmind.com,
  file `GeoLite2-Country.mmdb`.

Put the file OUTSIDE the web folder (so it can't be downloaded), for example
`/home/USER/geoip/country.mmdb`, and add to `login/config.php`:

```php
define('GEOIP_COUNTRY_DB', '/home/USER/geoip/country.mmdb');
```

Update the file once a month (IP ranges change). Example cron line (DB-IP, on the 2nd of each month, when the new file is out):

```
5 3 2 * * cd /home/USER/geoip && curl -sf -o new.mmdb.gz "https://download.db-ip.com/free/dbip-country-lite-$(date +\%Y-\%m).mmdb.gz" && gunzip -f new.mmdb.gz && mv new.mmdb country.mmdb
```

If `GEOIP_COUNTRY_DB` is missing or the file can't be read, car chat is refused with
"the country check is not set up" (fail closed). Already turned on car chats keep working.

### 2. Only behind a proxy or Cloudflare

By default only the real connection address (`REMOTE_ADDR`) is used, because headers like
`X-Forwarded-For` can be typed by anyone. If the site is behind Cloudflare or another proxy,
`REMOTE_ADDR` is the proxy, so also add:

```php
define('GEOIP_TRUSTED_PROXIES', ['203.0.113.10']);          // the proxy address(es)
define('GEOIP_CLIENT_IP_HEADER', 'HTTP_X_FORWARDED_FOR');   // or 'HTTP_CF_CONNECTING_IP'
```

The header is then only believed when the request really comes from one of those addresses.
A normal shared host without a proxy needs neither line.

### 3. RDW (optional)

RDW open data works without a key. For higher request limits, get a free app token at
`https://opendata.rdw.nl` (Sign in, Developer settings, Create new app token) and add:

```php
define('RDW_APP_TOKEN', 'your-token');
```

Other optional settings: `RDW_TIMEOUT` (seconds, default 6) and `RDW_API_URL`.
Answers are cached for 15 minutes (found) / 10 minutes (not found) in the PHP temp folder.
When RDW is down or slow, the owner sees "The RDW vehicle register can't be reached right
now" and can try again later; nothing is saved.

## Limits

- RDW tells whether a plate exists and what car it is, never who owns it. Anyone with a Dutch
  IP address could still claim a plate that is not theirs. A super admin way to take back a
  plate 3WordID is an open question (not built yet).
- 3WordIDs with digits need a paid account (existing rule): car chat does not change that.
