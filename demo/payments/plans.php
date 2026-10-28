<?php
/**
 * DEMO: plans page. Prices are based on the live plans.php (yearly, in dollars).
 * Euro prices and monthly prices are placeholders and marked as such on the page.
 * ?region=eu|us picks currency and VAT wording, ?billing=yearly|monthly the period.
 */
require __DIR__ . '/_demo.php';

$region = pd_region();
$currency = pd_currency($region);
$billing = (($_GET['billing'] ?? 'yearly') === 'monthly') ? 'monthly' : 'yearly';
$note = (string)($_GET['note'] ?? '');
$sub = $_SESSION['paydemo']['sub'];

$features = [
    'single' => ['ids_single', 'numbers', 'gold', 'all'],
    'group10' => ['ids', 'numbers', 'gold', 'all'],
    'group100' => ['ids', 'numbers', 'gold', 'all'],
    'museum20' => ['ids', 'numbers', 'gold', 'all', 'proof', 'multiples'],
];

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

        <div class="plan-controls">
            <div class="seg" role="group" aria-label="<?php echo pd_t('plans.billing'); ?>">
                <a href="<?php echo pd_e(pd_url('plans.php', ['region' => $region, 'billing' => 'yearly'])); ?>"<?php echo $billing === 'yearly' ? ' aria-current="true"' : ''; ?>><?php echo pd_t('plans.yearly'); ?></a>
                <a href="<?php echo pd_e(pd_url('plans.php', ['region' => $region, 'billing' => 'monthly'])); ?>"<?php echo $billing === 'monthly' ? ' aria-current="true"' : ''; ?>><?php echo pd_t('plans.monthly'); ?></a>
            </div>
            <div class="seg" role="group" aria-label="<?php echo pd_t('plans.region'); ?>">
                <a href="<?php echo pd_e(pd_url('plans.php', ['region' => 'eu', 'billing' => $billing])); ?>"<?php echo $region === 'eu' ? ' aria-current="true"' : ''; ?>><?php echo pd_t('plans.region_eu'); ?></a>
                <a href="<?php echo pd_e(pd_url('plans.php', ['region' => 'us', 'billing' => $billing])); ?>"<?php echo $region === 'us' ? ' aria-current="true"' : ''; ?>><?php echo pd_t('plans.region_us'); ?></a>
            </div>
        </div>

        <p class="tax-note"><?php echo $region === 'us' ? pd_t('plans.vat_us') : pd_t('plans.vat_eu'); ?></p>

        <section class="plan-grid">
        <?php foreach (pd_plans() as $key => $p) {
            $period = ($billing === 'monthly' && $p['monthly'] !== null) ? 'monthly' : 'yearly';
            $unit = $period === 'monthly' ? $p['monthly'] : $p['yearly'];
            $placeholder = $period === 'monthly' || $currency === 'EUR';
            $is_current = is_array($sub) && $sub['plan'] === $key;
        ?>
            <article class="plan-card<?php echo $key === 'group10' ? ' plan-featured' : ''; ?>">
                <div class="plan-top">
                    <h2><?php echo pd_t('plan.' . $key . '.name'); ?></h2>
                    <?php if ($is_current) { ?><span class="badge badge-paid"><?php echo pd_t('plans.current'); ?></span><?php } ?>
                </div>
                <p class="plan-desc"><?php echo pd_t('plan.' . $key . '.desc'); ?></p>
                <div class="plan-price">
                    <strong><?php echo pd_e(pd_money((float)$unit, $currency)); ?></strong>
                    <span><?php echo $period === 'monthly' ? pd_t('plans.per_month') : pd_t('plans.per_year'); ?><?php echo $key === 'single' ? ' · 3WordID' : ''; ?></span>
                </div>
                <div class="plan-tags">
                    <?php if ($placeholder) { ?><span class="tag tag-placeholder"><?php echo pd_t('plans.placeholder'); ?></span><?php } ?>
                    <?php if ($billing === 'monthly' && $p['monthly'] === null) { ?><span class="tag"><?php echo pd_t('plans.yearly_only'); ?></span><?php } ?>
                </div>
                <ul class="plan-features">
                    <?php foreach ($features[$key] as $f) { ?>
                    <li><?php
                        if ($f === 'ids') {
                            echo pd_t('feat.ids', ['n' => $p['ids']]);
                        } elseif ($f === 'ids_single') {
                            echo pd_t('feat.ids_single', ['price' => pd_money((float)$unit, $currency)]);
                        } else {
                            echo pd_t('feat.' . $f);
                        }
                    ?></li>
                    <?php } ?>
                </ul>
                <form class="plan-form" method="get" action="checkout.php">
                    <input type="hidden" name="plan" value="<?php echo pd_e($key); ?>">
                    <input type="hidden" name="billing" value="<?php echo pd_e($period); ?>">
                    <input type="hidden" name="region" value="<?php echo pd_e($region); ?>">
                    <?php if ($p['qty_max'] > 1) { ?>
                    <label class="qty">
                        <span><?php echo pd_t('plans.qty'); ?></span>
                        <select name="qty">
                            <?php for ($i = $p['qty_min']; $i <= $p['qty_max']; $i++) { ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <?php } else { ?>
                    <input type="hidden" name="qty" value="1">
                    <?php } ?>
                    <button type="submit" class="btn <?php echo $key === 'group10' ? 'btn-gold' : 'btn-primary'; ?> plan-choose" data-plan="<?php echo pd_e($key); ?>"><?php echo pd_t('plans.choose'); ?></button>
                </form>
            </article>
        <?php } ?>
        </section>

        <div class="small-notes">
            <?php if ($currency === 'EUR') { ?><p><?php echo pd_t('plans.eur_placeholder'); ?></p><?php } ?>
            <?php if ($billing === 'monthly') { ?><p><?php echo pd_t('plans.monthly_placeholder'); ?></p><?php } ?>
        </div>
    </main>
<?php
pd_foot();
