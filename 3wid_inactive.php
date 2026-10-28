<?php
/**
 * 3wid_inactive.php - shown to a visitor who opens a 3WordID that exists but is
 * switched off by its owner (enabled = 0, e.g. closed for the weekend).
 *
 *   3wid_inactive.php?threewords=red+blue+dog  shows the three words above the text,
 *                                              but only when that 3WordID really is
 *                                              switched off: switched on goes to
 *                                              3wid_forward.php, not found goes to the
 *                                              homepage (same as before).
 *   3wid_inactive.php                          the same page without the words.
 *
 * Nothing about the owner or what the 3WordID points to is shown.
 */

  require_once 'php/functions.php';
  require_once 'login/config.php';

  header('X-Robots-Tag: noindex');

  $words = '';
  if (isset($_GET['threewords'])) {
      $words = preg_replace('/\s+/', ' ', trim((string) $_GET['threewords']));
      if ($words === '' || strlen($words) > 80) {
          header('location:' . $main_url);
          exit;
      }
      $word_row = db_3wordid_get_threeword($words);
      if ($word_row == NULL || empty($word_row['id'])) {
          header('location:' . $main_url);
          exit;
      }
      if ((int) $word_row['enabled'] === 1) {
          header('location:3wid_forward.php?threewords=' . rawurlencode($word_row['threeword']));
          exit;
      }
      $words = (string) $word_row['threeword'];
  }

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?php echo t('inactive.page_title'); ?> — 3WordID</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link href="/css/inter.css" rel="stylesheet">
    <style>
      :root {
        --navy: #0F1C3F;
        --navy-2: #162756;
        --slate: #5B6478;
        --muted: #8A93A6;
        --line: #E6E3DC;
        --paper: #F7F6F3;
        --white: #FFFFFF;
        --gold: #B45309;
        --shadow: 0 10px 30px rgba(15,28,63,.08);
        --chip: #EFECE6;
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
        background: rgba(247,246,243,.92);
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
        letter-spacing: -.03em;
        font-size: 20px;
      }
      .brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: var(--navy);
        display: grid;
        place-items: center;
        overflow: hidden;
      }
      .brand-mark img {
        width: 35px;
        height: 35px;
        object-fit: cover;
        display: block;
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
        padding: 10px 14px;
        font-size: 14px;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-2); }
      .btn-ghost { background: #fff; color: var(--navy); border: 1px solid var(--line); }
      .wrap { max-width: 720px; margin: 0 auto; padding: 48px 24px 64px; }
      .eyebrow {
        color: var(--gold);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .14em;
        margin-bottom: 8px;
      }
      h1 {
        margin: 0 0 8px;
        font-size: clamp(32px, 5vw, 44px);
        letter-spacing: -.04em;
        font-weight: 800;
      }
      .lede { margin: 0 0 22px; color: var(--slate); font-size: 16px; }
      .panel {
        background: var(--white);
        border: 1px solid #ECEAE4;
        border-radius: 16px;
        box-shadow: var(--shadow);
        padding: 22px;
      }
      .buttons { margin-top: 18px; }
      .site-footer {
        border-top: 1px solid var(--line);
        padding: 18px 28px 28px;
        color: var(--muted);
        font-size: 13px;
      }
      .inactive-panel { text-align: center; padding: 36px 22px; }
      .inactive-icon {
        width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 50%;
        background: var(--chip); color: var(--slate);
        display: grid; place-items: center;
      }
      .inactive-icon svg { stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
      .inactive-words { margin: 0 0 6px; color: var(--muted); font-size: 14px; font-weight: 600; overflow-wrap: anywhere; }
      .inactive-title { margin: 0 0 8px; font-size: clamp(22px, 4vw, 28px); letter-spacing: -.02em; font-weight: 800; line-height: 1.25; }
      .inactive-sub { margin: 0 0 22px; color: var(--slate); font-size: 16px; }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url;?>" title="<?php echo t('msgview.home_title'); ?>">
            <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
            3WordID
        </a>
        <div class="header-actions">
            <?php echo lang_switcher(); ?>
        </div>
    </header>

    <main class="wrap">
        <div class="panel inactive-panel">
            <div class="inactive-icon" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <?php if ($words !== ''): ?>
            <p class="inactive-words"><?php echo htmlspecialchars($words, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <h1 class="inactive-title"><?php echo t('inactive.title'); ?></h1>
            <p class="inactive-sub"><?php echo t('inactive.sub'); ?></p>
            <a class="btn btn-primary" href="<?php echo $main_url; ?>"><?php echo t('notif.home'); ?></a>
        </div>
    </main>
    <footer class="site-footer"><?php echo $footer; ?><?php echo site_copyright(); ?></footer>
</body>
</html>
