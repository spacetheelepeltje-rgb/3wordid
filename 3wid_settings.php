<?php
// Account settings: settings of the whole account (not of one 3WordID). Opened from the
// "Settings" button at the top of My 3WordIDs (3wid_list.php).
// One <section> per part; add new account settings (single user / admin organisation, payment
// details) as another section with its own "do" value in the POST handler.
// Now: Account (read-only), Organization name, Account & payment history (read-only).
// Code and texts: php/account_settings.php, lang key prefix "acct.".

require_once 'php/functions.php';
require_once 'login/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$data = current_user_from_session($conn);
if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode(t_plain('common.login_required')));
    exit;
}
if ((int) ($data['privacy_consent'] ?? 0) === 0) {
    header('Location: 3wid_privacy_consent.php');
    exit;
}

$self = '3wid_settings.php';
$org_ready = org_name_ready();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash = array('ok' => '', 'err' => '', 'draft' => null);
    if (!check_action_token($_POST['action_token'] ?? '')) {
        $flash['err'] = 'common.session_expired_retry';
    } else {
        $do = (string) ($_POST['do'] ?? '');
        if ($do === 'org' && $org_ready) {
            list($name, $error) = org_name_clean($_POST['org_name'] ?? '');
            if ($error !== '') {
                $flash['err'] = $error;
                $flash['draft'] = mb_substr($name, 0, 300, 'UTF-8');
            } elseif (db_org_name_set($data['id'], $name)) {
                $flash['ok'] = $name === '' ? 'acct.org_cleared' : 'acct.org_saved';
            } else {
                $flash['err'] = 'acct.save_failed';
                $flash['draft'] = $name;
            }
        }
    }
    $_SESSION['account_settings_flash'] = $flash;
    header('Location: ' . $self);
    exit;
}

$flash = array('ok' => '', 'err' => '', 'draft' => null);
if (!empty($_SESSION['account_settings_flash']) && is_array($_SESSION['account_settings_flash'])) {
    $flash = $_SESSION['account_settings_flash'] + $flash;
}
unset($_SESSION['account_settings_flash']);

$org_name = $org_ready ? db_org_name_get($data['id']) : '';
$org_value = is_string($flash['draft']) ? $flash['draft'] : $org_name;
$holder = account_holder_name($data);
$history = account_history($data);
$has_payments = false;
foreach ($history as $h) {
    if ($h['kind'] === 'payment') { $has_payments = true; break; }
}
$action_token = get_action_token();
$e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$none = '<span class="acct-none">' . t('acct.not_set') . '</span>';
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('acct.title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="css/chat_login.css?v=3">
</head>
<body class="cl-body">
    <header class="cl-header">
        <a class="cl-brand" href="index.php"><img src="img/3wid_big.png" alt="">3WordID</a>
        <div class="cl-header-actions">
            <?php echo lang_switcher(); ?>
            <a class="cl-btn cl-btn-ghost" href="3wid_list.php"><?php echo t('nav.back_list'); ?></a>
        </div>
    </header>

    <main class="cl-wrap wide">
        <div class="cl-eyebrow"><?php echo t('acct.eyebrow'); ?></div>
        <h1><?php echo t('acct.h1'); ?></h1>
        <p class="cl-sub"><?php echo t('acct.sub'); ?></p>

        <?php if ($flash['err'] !== ''): ?>
        <p class="cl-flash err" role="alert"><?php echo t($flash['err'], ['n' => ORG_NAME_MAX]); ?></p>
        <?php elseif ($flash['ok'] !== ''): ?>
        <p class="cl-flash ok" role="status"><?php echo t($flash['ok']); ?></p>
        <?php endif; ?>

        <!-- Account (read-only) -->
        <h2 class="cl-section-h" id="acctInfoH"><?php echo t('acct.info_h'); ?></h2>
        <section class="cl-card" aria-labelledby="acctInfoH">
            <dl class="acct-dl">
                <dt><?php echo t('acct.name'); ?></dt>
                <dd><?php echo $holder !== '' ? $e($holder) : $none; ?></dd>
                <dt><?php echo t('acct.type'); ?></dt>
                <dd><span class="acct-type<?php echo ((int) ($data['user_type'] ?? 0) === 2) ? ' paid' : ''; ?>"><?php echo $e(account_type_label($data)); ?></span></dd>
                <?php if (account_date($data['created_at'] ?? '') !== ''): ?>
                <dt><?php echo t('acct.created'); ?></dt>
                <dd><?php echo $e(account_date($data['created_at'], false)); ?></dd>
                <?php endif; ?>
                <?php if (account_date($data['last_login'] ?? '') !== ''): ?>
                <dt><?php echo t('acct.last_login'); ?></dt>
                <dd><?php echo $e(account_date($data['last_login'])); ?></dd>
                <?php endif; ?>
                <?php if (trim((string) ($data['payment_method'] ?? '')) !== ''): ?>
                <dt><?php echo t('acct.pay_method'); ?></dt>
                <dd><?php echo $e($data['payment_method']); ?></dd>
                <?php endif; ?>
                <?php if (trim((string) ($data['payment_status'] ?? '')) !== ''): ?>
                <dt><?php echo t('acct.pay_status'); ?></dt>
                <dd><?php echo $e($data['payment_status']); ?></dd>
                <?php endif; ?>
            </dl>
            <p class="cl-help acct-foot"><a href="plans.php"><?php echo t('acct.see_plans'); ?></a></p>
        </section>

        <?php if ($org_ready): ?>
        <!-- Setting: Organization name -->
        <h2 class="cl-section-h" id="acctOrgH"><?php echo org_icon(18); ?><?php echo t('acct.org_h'); ?></h2>
        <p class="cl-section-p"><?php echo t('acct.org_lede'); ?></p>
        <section class="cl-card" aria-labelledby="acctOrgH">
            <form method="post" action="<?php echo $e($self); ?>" autocomplete="off">
                <input type="hidden" name="action_token" value="<?php echo $e($action_token); ?>">
                <input type="hidden" name="do" value="org">
                <label class="cl-field" for="org_name"><?php echo t('acct.org_label'); ?></label>
                <input class="cl-input" type="text" id="org_name" name="org_name" maxlength="<?php echo ORG_NAME_MAX; ?>" value="<?php echo $e($org_value); ?>" placeholder="<?php echo t('acct.org_ph'); ?>" aria-describedby="orgHelp">
                <p class="cl-help" id="orgHelp"><?php echo t('acct.org_help', ['n' => ORG_NAME_MAX]); ?></p>
                <button type="submit" class="cl-btn cl-btn-primary"><?php echo t('acct.save'); ?></button>
            </form>
        </section>
        <?php endif; ?>

        <!-- Account & payment history (read-only) -->
        <h2 class="cl-section-h" id="acctHistH"><?php echo t('acct.hist_h'); ?></h2>
        <section class="cl-card" aria-labelledby="acctHistH">
            <?php if (!$has_payments): ?>
            <p class="acct-empty"><?php echo t('acct.no_payments'); ?></p>
            <?php endif; ?>
            <?php if ($history): ?>
            <div class="acct-table-wrap">
            <table class="acct-table">
                <thead><tr>
                    <th scope="col"><?php echo t('acct.h_date'); ?></th>
                    <th scope="col"><?php echo t('acct.h_what'); ?></th>
                    <th scope="col"><?php echo t('acct.h_amount'); ?></th>
                    <th scope="col"><?php echo t('acct.h_status'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                <tr class="k-<?php echo $e($h['kind']); ?>">
                    <td data-label="<?php echo t('acct.h_date'); ?>"><?php echo $e(account_date($h['date'], false)); ?></td>
                    <td data-label="<?php echo t('acct.h_what'); ?>"><?php echo $e($h['what']); ?></td>
                    <td data-label="<?php echo t('acct.h_amount'); ?>"><?php echo $h['amount'] !== '' ? $e($h['amount']) : '—'; ?></td>
                    <td data-label="<?php echo t('acct.h_status'); ?>"><?php echo $h['status'] !== '' ? $e($h['status']) : '—'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </section>

        <!-- More account settings (single user / admin organisation, payment details) go here -->

        <p class="cl-hint"><a href="3wid_list.php">← <?php echo t('nav.back_list'); ?></a></p>
    </main>

    <footer class="cl-footer"><?php echo site_copyright(); ?></footer>
</body>
</html>
