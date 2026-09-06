<?php
/** AJ Core-backed native blocks. No AJ Core storage access belongs in this layer. */
defined('ABSPATH') || exit;
require_once dirname(__DIR__) . '/carousel.php';

function ajnanda_reviews_attributes() {
    $attrs = array(
        'heading' => array('type' => 'string', 'default' => ''), 'supportingText' => array('type' => 'string', 'default' => ''),
        'layout' => array('type' => 'string', 'enum' => array('grid', 'list', 'carousel', 'featured'), 'default' => 'grid'),
        'order' => array('type' => 'string', 'enum' => array('configured', 'manual', 'date'), 'default' => 'configured'),
        'limit' => array('type' => 'number', 'default' => 6), 'columns' => array('type' => 'number', 'default' => 3),
        'textLines' => array('type' => 'number', 'default' => 0), 'interval' => array('type' => 'number', 'default' => 6000),
        'viewLabel' => array('type' => 'string', 'default' => ''), 'writeLabel' => array('type' => 'string', 'default' => ''),
    );
    foreach (array('showAvatar', 'showDate', 'showText', 'showRating', 'showSource', 'showOverallRating', 'showTotal', 'showViewButton', 'showWriteButton', 'showDots') as $name) { $attrs[$name] = array('type' => 'boolean', 'default' => true); }
    $attrs['autoplay'] = array('type' => 'boolean', 'default' => false);
    return $attrs;
}

add_action('init', function () {
    $uri = get_template_directory_uri() . '/blocks/ajnanda-blocks/reviews/';
    wp_register_script('ajnanda-reviews-editor', $uri . 'editor.js', array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'), ajnanda_blocks_asset_version('reviews/editor.js'), true);
    wp_register_script('ajnanda-reviews-view', $uri . 'view.js', array(), ajnanda_blocks_asset_version('reviews/view.js'), true);
    wp_register_style('ajnanda-reviews', $uri . 'style.css', array(), ajnanda_blocks_asset_version('reviews/style.css'));
    $attributes = ajnanda_reviews_attributes();
    wp_localize_script('ajnanda-reviews-editor', 'AJNandaReviewsBlocks', array('attributes' => $attributes));
    foreach (array('google-reviews', 'manual-testimonials') as $name) {
        register_block_type('ajnanda/' . $name, array(
            'api_version' => 3, 'attributes' => $attributes,
            'editor_script' => 'ajnanda-reviews-editor', 'style' => 'ajnanda-reviews', 'view_script' => 'ajnanda-reviews-view',
            'supports' => array('html' => false, 'align' => array('wide', 'full'), 'color' => array('text' => true, 'background' => true), 'spacing' => array('margin' => true, 'padding' => true), 'typography' => array('fontSize' => true)),
            'render_callback' => $name === 'google-reviews' ? 'ajnanda_render_google_reviews' : 'ajnanda_render_manual_testimonials',
        ));
    }
    wp_set_script_translations('ajnanda-reviews-editor', 'ajnanda');
});
add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_style('ajnanda-carousel', get_template_directory_uri() . '/blocks/ajnanda-blocks/carousel.css', array(), ajnanda_blocks_asset_version('carousel.css'));
});

// Permit real-data previews only for the authenticated core block-renderer route.
add_filter('rest_request_before_callbacks', function ($response, $handler, $request) {
    $GLOBALS['ajnanda_reviews_rest_context'] = true;
    $GLOBALS['ajnanda_reviews_editor_preview'] = current_user_can('edit_posts') && preg_match('#^/wp/v2/block-renderer/ajnanda/(google-reviews|manual-testimonials)$#D', $request->get_route());
    return $response;
}, 10, 3);
add_filter('rest_request_after_callbacks', function ($response, $handler, $request) {
    if (!empty($GLOBALS['ajnanda_reviews_editor_preview']) && $response instanceof WP_REST_Response) { $response->header('Cache-Control', 'no-store, private'); }
    $GLOBALS['ajnanda_reviews_editor_preview'] = false;
    $GLOBALS['ajnanda_reviews_rest_context'] = false;
    return $response;
}, 10, 3);

function ajnanda_reviews_no_cache() {
    if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
    if (!headers_sent()) { nocache_headers(); header('Cache-Control: no-store, private, max-age=0'); }
}

/** Detect synced patterns as well as directly inserted dynamic blocks, before template headers. */
function ajnanda_reviews_contains_google($content, $depth = 0) {
    if ($depth > 8) { return false; }
    if (has_block('ajnanda/google-reviews', $content)) { return true; }
    foreach (parse_blocks($content) as $block) {
        if ($block['blockName'] === 'core/block' && !empty($block['attrs']['ref'])) {
            $reference = get_post((int) $block['attrs']['ref']);
            if ($reference && ajnanda_reviews_contains_google($reference->post_content, $depth + 1)) { return true; }
        }
        if (!empty($block['innerBlocks']) && ajnanda_reviews_contains_google(serialize_blocks($block['innerBlocks']), $depth + 1)) { return true; }
    }
    return false;
}
add_action('template_redirect', function () {
    global $wp_query;
    foreach ((array) ($wp_query->posts ?? array()) as $post) {
        if ($post instanceof WP_Post && ajnanda_reviews_contains_google($post->post_content)) { ajnanda_reviews_no_cache(); break; }
    }
});

function ajnanda_render_google_reviews($attrs) { return ajnanda_render_review_collection($attrs, 'google'); }
function ajnanda_render_manual_testimonials($attrs) { return ajnanda_render_review_collection($attrs, 'manual'); }

function ajnanda_reviews_empty($message, $editor, $fallback = '') {
    return $editor ? '<div class="aj-reviews__empty" role="status">' . esc_html($message) . '</div>' : ($fallback !== '' ? '<p class="aj-reviews__empty">' . esc_html($fallback) . '</p>' : '');
}

function ajnanda_render_review_collection($attrs, $kind) {
    $editor = !empty($GLOBALS['ajnanda_reviews_editor_preview']);
    // Do not export synchronized content into feeds, search results, CLI discovery jobs or post REST responses.
    if (is_feed() || is_search() || (((defined('REST_REQUEST') && REST_REQUEST) || !empty($GLOBALS['ajnanda_reviews_rest_context'])) && !$editor) || (defined('WP_CLI') && WP_CLI)) { return ''; }
    $defaults = array_map(function ($attribute) { return $attribute['default']; }, ajnanda_reviews_attributes());
    $a = wp_parse_args($attrs, $defaults);
    $google = $kind === 'google';
    if (!function_exists('ajcore_get_review_collections')) { return ajnanda_reviews_empty(__('AJ Core is required. Activate AJ Core to display this collection.', 'ajnanda'), $editor); }
    $settings = ajcore_get_reviews_display_settings();
    $order = $a['order'] === 'configured' ? $settings['order'] : ($a['order'] === 'date' ? 'date' : 'manual');
    $layout = in_array($a['layout'], array('grid', 'list', 'carousel', 'featured'), true) ? $a['layout'] : 'grid';
    $limit = $layout === 'featured' ? 1 : max(1, min(50, (int) $a['limit']));
    $items = $google ? ajcore_get_featured_google_reviews($limit, $order) : ajcore_get_featured_testimonials($limit, $order);
    $status = $google ? ajcore_get_reviews_status() : array();
    if (!$items) {
        $messages = array('disconnected' => __('Google is not connected. An administrator can connect it in AJ Core → Reviews & Testimonials.', 'ajnanda'), 'no_reviews' => __('No valid synchronized Google reviews exist. An administrator can synchronize the selected location.', 'ajnanda'), 'expired' => __('Synchronized Google data has expired. Refresh it in AJ Core.', 'ajnanda'), 'no_featured' => __('No Google reviews are featured. Select reviews in the AJ Core Review Inbox.', 'ajnanda'));
        return ajnanda_reviews_empty($google ? ($messages[$status['state']] ?? __('No displayable Google reviews are available.', 'ajnanda')) : __('No published, featured manual testimonials are available. Create or feature them in AJ Core.', 'ajnanda'), $editor, $settings['fallback']);
    }
    if ($google) { ajnanda_reviews_no_cache(); }
    $summary = $google ? ajcore_get_google_location_summary() : array();
    $classes = 'aj-reviews aj-reviews--' . $kind . ' aj-reviews--' . $layout;
    if ($google && $status['stale']) { $classes .= ' is-stale'; }
    $wrapper = get_block_wrapper_attributes(array('class' => $classes, 'style' => '--aj-reviews-columns:' . max(1, min(4, (int) $a['columns'])) . ';--aj-reviews-lines:' . max(1, min(20, (int) $a['textLines']))));
    $out = '<section ' . $wrapper . ($google ? ' data-nosnippet data-google-expires="' . (int) $status['expires_at'] . '"' : '') . '>';
    if ($a['heading'] !== '') { $out .= '<h2 class="aj-reviews__heading">' . esc_html($a['heading']) . '</h2>'; }
    if ($a['supportingText'] !== '') { $out .= '<p class="aj-reviews__intro">' . esc_html($a['supportingText']) . '</p>'; }
    if ($google) {
        $out .= '<p class="aj-reviews__disclosure">' . esc_html__('Featured Google reviews selected by the business', 'ajnanda') . '. ' . esc_html($order === 'date' ? __('Ordered by publication date, newest first.', 'ajnanda') : __('Ordered by the business; ties use a stable identifier.', 'ajnanda')) . '</p>';
        $out .= '<div class="aj-reviews__summary"><span class="aj-reviews__google" translate="no">Google Maps</span>';
        if ($a['showOverallRating'] && isset($summary['rating'])) { $out .= '<span>' . esc_html(sprintf(__('Overall Google rating: %s out of 5', 'ajnanda'), number_format_i18n((float) $summary['rating'], 1))) . '</span>'; }
        if ($a['showTotal'] && isset($summary['total'])) { $out .= '<span>' . esc_html(sprintf(_n('%s total Google review', '%s total Google reviews', $summary['total'], 'ajnanda'), number_format_i18n($summary['total']))) . '</span>'; }
        $out .= '<span>' . esc_html(sprintf(__('As of %s', 'ajnanda'), wp_date(get_option('date_format'), $status['last_success']))) . '</span></div>';
        if ($editor && $status['stale']) { $out .= '<p class="aj-reviews__empty">' . esc_html__('Refresh is due or the last attempt failed. Only content still within its original expiry is shown.', 'ajnanda') . '</p>'; }
    } else { $out .= '<p class="aj-reviews__disclosure">' . esc_html__('Manual testimonials selected by the business', 'ajnanda') . '</p>'; }
    $cards = '';
    foreach ($items as $item) { $cards .= ajnanda_review_card($item, $a, $summary); }
    $out .= $layout === 'carousel' ? ajnanda_carousel_markup($cards, array('label' => $a['heading'] ?: ($google ? __('Featured Google reviews', 'ajnanda') : __('Manual testimonials', 'ajnanda')), 'autoplay' => !$editor && $a['autoplay'], 'interval' => $a['interval'], 'dots' => $a['showDots'])) : '<div class="aj-reviews__items">' . $cards . '</div>';
    if ($google) {
        $out .= '<div class="aj-reviews__actions">';
        if ($a['showViewButton'] && !empty($summary['maps_url'])) { $out .= '<a class="wp-element-button" href="' . esc_url($summary['maps_url']) . '">' . esc_html($a['viewLabel'] ?: __('View on Google', 'ajnanda')) . '</a>'; }
        if ($a['showWriteButton'] && !empty($summary['write_url'])) { $out .= '<a class="wp-element-button" href="' . esc_url($summary['write_url']) . '">' . esc_html($a['writeLabel'] ?: __('Write a Review', 'ajnanda')) . '</a>'; }
        $out .= '</div>';
    }
    if ($google && $status['expires_at'] <= time()) { return ''; }
    return $out . '</section>';
}

function ajnanda_review_card($item, $a, $summary) {
    $google = $item['kind'] === 'google';
    $name = $item['name'] ?: __('Anonymous reviewer', 'ajnanda');
    $out = '<article class="aj-review"><header class="aj-review__identity">';
    if ($a['showAvatar'] && !empty($item['avatar'])) { $out .= '<img class="aj-review__avatar" src="' . esc_url($item['avatar'], array('https', 'http')) . '" alt="" width="56" height="56" loading="lazy" referrerpolicy="no-referrer">'; }
    elseif (!$google && !empty($item['initials']) && $a['showAvatar']) { $out .= '<span class="aj-review__avatar" aria-hidden="true">' . esc_html($item['initials']) . '</span>'; }
    $out .= '<div><strong class="aj-review__name">' . (!empty($item['profile_url']) ? '<a href="' . esc_url($item['profile_url']) . '">' . esc_html($name) . '</a>' : esc_html($name)) . '</strong>';
    if (!$google && !empty($item['organization'])) { $out .= '<span class="aj-review__organization">' . esc_html($item['organization']) . '</span>'; }
    if ($a['showDate'] && !empty($item['date'])) { $out .= '<time class="aj-review__date" datetime="' . esc_attr($item['date']) . '">' . esc_html($item['relative_date'] ?? '') . (empty($item['relative_date']) ? esc_html(wp_date(get_option('date_format'), strtotime($item['date']))) : '') . '</time>'; }
    $out .= '</div></header>';
    if (($google || $a['showRating']) && isset($item['rating'])) { $out .= '<p class="aj-review__rating" aria-label="' . esc_attr(sprintf(__('%s out of 5 stars', 'ajnanda'), $item['rating'])) . '"><span aria-hidden="true">' . str_repeat('★', max(0, min(5, (int) $item['rating']))) . str_repeat('☆', max(0, 5 - (int) $item['rating'])) . '</span></p>'; }
    if ($a['showText'] && $item['text'] !== '') {
        $text = esc_html($item['text']);
        $lang = !empty($item['language']) && preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/D', $item['language']) ? ' lang="' . esc_attr($item['language']) . '"' : '';
        if ((int) $a['textLines'] > 0) {
            $out .= '<details class="aj-review__expand"><summary><span class="aj-review__preview" aria-hidden="true"' . $lang . '>' . $text . '</span><span class="aj-review__read">' . esc_html__('Read full text', 'ajnanda') . '</span><span class="aj-review__collapse">' . esc_html__('Collapse text', 'ajnanda') . '</span></summary><p class="aj-review__text"' . $lang . '>' . $text . '</p></details>';
        } else { $out .= '<p class="aj-review__text"' . $lang . '>' . $text . '</p>'; }
        if ($google && !empty($item['translated_text'])) { $out .= '<details><summary>' . esc_html__('Translation supplied by Google', 'ajnanda') . '</summary><p class="aj-review__text">' . esc_html($item['translated_text']) . '</p><p>' . esc_html($item['translation_status']) . '</p></details>'; }
    }
    $out .= '<footer class="aj-review__source">';
    if ($google) {
        $out .= '<span class="aj-reviews__google" translate="no">Google Maps</span>';
        if (!empty($item['source_url'])) { $out .= '<a href="' . esc_url($item['source_url']) . '">' . esc_html__('Read this review on Google', 'ajnanda') . '</a>'; }
        elseif (!empty($summary['maps_url'])) { $out .= '<a href="' . esc_url($summary['maps_url']) . '">' . esc_html__('View business on Google Maps', 'ajnanda') . '</a>'; }
        if (!empty($item['report_url'])) { $out .= '<a href="' . esc_url($item['report_url']) . '">' . esc_html__('Report this review', 'ajnanda') . '</a>'; }
    } elseif ($a['showSource'] && (!empty($item['source_label']) || !empty($item['source_url']))) {
        $label = $item['source_label'] ?: __('Source', 'ajnanda');
        $out .= !empty($item['source_url']) ? '<a href="' . esc_url($item['source_url']) . '">' . esc_html($label) . '</a>' : esc_html($label);
    }
    return $out . '</footer></article>';
}
