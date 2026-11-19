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

    function plateSeparator(entry) {
        var format = String((entry && entry.format) || '').replace(/\[[^\]]*\]/g, '');
        return format.indexOf('-') !== -1 ? '-' : ' ';
    }

    function formatPlate(entry, raw) {
        var compact = String(raw || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (!compact) return '';
        var sep = plateSeparator(entry || {});
        if (entry && entry.fixed && entry.fixed.length) {
            var parts = [], at = 0, i;
            for (i = 0; i < entry.fixed.length; i++) {
                var n = parseInt(entry.fixed[i], 10);
                if (!n || at >= compact.length) break;
                parts.push(compact.substr(at, n));
                at += n;
            }
            if (at < compact.length) parts.push(compact.substr(at));
            return parts.join(sep);
        }
        var r = runs(compact);
        return r.length >= 2 ? r.join(sep) : compact;
    }

    function plateExample(entry) {
        var ex = (entry && entry.examples && entry.examples[0] && entry.examples[0].plate) || (entry && entry.format) || '';
        return String(ex).indexOf('|') !== -1 ? String(ex).split('|')[1] : String(ex);
    }

    function plateStyle(code) {
        code = String(code || '').toUpperCase();
        if (code === 'NL') return 'nl';
        if (code === 'US' || code === 'CA') return 'na';
        return 'eu';
    }

    function inferCountry(list, plate, region) {
        var hits = [];
        if (!String(plate || '').trim()) return '';
        (list || []).forEach(function (c) {
            if (c.mode === 'region' && !region) return;
            if (plateToWords(c, plate, region || '')) hits.push(c.code);
        });
        return hits.length === 1 ? hits[0] : '';
    }

    function setFormattedValue(input, formatted) {
        if (!input || input.value === formatted) return;
        var before = String(input.value.slice(0, input.selectionStart || 0)).replace(/[^A-Za-z0-9]/g, '').length;
        input.value = formatted;
        var seen = 0, i = 0;
        for (; i < formatted.length; i++) {
            if (/[A-Z0-9]/.test(formatted[i])) seen++;
            if (seen >= before) { i++; break; }
        }
        try { input.setSelectionRange(i, i); } catch (err) {}
    }

    root.plateToWords = plateToWords;
    root.formatPlate = formatPlate;
    root.inferCountry = inferCountry;
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { plateToWords: plateToWords, formatPlate: formatPlate, inferCountry: inferCountry };
        return;
    }

    // ------------------------------------------------------------------ page
    function tr(key, fallback) { return (root.T3 && root.T3[key]) || fallback; }
    function $(id) { return document.getElementById(id); }

    document.addEventListener('DOMContentLoaded', function () {
        if (root.BC_CLAIM_FORM) { initClaimForm(); return; }
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
                var q = '?country=' + encodeURIComponent(c.code);
                if (region && region.value) q += '&region=' + encodeURIComponent(region.value);
                if (plate && plate.value) q += '&plate=' + encodeURIComponent(plate.value);
                useBtn.setAttribute('href', claimBase + q);
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

    function initClaimForm() {
        var data = root.BC_FORMATS || {};
        var list = data.countries || [];
        var lang = root.BC_LANG || 'en';
        var byCode = {};
        list.forEach(function (c) { byCode[c.code] = c; });
        function txt(o) { return (o && (o[lang] || o.en)) || ''; }
        function trv(key, fallback, vars) {
            var s = tr(key, fallback);
            Object.keys(vars || {}).forEach(function (k) {
                s = s.split('{' + k + '}').join(vars[k]);
            });
            return s;
        }

        var select = $('bcCountry'), region = $('bcRegion'), regionWrap = $('bcRegionWrap');
        var plate = $('bcPlate'), out = $('bcWords'), hint = $('bcPlateHint');
        var shell = $('bcPlateShell'), cc = $('bcPlateCc'), legend = $('bcPlateLegend');
        var next = $('bcNext'), showBtn = $('bcShowCountry'), suggest = $('bcSuggest');

        if (showBtn) showBtn.hidden = true;

        function fillRegions(c, keep) {
            if (!region || !regionWrap) return;
            var prev = keep || region.value;
            region.innerHTML = '';
            var opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = tr('bc_choose_region', 'Select a state or province');
            region.appendChild(opt0);
            if (!c || !c.regions) { regionWrap.hidden = true; return; }
            regionWrap.hidden = false;
            Object.keys(c.regions).forEach(function (k) {
                var o = document.createElement('option');
                o.value = k; o.textContent = k + ' \u00b7 ' + c.regions[k];
                if (k === prev) o.selected = true;
                region.appendChild(o);
            });
        }

        function applyStyle(c) {
            if (!shell) return;
            var code = c ? c.code : '';
            shell.setAttribute('data-style', plateStyle(code));
            shell.setAttribute('data-code', code);
            shell.classList.remove('is-eu', 'is-nl', 'is-na');
            shell.classList.add('is-' + plateStyle(code));
            if (cc) cc.textContent = c ? (c.strip || c.code) : '';
            if (legend) legend.textContent = (c && c.regions && region && region.value) ? region.value : '';
            if (plate) plate.setAttribute('placeholder', c ? plateExample(c) : '');
        }

        function revealRules(code) {
            document.querySelectorAll('.bc-country-rules').forEach(function (el) {
                var on = el.getAttribute('data-country') === code && code !== '';
                el.hidden = !on;
                el.classList.toggle('is-on', on);
            });
        }

        function current() { return byCode[select && select.value]; }

        function validate() {
            var c = current();
            var formatted = c && plate ? formatPlate(c, plate.value) : (plate ? plate.value.toUpperCase().replace(/[^A-Z0-9]/g, '') : '');
            if (plate && formatted !== plate.value) setFormattedValue(plate, formatted);
            var words = (c && plate && plate.value.trim() !== '') ? plateToWords(c, plate.value, region && region.value) : '';
            var needsRegion = !!(c && c.regions && region && !region.value);
            var ok = !!words && !needsRegion;

            if (shell) {
                shell.classList.toggle('is-ok', ok);
                shell.classList.toggle('is-bad', !!(plate && plate.value.trim() !== '' && !ok));
            }
            if (out) {
                if (!plate || plate.value.trim() === '') {
                    out.textContent = tr('bc_type_plate', 'Type your plate to see its three words.');
                    out.classList.remove('is-ok', 'is-bad');
                } else if (ok) {
                    out.textContent = words;
                    out.classList.add('is-ok'); out.classList.remove('is-bad');
                } else if (needsRegion) {
                    out.textContent = tr('bc_err_region', 'Choose a state or province.');
                    out.classList.add('is-bad'); out.classList.remove('is-ok');
                } else if (c) {
                    out.textContent = trv('bc_plate_invalid', 'This does not look like a {country} plate. {country} plates look like {example}.', {
                        country: txt(c.name) || c.code,
                        example: plateExample(c)
                    });
                    out.classList.add('is-bad'); out.classList.remove('is-ok');
                }
            }
            if (next) next.disabled = !ok;

            if (suggest) {
                suggest.hidden = true;
                suggest.textContent = '';
                if (plate && plate.value.trim() !== '' && !ok) {
                    var guess = inferCountry(list, plate.value, region && region.value);
                    if (guess && (!c || guess !== c.code)) {
                        var g = byCode[guess];
                        suggest.hidden = false;
                        suggest.innerHTML = '';
                        suggest.appendChild(document.createTextNode(trv('bc_suggest', 'This looks like a {country} plate. Use {country}?', { country: txt(g && g.name) || guess })));
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-ghost';
                        btn.textContent = txt(g && g.name) || guess;
                        btn.addEventListener('click', function () {
                            if (select) select.value = guess;
                            fillRegions(byCode[guess], region && region.value);
                            update();
                        });
                        suggest.appendChild(btn);
                    }
                } else if (plate && plate.value.trim() !== '' && (!select || !select.value)) {
                    var auto = inferCountry(list, plate.value, region && region.value);
                    if (auto && select) {
                        select.value = auto;
                        fillRegions(byCode[auto], region && region.value);
                        update();
                    }
                }
            }
        }

        function updateHint(c) {
            if (!hint) return;
            if (!c) { hint.textContent = ''; return; }
            hint.textContent = trv('bc_format_inline', 'Format: {format}  ·  e.g. {example}', {
                format: c.format || '',
                example: plateExample(c)
            });
        }

        function update() {
            var c = current();
            revealRules(c ? c.code : '');
            applyStyle(c);
            updateHint(c);
            validate();
        }

        if (select) {
            select.addEventListener('change', function () {
                fillRegions(byCode[select.value]);
                update();
                if (plate) plate.focus({ preventScroll: true });
            });
        }
        if (region) region.addEventListener('change', update);
        if (plate) {
            plate.addEventListener('input', validate);
            plate.addEventListener('blur', validate);
        }
        fillRegions(current(), region && region.value);
        update();
    }

})(typeof window !== 'undefined' ? window : globalThis);
