// Texts for the current language come from window.T3 (set by the page, see php/i18n.php).
function tr(key, fallback) {
    return (window.T3 && window.T3[key]) || fallback;
}


const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    
function validateEmail(email) {
	return emailRegex.test(String(email).toLowerCase());
}

function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
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

// Check the recipient's three words while they are typed, pasted or filled in
function checkRecipient() {
    var threewords = $('#threeword').val().trim();
    var words = threewords.split(/\s+/);

    if (words.length !== 3) {
        $('#threeword').css('color', '#000');
        $('#helperText').text(threewords === '' ? '' : tr('invalid', '3wid invalid'));
        return;
    }

    var invalid = 0;
    for (var i = 0; i < words.length; i++) {
        if (containsDigitsOrSpecialCharacters(words[i])) { invalid++; }
    }
    if (invalid > 0) {
        $('#threeword').css('color', '#000');
        $('#helperText').text(tr('invalid', '3wid invalid'));
        return;
    }

    $('#threeword').css('color', 'green');
    $.ajax({
        type: 'POST',
        url: '3wid_exists.php',
        data: { 'threewords': threewords },
        success: function (response) {
            // Ignore an answer for words that were changed in the meantime
            if ($('#threeword').val().trim() !== threewords) { return; }
            $('#helperText').text(response == 'exists' ? tr('ok_recipient', 'recipient exists') : tr('bad_recipient', 'invalid recipient'));
        }
    });
}

$('#threeword').on('input keyup', checkRecipient);

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
        $('#submitBtn').prop('disabled', false).removeAttr('aria-busy');
    });
});
