<?php
/**
 * Shared payments configuration loader.
 *
 * Reads login/payments.config.php (gitignored). Product prices and periods
 * come from the 'products' array in that file so later 3WordID tiers can be
 * added without changing this module. The amount charged is always the value
 * in config at the moment the payment is created, and is stored on the payment
 * row.
 */

function payments_config_path(): string
{
    return dirname(__DIR__, 2) . '/login/payments.config.php';
}

function payments_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $path = payments_config_path();
    if (!is_readable($path)) {
        $cfg = [];
        return $cfg;
    }
    $loaded = include $path;
    $cfg = is_array($loaded) ? $loaded : [];
    return $cfg;
}

function payments_configured(): bool
{
    $key = payments_mollie_api_key();
    return $key !== '' && $key !== 'test_xxxxxxxx';
}

function payments_mollie_api_key(): string
{
    $cfg = payments_config();
    $key = trim((string) ($cfg['mollie']['api_key'] ?? ''));
    return $key;
}

function payments_is_test_mode(): bool
{
    $key = payments_mollie_api_key();
    return $key === '' || str_starts_with($key, 'test_');
}

function payments_public_base_url(): string
{
    $cfg = payments_config();
    $url = trim((string) ($cfg['public_base_url'] ?? ''));
    if ($url === '' && isset($GLOBALS['main_url'])) {
        $url = (string) $GLOBALS['main_url'];
    }
    return rtrim($url, '/');
}

function payments_product(string $key): ?array
{
    $cfg = payments_config();
    $products = $cfg['products'] ?? [];
    if (!is_array($products) || !isset($products[$key]) || !is_array($products[$key])) {
        return null;
    }
    $p = $products[$key];
    $p['key'] = $key;
    $p['amount'] = payments_amount_value($p['amount'] ?? '0');
    $p['currency'] = strtoupper((string) ($p['currency'] ?? 'EUR'));
    $p['interval'] = (string) ($p['interval'] ?? 'year');
    $p['interval_count'] = max(1, (int) ($p['interval_count'] ?? 1));
    $p['method'] = (string) ($p['method'] ?? 'ideal');
    $p['renewal'] = (string) ($p['renewal'] ?? 'manual');
    return $p;
}

/** Mollie requires a string with two decimals, e.g. "7.95". */
function payments_amount_value($amount): string
{
    if (is_string($amount)) {
        $amount = str_replace(',', '.', trim($amount));
    }
    return number_format((float) $amount, 2, '.', '');
}

function payments_product_name(array $product, ?string $lang = null): string
{
    $name = $product['name'] ?? $product['key'] ?? '';
    if (is_array($name)) {
        $lang = $lang ?: (function_exists('current_lang') ? current_lang() : 'en');
        return (string) ($name[$lang] ?? $name['en'] ?? reset($name) ?? '');
    }
    return (string) $name;
}

function payments_period_phrase(array $product, ?string $lang = null): string
{
    $count = (int) ($product['interval_count'] ?? 1);
    $unit = (string) ($product['interval'] ?? 'year');
    $lang = $lang ?: (function_exists('current_lang') ? current_lang() : 'en');
    if ($unit === 'year') {
        if ($lang === 'nl') {
            return $count === 1 ? '1 jaar' : $count . ' jaar';
        }
        return $count === 1 ? '1 year' : $count . ' years';
    }
    if ($unit === 'month') {
        if ($lang === 'nl') {
            return $count === 1 ? '1 maand' : $count . ' maanden';
        }
        return $count === 1 ? '1 month' : $count . ' months';
    }
    return $count . ' ' . $unit;
}

function payments_description(array $product, string $subject): string
{
    $tpl = (string) ($product['description'] ?? '{product} {subject} — {period}');
    $text = strtr($tpl, [
        '{product}' => payments_product_name($product, 'en'),
        '{subject}' => $subject,
        '{period}' => payments_period_phrase($product, 'en'),
    ]);
    // Mollie description max 255 characters
    return substr($text, 0, 255);
}

/** New period end: from $from (or now), add the product interval. */
function payments_period_end(?string $from, array $product, ?int $now = null): string
{
    $now = $now ?? time();
    $start = $now;
    if ($from) {
        $ts = strtotime($from);
        if ($ts !== false && $ts > $start) {
            $start = $ts;
        }
    }
    $count = max(1, (int) ($product['interval_count'] ?? 1));
    $unit = (string) ($product['interval'] ?? 'year');
    if ($unit !== 'year' && $unit !== 'month' && $unit !== 'day') {
        $unit = 'year';
    }
    return date('Y-m-d H:i:s', strtotime('+' . $count . ' ' . $unit, $start));
}
