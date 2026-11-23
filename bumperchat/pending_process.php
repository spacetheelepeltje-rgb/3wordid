<?php
/**
 * Store a message for an unregistered plate, or (if the plate has been
 * claimed in the meantime) send it as a normal message.
 * POST only. CSRF: csrf_token (Bumperchat forms) and/or action_token (chat composer).
 */

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';
require_once __DIR__ . '/../php/chat.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

function pending_process_back(string $to_conv, string $form, bool $to_conversation): void
{
    header('Location: ' . ($to_conversation && $to_conv !== '' ? $to_conv : $form));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: pending_send.php');
    exit;
}

$user = current_user_from_session(function_exists('get_db_conn') ? get_db_conn() : $conn ?? null);
if (!$user) {
    $qs = [];
    foreach (['country', 'region', 'plate', 'words', 'from_id'] as $k) {
        if (isset($_POST[$k]) && (string) $_POST[$k] !== '') {
            $qs[$k] = (string) $_POST[$k];
        }
    }
    $next = 'pending_send.php' . ($qs ? ('?' . http_build_query($qs, '', '&', PHP_QUERY_RFC3986)) : '');
    header('Location: login.php?next=' . rawurlencode($next));
    exit;
}
if ((int) ($user['privacy_consent'] ?? 0) !== 1) {
    header('Location: ../3wid_privacy_consent.php?from=bumperchat');
    exit;
}

$csrf_ok = function_exists('bumperchat_csrf_ok') && bumperchat_csrf_ok();
$token_ok = function_exists('check_action_token') && check_action_token($_POST['action_token'] ?? '');
if (!$csrf_ok && !$token_ok) {
    error_log('pending_process: missing or wrong token for user ' . $user['id']);
    $_SESSION['pending_error'] = 'pending.err_csrf';
    header('Location: pending_send.php');
    exit;
}

$message = trim((string) ($_POST['message'] ?? ''));
$from_id = (int) ($_POST['from_id'] ?? 0);
$to_conversation = (($_POST['return'] ?? '') === 'conv');

if ($message === '') {
    $_SESSION['pending_error'] = 'chat.err_empty';
    $_SESSION['chat_draft'] = $message;
    header('Location: pending_send.php');
    exit;
}

$owned = db_3wordid_owned_by($from_id, (int) $user['id']);
if (!$owned) {
    error_log('pending_process: user ' . $user['id'] . ' tried to send from 3wid ' . $from_id);
    $_SESSION['pending_error'] = 'mp.session';
    header('Location: chat.php');
    exit;
}

$target = pending_resolve_target($_POST);
$words = $target['ok'] ? $target['words'] : pending_normalize_words((string) ($_POST['words'] ?? $_POST['threeword'] ?? ''));

// Reply from an existing pending conversation: country/plate may be omitted
// when the sender already has a row for these words.
if (!$target['ok'] && $words !== '') {
    $existing = pending_conversations_for_sender((int) $user['id'], $from_id);
    $group = $existing[pending_key($words)] ?? null;
    if ($group) {
        $target = [
            'ok' => true,
            'error' => '',
            'words' => $words,
            'country' => (string) ($group['country'] ?? ''),
            'region' => (string) ($group['region'] ?? ''),
            'plate' => (string) ($group['plate'] ?? ''),
        ];
    }
}

$form_q = [];
if (!empty($target['country'])) {
    $form_q['country'] = $target['country'];
}
if (!empty($target['region'])) {
    $form_q['region'] = $target['region'];
}
if (!empty($target['plate'])) {
    $form_q['plate'] = $target['plate'];
}
if ($words !== '') {
    $form_q['words'] = $words;
}
$form_q['from_id'] = (string) $from_id;
$form_url = 'pending_send.php' . ($form_q ? ('?' . http_build_query($form_q, '', '&', PHP_QUERY_RFC3986)) : '');
$conv_url = '../3wid_list_messages.php?wid_id=' . $from_id . '&with=' . rawurlencode(pending_key($words));

if (!$target['ok']) {
    $_SESSION['pending_error'] = $target['error'] !== '' ? $target['error'] : 'bc.err_country';
    $_SESSION['chat_draft'] = $message;
    pending_process_back($conv_url, $form_url, $to_conversation);
}

$words = $target['words'];

// If the plate has been claimed, deliver any leftover waiting rows and
// send this one as a normal message.
$claimed = function_exists('db_3wordid_get_threeword') ? db_3wordid_get_threeword($words) : null;
if (is_array($claimed) && !empty($claimed['id'])) {
    pending_deliver_for_words($words, (int) $claimed['id'], (int) $claimed['user_id']);
    $error = function_exists('chat_recipient_error') ? chat_recipient_error($claimed) : '';
    if ($error === '' && function_exists('chat_access_error')) {
        $error = chat_access_error($owned, $claimed);
    }
    if ($error === '') {
        $error = db_3wordid_message_rate_error((int) $claimed['id'], $from_id);
    }
    if ($error !== '') {
        $_SESSION['pending_error'] = $error;
        $_SESSION['chat_error'] = $error;
        $_SESSION['chat_draft'] = $message;
        $normal = '../3wid_messageform.php?threeword_id=' . $from_id . '&threeword=' . rawurlencode($claimed['threeword']);
        pending_process_back(
            '../3wid_list_messages.php?wid_id=' . $from_id . '&with=' . rawurlencode('w' . (int) $claimed['id']),
            $normal,
            $to_conversation
        );
    }
    $title = pending_title_from_message($message);
    $ok = db_3wordid_messageform_insert((int) $user['id'], (int) $claimed['id'], $from_id, (int) $claimed['user_id'], $title, $message, 1);
    if ($ok) {
        $_SESSION['chat_conv_sent'] = $from_id . '|w' . (int) $claimed['id'];
        $_SESSION['pending_notice'] = 'pending.already_claimed';
        header('Location: ../3wid_list_messages.php?wid_id=' . $from_id . '&with=' . rawurlencode('w' . (int) $claimed['id']));
        exit;
    }
    $_SESSION['pending_error'] = 'pending.err_schema';
    $_SESSION['chat_draft'] = $message;
    pending_process_back($conv_url, $form_url, $to_conversation);
}

$result = pending_insert([
    'sender_user_id' => (int) $user['id'],
    'from_wid_id' => $from_id,
    'threeword' => $words,
    'country' => $target['country'],
    'region' => $target['region'],
    'plate' => $target['plate'],
    'message' => $message,
]);
if (!$result['ok']) {
    $_SESSION['pending_error'] = $result['error'] !== '' ? $result['error'] : 'pending.err_schema';
    $_SESSION['chat_draft'] = $message;
    pending_process_back($conv_url, $form_url, $to_conversation);
}

$_SESSION['pending_notice'] = 'pending.sent';
$_SESSION['pending_remaining'] = (int) $result['remaining'];
if ($to_conversation) {
    $_SESSION['chat_conv_sent'] = $from_id . '|' . pending_key($words);
    header('Location: ' . $conv_url);
    exit;
}
header('Location: ' . $form_url);
exit;
