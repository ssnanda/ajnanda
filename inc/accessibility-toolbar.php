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
 * Where the floating button can sit: the four screen corners, plus three
 * edge-centred spots (bottom centre, and the middle of the left / right edge).
 */
function ajnanda_a11y_toolbar_positions() {
    return array(
        'top-left'      => __('Top left', 'ajnanda'),
        'top-right'     => __('Top right', 'ajnanda'),
        'bottom-left'   => __('Bottom left', 'ajnanda'),
        'bottom-right'  => __('Bottom right', 'ajnanda'),
        'bottom-center' => __('Bottom middle', 'ajnanda'),
        'left-middle'   => __('Left middle', 'ajnanda'),
        'right-middle'  => __('Right middle', 'ajnanda'),
    );
}

/**
 * Back-compat alias from when only the four corners existed.
 *
 * @deprecated Use ajnanda_a11y_toolbar_positions().
 */
function ajnanda_a11y_toolbar_corners() {
    return ajnanda_a11y_toolbar_positions();
}

/**
 * Resolved position for the given context ('desktop' or 'mobile').
 * Old left/right values are migrated to a top corner.
 */
function ajnanda_a11y_toolbar_position($context = 'desktop') {
    $mod     = 'mobile' === $context ? 'ajnanda_a11y_toolbar_position_mobile' : 'ajnanda_a11y_toolbar_position';
    $default = 'mobile' === $context ? 'bottom-right' : 'top-right';
    $value   = get_theme_mod($mod, $default);
    // Migrate the old left/right values.
    if ('left' === $value)  { $value = 'top-left'; }
    if ('right' === $value) { $value = 'top-right'; }
    return array_key_exists($value, ajnanda_a11y_toolbar_positions()) ? $value : $default;
}

/**
 * Tools => theme-mod name. Order here is the order they appear in the panel.
 */
function ajnanda_a11y_toolbar_tool_map() {
    return array(
        'textSize'       => 'ajnanda_a11y_tool_text_size',
        'grayscale'      => 'ajnanda_a11y_tool_grayscale',
        'invert'         => 'ajnanda_a11y_tool_invert',
        'underlineLinks' => 'ajnanda_a11y_tool_underline_links',
        'highlightLinks' => 'ajnanda_a11y_tool_highlight_links',
        'readableFont'   => 'ajnanda_a11y_tool_readable_font',
    );
}

/**
 * The tool keys the site owner has left switched on. "Reset" is always present
 * and is not in this list.
 *
 * @return string[]
 */
function ajnanda_a11y_toolbar_tools() {
    $enabled = array();
    foreach (ajnanda_a11y_toolbar_tool_map() as $key => $mod) {
        if (get_theme_mod($mod, true)) {
            $enabled[] = $key;
        }
    }
    return array_values((array) apply_filters('ajnanda_a11y_toolbar_tools', $enabled));
}

/**
 * Customizer: on/off, a position for desktop, a separate position for phones,
 * and a checkbox per tool.
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

    $positions = ajnanda_a11y_toolbar_positions();

    $wp_customize->add_setting('ajnanda_a11y_toolbar_position', array(
        'default'           => 'top-right',
        'sanitize_callback' => 'ajnanda_a11y_sanitize_position',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajnanda_a11y_toolbar_position', array(
        'label'   => __('Position on desktop / tablet', 'ajnanda'),
        'section' => 'ajnanda_accessibility',
        'type'    => 'select',
        'choices' => $positions,
    ));

    $wp_customize->add_setting('ajnanda_a11y_toolbar_position_mobile', array(
        'default'           => 'bottom-right',
        'sanitize_callback' => 'ajnanda_a11y_sanitize_position',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajnanda_a11y_toolbar_position_mobile', array(
        'label'       => __('Position on phones', 'ajnanda'),
        'description' => __('Set independently from the desktop position — e.g. right middle on desktop, bottom middle on phones.', 'ajnanda'),
        'section'     => 'ajnanda_accessibility',
        'type'        => 'select',
        'choices'     => $positions,
    ));

    $tool_labels = array(
        'textSize'       => __('Increase / Decrease Text', 'ajnanda'),
        'grayscale'      => __('Grayscale', 'ajnanda'),
        'invert'         => __('Invert Colors', 'ajnanda'),
        'underlineLinks' => __('Underline Links', 'ajnanda'),
        'highlightLinks' => __('Highlight Links', 'ajnanda'),
        'readableFont'   => __('Readable Font', 'ajnanda'),
    );
    $first = true;
    foreach (ajnanda_a11y_toolbar_tool_map() as $key => $mod) {
        $wp_customize->add_setting($mod, array(
            'default'           => true,
            'sanitize_callback' => 'ajnanda_sanitize_checkbox',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($mod, array(
            'label'       => $tool_labels[$key],
            'description' => $first ? __('Choose which tools appear in the panel. Reset is always shown.', 'ajnanda') : '',
            'section'     => 'ajnanda_accessibility',
            'type'        => 'checkbox',
        ));
        $first = false;
    }
}

function ajnanda_a11y_sanitize_position($value) {
    if ('left' === $value)  { return 'top-left'; }
    if ('right' === $value) { return 'top-right'; }
    return array_key_exists($value, ajnanda_a11y_toolbar_positions()) ? $value : 'top-right';
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
        'position'       => ajnanda_a11y_toolbar_position('desktop'),
        'positionMobile' => ajnanda_a11y_toolbar_position('mobile'),
        'tools'          => ajnanda_a11y_toolbar_tools(),
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
