<?php
/**
 * 3WordID: remove stale empty accounts. Command line only (cron), never from the web.
 *
 * Rule: accounts older than 30 days that have no 3WordID.
 *   - Truly empty (no 3WordIDs, no deleted or waiting 3WordIDs, no messages sent or received,
 *     no reseller row, nothing in any other table with a user_id column, credit still at the
 *     starting amount, no payment details on the account): deleted completely.
 *   - Otherwise (something still points at the account): first copied into
 *     google_users_deleted (named columns + deleted_at + delete_reason), then deleted.
 * Never touched: the admin (user id 4, and anyone with is_admin = 1), blocked users
 * (user_type 9, so their block stays), accounts with more credit than the starting amount
 * (google_users.credit defaults to 1), and accounts that logged in during the last 30 days.
 *
 * Usage:
 *   php tools/cleanup_empty_accounts.php              dry run: only lists what it would do
 *   php tools/cleanup_empty_accounts.php --apply      really deletes / backs up
 *   options: --days=30 (minimum 30), --limit=500 (max accounts deleted per run), --quiet (summary only)
 *
 * Needs sql/2026-10-28_users_deleted.sql (backup table). Account age: google_users.created_at.
 * Each run writes one summary line to the daily log (php/log.php, LOG_DIR).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const CLEANUP_ADMIN_ID = 4;
const CLEANUP_BLOCKED_TYPE = 9;
const CLEANUP_BACKUP_TABLE = 'google_users_deleted';
// Payment details kept on google_users itself: any of these filled in = not truly empty.
const CLEANUP_PAYMENT_COLUMNS = ['payment_status', 'payment_method', 'paypal_email', 'reseller_code', 'code'];
// Columns that are never copied into the backup (login secrets): stored as '' instead.
// (The backup table has no unique index on token, see the SQL file.)
const CLEANUP_SECRET_COLUMNS = ['token', 'session_id', 'csrf_token', 'password_hash'];
// Account creation date: the first of these columns that exists (older names first, so a
// column with real history wins over created_at added by the SQL file).
const CLEANUP_DATE_COLUMNS = ['creation_date', 'created', 'date_created', 'registration_date', 'signup_date', 'created_at'];

/* ------------------------------------------------------------------------------------------
 * Database access, small on purpose: the same logic runs against MySQL/MariaDB (mysqli) and,
 * in tests, against any PDO database.
 * ---------------------------------------------------------------------------------------- */
interface CleanupDb
{
    public function rows(string $sql, array $params = []): array;
    public function run(string $sql, array $params = []): int;
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;
    /** Column names of a table, [] when the table does not exist. */
    public function columns(string $table): array;
    /** Default value of a column (null when none or unknown). */
    public function columnDefault(string $table, string $column): ?string;
    /** All tables that have this column. */
    public function tablesWithColumn(string $column): array;
    /** ' FOR UPDATE' where supported. */
    public function lockSuffix(): string;
}

class CleanupMysqli implements CleanupDb
{
    private mysqli $c;
    private array $colCache = [];

    public function __construct(mysqli $c) { $this->c = $c; }

    private function stmt(string $sql, array $params): mysqli_stmt
    {
        $st = $this->c->prepare($sql);
        if ($params) {
            $types = '';
            foreach ($params as $p) {
                $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            }
            $st->bind_param($types, ...$params);
        }
        $st->execute();
        return $st;
    }

    public function rows(string $sql, array $params = []): array
    {
        $st = $this->stmt($sql, $params);
        $res = $st->get_result();
        $out = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $st->close();
        return $out;
    }

    public function run(string $sql, array $params = []): int
    {
        $st = $this->stmt($sql, $params);
        $n = $st->affected_rows;
        $st->close();
        return (int) $n;
    }

    public function begin(): void { $this->c->begin_transaction(); }
    public function commit(): void { $this->c->commit(); }
    public function rollback(): void { $this->c->rollback(); }

    public function columns(string $table): array
    {
        if (!isset($this->colCache[$table])) {
            $rows = $this->rows('SELECT COLUMN_NAME AS c FROM information_schema.COLUMNS
                                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION', [$table]);
            $this->colCache[$table] = array_column($rows, 'c');
        }
        return $this->colCache[$table];
    }

    public function columnDefault(string $table, string $column): ?string
    {
        $rows = $this->rows('SELECT COLUMN_DEFAULT AS d FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]);
        return isset($rows[0]['d']) ? trim((string) $rows[0]['d'], "'") : null;
    }

    public function tablesWithColumn(string $column): array
    {
        $rows = $this->rows('SELECT DISTINCT c.TABLE_NAME AS t FROM information_schema.COLUMNS c
                             JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
                             WHERE c.TABLE_SCHEMA = DATABASE() AND c.COLUMN_NAME = ? AND t.TABLE_TYPE = \'BASE TABLE\'', [$column]);
        return array_column($rows, 't');
    }

    public function lockSuffix(): string { return ' FOR UPDATE'; }
}

/** `name` quoting for identifiers that come from the schema (never from input). */
function cleanup_q(string $name): string
{
    return '`' . str_replace('`', '', $name) . '`';
}

/**
 * Tables and columns that point at a user. Fixed list plus every other table that has a
 * user_id column (payments, credits, ... added later are picked up automatically).
 * Returns [[table, column], ...] for the ones that exist.
 */
function cleanup_linked_columns(CleanupDb $db): array
{
    $wanted = [
        ['3wordid', 'user_id'],
        ['3wordid_deleted', 'user_id'],
        ['3wordid_messages', 'from_id'],
        ['3wordid_messages', 'to_id'],
        ['3wordid_messages_deleted', 'from_id'],
        ['3wordid_messages_deleted', 'to_id'],
        ['3wordid_waiting', 'user_id'],
        ['3wordid_resellers', 'user_id'],
        ['3wordid_resellers', 'created_user'],
    ];
    foreach ($db->tablesWithColumn('user_id') as $t) {
        if (stripos($t, 'google_users') !== 0) {
            $wanted[] = [$t, 'user_id'];
        }
    }
    $out = [];
    foreach ($wanted as [$t, $c]) {
        if (in_array($c, $db->columns($t), true) && !in_array([$t, $c], $out, true)) {
            $out[] = [$t, $c];
        }
    }
    return $out;
}

/**
 * Works out what to do with each stale account. Changes nothing.
 * Returns ['date_column' => ..., 'plan' => [[id, action, detail], ...]] where action is
 * delete | backup | skip. Throws RuntimeException when the schema isn't ready.
 */
function cleanup_plan(CleanupDb $db, int $days, int $limit, int $now): array
{
    $ucols = $db->columns('google_users');
    if (!$ucols) {
        throw new RuntimeException('table google_users not found');
    }
    if (!$db->columns('3wordid')) {
        throw new RuntimeException('table 3wordid not found');
    }
    $date_col = '';
    foreach (CLEANUP_DATE_COLUMNS as $c) {
        if (in_array($c, $ucols, true)) {
            $date_col = $c;
            break;
        }
    }
    if ($date_col === '') {
        throw new RuntimeException('google_users has no account creation date column (created_at expected)');
    }

    $cutoff = date('Y-m-d H:i:s', $now - $days * 86400);
    $extra = [];
    foreach (array_merge(['credit', 'user_type', 'is_admin', 'last_login'], CLEANUP_PAYMENT_COLUMNS) as $c) {
        if (in_array($c, $ucols, true)) {
            $extra[] = 'u.' . cleanup_q($c);
        }
    }
    $sql = 'SELECT u.id, u.' . cleanup_q($date_col) . ' AS created' . ($extra ? ', ' . implode(', ', $extra) : '') . '
            FROM google_users u
            WHERE u.id <> ? AND u.' . cleanup_q($date_col) . ' IS NOT NULL AND u.' . cleanup_q($date_col) . ' < ?
              AND NOT EXISTS (SELECT 1 FROM 3wordid w WHERE w.user_id = u.id)
            ORDER BY u.id';
    $candidates = $db->rows($sql, [CLEANUP_ADMIN_ID, $cutoff]);

    $linked = cleanup_linked_columns($db);
    // Every account starts with some credit (column default, 1 on the live database): that
    // amount doesn't count as money on the account.
    $start_credit = (float) ($db->columnDefault('google_users', 'credit') ?? 0);
    $plan = [];
    $actionable = 0;
    foreach ($candidates as $u) {
        $id = (int) $u['id'];
        if ((int) ($u['is_admin'] ?? 0) === 1) {
            $plan[] = [$id, 'skip', 'admin'];
            continue;
        }
        if (isset($u['user_type']) && (int) $u['user_type'] === CLEANUP_BLOCKED_TYPE) {
            $plan[] = [$id, 'skip', 'blocked user (block is kept)'];
            continue;
        }
        if (isset($u['credit']) && (float) $u['credit'] > $start_credit + 0.001) {
            $plan[] = [$id, 'skip', 'has credit ' . $u['credit']];
            continue;
        }
        if (!empty($u['last_login']) && $u['last_login'] >= $cutoff) {
            $plan[] = [$id, 'skip', 'logged in since ' . $cutoff];
            continue;
        }
        $found = [];
        if (isset($u['credit']) && abs((float) $u['credit'] - $start_credit) > 0.001) {
            $found[] = 'credit=' . $u['credit'];
        }
        foreach (CLEANUP_PAYMENT_COLUMNS as $c) {
            if (isset($u[$c]) && trim((string) $u[$c]) !== '') {
                $found[] = 'google_users.' . $c;
            }
        }
        foreach ($linked as [$t, $c]) {
            $n = (int) ($db->rows('SELECT COUNT(*) AS n FROM ' . cleanup_q($t) . ' WHERE ' . cleanup_q($c) . ' = ?', [$id])[0]['n'] ?? 0);
            if ($n > 0) {
                $found[] = $t . '.' . $c . '=' . $n;
            }
        }
        $plan[] = $found ? [$id, 'backup', implode(' ', $found)] : [$id, 'delete', 'nothing linked'];
        if (++$actionable >= max(1, $limit)) {
            break; // the rest waits for the next run
        }
    }
    return ['date_column' => $date_col, 'cutoff' => $cutoff, 'plan' => $plan];
}

/** Copies one user into the backup table (named columns, secrets blanked). */
function cleanup_backup_user(CleanupDb $db, int $id, string $reason, int $now): void
{
    $bcols = $db->columns(CLEANUP_BACKUP_TABLE);
    if (!$bcols) {
        throw new RuntimeException('backup table ' . CLEANUP_BACKUP_TABLE . ' not found; run sql/2026-10-28_users_deleted.sql');
    }
    if (!in_array('deleted_at', $bcols, true)) {
        throw new RuntimeException(CLEANUP_BACKUP_TABLE . '.deleted_at missing; run sql/2026-10-28_users_deleted.sql');
    }
    $into = [];
    $select = [];
    foreach ($db->columns('google_users') as $c) {
        if (!in_array($c, $bcols, true) || $c === 'deleted_at' || $c === 'delete_reason') {
            continue;
        }
        $into[] = cleanup_q($c);
        $select[] = in_array($c, CLEANUP_SECRET_COLUMNS, true) ? "''" : cleanup_q($c);
    }
    $params = [date('Y-m-d H:i:s', $now)];
    $into[] = '`deleted_at`';
    $select[] = '?';
    if (in_array('delete_reason', $bcols, true)) {
        $into[] = '`delete_reason`';
        $select[] = '?';
        $params[] = substr($reason, 0, 64);
    }
    $params[] = $id;
    $n = $db->run('INSERT INTO ' . CLEANUP_BACKUP_TABLE . ' (' . implode(', ', $into) . ')
                   SELECT ' . implode(', ', $select) . ' FROM google_users WHERE id = ?', $params);
    if ($n !== 1) {
        throw new RuntimeException('backup copy failed');
    }
}

/**
 * Carries out the plan, one transaction per account. Before deleting it checks again, inside
 * the transaction, that the account still has no 3WordID and nothing new got linked.
 * Returns ['deleted' => ids, 'backed_up' => ids, 'errors' => [id => message]].
 */
function cleanup_apply(CleanupDb $db, array $plan, int $now): array
{
    $done = ['deleted' => [], 'backed_up' => [], 'errors' => []];
    $linked = cleanup_linked_columns($db);
    foreach ($plan as [$id, $action, $detail]) {
        if ($action === 'skip' || $id === CLEANUP_ADMIN_ID) {
            continue;
        }
        try {
            $db->begin();
            if (!$db->rows('SELECT id FROM google_users WHERE id = ?' . $db->lockSuffix(), [$id])) {
                throw new RuntimeException('already gone');
            }
            if ($db->rows('SELECT 1 AS x FROM 3wordid WHERE user_id = ? LIMIT 1', [$id])) {
                throw new RuntimeException('has a 3WordID now');
            }
            if ($action === 'delete') {
                foreach ($linked as [$t, $c]) {
                    if ($db->rows('SELECT 1 AS x FROM ' . cleanup_q($t) . ' WHERE ' . cleanup_q($c) . ' = ? LIMIT 1', [$id])) {
                        $action = 'backup';
                        $detail = 'linked since plan: ' . $t . '.' . $c;
                        break;
                    }
                }
            }
            if ($action === 'backup') {
                cleanup_backup_user($db, $id, 'empty 30d: ' . $detail, $now);
            }
            if ($db->run('DELETE FROM google_users WHERE id = ? AND id <> ?', [$id, CLEANUP_ADMIN_ID]) !== 1) {
                throw new RuntimeException('delete failed');
            }
            $db->commit();
            $done[$action === 'backup' ? 'backed_up' : 'deleted'][] = $id;
        } catch (Throwable $e) {
            try { $db->rollback(); } catch (Throwable $ignored) {}
            $done['errors'][$id] = $e->getMessage();
        }
    }
    return $done;
}

/** One summary line for the log. */
function cleanup_summary(bool $apply, int $days, array $planned, ?array $done): string
{
    $by = ['delete' => [], 'backup' => [], 'skip' => []];
    foreach ($planned['plan'] as [$id, $action, $detail]) {
        $by[$action][] = $action === 'skip' ? $id . '(' . $detail . ')' : $id;
    }
    $list = static fn(array $ids) => count($ids) . ($ids ? ' [' . implode(',', $ids) . ']' : '');
    $line = 'cleanup_empty_accounts mode=' . ($apply ? 'apply' : 'dry-run') . ' days=' . $days
        . ' date_column=' . $planned['date_column'] . ' older_than=' . $planned['cutoff']
        . ' candidates=' . count($planned['plan']);
    if ($done === null) {
        $line .= ' would_delete=' . $list($by['delete']) . ' would_backup_then_delete=' . $list($by['backup']);
    } else {
        $errs = [];
        foreach ($done['errors'] as $id => $msg) {
            $errs[] = $id . '(' . $msg . ')';
        }
        $line .= ' deleted=' . $list($done['deleted']) . ' backed_up_then_deleted=' . $list($done['backed_up'])
            . ' errors=' . $list($errs);
    }
    return $line . ' skipped=' . $list($by['skip']);
}

function cleanup_main(array $argv): int
{
    $opts = ['apply' => false, 'days' => 30, 'limit' => 500, 'quiet' => false];
    foreach (array_slice($argv, 1) as $a) {
        if ($a === '--apply') {
            $opts['apply'] = true;
        } elseif ($a === '--quiet') {
            $opts['quiet'] = true;
        } elseif (preg_match('/^--(days|limit)=(\d+)$/', $a, $m)) {
            $opts[$m[1]] = (int) $m[2];
        } else {
            fwrite(STDERR, "Usage: php tools/cleanup_empty_accounts.php [--apply] [--days=30] [--limit=500] [--quiet]\n");
            return 64;
        }
    }
    if ($opts['days'] < 30) {
        fwrite(STDERR, "--days must be 30 or more\n");
        return 64;
    }

    $root = dirname(__DIR__);
    // config.php is written for web pages; give it the bits of $_SERVER it may read.
    $_SERVER += ['HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/', 'SERVER_NAME' => 'localhost', 'HTTPS' => 'on'];
    chdir($root . '/login'); // config.php may include files relative to the login folder
    require $root . '/login/config.php';
    require_once $root . '/php/log.php';

    // Only one run at a time.
    $lock = fopen(__FILE__, 'r');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        fwrite(STDERR, "Another cleanup run is still busy.\n");
        return 75;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(SERVER, WRITEUSER, WRITEPASSWORD, DATABASE);
        $conn->set_charset('utf8mb4');
        $db = new CleanupMysqli($conn);
        $now = time();
        $planned = cleanup_plan($db, $opts['days'], $opts['limit'], $now);
        $done = $opts['apply'] ? cleanup_apply($db, $planned['plan'], $now) : null;
    } catch (Throwable $e) {
        $msg = 'cleanup_empty_accounts failed: ' . $e->getMessage();
        app_log($msg, 'error');
        fwrite(STDERR, $msg . "\n");
        return 1;
    }

    if (!$opts['quiet']) {
        echo ($opts['apply'] ? "APPLY" : "DRY RUN (nothing is changed; add --apply to act)") . "\n";
        foreach ($planned['plan'] as [$id, $action, $detail]) {
            $verb = ['delete' => 'delete completely', 'backup' => 'copy to ' . CLEANUP_BACKUP_TABLE . ', then delete', 'skip' => 'keep'][$action];
            printf("  user %-6d %-45s %s\n", $id, $verb, $detail);
        }
    }
    $summary = cleanup_summary($opts['apply'], $opts['days'], $planned, $done);
    echo $summary . "\n";
    app_log($summary, $done && $done['errors'] ? 'warn' : 'info');
    return ($done && $done['errors']) ? 2 : 0;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(cleanup_main($_SERVER['argv'] ?? []));
}
