<?php
// Shared site footer (used by index.php and terms.php).
// The "Cookie settings" link is added to this <footer> by the cookie banner script
// ($google_stats in php/functions.php), so keep the element a plain <footer>.
// Optional variables set by the page before including this file:
//   $footer       text from login/config.php
//   $footer_note  extra line of text (for example the 'no rights' disclaimer)
?>
    <footer class="site-footer">
        <div class="footer-text">
            <?php echo $footer ?? ''; ?>
            <?php if (!empty($footer_note)) { echo '<div>' . htmlspecialchars($footer_note, ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
        </div>
        <div class="footer-links">
            <a href="https://x.com/climatebabes/status/1921113933592584660">Docs on X</a>
            <a href="https://github.com/spacetheelepeltje-rgb/3wordid">Open source on GitHub</a>
            <a href="/terms.php">Terms</a>
        </div>
    </footer>
