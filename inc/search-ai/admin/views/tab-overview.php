<?php
if (! defined('ABSPATH')) { exit; }
$state_labels = array('pass' => __('Pass', 'ajnanda'), 'warning' => __('Warning', 'ajnanda'), 'fail' => __('Fail', 'ajnanda'), 'not_applicable' => __('Not applicable', 'ajnanda'), 'externally_unverifiable' => __('Externally unverifiable', 'ajnanda'));
$tab_url = static function ($tab) { return add_query_arg(array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => $tab), admin_url('admin.php')); };
$action_url = static function ($check) use ($tab_url) {
    $wordpress_settings = array('search_visibility' => 'options-reading.php', 'https' => 'options-general.php', 'canonical_url' => 'options-general.php', 'permalinks' => 'options-permalink.php');
    return isset($wordpress_settings[$check['id']]) ? admin_url($wordpress_settings[$check['id']]) : $tab_url($check['tab']);
};
$score = $readiness['score']['value'];
?>
<div class="ajnanda-readiness-summary ajnanda-admin-section">
    <div class="ajnanda-readiness-score" aria-label="<?php echo esc_attr(sprintf(__('Readiness score: %d out of 100', 'ajnanda'), $score)); ?>"><strong><?php echo esc_html($score); ?></strong><span>/100</span></div>
    <div><p class="ajnanda-admin-eyebrow"><?php esc_html_e('Technical readiness', 'ajnanda'); ?></p><h2><?php echo $readiness['issues'] ? esc_html__('Review the actions below', 'ajnanda') : esc_html__('Search & AI foundations look healthy', 'ajnanda'); ?></h2><p><?php esc_html_e('Every scored point comes from the visible technical checks below. Policy choices and externally unverifiable conditions do not reduce the score.', 'ajnanda'); ?></p></div>
</div>

<div class="ajnanda-readiness-cards">
    <?php foreach (array('search', 'ai', 'entity', 'outputs', 'content') as $key) : $category = $readiness['categories'][$key]; ?>
        <a class="ajnanda-readiness-card" href="<?php echo esc_url($tab_url(array('search' => 'seo', 'ai' => 'ai-discovery', 'entity' => 'site-profile', 'outputs' => 'discovery-files', 'content' => 'content-access')[$key])); ?>"><span><?php echo esc_html($category['label']); ?></span><strong class="ajnanda-state-<?php echo esc_attr($category['state']); ?>"><?php echo esc_html($state_labels[$category['state']]); ?></strong></a>
    <?php endforeach; ?>
</div>

<div class="ajnanda-admin-grid ajnanda-overview-main">
    <section class="ajnanda-admin-card">
        <h2><?php esc_html_e('Issues and recommended actions', 'ajnanda'); ?></h2>
        <?php if (! $readiness['issues']) : ?><p><?php esc_html_e('No scored configuration issues need attention.', 'ajnanda'); ?></p><?php else : ?>
            <ol class="ajnanda-readiness-actions"><?php foreach (array_slice($readiness['issues'], 0, 6) as $check) : ?><li><span class="ajnanda-admin-pill is-<?php echo esc_attr($check['state']); ?>"><?php echo esc_html($state_labels[$check['state']]); ?></span><strong><?php echo esc_html($check['label']); ?></strong><p><?php echo esc_html($check['message']); ?></p><a href="<?php echo esc_url($action_url($check)); ?>"><?php esc_html_e('Review setting', 'ajnanda'); ?> &rarr;</a></li><?php endforeach; ?></ol>
        <?php endif; ?>
    </section>
    <section class="ajnanda-admin-card">
        <h2><?php esc_html_e('Capability ownership', 'ajnanda'); ?></h2>
        <dl class="ajnanda-ownership-list"><?php foreach ($readiness['categories']['ownership']['checks'] as $check) : ?><div><dt><?php echo esc_html($check['label']); ?></dt><dd><?php echo esc_html($check['message']); ?></dd></div><?php endforeach; ?></dl>
    </section>
</div>

<?php $stale_findings = isset($stale_references['findings']) ? $stale_references['findings'] : array(); $stale_count = isset($stale_references['count']) ? (int) $stale_references['count'] : 0; ?>
<section class="ajnanda-admin-card ajnanda-admin-section ajnanda-stale-references">
    <h2><?php esc_html_e('Stale AI references', 'ajnanda'); ?></h2>
    <p>
        <span class="ajnanda-readiness-score ajnanda-stale-count" aria-label="<?php echo esc_attr(sprintf(_n('%d stale AI reference', '%d stale AI references', $stale_count, 'ajnanda'), $stale_count)); ?>"><strong><?php echo esc_html($stale_count); ?></strong></span>
        <?php esc_html_e('URLs promoted through Search & AI (llms.txt, Important Pages, custom schema, AJNanda discovery outputs) that are no longer appropriate discovery targets. A healthy site shows 0.', 'ajnanda'); ?>
    </p>
    <?php if (! $stale_count) : ?>
        <p><span class="ajnanda-admin-pill is-pass"><?php esc_html_e('Pass', 'ajnanda'); ?></span> <?php esc_html_e('Every promoted URL is published, indexable, canonical, and allowed by Content Access.', 'ajnanda'); ?></p>
    <?php else : ?>
        <table class="widefat striped ajnanda-stale-references-table">
            <thead><tr><th><?php esc_html_e('Reference', 'ajnanda'); ?></th><th><?php esc_html_e('Source', 'ajnanda'); ?></th><th><?php esc_html_e('Why it is stale', 'ajnanda'); ?></th><th><?php esc_html_e('Fix', 'ajnanda'); ?></th></tr></thead>
            <tbody>
            <?php foreach ($stale_findings as $finding) : ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($finding['label']); ?></strong>
                        <?php if (! empty($finding['url'])) : ?><br><small><?php echo esc_html($finding['url']); ?></small><?php endif; ?>
                    </td>
                    <td><?php echo esc_html($finding['source_label']); ?></td>
                    <td><?php echo esc_html($finding['reason']); ?></td>
                    <td><a href="<?php echo esc_url($tab_url($finding['tab'])); ?>"><?php esc_html_e('Review', 'ajnanda'); ?> &rarr;</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description"><?php esc_html_e('These URLs are already withheld from public AI discovery output. Update or remove the stored selection to clear the warning. Intentional external authoritative links are not listed here.', 'ajnanda'); ?></p>
    <?php endif; ?>
</section>

<details class="ajnanda-admin-section ajnanda-search-ai-advanced">
    <summary><strong><?php esc_html_e('All readiness checks', 'ajnanda'); ?></strong> <span><?php esc_html_e('See exactly how the result was calculated.', 'ajnanda'); ?></span></summary>
    <div class="ajnanda-search-ai-details-body"><?php foreach ($readiness['categories'] as $key => $category) : if ('ownership' === $key) { continue; } ?><h3><?php echo esc_html($category['label']); ?></h3><ul class="ajnanda-readiness-checks"><?php foreach ($category['checks'] as $check) : ?><li><span class="ajnanda-admin-pill is-<?php echo esc_attr($check['state']); ?>"><?php echo esc_html($state_labels[$check['state']]); ?></span><span><strong><?php echo esc_html($check['label']); ?></strong><small><?php echo esc_html($check['message']); ?><?php if ($check['weight'] && ! in_array($check['state'], array('not_applicable', 'externally_unverifiable'), true)) : ?> <?php printf(esc_html__('Weight: %d.', 'ajnanda'), (int) $check['weight']); ?><?php else : ?> <?php esc_html_e('Not scored.', 'ajnanda'); ?><?php endif; ?></small></span></li><?php endforeach; ?></ul><?php endforeach; ?></div>
</details>
