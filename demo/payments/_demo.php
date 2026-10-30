<?php
/**
 * 3WordID payment flow DEMO (Mollie, iDEAL | Wero only) - shared code for all demo pages.
 *
 * Self-contained on purpose: no database, no login, no real payment calls, and it does not
 * include php/functions.php or login/config.php. State (chosen plan, fake orders, "active"
 * subscription) lives in a separate PHP session (cookie "3wid_paydemo") so it never touches
 * the real login session. Texts are in _lang.php (en + nl), not in lang/*.php.
 * Plans and prices are in plans.json (see docs/PAYMENTS.md, "Editing plans.json").
 *
 * Nothing links here from the live site. Open /demo/payments/ directly.
 */

declare(strict_types=1);

session_name('3wid_paydemo');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
session_start();

header('X-Robots-Tag: noindex, nofollow');

// 'v' = shape of the demo state. Older demo sessions (other demo versions) start fresh.
if (!isset($_SESSION['paydemo']) || !is_array($_SESSION['paydemo']) || ($_SESSION['paydemo']['v'] ?? 0) !== 2) {
    $_SESSION['paydemo'] = ['v' => 2, 'orders' => [], 'sub' => null];
}

/** The seller's own country. Used for the VAT example (home VAT rate, reverse charge). */
const PD_SELLER_COUNTRY = 'NL';

/** Payment provider shown in the demo texts. Mollie (iDEAL | Wero first payment, then SEPA Direct Debit). */
const PD_PROVIDER = 'Mollie';

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

// ---------------------------------------------------------------- plans (plans.json), money, VAT

/** Only currency in this demo: EUR. iDEAL | Wero only takes euros. */
const PD_CURRENCY = 'EUR';

/** Billing periods a plan can have in plans.json ("prices": {"year": .., "month": ..}). */
const PD_PERIODS = ['year', 'month'];

/** Where the plans live. Public file, no secrets in it. */
const PD_PLANS_FILE = __DIR__ . '/plans.json';

/**
 * Load and check plans.json. Returns ['plans' => [id => plan], 'placeholder' => bool].
 * Throws RuntimeException with a short, human message when the file is missing, is not valid
 * JSON or a plan is incomplete. Pages call pd_plans(), which turns that into a friendly page.
 */
function pd_load_plans(string $file): array
{
    if (!is_file($file) || !is_readable($file)) {
        throw new RuntimeException('plans.json was not found.');
    }
    $raw = (string)file_get_contents($file);
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
        $raw = substr($raw, 3); // tolerate a BOM added by some editors
    }
    try {
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new RuntimeException('plans.json is not valid JSON (' . $e->getMessage() . '). Check for a missing comma or quote, or a comma after the last item.');
    }
    if (!is_array($data) || !isset($data['plans']) || !is_array($data['plans']) || !$data['plans']) {
        throw new RuntimeException('plans.json needs a "plans" list with at least one plan.');
    }
    if (isset($data['currency']) && $data['currency'] !== PD_CURRENCY) {
        throw new RuntimeException('plans.json: "currency" must be "EUR" (iDEAL only takes euros).');
    }
    $plans = [];
    foreach (array_values($data['plans']) as $i => $p) {
        $where = 'plan ' . ($i + 1);
        if (!is_array($p)) {
            throw new RuntimeException("plans.json: $where is not an object { … }.");
        }
        $id = $p['id'] ?? null;
        if (!is_string($id) || !preg_match('/^[a-z0-9_]{1,20}$/', $id)) {
            throw new RuntimeException("plans.json: $where needs an \"id\" of 1-20 lowercase letters, digits or _.");
        }
        $where = 'plan "' . $id . '"';
        if (isset($plans[$id])) {
            throw new RuntimeException("plans.json: the id \"$id\" is used twice.");
        }
        if (!isset($p['name']['en']) || !is_string($p['name']['en']) || trim($p['name']['en']) === '') {
            throw new RuntimeException("plans.json: $where needs a \"name\" with at least \"en\".");
        }
        foreach (['ids', 'qty_min', 'qty_max'] as $k) {
            $p[$k] = $p[$k] ?? ($k === 'ids' ? null : 1);
            if (!is_int($p[$k]) || $p[$k] < 1 || $p[$k] > 100000) {
                throw new RuntimeException("plans.json: $where needs \"$k\" as a whole number of 1 or more.");
            }
        }
        if ($p['qty_min'] > $p['qty_max'] || $p['qty_max'] > 100) {
            throw new RuntimeException("plans.json: $where: \"qty_min\" must be ≤ \"qty_max\" (max 100).");
        }
        $prices = [];
        foreach (PD_PERIODS as $per) {
            $v = $p['prices'][$per] ?? null;
            if ($v === null) {
                continue;
            }
            if ((!is_int($v) && !is_float($v)) || $v <= 0 || $v > 100000) {
                throw new RuntimeException("plans.json: $where: price \"$per\" must be a number like 10.00 (or null).");
            }
            $prices[$per] = round((float)$v, 2);
        }
        if (!$prices) {
            throw new RuntimeException("plans.json: $where needs a price for \"year\" and/or \"month\".");
        }
        $texts = function ($v): array {
            $out = [];
            foreach ((array)$v as $lang => $s) {
                if (is_string($lang) && is_string($s)) {
                    $out[$lang] = $s;
                }
            }
            return $out;
        };
        $features = [];
        foreach ((array)($p['features'] ?? []) as $lang => $list) {
            if (is_string($lang) && is_array($list)) {
                $features[$lang] = array_values(array_filter($list, 'is_string'));
            }
        }
        $plans[$id] = [
            'id' => $id,
            'name' => $texts($p['name']),
            'description' => $texts($p['description'] ?? []),
            'ids' => $p['ids'],
            'qty_min' => $p['qty_min'],
            'qty_max' => $p['qty_max'],
            'prices' => $prices,
            'features' => $features,
            'highlight' => !empty($p['highlight']),
            'proof' => !empty($p['proof_required']),
        ];
    }
    return ['plans' => $plans, 'placeholder' => !array_key_exists('prices_are_placeholders', $data) || !empty($data['prices_are_placeholders'])];
}

/** Plans from plans.json, cached per request. Shows a friendly error page and stops if the file is broken. */
function pd_plans_data(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    try {
        return $data = pd_load_plans(PD_PLANS_FILE);
    } catch (RuntimeException $e) {
        error_log('3WordID payment demo: ' . $e->getMessage());
        http_response_code(503);
        pd_head(pd_t('err.plans_title'), 'page-error');
        echo '<main class="wrap narrow"><div class="flash flash-err" role="alert" id="plansError"><strong>'
            . pd_t('err.plans_title') . '</strong><p>' . pd_t('err.plans_body') . '</p>'
            . '<p class="muted small">' . pd_e($e->getMessage()) . '</p></div></main>';
        pd_foot();
        exit;
    }
}

function pd_plans(): array
{
    return pd_plans_data()['plans'];
}

/** A text from plans.json in the current language, English as fallback. */
function pd_ptext(array $texts): string
{
    return (string)($texts[pd_lang()] ?? $texts['en'] ?? '');
}

function pd_plan_features(array $plan): array
{
    return $plan['features'][pd_lang()] ?? $plan['features']['en'] ?? [];
}

function pd_plan_price(string $plan, string $period, int $qty): ?float
{
    $plans = pd_plans();
    if (!isset($plans[$plan]) || !isset($plans[$plan]['prices'][$period])) {
        return null;
    }
    return round($plans[$plan]['prices'][$period] * $qty, 2);
}

function pd_money(float $amount): string
{
    return pd_lang() === 'nl'
        ? '€ ' . number_format($amount, 2, ',', '.')
        : '€' . number_format($amount, 2, '.', ',');
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

/** Country names for the billing select (English names, kept short). */
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
 * Order totals. Prices in plans.json INCLUDE VAT for consumers.
 * Consumer: VAT at the customer's country rate (OSS; under the EUR 10,000 threshold the seller's
 *   home rate may be used instead - see docs/PAYMENTS.md).
 * EU business with a VAT number in another EU country: reverse charge, price excl. home VAT, 0% VAT.
 */
function pd_totals(float $list, string $country, string $vat_number): array
{
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

/** Banks shown on the simulated iDEAL bank choice (names only, no logos). */
function pd_ideal_banks(): array
{
    return ['ABN AMRO', 'ASN Bank', 'bunq', 'ING', 'Knab', 'Rabobank', 'RegioBank', 'Revolut', 'SNS', 'Triodos Bank', 'Van Lanschot'];
}

function pd_plan_name(string $plan, int $qty = 1): string
{
    $plans = pd_plans();
    $name = isset($plans[$plan]) ? pd_ptext($plans[$plan]['name']) : $plan;
    return pd_e($name) . ($qty > 1 ? ' × ' . $qty : '');
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
    <link href="demo.css?v=2" rel="stylesheet">
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
