<?php
/**
 * Accessibility toolbar — a small front-end widget offering per-visitor display
 * adjustments (text size, greyscale, inverted colours, link emphasis, a readable
 * font). Choices persist in the visitor's browser only (localStorage); nothing
 * is stored server-side.
 *
 * Enabled by default; toggle it in Customizer > Accessibility. Ships as its own
 * file so it stays easy to lift out.
 *
 * @package NCLLC_Pro
 */

defined('ABSPATH') || exit;

/**
 * Is the toolbar switched on for this site?
 */
function ajnanda_a11y_toolbar_enabled() {
    return (bool) apply_filters('ajnanda_a11y_toolbar_enabled', get_theme_mod('ajnanda_a11y_toolbar_enabled', true));
}

/**
 * Customizer: a single on/off toggle plus a corner choice.
 */
add_action('customize_register', 'ajnanda_a11y_toolbar_customize_register');
function ajnanda_a11y_toolbar_customize_register($wp_customize) {
    $wp_customize->add_section('ajnanda_accessibility', array(
        'title'    => __('Accessibility', 'ajnanda'),
        'priority' => 125,
    ));

    $wp_customize->add_setting('ajnanda_a11y_toolbar_enabled', array(
        'default'           => true,
        'sanitize_callback' => 'ajnanda_sanitize_checkbox',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajnanda_a11y_toolbar_enabled', array(
        'label'       => __('Show the accessibility toolbar', 'ajnanda'),
        'description' => __('A floating button visitors can use to enlarge text, switch to greyscale or inverted colours, emphasise links, or pick a more readable font. Their choices are remembered in their own browser.', 'ajnanda'),
        'section'     => 'ajnanda_accessibility',
        'type'        => 'checkbox',
    ));

    $wp_customize->add_setting('ajnanda_a11y_toolbar_position', array(
        'default'           => 'right',
        'sanitize_callback' => 'ajnanda_a11y_sanitize_position',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajnanda_a11y_toolbar_position', array(
        'label'   => __('Toolbar side', 'ajnanda'),
        'section' => 'ajnanda_accessibility',
        'type'    => 'radio',
        'choices' => array(
            'right' => __('Right', 'ajnanda'),
            'left'  => __('Left', 'ajnanda'),
        ),
    ));
}

function ajnanda_a11y_sanitize_position($value) {
    return in_array($value, array('left', 'right'), true) ? $value : 'right';
}

/**
 * Front-end assets — only loaded when the toolbar is on.
 */
add_action('wp_enqueue_scripts', 'ajnanda_a11y_toolbar_assets');
function ajnanda_a11y_toolbar_assets() {
    if (! ajnanda_a11y_toolbar_enabled()) {
        return;
    }

    wp_enqueue_style(
        'ajnanda-a11y-toolbar',
        get_template_directory_uri() . '/css/accessibility-toolbar.css',
        array(),
        ajnanda_asset_version('css/accessibility-toolbar.css')
    );

    wp_enqueue_script(
        'ajnanda-a11y-toolbar',
        get_template_directory_uri() . '/js/accessibility-toolbar.js',
        array(),
        ajnanda_asset_version('js/accessibility-toolbar.js'),
        true
    );

    wp_localize_script('ajnanda-a11y-toolbar', 'ajnandaA11y', array(
        'position' => get_theme_mod('ajnanda_a11y_toolbar_position', 'right') === 'left' ? 'left' : 'right',
        'i18n'     => array(
            'open'      => __('Accessibility tools', 'ajnanda'),
            'close'     => __('Close accessibility tools', 'ajnanda'),
            'heading'   => __('Accessibility Tools', 'ajnanda'),
            'increase'  => __('Increase Text', 'ajnanda'),
            'decrease'  => __('Decrease Text', 'ajnanda'),
            'grayscale' => __('Grayscale', 'ajnanda'),
            'invert'    => __('Invert Colors', 'ajnanda'),
            'underline' => __('Underline Links', 'ajnanda'),
            'highlight' => __('Highlight Links', 'ajnanda'),
            'readable'  => __('Readable Font', 'ajnanda'),
            'reset'     => __('Reset', 'ajnanda'),
        ),
    ));
}

/**
 * Toolbar markup. Rendered late in the footer so it's the last thing in <body>;
 * the widget is built by JS from these hooks staying minimal keeps the no-JS
 * footprint to a single hidden container.
 */
add_action('wp_footer', 'ajnanda_a11y_toolbar_markup', 100);
function ajnanda_a11y_toolbar_markup() {
    if (! ajnanda_a11y_toolbar_enabled()) {
        return;
    }
    // The script enhances this container; with no JS it stays display:none via CSS.
    echo '<div id="ajn-a11y" class="ajn-a11y" data-ajn-a11y hidden></div>';
}
