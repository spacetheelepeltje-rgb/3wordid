<?php
// Admin-only: list, add and activate/deactivate referral reseller codes.
// Same admin check as the other admin pages ($admin_id = 4).

require_once 'php/functions.php';
require_once 'login/config.php';

$data = current_user_from_session($conn);

if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
}

$admin_id = 4;

if ((int)$data['id'] !== $admin_id) {
    header('Location: 3wid_list.php?message=' . rawurlencode(t_plain('admin.not_allowed')));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flash = '';
$flash_err = '';
$form_code = '';
$form_name = '';
$form_contact = '';

$known_ok = ['res.added', 'res.toggled'];
$known_err = ['res.err_code', 'res.err_name', 'res.err_exists', 'res.err_csrf', 'res.err_save', 'res.err_schema'];
if (isset($_GET['ok']) && in_array($_GET['ok'], $known_ok, true)) {
    $flash = (string) $_GET['ok'];
}
if (isset($_GET['err']) && in_array($_GET['err'], $known_err, true)) {
    $flash_err = (string) $_GET['err'];
}

function resellers_redirect(string $param, string $key): void
{
    header('Location: 3wid_resellers.php?' . $param . '=' . rawurlencode($key));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string) $_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        resellers_redirect('err', 'res.err_csrf');
    }
    if (!reseller_schema_ready()) {
        resellers_redirect('err', 'res.err_schema');
    }
    $action = (string) ($_POST['action'] ?? 'add');
    $db = get_db_conn();
    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && $db) {
            $st = $db->prepare('UPDATE resellers SET active = IF(active = 1, 0, 1) WHERE id = ?');
            if ($st) {
                $st->bind_param('i', $id);
                $st->execute();
                $st->close();
            }
        }
        resellers_redirect('ok', 'res.toggled');
    }

    $form_code = reseller_normalize_code((string) ($_POST['code'] ?? ''));
    $form_name = trim((string) ($_POST['name'] ?? ''));
    $form_contact = trim((string) ($_POST['contact'] ?? ''));
    if ($form_code === '') {
        $flash_err = 'res.err_code';
    } elseif ($form_name === '') {
        $flash_err = 'res.err_name';
    } elseif (!$db) {
        $flash_err = 'res.err_save';
    } else {
        $active = empty($_POST['active']) ? 0 : 1;
        $contact_db = $form_contact !== '' ? $form_contact : null;
        $ins = $db->prepare('INSERT INTO resellers (code, name, contact, active) VALUES (?, ?, ?, ?)');
        if (!$ins) {
            $flash_err = 'res.err_save';
        } else {
            $ins->bind_param('sssi', $form_code, $form_name, $contact_db, $active);
            if ($ins->execute()) {
                $ins->close();
                resellers_redirect('ok', 'res.added');
            }
            $ins->close();
            $flash_err = ($db->errno === 1062) ? 'res.err_exists' : 'res.err_save';
        }
    }
}

$rows = [];
$schema_ok = reseller_schema_ready();
$pay_ok = $schema_ok && reseller_payments_column_ready();
if ($schema_ok) {
    $db = get_db_conn();
    if ($db) {
        $sql = 'SELECT r.id, r.code, r.name, r.contact, r.active, r.created_at,
                       (SELECT COUNT(*) FROM google_users u WHERE u.reseller_id = r.id) AS user_count';
        if ($pay_ok) {
            $sql .= ",
                       (SELECT COUNT(*) FROM payments p WHERE p.reseller_id = r.id AND p.status = 'paid') AS paid_count,
                       (SELECT COALESCE(SUM(CAST(p.amount AS DECIMAL(12,2))), 0)
                          FROM payments p
                         WHERE p.reseller_id = r.id AND p.status = 'paid' AND UPPER(p.currency) = 'EUR') AS paid_total_eur";
        } else {
            $sql .= ', 0 AS paid_count, 0 AS paid_total_eur';
        }
        $sql .= ' FROM resellers r ORDER BY r.created_at DESC, r.id DESC';
        $res = $db->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
    }
}

function res_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('res.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <style>
      :root {
        --navy: #0f1c3f;
        --navy-2: #162756;
        --slate: #5b6478;
        --muted: #8a93a6;
        --line: #e6e3dc;
        --paper: #f7f6f3;
        --gold: #b45309;
      }
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body { font-family: Inter, system-ui, sans-serif; background: var(--paper); color: var(--navy); min-height: 100vh; }
      .site-header { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 28px; background: var(--paper); border-bottom: 1px solid var(--line); }
      .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--navy); font-weight: 800; letter-spacing: -.04em; }
      .header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
      .btn { display: inline-flex; align-items: center; height: 36px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--line); background: #fff; color: var(--navy); text-decoration: none; font-size: 14px; font-weight: 600; cursor: pointer; }
      .btn-primary { background: var(--navy); color: #fff; border-color: var(--navy); }
      .btn-ghost { background: #fff; }
      .logout-icon { display: inline-flex; color: var(--navy); }
      .wrap { max-width: 1100px; margin: 0 auto; padding: 28px 24px 64px; }
      h1 { font-size: 28px; font-weight: 800; letter-spacing: -.04em; margin-bottom: 8px; }
      .lede { color: var(--slate); font-size: 15px; line-height: 1.5; margin-bottom: 22px; max-width: 46em; }
      .flash { background: #fff; border: 1px solid #eceae4; border-left: 4px solid #1b6b34; border-radius: 14px; padding: 12px 14px; color: var(--slate); font-size: 14px; margin-bottom: 16px; }
      .flash.is-err { border-left-color: var(--gold); }
      .card { background: #fff; border: 1px solid #eceae4; border-radius: 16px; padding: 20px; margin-bottom: 22px; box-shadow: 0 10px 30px rgba(15,28,63,.06); }
      table { width: 100%; border-collapse: collapse; font-size: 14px; }
      th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--line); vertical-align: middle; }
      th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); font-weight: 700; }
      code { font-size: 13px; }
      .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
      .pill-on { background: #e6f6ea; color: #1b6b34; }
      .pill-off { background: #f0f0f0; color: #555; }
      .add-grid { display: grid; grid-template-columns: 1fr 1.2fr 1.4fr auto; gap: 12px; align-items: end; }
      label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
      input[type=text] { width: 100%; height: 40px; border: 1px solid var(--line); border-radius: 10px; padding: 0 12px; font: inherit; color: var(--navy); }
      .active-row { display: flex; align-items: center; gap: 8px; height: 40px; font-size: 14px; }
      .empty { color: var(--muted); font-size: 14px; }
      .toggle { height: 32px; font-size: 13px; }
      @media (max-width: 800px) {
        .add-grid { grid-template-columns: 1fr; }
        .site-header { padding: 14px 20px; }
      }
    </style>
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?php echo htmlspecialchars($main_url ?? 'index.php'); ?>" title="<?php echo t('admin.to_home'); ?>">
        <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
        3WordID
    </a>
    <div class="header-actions">
        <?php echo lang_switcher(); ?>
        <a class="btn btn-ghost" href="3wid_list.php"><?php echo t('admin.dashboard'); ?></a>
        <a class="btn btn-ghost" href="3wid_user_list.php"><?php echo t('ue.all_users'); ?></a>
        <a class="btn btn-ghost" href="3wid_admin_3wids.php"><?php echo t('admin.all_3wids'); ?></a>
        <a href="login/logout.php" class="logout-icon" title="<?php echo t('nav.logout'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.logout'); ?>">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </a>
    </div>
</header>

<main class="wrap">
    <h1><?php echo t('res.h1'); ?></h1>
    <p class="lede"><?php echo t('res.lead'); ?></p>

    <?php if ($flash !== ''): ?>
        <div class="flash" role="status"><?php echo t($flash); ?></div>
    <?php endif; ?>
    <?php if ($flash_err !== ''): ?>
        <div class="flash is-err" role="alert"><?php echo t($flash_err); ?></div>
    <?php endif; ?>

    <?php if (!$schema_ok): ?>
        <div class="flash is-err" role="status"><?php echo t('res.err_schema'); ?></div>
    <?php endif; ?>

    <div class="card">
        <?php if (!$rows): ?>
            <p class="empty"><?php echo t('res.empty'); ?></p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?php echo t('res.code'); ?></th>
                    <th><?php echo t('res.name'); ?></th>
                    <th><?php echo t('res.contact'); ?></th>
                    <th><?php echo t('res.active'); ?></th>
                    <th><?php echo t('res.users'); ?></th>
                    <th><?php echo t('res.payments'); ?></th>
                    <th><?php echo t('res.total'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><code><?php echo res_e($row['code']); ?></code></td>
                    <td><?php echo res_e($row['name']); ?></td>
                    <td><?php echo res_e($row['contact'] ?? ''); ?></td>
                    <td>
                        <?php if ((int) $row['active'] === 1): ?>
                            <span class="pill pill-on"><?php echo t('res.active'); ?></span>
                        <?php else: ?>
                            <span class="pill pill-off"><?php echo t('res.inactive'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int) $row['user_count']; ?></td>
                    <td><?php echo (int) $row['paid_count']; ?></td>
                    <td><?php echo number_format((float) $row['paid_total_eur'], 2, '.', ''); ?></td>
                    <td>
                        <form method="post" action="3wid_resellers.php">
                            <input type="hidden" name="csrf_token" value="<?php echo res_e($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn toggle" type="submit"><?php echo (int) $row['active'] === 1 ? t('res.deactivate') : t('res.activate'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-size:18px;margin-bottom:14px;"><?php echo t('res.add'); ?></h2>
        <form method="post" action="3wid_resellers.php" class="add-grid">
            <input type="hidden" name="csrf_token" value="<?php echo res_e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="res_code"><?php echo t('res.code'); ?></label>
                <input id="res_code" name="code" type="text" maxlength="20" autocapitalize="characters" spellcheck="false" required placeholder="<?php echo t('res.code_ph'); ?>" value="<?php echo res_e($form_code); ?>">
            </div>
            <div>
                <label for="res_name"><?php echo t('res.name'); ?></label>
                <input id="res_name" name="name" type="text" maxlength="120" required placeholder="<?php echo t('res.name_ph'); ?>" value="<?php echo res_e($form_name); ?>">
            </div>
            <div>
                <label for="res_contact"><?php echo t('res.contact'); ?></label>
                <input id="res_contact" name="contact" type="text" maxlength="255" placeholder="<?php echo t('res.contact_ph'); ?>" value="<?php echo res_e($form_contact); ?>">
            </div>
            <div>
                <label class="active-row">
                    <input type="checkbox" name="active" value="1" checked>
                    <?php echo t('res.active'); ?>
                </label>
                <button class="btn btn-primary" type="submit"><?php echo t('res.add'); ?></button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
