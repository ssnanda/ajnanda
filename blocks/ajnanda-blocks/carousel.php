<?php
/** Shared progressively enhanced carousel for sliders and review collections. */
defined( 'ABSPATH' ) || exit;

function ajnanda_carousel_assets() {
    $uri = get_template_directory_uri() . '/blocks/ajnanda-blocks/';
    wp_enqueue_style('ajnanda-carousel', $uri . 'carousel.css', array(), ajnanda_blocks_asset_version('carousel.css'));
    wp_enqueue_script('ajnanda-carousel', $uri . 'carousel.js', array(), ajnanda_blocks_asset_version('carousel.js'), true);
}
// Classic themes render content after wp_head: enqueue the small shared assets before the head.
add_action('wp_enqueue_scripts', 'ajnanda_carousel_assets');

/** Inline chevron for the carousel's prev/next buttons. Decorative — the button
 *  carries its accessible name in a .screen-reader-text span. */
function ajnanda_carousel_chevron($dir) {
    $path = 'prev' === $dir ? 'M15 18l-6-6 6-6' : 'M9 18l6-6-6-6';
    return '<svg class="aj-carousel__chevron" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . $path . '"/></svg>';
}

/** Play + pause glyphs for the autoplay toggle. CSS shows one at a time based on
 *  the button's [data-paused] state (set by carousel.js). Decorative — the
 *  button's accessible name lives in its .screen-reader-text span. */
function ajnanda_carousel_playpause_icon() {
    return '<svg class="aj-carousel__ico aj-carousel__ico--pause" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>'
        . '<svg class="aj-carousel__ico aj-carousel__ico--play" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5l12 7-12 7z"/></svg>';
}

function ajnanda_carousel_markup($content, $args = array()) {
    $args = wp_parse_args($args, array('label' => __('Carousel', 'ajnanda'), 'autoplay' => false, 'interval' => 6000, 'dots' => true, 'loop' => true, 'effect' => 'slide', 'speed' => 400));
    ajnanda_carousel_assets();
    $id = wp_unique_id('ajnanda-carousel-');
    return '<div class="aj-carousel" role="region" aria-roledescription="' . esc_attr__('carousel', 'ajnanda') . '" aria-label="' . esc_attr($args['label']) . '" data-aj-carousel data-effect="' . ($args['effect'] === 'fade' ? 'fade' : 'slide') . '" data-speed="' . max(0, min(2000, (int) $args['speed'])) . '" data-autoplay="' . ($args['autoplay'] ? 'true' : 'false') . '" data-loop="' . ($args['loop'] ? 'true' : 'false') . '" data-interval="' . max(3000, min(30000, (int) $args['interval'])) . '">'
        // Track + the controls overlaid on it (prev/next on the sides, pause
        // bottom-centre) share this positioning context so they anchor to the
        // slide box, not the whole component. Dots + the live-region stay below.
        . '<div class="aj-carousel__viewport">'
        . '<div class="aj-carousel__track" id="' . esc_attr($id) . '" tabindex="0" aria-label="' . esc_attr__('Slides; use left and right arrow keys to navigate', 'ajnanda') . '">' . $content . '</div>'
        . '<div class="aj-carousel__overlay" hidden><button type="button" data-prev aria-controls="' . esc_attr($id) . '"><span class="screen-reader-text">' . esc_html__('Previous', 'ajnanda') . '</span>' . ajnanda_carousel_chevron('prev') . '</button>'
        . '<button type="button" data-next aria-controls="' . esc_attr($id) . '"><span class="screen-reader-text">' . esc_html__('Next', 'ajnanda') . '</span>' . ajnanda_carousel_chevron('next') . '</button>'
        . ($args['autoplay'] ? '<button type="button" data-pause data-play-label="' . esc_attr__('Start automatic rotation', 'ajnanda') . '" data-pause-label="' . esc_attr__('Pause automatic rotation', 'ajnanda') . '"><span class="screen-reader-text" data-pause-text>' . esc_html__('Pause automatic rotation', 'ajnanda') . '</span>' . ajnanda_carousel_playpause_icon() . '</button>' : '')
        . '</div>'
        // Dim "next slide in Ns" hint, bottom-right. Decorative (aria-hidden), JS
        // only shows it while autoplay is actually counting down.
        . ($args['autoplay'] ? '<div class="aj-carousel__countdown" data-countdown="' . esc_attr__('Next slide in %ds', 'ajnanda') . '" aria-hidden="true" hidden></div>' : '')
        . '</div>'
        . '<div class="aj-carousel__controls" hidden>'
        . ($args['dots'] ? '<div class="aj-carousel__dots" data-dots aria-label="' . esc_attr__('Choose slide', 'ajnanda') . '"></div>' : '')
        . '<span class="screen-reader-text" data-position aria-live="polite" data-slide-label="' . esc_attr__('Slide %1$d of %2$d', 'ajnanda') . '"></span></div></div>';
}
