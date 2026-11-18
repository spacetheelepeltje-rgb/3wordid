<?php
/**
 * Payments configuration (Mollie).
 *
 * Copy this file to login/payments.config.php on the server and fill in your
 * Mollie API key. login/payments.config.php is gitignored and must never be
 * committed. Never put a real key in this example file.
 *
 * The Bumperchat plate product is defined here at EUR 7.95 per year (one-off
 * iDEAL, no automatic renewal). Later 3WordID pricing tiers can be added as
 * extra entries in 'products' — the shared module in php/payments/ reads
 * amount, currency, period and method from this array at payment time and
 * stores those values on the payment row.
 */

return [
    // Public site URL, no trailing slash. Used to build redirectUrl and webhookUrl.
    // Must be HTTPS and reachable by Mollie (the webhook cannot be localhost).
    'public_base_url' => 'https://www.3wordid.com',

    'mollie' => [
        // test_... for test mode, live_... for live payments.
        // Placeholder only — replace in payments.config.php, never in this file.
        'api_key' => 'test_xxxxxxxx',
    ],

    'products' => [
        'bumperchat_plate' => [
            'name' => [
                'en' => 'Bumperchat plate',
                'nl' => 'Bumperchat-kenteken',
                'fr' => 'Plaque Bumperchat',
                'es' => 'Matrícula Bumperchat',
            ],
            'amount' => '7.95',
            'currency' => 'EUR',
            'interval' => 'year',
            'interval_count' => 1,
            'method' => 'ideal',
            'renewal' => 'manual',
            'description' => 'Bumperchat {subject} — {period}',
        ],
        // Later: add 3WordID account tiers here (individual, group_10, ...).
    ],
];
