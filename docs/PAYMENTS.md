# Payments for 3WordID: plan, providers and admin

Status: **proposal + clickable demo**. Nothing here takes real money and nothing is linked from the site yet.
Fees and rules were checked on 28 October 2026. Prices change, so check the linked pages again before you sign up.

- Demo: `demo/payments/` → open `https://3wordid.com/demo/payments/` after uploading the folder.
- Market at first: the eurozone (EUR) and the US (USD).
- Seller assumed: a business in the Netherlands (change the NL parts if that is not the case).

---

## 1. How the flow works (the demo)

```
3WordID list page ──[Subscribe]──▶ plans ──[Choose]──▶ checkout ──[Pay]──▶ provider's payment page
       ▲                              ▲                     ▲                        │
       │ paid: green banner,          │ canceled:           │ failed: message,       │
       │ gold lock, "Paid" badge      │ note                │ form kept              │
       └──────────────────────────────┴─────────────────────┴──────── return.php ◀───┘
                                   (+ in production: webhook.php, server to server)
```

| Demo file | What it shows |
|---|---|
| `index.php` | Fake "Your 3WordIDs" list. Free: one 3WordID, gold **Subscribe** button in the toolbar. Paid: green "Your subscription is active" banner, gold lock + "Paid" badge, **Manage subscription**. |
| `plans.php` | The current plans (Individual 1–5, Group 10, Group 100, Museums & galleries 20). Yearly/monthly switch, Europe (€)/United States ($) switch, VAT note. |
| `checkout.php` | Order summary with VAT, billing details (email, name, company, country, optional EU VAT number for reverse charge, US ZIP), payment method, terms + "start right away" consent. |
| `provider_mock.php` | Neutral stand-in for the provider's hosted page with three buttons: succeeds / I cancel / fails. |
| `return.php` | Where the customer comes back. Demo only: it switches the plan on. **In production only the webhook does that.** |
| `manage.php` | Mock "Manage subscription": change plan, change payment method, cancel at end of period, **Withdraw from contract here** (EU 14 days), invoices. |
| `reset.php` | Start the demo again as a free account. |

The demo uses its own PHP session and its own texts (`_lang.php`, English + Dutch). It does not load `login/config.php`, the database or `lang/*.php`. Your real account is not touched.

Payment methods in the demo:

- **Europe:** iDEAL | Wero, Bancontact, SEPA Direct Debit, card, Apple Pay / Google Pay, PayPal.
- **US:** card, Apple Pay / Google Pay, PayPal, US bank account (ACH).

A plain **bank transfer** (like the current Rabobank QR on `3wid_signup_payment.php`) is left out of the subscription flow on purpose. It cannot renew by itself and you have to match every payment by hand. Keep it only for invoices to organisations that ask for it.

---

## 2. Which payment provider?

### Fees (online, per successful payment, without VAT)

| | **Mollie** (NL) | **Stripe** (NL account) | **Stripe Managed Payments** | **Paddle** | **Lemon Squeezy** |
|---|---|---|---|---|---|
| Role | Payment provider | Payment provider | Merchant of record (they sell, they handle tax) | Merchant of record | Merchant of record |
| Monthly fee | none | none | none | none | none |
| iDEAL \| Wero | €0.32 | €0.29 | standard fee + 3.5% | inside 5% + $0.50 | inside 5% + $0.50 |
| Bancontact | 1.40% + €0.25 | €0.35 | " | " | " |
| SEPA Direct Debit (renewals) | €0.35 | €0.35 | " | " | " |
| EEA consumer card | 1.80% + €0.25 | 1.5% + €0.25 (standard), 2.8% + €0.25 (premium) | " | " | " |
| Non-EEA card (e.g. US cards) | 3.25% + €0.25 | 3.15% + €0.25 | " | " | " |
| PayPal | PayPal fee + €0.10 | PayPal fee + 0.2% + €0.10 | " | PayPal included | PayPal included |
| Apple Pay / Google Pay | card rate | card rate | " | " | " |
| US bank (ACH) | **not offered** | 0.8%, max €5 (+1.5% if international) | " | " | " |
| Subscriptions | Recurring: no extra fee | Stripe Billing 0.7% of billing volume | included | included | included |
| Tax calculation | not included | Stripe Tax 0.5% per transaction (no-code) | included, 80+ countries | included | included |
| Chargeback / dispute | per Mollie terms | €20 card dispute, €15 ACH dispute, €4 failed ACH | | | |
| Currency | EUR + others, USD payout possible (1% if another currency is paid out) | many currencies; conversion ≥ 2% | | | |

Sources: [Mollie pricing](https://www.mollie.com/pricing) ·
[Stripe NL pricing](https://stripe.com/nl/pricing) ·
[Stripe NL local payment methods](https://stripe.com/nl/pricing/local-payment-methods) ·
[Stripe US pricing](https://stripe.com/pricing) (US accounts: 2.9% + 30¢, $15 dispute) ·
[Stripe Managed Payments](https://stripe.com/managed-payments) and [how it works](https://docs.stripe.com/payments/managed-payments/how-it-works) ·
[Paddle pricing](https://www.paddle.com/pricing) (5% + 50¢, custom pricing under $10) ·
[Lemon Squeezy pricing](https://www.lemonsqueezy.com/pricing) (5% + 50¢, extra fees in some cases; part of Stripe since 2024, see their [2026 update](https://www.lemonsqueezy.com/blog/2026-update)).

### What that means for a €10 / $10 per year 3WordID

| Payment | Provider fee | Share of €10 |
|---|---|---|
| iDEAL \| Wero via Mollie | €0.32 | 3.2% |
| iDEAL \| Wero via Stripe + Billing 0.7% | €0.29 + €0.07 = €0.36 | 3.6% |
| EEA card via Stripe + Billing | €0.40 + €0.07 = €0.47 | 4.7% |
| US card via Stripe (NL account) + Billing | €0.57 + €0.07 = €0.64 (+ conversion if paid out in EUR) | ~6–8% |
| Paddle / Lemon Squeezy | $0.50 + $0.50 = $1.00 | **10%** |
| **Monthly** €1.50 by card via Stripe | €0.27 + €0.01 | **19%** |

The fixed amount per payment (about €0.25–0.50) hurts small payments. **Yearly billing is much cheaper for you.** If you do offer monthly, offer it mainly with SEPA Direct Debit, or only on the bigger plans.

### Recommendation

**Start with Stripe** (Dutch account, EUR and USD prices). One integration covers both markets: iDEAL | Wero €0.29, Bancontact and SEPA Direct Debit €0.35, EEA cards 1.5% + €0.25, international cards 3.15% + €0.25 and US bank (ACH) 0.8% (max €5), plus Billing at 0.7% for the renewals. The hosted Checkout and the ready-made **customer portal** cover what the law asks for: an easy online cancel, invoices and a change of payment method. That saves building your own. Choose **Mollie** instead if almost all customers will be in the eurozone. It is Dutch, recurring is free, iDEAL costs €0.32, but there is no ACH, and US customers can only pay by card or PayPal. Only move to a **merchant of record** (Stripe Managed Payments +3.5%, or Paddle / Lemon Squeezy 5% + $0.50 ≈ 10% of a $10 sale) once VAT/sales tax administration in many countries becomes the bigger cost.

Good to know:

- **Wero:** all Dutch banks went live for Wero web payments on 26 Oct 2026. iDEAL is now "iDEAL | Wero" and moves fully to Wero by the end of 2027. **Wero subscriptions only arrive in Q2 2027.** Until then a renewal works like this: first payment with iDEAL | Wero (or Bancontact), and the provider sets up a SEPA Direct Debit mandate for the next payments. Mollie and Stripe both do this. Sources: [EPI](https://european-payments-initiative-lux.prezly.com/epi-company-initiates-wero-roll-out-in-the-netherlands), [Betaalvereniging](https://www.betaalvereniging.nl/actueel/volgende-fase-migratie-ideal-naar-wero-begint-in-oktober/), [iDEAL | Wero FAQ](https://www.betaalvereniging.nl/en/knowledge-base/online-payments/ideal-wero/), [Mollie recurring](https://docs.mollie.com/docs/recurring-payments).
- **SEPA Direct Debit:** the customer can reverse it for 8 weeks with no reason, or for 13 months if it was not authorised. The money also comes in later than with iDEAL. ([Mollie SEPA DD](https://docs.mollie.com/docs/sepa-direct-debit))

---

## 3. Admin checklist (what to organise)

Not legal or tax advice. Have an accountant check items 3–5 once, it is cheap compared to fixing it later.

1. **Business registration.** You need a registered business to open a merchant account (KYC asks for it). NL example: register in the [KvK Handelsregister](https://www.kvk.nl/en/registration/) as a sole trader (eenmanszaak) or a BV. You get a KvK number and a VAT ID (btw-id), which must go on the site and on the invoices.
2. **Business bank account.** A separate EUR account in your business name for payouts. Optionally a USD account, or a multi-currency account (Wise and others), so US money is not converted at ≥2%.
3. **VAT in the EU (OSS).** Below €10,000 per year of B2C sales to consumers in *other* EU countries, you may charge Dutch VAT (21%). Above that, you charge the customer's country rate (the demo already does this) and file one quarterly **OSS** return with the Belastingdienst. Business customers with a valid VAT number in another EU country: **reverse charge** (0% plus the text "VAT reverse charged"); check the number in [VIES](https://ec.europa.eu/taxation_customs/vies/). Keep two pieces of evidence of the customer's country (billing country + IP or bank country). Sources: [EU OSS](https://vat-one-stop-shop.ec.europa.eu/index_en). Small-business scheme **KOR**: VAT exemption for NL businesses with ≤ €20,000 turnover per year, but then you cannot reclaim VAT on costs. Ask your accountant whether it fits with OSS sales ([business.gov.nl KOR](https://business.gov.nl/subsidies-and-schemes/small-businesses-scheme/)).
4. **US sales tax.** As a foreign seller you only have to collect sales tax in a state once you pass its "economic nexus" threshold. That is usually $100,000 in sales per year (CA/NY/TX $500,000; AL/MS $250,000), and SaaS is only taxable in some states. At 3WordID prices this is far away. Watch the totals per state (Stripe Tax can do this) and decide when you get close. Sources: [TaxCloud SaaS by state](https://taxcloud.com/blog/saas-sales-tax-by-state/), [Stripe on digital products](https://stripe.com/resources/more/sales-tax-on-digital-products-by-state).
5. **Invoices.** Every payment needs an invoice with: invoice number (consecutive), date, your name/address/VAT ID/KvK, the customer's name and address (and VAT ID for reverse charge), what you supplied and the period, the amount excl. VAT, the VAT rate and VAT amount (or "VAT reverse charged"), and the total. Up to €100 a simplified invoice is allowed. Stripe Billing and Mollie can make these automatically. Sources: [Belastingdienst](https://www.belastingdienst.nl/wps/wcm/connect/bldcontenten/belastingdienst/business/vat/vat_in_the_netherlands/vat_administration/invoice_requirements), [business.gov.nl](https://business.gov.nl/regulations/invoice-requirements/).
6. **Terms & conditions.** Add a "Subscriptions" part to `terms.php`:
   - what each plan includes, the price incl. VAT and that it **renews automatically**;
   - how to cancel (online, any time, effective at the end of the paid period) and a reminder before a yearly renewal;
   - the **EU 14-day right of withdrawal**. For a digital service it normally ends or becomes pro-rata once the service has started at the customer's request. Simplest and friendliest: **give a full refund within 14 days**, as the demo does. The checkbox "Start my subscription right away…" records the consent;
   - since **19 June 2026** an online contract must have a clearly labelled **"withdraw from contract here"** function with a confirm step. The demo has this on Manage subscription ([Directive (EU) 2023/2673](https://eur-lex.europa.eu/eli/dir/2023/2673/oj/eng), [ACM](https://www.acm.nl/en/publications/acm-calls-online-retailers-prepare-withdrawal-button), [EU consumer rights summary](https://eur-lex.europa.eu/EN/legal-content/summary/consumer-information-right-of-withdrawal-and-other-consumer-rights.html));
   - what happens at the end: extra 3WordIDs are switched off, not deleted (see 4.6);
   - museums: what proof you ask for and what happens if it is not given.
7. **Privacy.** Update the privacy statement. Name the payment provider as a recipient (it acts as an independent controller for payments and/or as a processor). State which billing data you keep and for how long: **7 years** for the books in NL. Sign the provider's data processing agreement where they offer one.
8. **Refunds.** Decide on a policy (e.g. full refund within 14 days, otherwise none, but you keep your plan until the end of the period) and put it in the terms. Refund through the provider's dashboard/API, never by separate bank transfer, so the invoice and the VAT stay right (issue a credit note).
9. **Chargebacks / disputes.** Expect a fee per dispute (Stripe €20 card, €15 ACH). Answer with evidence: invoice, login logs, terms accepted at checkout. Switch the plan off automatically when a dispute is lost (webhook).
10. **KYC at the provider.** Have ready: ID document, KvK extract, bank account in the business name, website with terms, prices, contact details and refund policy. The provider checks the site, so the plans page and terms must be live (they can be unlinked) before you apply.
11. **Bookkeeping.** Export the provider's payouts and fees monthly into your bookkeeping, e.g. via a connection in Moneybird, e-Boekhouden or Exact. Keep track of VAT per country for OSS. Keep everything for 7 years.

---

## 4. Technical integration plan (for later)

### 4.1 Pages and button

- **Subscribe button:** on `3wid_list.php`, in the `.toolbar` after "Search box for your website" (exactly where the demo shows it). Show it only to free users; paying users get "Manage subscription" instead. Also show it in the "only one 3WordID" message.
- New pages:
  - `plans.php` (the real one, rebuilt from the demo; also fix the current issues: `<title>` says "Terms and Conditions", "2." appears twice, and Group 10 says "$60" in the heading but "$30" in the text);
  - `subscribe_checkout.php`;
  - `subscribe_return.php`;
  - `subscribe_webhook.php`;
  - `subscribe_manage.php`, or a link to the Stripe customer portal.
- Login required on all of them except the webhook.

### 4.2 Checkout (server side)

1. Take the plan, billing, quantity and region from the form, and **look up the price on the server**. Never take an amount from the browser.
2. Create a row in `payments` with `status = 'open'` and our own `order_ref`.
3. Create the checkout at the provider (Stripe Checkout Session in `subscription` mode with `client_reference_id = order_ref` and `metadata.user_id`, or a Mollie first payment with `sequenceType=first`). Set:
   - `success_url` / `redirectUrl` = `subscribe_return.php?ref=<order_ref>`;
   - `cancel_url` = `plans.php?note=canceled`;
   - the webhook URL.
4. Redirect to the provider's page.

### 4.3 Return URL

- `subscribe_return.php` **only shows a status.** It loads our payment row by `order_ref` and checks that it belongs to the logged-in user.
  - If the webhook has already marked it paid: green banner on the list page.
  - If not yet: "We are confirming your payment…" and reload after a few seconds.
  - If failed: back to checkout with a message.
- Never switch anything on based on the URL. Customers can close the browser before they return, and anyone can type `?status=paid`.

### 4.4 Webhook (the only place that changes state)

- `subscribe_webhook.php`, POST from the provider:
  - **Stripe:** verify the `Stripe-Signature` header with the webhook secret, kept in `login/config.php` and never in git.
  - **Mollie:** the webhook only sends an `id`. Fetch that payment from the Mollie API with your API key and trust only that answer.
- Make it idempotent: store the provider's event/payment id with a unique key and ignore repeats. Respond `200` quickly.
- Events to handle:
  - paid / `invoice.paid`: payment `paid`; subscription `active`; `current_period_end` from the provider; update the user (4.6); create the invoice row; send a receipt email.
  - failed / `invoice.payment_failed`: payment `failed`. Subscription `past_due` (the provider retries). Email the customer.
  - canceled at period end / `customer.subscription.updated`: set `cancel_at_period_end = 1`.
  - ended / `customer.subscription.deleted`: subscription `ended`; update the user (4.6).
  - refund / dispute lost: `refunded` / `charged_back`. Withdrawal within 14 days: subscription `withdrawn`, user back to free.

### 4.5 Cron (daily)

`cron/subscriptions_expire.php`, once a day (like the existing `tools/cleanup_empty_accounts.php`):

- `active` or `past_due` subscriptions with `current_period_end` more than 3 days ago and no successful renewal → `ended`, then update the user (4.6). This is a safety net in case a webhook was missed.
- 30 days and 7 days before a **yearly** renewal: send the reminder email (good practice; required in some countries).

### 4.6 Mapping to the current user fields

Current rules in the code:

- `user_is_paying()` = `user_type > 0` and not 9 (9 = blocked).
- `db_3wid_validate_type()` allows numbers in 3WordIDs when `user_type > 0`.
- The list page allows `max_3wids + 1` 3WordIDs, so a free user with `max_3wids = 0` has 1.
- `db_3wid_update_credit_usertype()` already updates `credit`, `user_type` and `max_3wids` together.

| Event | `user_type` | `max_3wids` |
|---|---|---|
| Subscription becomes active | the paying type (1, or whatever the existing paying value is; check before using) | (3WordIDs in the plan × quantity) − 1, e.g. Group 10 → 9, Individual ×3 → 2 |
| Change of plan | unchanged | recalculated |
| Ended / withdrawn / dispute lost | 0 | 0 |

Never touch `user_type = 9` (blocked). Never delete 3WordIDs when a plan ends: switch the ones above the free limit **off** (they stay in the list) and say so in the terms. Museums: a manual check of the proof before or after the first payment (subscription status `pending_review`).

The `google_users` columns `payment_status`, `payment_method`, `paypal_email`, `reseller_code` and `code` can stay for the old manual flow. New subscriptions go into the new tables below.

### 4.7 Proposed tables (MySQL 8). **Proposal only, do not run yet.**

Check the type of `google_users.id` first (`SHOW CREATE TABLE google_users`) and use the same type for `user_id`. No foreign keys, matching the rest of the schema.

```sql
CREATE TABLE subscriptions (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id                INT NOT NULL,
  plan_key               VARCHAR(20) NOT NULL,          -- single, group10, group100, museum20
  quantity               SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  billing_period         ENUM('yearly','monthly') NOT NULL DEFAULT 'yearly',
  currency               CHAR(3) NOT NULL,              -- EUR, USD
  amount_cents           INT UNSIGNED NOT NULL,         -- price per period incl. VAT
  status                 ENUM('pending','pending_review','active','past_due','canceled','ended','withdrawn') NOT NULL DEFAULT 'pending',
  cancel_at_period_end   TINYINT(1) NOT NULL DEFAULT 0,
  provider               VARCHAR(20) NOT NULL,          -- stripe, mollie
  provider_customer_id   VARCHAR(64) NULL,
  provider_subscription_id VARCHAR(64) NULL,
  provider_mandate_id    VARCHAR(64) NULL,              -- Mollie SEPA mandate
  billing_name           VARCHAR(120) NOT NULL,
  billing_company        VARCHAR(120) NULL,
  billing_country        CHAR(2) NOT NULL,
  billing_postcode       VARCHAR(16) NULL,
  vat_number             VARCHAR(20) NULL,
  vat_number_valid       TINYINT(1) NULL,               -- VIES result
  started_at             DATETIME NULL,
  current_period_end     DATETIME NULL,
  withdraw_until         DATETIME NULL,                 -- started_at + 14 days, EU consumers
  consent_immediate_at   DATETIME NULL,                 -- "start right away" checkbox
  terms_version          VARCHAR(20) NOT NULL,
  ended_at               DATETIME NULL,
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_provider_sub (provider, provider_subscription_id),
  KEY idx_user (user_id),
  KEY idx_status_end (status, current_period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  subscription_id        INT UNSIGNED NULL,
  user_id                INT NOT NULL,
  order_ref              CHAR(24) NOT NULL,             -- our own reference, also in the return URL
  provider               VARCHAR(20) NOT NULL,
  provider_payment_id    VARCHAR(64) NULL,
  method                 VARCHAR(20) NULL,              -- ideal, bancontact, sepa_dd, card, wallet, paypal, ach
  sequence               ENUM('first','recurring','oneoff') NOT NULL DEFAULT 'first',
  currency               CHAR(3) NOT NULL,
  net_cents              INT UNSIGNED NOT NULL,
  vat_cents              INT UNSIGNED NOT NULL DEFAULT 0,
  total_cents            INT UNSIGNED NOT NULL,
  vat_rate               DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  vat_mode               ENUM('domestic','oss','reverse_charge','outside_eu') NOT NULL,
  status                 ENUM('open','paid','failed','canceled','expired','refunded','charged_back') NOT NULL DEFAULT 'open',
  paid_at                DATETIME NULL,
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_order_ref (order_ref),
  UNIQUE KEY uq_provider_payment (provider, provider_payment_id),
  KEY idx_sub (subscription_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_number         VARCHAR(20) NOT NULL,          -- consecutive, e.g. 3WID-2026-0001
  payment_id             INT UNSIGNED NOT NULL,
  user_id                INT NOT NULL,
  kind                   ENUM('invoice','credit_note') NOT NULL DEFAULT 'invoice',
  issued_at              DATETIME NOT NULL,
  customer_name          VARCHAR(120) NOT NULL,
  customer_company       VARCHAR(120) NULL,
  customer_country       CHAR(2) NOT NULL,
  customer_vat_number    VARCHAR(20) NULL,
  description            VARCHAR(255) NOT NULL,         -- plan + period
  currency               CHAR(3) NOT NULL,
  net_cents              INT NOT NULL,                  -- negative for credit notes
  vat_cents              INT NOT NULL,
  total_cents            INT NOT NULL,
  vat_rate               DECIMAL(4,2) NOT NULL,
  vat_mode               ENUM('domestic','oss','reverse_charge','outside_eu') NOT NULL,
  provider_invoice_id    VARCHAR(64) NULL,              -- when the provider makes the PDF
  pdf_path               VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_number (invoice_number),
  KEY idx_payment (payment_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_events (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider               VARCHAR(20) NOT NULL,
  event_id               VARCHAR(80) NOT NULL,          -- for idempotency
  event_type             VARCHAR(60) NOT NULL,
  received_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at           DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_event (provider, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.8 Secrets and deploy

- API keys and the webhook secret go in `login/config.php` on the server (never in git), with separate test and live keys. Do everything in **test mode** first; Stripe and Mollie both have one.
- `DEPLOY.md`'s file check will also list the `demo/payments/` files. They are standalone; remove the folder from the server once the real flow is live.

---

## 5. Open decisions for you

1. Euro prices: the demo uses the dollar numbers (€10 / €60 / €500 / €20). Choose real EUR prices, incl. VAT.
2. Monthly: offer it at all? The demo shows monthly prices as placeholders (1.50 / 6 / 50, museums yearly only). Fixed fees make monthly card payments expensive (≈19% on €1.50).
3. Group 10: $60 (heading on the live plans page) or $30 (text below it)?
4. 14-day withdrawal: a full refund (demo, simple) or pro rata?
5. Mollie or Stripe (see 2). Decide before building the real flow; the database plan above works for both.
