<?php
/**
 * Simple translations for 3WordID.
 *
 * - Texts live in lang/<code>.php (each file returns key => text). English (lang/en.php) is
 *   the source and the fallback.
 * - Language choice, in this order: ?lang=xx (only the codes in i18n_languages()), the choice
 *   stored earlier in the session, the browser's Accept-Language header, then English.
 *   There is no IP lookup. The choice is kept in the existing session only: no extra cookie.
 * - Loaded from php/functions.php, so every page that includes it has these functions.
 *
 * Usage in a page:
 *   <html lang="<?php echo current_lang(); ?>">
 *   <?php echo t('home.lookup'); ?>                  plain text, HTML-escaped
 *   <?php echo t_raw('home.step1_text'); ?>          text with intended markup (links, <b>, ...)
 *   <?php echo t('home.views_other', ['n' => 5]); ?> {n} in the text is replaced (and escaped)
 *   <?php echo t_n('home.views', 1); ?>              singular/plural: home.views_one / home.views_other
 *   <?php echo lang_switcher(); ?>                   small "EN | NL | FR | ES" switch
 *   <?php echo lang_head_tags(); ?>                  canonical + hreflang links for <head>
 */

/** Allowed language codes. To add a language, add its code here and create lang/<code>.php. */
function i18n_languages(): array
{
    return ['en', 'nl', 'fr', 'es'];
}

/** Open Graph locale (og:locale) for a language code. */
function i18n_og_locale(string $code): string
{
    $map = ['en' => 'en_US', 'nl' => 'nl_NL', 'fr' => 'fr_FR', 'es' => 'es_ES'];
    return $map[$code] ?? 'en_US';
}

/** Label shown in the language switch. */
function i18n_language_label(string $code): string
{
    return strtoupper($code);
}

/** Best language from the Accept-Language header, or null. */
function i18n_from_accept_language(?string $header): ?string
{
    if ($header === null || $header === '') {
        return null;
    }
    $allowed = i18n_languages();
    $best = null;
    $bestQ = 0.0;
    foreach (explode(',', $header) as $part) {
        $bits = explode(';', trim($part));
        $tag = strtolower(trim($bits[0]));
        if ($tag === '' || $tag === '*') {
            continue;
        }
        $q = 1.0;
        foreach (array_slice($bits, 1) as $param) {
            $param = trim($param);
            if (stripos($param, 'q=') === 0) {
                $q = (float)substr($param, 2);
            }
        }
        $primary = explode('-', $tag)[0];
        if ($q > $bestQ && in_array($primary, $allowed, true)) {
            $best = $primary;
            $bestQ = $q;
        }
    }
    return $best;
}

/** The language for this request: 'en', 'nl', 'fr', 'es'. */
function current_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $allowed = i18n_languages();

    if (isset($_GET['lang']) && is_string($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        $lang = $_GET['lang'];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['lang'] = $lang;
        }
        return $lang;
    }
    if (isset($_SESSION['lang']) && is_string($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed, true)) {
        return $lang = $_SESSION['lang'];
    }
    $fromBrowser = i18n_from_accept_language($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null);
    return $lang = ($fromBrowser ?? 'en');
}

/** Loads lang/<code>.php (cached). Returns [] if the file is missing or broken. */
function i18n_load(string $code): array
{
    static $cache = [];
    if (!isset($cache[$code])) {
        $cache[$code] = [];
        if (in_array($code, i18n_languages(), true)) {
            $file = __DIR__ . '/../lang/' . $code . '.php';
            if (is_file($file)) {
                $texts = include $file;
                if (is_array($texts)) {
                    $cache[$code] = $texts;
                }
            }
        }
    }
    return $cache[$code];
}

/** Raw (unescaped) text for a key: current language, then English, then the key itself. */
function i18n_text(string $key): string
{
    $texts = i18n_load(current_lang());
    if (isset($texts[$key])) {
        return $texts[$key];
    }
    $fallback = i18n_load('en');
    if (isset($fallback[$key])) {
        return $fallback[$key];
    }
    return $key;
}

/** Plain text with {placeholders} replaced. Not escaped: for JSON or plain-text output only. */
function t_plain(string $key, array $vars = []): string
{
    $text = i18n_text($key);
    if ($vars) {
        $map = [];
        foreach ($vars as $name => $value) {
            $map['{' . $name . '}'] = (string)$value;
        }
        $text = strtr($text, $map);
    }
    return $text;
}

/** Text, HTML-escaped (safe in body text and in attributes). */
function t(string $key, array $vars = []): string
{
    return htmlspecialchars(t_plain($key, $vars), ENT_QUOTES, 'UTF-8');
}

/**
 * Text with a singular/plural choice: uses '<key>_one' when $n is 1, otherwise '<key>_other'.
 * {n} in the text is replaced by $n. Example: t_n('home.views', 5)
 */
function t_n(string $key, int $n, array $vars = []): string
{
    $vars['n'] = $n;
    return t($key . ($n === 1 ? '_one' : '_other'), $vars);
}

/** Text that contains intended markup (links, <b>, <br>). The text is not escaped; the {placeholders} values are. */
function t_raw(string $key, array $vars = []): string
{
    $text = i18n_text($key);
    if ($vars) {
        $map = [];
        foreach ($vars as $name => $value) {
            $map['{' . $name . '}'] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
        $text = strtr($text, $map);
    }
    return $text;
}

/** Path + query of the current request, without the lang parameter. [path, query-params] */
function i18n_current_parts(): array
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }
    $params = [];
    parse_str((string)parse_url($uri, PHP_URL_QUERY), $params);
    unset($params['lang']);
    return [$path, $params];
}

/** Relative URL of the current page in the given language. */
function lang_url(string $code): string
{
    [$path, $params] = i18n_current_parts();
    $params['lang'] = $code;
    return $path . '?' . http_build_query($params);
}

/** Same as lang_url() but with scheme and host, for canonical and hreflang links. */
function lang_url_absolute(?string $code = null): string
{
    $base = 'https://3wordid.com';
    if ($code === null) {
        [$path, $params] = i18n_current_parts();
        $url = $path . ($params ? '?' . http_build_query($params) : '');
    } else {
        $url = lang_url($code);
    }
    return $base . preg_replace('#/index\.php(?=$|\?)#', '/', $url);
}

/** <link rel="canonical"> and hreflang alternates (one per language, plus x-default). */
function lang_head_tags(): string
{
    $out = '<link rel="canonical" href="' . htmlspecialchars(lang_url_absolute(current_lang()), ENT_QUOTES, 'UTF-8') . '">' . "\n";
    foreach (i18n_languages() as $code) {
        $out .= '    <link rel="alternate" hreflang="' . $code . '" href="' . htmlspecialchars(lang_url_absolute($code), ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    $out .= '    <link rel="alternate" hreflang="x-default" href="' . htmlspecialchars(lang_url_absolute(null), ENT_QUOTES, 'UTF-8') . '">' . "\n";
    return $out;
}

/** Small "EN | NL | FR | ES" switch. Includes its own tiny style so it works on every page. */
function lang_switcher(): string
{
    $current = current_lang();
    $out = '<style>.lang-switch{display:inline-flex;flex-wrap:wrap;align-items:center;gap:6px;white-space:nowrap;font-size:13px;font-weight:600;margin:0 8px;color:#8a93a6}'
        . '.lang-switch a{color:#5b6478;text-decoration:none;padding:2px 4px;border-radius:4px}'
        . '.lang-switch a:hover{color:#0f1c3f}.lang-switch a[aria-current="true"]{color:#0f1c3f;text-decoration:underline}</style>';
    $out .= '<span class="lang-switch" role="group" aria-label="' . t('lang.switch_label') . '">';
    $first = true;
    foreach (i18n_languages() as $code) {
        if (!$first) {
            $out .= '<span aria-hidden="true">|</span>';
        }
        $first = false;
        $out .= '<a href="' . htmlspecialchars(lang_url($code), ENT_QUOTES, 'UTF-8') . '" hreflang="' . $code . '" lang="' . $code . '"'
            . ($code === $current ? ' aria-current="true"' : '') . '>' . htmlspecialchars(i18n_language_label($code), ENT_QUOTES, 'UTF-8') . '</a>';
    }
    return $out . '</span>';
}

/**
 * <script> that gives the page's JavaScript its texts as window.T3 (use T3.name).
 * $map: js name => text key. Example: i18n_js(['exists' => 'home.js_exists'])
 */
function i18n_js_object(array $map): string
{
    $texts = [];
    foreach ($map as $name => $key) {
        $texts[$name] = t_plain($key);
    }
    return json_encode($texts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

function i18n_js(array $map): string
{
    return '<script>window.T3 = ' . i18n_js_object($map) . ';</script>';
}

/**
 * <script> with window.T3 holding every text whose key starts with "js." (the strings used by the
 * JavaScript files in js/), under the name without the prefix: key "js.bad_url" becomes T3.bad_url.
 */
function i18n_js_all(string $prefix = 'js.'): string
{
    $map = [];
    foreach (array_keys(i18n_load('en')) as $key) {
        if (strpos($key, $prefix) === 0) {
            $map[substr($key, strlen($prefix))] = $key;
        }
    }
    return i18n_js($map);
}

// Pages differ per language, so caches must not mix them up.
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Vary: Accept-Language', false);
}
