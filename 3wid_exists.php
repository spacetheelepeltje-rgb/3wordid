<?php
/*
 * Live "does this 3WordID exist?" check, used by the homepage search box (lock icon) and the
 * add form (js/3wid_index_2.js, js/3wid_add.js).
 *
 * POST threewords=<three words>
 *   -> plain text "exists" / "does not exist"               (add form, as before)
 *   with format=json -> {"exists": bool, "paying": bool}   (homepage search lock)
 *
 * Privacy: only yes/no for the exact, complete three words, plus whether the owner is a
 * paying account (gold lock). No suggestions, no partial matches, nothing else about the
 * 3WordID or its owner. Switched-off 3WordIDs count as existing: the search goes on and the
 * visitor lands on the "not active at the moment" page. Public, so limited per session.
 */

require_once 'php/functions.php';
require_once 'login/config.php';

const EXISTS_CHECK_MAX = 120;     // checks per session ... (it runs while typing)
const EXISTS_CHECK_WINDOW = 300;  // ... per 5 minutes

header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex');

$json = (($_POST['format'] ?? '') === 'json');
header($json ? 'Content-Type: application/json; charset=utf-8' : 'Content-Type: text/plain; charset=utf-8');

function exists_answer(bool $json, int $status, bool $exists, bool $paying = false): void
{
    http_response_code($status);
    if ($json) {
        echo json_encode(['exists' => $exists, 'paying' => $exists && $paying]);
    } else {
        echo $exists ? 'exists' : 'does not exist';
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    exists_answer($json, 405, false);
}

// Rate limit per session (sliding window), then release the session lock.
$now = time();
$hits = array_values(array_filter(
    (array) ($_SESSION['exists_checks'] ?? []),
    function ($t) use ($now) { return is_int($t) && $t > $now - EXISTS_CHECK_WINDOW; }
));
if (count($hits) >= EXISTS_CHECK_MAX) {
    $_SESSION['exists_checks'] = $hits;
    // One log line per limited window, not one per refused check
    $log = (int) ($_SESSION['exists_limit_logged'] ?? 0) <= $now - EXISTS_CHECK_WINDOW;
    if ($log) { $_SESSION['exists_limit_logged'] = $now; }
    session_write_close();
    if ($log && function_exists('app_log')) {
        app_log('exists check: limit reached', 'warn', true);
    }
    header('Retry-After: ' . EXISTS_CHECK_WINDOW);
    http_response_code(429);
    exit;
}
$hits[] = $now;
$_SESSION['exists_checks'] = $hits;
session_write_close();

// Only complete three words, exact match
$words = trim(preg_replace('/\s+/u', ' ', is_string($_POST['threewords'] ?? null) ? $_POST['threewords'] : ''));
if (strlen($words) > 80 || !preg_match('/^[A-Za-z0-9]+ [A-Za-z0-9]+ [A-Za-z0-9]+$/', $words)) {
    exists_answer($json, 200, false);
}

$status = db_3wordid_exists_status($words);
exists_answer($json, 200, $status !== null, (bool) ($status['paying'] ?? false));
