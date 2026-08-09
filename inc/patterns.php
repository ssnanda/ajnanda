<?php
/**
 * AJNanda Pattern Categories.
 *
 * Registers the block-pattern categories used by everything under /patterns
 * (both individual section patterns and full Page Designs).
 *
 * Note: the "is-style-ajnanda-*" block styles (cards, eyebrow, checklists,
 * icon tiles, equal-height columns) are already registered as native
 * Gutenberg block styles client-side in js/editor-controls.js
 * (registerAjnandaBlockStyles()) — patterns reuse those existing
 * classNames rather than re-registering them here, so there's no server
 * side duplicate of that system.
 *
 * The pattern *content* itself is not registered here — WordPress core
 * auto-registers every file in /patterns using its file-header comment
 * (Title/Slug/Categories/etc.), so adding a new pattern never requires
 * touching this file. See docs/patterns.md.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJNanda block pattern categories.
 *
 * `ajnanda-builder` is the original (pre-1.3.0) category and is kept for
 * backward compatibility with the 14 legacy patterns. `ajnanda-page-designs`
 * holds full-page patterns (see inc/page-designs.php). The rest map 1:1 to
 * the section types documented in docs/patterns.md.
 */
function ajnanda_register_pattern_categories() {
    if (!function_exists('register_block_pattern_category')) {
        return;
    }

    $categories = array(
        'ajnanda-builder'      => __('AJNanda Sections (Legacy)', 'ajnanda'),
        'ajnanda-page-designs' => __('AJNanda: Page Designs', 'ajnanda'),
        'ajnanda-hero'         => __('AJNanda: Hero', 'ajnanda'),
        'ajnanda-services'     => __('AJNanda: Services', 'ajnanda'),
        'ajnanda-content'      => __('AJNanda: Content', 'ajnanda'),
        'ajnanda-social-proof' => __('AJNanda: Social Proof', 'ajnanda'),
        'ajnanda-data'         => __('AJNanda: Stats & Data', 'ajnanda'),
        'ajnanda-cta'          => __('AJNanda: Calls to Action', 'ajnanda'),
        'ajnanda-faq'          => __('AJNanda: FAQ', 'ajnanda'),
        'ajnanda-team'         => __('AJNanda: Team', 'ajnanda'),
        'ajnanda-contact'      => __('AJNanda: Contact', 'ajnanda'),
        'ajnanda-footer'       => __('AJNanda: Footer & Auxiliary', 'ajnanda'),
    );

    foreach ($categories as $slug => $label) {
        register_block_pattern_category($slug, array('label' => $label));
    }
}
add_action('init', 'ajnanda_register_pattern_categories', 5);
