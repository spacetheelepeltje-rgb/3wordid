<?php
/**
 * Checks reseller-code helpers that do not need the database:
 * normalisation, unknown codes, first-code-wins, payment attribution, ?ref=.
 * Run from the repo root:  php tools/test_resellers.php
 * Not needed on the web server. Exit code 1 when something differs.
 */

require_once __DIR__ . '/../php/resellers.php';

$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}

// --- Normalisation ---
expect('trim and uppercase', reseller_normalize_code('  ab-cd1  '), 'AB-CD1');
expect('inner spaces stripped', reseller_normalize_code('ab cd'), 'ABCD');
expect('already upper', reseller_normalize_code('PARTNER-1'), 'PARTNER-1');
expect('too short', reseller_normalize_code('AB'), '');
expect('two chars after trim', reseller_normalize_code('  ab  '), '');
expect('empty', reseller_normalize_code(''), '');
expect('whitespace only', reseller_normalize_code(" \n "), '');
expect('too long 21', reseller_normalize_code(str_repeat('A', 21)), '');
expect('length 20 ok', reseller_normalize_code(str_repeat('A', 20)), str_repeat('A', 20));
expect('length 3 ok', reseller_normalize_code('ab9'), 'AB9');
expect('reject lowercase punctuation', reseller_normalize_code('AB_CD'), '');
expect('reject symbols', reseller_normalize_code('AB*CD'), '');
expect('digits and hyphen', reseller_normalize_code('r-99'), 'R-99');
expect('payment order ref ignored (24 hex)', reseller_normalize_code(str_repeat('a', 24)), '');
expect('valid format helper', reseller_code_valid_format('ABC'), true);
expect('invalid format helper', reseller_code_valid_format('ab'), false);

// --- Unknown / inactive / empty (stub lookup, no DB) ---
$GLOBALS['_reseller_schema_ready'] = true;
$GLOBALS['_reseller_lookup'] = static function (string $code): ?array {
    if ($code === 'ACTIVE1') {
        return ['id' => 11, 'code' => 'ACTIVE1', 'name' => 'Active One'];
    }
    if ($code === 'ACTIVE2') {
        return ['id' => 22, 'code' => 'ACTIVE2', 'name' => 'Active Two'];
    }
    return null;
};

$empty = reseller_resolve_code('');
expect('empty not unknown', $empty['unknown'], false);
expect('empty no id', $empty['id'], null);

$blank = reseller_resolve_code('   ');
expect('blank not unknown', $blank['unknown'], false);

$badfmt = reseller_resolve_code('!!');
expect('invalid format unknown', $badfmt['unknown'], true);
expect('invalid format no id', $badfmt['id'], null);
expect('invalid format no stored code', $badfmt['code'], '');

$unknown = reseller_resolve_code('no-such');
expect('unknown flag', $unknown['unknown'], true);
expect('unknown keeps normalised code', $unknown['code'], 'NO-SUCH');
expect('unknown no id', $unknown['id'], null);

$inactive_or_missing = reseller_resolve_code('sleeping');
expect('inactive treated unknown', $inactive_or_missing['unknown'], true);
expect('inactive no id', $inactive_or_missing['id'], null);

$ok = reseller_resolve_code(' active1 ');
expect('known active id', $ok['id'], 11);
expect('known not unknown', $ok['unknown'], false);
expect('known normalised', $ok['code'], 'ACTIVE1');

// --- First code wins ---
expect('first wins keeps existing', reseller_first_wins(11, 22), 11);
expect('first wins takes incoming when empty', reseller_first_wins(null, 22), 22);
expect('first wins treats 0 as empty', reseller_first_wins(0, 22), 22);
expect('first wins both empty', reseller_first_wins(null, null), null);
expect('unknown incoming does not clear existing', reseller_first_wins(11, null), 11);

// --- Payment attribution (same first-wins rule) ---
expect('pay uses user reseller', reseller_payment_attribution(11, 22), 11);
expect('pay uses entered when user empty', reseller_payment_attribution(null, 22), 22);
expect('pay none when both empty', reseller_payment_attribution(null, null), null);
expect('pay ignores unknown entered if user set', reseller_payment_attribution(11, null), 11);
expect('pay ignores unknown entered if user empty', reseller_payment_attribution(null, null), null);

// apply_to_user with no DB: lookup works, set-user no-ops (schema stub has no conn)
$applied = reseller_apply_to_user(0, 'ACTIVE1');
expect('apply without user keeps resolved id', $applied['id'], 11);
expect('apply without user not written', $applied['applied'], false);

$applied_unknown = reseller_apply_to_user(0, 'GHOST');
expect('apply unknown ignored', $applied_unknown['id'], null);
expect('apply unknown flag', $applied_unknown['unknown'], true);
expect('apply unknown not written', $applied_unknown['applied'], false);

// --- ?ref= capture (session only; cookies are skipped in CLI) ---
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_GET = ['ref' => '  active1 '];
$_SESSION = [];
$_COOKIE = [];
reseller_capture_ref();
expect('ref stored in session', $_SESSION[RESELLER_SESSION_KEY] ?? '', 'ACTIVE1');
expect('remembered from session', reseller_remembered_code(), 'ACTIVE1');

$_GET = ['ref' => str_repeat('ab', 12)]; // 24-char payment-style ref
$_SESSION = [];
reseller_capture_ref();
expect('payment ref not captured', $_SESSION[RESELLER_SESSION_KEY] ?? '', '');

$_GET = ['ref' => 'nope!'];
$_SESSION = [];
reseller_capture_ref();
expect('invalid ref not captured', isset($_SESSION[RESELLER_SESSION_KEY]), false);

$_GET = [];
$_POST = ['reseller_code' => 'active2'];
$_SESSION = [RESELLER_SESSION_KEY => 'ACTIVE1'];
expect('posted code beats session', reseller_remembered_code(), 'ACTIVE2');

$_POST = [];
$_SESSION = [];
$_COOKIE = [RESELLER_COOKIE => 'active1'];
expect('cookie used when session empty', reseller_remembered_code(), 'ACTIVE1');

$_GET = ['ref' => 'xx']; // too short
$_SESSION = [];
$_COOKIE = [];
reseller_capture_ref();
expect('short ref ignored', isset($_SESSION[RESELLER_SESSION_KEY]), false);

if ($fail === 0) {
    echo "OK test_resellers.php\n";
    exit(0);
}
echo "$fail test(s) failed\n";
exit(1);
