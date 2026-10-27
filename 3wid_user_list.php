<?php
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

$page = max(0, (int)($_GET['page'] ?? 0));
$page_size = 50;
$order = (isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC') ? 'ASC' : 'DESC';

$users = db_3wordid_get_google_users($page, $page_size, $order);
if (!is_array($users)) {
    $users = [];
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
$count = count($users);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('ul.title'); ?></title>
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
        --white: #ffffff;
        --gold: #b45309;
        --ok: #16794a;
        --danger: #9b1c1c;
        --shadow: 0 10px 30px rgba(15, 28, 63, 0.08);
      }
      * { box-sizing: border-box; }
      html, body { margin: 0; padding: 0; }
      body {
        font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
        background: var(--paper);
        color: var(--navy);
        min-height: 100vh;
      }
      a { color: var(--navy); }
      .site-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 28px;
        background: rgba(247,246,243,0.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--line);
        position: sticky;
        top: 0;
        z-index: 20;
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--navy);
        font-weight: 800;
        letter-spacing: -0.03em;
        font-size: 20px;
      }
      .brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        overflow: hidden;
        background: var(--navy);
      }
      .header-actions { display: flex; align-items: center; gap: 10px; }
      .btn {
        border: 0;
        cursor: pointer;
        border-radius: 10px;
        font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 14px;
        font-size: 14px;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-2); }
      .btn-ghost { background: #fff; color: var(--navy); border: 1px solid var(--line); }
      .logout-icon { color: var(--navy); display: inline-flex; padding: 6px; }
      .wrap { max-width: 1240px; margin: 0 auto; padding: 28px 24px 64px; }
      .page-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
      }
      h1 { margin: 0 0 6px; font-size: 32px; letter-spacing: -0.04em; }
      .lede { margin: 0; color: var(--slate); }
      .flash {
        background: #fff;
        border: 1px solid #eceae4;
        border-left: 4px solid var(--gold);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 18px;
        font-weight: 600;
      }
      .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 18px; }
      .stat { background: #fff; border: 1px solid #eceae4; border-radius: 14px; padding: 16px; }
      .stat span { display: block; color: var(--muted); font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
      .stat strong { font-size: 22px; }
      .search { flex: 1 1 280px; border: 1px solid var(--line); border-radius: 10px; padding: 10px 14px; font: 15px/1.4 Inter, system-ui, sans-serif; color: var(--navy); background: #fff; }
      .toolbar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
      .panel {
        background: #fff;
        border: 1px solid #eceae4;
        border-radius: 16px;
        box-shadow: var(--shadow);
        overflow: hidden;
      }
      .id-row {
        display: grid;
        grid-template-columns: 60px minmax(160px, 1.2fr) minmax(90px, .7fr) 70px 70px 80px 130px 100px 190px;
        gap: 12px;
        align-items: center;
        padding: 16px 18px;
        border-top: 1px solid #f0eee8;
      }
      .id-row:first-child { border-top: 0; }
      .id-name { font-weight: 700; text-decoration: none; display: block; }
      .meta { color: var(--muted); font-size: 13px; }
      .pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 600;
        background: #efece6;
        color: var(--slate);
      }
      .pill.on { background: #e5f4ec; color: var(--ok); }
      .count-link { font-weight: 700; text-decoration: none; }
      .count-link:hover { text-decoration: underline; }
      .id-row.head { padding-top: 12px; padding-bottom: 12px; background: #faf9f6; }
      .id-row.head .meta { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
      .row-actions { display: flex; gap: 6px; flex-wrap: wrap; }
      .empty { padding: 28px 18px; color: var(--slate); }
      .site-footer {
        border-top: 1px solid var(--line);
        padding: 18px 28px 28px;
        color: var(--muted);
        font-size: 13px;
      }
      @media (max-width: 800px) {
        .stats, .id-row { grid-template-columns: 1fr; }
        .id-row.head { display: none; }
        .page-head { align-items: flex-start; }
      }
    </style>
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?php echo $main_url; ?>" title="<?php echo t('admin.to_home'); ?>">
        <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
        3WordID
    </a>
    <div class="header-actions">
        <?php echo lang_switcher(); ?>
        <a class="btn btn-ghost" href="3wid_list.php"><?php echo t('admin.dashboard'); ?></a>
        <a class="btn btn-ghost" href="3wid_admin_3wids.php"><?php echo t('admin.all_3wids'); ?></a>
        <a class="btn btn-primary" href="3wid_user_list.php?order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>">
            <?php echo $order === 'ASC' ? t('ul.sort_newest') : t('ul.sort_oldest'); ?>
        </a>
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
    <div class="page-head">
        <div>
            <h1><?php echo t('ul.h1'); ?></h1>
            <p class="lede"><?php echo t('ul.lede', ['id' => (int)$admin_id]); ?></p>
        </div>
    </div>

    <?php if ($message !== '') { ?>
        <div class="flash"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <div class="stats">
        <div class="stat"><span><?php echo t('ul.this_page'); ?></span><strong><?php echo (int)$count; ?></strong></div>
        <div class="stat"><span><?php echo t('ul.page'); ?></span><strong><?php echo (int)$page; ?></strong></div>
        <div class="stat"><span><?php echo t('ul.order'); ?></span><strong><?php echo htmlspecialchars($order); ?></strong></div>
    </div>

    <form class="toolbar" method="get" action="3wid_admin_3wids.php">
        <input class="search" type="search" name="q" placeholder="<?php echo t('admin.search_ph'); ?>">
        <button class="btn btn-primary" type="submit"><?php echo t('admin.search_btn'); ?></button>
    </form>

    <div class="toolbar">
        <?php if ($page > 0) { ?>
            <a class="btn btn-ghost" href="3wid_user_list.php?page=<?php echo $page - 1; ?>&amp;order=<?php echo urlencode($order); ?>"><?php echo t('ul.prev'); ?></a>
        <?php } ?>
        <?php if ($count >= $page_size) { ?>
            <a class="btn btn-ghost" href="3wid_user_list.php?page=<?php echo $page + 1; ?>&amp;order=<?php echo urlencode($order); ?>"><?php echo t('ul.next'); ?></a>
        <?php } ?>
    </div>

    <div class="panel">
        <?php if ($count === 0) { ?>
            <div class="empty"><?php echo t('ul.empty'); ?></div>
        <?php } else { ?>
            <div class="id-row head">
                <div class="meta"><?php echo t('ul.col_id'); ?></div>
                <div class="meta"><?php echo t('ul.col_name'); ?></div>
                <div class="meta"><?php echo t('ul.col_login'); ?></div>
                <div class="meta"><?php echo t('ul.col_type'); ?></div>
                <div class="meta"><?php echo t('ul.col_credit'); ?></div>
                <div class="meta"><?php echo t('admin.3wids'); ?></div>
                <div class="meta"><?php echo t('ul.col_last'); ?></div>
                <div class="meta"><?php echo t('ul.col_consent'); ?></div>
                <div class="meta"></div>
            </div>
            <?php foreach ($users as $user) {
                $nr_3wids = (int)($user['nr_3wids'] ?? 0);
                $last_login_at = (string)($user['last_login_at'] ?? '');
                $last_login_ts = $last_login_at !== '' ? strtotime($last_login_at) : false;
                $last_login_text = $last_login_ts ? date('Y-m-d H:i', $last_login_ts) : t_plain('ul.never');
            ?>
                <div class="id-row">
                    <div class="meta">#<?php echo (int)$user['id']; ?></div>
                    <div>
                        <a class="id-name" href="3wid_user_edit.php?id=<?php echo (int)$user['id']; ?>">
                            <?php echo htmlspecialchars((string)($user['full_name'] !== '' ? $user['full_name'] : $user['email'])); ?>
                        </a>
                        <div class="meta"><?php echo htmlspecialchars((string)$user['email']); ?></div>
                    </div>
                    <div class="meta"><?php echo htmlspecialchars((string)($user['auth_provider'] ?? '')); ?></div>
                    <div class="meta"><?php echo t('ul.type'); ?> <?php echo htmlspecialchars((string)($user['user_type'] ?? '')); ?></div>
                    <div class="meta"><?php echo htmlspecialchars((string)($user['credit'] ?? '0')); ?> €</div>
                    <div>
                        <a class="count-link" href="3wid_user_3wids.php?user_id=<?php echo (int)$user['id']; ?>" title="<?php echo t('ul.show_3wids'); ?>"><?php echo $nr_3wids; ?></a>
                    </div>
                    <div class="meta" title="<?php echo t('ul.col_last'); ?>"><?php echo htmlspecialchars($last_login_text); ?></div>
                    <div>
                        <?php if ((int)($user['privacy_consent'] ?? 0) === 1) { ?>
                            <span class="pill on"><?php echo t('ul.col_consent'); ?></span>
                        <?php } else { ?>
                            <span class="pill"><?php echo t('ul.no_consent'); ?></span>
                        <?php } ?>
                    </div>
                    <div class="row-actions">
                        <a class="btn btn-ghost" href="3wid_user_3wids.php?user_id=<?php echo (int)$user['id']; ?>"><?php echo t('admin.3wids'); ?></a>
                        <a class="btn btn-ghost" href="3wid_user_edit.php?id=<?php echo (int)$user['id']; ?>"><?php echo t('common.edit'); ?></a>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</main>

<footer class="site-footer">
    <div><?php echo $footer; ?></div>
</footer>
</body>
</html>
