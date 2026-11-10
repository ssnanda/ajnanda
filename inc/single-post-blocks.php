<?php
/**
 * Block-native single posts (classic-theme compatible).
 *
 * AJNanda is a classic theme: header.php/footer.php are the builder-driven
 * document shell, so a block template (templates/single.html +
 * add_theme_support('block-templates')) would bypass them on every site.
 * Instead:
 *  - core post blocks (post-featured-image, post-title, post-date, ...) placed
 *    inside post content get postId/postType context on the front end;
 *  - single.php skips its built-in hero when the content has its own;
 *  - the Template Slot "single" pattern (patterns/template-single-post.php)
 *    remains the full block layout, opt-in via Customizer.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Give core post blocks their post context when rendered via the_content(). */
function ajnanda_post_blocks_context($context, $parsed_block) {
    if (!is_singular('post') || !in_the_loop() || isset($context['postId'])) {
        return $context;
    }
    $name = isset($parsed_block['blockName']) ? (string) $parsed_block['blockName'] : '';
    if (0 === strpos($name, 'core/post-')) {
        $context['postId']   = get_the_ID();
        $context['postType'] = get_post_type();
    }
    return $context;
}
add_filter('render_block_context', 'ajnanda_post_blocks_context', 10, 2);

/** True when the current post's content carries its own featured image block. */
function ajnanda_post_has_own_hero() {
    $post = get_post();
    return $post && has_block('core/post-featured-image', $post);
}

function ajnanda_register_post_block_styles() {
    register_block_style('core/group', array('name' => 'ajn-callout', 'label' => __('Callout', 'ajnanda')));
    register_block_style('core/group', array('name' => 'ajn-cta-band', 'label' => __('Dark CTA band', 'ajnanda')));
    register_block_style('core/column', array('name' => 'ajn-info-card', 'label' => __('Info card', 'ajnanda')));
    register_block_style('core/details', array('name' => 'ajn-faq', 'label' => __('FAQ', 'ajnanda')));
}
add_action('init', 'ajnanda_register_post_block_styles');

function ajnanda_enqueue_post_block_styles() {
    if (is_singular('post')) {
        wp_enqueue_style('ajnanda-post-blocks', get_template_directory_uri() . '/css/post-blocks.css', array(), ajnanda_asset_version('css/post-blocks.css'));
    }
}
add_action('wp_enqueue_scripts', 'ajnanda_enqueue_post_block_styles', 20);

/** Editor: post type "post" only, injected into the (iframed) canvas. */
function ajnanda_post_editor_styles($settings, $context) {
    if (empty($context->post) || 'post' !== $context->post->post_type) {
        return $settings;
    }
    foreach (array('css/post-blocks.css', 'css/editor-post.css') as $file) {
        $path = get_template_directory() . '/' . $file;
        if (is_readable($path)) {
            $settings['styles'][] = array('css' => file_get_contents($path)); // phpcs:ignore WordPress.WP.AlternativeFunctions
        }
    }
    return $settings;
}
add_filter('block_editor_settings_all', 'ajnanda_post_editor_styles', 30, 2);
