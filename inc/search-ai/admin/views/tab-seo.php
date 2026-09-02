<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<?php if ($saved) : ?>
    <div class="notice notice-success is-dismissible"><p><?php esc_html_e('SEO settings saved.', 'ajnanda'); ?></p></div>
<?php endif; ?>
<div class="ajnanda-admin-card">
    <h2><?php esc_html_e('Existing SEO settings', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('These controls retain their existing storage and frontend behavior during the Search & AI transition.', 'ajnanda'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="ajnanda_save_seo_settings">
        <?php wp_nonce_field('ajnanda_seo_save_settings', 'ajnanda_seo_settings_nonce'); ?>
        <table class="form-table" role="presentation">
            <tr><th scope="row"><label for="seo_meta_description_default"><?php esc_html_e('Default Meta Description', 'ajnanda'); ?></label></th><td><textarea id="seo_meta_description_default" name="seo_meta_description_default" rows="3" class="large-text"><?php echo esc_textarea($values['seo_meta_description_default']); ?></textarea></td></tr>
            <tr><th scope="row"><label for="seo_default_social_image"><?php esc_html_e('Default Social Share Image', 'ajnanda'); ?></label></th><td><input type="url" id="seo_default_social_image" name="seo_default_social_image" value="<?php echo esc_url($values['seo_default_social_image']); ?>" class="regular-text"> <button type="button" class="button" id="ajnanda_seo_social_image_button"><?php esc_html_e('Choose Image', 'ajnanda'); ?></button></td></tr>
            <tr><th scope="row"><label for="seo_twitter_handle"><?php esc_html_e('Twitter/X Handle', 'ajnanda'); ?></label></th><td><input type="text" id="seo_twitter_handle" name="seo_twitter_handle" value="<?php echo esc_attr($values['seo_twitter_handle']); ?>" class="regular-text"></td></tr>
            <tr><th scope="row"><label for="seo_business_phone"><?php esc_html_e('Business Phone', 'ajnanda'); ?></label></th><td><input type="text" id="seo_business_phone" name="seo_business_phone" value="<?php echo esc_attr($values['seo_business_phone']); ?>" class="regular-text"></td></tr>
            <tr><th scope="row"><label for="seo_business_address"><?php esc_html_e('Business Address', 'ajnanda'); ?></label></th><td><input type="text" id="seo_business_address" name="seo_business_address" value="<?php echo esc_attr($values['seo_business_address']); ?>" class="regular-text"></td></tr>
            <tr><th scope="row"><?php esc_html_e('Existing output', 'ajnanda'); ?></th><td><label><input type="checkbox" name="seo_schema_enabled" value="1" <?php checked($values['seo_schema_enabled']); ?>> <?php esc_html_e('Enable Schema Markup', 'ajnanda'); ?></label><br><label><input type="checkbox" name="seo_llms_txt_enabled" value="1" <?php checked($values['seo_llms_txt_enabled']); ?>> <?php esc_html_e('Publish /llms.txt', 'ajnanda'); ?></label><p class="description"><?php esc_html_e('AI crawler access is managed separately on the AI Discovery tab.', 'ajnanda'); ?></p></td></tr>
        </table>
        <?php submit_button(__('Save SEO Settings', 'ajnanda')); ?>
    </form>
</div>
<script>
(function () {
    var button = document.getElementById('ajnanda_seo_social_image_button');
    var input = document.getElementById('seo_default_social_image');
    if (!button || !input || typeof wp === 'undefined' || !wp.media) { return; }
    button.addEventListener('click', function (event) {
        event.preventDefault();
        var frame = wp.media({ title: <?php echo wp_json_encode(__('Choose Default Social Share Image', 'ajnanda')); ?>, multiple: false });
        frame.on('select', function () { input.value = frame.state().get('selection').first().toJSON().url; });
        frame.open();
    });
})();
</script>
