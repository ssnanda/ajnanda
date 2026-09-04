<?php if (! defined('ABSPATH')) { exit; } ?>
<div class="ajnanda-admin-section">
    <h2><?php esc_html_e('Search and performance evidence', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('Opportunities below come from connected provider data. They are observations, not promises of rankings, indexing, or AI citation.', 'ajnanda'); ?></p>
    <?php foreach ($insights['providers'] as $provider) : ?><p><span class="ajnanda-admin-pill <?php echo 'available' === $provider['state'] ? 'is-success' : 'is-warning'; ?>"><?php echo esc_html($provider['label']); ?></span> <?php echo esc_html($provider['message']); ?><?php if (! empty($provider['cache_state'])) : ?> <small><?php printf(esc_html__('Snapshot: %s.', 'ajnanda'), esc_html($provider['cache_state'])); ?></small><?php endif; ?><?php if (! empty($provider['refreshed_at_utc'])) : ?> <small><?php printf(esc_html__('Refreshed %s UTC.', 'ajnanda'), esc_html($provider['refreshed_at_utc'])); ?></small><?php endif; ?></p><?php endforeach; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="ajnanda_refresh_search_ai_insights"><?php wp_nonce_field('ajnanda_refresh_search_ai_insights'); ?><?php submit_button(__('Refresh evidence now', 'ajnanda'), 'secondary', 'submit', false); ?></form>
</div>

<div class="ajnanda-admin-grid">
    <section class="ajnanda-admin-card">
        <h2><?php esc_html_e('Observed opportunities', 'ajnanda'); ?></h2>
        <?php if (empty($insights['opportunities'])) : ?><p><?php esc_html_e('No specific data-backed opportunities are currently available.', 'ajnanda'); ?></p><?php else : ?><ul class="ajnanda-insight-list"><?php foreach ($insights['opportunities'] as $item) : ?><li><span class="ajnanda-admin-pill"><?php echo esc_html($item['label']); ?></span><p><?php echo esc_html($item['observation'] ?? $item['message']); ?></p><?php if (! empty($item['interpretation'])) : ?><p><strong><?php esc_html_e('Interpretation:', 'ajnanda'); ?></strong> <?php echo esc_html($item['interpretation']); ?></p><?php endif; ?><p><strong><?php esc_html_e('Suggested action:', 'ajnanda'); ?></strong> <?php echo esc_html($item['suggested_action'] ?? ''); ?></p><p><small><?php printf(esc_html__('Confidence: %1$s. Caveat: %2$s', 'ajnanda'), esc_html($item['confidence'] ?? 'low'), esc_html($item['caveats'] ?? '')); ?></small></p><?php if ($item['url']) : ?><a href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('View page', 'ajnanda'); ?> &rarr;</a><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
    </section>
    <section class="ajnanda-admin-card">
        <h2><?php esc_html_e('Configuration follow-up', 'ajnanda'); ?></h2>
        <?php if (empty($insights['configuration_issues'])) : ?><p><?php esc_html_e('No readiness issues need follow-up.', 'ajnanda'); ?></p><?php else : ?><p><?php printf(esc_html(_n('%d readiness issue may affect the evidence available here.', '%d readiness issues may affect the evidence available here.', count($insights['configuration_issues']), 'ajnanda')), count($insights['configuration_issues'])); ?></p><a class="button" href="<?php echo esc_url(add_query_arg(array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'overview'), admin_url('admin.php'))); ?>"><?php esc_html_e('Review readiness', 'ajnanda'); ?></a><?php endif; ?>
    </section>
</div>
