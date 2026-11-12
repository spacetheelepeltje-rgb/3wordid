// Bumperchat (bumperchat/index.php): licence plate -> three words.
// The rules come from bumperchat/plate_formats.json (passed in by the page as window.BC_FORMATS).
// Texts come from window.T3 (php/i18n.php). Works without jQuery. Also used by the test
// tools/test_bumperchat_plates.js (Node): plateToWords() is exported there.

(function (root) {
    'use strict';

    // "AB123CD" -> ["AB", "123", "CD"]: split where letters and digits change
    function runs(s) {
        return s.match(/[A-Z]+|[0-9]+/g) || [];
    }

    // Groups as printed: anything that is not a letter or digit (space, dash, dot) separates
    function groups(plate) {
        return plate.toUpperCase().split(/[^A-Z0-9]+/).filter(function (g) { return g !== ''; });
    }

    function fixedSplit(compact, lengths) {
        var total = lengths.reduce(function (a, b) { return a + b; }, 0);
        if (compact.length !== total) return null;
        var out = [], at = 0;
        lengths.forEach(function (n) { out.push(compact.substr(at, n)); at += n; });
        return out;
    }

    /**
     * entry: one country from plate_formats.json. plate: what the user typed.
     * region: state / province code (US and Canada only).
     * Returns the three words (lower case, joined with spaces) or '' when the plate
     * doesn't fit the rule.
     */
    function plateToWords(entry, plate, region) {
        var g = groups(String(plate || ''));
        var compact = g.join('');
        var strip = String(entry.strip || '').toLowerCase();
        var out = null;
        if (compact === '' || compact.length > 20) return '';

        if (entry.mode === 'three') {
            if (g.length === 3) out = g;
            else if (entry.fixed) out = fixedSplit(compact, entry.fixed);
            if (!out) { var r = runs(compact); if (r.length === 3) out = r; }
        } else if (entry.mode === 'mixed') {
            if (g.length === 3) out = g;
            else {
                var r2 = runs(compact);
                if (r2.length === 3) out = r2;
                else if (r2.length === 2) out = [strip].concat(r2);
                else if (g.length === 2) out = [strip].concat(g);
            }
        } else if (entry.mode === 'prefix') {
            var r3 = runs(compact);
            if (g.length === 3 && entry.three_ok) out = g;
            else if (r3.length === 2) out = [strip].concat(r3);
            else if (g.length === 2) out = [strip].concat(g);
            else if (g.length === 1 && compact.length >= 3 && /^[0-9]+$/.test(compact)) {
                out = [strip, compact.substr(0, 2), compact.substr(2)];
            }
        } else if (entry.mode === 'region') {
            var reg = String(region || '').toUpperCase();
            if (!entry.regions || !entry.regions[reg]) return '';
            if (g.length === 2) out = [reg].concat(g);
            else if (g.length === 1) out = [strip, reg, g[0]];
        }
        if (!out || out.length !== 3) return '';
        return out.join(' ').toLowerCase();
    }

    root.plateToWords = plateToWords;
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { plateToWords: plateToWords };
        return;
    }

    // ------------------------------------------------------------------ page
    function tr(key, fallback) { return (root.T3 && root.T3[key]) || fallback; }
    function $(id) { return document.getElementById(id); }

    document.addEventListener('DOMContentLoaded', function () {
        var data = root.BC_FORMATS || {};
        var list = data.countries || [];
        var lang = root.BC_LANG || 'en';
        var byCode = {};
        list.forEach(function (c) { byCode[c.code] = c; });
        function txt(o) { return (o && (o[lang] || o.en)) || ''; }

        var select = $('bcCountry'), region = $('bcRegion'), regionWrap = $('bcRegionWrap');
        var plate = $('bcPlate'), out = $('bcWords'), rule = $('bcRule'), useBtn = $('bcUse');
        var claimBase = (useBtn && useBtn.getAttribute('data-claim')) || 'claim.php';

        function fillRegions(c) {
            if (!region) return;
            region.innerHTML = '';
            if (!c || !c.regions) { regionWrap.hidden = true; return; }
            regionWrap.hidden = false;
            Object.keys(c.regions).forEach(function (k) {
                var o = document.createElement('option');
                o.value = k; o.textContent = k + ' \u00b7 ' + c.regions[k];
                region.appendChild(o);
            });
        }

        function update() {
            var c = byCode[select && select.value];
            if (!c) return;
            if (rule) {
                var ex = c.examples[0];
                rule.textContent = txt(c.rule) + ' ' + tr('bc_example', 'Example:') + ' ' + ex.plate.replace('|', ' ') + ' \u2192 ' + ex.words;
            }
            var words = plate && plate.value.trim() !== '' ? plateToWords(c, plate.value, region && region.value) : '';
            if (out) {
                if (!plate || plate.value.trim() === '') {
                    out.textContent = tr('bc_type_plate', 'Type your plate to see its three words.');
                    out.classList.remove('is-ok', 'is-bad');
                } else if (words) {
                    out.textContent = words;
                    out.classList.add('is-ok'); out.classList.remove('is-bad');
                } else {
                    out.textContent = tr('bc_no_fit', 'This plate does not fit the rule. Type it as printed, with spaces or dashes between the groups.');
                    out.classList.add('is-bad'); out.classList.remove('is-ok');
                }
            }
            if (useBtn) {
                useBtn.hidden = !words;
                useBtn.setAttribute('href', claimBase + '?words=' + encodeURIComponent(words));
            }
        }

        if (select) {
            select.addEventListener('change', function () { fillRegions(byCode[select.value]); update(); });
            fillRegions(byCode[select.value]);
        }
        if (region) region.addEventListener('change', update);
        if (plate) plate.addEventListener('input', update);
        update();

        // Searchable list of all countries
        var filter = $('bcFilter');
        if (filter) {
            filter.addEventListener('input', function () {
                var q = filter.value.trim().toLowerCase();
                var shown = 0;
                document.querySelectorAll('.bc-format-row').forEach(function (row) {
                    var hit = q === '' || row.getAttribute('data-search').indexOf(q) !== -1;
                    row.hidden = !hit;
                    if (hit) shown++;
                });
                var none = $('bcNone');
                if (none) none.hidden = shown !== 0;
            });
        }
        // "Use" in the list: pick that country in the converter above
        document.querySelectorAll('[data-pick]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (!select) return;
                select.value = b.getAttribute('data-pick');
                fillRegions(byCode[select.value]);
                update();
                var conv = $('bcConverter');
                if (conv && conv.scrollIntoView) conv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                if (plate) plate.focus({ preventScroll: true });
            });
        });
    });
})(typeof window !== 'undefined' ? window : globalThis);
