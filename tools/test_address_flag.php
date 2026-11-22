<?php
/**
 * Checks the "this is an address" flag: icon precedence, SQL migration, form
 * and homepage wiring. No database. Run from the repo root:
 *   php tools/test_address_flag.php
 * Not needed on the web server. Exit code 1 when something differs.
 */

$root = dirname(__DIR__);
$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}

function expect_contains($label, $haystack, $needle) {
    global $fail;
    if (strpos($haystack, $needle) === false) {
        echo "FAIL $label: missing " . var_export($needle, true) . "\n";
        $fail++;
    }
}

function expect_absent($label, $haystack, $needle) {
    global $fail;
    if (strpos($haystack, $needle) !== false) {
        echo "FAIL $label: unexpectedly found " . var_export($needle, true) . "\n";
        $fail++;
    }
}

$src = file_get_contents($root . '/php/functions.php');
if (!preg_match('/function wid_search_lock_kind\(bool \$exists, bool \$plate, bool \$address\): string\s*\{.*?\n\}/s', $src, $m)) {
    echo "FAIL wid_search_lock_kind not found in php/functions.php\n";
    exit(1);
}
eval($m[0]);

expect('missing stays lock', wid_search_lock_kind(false, false, false), 'lock');
expect('address without existing id is lock', wid_search_lock_kind(false, false, true), 'lock');
expect('existing no flags is lock', wid_search_lock_kind(true, false, false), 'lock');
expect('existing address is home', wid_search_lock_kind(true, false, true), 'home');
expect('existing plate is car', wid_search_lock_kind(true, true, false), 'car');
expect('missing plate is still car', wid_search_lock_kind(false, true, false), 'car');
expect('plate wins over address', wid_search_lock_kind(true, true, true), 'car');
expect('missing plate wins over address', wid_search_lock_kind(false, true, true), 'car');

$sql = file_get_contents($root . '/sql/2026-11-22_address_flag.sql');
expect_contains('sql 3wordid column', $sql, 'ALTER TABLE 3wordid');
expect_contains('sql deleted column', $sql, 'ALTER TABLE 3wordid_deleted');
expect_contains('sql type', $sql, 'is_address TINYINT(1) NOT NULL DEFAULT 0');

$add = file_get_contents($root . '/3wid_add.php');
expect_contains('add form detects column', $add, 'db_3wordid_has_is_address()');
expect_contains('add form checkbox', $add, 'name="is_address"');
expect_contains('add form label', $add, "t('add.is_address')");

$edit = file_get_contents($root . '/3wid_3wid.php');
expect_contains('edit form detects column', $edit, 'db_3wordid_has_is_address()');
expect_contains('edit form checkbox', $edit, 'name="is_address"');

$insert = file_get_contents($root . '/3wid_insert.php');
expect_contains('insert saves flag', $insert, 'db_3wordid_set_is_address');
$update = file_get_contents($root . '/3wid_update.php');
expect_contains('update saves flag', $update, 'db_3wordid_set_is_address');

$exists = file_get_contents($root . '/3wid_exists.php');
expect_contains('exists json address', $exists, "'address'");

$index = file_get_contents($root . '/index.php');
expect_contains('homepage home icon', $index, 'id="homeIcon"');
expect_contains('homepage address i18n', $index, 'home.js_address');

$list = file_get_contents($root . '/3wid_list.php');
$chat = file_get_contents($root . '/3wid_chat.php');
expect_absent('no house icon in owner list', $list, 'homeIcon');
expect_absent('no is-address class in owner list', $list, 'is-address');
expect_absent('no house icon in chat', $chat, 'homeIcon');
expect_absent('no is-address class in chat', $chat, 'is-address');

$bc = file_get_contents($root . '/bumperchat/index.php');
expect_absent('no house icon on bumperchat search', $bc, 'homeIcon');

echo $fail ? "$fail problem(s)\n" : "OK\n";
exit($fail ? 1 : 0);
