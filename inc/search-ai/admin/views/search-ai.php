<?php
/** @var array $tabs */
/** @var string $tab */
/** @var array $profile */
/** @var array $ownership */
if (! defined('ABSPATH')) {
    exit;
}
$base_url = admin_url('admin.php?page=' . AJNanda_Search_AI_Admin::PAGE_SLUG);
?>
<div class="wrap ajnanda-admin-wrap ajnanda-search-ai-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Search & AI', 'ajnanda'); ?></h1>
        <p><?php esc_html_e('Control how search engines and AI systems discover, understand, and access your public website.', 'ajnanda'); ?></p>
    </div>

    <nav class="nav-tab-wrapper ajnanda-search-ai-tabs" aria-label="<?php esc_attr_e('Search & AI sections', 'ajnanda'); ?>">
        <?php foreach ($tabs as $slug => $label) : ?>
            <a class="nav-tab <?php echo $slug === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', $slug, $base_url)); ?>" <?php echo $slug === $tab ? 'aria-current="page"' : ''; ?>><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="ajnanda-search-ai-content">
        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Search & AI settings saved.', 'ajnanda'); ?></p></div>
        <?php endif; ?>
        <?php if ('site-profile' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-site-profile.php'; ?>
        <?php elseif ('content-access' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-content-access.php'; ?>
        <?php elseif ('ai-discovery' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-ai-discovery.php'; ?>
        <?php elseif ('discovery-files' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-discovery-files.php'; ?>
        <?php elseif ('seo' === $tab) : ?>
            <?php
            $values = array(
                'seo_meta_description_default' => get_theme_mod('seo_meta_description_default', ''),
                'seo_default_social_image'     => get_theme_mod('seo_default_social_image', ''),
                'seo_twitter_handle'           => get_theme_mod('seo_twitter_handle', ''),
                'seo_schema_enabled'           => get_theme_mod('seo_schema_enabled', true),
                'seo_allow_ai_crawlers'        => get_theme_mod('seo_allow_ai_crawlers', true),
                'seo_llms_txt_enabled'         => get_theme_mod('seo_llms_txt_enabled', true),
            );
            $saved = isset($_GET['ajnanda_seo_saved']);
            include get_template_directory() . '/inc/search-ai/admin/views/tab-seo.php';
            ?>
        <?php elseif ('insights' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-insights.php'; ?>
        <?php elseif ('overview' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-overview.php'; ?>
        <?php elseif ('crawler-log' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-crawler-log.php'; ?>
        <?php elseif ('suspicious-bots' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-suspicious-bots.php'; ?>
        <?php elseif ('settings' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-settings.php'; ?>
        <?php elseif ('roadmap' === $tab) : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-roadmap.php'; ?>
        <?php else : ?>
            <?php include get_template_directory() . '/inc/search-ai/admin/views/tab-foundation.php'; ?>
        <?php endif; ?>
    </div>
</div>
