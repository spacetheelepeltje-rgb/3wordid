// Checks that every example in bumperchat/plate_formats.json converts to its three words with
// the same code the page uses (js/bumperchat.js). Run from the repo root:
//   node tools/test_bumperchat_plates.js
// Not needed on the web server. Exit code 1 when something differs.
const fs = require('fs');
const path = require('path');
const { plateToWords, formatPlate, platePrefix, platePrefixLabel, inferCountry } = require(path.join(__dirname, '..', 'js', 'bumperchat.js'));
const data = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'bumperchat', 'plate_formats.json'), 'utf8'));

let fail = 0, n = 0;
const codes = new Set();
for (const c of data.countries) {
    if (codes.has(c.code)) { console.log(`duplicate country ${c.code}`); fail++; }
    codes.add(c.code);
    for (const k of ['strip', 'mode', 'format', 'source']) {
        if (!c[k]) { console.log(`${c.code}: missing ${k}`); fail++; }
    }
    if (!c.rule || !c.rule.en || !c.name || !c.name.en) { console.log(`${c.code}: missing English name or rule`); fail++; }
    for (const ex of c.examples) {
        n++;
        const [region, plate] = ex.plate.includes('|') ? ex.plate.split('|') : ['', ex.plate];
        const got = plateToWords(c, plate, region);
        if (got !== ex.words) { console.log(`${c.code}: "${ex.plate}" -> "${got}", expected "${ex.words}"`); fail++; }
        if (!/^[a-z0-9]+ [a-z0-9]+ [a-z0-9]+$/.test(ex.words)) { console.log(`${c.code}: "${ex.words}" is not three words`); fail++; }
    }
}
// Typed without spaces / with other separators
const by = Object.fromEntries(data.countries.map(c => [c.code, c]));
const extra = [
    ['NL', 'ab123c', 'ab 123 c'], ['NL', 'AB 123 C', 'ab 123 c'], ['FR', 'AB123CD', 'ab 123 cd'],
    ['DK', 'AB12345', 'ab 12 345'], ['ES', '1234BCD', 'e 1234 bcd'], ['BE', '1ABC234', '1 abc 234'],
    ['BE', '123-ABC', 'b 123 abc'], ['HU', 'AABC123', 'h aabc 123'], ['SE', 'ABC 12A', 's abc 12a'],
    ['LU', '1234', 'l 12 34'], ['CZ', '1A23456', 'cz 1a2 3456'], ['NL', 'ABCD12', 'ab cd 12'], ['US|XX', 'ABC123', ''],
    ['CA|QC', 'ABC 123', 'qc abc 123'], ['US|TX', 'ABC-1234', 'tx abc 1234'],
];
for (const [k, plate, want] of extra) {
    n++;
    const [code, region] = k.split('|');
    const got = plateToWords(by[code], plate, region || '');
    if (got !== want) { console.log(`${k}: "${plate}" -> "${got}", expected "${want}"`); fail++; }
}
// Prefix chip + typed-prefix stripping (JS, same cases as tools/test_bumperchat_flow.php)
const prefixCases = [
    ['FI', 'ABC-123', '', 'fin abc 123', 'ABC-123', 'fin'],
    ['FI', 'fin abc-123', '', 'fin abc 123', 'ABC-123', 'fin'],
    ['FI', 'FIN ABC-123', '', 'fin abc 123', 'ABC-123', 'fin'],
    ['FI', 'FIN-123', '', 'fin fin 123', 'FIN-123', 'fin'],
    ['NL', 'AB-123-C', '', 'ab 123 c', 'AB-123-C', ''],
    ['NL', 'NL AB-123-C', '', 'nlab 123 c', 'NL-AB-123-C', ''],
    ['CA|ON', 'ABCD 123', 'ON', 'on abcd 123', 'ABCD 123', 'on'],
    ['CA|ON', 'ON ABCD 123', 'ON', 'on abcd 123', 'ABCD 123', 'on'],
    ['US|TX', 'ABC-1234', 'TX', 'tx abc 1234', 'ABC 1234', 'tx'],
    ['US|TX', 'TX ABC-1234', 'TX', 'tx abc 1234', 'ABC 1234', 'tx'],
    ['US|CA', '7ABC123', 'CA', 'us ca 7abc123', '7ABC123', 'us'],
    ['BE', '123-ABC', '', 'b 123 abc', '123-ABC', 'b'],
    ['BE', '1-ABC-234', '', '1 abc 234', '1-ABC-234', ''],
];
for (const [code, plate, region, words, formatted, chip] of prefixCases) {
    n++;
    const c = by[code.split('|')[0]];
    const got = plateToWords(c, plate, region);
    if (got !== words) { console.log(`${code} words "${plate}" -> "${got}", expected "${words}"`); fail++; }
    const fmt = formatPlate(c, plate, region);
    if (fmt !== formatted) { console.log(`${code} format "${plate}" -> "${fmt}", expected "${formatted}"`); fail++; }
    const pref = platePrefix(c, plate, region);
    if (pref !== chip) { console.log(`${code} prefix "${plate}" -> "${pref}", expected "${chip}"`); fail++; }
}
if (platePrefix(by.FI, '', '') !== 'fin') { console.log('FI empty prefix'); fail++; }
if (platePrefix(by.NL, '', '') !== '') { console.log('NL empty prefix'); fail++; }
if (platePrefix(by.BE, '', '') !== '') { console.log('BE empty prefix'); fail++; }
n += 3;
const labels = [
    ['FI', '', '', 'FIN-'],
    ['EE', '123 ABC', '', 'EST '],
    ['US', 'ABC-1234', 'TX', 'TX-'],
    ['US', '7ABC123', 'CA', 'US-'],
    ['CA', 'ABCD 123', 'ON', 'ON-'],
    ['NL', 'AB-123-C', '', ''],
    ['BE', '123-ABC', '', 'B-'],
    ['BE', '1-ABC-234', '', ''],
];
for (const [code, plate, region, want] of labels) {
    n++;
    const got = platePrefixLabel(by[code], plate, region);
    if (got !== want) { console.log(`${code} label "${plate}" -> ${JSON.stringify(got)}, expected ${JSON.stringify(want)}`); fail++; }
}

// Three already-typed groups stay intact, including mixed letter/digit groups.
// Compact plates still split on letter/digit runs when the official groups are homogeneous.
const mixedCases = [
    ['NL', '2562AE 187 0', '2562AE-187-0', '2562ae 187 0'],
    ['NL', '2562AE-187-0', '2562AE-187-0', '2562ae 187 0'],
    ['NL', 'AB123C', 'AB-123-C', 'ab 123 c'],
    ['NL', '92ZD NG', '92-ZD-NG', '92 zd ng'],
    ['NL', '92-ZD-NG', '92-ZD-NG', '92 zd ng'],
];
for (const [code, plate, formatted, words] of mixedCases) {
    n++;
    const fmt = formatPlate(by[code], plate);
    if (fmt !== formatted) { console.log(`${code} format "${plate}" -> "${fmt}", expected "${formatted}"`); fail++; }
    const got = plateToWords(by[code], fmt);
    if (got !== words) { console.log(`${code} words after format "${plate}" -> "${got}", expected "${words}"`); fail++; }
}

// Official mixed-group formats (and mid-typing). Region plates stay as printed.
// [code, plate, region, formatted, words] — words '' means incomplete / ambiguous.
const officialCases = [
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
for (const [code, plate, region, formatted, words] of officialCases) {
    n++;
    const fmt = formatPlate(by[code], plate, region);
    if (fmt !== formatted) { console.log(`${code} official format "${plate}" -> "${fmt}", expected "${formatted}"`); fail++; }
    const got = plateToWords(by[code], fmt, region);
    if (got !== words) { console.log(`${code} official words "${plate}" -> "${got}", expected "${words}"`); fail++; }
    if (words) {
        const direct = plateToWords(by[code], plate, region);
        if (direct !== words) { console.log(`${code} official direct "${plate}" -> "${direct}", expected "${words}"`); fail++; }
    }
}
// Without a country, keep three typed groups; generic split only on fewer groups.
n++;
if (formatPlate({}, '2562AE 187 0') !== '2562AE 187 0') { console.log('no-country three groups were split'); fail++; }

// Official Dutch sidecode 99-XXX-9 (e.g. 36-JXN-1): format, words, and unique infer.
const nlSidecode = [
    ['36 jxn 1', '36-JXN-1', '36 jxn 1'],
    ['36-JXN-1', '36-JXN-1', '36 jxn 1'],
    ['36JXN1', '36-JXN-1', '36 jxn 1'],
];
for (const [plate, formatted, words] of nlSidecode) {
    n++;
    const fmt = formatPlate(by.NL, plate);
    if (fmt !== formatted) { console.log(`NL sidecode format "${plate}" -> "${fmt}", expected "${formatted}"`); fail++; }
    const got = plateToWords(by.NL, fmt);
    if (got !== words) { console.log(`NL sidecode words "${plate}" -> "${got}", expected "${words}"`); fail++; }
    const inferred = inferCountry(data.countries, plate);
    if (inferred !== 'NL') { console.log(`NL sidecode infer "${plate}" -> "${inferred}", expected "NL"`); fail++; }
}

const eu = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'];
for (const c of eu.concat(['US', 'CA'])) { if (!codes.has(c)) { console.log(`missing country ${c}`); fail++; } }
console.log(`${data.countries.length} countries, ${n} conversions checked, ${fail} problem(s)`);
process.exit(fail ? 1 : 0);
