<?php
if (! defined('ABSPATH')) { exit; }
$effect_labels = array(
    'noindex' => __('Request search engines not to index excluded content', 'ajnanda'),
    'automated_crawlers' => __('Block supported automated crawlers in robots.txt', 'ajnanda'),
    'traditional_search' => __('Do not advertise to traditional search', 'ajnanda'),
    'ai_search' => __('Do not advertise to AI Search/retrieval systems', 'ajnanda'),
    'ai_training' => __('Do not allow supported AI training crawlers', 'ajnanda'),
    'user_retrieval' => __('Request exclusion from user-initiated AI retrieval where controllable', 'ajnanda'),
    'sitemap' => __('Exclude from XML sitemaps', 'ajnanda'),
    'llms_txt' => __('Exclude from llms.txt', 'ajnanda'),
    'schema_relationships' => __('Exclude from schema relationships', 'ajnanda'),
);
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ajnanda_save_search_ai_policy">
    <?php wp_nonce_field('ajnanda_save_search_ai_policy'); ?>
    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('Content exclusions', 'ajnanda'); ?></h2>
        <p><?php esc_html_e('Normal published website content is allowed unless it matches an exclusion below. These settings are discovery controls—not security. Protect private content with authentication and application permissions.', 'ajnanda'); ?></p>

        <h3><?php esc_html_e('Specific posts and pages', 'ajnanda'); ?></h3>
        <select name="search_ai_excluded_post_ids[]" multiple size="10" class="ajnanda-search-ai-multiselect" aria-label="<?php esc_attr_e('Excluded posts and pages', 'ajnanda'); ?>">
            <?php foreach ($selectable_content as $item) : ?><option value="<?php echo esc_attr($item->ID); ?>" <?php selected(in_array((int) $item->ID, $policy['excluded_post_ids'], true)); ?>><?php echo esc_html(get_post_type_object($item->post_type)->labels->singular_name . ': ' . get_the_title($item)); ?></option><?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Use Ctrl/Command-click to select or remove multiple items. Up to 250 published items are shown.', 'ajnanda'); ?></p>

        <h3><?php esc_html_e('Post types', 'ajnanda'); ?></h3>
        <div class="ajnanda-search-ai-check-grid"><?php foreach ($public_post_types as $post_type => $object) : ?><label><input type="checkbox" name="search_ai_excluded_post_types[]" value="<?php echo esc_attr($post_type); ?>" <?php checked(in_array($post_type, $policy['excluded_post_types'], true)); ?>> <?php echo esc_html($object->labels->name); ?></label><?php endforeach; ?></div>

        <h3><label for="search_ai_excluded_paths"><?php esc_html_e('URL/path patterns', 'ajnanda'); ?></label></h3>
        <textarea class="large-text code" rows="7" id="search_ai_excluded_paths" name="search_ai_excluded_paths" placeholder="/client-portal/&#10;/account/&#10;/billing/"><?php echo esc_textarea(implode("\n", $policy['excluded_paths'])); ?></textarea>
        <p class="description"><?php esc_html_e('One site path per line. A path matches itself and its children. Use * as a wildcard when needed. Query strings are not considered.', 'ajnanda'); ?></p>
    </div>

    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('What an exclusion means', 'ajnanda'); ?></h2>
        <p><?php esc_html_e('Define the exclusion once, then choose its independent effects. Phase 3 will connect these decisions to each discovery output.', 'ajnanda'); ?></p>
        <div class="ajnanda-search-ai-toggle-list"><?php foreach ($effect_labels as $key => $label) : ?><label><input type="checkbox" name="search_ai_exclusion_effects[<?php echo esc_attr($key); ?>]" value="1" <?php checked(! empty($policy['effects'][$key])); ?>> <span><strong><?php echo esc_html($label); ?></strong><?php if ('automated_crawlers' === $key) : ?><small><?php esc_html_e('Advanced: usually leave this off when noindex is enabled, because a blocked crawler cannot read the noindex directive.', 'ajnanda'); ?></small><?php endif; ?></span></label><?php endforeach; ?></div>
        <?php submit_button(__('Save Content Access Policy', 'ajnanda')); ?>
    </div>
</form>
