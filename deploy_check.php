<?php
/**
 * deploy_check.php - lists a SHA-256 fingerprint of every site file, so an upload
 * can be compared with the repo (see DEPLOY.md and tools/deploy_compare.php).
 *
 * Only answers when the secret key is sent as the POST field "key" or the
 * "X-Deploy-Key" header (never in the URL, so it can't end up in access logs).
 * The key is the constant DEPLOY_CHECK_KEY in login/config.php. Without it,
 * or with a wrong key, the page answers 404 like a missing page.
 *
 * Output: JSON with fingerprints only. No file contents, no config values.
 * Fingerprints are of the file with CRLF line endings turned into LF, so a file
 * uploaded in FTP "ASCII" mode doesn't show as different.
 */

header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

function deploy_check_not_found(): void
{
    usleep(random_int(400000, 800000));   // slows down guessing
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not Found\n";
    exit;
}

$configFile = __DIR__ . '/login/config.php';
if (is_file($configFile)) {
    ob_start();
    require_once $configFile;
    ob_end_clean();
}

$given = '';
if (isset($_SERVER['HTTP_X_DEPLOY_KEY'])) {
    $given = (string) $_SERVER['HTTP_X_DEPLOY_KEY'];
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['key'])) {
    $given = (string) $_POST['key'];
}

$expected = defined('DEPLOY_CHECK_KEY') ? (string) DEPLOY_CHECK_KEY : '';
if (strlen($expected) < 16 || $given === '' || !hash_equals($expected, $given)) {
    deploy_check_not_found();
}

// Which files count as site files. Keep in sync with tools/deploy_compare.php.
const DC_EXTENSIONS = ['php', 'js', 'css', 'html'];
const DC_SKIP_DIRS  = ['vendor', 'login/vendor', 'node_modules', 'qr_codes', 'logos', 'uploads',
                       'logging', 'backups', 'old', 'old_april', 'old_error', 'tools', 'sql'];
const DC_SKIP_FILES = ['login/config.php', 'errors.php'];

$root  = realpath(__DIR__);
$files = [];

$dirs = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);  // symlinks are not followed
$filter = new RecursiveCallbackFilterIterator($dirs, function (SplFileInfo $f, $path) use ($root) {
    $rel  = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $name = $f->getFilename();
    if ($f->isLink()) {
        return false;
    }
    if ($f->isDir()) {
        return $name[0] !== '.' && !in_array($rel, DC_SKIP_DIRS, true);
    }
    if ($name !== '.htaccess' && $name[0] === '.') {
        return false;
    }
    if (in_array($rel, DC_SKIP_FILES, true) || $name === 'errors.php') {
        return false;
    }
    return $name === '.htaccess' || in_array(strtolower($f->getExtension()), DC_EXTENSIONS, true);
});

foreach (new RecursiveIteratorIterator($filter) as $path => $f) {
    if (!$f->isFile() || $f->getSize() > 5 * 1024 * 1024) {
        continue;
    }
    $data = @file_get_contents($path);
    if ($data === false) {
        continue;
    }
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $files[$rel] = hash('sha256', str_replace("\r\n", "\n", $data));
}
ksort($files, SORT_STRING);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'generated' => gmdate('c'),
    'hash'      => 'sha256, CRLF converted to LF',
    'files'     => $files,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
