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

    function prefixTokens(entry, region) {
        var mode = String((entry && entry.mode) || '');
        if (mode === 'three' || mode === '') return [];
        var tokens = [];
        var strip = String((entry && entry.strip) || '').toLowerCase();
        if (strip) tokens.push(strip);
        if (mode === 'region') {
            var reg = String(region || '').toLowerCase();
            if (reg && reg !== strip) tokens.push(reg);
        }
        return tokens;
    }

    function remainderIsComplete(entry, plate, region) {
        if (!plateToWordsRaw(entry, plate, region)) return false;
        var mode = String((entry && entry.mode) || '');
        var g = groups(String(plate || ''));
        var r = runs(g.join(''));
        if (mode === 'prefix' || mode === 'mixed') {
            if (g.length === 2 || r.length === 2) return true;
            if ((mode === 'mixed' || entry.three_ok) && (g.length === 3 || r.length === 3)) return true;
            return false;
        }
        if (mode === 'region') return g.length === 1 || g.length === 2;
        return false;
    }

    /**
     * If the user typed the country/region prefix in front of the plate
     * (FIN ABC-123, ON ABCD 123), drop that leading duplicate. Does not peel
     * a leading group that is itself the plate (FIN-123 stays FIN-123).
     */
    function stripDuplicatePrefix(entry, raw, region) {
        raw = String(raw || '');
        var tokens = prefixTokens(entry, region);
        if (!tokens.length || !raw.trim()) return raw;
        var g = groups(raw);
        var i = 0;
        while (i < g.length && tokens.indexOf(g[i].toLowerCase()) !== -1) i++;
        if (i > 0 && i < g.length) {
            var rest = g.slice(i).join(' ');
            if (remainderIsComplete(entry, rest, region)) return rest;
        }
        if (g.length === 1) {
            var compact = raw.toUpperCase().replace(/[^A-Z0-9]/g, '');
            var strip = String((entry && entry.strip) || '').toUpperCase();
            var mode = String((entry && entry.mode) || '');
            if (strip.length >= 2 && (mode === 'prefix' || mode === 'mixed')
                && compact.indexOf(strip) === 0 && compact.length > strip.length) {
                var peeled = compact.substr(strip.length);
                if (remainderIsComplete(entry, peeled, region)) return peeled;
            }
        }
        return raw;
    }

    /**
     * The word shown as a fixed chip (never typed). Lower case, matching the
     * first word of plateToWords. Empty for three-mode countries (NL, …).
     */
    function platePrefix(entry, plate, region) {
        if (!entry) return '';
        var mode = String(entry.mode || '');
        var strip = String(entry.strip || '').toLowerCase();
        plate = stripDuplicatePrefix(entry, plate, region);
        var g = groups(String(plate || ''));
        if (mode === 'prefix') {
            if (entry.three_ok && g.length === 3) return '';
            return strip;
        }
        if (mode === 'mixed') {
            if (g.length === 3) return '';
            var r = runs(g.join(''));
            if (r.length === 3) return '';
            if (g.length === 2 || r.length === 2) return strip;
            return '';
        }
        if (mode === 'region') {
            var reg = String(region || '').toUpperCase();
            if (!reg || !entry.regions || !entry.regions[reg]) return '';
            if (g.length === 1) return strip;
            return reg.toLowerCase();
        }
        return '';
    }

    /** Visible prefix: uppercase word plus the country's separator (US/CA always '-'). */
    function platePrefixLabel(entry, plate, region) {
        var prefix = platePrefix(entry, plate, region);
        if (!prefix) return '';
        var sep = (entry && entry.mode === 'region') ? '-' : plateSeparator(entry);
        return prefix.toUpperCase() + sep;
    }

    /**
     * entry: one country from plate_formats.json. plate: what the user typed.
     * region: state / province code (US and Canada only).
     * Returns the three words (lower case, joined with spaces) or '' when the plate
     * doesn't fit the rule.
     */
    function plateToWords(entry, plate, region) {
        return plateToWordsRaw(entry, stripDuplicatePrefix(entry, plate, region), region);
    }

    function plateToWordsRaw(entry, plate, region) {
        var g = groups(String(plate || ''));
        var compact = g.join('');
        var strip = String(entry.strip || '').toLowerCase();
        var out = null;
        if (compact === '' || compact.length > 20) return '';
        var official = uniqueFitAnyPattern(entry, compact, false);
        var mixedOfficial = patternHasMixed((entry && entry.group_patterns) || []);

        if (entry.mode === 'three') {
            if (g.length === 3) out = g;
            else if (official && official.length === 3) out = official;
            else if (entry.fixed) out = fixedSplit(compact, entry.fixed);
            if (!out && !mixedOfficial) { var r = runs(compact); if (r.length === 3) out = r; }
        } else if (entry.mode === 'mixed') {
            if (g.length === 3) out = g;
            else if (official && official.length === 3) out = official;
            else if (official && official.length === 2) out = [strip].concat(official);
            else if (!mixedOfficial) {
                var r2 = runs(compact);
                if (r2.length === 3) out = r2;
                else if (r2.length === 2) out = [strip].concat(r2);
                else if (g.length === 2) out = [strip].concat(g);
            } else if (g.length === 2 && (official && official.length === 2 || groupsMatchOfficial(entry, g))) out = [strip].concat(g);
        } else if (entry.mode === 'prefix') {
            var r3 = ((entry.group_patterns || []).length) ? [] : runs(compact);
            if (g.length === 3 && entry.three_ok) out = g;
            else if (official && official.length === 2) out = [strip].concat(official);
            else if (official && official.length === 3 && entry.three_ok) out = official;
            else if (g.length === 2 && groupsMatchOfficial(entry, g)) out = [strip].concat(g);
            else if (r3.length === 2) out = [strip].concat(r3);
            else if (!(entry.group_patterns || []).length && g.length === 2) out = [strip].concat(g);
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

    /**
     * Official group patterns from plate_formats.json (simple character classes
     * and {n}/{n,m} only). Used so mixed official groups (CZ 1A2, SE 12A, …)
     * are not split at letter/digit transitions, including while typing.
     */
    function compileGroupPattern(src) {
        var slots = [], i = 0, s = String(src || '');
        while (i < s.length) {
            var cls = null, lit = null;
            if (s[i] === '[') {
                var close = s.indexOf(']', i);
                if (close < 0) return null;
                var body = s.slice(i + 1, close);
                if (body === 'A-Z') cls = /[A-Z]/;
                else if (body === '0-9') cls = /[0-9]/;
                else if (body === 'A-Z0-9' || body === '0-9A-Z') cls = /[A-Z0-9]/;
                else if (body === 'EH' || body === 'HE') cls = /[EH]/;
                else return null;
                i = close + 1;
            } else if (/[A-Z0-9]/.test(s[i])) {
                lit = s[i];
                i += 1;
            } else {
                return null;
            }
            var min = 1, max = 1;
            if (s[i] === '{') {
                var end = s.indexOf('}', i);
                if (end < 0) return null;
                var spec = s.slice(i + 1, end).split(',');
                min = parseInt(spec[0], 10);
                max = spec.length > 1 ? parseInt(spec[1], 10) : min;
                if (!min || min < 1 || !max || max < min) return null;
                i = end + 1;
            } else if (s[i] === '?') {
                min = 0;
                max = 1;
                i += 1;
            }
            slots.push({ cls: cls, lit: lit, min: min, max: max });
        }
        return slots.length ? slots : null;
    }

    function slotsMinMax(slots) {
        var min = 0, max = 0, i;
        for (i = 0; i < slots.length; i++) {
            min += slots[i].min;
            max += slots[i].max;
        }
        return { min: min, max: max };
    }

    function slotsConsume(slots, text, si, ti, allowPartial) {
        if (si === slots.length) return ti === text.length || (allowPartial && ti <= text.length);
        if (ti > text.length) return false;
        var slot = slots[si], n, ch, ok;
        var maxTake = Math.min(slot.max, text.length - ti);
        if (allowPartial && si === slots.length - 1) {
            // last slot may be short while typing
        }
        for (n = maxTake; n >= slot.min; n--) {
            ok = true;
            for (var k = 0; k < n; k++) {
                ch = text[ti + k];
                if (slot.lit) { if (ch !== slot.lit) { ok = false; break; } }
                else if (!slot.cls.test(ch)) { ok = false; break; }
            }
            if (ok && slotsConsume(slots, text, si + 1, ti + n, allowPartial)) return true;
        }
        if (allowPartial && ti === text.length && slot.min === 0) {
            return slotsConsume(slots, text, si + 1, ti, allowPartial);
        }
        // Incomplete last slot while typing (0..min-1 characters already taken by recursion above only if min=0)
        if (allowPartial && si === slots.length - 1 && ti < text.length) {
            var rest = text.length - ti;
            if (rest < slot.min && rest <= slot.max) {
                ok = true;
                for (k = 0; k < rest; k++) {
                    ch = text[ti + k];
                    if (slot.lit) { if (ch !== slot.lit) ok = false; }
                    else if (!slot.cls.test(ch)) ok = false;
                    if (!ok) break;
                }
                if (ok) return true;
            }
        }
        if (allowPartial && si < slots.length && ti === text.length) return true;
        return false;
    }

    function groupFullMatch(pattern, text) {
        var slots = compileGroupPattern(pattern);
        return !!(slots && text !== '' && slotsConsume(slots, text, 0, 0, false));
    }

    function groupPrefixMatch(pattern, text) {
        var slots = compileGroupPattern(pattern);
        if (!slots) return false;
        if (text === '') return true;
        return slotsConsume(slots, text, 0, 0, true);
    }

    /** True when typed groups are one complete official pattern (not a prefix). */
    function groupsMatchOfficial(entry, groups) {
        var list = (entry && entry.group_patterns) || [], p, i, pats, ok;
        if (!groups || !groups.length) return false;
        for (p = 0; p < list.length; p++) {
            pats = list[p];
            if (pats.length !== groups.length) continue;
            ok = true;
            for (i = 0; i < pats.length; i++) {
                if (!groupFullMatch(pats[i], groups[i])) { ok = false; break; }
            }
            if (ok) return true;
        }
        return false;
    }

    function groupIsMixed(pattern) {
        return /\[0-9A-Z\]|\[A-Z0-9\]|\[0-9\].*\[A-Z\]|\[A-Z\].*\[0-9\]/.test(String(pattern || ''))
            || (/\[0-9\]/.test(String(pattern || '')) && /\[A-Z\]/.test(String(pattern || '')))
            || (/\[0-9\]/.test(String(pattern || '')) && /\[EH\]/.test(String(pattern || '')));
    }

    function patternHasMixed(patterns) {
        var i, j;
        for (i = 0; i < (patterns || []).length; i++) {
            for (j = 0; j < patterns[i].length; j++) {
                if (groupIsMixed(patterns[i][j])) return true;
            }
        }
        return false;
    }

    /**
     * Collect every way compact text can fit one official pattern.
     * allowPartial: later groups may be missing and the last used group may
     * be incomplete (while typing).
     */
    function collectPatternFits(groupPatterns, text, allowPartial) {
        var fits = [];
        if (!text) {
            if (allowPartial) fits.push([]);
            return fits;
        }
        function rec(gi, pos, acc) {
            if (gi === groupPatterns.length) {
                if (pos === text.length) fits.push(acc);
                return;
            }
            if (pos === text.length) {
                if (allowPartial) fits.push(acc);
                return;
            }
            var slots = compileGroupPattern(groupPatterns[gi]);
            if (!slots) return;
            var mm = slotsMinMax(slots);
            var left = text.length - pos;
            var isLast = gi === groupPatterns.length - 1;
            var maxLen = Math.min(mm.max, left);
            var minLen = allowPartial ? 1 : mm.min;
            if (minLen > maxLen && allowPartial && left > 0) minLen = 1;
            var n, piece, more, okFull, okPart;
            for (n = maxLen; n >= minLen; n--) {
                piece = text.substr(pos, n);
                more = pos + n < text.length;
                okFull = slotsConsume(slots, piece, 0, 0, false);
                okPart = allowPartial && !more && slotsConsume(slots, piece, 0, 0, true);
                if (more && !okFull) continue;
                if (!more && !okFull && !okPart) continue;
                if (more && isLast) continue;
                rec(gi + 1, pos + n, acc.concat([piece]));
            }
        }
        rec(0, 0, []);
        return fits;
    }

    /**
     * The official grouping when every matching pattern agrees on the same
     * group boundaries. Null when the plate is ambiguous (e.g. compact German
     * without separators) or does not fit.
     */
    function uniqueFitAnyPattern(entry, text, allowPartial) {
        var list = (entry && entry.group_patterns) || [];
        var all = [], i, j, fits, key;
        for (i = 0; i < list.length; i++) {
            fits = collectPatternFits(list[i], text, allowPartial);
            for (j = 0; j < fits.length; j++) all.push(fits[j]);
        }
        if (!all.length) return null;
        key = all[0].join('\0');
        for (i = 1; i < all.length; i++) {
            if (all[i].join('\0') !== key) return null;
        }
        return all[0];
    }

    function countryFormatKnown(entry) {
        return !!(entry && (entry.format || entry.fixed || entry.mode === 'region' || (entry.group_patterns && entry.group_patterns.length)));
    }

    function joinFormatted(parts, sep, trailing) {
        var out = parts.filter(function (g) { return g !== ''; }).join(sep);
        if (trailing && out) out += sep;
        return out;
    }

    /**
     * Format a typed plate for the Bumperchat chain only (index, claim,
     * onboarding). Not used on the main 3WordID site or the create form.
     * When a country format is known, follow that format (including official
     * mixed letter/digit groups, also while typing). The generic letter/digit
     * split is only used when no country format is known, or when every
     * official group is a homogeneous run of letters or of digits (the
     * transition then is the official group boundary). Same as PHP
     * bumperchat_format_plate().
     */
    function formatPlate(entry, raw, region) {
        raw = stripDuplicatePrefix(entry, raw, region);
        var compact = String(raw || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (!compact) return '';
        var sep = plateSeparator(entry || {});
        var typed = String(raw || '').toUpperCase();
        var trailing = /[-.\s]$/.test(typed);
        var typedGroups = typed.split(/[^A-Z0-9]+/).filter(function (g) { return g !== ''; });
        var known = countryFormatKnown(entry);
        var patterns = (entry && entry.group_patterns) || [];
        var mode = String((entry && entry.mode) || '');

        // Region plates are used as printed: one block stays one block, two
        // groups stay two groups. Never split a mixed block such as 7ABC123.
        if (mode === 'region') {
            return joinFormatted(typedGroups, sep, trailing);
        }

        // If the user already typed exactly three groups, those are the three
        // words. Do not split mixed groups such as 2562AE 187 0 or DE 123E.
        if (typedGroups.length === 3) {
            return joinFormatted(typedGroups, sep, trailing);
        }

        // Official patterns: compact or mid-typing, including mixed groups.
        // Only apply a split when every official reading agrees.
        if (patterns.length) {
            if (typedGroups.length <= 1) {
                var fitted = uniqueFitAnyPattern(entry, compact, true);
                if (fitted && fitted.length) return joinFormatted(fitted, sep, trailing);
            } else {
                var kept = [];
                typedGroups.forEach(function (g) {
                    var keepWhole = false, p, gi;
                    for (p = 0; p < patterns.length && !keepWhole; p++) {
                        for (gi = 0; gi < patterns[p].length; gi++) {
                            if (groupFullMatch(patterns[p][gi], g) || (groupIsMixed(patterns[p][gi]) && groupPrefixMatch(patterns[p][gi], g))) {
                                keepWhole = true;
                                break;
                            }
                        }
                    }
                    if (keepWhole) {
                        kept.push(g);
                    } else {
                        var pieces = runs(g);
                        if (pieces.length) kept = kept.concat(pieces);
                        else kept.push(g);
                    }
                });
                if (kept.length) return joinFormatted(kept, sep, trailing);
            }
        }

        if (entry && entry.fixed && entry.fixed.length && typedGroups.length <= 1) {
            var parts = [], at = 0, i;
            for (i = 0; i < entry.fixed.length; i++) {
                var n = parseInt(entry.fixed[i], 10);
                if (!n || at >= compact.length) break;
                parts.push(compact.substr(at, n));
                at += n;
            }
            if (at < compact.length) parts.push(compact.substr(at));
            return joinFormatted(parts, sep, trailing);
        }

        // Generic letter/digit split only when no country format is known, or
        // when official groups are homogeneous so the transition is the boundary.
        if (!known || !patternHasMixed(patterns)) {
            var parts2 = [];
            typedGroups.forEach(function (g) {
                runs(g).forEach(function (x) { parts2.push(x); });
            });
            return joinFormatted(parts2, sep, trailing);
        }
        return joinFormatted(typedGroups, sep, trailing);
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
        var atEnd = (input.selectionStart || 0) >= input.value.length;
        var before = String(input.value.slice(0, input.selectionStart || 0)).replace(/[^A-Za-z0-9]/g, '').length;
        input.value = formatted;
        if (atEnd) { try { input.setSelectionRange(formatted.length, formatted.length); } catch (err) {} return; }
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
    root.platePrefix = platePrefix;
    root.platePrefixLabel = platePrefixLabel;
    root.stripDuplicatePrefix = stripDuplicatePrefix;
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = {
            plateToWords: plateToWords,
            formatPlate: formatPlate,
            inferCountry: inferCountry,
            platePrefix: platePrefix,
            platePrefixLabel: platePrefixLabel,
            stripDuplicatePrefix: stripDuplicatePrefix
        };
        return;
    }

    // ------------------------------------------------------------------ page
    function tr(key, fallback) { return (root.T3 && root.T3[key]) || fallback; }
    function $(id) { return document.getElementById(id); }

    document.addEventListener('DOMContentLoaded', function () {
        if (root.BC_CLAIM_FORM || root.BC_PENDING_FORM) { initClaimForm(); return; }
        var data = root.BC_FORMATS || {};
        var list = data.countries || [];
        var lang = root.BC_LANG || 'en';
        var byCode = {};
        list.forEach(function (c) { byCode[c.code] = c; });
        function txt(o) { return (o && (o[lang] || o.en)) || ''; }

        var select = $('bcCountry'), region = $('bcRegion'), regionWrap = $('bcRegionWrap');
        var plate = $('bcPlate'), out = $('bcWords'), rule = $('bcRule'), useBtn = $('bcUse');
        var leaveBtn = $('bcLeaveMsg');
        var claimBase = (useBtn && useBtn.getAttribute('data-claim')) || 'claim.php';
        var pendingBase = (leaveBtn && leaveBtn.getAttribute('data-pending')) || 'pending_send.php';
        var loginBase = (leaveBtn && leaveBtn.getAttribute('data-login')) || 'login.php';

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
            if (plate) {
                var formatted = formatPlate(c, plate.value, region && region.value);
                if (formatted !== plate.value) setFormattedValue(plate, formatted);
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
            if (useBtn || leaveBtn) {
                var q = '?country=' + encodeURIComponent(c.code);
                if (region && region.value) q += '&region=' + encodeURIComponent(region.value);
                if (plate && plate.value) q += '&plate=' + encodeURIComponent(plate.value);
                if (useBtn) {
                    useBtn.hidden = !words;
                    useBtn.setAttribute('href', claimBase + q);
                }
                if (leaveBtn) {
                    leaveBtn.hidden = !words;
                    var pendingHref = pendingBase + q;
                    leaveBtn.setAttribute('href', root.BC_LOGGED_IN ? pendingHref : (loginBase + '?next=' + encodeURIComponent(pendingHref)));
                }
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
        var prefixEl = $('bcPlatePrefix'), prefixHint = $('bcPlatePrefixHint');
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
            applyPrefix(c);
        }

        function applyPrefix(c) {
            var prefix = c ? platePrefix(c, plate && plate.value || '', region && region.value || '') : '';
            var label = c ? platePrefixLabel(c, plate && plate.value || '', region && region.value || '') : '';
            if (shell) shell.classList.toggle('is-prefixed', !!label);
            if (prefixEl) {
                prefixEl.textContent = label;
                prefixEl.hidden = !label;
            }
            if (prefixHint) {
                prefixHint.textContent = prefix
                    ? trv('bc_plate_prefix_hint', 'The first word “{prefix}” is already filled in from the country or state. Type only the rest of the plate.', { prefix: prefix })
                    : '';
            }
            if (plate) {
                if (prefix) plate.setAttribute('aria-describedby', 'bcPlatePrefixHint');
                else plate.removeAttribute('aria-describedby');
            }
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
            var formatted = c && plate ? formatPlate(c, plate.value, region && region.value) : (plate ? plate.value.toUpperCase().replace(/[^A-Z0-9]/g, '') : '');
            if (plate && formatted !== plate.value) setFormattedValue(plate, formatted);
            applyPrefix(c);
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
            if (root.BC_CLAIM_FORM && !root.BC_PENDING_FORM) {
                var card = $('bcTakenCard');
                if (card && card.getAttribute('data-words') !== words) {
                    card.hidden = true;
                } else if (card && words) {
                    card.hidden = false;
                }
                if (!ok) clearTakenActions();
                checkTaken(ok ? words : '');
            }
            if (root.BC_PENDING_FORM) {
                var msg = $('bcPendingMsg');
                var hasMsg = !msg || String(msg.value || '').trim() !== '';
                if (next) next.disabled = !ok || !hasMsg;
            }

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

        // Live "already claimed?" check (same endpoint as the homepage lock, rate limited per
        // session). Only yes/no for the exact three words. Next stays enabled: when the plate is
        // the visitor's own, the server sends them on after login.
        var takenTimer = null, takenFor = '', takenCache = {};
        function clearTakenActions() {
            var box = $('bcTakenActions');
            if (!box) return;
            box.hidden = true;
            while (box.firstChild) box.removeChild(box.firstChild);
        }
        function addTakenLink(box, href, label, className) {
            var a = document.createElement('a');
            a.href = href;
            a.className = className;
            a.textContent = label;
            box.appendChild(a);
        }
        function renderTakenActions(words) {
            var box = $('bcTakenActions');
            if (!box) {
                box = document.createElement('div');
                box.id = 'bcTakenActions';
                box.className = 'bc-taken-actions';
                if (out && out.parentNode) out.parentNode.appendChild(box);
                else return;
            }
            while (box.firstChild) box.removeChild(box.firstChild);
            box.className = 'bc-taken-actions';
            addTakenLink(box, '../3wid_forward.php?threewords=' + encodeURIComponent(words),
                tr('bc_send_to_plate', 'Send a message to this plate'), 'btn btn-primary');
            if (!root.BC_LOGGED_IN) {
                var next = 'claim.php?words=' + encodeURIComponent(words);
                addTakenLink(box, 'login.php?next=' + encodeURIComponent(next),
                    tr('bc_taken_login', 'Is it yours? Log in'), 'btn btn-ghost');
            }
            var retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn btn-ghost';
            retry.textContent = tr('bc_try_another', 'Try another plate');
            retry.addEventListener('click', function () {
                if (plate) {
                    plate.value = '';
                    plate.focus({ preventScroll: true });
                }
                clearTakenActions();
                var card = $('bcTakenCard');
                if (card) card.hidden = true;
                update();
            });
            box.appendChild(retry);
            var dispute = document.createElement('p');
            dispute.className = 'bc-dispute';
            var dlink = document.createElement('a');
            dlink.href = 'dispute.php?words=' + encodeURIComponent(words);
            dlink.textContent = tr('bc_dispute_link', 'Dispute ownership');
            dispute.appendChild(dlink);
            box.appendChild(dispute);
            box.hidden = false;
        }
        function showTaken(words, exists) {
            if (!out || out.textContent !== words) return;
            if (!exists) {
                clearTakenActions();
                return;
            }
            out.textContent = trv('bc_taken', '"{words}" is already in use', { words: words }) + '. ' +
                tr('bc_taken_p', 'Someone already uses these three words.');
            out.classList.add('is-bad'); out.classList.remove('is-ok');
            if (shell) { shell.classList.remove('is-ok'); shell.classList.add('is-bad'); }
            var card = $('bcTakenCard');
            if (card && !card.hidden && card.getAttribute('data-words') === words) {
                clearTakenActions();
                return;
            }
            renderTakenActions(words);
        }
        function checkTaken(words) {
            if (takenTimer) { clearTimeout(takenTimer); takenTimer = null; }
            takenFor = words;
            if (!words || !root.fetch) return;
            if (words in takenCache) { showTaken(words, takenCache[words]); return; }
            takenTimer = setTimeout(function () {
                var body = new URLSearchParams();
                body.set('threewords', words);
                body.set('format', 'json');
                fetch(root.BC_EXISTS_URL || '../3wid_exists.php', { method: 'POST', body: body, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (j) {
                        if (!j) return;
                        takenCache[words] = !!j.exists;
                        if (takenFor === words) showTaken(words, !!j.exists);
                    })
                    .catch(function () {});
            }, 400);
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
        var pendingMsg = $('bcPendingMsg');
        if (pendingMsg) pendingMsg.addEventListener('input', validate);
        fillRegions(current(), region && region.value);
        update();
    }

})(typeof window !== 'undefined' ? window : globalThis);
