<?php
/**
 * Site log: one file per day, for easy searching (logs/2026-10-28.log style).
 *
 * Usage:  app_log('something happened');                       level "info"
 *         app_log('signup rejected (too_fast)', 'warn', true);  with the visitor's IP
 *
 * Line format (one line per entry, tab separated):
 *   2026-10-28 10:05:03 +0000  WARN  /login/email_register.php  ip=203.0.113.7  message
 *
 * Settings, all optional, in login/config.php:
 *   define('LOG_DIR', '/home/USER/3wid_logs');  // folder for the daily files. Best OUTSIDE the
 *                                               // web root. Default: the "logging" folder in the
 *                                               // web root, which .htaccess already blocks and
 *                                               // git ignores.
 *   define('LOG_MIRROR_ERROR_LOG', false);      // default true: every app_log() line from a web
 *                                               // page is also sent to PHP's normal error log, so
 *                                               // errors.php keeps showing it.
 *   define('LOG_PHP_ERRORS_DATED', true);       // default false: also send PHP's own error log
 *                                               // (all older error_log() calls and PHP warnings)
 *                                               // to the daily file. errors.php then only shows
 *                                               // entries from before the switch.
 *
 * Loaded from php/session_boot.php, so every page (and the login pages) has app_log().
 * Never throws: if the folder can't be written, the line goes to PHP's error log instead.
 */

if (!function_exists('app_log')) {

function app_log_dir(): string
{
    $dir = (defined('LOG_DIR') && (string) LOG_DIR !== '') ? (string) LOG_DIR : dirname(__DIR__) . '/logging';
    return rtrim($dir, "/\\");
}

/** Full path of the log file for a day (default: today, server time zone). */
function app_log_file(?int $time = null): string
{
    return app_log_dir() . '/' . date('Y-m-d', $time ?? time()) . '.log';
}

/** True when the log folder exists (it is created when missing) and can be written. */
function app_log_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        $dir = app_log_dir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $ready = is_dir($dir) && is_writable($dir);
    }
    return $ready;
}

/** Visitor IP for the log: REMOTE_ADDR, plus the forwarded-for header when one is sent. */
function app_log_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $fwd = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    $out = 'ip=' . ($ip !== '' ? $ip : '-');
    if ($fwd !== '' && $fwd !== $ip) {
        $out .= ' fwd=' . substr(preg_replace('/[^0-9a-fA-F:., ]/', '', $fwd), 0, 100);
    }
    return $out;
}

/** The page or script that writes the line. */
function app_log_page(): string
{
    if (PHP_SAPI === 'cli') {
        return 'cli:' . basename((string) ($_SERVER['argv'][0] ?? $_SERVER['SCRIPT_NAME'] ?? '?'));
    }
    $page = (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '?');
    return $page !== '' ? $page : '?';
}

/**
 * Write one line to today's log file. $level: debug, info, warn or error.
 * $with_ip adds the visitor's IP (use it where the IP matters: abuse, spam, blocks).
 */
function app_log(string $message, string $level = 'info', bool $with_ip = false): bool
{
    $level = strtoupper(preg_replace('/[^a-z]/i', '', $level) ?: 'INFO');
    $message = str_replace(["\r", "\n", "\t"], ' ', $message);
    $parts = [date('Y-m-d H:i:s O'), $level, app_log_page()];
    if ($with_ip && PHP_SAPI !== 'cli') {
        $parts[] = app_log_ip();
    }
    $parts[] = $message;
    $line = implode("\t", $parts);

    $written = app_log_ready() && @file_put_contents(app_log_file(), $line . "\n", FILE_APPEND | LOCK_EX) !== false;

    $mirror = !defined('LOG_MIRROR_ERROR_LOG') || LOG_MIRROR_ERROR_LOG;
    $php_to_daily = defined('LOG_PHP_ERRORS_DATED') && LOG_PHP_ERRORS_DATED;
    // (Not from the command line: there error_log() prints to the screen, so cron would mail it.)
    if (!$written || ($mirror && !$php_to_daily && PHP_SAPI !== 'cli')) {
        // PHP adds its own timestamp; keep level, page and IP.
        error_log('[' . $level . '] ' . implode(' ', array_slice($parts, 2)));
    }
    return $written;
}

/** With LOG_PHP_ERRORS_DATED, PHP's own error log goes to today's file as well. */
function app_log_route_php_errors(): void
{
    if (defined('LOG_PHP_ERRORS_DATED') && LOG_PHP_ERRORS_DATED && app_log_ready()) {
        ini_set('error_log', app_log_file());
    }
}

}

app_log_route_php_errors();
