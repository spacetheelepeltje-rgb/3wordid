# Mollie iDEAL (one-off payments)

This is the live payment module (`php/payments/`), first used for Bumperchat
licence plate 3WordIDs. It is a **one-off iDEAL payment**, not a Mollie
subscription and not SEPA Direct Debit. The clickable demo in `demo/payments/`
is unchanged and still not linked from the site.

Nothing here takes real money until you put a **live** Mollie key in the
server config and switch iDEAL on in the Mollie dashboard.

## What the user pays (Bumperchat plate)

- Product key: `bumperchat_plate`
- Default price: **EUR 7.95 per year** (set in `login/payments.config.php`)
- Method: iDEAL
- Renewal: **manual**. When the year ends, the owner is sent back to the pay
  page and pays the price then in the config. A paid year keeps the price that
  was stored on that payment row.

The amount is read from config **at payment time** and stored on the `payments`
row, so later price changes do not alter a year already paid.

## Setup (you do this on the server)

1. Create a [Mollie](https://www.mollie.com/) account. Mollie requires a Dutch
   business registration (KvK) to go live; test mode can be used before that.
2. In the Mollie dashboard, enable **iDEAL**.
3. Copy `login/payments.config.example.php` to `login/payments.config.php`
   (that file is gitignored and must never be committed).
4. Put your API key in `mollie.api_key`:
   - `test_...` for test mode (no real charges)
   - `live_...` for live payments
   Never put a real key in the example file or in the git repository.
5. Set `public_base_url` to the public HTTPS origin, for example
   `https://www.3wordid.com` (no trailing slash). Mollie must be able to reach
   `https://www.3wordid.com/payments/webhook.php`. Localhost will not work for
   the webhook; the return page also checks the status with Mollie, so a test
   payment can still complete if you come back from the checkout.
6. Run the SQL in `sql/2026-11-18_bumperchat_trial_payments.sql` once
   (phpMyAdmin or the mysql command line). Until that has run, claiming a
   Bumperchat plate shows that the database is not ready, and the rest of the
   site works as before.
7. Upload the new and changed PHP/CSS/lang files (not `docs/`, not the SQL
   file as a page, not the example key). `login/payments.config.php` stays
   only on the server.

## URLs

| URL | Role |
|---|---|
| `bumperchat/pay.php?words=ab+123+c` | Pay page for that plate (owner only) |
| `bumperchat/pay_return.php?ref=...` | Customer return after iDEAL |
| `payments/webhook.php` | Mollie server-to-server webhook (must be public) |
| `payments/return.php?ref=...` | Shared return; sends Bumperchat payments to `pay_return.php` |

The webhook POST body is not trusted. The script fetches `GET /v2/payments/tr_...`
from Mollie and only then marks the payment paid and extends `paid_until`.

## Adding a 3WordID tier later

Add an entry under `products` in `login/payments.config.php` (amount, currency,
interval, method). Register a fulfiller with `payments_register_fulfiller()`
that updates `user_type` / `max_3wids` (or whatever that tier should do).
Start the payment with `payments_start($product_key, $user_id, $opts)`.
The existing live plans page (`plans.php`) is still the old email-and-invoice
flow; this module does not replace it until you plug those tiers in.

## Test mode

If the API key starts with `test_`, the pay page shows a test-mode note.
Use Mollie’s test iDEAL flow. No real money is taken.
