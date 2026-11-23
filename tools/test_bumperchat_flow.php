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
    ['CZ', '1A23456', 'cz 1a2 3456'],
    ['NL', 'ABCD12', 'ab cd 12'], // official sidecode 5 XX-XX-99
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

// Prefix chip + typed-prefix stripping (same cases as tools/test_bumperchat_plates.js)
$prefixCases = [
    ['FI', 'ABC-123', '', 'fin abc 123', 'ABC-123', 'fin'],
    ['FI', 'fin abc-123', '', 'fin abc 123', 'ABC-123', 'fin'],
    ['FI', 'FIN ABC-123', '', 'fin abc 123', 'ABC-123', 'fin'],
    ['FI', 'FIN-123', '', 'fin fin 123', 'FIN-123', 'fin'],
    ['NL', 'AB-123-C', '', 'ab 123 c', 'AB-123-C', ''],
    ['NL', 'NL AB-123-C', '', 'nlab 123 c', 'NL-AB-123-C', ''],
    ['CA', 'ABCD 123', 'ON', 'on abcd 123', 'ABCD 123', 'on'],
    ['CA', 'ON ABCD 123', 'ON', 'on abcd 123', 'ABCD 123', 'on'],
    ['US', 'ABC-1234', 'TX', 'tx abc 1234', 'ABC 1234', 'tx'],
    ['US', 'TX ABC-1234', 'TX', 'tx abc 1234', 'ABC 1234', 'tx'],
    ['US', '7ABC123', 'CA', 'us ca 7abc123', '7ABC123', 'us'],
    ['BE', '123-ABC', '', 'b 123 abc', '123-ABC', 'b'],
    ['BE', '1-ABC-234', '', '1 abc 234', '1-ABC-234', ''],
];
foreach ($prefixCases as [$code, $plate, $region, $words, $formatted, $chip]) {
    expect("$code words \"$plate\"", bumperchat_plate_to_words($by[$code], $plate, $region), $words);
    expect("$code format \"$plate\"", bumperchat_format_plate($by[$code], $plate, $region), $formatted);
    expect("$code prefix \"$plate\"", bumperchat_plate_prefix($by[$code], $plate, $region), $chip);
}
expect('FI empty prefix', bumperchat_plate_prefix($by['FI'], '', ''), 'fin');
expect('NL empty prefix', bumperchat_plate_prefix($by['NL'], '', ''), '');
expect('BE empty prefix', bumperchat_plate_prefix($by['BE'], '', ''), '');
expect('FI label', bumperchat_plate_prefix_label($by['FI'], '', ''), 'FIN-');
expect('EE label space', bumperchat_plate_prefix_label($by['EE'], '123 ABC', ''), 'EST ');
expect('US TX label', bumperchat_plate_prefix_label($by['US'], 'ABC-1234', 'TX'), 'TX-');
expect('US CA 1-group label', bumperchat_plate_prefix_label($by['US'], '7ABC123', 'CA'), 'US-');
expect('CA ON label', bumperchat_plate_prefix_label($by['CA'], 'ABCD 123', 'ON'), 'ON-');
expect('NL no label', bumperchat_plate_prefix_label($by['NL'], 'AB-123-C', ''), '');
expect('BE 2-group label', bumperchat_plate_prefix_label($by['BE'], '123-ABC', ''), 'B-');
expect('BE 3-group no label', bumperchat_plate_prefix_label($by['BE'], '1-ABC-234', ''), '');

$pre = bumperchat_prefill_from_words('fin abc 123');
expect('prefill FI country', $pre['country'], 'FI');
expect('prefill FI plate only', $pre['plate'], 'ABC-123');
expect('prefill FI words', $pre['words'], 'fin abc 123');

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
expect('next dispute', bumperchat_safe_next('dispute.php'), 'dispute.php');
expect('next dispute qs', bumperchat_safe_next('dispute.php?words=ab%20123%20c'), 'dispute.php?words=ab%20123%20c');
expect('next claim words', bumperchat_safe_next('claim.php?words=ab%20123%20c'), 'claim.php?words=ab%20123%20c');
expect('contact email', SITE_CONTACT_EMAIL, 'frits@rincker.nl');

$actions = bumperchat_taken_actions('ab 123 c', ['logged_in' => false]);
expect('actions send href', strpos($actions, '../3wid_forward.php?from=bumperchat&amp;threewords=ab%20123%20c') !== false, true);
expect('actions login href', strpos($actions, 'login.php?next=' . rawurlencode('claim.php?words=' . rawurlencode('ab 123 c'))) !== false, true);
expect('actions retry', strpos($actions, 'claim.php?new=1') !== false, true);
expect('actions dispute', strpos($actions, 'dispute.php?words=ab%20123%20c') !== false, true);
$actions_in = bumperchat_taken_actions('ab 123 c', ['logged_in' => true]);
expect('logged-in hides login', strpos($actions_in, 'login.php?next=') === false, true);
expect('empty words no actions', bumperchat_taken_actions('not-three-words'), '');
expect('dispute link empty', bumperchat_dispute_link(''), '');
expect('dispute link href', strpos(bumperchat_dispute_link('ab 123 c'), 'dispute.php?words=ab%20123%20c') !== false, true);


// --- plate auto-format (separators added, junk stripped, already-spaced input accepted) ---
expect('format NL compact', bumperchat_format_plate($by['NL'], 'ab123c'), 'AB-123-C');
expect('format NL runs', bumperchat_format_plate($by['NL'], '12abc3'), '12-ABC-3');
expect('format NL spaced', bumperchat_format_plate($by['NL'], 'ab 123 c'), 'AB-123-C');
expect('format NL junk', bumperchat_format_plate($by['NL'], 'AB--123--C!'), 'AB-123-C');
expect('format FR', bumperchat_format_plate($by['FR'], 'AB123CD'), 'AB-123-CD');
expect('format DK fixed', bumperchat_format_plate($by['DK'], 'AB12345'), 'AB 12 345');
expect('format ES', bumperchat_format_plate($by['ES'], '1234bcd'), '1234 BCD');
expect('format empty', bumperchat_format_plate($by['NL'], '---'), '');
expect('format keeps three mixed groups', bumperchat_format_plate($by['NL'], '2562AE 187 0'), '2562AE-187-0');
expect('format keeps three mixed dashed', bumperchat_format_plate($by['NL'], '2562AE-187-0'), '2562AE-187-0');
expect('format still splits compact NL', bumperchat_format_plate($by['NL'], 'AB123C'), 'AB-123-C');
expect('format still splits two groups', bumperchat_format_plate($by['NL'], '92ZD NG'), '92-ZD-NG');
expect('words from three mixed groups', bumperchat_plate_to_words($by['NL'], '2562AE 187 0'), '2562ae 187 0');
expect('words after format mixed groups', bumperchat_plate_to_words($by['NL'], bumperchat_format_plate($by['NL'], '2562AE 187 0')), '2562ae 187 0');

// Official mixed-group formats (and mid-typing). Region plates stay as printed.
$officialCases = [
    ['CZ', '1A2 3456', '', '1A2 3456', 'cz 1a2 3456'],
    ['CZ', '1A23456', '', '1A2 3456', 'cz 1a2 3456'],
    ['CZ', '1AA 0001', '', '1AA 0001', 'cz 1aa 0001'],
    ['CZ', '1AA0001', '', '1AA 0001', 'cz 1aa 0001'],
    ['CZ', '1A2', '', '1A2', ''],
    ['CZ', '1A23', '', '1A2 3', ''],
    ['CZ', '1A2345', '', '1A2 345', ''],
    ['SE', 'ABC 123', '', 'ABC 123', 's abc 123'],
    ['SE', 'ABC123', '', 'ABC 123', 's abc 123'],
    ['SE', 'ABC 12A', '', 'ABC 12A', 's abc 12a'],
    ['SE', 'ABC12A', '', 'ABC 12A', 's abc 12a'],
    ['SE', 'ABC12', '', 'ABC 12', ''],
    ['PL', 'WA 12345', '', 'WA 12345', 'pl wa 12345'],
    ['PL', 'WA12345', '', 'WA 12345', 'pl wa 12345'],
    ['PL', 'KR 1234A', '', 'KR 1234A', 'pl kr 1234a'],
    ['PL', 'KR1234A', '', 'KR 1234A', 'pl kr 1234a'],
    ['PL', 'KRA1234A', '', 'KRA 1234A', 'pl kra 1234a'],
    ['PL', 'KR12', '', 'KR 12', ''],
    ['DE', 'M AB 1234', '', 'M AB 1234', 'm ab 1234'],
    ['DE', 'B XY 123E', '', 'B XY 123E', 'b xy 123e'],
    ['DE', 'B XY 123', '', 'B XY 123', 'b xy 123'],
    ['DE', 'BXY123E', '', 'BXY123E', ''],
    ['DE', 'MAB1234', '', 'MAB1234', ''],
    ['US', '7ABC123', 'CA', '7ABC123', 'us ca 7abc123'],
    ['US', 'ABC-1234', 'TX', 'ABC 1234', 'tx abc 1234'],
    ['SK', 'AB 123 CD', '', 'AB 123 CD', 'ab 123 cd'],
    ['SK', 'BA123AB', '', 'BA 123 AB', 'ba 123 ab'],
    ['SK', 'BA-123AB', '', 'BA 123 AB', 'ba 123 ab'],
];
foreach ($officialCases as [$code, $plate, $region, $formatted, $words]) {
    expect("$code official format \"$plate\"", bumperchat_format_plate($by[$code], $plate, $region), $formatted);
    expect("$code official words \"$plate\"", bumperchat_plate_to_words($by[$code], bumperchat_format_plate($by[$code], $plate, $region), $region), $words);
    if ($words !== '') {
        expect("$code official direct \"$plate\"", bumperchat_plate_to_words($by[$code], $plate, $region), $words);
    }
}
expect('no-country keeps three typed groups', bumperchat_format_plate([], '2562AE 187 0'), '2562AE 187 0');
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

// Official Dutch sidecodes (including 99-XXX-9 / 36-JXN-1) infer as NL even when
// the default country is a region-mode country that does not accept three groups.
expect('format NL sidecode 99-XXX-9 spaced', bumperchat_format_plate($by['NL'], '36 jxn 1'), '36-JXN-1');
expect('format NL sidecode 99-XXX-9 compact', bumperchat_format_plate($by['NL'], '36JXN1'), '36-JXN-1');
expect('words NL 36 jxn 1', bumperchat_plate_to_words($by['NL'], '36 jxn 1'), '36 jxn 1');
expect('words US no region for Dutch plate', bumperchat_plate_to_words($by['US'], '36 jxn 1', ''), '');
expect('infer 36-JXN-1 is NL', bumperchat_infer_country('36-JXN-1'), 'NL');
expect('infer 36 jxn 1 is NL', bumperchat_infer_country('36 jxn 1'), 'NL');
expect('infer compact 36JXN1 is NL', bumperchat_infer_country('36JXN1'), 'NL');
expect('pick US default -> NL for 36 jxn 1', bumperchat_pick_country_for_plate('36 jxn 1', '', 'US'), 'NL');
expect('pick NL default stays NL', bumperchat_pick_country_for_plate('36 jxn 1', '', 'NL'), 'NL');
expect('default en-US is US (no geo/ip)', bumperchat_default_country([], 'en-US', false), 'US');
expect('three mixed groups still kept on NL', bumperchat_format_plate($by['NL'], '2562AE 187 0'), '2562AE-187-0');

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
