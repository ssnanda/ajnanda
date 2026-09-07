<?php
/** Theme presentation for AJ Core's review invitation. */
defined('ABSPATH') || exit;

function ajnanda_review_prompt_settings() {
    if (!function_exists('ajcore_get_review_prompt_settings')) { return array(); }
    $settings = ajcore_get_review_prompt_settings();
    return $settings['enabled'] && $settings['available'] ? $settings : array();
}

/**
 * Where the prompt bar sits. `top` and `bottom` are full-width bars; `left` and
 * `right` are a compact card pinned to that edge, vertically centred. A separate
 * choice applies on phones.
 */
function ajnanda_review_prompt_positions() {
    return array(
        'top'    => __('Top (full-width bar)', 'ajnanda'),
        'bottom' => __('Bottom (full-width bar)', 'ajnanda'),
        'left'   => __('Left (compact card)', 'ajnanda'),
        'right'  => __('Right (compact card)', 'ajnanda'),
    );
}

function ajnanda_review_prompt_sanitize_position($value) {
    return array_key_exists($value, ajnanda_review_prompt_positions()) ? $value : 'top';
}

/**
 * @param string $context 'desktop' or 'mobile'.
 */
function ajnanda_review_prompt_position($context = 'desktop') {
    $mod = 'mobile' === $context ? 'ajnanda_review_prompt_position_mobile' : 'ajnanda_review_prompt_position';
    return ajnanda_review_prompt_sanitize_position(get_theme_mod($mod, 'top'));
}

add_action('customize_register', function ($wp_customize) {
    $wp_customize->add_section('ajnanda_reviews_prompt', array(
        'title'       => __('Reviews & Testimonials', 'ajnanda'),
        'description' => __('Placement of the "Rate Us" invitation bar. Whether it shows at all, its label, and the review links are set in AJ Core → Reviews & Testimonials.', 'ajnanda'),
        'priority'    => 126,
    ));

    $choices = ajnanda_review_prompt_positions();

    $wp_customize->add_setting('ajnanda_review_prompt_position', array(
        'default'           => 'top',
        'sanitize_callback' => 'ajnanda_review_prompt_sanitize_position',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajnanda_review_prompt_position', array(
        'label'   => __('Position on desktop / tablet', 'ajnanda'),
        'section' => 'ajnanda_reviews_prompt',
        'type'    => 'select',
        'choices' => $choices,
    ));

    $wp_customize->add_setting('ajnanda_review_prompt_position_mobile', array(
        'default'           => 'top',
        'sanitize_callback' => 'ajnanda_review_prompt_sanitize_position',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajnanda_review_prompt_position_mobile', array(
        'label'       => __('Position on phones', 'ajnanda'),
        'description' => __('Set independently from desktop. At the top on phones the bar stays hidden until the visitor scrolls down past it.', 'ajnanda'),
        'section'     => 'ajnanda_reviews_prompt',
        'type'        => 'select',
        'choices'     => $choices,
    ));
});

/** Small inline glyphs for the prompt bar's address / phone items. */
function ajnanda_review_prompt_icon($name) {
    if ('pin' === $name) {
        return '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.9.35 1.77.66 2.61a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.47-1.47a2 2 0 0 1 2.11-.45c.84.31 1.71.53 2.61.66A2 2 0 0 1 22 16.92z"/></svg>';
}

/**
 * Contact + social items shown alongside the Rate Us widget in the prompt bar.
 * Pulls from the theme's existing settings (no new fields):
 *   - address: `seo_business_address` (composed by Search & AI's profile save)
 *   - phone:   `seo_business_phone`   (synced from `search_ai_profile_phone`)
 *   - social:  the header-builder social entries `ajn_builder_social_{1..5}_*`
 * Filterable as a map of regions -> HTML: `lead` (left), `before` and `after`
 * (right, around the widget).
 *
 * @param array $settings The resolved review-prompt settings.
 * @return array{lead:string,before:string,after:string}
 */
function ajnanda_review_prompt_bar_items($settings) {
    $address = trim((string) get_theme_mod('seo_business_address', ''));
    $phone   = trim((string) get_theme_mod('seo_business_phone', ''));

    $lead = '';
    if ('' !== $address) {
        $lead = '<span class="aj-review-prompt-bar__item aj-review-prompt-bar__addr">'
            . ajnanda_review_prompt_icon('pin') . '<span>' . esc_html($address) . '</span></span>';
    }

    $before = '';
    if ('' !== $phone) {
        $tel = preg_replace('/[^0-9+]/', '', $phone);
        $before = '<a class="aj-review-prompt-bar__item aj-review-prompt-bar__phone" href="' . esc_attr('tel:' . $tel) . '">'
            . ajnanda_review_prompt_icon('phone') . '<span>' . esc_html($phone) . '</span></a>';
    }

    $links = array();
    for ($i = 1; $i <= 5; $i++) {
        $url = trim((string) get_theme_mod('ajn_builder_social_' . $i . '_url', ''));
        if ('' === $url || '#' === $url) { continue; }
        $label = get_theme_mod('ajn_builder_social_' . $i . '_label', __('Social', 'ajnanda'));
        $icon  = function_exists('ajnanda_social_icon_svg') ? ajnanda_social_icon_svg($url) : esc_html($label);
        $links[] = '<a href="' . esc_url($url) . '" aria-label="' . esc_attr($label) . '" target="_blank" rel="noopener noreferrer">' . $icon . '</a>';
    }
    $after = $links ? '<span class="aj-review-prompt-bar__item aj-review-prompt-bar__social">' . implode('', $links) . '</span>' : '';

    /**
     * Filter the prompt bar's items. Return a map with `lead` / `before` / `after`
     * HTML strings; anything else is ignored. Regions render as:
     *   [lead] … [before] [Rate Us widget] [after]
     */
    $items = apply_filters('ajnanda_review_prompt_bar_items', array(
        'lead'   => $lead,
        'before' => $before,
        'after'  => $after,
    ), $settings);

    return array(
        'lead'   => isset($items['lead']) ? (string) $items['lead'] : '',
        'before' => isset($items['before']) ? (string) $items['before'] : '',
        'after'  => isset($items['after']) ? (string) $items['after'] : '',
    );
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
    // Native star links work without JavaScript; this only adds the phone-only
    // "slide the strip in once you scroll past the top" reveal (progressive).
    wp_enqueue_script('ajnanda-review-prompt', $uri . 'prompt.js', array(), ajnanda_blocks_asset_version('reviews/prompt.js'), true);
    if ($settings['expires_at']) { wp_enqueue_script('ajnanda-reviews-view'); }
});

function ajnanda_render_review_prompt_bar() {
    $settings = ajnanda_review_prompt_settings();
    if (!$settings) { return; }
    $id    = wp_unique_id('aj-rate-us-');
    $items = ajnanda_review_prompt_bar_items($settings);
    $pos_d = ajnanda_review_prompt_position('desktop');
    $pos_m = ajnanda_review_prompt_position('mobile');
    $classes = 'aj-review-prompt-bar aj-review-prompt-bar--d-' . $pos_d . ' aj-review-prompt-bar--m-' . $pos_m;
    ?>
    <div class="<?php echo esc_attr($classes); ?>" data-m-position="<?php echo esc_attr($pos_m); ?>"<?php if ($settings['expires_at']) : ?> data-nosnippet data-google-expires="<?php echo (int) $settings['expires_at']; ?>"<?php endif; ?>>
        <div class="container aj-review-prompt-bar__inner">
            <?php if ('' !== $items['lead']) : ?>
                <div class="aj-review-prompt-bar__lead"><?php echo $items["lead"]; ?></div>
            <?php endif; ?>
            <div class="aj-review-prompt-bar__actions">
                <?php if ('' !== $items['before']) { echo $items["before"]; } ?>
            <div class="aj-rate-us">
                <span class="aj-rate-us__label" id="<?php echo esc_attr($id); ?>-label"><?php echo esc_html($settings['label']); ?></span>
                <div class="aj-rate-us__stars" role="group" aria-labelledby="<?php echo esc_attr($id); ?>-label">
                    <?php for ($star = 1; $star <= 5; ++$star) :
                        $is_google = 5 === $star;
                        $url = $is_google ? $settings['google_review_url'] : $settings['feedback_url'];
                        /* translators: %d: selected star rating. */
                        $label = $is_google ? __('5 stars: Leave a review on Google', 'ajnanda') : sprintf(__('%d stars: Send private feedback', 'ajnanda'), $star);
                        ?>
                        <a class="aj-rate-us__star" href="<?php echo esc_url($url); ?>"<?php if ($is_google) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?> aria-label="<?php echo esc_attr($label); ?>"><span aria-hidden="true">★</span></a>
                    <?php endfor; ?>
                </div>
            </div>
                <?php if ('' !== $items['after']) { echo $items["after"]; } ?>
            </div>
        </div>
    </div>
    <?php
}
