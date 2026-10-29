# Payments for 3WordID: Mollie + iDEAL | Wero (demo, plan and admin)

Status: **proposal + clickable demo**. Nothing here takes real money and nothing is linked from the site yet.
Fees and rules were checked on **29 October 2026**. Prices change, so check the linked pages again before you sign up.

- Demo: `demo/payments/` → open `https://3wordid.com/demo/payments/` after uploading the folder.
- **Provider: Mollie.** **Payment method shown to customers: iDEAL | Wero only.** Currency: EUR only.
- Seller assumed: a business registered in the Netherlands (KvK), see 3.1.
- This is an alternative to the multi-provider/multi-region demo in PR #50.

---

## 1. How the flow works (the demo)

```
3WordID list page ──[Subscribe]──▶ plans ──[Choose]──▶ checkout ──[Pay with iDEAL | Wero]──▶ Mollie checkout (demo):
       ▲                              ▲                     ▲                                 choose bank, pay
       │ paid: green banner,          │ canceled:           │ failed: message,                       │
       │ gold lock, "Paid" badge      │ note                │ form kept                              │
       └──────────────────────────────┴─────────────────────┴──────────────── return.php ◀───────────┘
                         (+ in production: Mollie calls subscribe_webhook.php, server to server)

After the first payment: iDEAL gives a SEPA Direct Debit mandate → Mollie subscription → every year
(or month) Mollie collects the renewal by SEPA Direct Debit and calls the webhook. No customer action.
```

| Demo file | What it shows |
|---|---|
| `plans.json` | **The plans and prices.** Edit this file to change plans (see section 2). |
| `index.php` | Fake "Your 3WordIDs" list. Free: one 3WordID, gold **Subscribe** button. Paid: green "Your subscription is active" banner, gold lock + "Paid" badge, **Manage subscription**. |
| `plans.php` | The plans from `plans.json`. Yearly/monthly switch (only shown when plans have both), VAT note, "Placeholder" tags. |
| `checkout.php` | Order summary with VAT and the renewal date, billing details (email, name, company, EU country, optional VAT number for reverse charge), **iDEAL \| Wero** as the only method, the SEPA mandate note, terms + "start right away" consent. |
| `provider_mock.php` | Neutral stand-in for the Mollie hosted checkout ("Mollie (demo)", no logos): choose a bank, then succeeds / I cancel / fails. |
| `return.php` | Where the customer comes back. Demo only: it switches the plan on. **In production only the webhook does that.** |
| `manage.php` | Manage subscription: plan, status, **next renewal**, renewals paid by SEPA Direct Debit (bank + masked IBAN), change plan, change bank account, **cancel subscription** (stays active until the end of the paid period) / undo, **Withdraw from contract here** (14 days), invoices. A yellow demo-only button **Simulate next renewal** adds a SEPA Direct Debit invoice so you can see a renewal. |
| `reset.php` | Start the demo again as a free account. |
| `_demo.php`, `_lang.php`, `demo.css` | Shared code (including the `plans.json` loader), texts (English + Dutch) and styles. |

The demo uses its own PHP session and its own texts. It does not load `login/config.php`, the database or `lang/*.php`. Your real account is not touched.

---

## 2. Editing `plans.json`

`demo/payments/plans.json` holds every plan. Edit it in any text editor, upload it, and reload `plans.php`. No PHP knowledge needed.

```json
{
    "currency": "EUR",
    "prices_are_placeholders": true,
    "plans": [
        {
            "id": "group10",
            "name": { "en": "Group 10", "nl": "Groep 10" },
            "description": { "en": "For a team, club or small business", "nl": "Voor een team, club of klein bedrijf" },
            "ids": 10,
            "qty_min": 1,
            "qty_max": 1,
            "prices": { "year": 60.00, "month": 6.00 },
            "features": { "en": ["10 3WordIDs", "…"], "nl": ["10 3WordID’s", "…"] },
            "highlight": true,
            "proof_required": false
        }
    ]
}
```

| Field | Meaning |
|---|---|
| `id` | Short code, 1–20 lowercase letters, digits or `_`. Must be unique. **Don't change it once people have bought the plan**; it is stored with their subscription. |
| `name`, `description` | Text per language. `en` is required and is used when a language is missing. |
| `ids` | 3WordIDs per unit of this plan. |
| `qty_min`, `qty_max` | How many units a customer may buy (Individual: 1–5). Same numbers → no quantity box. Max 100. |
| `prices.year`, `prices.month` | Price in **euros including VAT**, like `10.00`. Use `null` for "not offered". A plan needs at least one. |
| `features` | Bullet list per language. Don't put prices in it, so you only change prices in one place. |
| `highlight` | `true` → gold border and gold button (the "recommended" plan). |
| `proof_required` | `true` → checkout says you will ask for proof (museums). |
| `prices_are_placeholders` | While `true`, every price shows a "Placeholder" tag. Set to `false` once the prices are final. |
| `currency` | Must stay `"EUR"` (iDEAL \| Wero only takes euros). |

The plans appear in the order of the file. Keys starting with `_` (like `_help`) are ignored.

**If the file has a mistake** (a missing comma, a quote, a price written as text), the demo pages show "The plans could not be loaded" with a short hint like *plans.json is not valid JSON (Syntax error)* or *plan "single": price "year" must be a number*, instead of a broken page. Nothing can be bought until it is fixed. A quick check before uploading: paste the file into any JSON validator.

**Is it safe that `plans.json` can be opened in a browser?** Yes. It only contains public plan names and prices, the same things the plans page shows. Never put API keys or other secrets in it (those go in `login/config.php`). The server always computes the amount from this file; a customer can't change a price by editing anything in the browser. For the real version, keep the file outside the web root or block it in `.htaccess` if you prefer, it is not needed by the browser.

**All prices in `plans.json` are placeholders** (the dollar numbers from today's `plans.php`, written as euros). Choose the real euro prices before going live.

---

## 3. Provider: Mollie

### 3.1 Sign-up requirements

- **Mollie is only for registered businesses, not private individuals.** You need a registered company or other officially registered business, and its registration details, during onboarding ([Mollie help: documents needed](https://help.mollie.com/hc/en-us/articles/115000481665)). In the Netherlands that means a **KvK registration**, e.g. as an eenmanszaak; you register with DigiD and finish at a KvK office, and the Belastingdienst then sends your VAT number ([KvK eenmanszaak](https://www.kvk.nl/en/starting/eenmanszaak-sole-proprietorship/)).
- Mollie also asks for: a **live website** that shows your prices and service descriptions, a **bank account** (verified with a small payment from that account), and **ID of each legal representative**. The review can take up to 10 business days ([Mollie docs: create an account](https://docs.mollie.com/docs/create-an-account)). Expect to need a **business bank account** in the business name for a Dutch business.
- Recurring payments (and so subscriptions) only work through the **API**, not from the Mollie web app ([Mollie recurring](https://docs.mollie.com/docs/recurring-payments)). Activate **iDEAL** *and* **SEPA Direct Debit** in your Mollie profile: the iDEAL first payment creates a `directdebit` mandate, and Mollie says SEPA Direct Debit must be enabled for that.

### 3.2 Fees (from [mollie.com/pricing](https://www.mollie.com/pricing), checked 29 Oct 2026, standard pricing, per successful transaction)

| Item | Mollie fee |
|---|---|
| Monthly fee / minimum / lock-in | none ("No minimum costs, no lock-in contracts") |
| **iDEAL \| Wero** (first payment) | **€0.32** |
| **SEPA Direct Debit** (every renewal) | **€0.35** |
| Recurring, Checkout, Invoicing, Payment Links | "Included at no extra cost" |
| Payouts | up to 5 per month free, then €0.25 per extra payout (when you process less than €500,000 a year) |
| Payout in another currency than your primary balance | 1% of the payout (not relevant: EUR only) |
| Note | Mollie: "Minimum fee amount applies" and "Exemptions apply" (details linked from the pricing page) |

For comparison only (not offered in this demo): EEA consumer cards 1.80% + €0.25, Bancontact 1.40% + €0.25, PayPal = PayPal fees + €0.10.
Volume pricing only starts above €100,000 a month.

What that means for 3WordID (placeholder prices):

| Payment | Mollie fee | Share |
|---|---|---|
| €10 per year, first payment (iDEAL \| Wero) | €0.32 | 3.2% |
| €10 per year, each renewal (SEPA Direct Debit) | €0.35 | 3.5% |
| €60 Group 10 per year, renewal | €0.35 | 0.6% |
| **€1.50 per month**, each renewal | €0.35 | **23%** |

The fixed fee per payment hurts small amounts: **yearly billing is much cheaper for you.** Consider dropping monthly for the Individual plan (set `"month": null` in `plans.json`).

### 3.3 Things to know about this setup

- **Wero:** iDEAL is now "iDEAL | Wero" and moves fully to Wero by the end of 2027. Customers pay in their bank app as before. Wero's own subscriptions are announced for Q2 2027; until then (and with Mollie, as built here) renewals run on the **SEPA Direct Debit mandate** from the first iDEAL payment. Sources: [Betaalvereniging: iDEAL | Wero FAQ](https://www.betaalvereniging.nl/en/knowledge-base/online-payments/ideal-wero/), [Betaalvereniging: migration](https://www.betaalvereniging.nl/actueel/volgende-fase-migratie-ideal-naar-wero-begint-in-oktober/).
- **SEPA Direct Debit can be reversed** by the customer for 8 weeks without a reason (13 months if unauthorised), and the money arrives a few days later than with iDEAL ([Mollie SEPA DD](https://docs.mollie.com/docs/sepa-direct-debit)).
- **Failed renewals:** Mollie retries a failed subscription payment up to 5 times and then cancels the subscription when all retries fail. Some reasons (closed account, invalid IBAN) cancel it immediately ([Mollie recurring](https://docs.mollie.com/docs/recurring-payments)). Our webhook must handle this (4.4).
- **Tell customers before you collect.** Mollie recommends a notice a few days before each payment to avoid chargebacks; we also send one 30 and 7 days before a yearly renewal (4.5).
- The customer's bank account must be a SEPA account in their name; customers who don't use a Dutch (or other iDEAL | Wero) bank can't subscribe in this version. Cards or Bancontact can be switched on in Mollie later without changing the database plan.

---

## 4. Integration outline (Mollie, for later)

Use the official PHP client `mollie/mollie-api-php` (Composer; the repo already has a `login/composer.json`). API key in `login/config.php` only (separate `test_…` and `live_…` keys). Build and test everything in **test mode** first.

### 4.1 Pages and button

- **Subscribe button** on `3wid_list.php`, in the `.toolbar` after "Search box for your website" (where the demo shows it). Only for free users; paying users get "Manage subscription".
- New pages: `plans.php` (rebuilt from the demo, reading `plans.json`; also fix the current issues: `<title>` says "Terms and Conditions", "2." appears twice, Group 10 says "$60" in the heading but "$30" in the text), `subscribe_checkout.php`, `subscribe_return.php`, `subscribe_webhook.php`, `subscribe_manage.php`. Login required on all except the webhook.

### 4.2 Customer

- On the first checkout, create a **Mollie customer** (`POST /v2/customers` with name + email, `metadata.user_id`) and store `cst_…` in `subscriptions.mollie_customer_id`. Reuse it for later purchases.

### 4.3 First payment (`sequenceType=first`)

1. Look up the plan and price **on the server** from `plans.json` (never from the browser). Compute VAT (country / reverse charge).
2. Insert a `payments` row: `status='open'`, our own `order_ref`, `sequence='first'`.
3. `POST /v2/payments` with:
   - `amount` = total incl. VAT (e.g. `{"currency":"EUR","value":"60.00"}`), `description` = "3WordID Group 10 – 1 year",
   - `method = "ideal"`, `customerId = cst_…`, **`sequenceType = "first"`**,
   - `redirectUrl = https://3wordid.com/subscribe_return.php?ref=<order_ref>`,
   - `webhookUrl = https://3wordid.com/subscribe_webhook.php`,
   - `metadata = {"order_ref": …, "user_id": …, "plan": …, "qty": …, "interval": "12 months"}`.
4. Save `tr_…` in `payments.mollie_payment_id`; redirect (HTTP GET) to `_links.checkout.href`.

### 4.4 Webhook (the only place that changes state)

Mollie POSTs only `id=tr_…`. **Fetch the payment from the Mollie API** with your key and trust only that answer (no signature to check; the API call is the check). Make it idempotent (unique `mollie_payment_id`, ignore repeats) and answer `200` quickly.

- **First payment `paid`:**
  1. Mark the payment paid, create the invoice, update the user (4.6).
  2. Read the **mandate** (`payment.mandateId`, or `GET /v2/customers/{cst}/mandates`): method `directdebit`, status `valid` or `pending`. Store `mdt_…` and the masked IBAN/bank for the manage page.
  3. Create the **subscription**: `POST /v2/customers/{cst}/subscriptions` with `amount` (same total), `interval = "12 months"` or `"1 month"` (12 months is Mollie's maximum), **`startDate` = today + 1 period** (the first period is already paid), `description` unique per customer (e.g. "3WordID Group 10 – #<subscription id>"), `mandateId`, `webhookUrl`, `metadata.subscription_id`. Store `sub_…`, `current_period_end` = startDate.
- **First payment `canceled` / `expired` / `failed`:** mark it; nothing else.
- **Renewal payments:** Mollie creates them itself, so the webhook receives a `tr_…` **you have never seen**. The payment has `subscriptionId`; find our subscription by it, insert a `payments` row (`sequence='recurring'`, `method='directdebit'`).
  - `paid` → extend `current_period_end` by one period, invoice + receipt email.
  - `failed` → `past_due`, email the customer (Mollie retries up to 5×).
  - `chargeback` / refunded → mark it; if the subscription got canceled by Mollie (`GET` the subscription, status `canceled`), set it to `ended` at the end of the paid period.
- Subscriptions don't have their own webhooks at Mollie; status changes reach you through these payment calls, plus the daily cron (4.5) that re-checks.

### 4.5 Return URL, cancel, withdraw, cron

- `subscribe_return.php` **only shows a status**: load our payment by `order_ref`, check it belongs to the logged-in user. Paid → list page with the green banner; still open → "We are confirming your payment…" and reload; failed → back to checkout. Never switch anything on from the URL.
- **Cancel** (manage page): `DELETE /v2/customers/{cst}/subscriptions/{sub}`. No more SEPA collections. Set `cancel_at_period_end = 1`; the plan stays until `current_period_end`. Undo = create a new subscription with `startDate = current_period_end`.
- **Withdraw from contract here** (EU, 14 days): refund the first payment (`POST /v2/payments/{tr}/refunds`), cancel the subscription, user back to free, credit note.
- **Change bank account:** a new iDEAL | Wero `first` payment (e.g. €0.01) creates a new mandate; then update the subscription's `mandateId`.
- **Daily cron** `cron/subscriptions_expire.php` (like `tools/cleanup_empty_accounts.php`): end `active`/`past_due`/canceled subscriptions whose `current_period_end` is more than 3 days ago with no successful renewal; send the reminder emails 30 and 7 days before a yearly renewal (Mollie recommends a notice before every collection).

### 4.6 Mapping to the current user fields

- `user_is_paying()` = `user_type > 0` and not 9 (9 = blocked). `db_3wid_validate_type()` allows numbers when `user_type > 0`. The list page allows `max_3wids + 1` 3WordIDs. `db_3wid_update_credit_usertype()` updates `credit`, `user_type` and `max_3wids` together.

| Event | `user_type` | `max_3wids` |
|---|---|---|
| First payment paid | the paying type (check the existing paying value before using) | (`ids` × quantity) − 1, e.g. Group 10 → 9, Individual ×3 → 2 |
| Change of plan | unchanged | recalculated |
| Ended / withdrawn / chargeback | 0 | 0 |

Never touch `user_type = 9`. Never delete 3WordIDs when a plan ends: switch the ones above the free limit **off** and say so in the terms. Museums: manual check of the proof (`pending_review`).

### 4.7 Proposed tables (MySQL 8). **Proposal only, do not run yet.**

Check the type of `google_users.id` first (`SHOW CREATE TABLE google_users`) and use the same type for `user_id`. No foreign keys, matching the rest of the schema.

```sql
CREATE TABLE subscriptions (
  id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id                  INT NOT NULL,
  plan_key                 VARCHAR(20) NOT NULL,          -- "id" from plans.json
  quantity                 SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  billing_period           ENUM('year','month') NOT NULL DEFAULT 'year',
  amount_cents             INT UNSIGNED NOT NULL,         -- EUR per period incl. VAT
  status                   ENUM('pending','pending_review','active','past_due','canceled','ended','withdrawn') NOT NULL DEFAULT 'pending',
  cancel_at_period_end     TINYINT(1) NOT NULL DEFAULT 0,
  mollie_customer_id       VARCHAR(64) NOT NULL,          -- cst_...
  mollie_mandate_id        VARCHAR(64) NULL,              -- mdt_... (directdebit, from the iDEAL first payment)
  mollie_subscription_id   VARCHAR(64) NULL,              -- sub_...
  mandate_bank_label       VARCHAR(60) NULL,              -- e.g. "ING · NL** **** 1234" for the manage page
  billing_name             VARCHAR(120) NOT NULL,
  billing_company          VARCHAR(120) NULL,
  billing_country          CHAR(2) NOT NULL,
  vat_number               VARCHAR(20) NULL,
  vat_number_valid         TINYINT(1) NULL,               -- VIES result
  started_at               DATETIME NULL,
  current_period_end       DATETIME NULL,
  withdraw_until           DATETIME NULL,                 -- started_at + 14 days
  consent_immediate_at     DATETIME NULL,                 -- "start right away" checkbox
  terms_version            VARCHAR(20) NOT NULL,
  ended_at                 DATETIME NULL,
  created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mollie_sub (mollie_subscription_id),
  KEY idx_user (user_id),
  KEY idx_customer (mollie_customer_id),
  KEY idx_status_end (status, current_period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  subscription_id          INT UNSIGNED NULL,
  user_id                  INT NOT NULL,
  order_ref                CHAR(24) NULL,                 -- our ref for first payments (in the return URL); NULL for renewals
  mollie_payment_id        VARCHAR(64) NOT NULL,          -- tr_...
  method                   ENUM('ideal','directdebit') NOT NULL,
  sequence                 ENUM('first','recurring') NOT NULL,
  net_cents                INT UNSIGNED NOT NULL,
  vat_cents                INT UNSIGNED NOT NULL DEFAULT 0,
  total_cents              INT UNSIGNED NOT NULL,
  vat_rate                 DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  vat_mode                 ENUM('domestic','oss','reverse_charge') NOT NULL,
  status                   ENUM('open','pending','paid','failed','canceled','expired','refunded','charged_back') NOT NULL DEFAULT 'open',
  paid_at                  DATETIME NULL,
  created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mollie_payment (mollie_payment_id),
  UNIQUE KEY uq_order_ref (order_ref),
  KEY idx_sub (subscription_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
  id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_number           VARCHAR(20) NOT NULL,          -- consecutive, e.g. 3WID-2026-0001
  payment_id               INT UNSIGNED NOT NULL,
  user_id                  INT NOT NULL,
  kind                     ENUM('invoice','credit_note') NOT NULL DEFAULT 'invoice',
  issued_at                DATETIME NOT NULL,
  customer_name            VARCHAR(120) NOT NULL,
  customer_company         VARCHAR(120) NULL,
  customer_country         CHAR(2) NOT NULL,
  customer_vat_number      VARCHAR(20) NULL,
  description              VARCHAR(255) NOT NULL,         -- plan + period
  net_cents                INT NOT NULL,                  -- negative for credit notes
  vat_cents                INT NOT NULL,
  total_cents              INT NOT NULL,
  vat_rate                 DECIMAL(4,2) NOT NULL,
  vat_mode                 ENUM('domestic','oss','reverse_charge') NOT NULL,
  pdf_path                 VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_number (invoice_number),
  KEY idx_payment (payment_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_events (                            -- webhook log, for idempotency and debugging
  id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mollie_payment_id        VARCHAR(64) NOT NULL,
  status_seen              VARCHAR(20) NOT NULL,
  received_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at             DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_event (mollie_payment_id, status_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.8 Secrets and deploy

- The Mollie API key (test and live) goes in `login/config.php` on the server, never in git, never in `plans.json`.
- `DEPLOY.md`'s file check will also list the `demo/payments/` files. They are standalone; remove the folder from the server once the real flow is live.

---

## 5. Admin checklist (what to organise)

Not legal or tax advice. Have an accountant check items 3–5 once.

1. **KvK registration** (eenmanszaak or BV). Required by Mollie (3.1). You get a KvK number and a VAT ID (btw-id); both must go on the site and on invoices.
2. **Business bank account** in the business name, for Mollie payouts.
3. **VAT (OSS).** Below €10,000 per year of B2C sales to consumers in *other* EU countries you may charge Dutch VAT (21%). Above that, charge the customer's country rate (the demo already does) and file a quarterly **OSS** return. Business customers with a valid VAT number in another EU country: **reverse charge**; check the number in [VIES](https://ec.europa.eu/taxation_customs/vies/). Sources: [EU OSS](https://vat-one-stop-shop.ec.europa.eu/index_en). Small-business scheme **KOR** (≤ €20,000 turnover per year, no VAT, but no VAT refunds on costs): ask your accountant whether it fits ([business.gov.nl KOR](https://business.gov.nl/subsidies-and-schemes/small-businesses-scheme/)).
4. **Invoices** for every payment, including each SEPA renewal: consecutive number, date, your name/address/VAT ID/KvK, customer name and address (and VAT ID for reverse charge), what and which period, amount excl. VAT, VAT rate and amount (or "VAT reverse charged"), total ([Belastingdienst](https://www.belastingdienst.nl/wps/wcm/connect/bldcontenten/belastingdienst/business/vat/vat_in_the_netherlands/vat_administration/invoice_requirements), [business.gov.nl](https://business.gov.nl/regulations/invoice-requirements/)). Mollie lists Invoicing as included; check whether it covers subscription renewals, otherwise generate them from the `invoices` table.
5. **Terms & conditions** – add a "Subscriptions" part to `terms.php`:
   - what each plan includes, the price incl. VAT, and that it **renews automatically** and is collected by **SEPA Direct Debit** after the first iDEAL | Wero payment (the mandate);
   - how to cancel (online, any time, effective at the end of the paid period) and the reminder before a yearly renewal;
   - the **EU 14-day right of withdrawal**. Simplest: a full refund within 14 days, as the demo does; the "Start my subscription right away…" checkbox records the consent;
   - since **19 June 2026** online contracts need a clearly labelled **"withdraw from contract here"** function with a confirm step; the demo has it on Manage subscription ([Directive (EU) 2023/2673](https://eur-lex.europa.eu/eli/dir/2023/2673/oj/eng), [ACM](https://www.acm.nl/en/publications/acm-calls-online-retailers-prepare-withdrawal-button));
   - what happens at the end: extra 3WordIDs are switched off, not deleted;
   - museums: what proof you ask for.
6. **Privacy statement:** name Mollie as a recipient of payment data; say which billing data you keep and for how long (**7 years** for the books in NL). Sign Mollie's data processing terms where offered.
7. **Refunds** only through Mollie (dashboard/API), never by a separate bank transfer, so invoice and VAT stay right (credit note).
8. **Chargebacks / reversed direct debits:** answer with evidence (invoice, terms accepted, reminder email). The webhook switches the plan off when Mollie cancels the subscription.
9. **Mollie onboarding:** have ready your ID, KvK details, business bank account, and a live site with terms, prices, contact details and refund policy (the plans page and terms can be live but unlinked).
10. **Bookkeeping:** export Mollie settlements (payouts and fees) monthly into your bookkeeping (many Dutch bookkeeping packages can import Mollie settlements; check yours). Keep everything for 7 years.

---

## 6. Open decisions for you

1. Real euro prices incl. VAT (now placeholders in `plans.json`: €10 / €60 / €500 / €20 per year).
2. Monthly: keep it? With Mollie every monthly renewal costs €0.35 (23% of €1.50). Easy to switch off per plan in `plans.json`.
3. Group 10: €60 or €30 (the live plans page says both)?
4. 14-day withdrawal: full refund (demo) or pro rata?
5. Changing plan mid-period: start the new plan right away with a pro-rata credit, or switch at the next renewal? (The demo only links to the plans page.)
