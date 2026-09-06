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

function ajnanda_carousel_markup($content, $args = array()) {
    $args = wp_parse_args($args, array('label' => __('Carousel', 'ajnanda'), 'autoplay' => false, 'interval' => 6000, 'dots' => true, 'loop' => true, 'effect' => 'slide', 'speed' => 400));
    ajnanda_carousel_assets();
    $id = wp_unique_id('ajnanda-carousel-');
    return '<div class="aj-carousel" role="region" aria-roledescription="' . esc_attr__('carousel', 'ajnanda') . '" aria-label="' . esc_attr($args['label']) . '" data-aj-carousel data-effect="' . ($args['effect'] === 'fade' ? 'fade' : 'slide') . '" data-speed="' . max(0, min(2000, (int) $args['speed'])) . '" data-autoplay="' . ($args['autoplay'] ? 'true' : 'false') . '" data-loop="' . ($args['loop'] ? 'true' : 'false') . '" data-interval="' . max(3000, min(30000, (int) $args['interval'])) . '">'
        . '<div class="aj-carousel__track" id="' . esc_attr($id) . '" tabindex="0" aria-label="' . esc_attr__('Slides; use left and right arrow keys to navigate', 'ajnanda') . '">' . $content . '</div>'
        . '<div class="aj-carousel__controls" hidden><button type="button" data-prev aria-controls="' . esc_attr($id) . '">' . esc_html__('Previous', 'ajnanda') . '</button>'
        . '<button type="button" data-next aria-controls="' . esc_attr($id) . '">' . esc_html__('Next', 'ajnanda') . '</button>'
        . ($args['autoplay'] ? '<button type="button" data-pause data-play-label="' . esc_attr__('Start automatic rotation', 'ajnanda') . '" data-pause-label="' . esc_attr__('Pause automatic rotation', 'ajnanda') . '">' . esc_html__('Pause automatic rotation', 'ajnanda') . '</button>' : '')
        . ($args['dots'] ? '<div class="aj-carousel__dots" data-dots aria-label="' . esc_attr__('Choose slide', 'ajnanda') . '"></div>' : '')
        . '<span class="screen-reader-text" data-position aria-live="polite" data-slide-label="' . esc_attr__('Slide %1$d of %2$d', 'ajnanda') . '"></span></div></div>';
}
