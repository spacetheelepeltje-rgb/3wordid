<?php
/*
 * Live "does this 3WordID exist?" check, used by the homepage search box (lock icon) and the
 * add form (js/3wid_index_2.js, js/3wid_add.js).
 *
 * POST threewords=<three words>
 *   -> plain text "exists" / "does not exist"               (add form, as before)
 *   with format=json -> {"exists": bool, "paying": bool}   (homepage search lock)
 *   + plate=1 (homepage search) -> also "plate": bool, only when the words look like a
 *     licence plate (three words of 1-3 letters / digits, at least one digit): is it a Dutch
 *     plate in the RDW register? (car icon instead of the lock)
 *
 * Privacy: only yes/no for the exact, complete three words, plus whether the owner is a
 * paying account (gold lock). No suggestions, no partial matches, nothing else about the
 * 3WordID or its owner. "plate" is only yes / no from RDW (car_plate_known() in
 * php/car_chat.php: server side, cached, short timeout), never make, colour or anything else;
 * RDW down or slow = no "plate" at all. Switched-off 3WordIDs count as existing: the search goes on and the
 * visitor lands on the "not active at the moment" page. Public, so limited per session.
 */

require_once 'php/functions.php';
require_once 'login/config.php';

// Limit: EXISTS_CHECK_MAX checks per EXISTS_CHECK_WINDOW seconds per session (exists_check_allowed()
// in php/functions.php, shared with bumperchat/claim.php)

header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex');

$json = (($_POST['format'] ?? '') === 'json');
header($json ? 'Content-Type: application/json; charset=utf-8' : 'Content-Type: text/plain; charset=utf-8');

function exists_answer(bool $json, int $status, bool $exists, bool $paying = false, ?bool $plate = null): void
{
    http_response_code($status);
    if ($json) {
        $out = ['exists' => $exists, 'paying' => $exists && $paying];
        if ($plate !== null) {
            $out['plate'] = $plate;
        }
        echo json_encode($out);
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
$allowed = exists_check_allowed();
session_write_close();
if (!$allowed) {
    header('Retry-After: ' . EXISTS_CHECK_WINDOW);
    http_response_code(429);
    exit;
}

// Only complete three words, exact match
$words = trim(preg_replace('/\s+/u', ' ', is_string($_POST['threewords'] ?? null) ? $_POST['threewords'] : ''));
if (strlen($words) > 80 || !preg_match('/^[A-Za-z0-9]+ [A-Za-z0-9]+ [A-Za-z0-9]+$/', $words)) {
    exists_answer($json, 200, false);
}

$status = db_3wordid_exists_status($words);
// Licence plate? (homepage search only, after the session is released: RDW can take a moment)
$plate = ($json && (($_POST['plate'] ?? '') === '1') && car_plate_pattern($words)) ? car_plate_known($words) : null;
exists_answer($json, 200, $status !== null, (bool) ($status['paying'] ?? false), $plate);
