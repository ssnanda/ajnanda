<?php
if (! defined('ABSPATH')) { exit; }
$categories = AJNanda_Search_AI_Crawler_Registry::categories();
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ajnanda_save_ai_discovery">
    <?php wp_nonce_field('ajnanda_save_ai_discovery'); ?>
    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('AI discovery policy', 'ajnanda'); ?></h2>
        <p><?php esc_html_e('Choose how AI systems may use normal public content. Content Access exclusions can narrow these site-wide choices.', 'ajnanda'); ?></p>
        <div class="ajnanda-search-ai-toggle-list">
            <label><input type="checkbox" name="search_ai_allow_ai_search" value="1" <?php checked(AJNanda_Search_AI_Settings::get('search_ai_allow_ai_search')); ?>> <span><strong><?php esc_html_e('Allow public content in AI Search and retrieval', 'ajnanda'); ?></strong><small><?php esc_html_e('Allows supported search crawlers that help services cite and link to this website.', 'ajnanda'); ?></small></span></label>
            <label><input type="checkbox" name="search_ai_allow_ai_training" value="1" <?php checked(AJNanda_Search_AI_Settings::get('search_ai_allow_ai_training')); ?>> <span><strong><?php esc_html_e('Allow AI companies to use public content for model development', 'ajnanda'); ?></strong><small><?php esc_html_e('Controls supported training/model-development crawler tokens separately from AI Search.', 'ajnanda'); ?></small></span></label>
            <label><input type="checkbox" name="search_ai_allow_user_retrieval" value="1" <?php checked(AJNanda_Search_AI_Settings::get('search_ai_allow_user_retrieval')); ?>> <span><strong><?php esc_html_e('Allow user-initiated AI retrieval where controllable', 'ajnanda'); ?></strong><small><?php esc_html_e('Some agents fetch a page because a user requested it. Some providers state that robots.txt may not apply, so this preference cannot guarantee blocking.', 'ajnanda'); ?></small></span></label>
        </div>
        <?php submit_button(__('Save AI Discovery', 'ajnanda')); ?>
    </div>
</form>

<div class="ajnanda-admin-section">
    <h2><?php esc_html_e('Crawler and provider registry', 'ajnanda'); ?></h2>
    <p class="description"><?php esc_html_e('AJNanda derives crawler policy from this extensible registry. Provider names and behavior can change over time.', 'ajnanda'); ?></p>
    <table class="widefat striped ajnanda-search-ai-registry">
        <thead><tr><th><?php esc_html_e('Provider', 'ajnanda'); ?></th><th><?php esc_html_e('Crawler/token', 'ajnanda'); ?></th><th><?php esc_html_e('Category', 'ajnanda'); ?></th><th><?php esc_html_e('robots.txt control', 'ajnanda'); ?></th><th><?php esc_html_e('Current policy', 'ajnanda'); ?></th></tr></thead>
        <tbody><?php foreach ($crawler_registry as $crawler) : ?><tr><td><?php echo esc_html($crawler['provider']); ?></td><td><code><?php echo esc_html($crawler['label']); ?></code><?php if (! empty($crawler['control_only'])) : ?><br><small><?php esc_html_e('Policy token; not a distinct request User-Agent.', 'ajnanda'); ?></small><?php endif; ?></td><td><?php echo esc_html($categories[$crawler['category']] ?? $crawler['category']); ?></td><td><?php echo ! empty($crawler['robots_control']) ? esc_html__('Supported', 'ajnanda') : esc_html__('Not reliably controllable', 'ajnanda'); ?></td><td><?php echo AJNanda_Search_AI_Crawler_Registry::category_allowed($crawler['category']) ? esc_html__('Allow', 'ajnanda') : esc_html__('Restrict', 'ajnanda'); ?></td></tr><?php endforeach; ?></tbody>
    </table>
</div>

