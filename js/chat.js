// Small helpers for the chat style message pages. Plain JavaScript, no libraries.
(function () {
    'use strict';

    // Open scrolled to the newest message (not counting the small footer below it)
    function scrollToEnd() {
        var area = document.querySelector('.chat-scroll[data-scroll="end"]');
        if (!area) { return; }
        var items = area.querySelectorAll('.row, .pill-wrap');
        var last = items.length ? items[items.length - 1] : null;
        if (!last) { return; }
        var target = last.offsetTop + last.offsetHeight - area.clientHeight + 16;
        area.scrollTop = Math.max(0, target);
    }

    var text = document.querySelector('.composer .c-text');

    function grow() {
        if (!text) { return; }
        text.style.height = 'auto';
        text.style.height = Math.min(text.scrollHeight, 140) + 'px';
    }

    // Draft: keep what was typed in this tab (sessionStorage, never sent to the server),
    // so switching the language, which reloads the page, does not lose the text.
    var form = text ? text.form : null;
    var to = form ? document.querySelector('input#threeword[type="text"]') : null;
    var draftPrefix = 'chat-draft:' + location.pathname + '?';
    var draftKey = (function () {
        var q = new URLSearchParams(location.search);
        q.delete('lang');
        q.sort();
        return draftPrefix + q.toString();
    })();
    function store() { try { return window.sessionStorage; } catch (e) { return null; } }
    function saveDraft() {
        var st = store();
        if (!st || !text) { return; }
        var d = { m: text.value, to: to ? to.value : null };
        try {
            if (d.m === '') { st.removeItem(draftKey); } else { st.setItem(draftKey, JSON.stringify(d)); }
        } catch (e) { /* storage full or blocked: ignore */ }
    }
    function clearDrafts() {
        var st = store();
        if (!st) { return; }
        try {
            for (var i = st.length - 1; i >= 0; i--) {
                var k = st.key(i);
                if (k && k.indexOf(draftPrefix) === 0) { st.removeItem(k); }
            }
        } catch (e) { /* ignore */ }
    }
    if (form && form.getAttribute('data-sent') === '1') {
        // The message was sent: forget the drafts of this page
        clearDrafts();
    } else if (text) {
        var st = store(), raw = null;
        try { raw = st ? st.getItem(draftKey) : null; } catch (e) { raw = null; }
        if (raw) {
            try {
                var d = JSON.parse(raw);
                if (text.value === '' && typeof d.m === 'string') { text.value = d.m; }
                // Drafts saved before the subject field was removed may hold a subject (d.s).
                // Keep it only when the person typed their own and the message does not
                // already start with it, so nothing they wrote is lost.
                if (d.t && typeof d.s === 'string' && d.s.trim() !== '') {
                    var subj = d.s.trim();
                    var body = text.value.replace(/\s+/g, ' ').trim().toLowerCase();
                    if (body.indexOf(subj.replace(/[\u2026.\s]+$/, '').toLowerCase()) !== 0) {
                        text.value = subj + (text.value ? '\n' + text.value : '');
                    }
                }
                if (to && to.value === '' && typeof d.to === 'string') { to.value = d.to; }
            } catch (e) { /* bad data: ignore */ }
        }
    }

    if (text) {
        text.addEventListener('input', function () {
            grow();
            saveDraft();
        });

        // Enter sends, Shift+Enter makes a new line. Only with a real keyboard:
        // on a phone Enter stays a new line and the send button sends.
        var hasKeyboard = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
        text.addEventListener('keydown', function (e) {
            if (!hasKeyboard || e.key !== 'Enter' || e.shiftKey || e.isComposing) { return; }
            e.preventDefault();
            var btn = text.form && text.form.querySelector('.send');
            if (btn) { btn.click(); }
        });
        if (to) { to.addEventListener('input', saveDraft); }
        grow();
    }

    window.addEventListener('load', scrollToEnd);
    scrollToEnd();

    // Tap a profile photo to see it larger (Esc, tap or the close button closes it)
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-photo]') : null;
        if (!btn) { return; }
        e.preventDefault();
        var img = btn.querySelector('img');
        if (!img) { return; } // photo failed to load: nothing to show
        var box = document.createElement('div');
        box.className = 'photo-box';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-label', btn.getAttribute('aria-label') || '');
        var big = document.createElement('img');
        big.src = btn.getAttribute('data-photo');
        big.alt = btn.getAttribute('aria-label') || '';
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'close';
        close.setAttribute('aria-label', btn.getAttribute('data-close') || 'Close');
        close.textContent = '\u00d7';
        box.appendChild(big);
        box.appendChild(close);
        function shut() {
            document.removeEventListener('keydown', onKey);
            if (box.parentNode) { box.parentNode.removeChild(box); }
            btn.focus();
        }
        function onKey(ev) { if (ev.key === 'Escape') { shut(); } }
        box.addEventListener('click', shut);
        document.addEventListener('keydown', onKey);
        document.body.appendChild(box);
        close.focus();
    });
})();

// Two-way chat: while the tab is visible, look for new messages every ~15 s by
// fetching this same (owner-only) page and swapping in the message list when it
// changed. The composer is left alone, so typing is never interrupted.
(function () {
    var area = document.querySelector('.chat-scroll[data-refresh]');
    if (!area || !window.fetch || !window.DOMParser) { return; }
    var every = Math.max(10, parseInt(area.getAttribute('data-refresh'), 10) || 15) * 1000;
    var busy = false;
    function lastId(root) {
        var rows = root.querySelectorAll('.row[data-id]');
        return rows.length ? rows[rows.length - 1].getAttribute('data-id') + ':' + rows.length : '';
    }
    function nearEnd() { return area.scrollHeight - area.scrollTop - area.clientHeight < 80; }
    function check() {
        if (busy || document.visibilityState !== 'visible') { return; }
        busy = true;
        fetch(window.location.href.split('#')[0], { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return (r.ok && !r.redirected) ? r.text() : ''; })
            .then(function (html) {
                if (!html) { return; }
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.querySelector('.chat-scroll[data-refresh]');
                if (!fresh || lastId(fresh) === lastId(area)) { return; }
                var stick = nearEnd();
                area.innerHTML = fresh.innerHTML;
                if (stick) { area.scrollTop = area.scrollHeight; }
            })
            .catch(function () {})
            .then(function () { busy = false; });
    }
    setInterval(check, every);
    document.addEventListener('visibilitychange', check);
})();

// Top bar language menu (<details>): close it on a click outside or with Esc
(function () {
    var menu = document.querySelector('.chat-topbar .tb-lang');
    if (!menu) { return; }
    document.addEventListener('click', function (e) {
        if (menu.open && !menu.contains(e.target)) { menu.open = false; }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && menu.open) {
            menu.open = false;
            var s = menu.querySelector('summary');
            if (s) { s.focus(); }
        }
    });
})();
