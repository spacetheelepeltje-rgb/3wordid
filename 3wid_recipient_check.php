<?php
/*
 * Live check for the recipient field of the message form (3wid_messageform.php).
 *
 * POST threewords=<three words>  ->  {"exists": true|false}
 *
 * Privacy: the answer is only yes/no for the exact, complete three words. There are no
 * suggestions, no autocomplete and no partial matches, and nothing about the owner or
 * the 3WordID is returned. "Exists" means a real recipient: the 3WordID is found, is
 * switched on and is not the visitor placeholder (same rule as sending,
 * chat_recipient_error()). Logged-in members only, with a per-session limit so the
 * check can't be used to go through many word combinations.
 */

require_once 'php/functions.php';
require_once 'login/config.php';
require_once 'php/chat.php';

const RCPT_CHECK_MAX = 60;       // checks per member session ...
const RCPT_CHECK_WINDOW = 300;   // ... per 5 minutes

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex');

function rcpt_answer(int $status, bool $exists): void
{
    http_response_code($status);
    echo json_encode(['exists' => $exists]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    rcpt_answer(405, false);
}

$data = current_user_from_session(get_db_conn());
if (!$data) {
    rcpt_answer(401, false);
}

// Rate limit per session (sliding window). Count first, then let other requests of this
// session go on (session lock released).
$now = time();
$hits = array_values(array_filter(
    (array) ($_SESSION['rcpt_checks'] ?? []),
    function ($t) use ($now) { return is_int($t) && $t > $now - RCPT_CHECK_WINDOW; }
));
if (count($hits) >= RCPT_CHECK_MAX) {
    $_SESSION['rcpt_checks'] = $hits;
    // One log line per limited window, not one per refused check
    $log = (int) ($_SESSION['rcpt_limit_logged'] ?? 0) <= $now - RCPT_CHECK_WINDOW;
    if ($log) { $_SESSION['rcpt_limit_logged'] = $now; }
    session_write_close();
    if ($log && function_exists('app_log')) {
        app_log('recipient check: limit reached for user ' . (int) $data['id'], 'warn', true);
    }
    header('Retry-After: ' . RCPT_CHECK_WINDOW);
    rcpt_answer(429, false);
}
$hits[] = $now;
$_SESSION['rcpt_checks'] = $hits;
session_write_close();

// Only complete three words are looked up, and only as an exact match
$words = chat_normalize_words(is_string($_POST['threewords'] ?? null) ? $_POST['threewords'] : '');
if (strlen($words) > 80 || !preg_match('/^[a-z0-9]+ [a-z0-9]+ [a-z0-9]+$/', $words)) {
    rcpt_answer(200, false);
}

rcpt_answer(200, chat_recipient_error(db_3wordid_get_threeword($words)) === '');
