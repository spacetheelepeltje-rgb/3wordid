<?php
/**
 * Optional cron: expire pending messages whose 120 days have elapsed.
 *
 * The site already expires those rows lazily when a sender, the claim flow,
 * or the conversation list touches them (php/pending_messages.php). This
 * script is only useful if you want expired message bodies cleared without
 * waiting for that access. You decide whether to schedule it; the feature
 * works without a cron job.
 *
 * Command line only, never from the web.
 *
 * Usage:
 *   php tools/cleanup_pending_messages.php            expire due rows
 *   php tools/cleanup_pending_messages.php --dry-run  count only, change nothing
 *
 * Needs sql/2026-11-19_pending_messages.sql (run once) and login/config.php.
 * Example crontab (once a day; pick your own time):
 *   20 3 * * * php /var/www/vhosts/example.com/httpdocs/tools/cleanup_pending_messages.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$dry = in_array('--dry-run', $argv, true);

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'cli';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

chdir($root . '/login');
require $root . '/login/config.php';
require_once $root . '/php/functions.php';

if (!function_exists('pending_schema_ready') || !pending_schema_ready()) {
    fwrite(STDERR, "pending_messages table is not installed. Run sql/2026-11-19_pending_messages.sql first.\n");
    exit(2);
}

$now = time();
if ($dry) {
    $n = 0;
    $conn = get_db_conn();
    if ($conn && !$conn->connect_error) {
        $when = pending_datetime($now);
        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS n FROM pending_messages
              WHERE status = \'waiting\' AND expires_at <= ?'
        );
        if ($stmt) {
            $stmt->bind_param('s', $when);
            if ($stmt->execute()) {
                $row = $stmt->get_result()->fetch_assoc();
                $n = (int) ($row['n'] ?? 0);
            }
            $stmt->close();
        }
        $conn->close();
    }
    echo "dry-run: $n waiting message(s) would be marked expired and have their body cleared.\n";
    exit(0);
}

$n = pending_cleanup_expired($now);
echo "expired $n waiting message(s).\n";
exit(0);
