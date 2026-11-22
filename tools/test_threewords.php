<?php
/**
 * Checks 3WordID word rules that do not need the database:
 * space-separated groups only, mixed letter/digit groups, single-character groups.
 * Run from the repo root:  php tools/test_threewords.php
 * Not needed on the web server. Exit code 1 when something differs.
 */

require_once __DIR__ . '/../php/threewords.php';

$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}

// Split only on whitespace: 2562AE stays one word
expect('split mixed three', threeword_split('2562AE 187 0'), ['2562AE', '187', '0']);
expect('split trim and spaces', threeword_split('  ab   123  c  '), ['ab', '123', 'c']);
expect('split one mixed group', threeword_split('2562AE'), ['2562AE']);
expect('split empty', threeword_split('   '), []);
expect('split does not break on letter/digit', threeword_split('92ZDNG'), ['92ZDNG']);

// Paying users may use mixed groups and a one-character word
expect('paying mixed ok', threeword_error('2562AE 187 0', true), null);
expect('paying lowercase ok', threeword_error('2562ae 187 0', true), null);
expect('paying single char ok', threeword_error('a b 0', true), null);
expect('paying still exactly three', threeword_error('2562AE 187 0 extra', true), 'js.max_three');
expect('paying two words complete', threeword_error('2562AE 187', true), 'js.max_three');
expect('paying two words typing', threeword_error('2562AE 187', true, false), null);
expect('paying empty complete', threeword_error('  ', true), 'js.add_need_3wid');
expect('paying hyphen rejected', threeword_error('2562AE-187-0', true), 'js.add_err_chars_paid');

// Free users: letters only; the message for digits is not "max three words"
expect('free mixed is digits', threeword_error('2562AE 187 0', false), 'js.add_err_digits');
expect('free letters ok', threeword_error('our delicious menu', false), null);
expect('free four words', threeword_error('one two three four', false), 'js.max_three');

if ($fail === 0) {
    echo "OK test_threewords.php\n";
    exit(0);
}
echo "$fail test(s) failed\n";
exit(1);
