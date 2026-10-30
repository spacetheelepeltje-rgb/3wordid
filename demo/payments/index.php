<?php
/**
 * DEMO: a fake "Your 3WordIDs" list page (looks like 3wid_list.php) with the new
 * Subscribe button. After a (simulated) successful payment it shows the green
 * "subscription is active" banner, the gold lock on the 3WordIDs and "Manage subscription".
 * Plan sizes come from plans.json.
 * No database, no login: everything below is fake data.
 */
require __DIR__ . '/_demo.php';

$sub = $_SESSION['paydemo']['sub'];
$paid = is_array($sub);
$plans = pd_plans();

// Fake 3WordIDs of the demo account
$rows = [
    ['words' => 'blue garden lamp', 'views' => 37, 'enabled' => true],
];
$max = $paid ? (int)($plans[$sub['plan']]['ids'] ?? 1) * (int)$sub['qty'] : 1;
$count = count($rows);
$remaining = max(0, $max - $count);
$total_views = array_sum(array_column($rows, 'views'));

$welcome = $paid && isset($_GET['welcome']);
$note = (string)($_GET['note'] ?? '');

pd_head(pd_t('list.title'), 'page-list');
?>
    <main class="wrap">
        <div class="page-head">
            <div>
                <h1><?php echo pd_t('list.h1'); ?></h1>
            </div>
        </div>

        <?php if ($welcome) { ?>
        <div class="flash flash-ok" role="status">
            <span class="flash-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </span>
            <div>
                <?php echo pd_t('list.active_banner'); ?>
                <div class="flash-sub"><?php echo pd_t('list.active_detail', [
                    'plan' => html_entity_decode(pd_plan_name($sub['plan'], (int)$sub['qty']), ENT_QUOTES, 'UTF-8'),
                    'amount' => pd_money((float)$sub['total']),
                    'date' => pd_date((int)$sub['renews']),
                ]); ?></div>
            </div>
        </div>
        <?php } elseif ($note === 'withdrawn') { ?>
        <div class="flash" role="status"><?php echo pd_t('list.withdrawn_note'); ?></div>
        <?php } elseif ($paid && !empty($sub['cancel_at_end'])) { ?>
        <div class="flash" role="status"><?php echo pd_t('list.canceled_note', ['date' => pd_date((int)$sub['renews'])]); ?></div>
        <?php } elseif (!$paid) { ?>
        <div class="flash"><?php echo pd_t('list.upsell'); ?></div>
        <?php } ?>

        <section class="stats">
            <div class="stat"><span><?php echo pd_t('list.stat_active'); ?></span><strong><?php echo $count; ?></strong></div>
            <div class="stat"><span><?php echo pd_t('list.stat_remaining'); ?></span><strong><?php echo $remaining; ?></strong></div>
            <div class="stat"><span><?php echo pd_t('list.stat_searches'); ?></span><strong><?php echo $total_views; ?></strong></div>
        </section>

        <div class="toolbar">
            <?php if ($remaining > 0) { ?>
            <a class="btn btn-primary btn-create" href="#" data-demo="1"><?php echo pd_t('list.create'); ?></a>
            <?php } else { ?>
            <a class="btn btn-primary btn-create" href="#" style="pointer-events: none;opacity: 0.5" aria-disabled="true"><?php echo pd_t('list.one_only'); ?></a>
            <?php } ?>
            <a class="btn btn-ghost btn-compose" href="#" data-demo="1"><?php echo pd_t('list.compose'); ?></a>
            <a class="btn btn-ghost btn-embed" href="#" data-demo="1"><?php echo pd_t('list.embed'); ?></a>
            <?php if ($paid) { ?>
            <a class="btn btn-ghost btn-manage" href="manage.php"><?php echo pd_gold_lock(pd_t('list.gold_title')); ?> <?php echo pd_t('list.manage'); ?></a>
            <?php } else { ?>
            <!-- NEW: the Subscribe button. On the real 3wid_list.php it goes in this toolbar, after "Search box for your website". -->
            <a class="btn btn-gold btn-subscribe" href="plans.php" title="<?php echo pd_t('list.subscribe_title'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
                <?php echo pd_t('list.subscribe'); ?>
            </a>
            <?php } ?>
        </div>

        <section class="panel">
        <?php foreach ($rows as $row) {
            $dotted = str_replace(' ', '.', $row['words']);
        ?>
            <div class="id-row">
                <div>
                    <span class="id-name">
                        <?php echo pd_e($row['words']); ?>
                        <?php if ($paid) { echo pd_gold_lock(pd_t('list.gold_title')); } ?>
                    </span>
                    <div class="id-meta">
                        <?php echo pd_e($dotted); ?>
                        · <?php echo pd_t('list.views', ['n' => $row['views']]); ?>
                        <span class="badge badge-on"><?php echo pd_t('list.on'); ?></span>
                        <?php if ($paid) { ?><span class="badge badge-paid"><?php echo pd_t('list.paid_badge'); ?></span><?php } ?>
                    </div>
                </div>
                <div class="icon-row">
                    <button type="button" class="icon-btn" data-demo="1" title="<?php echo pd_t('list.edit'); ?>" aria-label="<?php echo pd_t('list.edit'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button type="button" class="icon-btn enabled" data-demo="1" title="<?php echo pd_t('list.toggle'); ?>" aria-label="<?php echo pd_t('list.toggle'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2v10"></path><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path></svg>
                    </button>
                    <button type="button" class="copy-button" data-demo="1" title="<?php echo pd_t('list.copy'); ?>" aria-label="<?php echo pd_t('list.copy'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/></svg>
                    </button>
                    <button type="button" class="icon-btn mail-icon mail-none" data-demo="1" title="<?php echo pd_t('list.mail'); ?>" aria-label="<?php echo pd_t('list.mail'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path><path d="M1 6l11 7 11-7"></path></svg>
                    </button>
                    <button type="button" class="icon-btn trash-icon" data-demo="1" title="<?php echo pd_t('list.delete'); ?>" aria-label="<?php echo pd_t('list.delete'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            </div>
        <?php } ?>
        </section>

        <div class="foot-card">
            <div class="reseller">
                <?php if ($paid) {
                    echo pd_t(empty($sub['cancel_at_end']) ? 'list.plan_paid' : 'list.plan_ending', ['plan' => html_entity_decode(pd_plan_name($sub['plan'], (int)$sub['qty']), ENT_QUOTES, 'UTF-8'), 'date' => pd_date((int)$sub['renews'])]);
                    ?> <a href="manage.php"><?php echo pd_t('list.manage'); ?></a><?php
                } else {
                    echo pd_t('list.plan_free');
                    ?> <a href="plans.php"><?php echo pd_t('list.subscribe'); ?></a><?php
                } ?>
            </div>
        </div>
        <div class="demo-toast" id="demoToast" role="status" aria-live="polite" hidden><?php echo pd_t('demo.click'); ?></div>
    </main>
    <script>
    (function () {
        var toast = document.getElementById('demoToast');
        var timer = null;
        document.querySelectorAll('[data-demo]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                toast.hidden = false;
                clearTimeout(timer);
                timer = setTimeout(function () { toast.hidden = true; }, 1800);
            });
        });
    })();
    </script>
<?php
pd_foot();
