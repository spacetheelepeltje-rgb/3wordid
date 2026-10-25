<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$data = current_user_from_session($conn);

if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode('Please log in.'));
    exit;
}

$admin_id = 4;

if ((int)$data['id'] !== $admin_id) {
    header('Location: 3wid_list.php?message=' . rawurlencode('Not allowed.'));
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
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3WordID — Users</title>
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
    <a class="brand" href="<?php echo $main_url; ?>" title="to 3wordID.com">
        <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
        3WordID
    </a>
    <div class="header-actions">
        <a class="btn btn-ghost" href="3wid_list.php">Dashboard</a>
        <a class="btn btn-ghost" href="3wid_admin_3wids.php">All 3WordIDs</a>
        <a class="btn btn-primary" href="3wid_user_list.php?order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>">
            Sort <?php echo $order === 'ASC' ? 'newest' : 'oldest'; ?>
        </a>
        <a href="login/logout.php" class="logout-icon" title="log out">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Logout Icon">
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
            <h1>Users</h1>
            <p class="lede">google_users records. Restricted to admin id <?php echo (int)$admin_id; ?>.</p>
        </div>
    </div>

    <?php if ($message !== '') { ?>
        <div class="flash"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <div class="stats">
        <div class="stat"><span>This page</span><strong><?php echo (int)$count; ?></strong></div>
        <div class="stat"><span>Page</span><strong><?php echo (int)$page; ?></strong></div>
        <div class="stat"><span>Order</span><strong><?php echo htmlspecialchars($order); ?></strong></div>
    </div>

    <form class="toolbar" method="get" action="3wid_admin_3wids.php">
        <input class="search" type="search" name="q" placeholder="Search 3WordIDs by words, owner name or user id">
        <button class="btn btn-primary" type="submit">Search 3WordIDs</button>
    </form>

    <div class="toolbar">
        <?php if ($page > 0) { ?>
            <a class="btn btn-ghost" href="3wid_user_list.php?page=<?php echo $page - 1; ?>&amp;order=<?php echo urlencode($order); ?>">Previous</a>
        <?php } ?>
        <?php if ($count >= $page_size) { ?>
            <a class="btn btn-ghost" href="3wid_user_list.php?page=<?php echo $page + 1; ?>&amp;order=<?php echo urlencode($order); ?>">Next</a>
        <?php } ?>
    </div>

    <div class="panel">
        <?php if ($count === 0) { ?>
            <div class="empty">No users returned for this page.</div>
        <?php } else { ?>
            <div class="id-row head">
                <div class="meta">Id</div>
                <div class="meta">Name</div>
                <div class="meta">Login via</div>
                <div class="meta">Type</div>
                <div class="meta">Credit</div>
                <div class="meta">3WordIDs</div>
                <div class="meta">Last login</div>
                <div class="meta">Consent</div>
                <div class="meta"></div>
            </div>
            <?php foreach ($users as $user) {
                $nr_3wids = (int)($user['nr_3wids'] ?? 0);
                $last_login_at = (string)($user['last_login_at'] ?? '');
                $last_login_ts = $last_login_at !== '' ? strtotime($last_login_at) : false;
                $last_login_text = $last_login_ts ? date('Y-m-d H:i', $last_login_ts) : 'never';
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
                    <div class="meta">type <?php echo htmlspecialchars((string)($user['user_type'] ?? '')); ?></div>
                    <div class="meta"><?php echo htmlspecialchars((string)($user['credit'] ?? '0')); ?> €</div>
                    <div>
                        <a class="count-link" href="3wid_user_3wids.php?user_id=<?php echo (int)$user['id']; ?>" title="Show this user's 3WordIDs"><?php echo $nr_3wids; ?></a>
                    </div>
                    <div class="meta" title="Last login"><?php echo htmlspecialchars($last_login_text); ?></div>
                    <div>
                        <?php if ((int)($user['privacy_consent'] ?? 0) === 1) { ?>
                            <span class="pill on">Consent</span>
                        <?php } else { ?>
                            <span class="pill">No consent</span>
                        <?php } ?>
                    </div>
                    <div class="row-actions">
                        <a class="btn btn-ghost" href="3wid_user_3wids.php?user_id=<?php echo (int)$user['id']; ?>">3WordIDs</a>
                        <a class="btn btn-ghost" href="3wid_user_edit.php?id=<?php echo (int)$user['id']; ?>">Edit</a>
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
