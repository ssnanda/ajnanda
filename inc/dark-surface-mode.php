<?php
/**
 * AJNanda Dark Surface Mode.
 *
 * The existing Color Scheme system (inc/color-schemes.php) only ever
 * touches 4 brand tokens (--primary/--primary-dark/--secondary/--accent) —
 * even the "Dark" preset just makes buttons/hero gradients dark, while
 * page backgrounds, cards, and borders stay on the light --white/--gray-*
 * ramp (theme.json + style.css :root). There was no way to get a genuinely
 * dark site (dark canvas, dark cards, light text) without hand-editing
 * CSS.
 *
 * This file adds exactly that, as a second, independent toggle: a
 * boolean Customizer setting that — when on — redefines the *neutral*
 * ramp (--white, --gray-50…--gray-900) to a mirrored-lightness dark
 * ramp. It intentionally does NOT touch --primary/--secondary/--accent;
 * those stay whatever Color Scheme is active, so any brand color scheme
 * can be combined with a light or dark surface. Nothing else in the
 * theme needs to change: --white/--gray-* were already the tokens
 * site.css's cards, sections, and text colors are built from (see the
 * "Token-based" comments on body/.builder-section/.feature-card/
 * .builder-card in style.css, and .is-style-ajnanda-card-* which were
 * already fully token-based) — this file only ever prints one more
 * `:root{...}` override, the same mechanism color-schemes.php already
 * uses for brand colors.
 *
 * Mirrored-lightness mapping (why these 9 values specifically): every
 * pair of tokens that reads as "light bg + dark text" or "dark accent +
 * light text" in the default ramp keeps that same *relationship* once
 * inverted — e.g. .builder-section-soft (bg: --gray-50) sits slightly
 * darker than .builder-section (bg: --white) in light mode; in dark mode
 * --gray-50 is mapped darker than --white too, so that same subtle
 * section-alternation rhythm survives untouched. The one deliberately
 * fixed-dark modifier this theme already had (.section-tone-dark /
 * .hero-tone-dark, style.css) reads --gray-900 background + --white/
 * --gray-300 text either way — with the ramp inverted it renders as a
 * bright "highlight" panel against an otherwise-dark site instead of
 * a dark panel against a light site, which is the correct emergent
 * behavior, not a bug: it stays the highest-contrast section on the
 * page in both modes.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The dark-mode mirror of style.css's default --white/--gray-* ramp.
 * Keys match the CSS custom property names exactly (minus the `--`).
 *
 * @return array<string,string>
 */
function ajnanda_get_dark_surface_ramp() {
    return array(
        'white'    => '#1a1d24', // was #ffffff — card/element surfaces, one step lighter than the page canvas below.
        'gray-50'  => '#101216', // was #f9fafb — page canvas / .builder-section-soft, the darkest surface.
        'gray-100' => '#23262d', // was #f3f4f6 — subtle borders/hover backgrounds.
        'gray-200' => '#2d323b', // was #e5e7eb — card borders.
        'gray-300' => '#454b57', // was #d1d5db — light borders / dim text-on-dark.
        'gray-600' => '#9aa1ac', // was #4b5563 — muted secondary text.
        'gray-700' => '#c3c9d1', // was #374151 — secondary text.
        'gray-800' => '#e4e7eb', // was #1f2937 — default body text.
        'gray-900' => '#f7f8fa', // was #111827 — headings, highest-contrast text.
    );
}

/**
 * @return bool
 */
function ajnanda_is_dark_surface_mode_active() {
    return (bool) get_theme_mod('ajnanda_dark_surface_mode', false);
}

/**
 * @return string A `:root{...}` CSS custom-property override, or '' when
 *                dark surface mode is off (nothing to print).
 */
function ajnanda_get_dark_surface_css() {
    if (!ajnanda_is_dark_surface_mode_active()) {
        return '';
    }

    $props = '';
    foreach (ajnanda_get_dark_surface_ramp() as $token => $value) {
        $props .= '--' . $token . ':' . esc_attr($value) . ';';
    }

    return ':root{' . $props . '}';
}

/**
 * Prints after ajnanda_customizer_css() (which also hooks wp_head at the
 * default priority) — later source order wins for same-specificity :root
 * rules, and in any case these are different custom properties (surface
 * neutrals here vs. brand colors there), so the two never actually
 * conflict; the ordering just keeps this file visually "layered after"
 * the brand-color output for anyone reading View Source.
 */
function ajnanda_output_dark_surface_css() {
    $css = ajnanda_get_dark_surface_css();
    if ($css === '') {
        return;
    }
    echo '<style id="ajnanda-dark-surface-mode">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- ajnanda_get_dark_surface_css() already escapes each value.
}
add_action('wp_head', 'ajnanda_output_dark_surface_css', 11);

/* -----------------------------------------------------------------------
 * Editor gap: same reasoning as color-schemes.php's identically-named
 * problem — ajnanda_output_dark_surface_css() only hooks wp_head, so the
 * iframed block editor canvas and the "Choose a pattern" modal's preview
 * thumbnails would otherwise never see it.
 * ------------------------------------------------------------------- */

function ajnanda_enqueue_dark_surface_editor_css() {
    $css = ajnanda_get_dark_surface_css();
    if ($css === '' || !wp_style_is('ajnanda-pro-editor-style', 'registered')) {
        return;
    }
    wp_add_inline_style('ajnanda-pro-editor-style', $css);
}
add_action('enqueue_block_editor_assets', 'ajnanda_enqueue_dark_surface_editor_css', 21);

function ajnanda_add_dark_surface_to_iframed_editor($settings) {
    $css = ajnanda_get_dark_surface_css();
    if ($css === '') {
        return $settings;
    }
    if (!isset($settings['styles']) || !is_array($settings['styles'])) {
        $settings['styles'] = array();
    }
    $settings['styles'][] = array('css' => $css);
    return $settings;
}
add_filter('block_editor_settings_all', 'ajnanda_add_dark_surface_to_iframed_editor');

/* -----------------------------------------------------------------------
 * Customizer control: one checkbox in the native Colors panel, right
 * under the Quick Kits / Quick presets swatches and above the 4 real
 * color pickers. Reuses functions.php's existing ajnanda_sanitize_checkbox()
 * and the native 'checkbox' control type — same pattern the theme already
 * uses for its other boolean settings (post-meta show/hide toggles etc.),
 * so no new custom control class is needed here.
 * ------------------------------------------------------------------- */

function ajnanda_register_dark_surface_control($wp_customize) {
    $wp_customize->add_setting('ajnanda_dark_surface_mode', array(
        'default'           => false,
        'sanitize_callback' => 'ajnanda_sanitize_checkbox',
    ));

    $wp_customize->add_control('ajnanda_dark_surface_mode', array(
        'label'       => __('Dark Mode (site-wide)', 'ajnanda'),
        'description' => __('Inverts backgrounds, cards, borders, and text everywhere — a genuinely dark UI, not just dark accent colors. Your Primary/Secondary/Accent colors below stay exactly as set.', 'ajnanda'),
        'section'     => 'colors',
        'type'        => 'checkbox',
        'priority'    => 1, // just under Quick Kits (-10) / Quick presets (0), above the 4 real color pickers.
    ));
}
add_action('customize_register', 'ajnanda_register_dark_surface_control');
