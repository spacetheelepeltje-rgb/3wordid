// Texts for the current language come from window.T3 (set by the page, see php/i18n.php).
var tr = window.tr || function (key, fallback) {
    return (window.T3 && window.T3[key]) || fallback;
};

// Add form (3wid_add.php). The server (3wid_insert.php) does the same checks.
//  - Free users: letters only. Paying users: letters and digits.
//  - The Message field is only required when the 3WordID shows it, so not for
//    "Use forward URL" or "Link to chat".
//  - "Message with link": the message is shown with a link below it, so the
//    Message, URL and Link title fields are all required.

function isPaying() {
    return $('#3widForm').attr('data-paying') === '1';
}

// Why the typed 3WordID can't be used ('' = fine so far). Incomplete input
// (fewer than three words) is not an error while typing.
function threewordProblem(threewords, complete) {
    var value = threewords.trim();
    if (value === '') {
        return complete ? tr('add_need_3wid', 'Please provide a 3WordID') : '';
    }
    var words = value.split(/\s+/);
    var paying = isPaying();
    for (var i = 0; i < words.length; i++) {
        if (!paying && /[0-9]/.test(words[i])) {
            return tr('add_err_digits', 'Numbers are only available with a paid plan.');
        }
        if (!(paying ? /^[a-zA-Z0-9]+$/ : /^[a-zA-Z]+$/).test(words[i])) {
            return paying
                ? tr('add_err_chars_paid', 'Use only letters (a-z) and numbers (0-9).')
                : tr('add_err_chars', 'Use only letters (a-z).');
        }
    }
    if (words.length > 3 || (complete && words.length !== 3)) {
        return tr('max_three', 'Maximum is three words');
    }
    return '';
}

function showFieldError(field, box, text, withPlansLink) {
    var $box = $(box);
    if (text === '') {
        $box.attr('hidden', true).empty();
        $(field).removeAttr('aria-invalid');
        return;
    }
    $box.text(text);
    if (withPlansLink) {
        $box.append(' ').append($('<a>', { href: 'plans.php', text: tr('add_see_plans', 'See plans') }));
    }
    $box.removeAttr('hidden');
    $(field).attr('aria-invalid', 'true');
}

function noteRequired() {
    // ("Link to chat" is no longer on this page: it is set on the edit page)
    var chat = document.getElementById('emailform');
    return !document.getElementById('linkthruflag').checked && !(chat && chat.checked);
}

function msglinkChosen() {
    var box = document.getElementById('msglinkflag');
    return !!(box && box.checked);
}

// Same idea as the check in 3wid_3wid.js: false when the URL can't be used
function validateURL(url) {
    url = url.trim();
    if (url === '') { return false; }
    url = url.replace(/^http:\/\//i, 'https://');
    if (!/^https:\/\//i.test(url)) { url = 'https://' + url; }
    try {
        var parsed = new URL(url);
        return /\./.test(parsed.hostname) ? parsed.href : false;
    } catch (e) {
        return false;
    }
}

// "(optional)" next to the Message label when forward or chat is chosen;
// the Link title field and the "Link URL" label only for "Message with link"
function updateNoteOptional() {
    var required = noteRequired();
    $('#notificationOpt').prop('hidden', required);
    if (!required) {
        showFieldError('#notification', '#notificationError', '');
    }
    var msglink = msglinkChosen();
    $('#linkTitleField').prop('hidden', !msglink);
    var $label = $('#linkthruLabel');
    $label.text($label.attr(msglink ? 'data-msglink' : 'data-forward') || $label.text());
    if (!msglink) {
        showFieldError('#link_title', '#linkTitleError', '');
    }
    if (!msglink && !document.getElementById('linkthruflag').checked) {
        showFieldError('#linkthru', '#linkthruError', '');
    }
}

function setButton(color) {
    $('#submitBtn').css({ 'background-color': color || '', 'color': color ? 'white' : '' });
}

var existsRequest = 0;

function checkThreeword() {
    var threewords = $('#threeword').val();
    var problem = threewordProblem(threewords, false);
    var digits = problem !== '' && !isPaying() && /[0-9]/.test(threewords);
    showFieldError('#threeword', '#threewordError', problem, digits);

    var words = threewords.trim() === '' ? [] : threewords.trim().split(/\s+/);
    if (problem !== '') {
        $('#helperText').text('');
        setButton('grey');
        return;
    }
    if (words.length !== 3) {
        $('#helperText').text('');
        setButton('');
        return;
    }
    var mine = ++existsRequest;
    $.ajax({ type: 'POST', url: '3wid_exists.php', data: { 'threewords': threewords } })
        .done(function (response) {
            if (mine !== existsRequest) { return; } // an older answer
            if (response == 'exists') {
                $('#helperText').text(tr('add_exists', '3 word id exists'));
                setButton('red');
            } else {
                $('#helperText').text(tr('add_new', '3 word id is new'));
                setButton('green');
            }
        });
}

$(document).ready(function () {

    $('#threeword').on('input', checkThreeword);
    $('#linkthruflag, #msglinkflag').on('change', updateNoteOptional);
    $('#notification').on('input', function () {
        if ($('#notification').val().trim() !== '') {
            showFieldError('#notification', '#notificationError', '');
        }
    });
    $('#linkthru').on('input', function () {
        showFieldError('#linkthru', '#linkthruError', '');
    });
    $('#link_title').on('input', function () {
        if ($('#link_title').val().trim() !== '') {
            showFieldError('#link_title', '#linkTitleError', '');
        }
    });
    updateNoteOptional();
    if ($('#threeword').val().trim() !== '') {
        checkThreeword();
    }

    $('#3widForm').on('submit', function (event) {
        event.preventDefault();
        var form = this;
        var threeword = $('#threeword').val();
        var linkthru = $('#linkthru').val();
        var notification = $('#notification').val();

        $('#helperText').text('');

        var problem = threewordProblem(threeword, true);
        if (problem !== '') {
            showFieldError('#threeword', '#threewordError', problem, !isPaying() && /[0-9]/.test(threeword));
            $('#threeword').trigger('focus');
            return;
        }

        if (linkthru.toLowerCase().includes('3wordid.com')) {
            $('#helperText').text(tr('add_no_forward', 'You can not use a 3WordID forward'));
            return;
        }

        if (noteRequired() && notification.trim() === '') {
            showFieldError('#notification', '#notificationError', tr('need_notification', 'Please fill in the Message field'));
            $('#notification').trigger('focus');
            return;
        }

        var msglink = msglinkChosen();
        if (document.getElementById('linkthruflag').checked && linkthru.trim() === '') {
            $('#helperText').text(tr('bad_url', 'URL missing or invalid'));
            $('#linkthru').trigger('focus');
            return;
        }
        if (msglink && !validateURL(linkthru)) {
            showFieldError('#linkthru', '#linkthruError', tr('bad_url', 'URL missing or invalid'));
            $('#linkthru').trigger('focus');
            return;
        }

        if (msglink && $('#link_title').val().trim() === '') {
            showFieldError('#link_title', '#linkTitleError', tr('need_link_title', 'Please fill in the Link title field'));
            $('#link_title').trigger('focus');
            return;
        }

        $('#helperText').text(tr('clicked', 'Clicked submit'));
        form.submit(); // native submit: does not run this handler again
    });
});
