<?php
/**
 * Checks the Bumperchat claim-flow helpers that do not need the database:
 * PHP plate-to-words (same rules as js/bumperchat.js), remember-me cookie
 * format, and the login return-URL allow-list.
 *
 * Run from the repo root:  php tools/test_bumperchat_flow.php
 * Not needed on the web server. Exit code 1 when something differs.
 */

// bumperchat.php calls current_lang() / t() in some functions; stubs are enough
// for the helpers under test (plate conversion, state, safe next).
if (!function_exists('current_lang')) {
    function current_lang(): string { return 'en'; }
}
if (!function_exists('t')) {
    function t(string $key, array $vars = []): string { return $key; }
}

require_once __DIR__ . '/../php/bumperchat.php';
require_once __DIR__ . '/../php/remember.php';

$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}

$data = json_decode(file_get_contents(__DIR__ . '/../bumperchat/plate_formats.json'), true);
$countries = $data['countries'] ?? [];
$by = [];
foreach ($countries as $c) {
    $by[$c['code']] = $c;
}

$n = 0;
foreach ($countries as $c) {
    foreach ($c['examples'] as $ex) {
        $n++;
        $region = '';
        $plate = $ex['plate'];
        if (strpos($plate, '|') !== false) {
            [$region, $plate] = explode('|', $plate, 2);
        }
        $got = bumperchat_plate_to_words($c, $plate, $region);
        expect($c['code'] . ' "' . $ex['plate'] . '"', $got, $ex['words']);
    }
}

$extra = [
    ['NL', 'ab123c', 'ab 123 c'],
    ['NL', 'AB 123 C', 'ab 123 c'],
    ['FR', 'AB123CD', 'ab 123 cd'],
    ['DK', 'AB12345', 'ab 12 345'],
    ['ES', '1234BCD', 'e 1234 bcd'],
    ['BE', '1ABC234', '1 abc 234'],
    ['BE', '123-ABC', 'b 123 abc'],
    ['HU', 'AABC123', 'h aabc 123'],
    ['SE', 'ABC 12A', 's abc 12a'],
    ['LU', '1234', 'l 12 34'],
    ['CZ', '1A23456', ''],
    ['NL', 'ABCD12', ''],
];
foreach ($extra as [$code, $plate, $want]) {
    $n++;
    $got = bumperchat_plate_to_words($by[$code], $plate, '');
    expect("$code \"$plate\"", $got, $want);
}

$n++;
expect('US invalid region', bumperchat_plate_to_words($by['US'], 'ABC123', 'XX'), '');
$n++;
expect('CA QC', bumperchat_plate_to_words($by['CA'], 'ABC 123', 'QC'), 'qc abc 123');
$n++;
expect('US TX', bumperchat_plate_to_words($by['US'], 'ABC-1234', 'TX'), 'tx abc 1234');

expect('country NL', (bumperchat_country('nl')['code'] ?? ''), 'NL');
expect('country missing', bumperchat_country('XX'), null);
expect('normalize words', bumperchat_normalize_words('  AB  123  C '), 'ab 123 c');

// --- remember-me cookie format (no database) ---
$token = remember_new_token();
expect('selector hex 32', (bool) preg_match('/^[0-9a-f]{32}$/', $token['selector']), true);
expect('validator hex 64', (bool) preg_match('/^[0-9a-f]{64}$/', $token['validator']), true);
$val = remember_cookie_value($token['selector'], $token['validator']);
$parsed = remember_parse_cookie($val);
expect('parse selector', $parsed['selector'] ?? '', $token['selector']);
expect('parse validator', $parsed['validator'] ?? '', $token['validator']);
expect('parse empty', remember_parse_cookie(''), null);
expect('parse junk', remember_parse_cookie('not-a-token'), null);
expect('parse short', remember_parse_cookie('abc:def'), null);
$hash = remember_validator_hash($token['validator']);
expect('hash length', strlen($hash), 64);
expect('hash match', hash_equals($hash, remember_validator_hash($token['validator'])), true);
expect('hash differs', hash_equals($hash, remember_validator_hash($token['selector'] . $token['validator'])), false);
expect('lifetime 30 days', remember_lifetime(), 60 * 60 * 24 * 30);
expect('cookie name', remember_cookie_name(), '3WIDREM');

$opts = remember_cookie_options(time() + 10);
expect('httponly', $opts['httponly'], true);
expect('samesite', $opts['samesite'], 'Lax');
expect('path', $opts['path'], '/');

// --- login return URL allow-list ---
expect('next claim', bumperchat_safe_next('claim.php'), 'claim.php');
expect('next plate qs', bumperchat_safe_next('claim_plate.php?country=NL'), 'claim_plate.php?country=NL');
expect('next reject abs', bumperchat_safe_next('https://evil.example/'), 'chat.php');
expect('next reject path', bumperchat_safe_next('../login/email_login.php'), 'chat.php');
expect('next reject scheme', bumperchat_safe_next('//evil.example/'), 'chat.php');
expect('next empty fallback', bumperchat_safe_next('', 'index.php'), 'index.php');
expect('next login', bumperchat_safe_next('login.php'), 'login.php');

// --- plate auto-format (separators added, junk stripped, already-spaced input accepted) ---
expect('format NL compact', bumperchat_format_plate($by['NL'], 'ab123c'), 'AB-123-C');
expect('format NL runs', bumperchat_format_plate($by['NL'], '12abc3'), '12-ABC-3');
expect('format NL spaced', bumperchat_format_plate($by['NL'], 'ab 123 c'), 'AB-123-C');
expect('format NL junk', bumperchat_format_plate($by['NL'], 'AB--123--C!'), 'AB-123-C');
expect('format FR', bumperchat_format_plate($by['FR'], 'AB123CD'), 'AB-123-CD');
expect('format DK fixed', bumperchat_format_plate($by['DK'], 'AB12345'), 'AB 12 345');
expect('format ES', bumperchat_format_plate($by['ES'], '1234bcd'), '1234 BCD');
expect('format empty', bumperchat_format_plate($by['NL'], '---'), '');
expect('example NL', bumperchat_plate_example($by['NL']), 'AB-123-C');
expect('style NL', bumperchat_plate_style('NL'), 'nl');
expect('style FR', bumperchat_plate_style('FR'), 'eu');
expect('style US', bumperchat_plate_style('US'), 'na');

// --- prefill from 3WordID words ---
$p = bumperchat_prefill_from_words('e 1234 bcd');
expect('prefill ES country', $p['country'], 'ES');
expect('prefill ES plate', $p['plate'], '1234 BCD');
$p = bumperchat_prefill_from_words('tx abc 1234');
expect('prefill TX country', $p['country'], 'US');
expect('prefill TX region', $p['region'], 'TX');
expect('prefill TX plate', $p['plate'], 'ABC 1234');
$p = bumperchat_prefill_from_words('qc abc 123');
expect('prefill QC country', $p['country'], 'CA');
expect('prefill QC region', $p['region'], 'QC');
$p = bumperchat_prefill_from_words('cdn qc 123abc');
expect('prefill CDN country', $p['country'], 'CA');
expect('prefill CDN region', $p['region'], 'QC');
$p = bumperchat_prefill_from_words('ab 123 c');
expect('prefill NL-like no prefix country', $p['country'], '');
expect('prefill NL-like plate', $p['plate'], 'AB 123 C');
expect('prefill empty', bumperchat_prefill_from_words('not three')['words'], '');

// Prefix-mode rules are loose, so a plate often matches several countries; only a unique hit is used.
expect('infer many not unique', bumperchat_infer_country('1234 BCD'), '');
expect('infer ES among hits', in_array('ES', bumperchat_infer_countries('1234 BCD'), true), true);
expect('infer empty', bumperchat_infer_country(''), '');

// --- default country: headers, Accept-Language, fallback (no IP lookup in tests) ---
expect('default CF-IPCountry', bumperchat_default_country(['HTTP_CF_IPCOUNTRY' => 'FR'], '', false), 'FR');
expect('default GEOIP header', bumperchat_default_country(['GEOIP_COUNTRY_CODE' => 'DE'], '', false), 'DE');
expect('default MM header', bumperchat_default_country(['MM_COUNTRY_CODE' => 'IT'], '', false), 'IT');
expect('default CF unknown falls through', bumperchat_default_country(['HTTP_CF_IPCOUNTRY' => 'XX'], 'de-DE', false), 'DE');
expect('default GB not in list', bumperchat_default_country(['HTTP_CF_IPCOUNTRY' => 'GB'], '', false), 'NL');
expect('default accept nl-BE uses region', bumperchat_default_country([], 'nl-BE,en;q=0.8', false), 'BE');
expect('default accept de', bumperchat_default_country([], 'de,en;q=0.5', false), 'DE');
expect('default empty is NL', bumperchat_default_country([], '', false), 'NL');
expect('header ignored if not in plate list then accept', bumperchat_default_country(['HTTP_CF_IPCOUNTRY' => 'NO'], 'es-ES', false), 'ES');


echo count($countries) . " countries, $n conversions checked, $fail problem(s)\n";
exit($fail ? 1 : 0);
