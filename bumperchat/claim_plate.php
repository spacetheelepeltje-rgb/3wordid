<?php
// Old step-2 URL: send visitors to the combined country + plate page,
// keeping any country, plate, region or words query parameters.

require_once __DIR__ . '/../php/functions.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$keep = [];
foreach (['country', 'plate', 'region', 'words', 'threewords', 'lang'] as $key) {
    if (isset($_GET[$key]) && is_string($_GET[$key]) && $_GET[$key] !== '') {
        $keep[$key] = $_GET[$key];
    }
}
$qs = $keep ? ('?' . http_build_query($keep)) : '';
header('Location: claim.php' . $qs, true, 302);
exit;
