<?php
if (! defined('ABSPATH')) {
    exit;
}
$external_plugins = AJNanda_Search_AI_Capability_Ownership::detected_plugins();
?>
<div class="ajnanda-admin-grid">
    <div class="ajnanda-admin-card">
        <h2><?php echo esc_html($tabs[$tab]); ?></h2>
        <span class="ajnanda-admin-pill"><?php esc_html_e('Foundation ready', 'ajnanda'); ?></span>
        <p class="ajnanda-search-ai-card-copy"><?php esc_html_e('The shared service layer for this section is installed. Its workflow and output will be activated in the appropriate implementation phase.', 'ajnanda'); ?></p>
    </div>

    <?php if ('overview' === $tab) : ?>
        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Canonical site identity', 'ajnanda'); ?></h2>
            <p><strong><?php echo esc_html($profile['name'] ?: __('Not set', 'ajnanda')); ?></strong></p>
            <p><?php echo esc_html($profile['description'] ?: __('Add a site description in WordPress Site Identity.', 'ajnanda')); ?></p>
        </div>
        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('SEO capability ownership', 'ajnanda'); ?></h2>
            <?php if (empty($external_plugins)) : ?>
                <span class="ajnanda-admin-pill is-success"><?php esc_html_e('AJNanda native', 'ajnanda'); ?></span>
                <p class="ajnanda-search-ai-card-copy"><?php esc_html_e('No recognized SEO plugin ownership was detected.', 'ajnanda'); ?></p>
            <?php else : ?>
                <span class="ajnanda-admin-pill is-warning"><?php esc_html_e('Integration review needed', 'ajnanda'); ?></span>
                <p class="ajnanda-search-ai-card-copy"><?php echo esc_html(implode(', ', wp_list_pluck($external_plugins, 'label'))); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

