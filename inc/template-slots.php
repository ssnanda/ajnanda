<?php
/**
 * Template slots + brand palette sync.
 *
 * AJNanda is a classic PHP theme, so footer / 404 / single / archive are not
 * block templates. A "slot" lets each of them be edited with blocks anyway:
 * Customizer > Template Slots picks a theme pattern or a synced pattern
 * (wp_block) per slot, and the PHP template renders it in place of the
 * built-in markup. "Built-in" stays the default, so nothing changes until a
 * site opts in.
 *
 * Slots: footer, 404, single, archive, post-cta.
 *
 * Theme patterns offered for a slot are those in category `ajnanda-templates`
 * whose Keywords include `slot-<slot>`.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

function ajnanda_get_template_slots() {
    return array(
        'footer'   => __('Footer', 'ajnanda'),
        '404'      => __('404 page', 'ajnanda'),
        'single'   => __('Single post', 'ajnanda'),
        'archive'  => __('Archive / blog index', 'ajnanda'),
        'post-cta' => __('Post CTA', 'ajnanda'),
    );
}

/**
 * Choices for one slot: theme patterns tagged for it + synced patterns.
 */
function ajnanda_get_slot_choices($slot) {
    $choices = array('' => __('Built-in', 'ajnanda'));

    if (class_exists('WP_Block_Patterns_Registry')) {
        foreach (WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern) {
            if (!in_array('slot-' . $slot, (array) ($pattern['keywords'] ?? array()), true)) {
                continue;
            }
            $choices['pattern:' . $pattern['name']] = sprintf(__('Pattern: %s', 'ajnanda'), $pattern['title']);
        }
    }

    $synced = get_posts(array(
        'post_type'      => 'wp_block',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ));
    foreach ($synced as $post) {
        $choices['block:' . $post->ID] = sprintf(__('Synced: %s', 'ajnanda'), $post->post_title ?: '#' . $post->ID);
    }

    return $choices;
}

function ajnanda_sanitize_slot_choice($value) {
    $value = (string) $value;
    return preg_match('/^(pattern:[a-z0-9_\-\/]+|block:\d+)?$/i', $value) ? $value : '';
}

/**
 * Raw block markup for a slot, or '' when the slot uses the built-in markup.
 */
function ajnanda_get_slot_content($slot) {
    $value = (string) get_theme_mod('ajn_slot_' . str_replace('-', '_', $slot), '');
    if ('' === $value) {
        return '';
    }

    if (0 === strpos($value, 'pattern:')) {
        $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered(substr($value, 8));
        return $pattern ? (string) $pattern['content'] : '';
    }

    $post = get_post((int) substr($value, 6));
    return ($post && 'wp_block' === $post->post_type && 'publish' === $post->post_status) ? (string) $post->post_content : '';
}

/**
 * Render a slot. $context (e.g. postId/postType) is handed to every top-level
 * block so post-title / post-content / post-navigation-link work outside a
 * block template. Returns '' when the slot is unset (caller falls back).
 */
function ajnanda_render_slot($slot, $context = array()) {
    $content = ajnanda_get_slot_content($slot);
    if ('' === trim($content)) {
        return '';
    }

    if (!$context) {
        return do_blocks($content);
    }

    $html = '';
    foreach (parse_blocks($content) as $parsed) {
        $html .= (new WP_Block($parsed, $context))->render();
    }
    return $html;
}

/**
 * Dynamic block so one slot (e.g. the post CTA) can be dropped into another
 * slot's pattern.
 */
function ajnanda_register_slot_block() {
    register_block_type('ajnanda/slot', array(
        'api_version'     => 3,
        'title'           => __('AJNanda Slot', 'ajnanda'),
        'category'        => 'design',
        'attributes'      => array('slot' => array('type' => 'string', 'default' => 'post-cta')),
        'supports'        => array('autoRegister' => true, 'html' => false),
        'render_callback' => static function ($attributes) {
            $slot = isset($attributes['slot']) ? (string) $attributes['slot'] : '';
            // Only the CTA slot nests; allowing others would permit recursion.
            return 'post-cta' === $slot ? ajnanda_render_slot('post-cta') : '';
        },
    ));
}
add_action('init', 'ajnanda_register_slot_block');

/**
 * "Related posts": a core/query block with className ajn-related-posts shows
 * posts sharing a category with the current post, minus the current post.
 */
function ajnanda_related_query_vars($query, $block) {
    $class = $block->parsed_block['attrs']['className'] ?? '';
    if (false === strpos((string) $class, 'ajn-related-posts') || !is_singular()) {
        return $query;
    }

    $post_id = get_queried_object_id();
    $cats    = wp_get_post_categories($post_id);
    if ($cats) {
        $query['category__in'] = $cats;
    }
    $query['post__not_in']        = array($post_id);
    $query['ignore_sticky_posts'] = 1;

    return $query;
}
add_filter('query_loop_block_query_vars', 'ajnanda_related_query_vars', 10, 2);

/**
 * Customizer: Template Slots section.
 */
function ajnanda_slots_customize_register($wp_customize) {
    $wp_customize->add_section('ajnanda_template_slots', array(
        'title'    => __('Template Slots', 'ajnanda'),
        'priority' => 125,
    ));

    foreach (ajnanda_get_template_slots() as $slot => $label) {
        $id = 'ajn_slot_' . str_replace('-', '_', $slot);
        $wp_customize->add_setting($id, array(
            'default'           => '',
            'sanitize_callback' => 'ajnanda_sanitize_slot_choice',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($id, array(
            'section' => 'ajnanda_template_slots',
            'label'   => $label,
            'type'    => 'select',
            'choices' => ajnanda_get_slot_choices($slot),
        ));
    }
}
add_action('customize_register', 'ajnanda_slots_customize_register');

/**
 * Brand palette sync: Customizer brand colors -> block editor / theme.json
 * palette, so editors can pick them. Slugs stay put (patterns reference
 * them); only the color value and label follow the Customizer.
 */
function ajnanda_get_brand_palette_map() {
    return array(
        'primary-blue' => array('theme_primary_color', __('Brand Primary', 'ajnanda')),
        'deep-blue'    => array('theme_primary_dark_color', __('Brand Primary Hover', 'ajnanda')),
        'purple'       => array('theme_secondary_color', __('Brand Secondary', 'ajnanda')),
        'gold'         => array('theme_accent_color', __('Brand Accent', 'ajnanda')),
    );
}

function ajnanda_sync_brand_palette($theme_json) {
    $data    = $theme_json->get_data();
    $palette = $data['settings']['color']['palette'] ?? array();
    if (!$palette) {
        return $theme_json;
    }

    $map = ajnanda_get_brand_palette_map();
    foreach ($palette as $i => $entry) {
        $slug = $entry['slug'] ?? '';
        if (!isset($map[$slug])) {
            continue;
        }
        $color = sanitize_hex_color((string) get_theme_mod($map[$slug][0], ''));
        if ($color) {
            $palette[$i]['color'] = $color;
        }
        $palette[$i]['name'] = $map[$slug][1];
    }

    return $theme_json->update_with(array(
        'version'  => $data['version'] ?? 3,
        'settings' => array('color' => array('palette' => $palette)),
    ));
}
add_filter('wp_theme_json_data_theme', 'ajnanda_sync_brand_palette');
