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

$(document).ready(function () {

    // Check the form, then let the browser send it as a normal POST.
    // There is no subject field: the server makes the title from the message.
    $('#3widForm').on('submit', function (event) {
        var form = this;
        var threeword = $.trim($('#threeword').val() || '');
        var message = $.trim($('#message').val() || '');

        if (form.getAttribute('data-sending') === '1') {
            // Already on its way: do not send it twice
            event.preventDefault();
            return;
        }
        if (message === '') {
            event.preventDefault();
            $('#helperText').text(tr('need_message', 'Please provide a message'));
            $('#message').focus();
            return;
        }
        if (threeword === '') {
            event.preventDefault();
            $('#helperText').text(tr('need_3wid', 'Please provide an 3WordID'));
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
