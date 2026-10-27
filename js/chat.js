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

    if (subject) {
        // The title is required by the form. Fill it from the start of the message
        // until the person types their own.
        subject.addEventListener('input', function () { subject.setAttribute('data-touched', '1'); });
    }

    if (text) {
        text.addEventListener('input', function () {
            grow();
            if (subject && !subject.getAttribute('data-touched')) {
                var first = text.value.replace(/\s+/g, ' ').trim();
                subject.value = first.length > 40 ? first.slice(0, 39).trim() + '…' : first;
            }
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
        grow();
    }

    window.addEventListener('load', scrollToEnd);
    scrollToEnd();
})();
