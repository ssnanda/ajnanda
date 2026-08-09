<?php
/**
 * AJNanda Color Schemes.
 *
 * Every AJNanda pattern, page design, and starter site is already styled
 * from a small set of brand CSS custom properties (--primary,
 * --primary-dark, --secondary, --accent — see style.css :root and the
 * theme.json button element). A "color scheme" here is just a named set of
 * overrides for those same four properties, applied either:
 *
 *  - Site-wide, via a Customizer control (AJNanda: Color Scheme) — every
 *    existing pattern recolors automatically, no per-pattern work needed.
 *  - Per page, via the "Color scheme" picker on the AJNanda → Page Library
 *    admin screen, which wraps that one page's content in a matching
 *    .ajnanda-scheme-{slug} class (see style.css) instead of changing the
 *    site-wide setting.
 *
 * Important: the override must reach three places, not just the frontend —
 * confirmed against a real AJNanda site that already hand-rolls a similar
 * override (an inline `:root` block in wp_head) and found its "Choose a
 * pattern" previews still showed default colors, because that override
 * never reached the block editor's iframe:
 *   1. The public-facing site (wp_enqueue_scripts)
 *   2. The classic post-editor stylesheet (enqueue_block_editor_assets)
 *   3. The iframed block editor / pattern-preview thumbnails
 *      (block_editor_settings_all — this is what the "Choose a pattern"
 *      modal actually renders previews with)
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * All available color schemes. Primary/secondary/accent values reuse the
 * theme's existing theme.json palette colors (primary-blue, deep-blue,
 * purple, gold, ink) — only the "-dark" hover shades are new, matching the
 * theme's existing practice of keeping hover shades in style.css rather
 * than theme.json.
 *
 * @return array<string,array{label:string,swatch:string,primary:string,primary_dark:string,secondary:string,accent:string}>
 */
function ajnanda_get_color_schemes() {
    return array(
        'blue' => array(
            'label'        => __('Blue (Default)', 'ajnanda'),
            'swatch'       => '#2563eb',
            'primary'      => '#2563eb',
            'primary_dark' => '#1e40af',
            'secondary'    => '#7c3aed',
            'accent'       => '#f59e0b',
        ),
        'purple' => array(
            'label'        => __('Purple', 'ajnanda'),
            'swatch'       => '#7c3aed',
            'primary'      => '#7c3aed',
            'primary_dark' => '#6d28d9',
            'secondary'    => '#2563eb',
            'accent'       => '#f59e0b',
        ),
        'gold' => array(
            'label'        => __('Gold', 'ajnanda'),
            'swatch'       => '#f59e0b',
            'primary'      => '#f59e0b',
            'primary_dark' => '#b45309',
            'secondary'    => '#111827',
            'accent'       => '#2563eb',
        ),
        'dark' => array(
            'label'        => __('Dark', 'ajnanda'),
            'swatch'       => '#111827',
            'primary'      => '#111827',
            'primary_dark' => '#000000',
            'secondary'    => '#f59e0b',
            'accent'       => '#f59e0b',
        ),
    );
}

/**
 * The site-wide scheme currently set in the Customizer.
 *
 * @return string A key from ajnanda_get_color_schemes(), always valid.
 */
function ajnanda_get_active_color_scheme_slug() {
    $slug    = get_theme_mod('ajnanda_color_scheme', 'blue');
    $schemes = ajnanda_get_color_schemes();
    return isset($schemes[$slug]) ? $slug : 'blue';
}

/**
 * @param string $slug
 * @return string A `:root{...}` CSS custom-property override, or '' for an unknown slug.
 */
function ajnanda_get_color_scheme_css($slug) {
    $schemes = ajnanda_get_color_schemes();
    if (!isset($schemes[$slug])) {
        return '';
    }
    $s = $schemes[$slug];
    return sprintf(
        ':root{--primary:%1$s;--primary-dark:%2$s;--secondary:%3$s;--accent:%4$s;}',
        esc_attr($s['primary']),
        esc_attr($s['primary_dark']),
        esc_attr($s['secondary']),
        esc_attr($s['accent'])
    );
}

/**
 * Wrap a block of page content in a per-page color-scheme class. Used by
 * the Page Library "Add as New Page" action when the chosen scheme differs
 * from the site-wide one. No-op for 'blue' when the site-wide scheme is
 * also blue, to avoid an unnecessary wrapper group on the common path.
 *
 * @param string $content Composed block markup.
 * @param string $slug    Color scheme slug.
 * @return string
 */
function ajnanda_wrap_content_with_color_scheme($content, $slug) {
    $schemes = ajnanda_get_color_schemes();
    if (!isset($schemes[$slug])) {
        return $content;
    }

    if ($slug === ajnanda_get_active_color_scheme_slug()) {
        return $content; // matches the site-wide scheme already — no wrapper needed.
    }

    $class = 'ajnanda-scheme-' . $slug;

    return '<!-- wp:group {"className":"' . $class . '","layout":{"type":"constrained"}} -->'
        . '<div class="wp-block-group ' . $class . '">' . $content . '</div>'
        . '<!-- /wp:group -->';
}

/* -----------------------------------------------------------------------
 * Customizer control
 * ------------------------------------------------------------------- */

function ajnanda_color_scheme_customize_register($wp_customize) {
    $wp_customize->add_section('ajnanda_color_scheme', array(
        'title'    => __('AJNanda: Color Scheme', 'ajnanda'),
        'priority' => 24,
    ));

    $wp_customize->add_setting('ajnanda_color_scheme', array(
        'default'           => 'blue',
        'sanitize_callback' => 'ajnanda_sanitize_color_scheme',
        'transport'         => 'refresh',
    ));

    $choices = array();
    foreach (ajnanda_get_color_schemes() as $slug => $scheme) {
        $choices[$slug] = $scheme['label'];
    }

    $wp_customize->add_control('ajnanda_color_scheme', array(
        'label'       => __('Color Scheme', 'ajnanda'),
        'description' => __('Recolors every AJNanda pattern, page design, and starter site using the brand colors they already use — no per-pattern changes needed. New pages added from the Page Library default to this scheme.', 'ajnanda'),
        'section'     => 'ajnanda_color_scheme',
        'type'        => 'select',
        'choices'     => $choices,
    ));
}
add_action('customize_register', 'ajnanda_color_scheme_customize_register');

function ajnanda_sanitize_color_scheme($value) {
    $schemes = ajnanda_get_color_schemes();
    return isset($schemes[$value]) ? $value : 'blue';
}

/* -----------------------------------------------------------------------
 * Apply the site-wide scheme — frontend, classic editor, iframed editor.
 * ------------------------------------------------------------------- */

function ajnanda_enqueue_color_scheme_frontend_css() {
    $slug = ajnanda_get_active_color_scheme_slug();
    if ('blue' === $slug || !wp_style_is('ajnanda-pro-style', 'registered')) {
        return;
    }
    wp_add_inline_style('ajnanda-pro-style', ajnanda_get_color_scheme_css($slug));
}
add_action('wp_enqueue_scripts', 'ajnanda_enqueue_color_scheme_frontend_css', 20);

function ajnanda_enqueue_color_scheme_editor_css() {
    $slug = ajnanda_get_active_color_scheme_slug();
    if ('blue' === $slug || !wp_style_is('ajnanda-pro-editor-style', 'registered')) {
        return;
    }
    wp_add_inline_style('ajnanda-pro-editor-style', ajnanda_get_color_scheme_css($slug));
}
add_action('enqueue_block_editor_assets', 'ajnanda_enqueue_color_scheme_editor_css', 20);

/**
 * Reaches the iframed block editor canvas and, critically, the "Choose a
 * pattern" modal's own preview thumbnails — neither is covered by a normal
 * enqueued stylesheet, only by being added to the editor's own settings.
 */
function ajnanda_add_color_scheme_to_iframed_editor($settings) {
    $slug = ajnanda_get_active_color_scheme_slug();
    if ('blue' === $slug) {
        return $settings;
    }
    if (!isset($settings['styles']) || !is_array($settings['styles'])) {
        $settings['styles'] = array();
    }
    $settings['styles'][] = array('css' => ajnanda_get_color_scheme_css($slug));
    return $settings;
}
add_filter('block_editor_settings_all', 'ajnanda_add_color_scheme_to_iframed_editor');
