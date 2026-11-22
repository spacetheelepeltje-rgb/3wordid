// Checks homepage search-box icon precedence (plate / address / lock).
// Run from the repo root:  node tools/test_search_lock.js
// Not needed on the web server. Exit code 1 when something differs.
const { looksLikePlate, searchLockKind } = require('../js/3wid_index_2.js');

let fail = 0;
function expect(label, got, want) {
    if (got !== want) {
        console.log('FAIL ' + label + ': got ' + JSON.stringify(got) + ', expected ' + JSON.stringify(want));
        fail++;
    }
}

expect('missing stays lock', searchLockKind(false, false, false), 'lock');
expect('address without existing id is lock', searchLockKind(false, false, true), 'lock');
expect('existing no flags is lock', searchLockKind(true, false, false), 'lock');
expect('existing address is home', searchLockKind(true, false, true), 'home');
expect('existing plate is car', searchLockKind(true, true, false), 'car');
expect('missing plate is still car', searchLockKind(false, true, false), 'car');
expect('plate wins over address', searchLockKind(true, true, true), 'car');
expect('missing plate wins over address', searchLockKind(false, true, true), 'car');

expect('plate pattern 1 kbb 00', looksLikePlate(['1', 'kbb', '00']), true);
expect('not a plate 2562AE 187 0', looksLikePlate(['2562AE', '187', '0']), false);
expect('not a plate words only', looksLikePlate(['private', 'anonymous', 'message']), false);

console.log(fail ? fail + ' problem(s)' : 'OK');
process.exit(fail ? 1 : 0);
