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

echo count($countries) . " countries, $n conversions checked, $fail problem(s)\n";
exit($fail ? 1 : 0);
