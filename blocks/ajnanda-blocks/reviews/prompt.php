<?php
/** Theme presentation for AJ Core's rating-neutral review invitation. */
defined('ABSPATH') || exit;

function ajnanda_review_prompt_settings() {
    if (!function_exists('ajcore_get_review_prompt_settings')) { return array(); }
    $settings = ajcore_get_review_prompt_settings();
    return $settings['enabled'] && $settings['available'] ? $settings : array();
}

add_action('template_redirect', function () {
    $settings = ajnanda_review_prompt_settings();
    if (!empty($settings['expires_at'])) { ajnanda_reviews_no_cache(); }
});

add_action('wp_enqueue_scripts', function () {
    $settings = ajnanda_review_prompt_settings();
    if (!$settings) { return; }
    $uri = get_template_directory_uri() . '/blocks/ajnanda-blocks/reviews/';
    wp_enqueue_style('ajnanda-review-prompt', $uri . 'prompt.css', array(), ajnanda_blocks_asset_version('reviews/prompt.css'));
    wp_enqueue_script('ajnanda-review-prompt', $uri . 'prompt.js', array(), ajnanda_blocks_asset_version('reviews/prompt.js'), true);
    if ($settings['expires_at']) { wp_enqueue_script('ajnanda-reviews-view'); }
});

function ajnanda_render_review_prompt_bar() {
    $settings = ajnanda_review_prompt_settings();
    if (!$settings) { return; }
    $id = wp_unique_id('aj-rate-us-');
    ?>
    <div class="aj-review-prompt-bar"<?php if ($settings['expires_at']) : ?> data-nosnippet data-google-expires="<?php echo (int) $settings['expires_at']; ?>"<?php endif; ?>>
        <div class="container aj-review-prompt-bar__inner">
            <div class="aj-rate-us" data-aj-rate-us>
                <span class="aj-rate-us__label" id="<?php echo esc_attr($id); ?>-label"><?php echo esc_html($settings['label']); ?></span>
                <div class="aj-rate-us__stars" role="group" aria-labelledby="<?php echo esc_attr($id); ?>-label" hidden>
                    <?php for ($rating = 1; $rating <= 5; ++$rating) : ?>
                        <button type="button" data-rating="<?php echo (int) $rating; ?>" aria-controls="<?php echo esc_attr($id); ?>-choices" aria-expanded="false" aria-label="<?php echo esc_attr(sprintf(_n('%d star: choose feedback options', '%d stars: choose feedback options', $rating, 'ajnanda'), $rating)); ?>" data-message="<?php echo esc_attr(sprintf(_n('You selected %d star. Choose where to share your experience.', 'You selected %d stars. Choose where to share your experience.', $rating, 'ajnanda'), $rating)); ?>"><span aria-hidden="true">★</span></button>
                    <?php endfor; ?>
                </div>
                <div class="aj-rate-us__choices" id="<?php echo esc_attr($id); ?>-choices">
                    <p class="aj-rate-us__status" role="status"><?php esc_html_e('Choose where to share your experience.', 'ajnanda'); ?></p>
                    <a href="<?php echo esc_url($settings['feedback_url']); ?>"><?php esc_html_e('Send private feedback', 'ajnanda'); ?></a>
                    <a href="<?php echo esc_url($settings['google_review_url']); ?>"><?php esc_html_e('Leave a Google review', 'ajnanda'); ?></a>
                    <p class="aj-rate-us__note"><?php esc_html_e('Both options are available for every rating. Your star selection is not submitted here.', 'ajnanda'); ?></p>
                    <button class="aj-rate-us__close" type="button" hidden><?php esc_html_e('Close', 'ajnanda'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
