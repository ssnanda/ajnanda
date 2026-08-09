<?php
/**
 * AJNanda Page Designs — composition helpers.
 *
 * A "Page Design" is a full-page block pattern registered from
 * /patterns/page-*.php with `Block Types: core/post-content` and
 * `Post Types: page`, which makes WordPress core show it automatically in
 * the native "Choose a pattern" modal on Pages → Add New.
 *
 * Rather than re-pasting section markup into every page-design file, each
 * page-design file calls ajnanda_compose_page_content() with an ordered
 * list of section-pattern slugs. This function pulls each section's
 * already-registered `content` straight from WP_Block_Patterns_Registry —
 * the section pattern file is the single canonical source, the page design
 * is just a manifest of which sections to stack. Once a page design is
 * actually inserted into a page, the result is a normal, static block
 * markup string saved into post_content — there is no ongoing dependency
 * on the section pattern or a synced/reusable block.
 *
 * See docs/page-designs.md.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fetch a single registered pattern's block markup by slug.
 *
 * @param string $slug Pattern slug, e.g. "ajnanda/section-hero-super-bold".
 * @return string Block markup, or an HTML comment noting the missing
 *                pattern (visible only to logged-in editors reading source,
 *                harmless on the front end, and easy to grep for).
 */
function ajnanda_get_pattern_content($slug) {
    if (!class_exists('WP_Block_Patterns_Registry')) {
        return '';
    }

    $registry = WP_Block_Patterns_Registry::get_instance();

    if (!$registry->is_registered($slug)) {
        return sprintf('<!-- AJNanda: pattern "%s" not found -->', esc_html($slug));
    }

    $pattern = $registry->get_registered($slug);

    return isset($pattern['content']) ? $pattern['content'] : '';
}

/**
 * Compose a page design's content out of an ordered list of section
 * pattern slugs.
 *
 * @param string[]             $section_slugs Ordered list of section pattern slugs to stack.
 * @param array<string,string> $tokens        Optional extra {{token}} => replacement pairs,
 *                                             merged over the built-in site tokens below.
 * @return string Combined block markup.
 */
function ajnanda_compose_page_content(array $section_slugs, array $tokens = array()) {
    $content = '';

    foreach ($section_slugs as $slug) {
        $content .= ajnanda_get_pattern_content($slug);
    }

    $default_tokens = array(
        '{{site_title}}'   => get_bloginfo('name'),
        '{{site_tagline}}' => get_bloginfo('description'),
    );

    $tokens = array_merge($default_tokens, $tokens);

    return strtr($content, $tokens);
}

/**
 * Return every registered Page Design (patterns tagged with the
 * ajnanda-page-designs category), keyed by slug.
 *
 * Used by the Page Library admin screen, the starter-site importer, and
 * the `wp ajnanda page-design` CLI commands — a single source of truth so
 * all three stay in sync automatically as page designs are added.
 *
 * @return array<string,array> slug => pattern data.
 */
function ajnanda_get_page_designs() {
    if (!class_exists('WP_Block_Patterns_Registry')) {
        return array();
    }

    $registry = WP_Block_Patterns_Registry::get_instance();
    $designs  = array();

    foreach ($registry->get_all_registered() as $slug => $pattern) {
        $categories = isset($pattern['categories']) ? (array) $pattern['categories'] : array();
        if (in_array('ajnanda-page-designs', $categories, true)) {
            $designs[$slug] = $pattern;
        }
    }

    return $designs;
}

/**
 * Insert a single Page Design as a new WordPress page.
 *
 * Shared by the Page Library admin screen and the
 * `wp ajnanda page-design insert` CLI command.
 *
 * @param string $slug   Page design pattern slug.
 * @param string $title  Title for the new page.
 * @param string $status 'draft' or 'publish'.
 * @return int|WP_Error New page ID, or WP_Error on failure.
 */
function ajnanda_insert_page_design($slug, $title, $status = 'draft') {
    if (!class_exists('WP_Block_Patterns_Registry') || !WP_Block_Patterns_Registry::get_instance()->is_registered($slug)) {
        return new WP_Error('ajnanda_unknown_page_design', __('Unknown page design.', 'ajnanda'));
    }

    if (!in_array($status, array('draft', 'publish'), true)) {
        $status = 'draft';
    }

    $content = ajnanda_get_pattern_content($slug);

    $post_id = wp_insert_post(array(
        'post_type'    => 'page',
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $status,
    ), true);

    if (!is_wp_error($post_id)) {
        update_post_meta($post_id, '_ajnanda_page_design', $slug);
    }

    return $post_id;
}
