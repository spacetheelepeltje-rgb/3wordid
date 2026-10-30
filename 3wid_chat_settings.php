<?php
// Chat settings of one 3WordID, for its owner (admin) only. Opened from the key link next
// to "Link to chat" on the edit page (3wid_3wid.php) and the key icon on the list page.
// One <section class="cl-card cl-setting"> per setting; add new settings (for example an
// uploaded picture) as another section with its own "do" value in the POST handler.
// Now: Chat login - turn it on/off and set or change the password. The password is never shown.
// Every change raises chat_pw_version, which logs out an active member session at once.

require_once 'php/functions.php';
require_once 'login/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$data = current_user_from_session($conn);
if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
}

$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));

// Only the owner of this 3WordID, and only when the feature is there and allowed
$owned = db_3wordid_owned_by($id, $data['id']);
if (!$owned || !chat_login_ready() || !chat_login_allowed($data)) {
    header('Location: 3wid_list.php');
    exit;
}

$self = '3wid_chat_settings.php?id=' . (int) $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash = array('ok' => '', 'err' => '');
    if (!check_action_token($_POST['action_token'] ?? '')) {
        $flash['err'] = 'common.session_expired_retry';
    } else {
        $do = (string) ($_POST['do'] ?? '');
        if ($do === 'password') {
            $pw = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['password_confirm'] ?? '');
            $error = chat_password_error($pw, $confirm);
            if ($error !== '') {
                $flash['err'] = $error;
            } elseif (db_chat_login_set_password($id, $data['id'], password_hash($pw, PASSWORD_DEFAULT))) {
                $flash['ok'] = 'cl.saved_pw';
            } else {
                $flash['err'] = 'cl.save_failed';
            }
        } elseif ($do === 'off' || $do === 'on') {
            $on = ($do === 'on');
            if (db_chat_login_set_on($id, $data['id'], $on)) {
                $flash['ok'] = $on ? 'cl.saved_on' : 'cl.saved_off';
            } else {
                $flash['err'] = 'cl.save_failed';
            }
        }
    }
    // Never keep the typed password anywhere; show the result once after the redirect
    $_SESSION['chat_settings_flash'] = array('id' => (int) $id) + $flash;
    header('Location: ' . $self);
    exit;
}

$flash = array('ok' => '', 'err' => '');
if (!empty($_SESSION['chat_settings_flash']) && (int) ($_SESSION['chat_settings_flash']['id'] ?? 0) === (int) $id) {
    $flash['ok'] = (string) ($_SESSION['chat_settings_flash']['ok'] ?? '');
    $flash['err'] = (string) ($_SESSION['chat_settings_flash']['err'] ?? '');
}
unset($_SESSION['chat_settings_flash']);

$row = db_chat_login_get($id);
$is_on = ((int) ($row['chat_login'] ?? 0) === 1);
$has_pw = ((string) ($row['chat_pw_hash'] ?? '') !== '');
$action_token = get_action_token();
$login_url = 'https://3wordid.com/3wid_chat_login.php';
$e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('cl.settings_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="css/chat_login.css?v=2">
</head>
<body class="cl-body">
    <header class="cl-header">
        <a class="cl-brand" href="index.php"><img src="img/3wid_big.png" alt="">3WordID</a>
        <div class="cl-header-actions">
            <?php echo lang_switcher(); ?>
            <a class="cl-btn cl-btn-ghost" href="3wid_3wid.php?id=<?php echo (int) $id; ?>"><?php echo t('cl.back_edit'); ?></a>
            <a class="cl-btn cl-btn-ghost cl-hide-sm" href="3wid_list.php"><?php echo t('nav.back_list'); ?></a>
        </div>
    </header>

    <main class="cl-wrap wide">
        <div class="cl-eyebrow"><?php echo t('cl.settings_eyebrow'); ?></div>
        <h1><?php echo t('cl.settings_h1'); ?></h1>
        <p class="cl-sub"><?php echo t_raw('cl.settings_sub', ['words' => $row['threeword']]); ?></p>

        <?php if ($flash['err'] !== ''): ?>
        <p class="cl-flash err" role="alert"><?php echo t($flash['err'], ['n' => $flash['err'] === 'cl.pw_long' ? CHAT_PW_MAX : CHAT_PW_MIN]); ?></p>
        <?php elseif ($flash['ok'] !== ''): ?>
        <p class="cl-flash ok" role="status"><?php echo t($flash['ok']); ?></p>
        <?php endif; ?>

        <!-- Setting: Chat login -->
        <h2 class="cl-section-h" id="clLoginH"><?php echo chat_login_key_icon(); ?><?php echo t('cl.login_section_h'); ?></h2>
        <p class="cl-section-p"><?php echo t('cl.settings_lede'); ?></p>

        <section class="cl-card" aria-labelledby="clStatus">
            <p class="cl-status<?php echo $is_on ? ' on' : ''; ?>" id="clStatus"><span class="cl-dot" aria-hidden="true"></span><?php echo $is_on ? t('cl.status_on') : t('cl.status_off'); ?></p>
            <?php if ($is_on): ?>
            <p><?php echo t('cl.status_on_p', ['url' => $login_url]); ?></p>
            <?php if ((int) $row['enabled'] !== 1): ?><p><?php echo t('cl.status_switched_off'); ?></p><?php endif; ?>
            <form method="post" action="<?php echo $e($self); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                <input type="hidden" name="action_token" value="<?php echo $e($action_token); ?>">
                <input type="hidden" name="do" value="off">
                <button type="submit" class="cl-btn cl-btn-ghost"><?php echo t('cl.turn_off'); ?></button>
            </form>
            <p class="cl-help" style="margin:10px 0 0"><?php echo t('cl.turn_off_help'); ?></p>
            <?php elseif ($has_pw): ?>
            <p><?php echo t('cl.status_off_p'); ?></p>
            <form method="post" action="<?php echo $e($self); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                <input type="hidden" name="action_token" value="<?php echo $e($action_token); ?>">
                <input type="hidden" name="do" value="on">
                <button type="submit" class="cl-btn cl-btn-primary"><?php echo t('cl.turn_on'); ?></button>
            </form>
            <p class="cl-help" style="margin:10px 0 0"><?php echo t('cl.turn_on_help'); ?></p>
            <?php else: ?>
            <p><?php echo t('cl.no_pw_yet'); ?></p>
            <?php endif; ?>
        </section>

        <section class="cl-card" aria-labelledby="clPw">
            <h2 id="clPw"><?php echo $has_pw ? t('cl.pw_h_change') : t('cl.pw_h'); ?></h2>
            <p><?php echo t('cl.pw_help', ['n' => CHAT_PW_MIN]); ?></p>
            <form method="post" action="<?php echo $e($self); ?>" autocomplete="off" id="clPwForm">
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                <input type="hidden" name="action_token" value="<?php echo $e($action_token); ?>">
                <input type="hidden" name="do" value="password">
                <label class="cl-field" for="password"><?php echo t('cl.pw_new'); ?></label>
                <input class="cl-input" type="password" id="password" name="password" required minlength="<?php echo CHAT_PW_MIN; ?>" maxlength="<?php echo CHAT_PW_MAX; ?>" autocomplete="new-password" aria-describedby="clPwRules">
                <ul class="cl-rules" id="clPwRules" data-min="<?php echo CHAT_PW_MIN; ?>">
                    <li data-rule="len"><?php echo t('cl.rule_len', ['n' => CHAT_PW_MIN]); ?></li>
                    <li data-rule="letter"><?php echo t('cl.rule_letter'); ?></li>
                    <li data-rule="digit"><?php echo t('cl.rule_digit'); ?></li>
                    <li data-rule="symbol"><?php echo t('cl.rule_symbol'); ?></li>
                </ul>
                <label class="cl-field" for="password_confirm"><?php echo t('cl.pw_confirm'); ?></label>
                <input class="cl-input" type="password" id="password_confirm" name="password_confirm" required minlength="<?php echo CHAT_PW_MIN; ?>" maxlength="<?php echo CHAT_PW_MAX; ?>" autocomplete="new-password">
                <button type="submit" class="cl-btn cl-btn-primary"><?php echo ($has_pw && $is_on) ? t('cl.pw_save') : t('cl.pw_save_on'); ?></button>
            </form>
        </section>

        <p class="cl-hint"><?php echo t('cl.admin_reads'); ?></p>

        <!-- More settings (for example an uploaded picture) go here, each in its own section -->
    </main>

    <footer class="cl-footer"><?php echo site_copyright(); ?></footer>
    <script src="js/chat_login.js?v=1"></script>
</body>
</html>
