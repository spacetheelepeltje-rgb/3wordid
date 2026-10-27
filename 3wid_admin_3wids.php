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

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($q) > 100) {
    $q = substr($q, 0, 100);
}
$page = max(0, (int)($_GET['page'] ?? 0));
$page_size = 50;

$wids = db_3wordid_admin_search($q, $page, $page_size);
if (!is_array($wids)) {
    $wids = [];
}
$has_next = count($wids) > $page_size;
if ($has_next) {
    $wids = array_slice($wids, 0, $page_size);
}
$count = count($wids);

$message = isset($_GET['message']) ? (string)$_GET['message'] : '';

function admin_safe_url($url) {
    $url = trim((string)$url);
    return preg_match('#^https?://#i', $url) ? $url : '';
}

function admin_page_link($page, $q) {
    $params = ['page' => (int)$page];
    if ($q !== '') {
        $params['q'] = $q;
    }
    return '3wid_admin_3wids.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('aa.title'); ?></title>
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
      .wrap { max-width: 1100px; margin: 0 auto; padding: 28px 24px 64px; }
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
        grid-template-columns: 70px minmax(160px, 1.2fr) minmax(120px, .8fr) 90px 80px 90px 200px;
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
        .page-head { align-items: flex-start; }
      }
      .id-row { grid-template-columns: 60px minmax(160px, 1.2fr) minmax(140px, 1fr) minmax(130px, .9fr) 60px 70px 110px 80px; }
      .forward { overflow-wrap: anywhere; }
      .search { flex: 1 1 280px; border: 1px solid var(--line); border-radius: 10px; padding: 10px 14px; font: 15px/1.4 Inter, system-ui, sans-serif; color: var(--navy); background: #fff; }
      @media (max-width: 800px) { .id-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?php echo htmlspecialchars($main_url); ?>" title="<?php echo t('admin.to_home'); ?>">
        <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
        3WordID
    </a>
    <div class="header-actions">
        <?php echo lang_switcher(); ?>
        <a class="btn btn-ghost" href="3wid_user_list.php"><?php echo t('ue.all_users'); ?></a>
        <a class="btn btn-ghost" href="3wid_list.php"><?php echo t('admin.dashboard'); ?></a>
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
            <h1><?php echo t('admin.all_3wids'); ?></h1>
            <p class="lede"><?php echo t('aa.lede', ['id' => (int)$admin_id]); ?></p>
        </div>
    </div>

    <?php if ($message !== '') { ?>
        <div class="flash"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <form class="toolbar" method="get" action="3wid_admin_3wids.php">
        <input class="search" type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="<?php echo t('aa.ph'); ?>" autofocus>
        <button class="btn btn-primary" type="submit"><?php echo t('common.search'); ?></button>
        <?php if ($q !== '') { ?>
            <a class="btn btn-ghost" href="3wid_admin_3wids.php"><?php echo t('aa.clear'); ?></a>
        <?php } ?>
    </form>

    <div class="stats">
        <div class="stat"><span><?php echo t('ul.this_page'); ?></span><strong><?php echo (int)$count; ?></strong></div>
        <div class="stat"><span><?php echo t('ul.page'); ?></span><strong><?php echo (int)$page; ?></strong></div>
        <div class="stat"><span><?php echo t('common.search'); ?></span><strong><?php echo $q === '' ? t('aa.all') : htmlspecialchars($q); ?></strong></div>
    </div>

    <div class="toolbar">
        <?php if ($page > 0) { ?>
            <a class="btn btn-ghost" href="<?php echo htmlspecialchars(admin_page_link($page - 1, $q)); ?>"><?php echo t('ul.prev'); ?></a>
        <?php } ?>
        <?php if ($has_next) { ?>
            <a class="btn btn-ghost" href="<?php echo htmlspecialchars(admin_page_link($page + 1, $q)); ?>"><?php echo t('ul.next'); ?></a>
        <?php } ?>
    </div>

    <div class="panel">
        <?php if ($count === 0) { ?>
            <div class="empty"><?php echo t('aa.empty'); ?></div>
        <?php } else { ?>
            <?php foreach ($wids as $w) {
                $words_url = implode('.', explode(' ', (string)$w['threeword']));
                $forward = admin_safe_url($w['linkthru'] ?? '');
                $is_on = (int)$w['enabled'] === 1;
                $owner_id = (int)$w['user_id'];
                $owner_name = trim((string)($w['owner_name'] ?? ''));
                if ($owner_name === '') {
                    $owner_name = t_plain('u3.user_n', ['id' => $owner_id]);
                }
            ?>
                <div class="id-row">
                    <div class="meta">#<?php echo (int)$w['id']; ?></div>
                    <div>
                        <a class="id-name" href="https://3wordid.com/<?php echo htmlspecialchars(rawurlencode($words_url)); ?>" target="_blank" rel="noopener">
                            <?php echo htmlspecialchars((string)$w['threeword']); ?>
                        </a>
                        <div class="meta">3wordid.com/<?php echo htmlspecialchars($words_url); ?></div>
                    </div>
                    <div class="meta forward">
                        <?php if ($forward !== '') { ?>
                            <a href="<?php echo htmlspecialchars($forward); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($forward); ?></a>
                            <?php if ((int)$w['linkthruflag'] !== 1) { ?><br><?php echo t('u3.fwd_unused'); ?><?php } ?>
                        <?php } else { ?>
                            <?php echo t('u3.no_forward'); ?>
                        <?php } ?>
                    </div>
                    <div>
                        <a class="id-name" href="3wid_user_3wids.php?user_id=<?php echo $owner_id; ?>" title="<?php echo t('aa.owner_title'); ?>"><?php echo htmlspecialchars($owner_name); ?></a>
                        <div class="meta"><?php echo t('aa.user_n', ['id' => $owner_id]); ?></div>
                    </div>
                    <div>
                        <span class="pill<?php echo $is_on ? ' on' : ''; ?>"><?php echo $is_on ? t('common.on') : t('common.off'); ?></span>
                    </div>
                    <div class="meta"><?php echo t_n('home.views', (int)$w['views']); ?></div>
                    <div class="meta"><?php echo htmlspecialchars((string)$w['creation_date']); ?></div>
                    <div>
                        <a class="btn btn-ghost" href="3wid_3wid.php?id=<?php echo (int)$w['id']; ?>&amp;from=admin"><?php echo t('common.edit'); ?></a>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</main>

<footer class="site-footer">
    <div><?php echo $footer; ?></div>
    <?php echo site_copyright(); ?>
</footer>
</body>
</html>
