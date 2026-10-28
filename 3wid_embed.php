<?php
/**
 * 3wid_embed.php - "Search box for your website".
 *
 * Shows a small piece of plain HTML that anyone can paste into their own website
 * to get a 3WordID search box there. The snippet is a normal form that sends the
 * three words to https://3wordid.com/3wid_forward.php with POST, exactly like the
 * search box on the homepage, so nothing new is exposed: no suggestions, no
 * autocomplete, no existence check.
 *
 * Linked from the list page (3wid_list.php). The page itself is public because
 * it contains nothing private: logged-in users get "Back to list", everybody
 * else gets "To Homepage".
 */

  require_once 'php/functions.php';
  require_once 'login/config.php';

  $data = current_user_from_session($conn);
  $logged_in = ($data != NULL);

  if ($logged_in) {
      $back_href  = '3wid_list.php';
      $back_label = t('nav.back_list');
  } else {
      $back_href  = $main_url;
      $back_label = t('notif.home');
  }

  // The snippet, in the language of this page. t() escapes the texts for HTML.
  $snippet = '<!-- 3WordID search box - https://3wordid.com -->' . "\n"
    . '<form action="https://3wordid.com/3wid_forward.php" method="post" target="_blank" rel="noopener" accept-charset="UTF-8" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;max-width:440px;margin:0;font-family:Inter,system-ui,-apple-system,\'Segoe UI\',Arial,sans-serif">' . "\n"
    . '  <input type="text" name="threewords" required maxlength="80" autocomplete="off" placeholder="' . t('embed.snippet_placeholder') . '" aria-label="3WordID" style="flex:1 1 180px;min-width:0;box-sizing:border-box;padding:10px 12px;font-size:16px;border:1px solid #D9D5CC;border-radius:10px;color:#0F1C3F;background:#fff">' . "\n"
    . '  <button type="submit" style="padding:10px 16px;font-size:15px;font-weight:600;border:0;border-radius:10px;background:#0F1C3F;color:#fff;cursor:pointer">' . t('embed.snippet_button') . '</button>' . "\n"
    . '  <a href="https://3wordid.com/" target="_blank" rel="noopener" style="flex-basis:100%;font-size:12px;color:#8A93A6;text-decoration:none">' . t('embed.snippet_powered') . '</a>' . "\n"
    . '</form>';
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <?php echo $google_stats ?? ''; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('embed.page_title'); ?> — 3WordID</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
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
        --ok: #16794A;
        --shadow: 0 10px 30px rgba(15,28,63,.08);
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
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
        padding: 16px 28px;
        background: rgba(247,246,243,.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--line);
        position: sticky; top: 0; z-index: 20;
      }
      .brand {
        display: flex; align-items: center; gap: 10px;
        text-decoration: none; color: var(--navy);
        font-weight: 800; letter-spacing: -.03em; font-size: 20px;
      }
      .brand-mark {
        width: 34px; height: 34px; border-radius: 8px; background: var(--navy);
        display: grid; place-items: center; overflow: hidden;
      }
      .brand-mark img { width: 35px; height: 35px; object-fit: cover; display: block; }
      .header-actions { display: flex; align-items: center; gap: 10px; }
      .btn {
        border: 0; cursor: pointer; border-radius: 10px;
        font-weight: 600; font-family: inherit; text-decoration: none;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 14px; font-size: 14px; min-height: 40px;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-2); }
      .btn-primary.is-copied { background: var(--ok); }
      .btn-ghost { background: #fff; color: var(--navy); border: 1px solid var(--line); }
      .btn-ghost:hover { border-color: #cfcabf; }
      .btn:focus-visible { outline: 2px solid var(--navy); outline-offset: 2px; }
      .wrap { max-width: 760px; margin: 0 auto; padding: 32px 24px 64px; }
      .back-row { margin-bottom: 18px; }
      h1 { margin: 0 0 8px; font-size: clamp(26px, 5vw, 38px); letter-spacing: -.04em; font-weight: 800; line-height: 1.15; }
      .lede { margin: 0 0 20px; color: var(--slate); font-size: 16px; line-height: 1.5; }
      .panel {
        background: var(--white); border: 1px solid #ECEAE4; border-radius: 16px;
        box-shadow: var(--shadow); padding: 20px; margin-bottom: 18px;
      }
      .label { display: block; margin: 0 0 8px; color: var(--muted); font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
      .code-box {
        display: block; width: 100%; height: 170px; margin: 0; resize: vertical;
        padding: 14px; border: 1px solid var(--line); border-radius: 12px;
        background: #0F1C3F; color: #E8ECF5;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        font-size: 13px; line-height: 1.55; white-space: pre; overflow: auto; tab-size: 2;
      }
      .copy-row { display: flex; align-items: center; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
      .copy-status { color: var(--ok); font-size: 14px; font-weight: 600; min-height: 1em; }
      .copy-status.is-error { color: var(--slate); font-weight: 500; }
      .copy-status:empty { display: none; }
      .note { margin: 10px 0 0; color: var(--slate); font-size: 14px; }
      .preview { padding: 16px; border: 1px dashed #D9D5CC; border-radius: 12px; background: #fff; }
      .site-footer { border-top: 1px solid var(--line); padding: 18px 28px 28px; color: var(--muted); font-size: 13px; }
      @media (max-width: 720px) {
        .site-header { position: static; padding: 10px 16px; }
        .wrap { padding: 18px 14px 48px; }
        .panel { padding: 14px; }
        .code-box { height: 180px; font-size: 12px; }
        .copy-row .btn { flex: 1 1 100%; min-height: 44px; }
        .back-row .btn { min-height: 44px; }
        .site-footer { padding: 16px 16px 24px; }
      }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url; ?>" title="<?php echo t('nav.to_home'); ?>">
            <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
            3WordID
        </a>
        <div class="header-actions">
            <?php echo lang_switcher(); ?>
        </div>
    </header>

    <main class="wrap">
        <div class="back-row">
            <a class="btn btn-ghost" id="backBtn" href="<?php echo htmlspecialchars($back_href, ENT_QUOTES, 'UTF-8'); ?>">&larr; <?php echo $back_label; ?></a>
        </div>

        <h1><?php echo t('embed.h1'); ?></h1>
        <p class="lede"><?php echo t('embed.lede'); ?></p>

        <section class="panel">
            <label class="label" for="embedCode"><?php echo t('embed.code_label'); ?></label>
            <textarea id="embedCode" class="code-box" readonly spellcheck="false" wrap="off"><?php echo htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="copy-row">
                <button type="button" class="btn btn-primary" id="copyBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                        <path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/>
                    </svg>
                    <span id="copyLabel"><?php echo t('embed.copy'); ?></span>
                </button>
                <span class="copy-status" id="copyStatus" role="status" aria-live="polite"></span>
            </div>
            <p class="note"><?php echo t('embed.note'); ?></p>
        </section>

        <section class="panel">
            <span class="label"><?php echo t('embed.preview'); ?></span>
            <div class="preview" id="embedPreview"><?php echo $snippet; ?></div>
        </section>
    </main>
    <footer class="site-footer"><?php echo $footer; ?><?php echo site_copyright(); ?></footer>
<script>
(function () {
    var box = document.getElementById('embedCode');
    var btn = document.getElementById('copyBtn');
    var label = document.getElementById('copyLabel');
    var status = document.getElementById('copyStatus');
    var T = <?php echo json_encode(['copy' => t_plain('embed.copy'), 'copied' => t_plain('embed.copied'), 'failed' => t_plain('embed.copy_failed')]); ?>;
    var timer = null;

    function done(ok) {
        clearTimeout(timer);
        status.classList.toggle('is-error', !ok);
        if (ok) {
            btn.classList.add('is-copied');
            label.textContent = T.copied;
            status.textContent = '';
        } else {
            box.focus();
            box.select();
            status.textContent = T.failed;
        }
        timer = setTimeout(function () {
            btn.classList.remove('is-copied');
            label.textContent = T.copy;
        }, 2000);
    }

    function fallback() {
        box.focus();
        box.select();
        box.setSelectionRange(0, box.value.length);
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        done(ok);
    }

    btn.addEventListener('click', function () {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(box.value).then(function () { done(true); }, fallback);
        } else {
            fallback();
        }
    });
    // one click in the code window selects all of it
    box.addEventListener('focus', function () { box.select(); });
})();
</script>
</body>
</html>
