<?php
/**
 * Open / closed chat rule (chat_access_error) with Bumperchat plate accounts. No database needed:
 * get_db_conn() is stubbed so car_chat_ready() is true (as on the live site).
 * Run from the repo root:  php tools/test_chat_access.php
 * Not needed on the web server. Exit code 1 when something differs.
 *
 * Root cause covered here: the 3wordid.chat_open column defaults to 0 (closed) and
 * db_3wordid_insert() did not set it, so a freshly claimed plate was "closed": it could not
 * message other accounts (chat.err_closed_out) and other accounts could not message it
 * (chat.err_closed_in). bumperchat_claim_plate() now opens it (db_chat_open_set).
 */

class StubConn {
    public $connect_error = null;
    public function query($sql) { return true; }
    public function close() {}
}
function get_db_conn() { return new StubConn(); }

require_once __DIR__ . '/../php/car_chat.php';

$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}

// Rows as the database returns them (db_3wordid_get): MySQL gives strings.
$plate_default = ['id' => '10', 'user_id' => '1', 'threeword' => 'ab 123 c', 'emailform' => '1', 'chat_open' => '0', 'claim_source' => 'bumperchat'];
$plate_open    = ['id' => '11', 'user_id' => '2', 'threeword' => 'de 456 f', 'emailform' => '1', 'chat_open' => '1', 'claim_source' => 'bumperchat'];
$plate_open_b  = ['id' => '12', 'user_id' => '3', 'threeword' => 'gh 789 i', 'emailform' => '1', 'chat_open' => '1', 'claim_source' => 'bumperchat'];
$org_closed    = ['id' => '20', 'user_id' => '4', 'threeword' => 'blue red fox', 'emailform' => '1', 'chat_open' => '0'];
$org_closed_2  = ['id' => '21', 'user_id' => '4', 'threeword' => 'blue red owl', 'emailform' => '1', 'chat_open' => '0'];
$org_open      = ['id' => '22', 'user_id' => '5', 'threeword' => 'open red fox', 'emailform' => '1', 'chat_open' => '1'];
$no_chat       = ['id' => '23', 'user_id' => '6', 'threeword' => 'plain link one', 'emailform' => '0', 'chat_open' => '0'];

// --- reproduction of the bug: a plate with the column default (closed) is blocked both ways ---
expect('bug: default plate cannot send to another account', chat_access_error($plate_default, $plate_open), 'chat.err_closed_out');
expect('bug: nobody can message a default plate', chat_access_error($plate_open, $plate_default), 'chat.err_closed_in');
expect('closed rule on row', chat_row_is_closed($plate_default), true);

// --- after the fix: claim opens the plate ---
$fixed = $plate_default; $fixed['chat_open'] = '1';
expect('fixed plate sends to a plate', chat_access_error($fixed, $plate_open), '');
expect('fixed plate receives from a plate', chat_access_error($plate_open, $fixed), '');
expect('plate to plate (both open)', chat_access_error($plate_open, $plate_open_b), '');
expect('plate to open normal 3WordID', chat_access_error($fixed, $org_open), '');
expect('open normal 3WordID to plate', chat_access_error($org_open, $fixed), '');

// --- separation for real organizations is unchanged ---
expect('closed org cannot be messaged by a plate', chat_access_error($fixed, $org_closed), 'chat.err_closed_in');
expect('closed org cannot message a plate', chat_access_error($org_closed, $fixed), 'chat.err_closed_out');
expect('closed org cannot message an open outsider', chat_access_error($org_closed, $org_open), 'chat.err_closed_out');
expect('closed org 3WordIDs of the same account can talk', chat_access_error($org_closed, $org_closed_2), '');
expect('visitor cannot message a closed org', chat_access_error(null, $org_closed), 'chat.err_closed_in');
expect('visitor can message an open plate', chat_access_error(null, $fixed), '');
expect('non-chat row is never closed', chat_row_is_closed($no_chat), false);

// --- the claim code opens the plate (no database here: check the source) ---
$src = file_get_contents(__DIR__ . '/../php/plate_billing.php');
$pos_claim = strpos($src, 'function bumperchat_claim_plate');
$pos_open = strpos($src, 'db_chat_open_set($new_id, true)', (int) $pos_claim);
expect('bumperchat_claim_plate opens the new plate', $pos_open !== false, true);

if ($fail) {
    echo "$fail failed\n";
    exit(1);
}
echo "ok\n";
exit(0);
