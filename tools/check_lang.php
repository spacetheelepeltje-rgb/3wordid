<?php
/**
 * Checks that every lang/<code>.php has the same keys, {placeholders} and HTML tags as lang/en.php.
 * Run from the repo root:  php tools/check_lang.php
 * Not needed on the web server. Exit code 1 when something differs.
 */
$dir = __DIR__ . '/../lang/';
$en = include $dir . 'en.php';

function lang_signature(string $text): array
{
    preg_match_all('/\{[a-z_0-9]+\}/i', $text, $ph);
    preg_match_all('/<[^>]+>/', $text, $tags);
    sort($ph[0]);
    sort($tags[0]);
    return [$ph[0], $tags[0]];
}

$errors = 0;
foreach (glob($dir . '*.php') as $file) {
    $code = basename($file, '.php');
    if ($code === 'en') {
        continue;
    }
    $tr = include $file;
    foreach (array_diff_key($en, $tr) as $k => $_) {
        echo "[$code] missing key: $k\n";
        $errors++;
    }
    foreach (array_diff_key($tr, $en) as $k => $_) {
        echo "[$code] extra key (not in en): $k\n";
        $errors++;
    }
    foreach ($en as $k => $text) {
        if (!isset($tr[$k])) {
            continue;
        }
        [$p1, $t1] = lang_signature($text);
        [$p2, $t2] = lang_signature($tr[$k]);
        if ($p1 !== $p2) {
            echo "[$code] placeholders differ in $k: " . implode(' ', $p1) . ' vs ' . implode(' ', $p2) . "\n";
            $errors++;
        }
        if ($t1 !== $t2) {
            echo "[$code] HTML tags differ in $k\n";
            $errors++;
        }
        if (trim($tr[$k]) === '') {
            echo "[$code] empty text in $k\n";
            $errors++;
        }
    }
    echo "[$code] " . count($tr) . " keys checked against en (" . count($en) . ")\n";
}
echo $errors ? "$errors problem(s)\n" : "OK\n";
exit($errors ? 1 : 0);
