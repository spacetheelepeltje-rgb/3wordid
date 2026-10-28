// Texts for the current language come from window.T3 (set by the page, see php/i18n.php).
function tr(key, fallback) {
    return (window.T3 && window.T3[key]) || fallback;
}


const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    
function validateEmail(email) {
	return emailRegex.test(String(email).toLowerCase());
}


function validateURL(url) {
    url = url.replace(/^http:\/\//, 'https://');
    if (!url.match(/^https:\/\//)) url = 'https://' + url;
    try {
        const parsed = new URL(url);
        return parsed.href;
    } catch {
        return false;
    }
}

// Live recipient check (3wid_recipient_check.php). Privacy: only complete, exact three
// words are sent, and the answer is only yes/no; no suggestions or partial matches.
// States of the field: neutral (typing / not three words), checking, ok (green), bad (red).
// Send stays disabled unless the recipient is known to exist; if the check itself is not
// possible (limit reached, network), sending is allowed and the server checks it.
var recipientTimer = null;
var recipientRequest = 0;
var recipientCache = {};

function recipientState(state, text) {
    var $f = $('#threeword'), $s = $('#helperText');
    $f.removeClass('rcpt-ok rcpt-bad rcpt-checking').removeAttr('aria-invalid');
    $s.removeClass('rcpt-ok rcpt-bad rcpt-checking');
    if (state !== 'neutral') {
        $f.addClass('rcpt-' + state);
        $s.addClass('rcpt-' + state);
    }
    if (state === 'bad') { $f.attr('aria-invalid', 'true'); }
    $s.text(text || '');
    $('#3widForm').attr('data-recipient', state);
    // ok: send; unknown (check not possible): send, the server checks
    $('#submitBtn').prop('disabled', !(state === 'ok' || state === 'unknown'));
}

function recipientWords() {
    return $('#threeword').val().replace(/\s+/g, ' ').trim().toLowerCase();
}

function recipientComplete(words) {
    return /^[a-z0-9]+ [a-z0-9]+ [a-z0-9]+$/.test(words) && words.length <= 80;
}

function checkRecipient() {
    clearTimeout(recipientTimer);
    var words = recipientWords();
    recipientRequest++;

    if (!recipientComplete(words)) {
        // Still typing, or not three words of letters/digits: no check, no colour
        var parts = words === '' ? [] : words.split(' ');
        var badChars = parts.some(function (w) { return /[^a-z0-9]/.test(w); });
        recipientState('neutral', (parts.length > 3 || badChars) ? tr('invalid', '3wid invalid') : '');
        return;
    }
    if (recipientCache.hasOwnProperty(words)) {
        showRecipient(recipientCache[words]);
        return;
    }
    recipientState('checking', tr('checking', 'Checking…'));
    var mine = recipientRequest;
    recipientTimer = setTimeout(function () {
        $.ajax({ type: 'POST', url: '3wid_recipient_check.php', data: { threewords: words }, dataType: 'json' })
            .done(function (answer) {
                if (mine !== recipientRequest) { return; } // words changed in the meantime
                var exists = !!(answer && answer.exists === true);
                recipientCache[words] = exists;
                showRecipient(exists);
            })
            .fail(function () {
                if (mine !== recipientRequest) { return; }
                recipientState('unknown', tr('check_later', 'This 3WordID can’t be checked right now. It is checked when you send.'));
            });
    }, 400);
}

function showRecipient(exists) {
    if (exists) {
        recipientState('ok', tr('ok_recipient', 'recipient exists'));
    } else {
        recipientState('bad', tr('bad_recipient', 'This 3WordID does not exist'));
    }
}

$('#threeword').on('input', checkRecipient);

$(document).ready(function () {

    // Check the form, then let the browser send it as a normal POST
    // (with the action token). There is no subject field: the server makes the title
    // from the start of the message.
    $('#3widForm').on('submit', function (event) {
        var form = this;
        var threeword = $.trim($('#threeword').val() || '');
        var message = $.trim($('#message').val() || '');

        if (form.getAttribute('data-sending') === '1') {
            // Already on its way: do not send it twice
            event.preventDefault();
            return;
        }
        if (threeword === '') {
            event.preventDefault();
            $('#helperText').text(tr('need_3wid', 'Please provide an 3WordID'));
            $('#threeword').focus();
            return;
        }
        var rstate = form.getAttribute('data-recipient');
        if (rstate !== 'ok' && rstate !== 'unknown') {
            // Recipient not (yet) known to exist: no sending
            event.preventDefault();
            if (rstate !== 'bad' && rstate !== 'checking') { checkRecipient(); }
            $('#threeword').focus();
            return;
        }
        if (message === '') {
            event.preventDefault();
            $('#helperText').text(tr('need_message', 'Please provide a message'));
            $('#message').focus();
            return;
        }

        form.setAttribute('data-sending', '1');
        $('#submitBtn').prop('disabled', true).attr('aria-busy', 'true');
        $('#helperText').text(tr('clicked', 'Sending…'));
    });

    // Back/forward cache: a page shown again must be sendable again
    window.addEventListener('pageshow', function () {
        $('#3widForm').removeAttr('data-sending');
        $('#submitBtn').removeAttr('aria-busy');
        checkRecipient(); // enables Send again only for a recipient that exists
    });
});
