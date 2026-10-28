<?php
/**
 * 3WordID payment flow DEMO - shared code for all demo pages.
 *
 * Self-contained on purpose: no database, no login, no real payment calls, and it does not
 * include php/functions.php or login/config.php. State (chosen plan, fake orders, "active"
 * subscription) lives in a separate PHP session (cookie "3wid_paydemo") so it never touches
 * the real login session. Texts are in _lang.php (en + nl), not in lang/*.php.
 *
 * Nothing links here from the live site. Open /demo/payments/ directly.
 */

declare(strict_types=1);

session_name('3wid_paydemo');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
session_start();

header('X-Robots-Tag: noindex, nofollow');

if (!isset($_SESSION['paydemo']) || !is_array($_SESSION['paydemo'])) {
    $_SESSION['paydemo'] = ['orders' => [], 'sub' => null];
}

/** The seller's own country. Used for the VAT example (home VAT rate, reverse charge). */
const PD_SELLER_COUNTRY = 'NL';

// ---------------------------------------------------------------- language

function pd_languages(): array
{
    return ['en', 'nl'];
}

function pd_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $allowed = pd_languages();
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        $_SESSION['paydemo_lang'] = $_GET['lang'];
    }
    if (isset($_SESSION['paydemo_lang']) && in_array($_SESSION['paydemo_lang'], $allowed, true)) {
        return $lang = $_SESSION['paydemo_lang'];
    }
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    return $lang = (strpos($accept, 'nl') === 0 ? 'nl' : 'en');
}

/** Raw text (may contain intended markup) with {placeholders} replaced by escaped values. */
function pd_raw(string $key, array $vars = []): string
{
    static $texts = null;
    if ($texts === null) {
        $texts = require __DIR__ . '/_lang.php';
    }
    $s = $texts[pd_lang()][$key] ?? $texts['en'][$key] ?? $key;
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $s);
    }
    return $s;
}

/** Plain text, HTML-escaped. */
function pd_t(string $key, array $vars = []): string
{
    static $texts = null;
    if ($texts === null) {
        $texts = require __DIR__ . '/_lang.php';
    }
    $s = $texts[pd_lang()][$key] ?? $texts['en'][$key] ?? $key;
    $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $s);
    }
    return $s;
}

function pd_e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------- region, plans, money

/** 'eu' (EUR, prices incl. VAT) or 'us' (USD, prices excl. sales tax). ?region= wins, then the session. */
function pd_region(): string
{
    if (isset($_GET['region']) && in_array($_GET['region'], ['eu', 'us'], true)) {
        $_SESSION['paydemo_region'] = $_GET['region'];
    }
    $r = $_SESSION['paydemo_region'] ?? 'eu';
    return in_array($r, ['eu', 'us'], true) ? $r : 'eu';
}

function pd_currency(string $region): string
{
    return $region === 'us' ? 'USD' : 'EUR';
}

/**
 * Plans, based on the live plans.php (23 Sept 2026): $10 per 3WordID per year (1-5),
 * 10 for $60, 100 for $500, museums/galleries 20 for $20. All yearly.
 * EUR uses the same numbers (placeholder). Monthly prices are PLACEHOLDERS: the live site
 * only sells yearly. 'monthly' => null means "yearly only".
 */
function pd_plans(): array
{
    return [
        'single' => ['qty_min' => 1, 'qty_max' => 5, 'ids' => 1, 'yearly' => 10.00, 'monthly' => 1.50, 'proof' => false],
        'group10' => ['qty_min' => 1, 'qty_max' => 1, 'ids' => 10, 'yearly' => 60.00, 'monthly' => 6.00, 'proof' => false],
        'group100' => ['qty_min' => 1, 'qty_max' => 1, 'ids' => 100, 'yearly' => 500.00, 'monthly' => 50.00, 'proof' => false],
        'museum20' => ['qty_min' => 1, 'qty_max' => 1, 'ids' => 20, 'yearly' => 20.00, 'monthly' => null, 'proof' => true],
    ];
}

function pd_plan_price(string $plan, string $billing, int $qty): ?float
{
    $plans = pd_plans();
    if (!isset($plans[$plan])) {
        return null;
    }
    $unit = $billing === 'monthly' ? $plans[$plan]['monthly'] : $plans[$plan]['yearly'];
    if ($unit === null) {
        return null;
    }
    return round($unit * $qty, 2);
}

function pd_money(float $amount, string $currency): string
{
    $lang = pd_lang();
    $num = $lang === 'nl'
        ? number_format($amount, 2, ',', '.')
        : number_format($amount, 2, '.', ',');
    if ($currency === 'USD') {
        return '$' . $num;
    }
    return $lang === 'nl' ? '€ ' . $num : '€' . $num;
}

/** Standard VAT rates (EU, %). Demo values: check the current rates before going live. */
function pd_vat_rates(): array
{
    return [
        'AT' => 20, 'BE' => 21, 'BG' => 20, 'HR' => 25, 'CY' => 19, 'CZ' => 21, 'DK' => 25,
        'EE' => 24, 'FI' => 25.5, 'FR' => 20, 'DE' => 19, 'GR' => 24, 'HU' => 27, 'IE' => 23,
        'IT' => 22, 'LV' => 21, 'LT' => 21, 'LU' => 17, 'MT' => 18, 'NL' => 21, 'PL' => 23,
        'PT' => 23, 'RO' => 21, 'SK' => 23, 'SI' => 22, 'ES' => 21, 'SE' => 25,
    ];
}

/** Country names for the EU billing select (English names, kept short). */
function pd_eu_countries(): array
{
    return [
        'AT' => 'Austria', 'BE' => 'Belgium', 'BG' => 'Bulgaria', 'HR' => 'Croatia', 'CY' => 'Cyprus',
        'CZ' => 'Czechia', 'DK' => 'Denmark', 'EE' => 'Estonia', 'FI' => 'Finland', 'FR' => 'France',
        'DE' => 'Germany', 'GR' => 'Greece', 'HU' => 'Hungary', 'IE' => 'Ireland', 'IT' => 'Italy',
        'LV' => 'Latvia', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands',
        'PL' => 'Poland', 'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia', 'SI' => 'Slovenia',
        'ES' => 'Spain', 'SE' => 'Sweden',
    ];
}

/**
 * Order totals.
 * EU consumer: the list price INCLUDES VAT at the customer's country rate (OSS; under the EUR 10,000
 *   threshold the seller's home rate may be used instead - see docs/PAYMENTS.md).
 * EU business with a VAT number in another EU country: reverse charge, price excl. home VAT, 0% VAT.
 * US: the list price EXCLUDES sales tax; tax (if any) would be added by the provider's tax tool.
 */
function pd_totals(float $list, string $region, string $country, string $vat_number): array
{
    if ($region === 'us') {
        return ['net' => $list, 'vat' => 0.0, 'total' => $list, 'rate' => 0, 'mode' => 'us'];
    }
    $rates = pd_vat_rates();
    $home = (float)$rates[PD_SELLER_COUNTRY];
    if ($vat_number !== '' && $country !== PD_SELLER_COUNTRY) {
        $net = round($list / (1 + $home / 100), 2);
        return ['net' => $net, 'vat' => 0.0, 'total' => $net, 'rate' => 0, 'mode' => 'reverse'];
    }
    $rate = (float)($rates[$country] ?? $home);
    $net = round($list / (1 + $rate / 100), 2);
    return ['net' => $net, 'vat' => round($list - $net, 2), 'total' => $list, 'rate' => $rate, 'mode' => 'incl'];
}

function pd_methods(string $region): array
{
    return $region === 'us'
        ? ['card', 'wallet', 'paypal', 'ach']
        : ['ideal', 'bancontact', 'sepa_dd', 'card', 'wallet', 'paypal'];
}

function pd_plan_name(string $plan, int $qty = 1): string
{
    if ($plan === 'single') {
        return pd_t('plan.single.name') . ($qty > 1 ? ' × ' . $qty : '');
    }
    return pd_t('plan.' . $plan . '.name');
}

function pd_date(int $ts): string
{
    if (pd_lang() === 'nl') {
        $m = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
        return date('j', $ts) . ' ' . $m[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }
    return date('j F Y', $ts);
}

// ---------------------------------------------------------------- page chrome

function pd_url(string $page, array $params = []): string
{
    return $page . ($params ? '?' . http_build_query($params) : '');
}

/** Link to the current page in another language (keeps the other GET params). */
function pd_lang_url(string $code): string
{
    $q = $_GET;
    $q['lang'] = $code;
    return '?' . http_build_query($q);
}

function pd_head(string $title, string $page_class = ''): void
{
    $lang = pd_lang();
    ?><!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo pd_e($title); ?> — 3WordID (demo)</title>
    <link rel="icon" type="image/x-icon" href="../../img/favicon.ico">
    <link href="../../css/inter.css" rel="stylesheet">
    <link href="../../css/3wid_list.css?v=4" rel="stylesheet">
    <link href="demo.css?v=1" rel="stylesheet">
</head>
<body class="<?php echo pd_e($page_class); ?>">
    <div class="demo-strip" role="note">
        <strong><?php echo pd_t('demo.ribbon'); ?></strong>
        <span class="demo-strip-links">
            <a href="index.php"><?php echo pd_t('demo.start'); ?></a>
            <a href="reset.php"><?php echo pd_t('demo.reset'); ?></a>
        </span>
    </div>
    <header class="site-header">
        <a class="brand" href="index.php" title="<?php echo pd_t('nav.list'); ?>">
            <span class="brand-mark"><img width="35" height="35" src="../../img/3wid_big.png" alt=""></span>
            3WordID
        </a>
        <nav class="nav-links">
            <a href="plans.php"><?php echo pd_t('nav.plans'); ?></a>
        </nav>
        <div class="header-actions">
            <span class="pd-lang" aria-label="Language">
                <?php foreach (pd_languages() as $code) { ?>
                <a href="<?php echo pd_e(pd_lang_url($code)); ?>"<?php echo $code === $lang ? ' aria-current="true"' : ''; ?>><?php echo strtoupper($code); ?></a>
                <?php } ?>
            </span>
            <a href="index.php" class="logout-icon" title="<?php echo pd_t('nav.logout_demo'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </header>
<?php
}

function pd_foot(): void
{
    ?>
    <footer class="site-footer">
        <div><?php echo pd_t('demo.footer'); ?></div>
    </footer>
</body>
</html>
<?php
}

/** Small gold lock (same gold as the homepage search lock). */
function pd_gold_lock(string $title): string
{
    return '<span class="gold-lock" title="' . pd_e($title) . '" role="img" aria-label="' . pd_e($title) . '">'
        . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">'
        . '<rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>';
}
