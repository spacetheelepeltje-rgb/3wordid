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
    var subject = document.querySelector('.composer .c-subject');

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
        var d = { m: text.value, s: subject ? subject.value : '', t: subject && subject.getAttribute('data-touched') ? 1 : 0, to: to ? to.value : null };
        try {
            if (d.m === '' && d.s === '') { st.removeItem(draftKey); } else { st.setItem(draftKey, JSON.stringify(d)); }
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
                if (subject && subject.value === '' && typeof d.s === 'string') { subject.value = d.s; }
                if (subject && d.t) { subject.setAttribute('data-touched', '1'); }
                if (to && to.value === '' && typeof d.to === 'string') { to.value = d.to; }
            } catch (e) { /* bad data: ignore */ }
        }
    }

    if (subject) {
        // The title is required by the form. Fill it from the start of the message
        // until the person types their own.
        subject.addEventListener('input', function () { subject.setAttribute('data-touched', '1'); saveDraft(); });
    }

    if (text) {
        text.addEventListener('input', function () {
            grow();
            if (subject && !subject.getAttribute('data-touched')) {
                var first = text.value.replace(/\s+/g, ' ').trim();
                subject.value = first.length > 40 ? first.slice(0, 39).trim() + '…' : first;
            }
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
})();
