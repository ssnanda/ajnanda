<?php if (! defined('ABSPATH')) { exit; } ?>
<div class="ajnanda-admin-section">
    <h2><?php esc_html_e('Search and performance evidence', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('Opportunities below come from connected provider data. They are observations, not promises of rankings, indexing, or AI citation.', 'ajnanda'); ?></p>
    <?php foreach ($insights['providers'] as $provider) : ?><p><span class="ajnanda-admin-pill <?php echo 'available' === $provider['state'] ? 'is-success' : 'is-warning'; ?>"><?php echo esc_html($provider['label']); ?></span> <?php echo esc_html($provider['message']); ?></p><?php endforeach; ?>
</div>

<div class="ajnanda-admin-grid">
    <section class="ajnanda-admin-card">
        <h2><?php esc_html_e('Observed opportunities', 'ajnanda'); ?></h2>
        <?php if (empty($insights['opportunities'])) : ?><p><?php esc_html_e('No specific data-backed opportunities are currently available.', 'ajnanda'); ?></p><?php else : ?><ul class="ajnanda-insight-list"><?php foreach ($insights['opportunities'] as $item) : ?><li><span class="ajnanda-admin-pill <?php echo 'warning' === $item['state'] ? 'is-warning' : ''; ?>"><?php echo esc_html($item['label']); ?></span><p><?php echo esc_html($item['message']); ?></p><?php if ($item['url']) : ?><a href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('View page', 'ajnanda'); ?> &rarr;</a><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
    </section>
    <section class="ajnanda-admin-card">
        <h2><?php esc_html_e('Configuration follow-up', 'ajnanda'); ?></h2>
        <?php if (empty($insights['configuration_issues'])) : ?><p><?php esc_html_e('No readiness issues need follow-up.', 'ajnanda'); ?></p><?php else : ?><p><?php printf(esc_html(_n('%d readiness issue may affect the evidence available here.', '%d readiness issues may affect the evidence available here.', count($insights['configuration_issues']), 'ajnanda')), count($insights['configuration_issues'])); ?></p><a class="button" href="<?php echo esc_url(add_query_arg(array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'overview'), admin_url('admin.php'))); ?>"><?php esc_html_e('Review readiness', 'ajnanda'); ?></a><?php endif; ?>
    </section>
</div>
