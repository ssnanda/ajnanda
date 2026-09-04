<?php
/**
 * AJNanda admin: SEO Settings screen.
 *
 * Moved here from the Customizer (see ajnanda_seo_register_admin_pages() in inc/seo.php) — these
 * are plain settings with no live-preview need, so a regular admin page loads faster and is easier
 * to find than a Customizer section. Still stored as theme_mods (set_theme_mod()/get_theme_mod()),
 * same as before the move, so every existing read site-wide (ajnanda_seo_head_tags(),
 * ajnanda_seo_output_schema(), ajnanda_seo_robots_txt(), etc.) keeps working unchanged.
 *
 * @package AJNanda
 * @var array $values
 * @var bool  $saved
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('SEO Settings', 'ajnanda'); ?></h1>
        <p><?php esc_html_e('Site-wide SEO defaults, structured data, and GEO/AEO (AI answer engine) crawler access.', 'ajnanda'); ?></p>
    </div>

    <?php if ($saved) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('SEO settings saved.', 'ajnanda'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="ajnanda_save_seo_settings">
        <?php wp_nonce_field('ajnanda_seo_save_settings', 'ajnanda_seo_settings_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="seo_meta_description_default"><?php esc_html_e('Default Meta Description', 'ajnanda'); ?></label></th>
                <td>
                    <textarea id="seo_meta_description_default" name="seo_meta_description_default" rows="3" class="large-text"><?php echo esc_textarea($values['seo_meta_description_default']); ?></textarea>
                    <p class="description"><?php esc_html_e('Used on pages/posts that don\'t set their own (see the SEO box on the post editor).', 'ajnanda'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="seo_default_social_image"><?php esc_html_e('Default Social Share Image', 'ajnanda'); ?></label></th>
                <td>
                    <input type="text" id="seo_default_social_image" name="seo_default_social_image" value="<?php echo esc_attr($values['seo_default_social_image']); ?>" class="regular-text">
                    <button type="button" class="button" id="ajnanda_seo_social_image_button"><?php esc_html_e('Choose Image', 'ajnanda'); ?></button>
                    <p class="description"><?php esc_html_e('Used for Open Graph/Twitter previews when a post has no featured image.', 'ajnanda'); ?></p>
                    <?php if ($values['seo_default_social_image']) : ?>
                        <p><img src="<?php echo esc_url($values['seo_default_social_image']); ?>" alt="" style="max-width:240px;height:auto;border-radius:8px;"></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="seo_twitter_handle"><?php esc_html_e('Twitter/X Handle', 'ajnanda'); ?></label></th>
                <td><input type="text" id="seo_twitter_handle" name="seo_twitter_handle" value="<?php echo esc_attr($values['seo_twitter_handle']); ?>" class="regular-text" placeholder="@yourbusiness"></td>
            </tr>
            <tr>
                <th scope="row"><label for="seo_business_phone"><?php esc_html_e('Business Phone', 'ajnanda'); ?></label></th>
                <td>
                    <input type="text" id="seo_business_phone" name="seo_business_phone" value="<?php echo esc_attr($values['seo_business_phone']); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Optional. Filling this in along with Business Address upgrades the schema markup from generic Organization to LocalBusiness.', 'ajnanda'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="seo_business_address"><?php esc_html_e('Business Address', 'ajnanda'); ?></label></th>
                <td><input type="text" id="seo_business_address" name="seo_business_address" value="<?php echo esc_attr($values['seo_business_address']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Schema Markup', 'ajnanda'); ?></th>
                <td>
                    <label><input type="checkbox" name="seo_schema_enabled" value="1" <?php checked($values['seo_schema_enabled']); ?>> <?php esc_html_e('Enable Schema Markup', 'ajnanda'); ?></label>
                    <p class="description"><?php esc_html_e('Adds structured data (Organization/LocalBusiness, Article, FAQ) that search engines and AI answer engines use to understand and cite your content.', 'ajnanda'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('GEO/AEO', 'ajnanda'); ?></th>
                <td>
                    <label><input type="checkbox" name="seo_allow_ai_crawlers" value="1" <?php checked($values['seo_allow_ai_crawlers']); ?>> <?php esc_html_e('Allow AI Crawlers (GEO/AEO)', 'ajnanda'); ?></label>
                    <p class="description"><?php esc_html_e('Explicitly allows GPTBot, ClaudeBot, PerplexityBot, Google-Extended, and CCBot in robots.txt, so ChatGPT/Claude/Perplexity/Google AI Overviews can crawl and cite this site.', 'ajnanda'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('llms.txt', 'ajnanda'); ?></th>
                <td>
                    <label><input type="checkbox" name="seo_llms_txt_enabled" value="1" <?php checked($values['seo_llms_txt_enabled']); ?>> <?php esc_html_e('Publish /llms.txt', 'ajnanda'); ?></label>
                    <p class="description"><?php esc_html_e('A plain-text summary of your site for AI tools that support the emerging llms.txt convention.', 'ajnanda'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Sitemap', 'ajnanda'); ?></th>
                <td>
                    <p>
                        <?php
                        echo wp_kses_post(
                            sprintf(
                                /* translators: %s: sitemap URL */
                                __('WordPress already publishes a sitemap automatically: %s', 'ajnanda'),
                                '<a href="' . esc_url(home_url('/wp-sitemap.xml')) . '" target="_blank" rel="noopener">' . esc_html(home_url('/wp-sitemap.xml')) . '</a>'
                            )
                        );
                        ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save SEO Settings', 'ajnanda')); ?>
    </form>
</div>
<script>
(function () {
    var btn = document.getElementById('ajnanda_seo_social_image_button');
    var input = document.getElementById('seo_default_social_image');
    if (!btn || !input || typeof wp === 'undefined' || !wp.media) { return; }
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        var frame = wp.media({ title: <?php echo wp_json_encode(__('Choose Default Social Share Image', 'ajnanda')); ?>, multiple: false });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            input.value = attachment.url;
        });
        frame.open();
    });
})();
</script>
