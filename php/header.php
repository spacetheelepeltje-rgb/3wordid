<?php
// Shared site header (used by index.php and terms.php).
// Optional variables set by the page before including this file:
//   $main_url      link for the logo (defaults to /)
//   $loginUrl      login/sign-up page (defaults to /login/options.php)
//   $header_extra  extra HTML placed before the 'Get started' button
$hdr_main_url  = isset($main_url) && $main_url !== '' ? $main_url : '/';
$hdr_login_url = isset($loginUrl) && $loginUrl !== '' ? $loginUrl : '/login/options.php';
?>
    <header class="site-header">
        <a class="brand" href="<?php echo htmlspecialchars($hdr_main_url, ENT_QUOTES, 'UTF-8'); ?>">
            <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
            3WordID
        </a>
        <nav class="nav-links">
            <a href="/plans.php">Plans</a>
            <a href="/terms.php">Terms</a>
        </nav>
        <div class="header-actions" id="userContainer">
            <?php echo $header_extra ?? ''; ?>
            <a href="<?php echo htmlspecialchars($hdr_login_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Get started</a>
            <a href="<?php echo htmlspecialchars($hdr_login_url, ENT_QUOTES, 'UTF-8'); ?>" class="login-icon" title="Log in" aria-label="Log in">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
                    <polyline points="8 7 13 12 8 17"></polyline>
                    <line x1="13" y1="12" x2="1" y2="12"></line>
                </svg>
            </a>
        </div>
    </header>
