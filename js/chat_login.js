/*
 * Chat login helpers (no jQuery), used by 3wid_chat_login.php, the "Three words" box on
 * login/options.php and 3wid_chat_settings.php.
 *
 * 1. Three-words field (input[data-exists-url]): turns green when the typed words are an
 *    existing 3WordID. Uses the same check as the homepage lock (3wid_exists.php, rate
 *    limited per session). Privacy: only a yes/no for the exact, complete three words;
 *    nothing is asked before there are three words, and there are never suggestions.
 * 2. Password rules (#clPwRules): ticks off the rules while typing. Only a hint; the server
 *    checks the same rules (chat_password_error in php/chat_login.php).
 */
(function () {
    'use strict';

    function normalize(v) {
        return String(v || '').trim().replace(/\s+/g, ' ').toLowerCase();
    }
    var THREE = /^[a-z0-9]+ [a-z0-9]+ [a-z0-9]+$/;

    Array.prototype.forEach.call(document.querySelectorAll('input[data-exists-url]'), function (input) {
        var url = input.getAttribute('data-exists-url');
        var status = document.getElementById(input.getAttribute('data-status') || '');
        var timer = null, seq = 0, last = null;

        function show(ok) {
            input.classList.toggle('is-valid', ok);
            if (status) {
                status.hidden = !ok;
            }
        }
        function check() {
            var words = normalize(input.value);
            if (!THREE.test(words) || words.length > 80) { last = null; seq++; show(false); return; }
            if (words === last) { return; }
            last = words;
            var mine = ++seq;
            var body = 'format=json&threewords=' + encodeURIComponent(words);
            fetch(url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body
            }).then(function (r) {
                if (!r.ok) { throw new Error(String(r.status)); }
                return r.json();
            }).then(function (j) {
                if (mine === seq) { show(!!(j && j.exists)); }
            }).catch(function () {
                if (mine === seq) { last = null; show(false); } // e.g. rate limited: just no green
            });
        }
        input.addEventListener('input', function () {
            clearTimeout(timer);
            if (!THREE.test(normalize(input.value))) { last = null; seq++; show(false); return; }
            timer = setTimeout(check, 350);
        });
        input.addEventListener('blur', function () { clearTimeout(timer); check(); });
        if (input.value) { check(); }
    });

    var rules = document.getElementById('clPwRules');
    var pw = document.getElementById('password');
    if (rules && pw) {
        var min = parseInt(rules.getAttribute('data-min'), 10) || 8;
        var tests = {
            len: function (v) { return Array.from(v).length >= min; },
            letter: function (v) { return /\p{L}/u.test(v); },
            digit: function (v) { return /\p{N}/u.test(v); },
            symbol: function (v) { return /[^\p{L}\p{N}\s]/u.test(v); }
        };
        var update = function () {
            Array.prototype.forEach.call(rules.querySelectorAll('li[data-rule]'), function (li) {
                var t = tests[li.getAttribute('data-rule')];
                li.classList.toggle('ok', !!(t && t(pw.value)));
            });
        };
        pw.addEventListener('input', update);
        update();
    }
})();
