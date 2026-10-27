<?php
/**
 * Compares the files on the server with the repo, using deploy_check.php on the server.
 * Run on your own computer from a git checkout (not needed on the web server):
 *
 *   DEPLOY_CHECK_KEY='...' php tools/deploy_compare.php [--ref=origin/main] [--url=https://3wordid.com/deploy_check.php]
 *
 * The key is read from the environment and sent in a header; it is never printed.
 * Lists files missing on the server, files that differ from the repo, and files that
 * only exist on the server (for information). Exit code 1 when something is missing
 * or different, 2 when the check itself fails.
 */

$opts = getopt('', ['ref:', 'url:']);
$ref  = $opts['ref'] ?? 'origin/main';
$url  = $opts['url'] ?? (getenv('DEPLOY_CHECK_URL') ?: 'https://3wordid.com/deploy_check.php');
$key  = (string) getenv('DEPLOY_CHECK_KEY');

function fail(string $msg): void
{
    fwrite(STDERR, $msg . "\n");
    exit(2);
}

if ($key === '') {
    fail('Set the environment variable DEPLOY_CHECK_KEY (the value from login/config.php on the server).');
}

// Same rules as deploy_check.php. Keep in sync.
const DC_EXTENSIONS = ['php', 'js', 'css', 'html'];
const DC_SKIP_DIRS  = ['vendor', 'login/vendor', 'node_modules', 'qr_codes', 'logos', 'uploads',
                       'logging', 'backups', 'old', 'old_april', 'old_error', 'tools', 'sql'];
const DC_SKIP_FILES = ['login/config.php', 'errors.php'];

function is_site_file(string $rel): bool
{
    $parts = explode('/', $rel);
    $name  = array_pop($parts);
    $dir   = '';
    foreach ($parts as $p) {
        $dir = $dir === '' ? $p : "$dir/$p";
        if ($p[0] === '.' || in_array($dir, DC_SKIP_DIRS, true)) {
            return false;
        }
    }
    if (in_array($rel, DC_SKIP_FILES, true) || $name === 'errors.php') {
        return false;
    }
    if ($name === '.htaccess') {
        return true;
    }
    if ($name[0] === '.') {
        return false;
    }
    return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), DC_EXTENSIONS, true);
}

// 1. Fingerprints of the repo at $ref (straight from git, so local line-ending settings don't matter).
$repoRoot = trim((string) shell_exec('git -C ' . escapeshellarg(__DIR__) . ' rev-parse --show-toplevel 2>/dev/null'));
if ($repoRoot === '') {
    fail('Run this inside a git checkout of the 3wordid repo.');
}
$git = 'git -C ' . escapeshellarg($repoRoot);
$list = shell_exec("$git ls-tree -r -z --full-tree " . escapeshellarg($ref) . ' 2>/dev/null');
if (!$list) {
    fail("Unknown git ref: $ref (run 'git fetch origin' first?)");
}
$repo = [];
foreach (explode("\0", rtrim($list, "\0")) as $line) {
    // "<mode> blob <sha>\t<path>"
    [$meta, $path] = explode("\t", $line, 2);
    [$mode, $type, $blob] = explode(' ', $meta);
    if ($type !== 'blob' || $mode === '120000' || !is_site_file($path)) {
        continue;
    }
    $data = shell_exec("$git cat-file blob " . escapeshellarg($blob));
    $repo[$path] = hash('sha256', str_replace("\r\n", "\n", (string) $data));
}

// 2. Fingerprints on the server.
$ctx = stream_context_create(['http' => [
    'method'        => 'POST',
    'header'        => "X-Deploy-Key: $key\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: 0\r\n",
    'content'       => '',
    'timeout'       => 30,
    'ignore_errors' => true,
]]);
$body   = @file_get_contents($url, false, $ctx);
$status = isset($http_response_header[0]) ? $http_response_header[0] : 'no response';
$json   = $body === false ? null : json_decode($body, true);
if (!is_array($json) || !isset($json['files']) || !is_array($json['files'])) {
    fail("No fingerprint list from $url ($status). Is deploy_check.php uploaded and is the key right?");
}
$server = $json['files'];

// 3. Compare.
$missing   = array_keys(array_diff_key($repo, $server));
$extra     = array_keys(array_diff_key($server, $repo));
$different = [];
foreach (array_intersect_key($repo, $server) as $path => $hash) {
    if (!hash_equals($hash, (string) $server[$path])) {
        $different[] = $path;
    }
}
sort($missing); sort($extra); sort($different);

// Files that are meant to live only on the server.
$expectedExtra = ['3wid_show_qr.php'];

function show(string $title, array $items, array $note = []): void
{
    echo "\n$title (" . count($items) . ")\n";
    foreach ($items as $i) {
        echo '  ' . $i . (in_array($i, $note, true) ? '   (expected: server-only)' : '') . "\n";
    }
}

echo "Repo: $ref, " . count($repo) . " files.  Server: $url, " . count($server) . " files, generated " . ($json['generated'] ?? '?') . "\n";
show('Missing on the server', $missing);
show('Different from the repo', $different);
show('Only on the server (for information)', $extra, $expectedExtra);
echo "\n" . (($missing || $different) ? "RESULT: upload the files listed above.\n" : "RESULT: all repo files are on the server and up to date.\n");
exit(($missing || $different) ? 1 : 0);
