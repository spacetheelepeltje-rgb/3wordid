<?php
/**
 * Checks pending-message helpers that do not need the database:
 * the 3-per-plate limit, 120-day expiry, delivery order, status
 * transitions, and sender conversation ordering.
 * Run from the repo root:  php tools/test_pending_messages.php
 * Not needed on the web server. Exit code 1 when something differs.
 */

require_once __DIR__ . '/../php/pending_messages.php';

$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}

function row($over = []) {
    $now = $over['now'] ?? strtotime('2026-11-19 12:00:00');
    $created = $over['created_at'] ?? date('Y-m-d H:i:s', $now);
    $base = [
        'id' => 1,
        'threeword' => 'nl 12 abc',
        'sender_user_id' => 10,
        'from_wid_id' => 100,
        'country' => 'NL',
        'region' => '',
        'plate' => '12-ABC-3',
        'title' => 'Hello',
        'message' => 'Hello there',
        'status' => 'waiting',
        'created_at' => $created,
        'expires_at' => pending_expires_at(pending_ts($created)),
        'status_changed_at' => $created,
        'delivered_at' => null,
        'read_at' => null,
        'expired_at' => null,
        'delivered_message_id' => null,
        'delivered_wid_id' => null,
    ];
    unset($over['now']);
    return array_merge($base, $over);
}

$t0 = strtotime('2026-11-19 12:00:00');

// --- constants ---
expect('ttl days', pending_ttl_days(), 120);
expect('max per plate', pending_max_per_plate(), 3);
expect('expires +120 days', pending_expires_at($t0), '2027-03-19 12:00:00');

// --- limit: only waiting counts, expired do not ---
$plate = 'nl 12 abc';
$rows = [
    row(['id' => 1, 'now' => $t0]),
    row(['id' => 2, 'now' => $t0 + 60]),
];
expect('used 2 waiting', pending_count_toward_limit($rows, 10, $plate, $t0 + 120), 2);
expect('remaining after 2', pending_remaining(2), 1);
expect('can send after 2', pending_can_send(2), true);

$rows[] = row(['id' => 3, 'now' => $t0 + 120]);
expect('used 3 waiting', pending_count_toward_limit($rows, 10, $plate, $t0 + 180), 3);
expect('remaining after 3', pending_remaining(3), 0);
expect('cannot send after 3', pending_can_send(3), false);

$rows[] = row(['id' => 4, 'now' => $t0, 'status' => 'expired', 'message' => null]);
expect('expired does not count', pending_count_toward_limit($rows, 10, $plate, $t0 + 180), 3);

$rows[] = row(['id' => 5, 'sender_user_id' => 11, 'now' => $t0]);
expect('other sender does not count', pending_count_toward_limit($rows, 10, $plate, $t0 + 180), 3);

$rows[] = row(['id' => 6, 'threeword' => 'ab 123 cd', 'now' => $t0]);
expect('other plate does not count', pending_count_toward_limit($rows, 10, $plate, $t0 + 180), 3);

// --- expiry: body cleared, status expired, then sender may send again ---
$old = row([
    'id' => 20,
    'created_at' => '2026-07-01 12:00:00',
    'expires_at' => '2026-10-29 12:00:00',
    'status_changed_at' => '2026-07-01 12:00:00',
    'message' => 'please move your car',
]);
$before = strtotime('2026-10-29 11:59:59');
$still = pending_expire_row($old, $before);
expect('not expired one second early', $still['status'], 'waiting');
expect('body kept before expiry', $still['message'], 'please move your car');
expect('flag off before', !empty($still['expired_now']), false);

$at = strtotime('2026-10-29 12:00:00');
$gone = pending_expire_row($old, $at);
expect('expired on the deadline', $gone['status'], 'expired');
expect('body cleared', $gone['message'], null);
expect('expired_at set', $gone['expired_at'], '2026-10-29 12:00:00');
expect('status change at expiry', $gone['status_changed_at'], '2026-10-29 12:00:00');
expect('flag on', !empty($gone['expired_now']), true);

// After expiry the same sender may send again (expired rows do not count)
expect('remaining after expiry', pending_remaining(pending_count_toward_limit([$gone], 10, $plate, $at)), 3);
expect('can send after expiry', pending_can_send(pending_count_toward_limit([$gone], 10, $plate, $at)), true);

// delivered / read are never auto-expired
$deliv = pending_expire_row(row(['id' => 21, 'status' => 'delivered', 'expires_at' => '2026-01-01 00:00:00']), $t0);
expect('delivered not auto-expired', $deliv['status'], 'delivered');
$read = pending_expire_row(row(['id' => 22, 'status' => 'read', 'expires_at' => '2026-01-01 00:00:00']), $t0);
expect('read not auto-expired', $read['status'], 'read');

// --- delivery order: original created_at, then id; skip expired ---
$drows = [
    row(['id' => 32, 'created_at' => '2026-11-10 10:00:00', 'expires_at' => '2027-03-10 10:00:00', 'message' => 'second']),
    row(['id' => 31, 'created_at' => '2026-11-10 09:00:00', 'expires_at' => '2027-03-10 09:00:00', 'message' => 'first']),
    row(['id' => 33, 'created_at' => '2026-11-10 10:00:00', 'expires_at' => '2027-03-10 10:00:00', 'message' => 'third']),
    row(['id' => 30, 'created_at' => '2026-06-01 10:00:00', 'expires_at' => '2026-09-29 10:00:00', 'message' => 'too old']),
];
$queue = pending_rows_for_delivery($drows, $plate, $t0);
expect('delivery count skips expired', count($queue), 3);
expect('delivery 1st is oldest', $queue[0]['message'], 'first');
expect('delivery 2nd same-time lower id', $queue[1]['message'], 'second');
expect('delivery 3rd same-time higher id', $queue[2]['message'], 'third');

$now_del = strtotime('2026-11-19 15:00:00');
$after = pending_apply_delivery($queue[0], 9001, 55, $now_del);
expect('delivered status', $after['status'], 'delivered');
expect('delivered message id', $after['delivered_message_id'], 9001);
expect('delivered wid', $after['delivered_wid_id'], 55);
expect('delivered_at', $after['delivered_at'], '2026-11-19 15:00:00');
expect('delivery keeps body', $after['message'], 'first');
expect('delivery keeps created_at', $after['created_at'], '2026-11-10 09:00:00');

$after_read = pending_apply_read($after, strtotime('2026-11-20 09:00:00'));
expect('read status', $after_read['status'], 'read');
expect('read_at', $after_read['read_at'], '2026-11-20 09:00:00');
expect('read does not change message id', $after_read['delivered_message_id'], 9001);

// reading a waiting row is a no-op
$noop = pending_apply_read(row(['id' => 1, 'now' => $t0]), $t0);
expect('cannot read a waiting row', $noop['status'], 'waiting');

// --- grouping + sort: waiting at the bottom; changed ones at the top by status change ---
$sender_rows = [
    row(['id' => 1, 'threeword' => 'aa 1 aaa', 'plate' => 'AA-1-AAA', 'status' => 'waiting', 'created_at' => '2026-11-18 10:00:00', 'expires_at' => '2027-03-18 10:00:00', 'status_changed_at' => '2026-11-18 10:00:00', 'message' => 'wait new']),
    row(['id' => 2, 'threeword' => 'bb 2 bbb', 'plate' => 'BB-2-BBB', 'status' => 'delivered', 'created_at' => '2026-11-17 10:00:00', 'status_changed_at' => '2026-11-19 11:00:00', 'delivered_at' => '2026-11-19 11:00:00', 'delivered_wid_id' => 201, 'message' => 'delivered later']),
    row(['id' => 3, 'threeword' => 'cc 3 ccc', 'plate' => 'CC-3-CCC', 'status' => 'read', 'created_at' => '2026-11-16 10:00:00', 'status_changed_at' => '2026-11-19 08:00:00', 'delivered_wid_id' => 202, 'message' => 'read earlier']),
    row(['id' => 4, 'threeword' => 'dd 4 ddd', 'plate' => 'DD-4-DDD', 'status' => 'expired', 'created_at' => '2026-07-01 10:00:00', 'status_changed_at' => '2026-10-29 10:00:00', 'message' => null]),
    row(['id' => 5, 'threeword' => 'ee 5 eee', 'plate' => 'EE-5-EEE', 'status' => 'waiting', 'created_at' => '2026-11-10 10:00:00', 'expires_at' => '2027-03-10 10:00:00', 'status_changed_at' => '2026-11-10 10:00:00', 'message' => 'wait old']),
];
$groups = pending_group_conversations($sender_rows, 10, 100, $t0);

// A normal conversation (no pending status) sits in the middle
$normal = [
    'w9' => [
        'key' => 'w9',
        'name' => 'normal chat',
        'visitor' => false,
        'deleted' => false,
        'wid' => 9,
        'unread' => 0,
        'items' => [['id' => 50, 'message' => 'hi', 'mine' => true, 'sent_at' => '2026-11-19 12:30:00', 'read_at' => '']],
    ],
];
// Delivered group with a known wid should attach to an existing conversation
$normal['w201'] = [
    'key' => 'w201',
    'name' => 'bb 2 bbb',
    'visitor' => false,
    'deleted' => false,
    'wid' => 201,
    'unread' => 0,
    'items' => [['id' => 9001, 'message' => 'delivered later', 'mine' => true, 'sent_at' => '2026-11-17 10:00:00', 'read_at' => null]],
];

$merged = pending_merge_conversations($normal, $groups);
$keys = array_keys($merged);

// Top: delivered (status change 11:00) then read (08:00) then expired (Oct 29)
// Middle: normal chat
// Bottom: waiting, newest first (aa then ee)
expect('merged count', count($keys), 6);
expect('1st is delivered (latest status change)', $merged[$keys[0]]['pending_status'] ?? '', 'delivered');
expect('1st attached to existing w201', $keys[0], 'w201');
expect('2nd is read', $merged[$keys[1]]['pending_status'] ?? '', 'read');
expect('3rd is expired', $merged[$keys[2]]['pending_status'] ?? '', 'expired');
expect('4th is the ordinary conversation', $keys[3], 'w9');
expect('5th is newer waiting', $merged[$keys[4]]['pending_status'] ?? '', 'waiting');
expect('5th is the later waiting plate', $merged[$keys[4]]['words'] ?? '', 'aa 1 aaa');
expect('6th is older waiting', $merged[$keys[5]]['words'] ?? '', 'ee 5 eee');

// Mixed waiting + expired on the same plate: still waiting (sits at the bottom)
$mixed = [
    row(['id' => 7, 'threeword' => 'ff 6 fff', 'status' => 'expired', 'created_at' => '2026-07-01 10:00:00', 'status_changed_at' => '2026-10-29 10:00:00', 'message' => null]),
    row(['id' => 8, 'threeword' => 'ff 6 fff', 'status' => 'waiting', 'created_at' => '2026-11-18 10:00:00', 'expires_at' => '2027-03-18 10:00:00', 'status_changed_at' => '2026-11-18 10:00:00']),
];
$gm = pending_group_conversations($mixed, 10, 100, $t0);
$st = pending_group_status(array_values($gm)[0]['items'] ? $mixed : $mixed);
// Use the group itself
$g0 = array_values($gm)[0];
expect('mixed group is waiting', $g0['pending_status'], 'waiting');
expect('mixed bucket is bottom', pending_conversation_bucket($g0), 2);

// All delivered then some read: group stays delivered until every one is read
$part = [
    row(['id' => 9, 'threeword' => 'gg 7 ggg', 'status' => 'delivered', 'status_changed_at' => '2026-11-19 10:00:00', 'delivered_wid_id' => 301]),
    row(['id' => 10, 'threeword' => 'gg 7 ggg', 'status' => 'read', 'status_changed_at' => '2026-11-19 11:00:00', 'delivered_wid_id' => 301]),
];
$gp = array_values(pending_group_conversations($part, 10, 100, $t0))[0];
expect('partially read group is delivered', $gp['pending_status'], 'delivered');
expect('partially read uses delivered change time', $gp['status_changed_at'], '2026-11-19 10:00:00');

echo $fail ? "$fail failure(s)\n" : "OK\n";
exit($fail ? 1 : 0);
