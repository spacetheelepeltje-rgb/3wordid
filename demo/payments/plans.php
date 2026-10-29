<?php
/**
 * DEMO: plans page. Plans, prices and features come from plans.json (EUR, incl. VAT).
 * Prices are placeholders while "prices_are_placeholders" is true in plans.json.
 * ?billing=year|month picks the period. A plan without a price for that period shows its
 * other period with a "Yearly only" / "Monthly only" tag.
 */
require __DIR__ . '/_demo.php';

$plans = pd_plans();
$placeholder = pd_plans_data()['placeholder'];
$billing = (($_GET['billing'] ?? 'year') === 'month') ? 'month' : 'year';
$note = (string)($_GET['note'] ?? '');
$sub = $_SESSION['paydemo']['sub'];
$any_month = (bool)array_filter($plans, function ($p) { return isset($p['prices']['month']); });
$any_year = (bool)array_filter($plans, function ($p) { return isset($p['prices']['year']); });

pd_head(pd_t('plans.title'), 'page-plans');
?>
    <main class="wrap">
        <p class="back-link"><a href="index.php">← <?php echo pd_t('plans.back'); ?></a></p>
        <div class="page-head">
            <div>
                <h1><?php echo pd_t('plans.h1'); ?></h1>
                <p class="lede"><?php echo pd_t('plans.lede'); ?></p>
            </div>
        </div>

        <?php if ($note === 'canceled') { ?>
        <div class="flash" role="status"><?php echo pd_t('plans.canceled'); ?></div>
        <?php } ?>

        <?php if ($any_month && $any_year) { ?>
        <div class="plan-controls">
            <div class="seg" role="group" aria-label="<?php echo pd_t('plans.billing'); ?>">
                <a href="<?php echo pd_e(pd_url('plans.php', ['billing' => 'year'])); ?>"<?php echo $billing === 'year' ? ' aria-current="true"' : ''; ?>><?php echo pd_t('plans.yearly'); ?></a>
                <a href="<?php echo pd_e(pd_url('plans.php', ['billing' => 'month'])); ?>"<?php echo $billing === 'month' ? ' aria-current="true"' : ''; ?>><?php echo pd_t('plans.monthly'); ?></a>
            </div>
        </div>
        <?php } ?>

        <p class="tax-note"><?php echo pd_t('plans.vat'); ?></p>

        <section class="plan-grid">
        <?php foreach ($plans as $key => $p) {
            $period = isset($p['prices'][$billing]) ? $billing : array_key_first($p['prices']);
            $unit = $p['prices'][$period];
            $is_current = is_array($sub) && $sub['plan'] === $key;
            $desc = pd_ptext($p['description']);
        ?>
            <article class="plan-card<?php echo $p['highlight'] ? ' plan-featured' : ''; ?>">
                <div class="plan-top">
                    <h2><?php echo pd_e(pd_ptext($p['name'])); ?></h2>
                    <?php if ($is_current) { ?><span class="badge badge-paid"><?php echo pd_t('plans.current'); ?></span><?php } ?>
                </div>
                <?php if ($desc !== '') { ?><p class="plan-desc"><?php echo pd_e($desc); ?></p><?php } ?>
                <div class="plan-price">
                    <strong><?php echo pd_e(pd_money((float)$unit)); ?></strong>
                    <span><?php echo $period === 'month' ? pd_t('plans.per_month') : pd_t('plans.per_year'); ?><?php echo $p['qty_max'] > 1 && $p['ids'] === 1 ? ' · ' . pd_t('plans.per_id') : ''; ?></span>
                </div>
                <div class="plan-tags">
                    <?php if ($placeholder) { ?><span class="tag tag-placeholder"><?php echo pd_t('plans.placeholder'); ?></span><?php } ?>
                    <?php if ($period !== $billing) { ?><span class="tag"><?php echo $period === 'year' ? pd_t('plans.yearly_only') : pd_t('plans.monthly_only'); ?></span><?php } ?>
                </div>
                <ul class="plan-features">
                    <?php foreach (pd_plan_features($p) as $f) { ?>
                    <li><?php echo pd_e($f); ?></li>
                    <?php } ?>
                </ul>
                <form class="plan-form" method="get" action="checkout.php">
                    <input type="hidden" name="plan" value="<?php echo pd_e($key); ?>">
                    <input type="hidden" name="billing" value="<?php echo pd_e($period); ?>">
                    <?php if ($p['qty_max'] > $p['qty_min']) { ?>
                    <label class="qty">
                        <span><?php echo pd_t('plans.qty'); ?></span>
                        <select name="qty">
                            <?php for ($i = $p['qty_min']; $i <= $p['qty_max']; $i++) { ?>
                            <option value="<?php echo $i; ?>"><?php echo $i * $p['ids']; ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <?php } else { ?>
                    <input type="hidden" name="qty" value="<?php echo (int)$p['qty_min']; ?>">
                    <?php } ?>
                    <button type="submit" class="btn <?php echo $p['highlight'] ? 'btn-gold' : 'btn-primary'; ?> plan-choose" data-plan="<?php echo pd_e($key); ?>"><?php echo pd_t('plans.choose'); ?></button>
                </form>
            </article>
        <?php } ?>
        </section>

        <?php if ($placeholder) { ?>
        <div class="small-notes"><p><?php echo pd_t('plans.placeholder_note'); ?></p></div>
        <?php } ?>
    </main>
<?php
pd_foot();
