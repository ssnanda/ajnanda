<?php
/**
 * NCLLC Pro Theme Functions
 * 
 * @package NCLLC_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function ajnanda_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    add_theme_support('custom-background');
    add_theme_support('customize-selective-refresh-widgets');
    
    // Register navigation menus — Primary and Footer always on.
    $menus = array(
        'primary' => __('Primary Menu', 'ajnanda'),
        'footer'  => __('Footer Menu', 'ajnanda'),
    );

    $panel_settings = function_exists('ajnanda_get_menu_toggles') ? ajnanda_get_menu_toggles() : array();
    if (!empty($panel_settings['left_panel_enabled'])) {
        $menus['office_shortcuts'] = !empty($panel_settings['left_panel_label'])
            ? $panel_settings['left_panel_label']
            : __('Left Floater Panel', 'ajnanda');
    }
    if (!empty($panel_settings['right_panel_enabled'])) {
        $menus['store_shortcuts'] = !empty($panel_settings['right_panel_label'])
            ? $panel_settings['right_panel_label']
            : __('Right Floater Panel', 'ajnanda');
    }

    register_nav_menus($menus);
}
add_action('after_setup_theme', 'ajnanda_setup');

// One-time migration: copy ncllc_ theme mods to ajnanda_ keys
add_action('after_setup_theme', function (): void {
    $migrate = [
        'ncllc_left_panel_enabled'  => 'ajnanda_left_panel_enabled',
        'ncllc_left_panel_label'    => 'ajnanda_left_panel_label',
        'ncllc_right_panel_enabled' => 'ajnanda_right_panel_enabled',
        'ncllc_right_panel_label'   => 'ajnanda_right_panel_label',
    ];
    foreach ($migrate as $old => $new) {
        if (get_theme_mod($new, '__unset__') === '__unset__') {
            $old_val = get_theme_mod($old, '__unset__');
            if ($old_val !== '__unset__') {
                set_theme_mod($new, $old_val);
            }
        }
    }
}, 5);

add_action('after_setup_theme', function (): void {
    $locations = get_theme_mod('nav_menu_locations', []);
    if (!is_array($locations)) {
        return;
    }

    $changed = false;
    $legacy_locations = [
        'upos_office_shortcuts' => 'office_shortcuts',
        'upos_store_shortcuts'  => 'store_shortcuts',
    ];

    foreach ($legacy_locations as $legacy => $current) {
        if (empty($locations[$current]) && !empty($locations[$legacy])) {
            $locations[$current] = $locations[$legacy];
            $changed = true;
        }
    }

    if ($changed) {
        set_theme_mod('nav_menu_locations', $locations);
    }
}, 11);

/**
 * Enqueue scripts and styles
 */
function ajnanda_asset_version($relative_path) {
    $relative_path = ltrim((string) $relative_path, '/');
    $path = 'style.css' === $relative_path
        ? get_stylesheet_directory() . '/style.css'
        : get_theme_file_path($relative_path);

    return file_exists($path) ? (string) filemtime($path) : wp_get_theme()->get('Version');
}

function ajnanda_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('ajnanda-pro-style', get_stylesheet_uri(), array(), ajnanda_asset_version('style.css'));

    // Enqueue custom JavaScript
    wp_enqueue_script('ajnanda-pro-script', get_template_directory_uri() . '/js/main.js', array('jquery'), ajnanda_asset_version('js/main.js'), true);

    // Localize script
    wp_localize_script('ajnanda-pro-script', 'ajnandaData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajnanda-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'ajnanda_scripts');


/**
 * Load the same page-section styling inside the block editor.
 */
function ajnanda_block_editor_assets() {
    // Editor Google Fonts (all registered pairings, not just the active
    // one — so a manual per-block font choice always renders) are enqueued
    // by inc/font-pairings.php's own enqueue_block_editor_assets hook,
    // not here — one place responsible for font loading instead of two.
    wp_enqueue_style('ajnanda-pro-editor-style', get_stylesheet_uri(), array(), ajnanda_asset_version('style.css'));
    wp_enqueue_script(
        'ajnanda-pro-editor-controls',
        get_template_directory_uri() . '/js/editor-controls.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks'),
        ajnanda_asset_version('js/editor-controls.js'),
        true
    );
}
add_action('enqueue_block_editor_assets', 'ajnanda_block_editor_assets');

/**
 * Register widget areas
 */
function ajnanda_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar', 'ajnanda'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here.', 'ajnanda'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    for ($i = 1; $i <= 4; $i++) {
        register_sidebar(array(
            'name'          => sprintf(__('Header Builder Widget %d', 'ajnanda'), $i),
            'id'            => 'header-builder-' . $i,
            'description'   => __('Use this widget area inside AJNanda header builder slots.', 'ajnanda'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));

        register_sidebar(array(
            'name'          => sprintf(__('Footer Builder Widget %d', 'ajnanda'), $i),
            'id'            => 'footer-builder-' . $i,
            'description'   => __('Use this widget area inside AJNanda footer builder slots.', 'ajnanda'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));
    }
}
add_action('widgets_init', 'ajnanda_widgets_init');

/**
 * Keep builder widget areas available while editing the Customizer.
 */
function ajnanda_keep_builder_widget_sections_active($active, $section) {
    if (empty($section->id)) {
        return $active;
    }

    if (0 === strpos($section->id, 'sidebar-widgets-header-builder-') || 0 === strpos($section->id, 'sidebar-widgets-footer-builder-')) {
        return true;
    }

    return $active;
}
add_filter('customize_section_active', 'ajnanda_keep_builder_widget_sections_active', 10, 2);

/**
 * Custom excerpt length
 */
function ajnanda_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'ajnanda_excerpt_length', 999);

/**
 * Custom excerpt more
 */
function ajnanda_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'ajnanda_excerpt_more');

/**
 * Convert saved Spectra markup to native WordPress block markup when Spectra is inactive.
 */
function ajnanda_convert_spectra_markup_to_core($content) {
    if (false === strpos($content, 'wp-block-uagb-') || ajnanda_is_spectra_active()) {
        return $content;
    }

    if (!class_exists('DOMDocument')) {
        return ajnanda_convert_spectra_markup_to_core_basic($content);
    }

    $previous_errors = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $charset = get_bloginfo('charset') ? get_bloginfo('charset') : 'UTF-8';
    $encoded_content = function_exists('mb_convert_encoding') ? mb_convert_encoding($content, 'HTML-ENTITIES', $charset) : $content;
    $wrapped = '<!DOCTYPE html><html><body>' . $encoded_content . '</body></html>';

    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-buttons ")]') as $node) {
        ajnanda_replace_spectra_buttons_node($dom, $xpath, $node);
    }

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-marketing-button ")]') as $node) {
        ajnanda_replace_spectra_marketing_button_node($dom, $xpath, $node);
    }

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-advanced-heading ")]') as $node) {
        ajnanda_unwrap_spectra_node($dom, $node);
    }

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-image ")]') as $node) {
        ajnanda_replace_spectra_image_node($dom, $xpath, $node);
    }

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-container ")]') as $node) {
        ajnanda_replace_spectra_container_node($dom, $node);
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $output = '';

    if ($body) {
        foreach ($body->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous_errors);

    return $output ? $output : $content;
}
add_filter('the_content', 'ajnanda_convert_spectra_markup_to_core', 8);

function ajnanda_is_spectra_active() {
    return defined('UAGB_VER') || defined('UAGB_FILE') || class_exists('UAGB_Loader') || class_exists('UAGB_Init');
}

function ajnanda_replace_spectra_buttons_node($dom, $xpath, $node) {
    $buttons = $dom->createElement('div');
    $buttons->setAttribute('class', 'wp-block-buttons is-content-justification-center is-layout-flex');

    foreach ($xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " wp-block-button__link ")]', $node) as $link) {
        $button = $dom->createElement('div');
        $button->setAttribute('class', 'wp-block-button');
        $button->appendChild(ajnanda_clone_anchor_as_core_button($dom, $xpath, $link, './/*[contains(concat(" ", normalize-space(@class), " "), " uagb-button__link ")]'));
        $buttons->appendChild($button);
    }

    $node->parentNode->replaceChild($buttons, $node);
}

function ajnanda_replace_spectra_marketing_button_node($dom, $xpath, $node) {
    $buttons = $dom->createElement('div');
    $buttons->setAttribute('class', 'wp-block-buttons is-content-justification-center is-layout-flex');
    $link = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " wp-block-button__link ")]', $node)->item(0);

    if ($link) {
        $button = $dom->createElement('div');
        $button->setAttribute('class', 'wp-block-button');
        $button->appendChild(ajnanda_clone_anchor_as_core_button($dom, $xpath, $link, './/*[contains(concat(" ", normalize-space(@class), " "), " uagb-marketing-btn__title ")]'));
        $buttons->appendChild($button);
    }

    $node->parentNode->replaceChild($buttons, $node);
}

function ajnanda_clone_anchor_as_core_button($dom, $xpath, $link, $label_selector) {
    $new_link = $dom->createElement('a');
    $label = trim($link->textContent);
    $label_node = $xpath->query($label_selector, $link)->item(0);

    if ($label_node) {
        $label = trim($label_node->textContent);
    }

    foreach (array('href', 'target', 'rel', 'aria-label') as $attribute) {
        if ($link->hasAttribute($attribute)) {
            $new_link->setAttribute($attribute, $link->getAttribute($attribute));
        }
    }

    $new_link->setAttribute('class', 'wp-block-button__link wp-element-button');
    $new_link->appendChild($dom->createTextNode($label));

    return $new_link;
}

function ajnanda_unwrap_spectra_node($dom, $node) {
    $fragment = $dom->createDocumentFragment();

    while ($node->firstChild) {
        $fragment->appendChild($node->firstChild);
    }

    $node->parentNode->replaceChild($fragment, $node);
}

function ajnanda_replace_spectra_image_node($dom, $xpath, $node) {
    $figure = $xpath->query('.//figure', $node)->item(0);

    if (!$figure) {
        ajnanda_unwrap_spectra_node($dom, $node);
        return;
    }

    $new_figure = $figure->cloneNode(true);
    $new_figure->setAttribute('class', 'wp-block-image');
    $node->parentNode->replaceChild($new_figure, $node);
}

function ajnanda_replace_spectra_container_node($dom, $node) {
    $classes = $node->hasAttribute('class') ? $node->getAttribute('class') : '';
    $group = $dom->createElement('div');
    $group_classes = array('wp-block-group');

    if (false !== strpos($classes, 'alignfull')) {
        $group_classes[] = 'alignfull';
    } elseif (false !== strpos($classes, 'alignwide')) {
        $group_classes[] = 'alignwide';
    }

    $group->setAttribute('class', implode(' ', $group_classes));

    while ($node->firstChild) {
        if ($node->firstChild instanceof DOMElement && false !== strpos(' ' . $node->firstChild->getAttribute('class') . ' ', ' uagb-container-inner-blocks-wrap ')) {
            while ($node->firstChild->firstChild) {
                $group->appendChild($node->firstChild->firstChild);
            }
            $node->removeChild($node->firstChild);
            continue;
        }

        $group->appendChild($node->firstChild);
    }

    $node->parentNode->replaceChild($group, $node);
}

function ajnanda_convert_spectra_markup_to_core_basic($content) {
    $content = preg_replace('/wp-block-uagb-buttons[^\"]*/', 'wp-block-buttons is-content-justification-center is-layout-flex', $content);
    $content = preg_replace('/wp-block-uagb-buttons-child[^\"]*/', 'wp-block-button', $content);
    $content = preg_replace('/uagb-buttons-repeater wp-block-button__link/', 'wp-block-button__link wp-element-button', $content);
    $content = preg_replace('/wp-block-uagb-container[^\"]*/', 'wp-block-group', $content);
    $content = preg_replace('/wp-block-uagb-image[^\"]*/', 'wp-block-image', $content);

    return $content;
}

/**
 * Add body classes
 */
function ajnanda_body_classes($classes) {
    if (!is_singular()) {
        $classes[] = 'hfeed';
    }
    
    if (is_front_page()) {
        $classes[] = 'ajnanda-home';
    }
    
    return $classes;
}
add_filter('body_class', 'ajnanda_body_classes');

/**
 * Sanitize multiline Customizer text while preserving simple line breaks.
 */
function ajnanda_sanitize_textarea($value) {
    return implode("\n", array_map('sanitize_text_field', explode("\n", $value)));
}

/**
 * Sanitize responsive logo height values.
 */
function ajnanda_sanitize_logo_height($value) {
    $value = absint($value);

    return min(100, max(20, $value));
}

/**
 * Sanitize responsive header padding values.
 */
function ajnanda_sanitize_header_padding($value) {
    $value = (float) $value;

    return (string) min(2, max(0.5, $value));
}

/**
 * Sanitize Customizer select values.
 */
function ajnanda_sanitize_choice($value, $setting) {
    $control = $setting->manager->get_control($setting->id);
    $choices = $control && isset($control->choices) ? $control->choices : array();

    return array_key_exists($value, $choices) ? $value : $setting->default;
}

function ajnanda_sanitize_builder_width($value) {
    $value = absint($value);

    return min(6, max(1, $value));
}

function ajnanda_sanitize_builder_count($value) {
    $value = absint($value);

    return min(4, max(1, $value));
}

function ajnanda_sanitize_builder_row_count($value) {
    $value = absint($value);

    return min(6, max(1, $value));
}

/**
 * Sanitize CSS size values used by Customizer controls.
 */
function ajnanda_sanitize_css_size($value) {
    $value = trim((string) $value);

    if ('' === $value) {
        return '';
    }

    if (preg_match('/^\d+(\.\d+)?$/', $value)) {
        return $value . 'px';
    }

    if (preg_match('/^\d+(\.\d+)?(px|rem|em|vh|vw|%)$/', $value)) {
        return $value;
    }

    return '';
}

function ajnanda_sanitize_css_size_or_auto($value) {
    $value = trim((string) $value);

    if ('auto' === strtolower($value)) {
        return 'auto';
    }

    return ajnanda_sanitize_css_size($value);
}

/**
 * Sanitize CSS color values used by Customizer controls.
 */
function ajnanda_sanitize_css_color($value) {
    $value = trim((string) $value);

    if ('' === $value) {
        return '';
    }

    $hex = sanitize_hex_color($value);
    if ($hex) {
        return $hex;
    }

    if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(\s*,\s*(0|1|0?\.\d+))?\s*\)$/', $value)) {
        return $value;
    }

    return '';
}

/**
 * Sanitize CSS backgrounds used by Header/Footer controls.
 */
function ajnanda_sanitize_css_background($value) {
    $value = trim((string) $value);

    if ('' === $value) {
        return '';
    }

    $color = ajnanda_sanitize_css_color($value);
    if ($color) {
        return $color;
    }

    if (preg_match('/^(linear|radial)-gradient\([#%.,\s0-9a-zA-Z-]+\)$/', $value) && false === stripos($value, 'url') && false === stripos($value, 'expression')) {
        return $value;
    }

    return '';
}

function ajnanda_sanitize_font_family($value) {
    $allowed = array('inherit', 'Inter', 'Poppins', 'Arial', 'Georgia', 'system-ui');

    return in_array($value, $allowed, true) ? $value : 'inherit';
}

function ajnanda_sanitize_font_weight($value) {
    $allowed = array('400', '500', '600', '700', '800');

    return in_array((string) $value, $allowed, true) ? (string) $value : '500';
}

function ajnanda_sanitize_header_font_preset($value) {
    $allowed = array('normal', 'bold', 'italic', 'bold-italic', 'underline', 'bold-underline');

    return in_array($value, $allowed, true) ? $value : 'normal';
}

function ajnanda_sanitize_footer_font_style($value) {
    return 'italic' === $value ? 'italic' : 'normal';
}

function ajnanda_sanitize_footer_text_decoration($value) {
    return 'underline' === $value ? 'underline' : 'none';
}

function ajnanda_sanitize_checkbox($value) {
    return (bool) $value;
}

function ajnanda_sanitize_opacity($value) {
    $value = (float) $value;

    return (string) min(1, max(0, $value));
}


/**
 * Keep the Builder Canvas template visible in the page editor template picker.
 */
function ajnanda_register_page_templates($templates) {
    $templates['page-builder.php'] = __('Builder Canvas', 'ajnanda');

    return $templates;
}
add_filter('theme_page_templates', 'ajnanda_register_page_templates');

/**
 * Load the Builder Canvas file when it is selected for a page.
 */
function ajnanda_load_page_template($template) {
    if (is_page() && 'page-builder.php' === get_page_template_slug()) {
        $builder_template = locate_template('page-builder.php');

        if ($builder_template) {
            return $builder_template;
        }
    }

    return $template;
}
add_filter('template_include', 'ajnanda_load_page_template');

/**
 * Split an editable leading hero from the rest of a page's block content.
 */
function ajnanda_split_leading_builder_hero($content) {
    $result = array(
        'hero' => '',
        'rest' => $content,
    );

    if (!function_exists('parse_blocks')) {
        return $result;
    }

    $blocks = parse_blocks($content);
    if (empty($blocks)) {
        return $result;
    }

    $first_block = $blocks[0];
    $class_name = isset($first_block['attrs']['className']) ? $first_block['attrs']['className'] : '';

    if (false === strpos($class_name, 'builder-hero-section')) {
        return $result;
    }

    $hero_block = array_shift($blocks);

    $result['hero'] = render_block($hero_block);
    $result['rest'] = serialize_blocks($blocks);

    return $result;
}

/**
 * Check whether page content begins with a builder hero, optionally preceded by
 * editor-visible spacer blocks that control the above-hero gap per page.
 */
function ajnanda_has_leading_builder_hero_content($content) {
    if (!function_exists('parse_blocks')) {
        return false;
    }

    $blocks = parse_blocks($content);
    if (empty($blocks)) {
        return false;
    }

    foreach ($blocks as $block) {
        $block_name = isset($block['blockName']) ? (string) $block['blockName'] : '';
        $inner_html = isset($block['innerHTML']) ? trim((string) $block['innerHTML']) : '';

        if ('' === $block_name && '' === $inner_html) {
            continue;
        }

        if ('core/spacer' === $block_name) {
            continue;
        }

        $class_name = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';

        return false !== strpos($class_name, 'builder-hero-section')
            || false !== strpos($inner_html, 'builder-hero-section');
    }

    return false;
}

/**
 * Check whether a saved block layout attribute has a meaningful value.
 */
function ajnanda_has_block_layout_value($block, $keys) {
    if (empty($block['attrs']) || !is_array($block['attrs'])) {
        return false;
    }

    foreach ($keys as $key) {
        if (isset($block['attrs'][$key]) && '' !== $block['attrs'][$key] && false !== $block['attrs'][$key]) {
            return true;
        }
    }

    return false;
}

/**
 * Normalize size strings so old saved values like "450" and "450px" compare
 * the same way when detecting legacy defaults.
 */
function ajnanda_normalize_css_size_value($value) {
    $value = strtolower(trim((string) $value));

    if ('' === $value) {
        return '';
    }

    if (preg_match('/^\d+(?:\.\d+)?$/', $value)) {
        return $value . 'px';
    }

    return preg_replace('/\s+/', '', $value);
}

/**
 * Check whether a value is one of the old hard-coded hero defaults.
 */
function ajnanda_is_legacy_css_size_value($value, $legacy_defaults = array()) {
    $normalized_value = ajnanda_normalize_css_size_value($value);

    foreach ($legacy_defaults as $legacy_default) {
        if ($normalized_value === ajnanda_normalize_css_size_value($legacy_default)) {
            return true;
        }
    }

    return false;
}

/**
 * Old editor controls saved the former theme default height into some pages.
 * Treat those as theme defaults again so new compact Hero Defaults can win.
 */
function ajnanda_has_legacy_saved_hero_height($block) {
    if (empty($block['attrs']) || !is_array($block['attrs'])) {
        return false;
    }

    $attrs = $block['attrs'];
    $class_name = isset($attrs['className']) ? (string) $attrs['className'] : '';

    if (false === strpos($class_name, 'builder-hero-section')) {
        return false;
    }

    $legacy_height_keys = array(
        'ajnMinHeightDesktop',
        'ajnHeightDesktop',
    );

    $has_legacy_desktop_height = false;
    foreach ($legacy_height_keys as $key) {
        if (isset($attrs[$key]) && ajnanda_is_legacy_css_size_value($attrs[$key], array('450px'))) {
            $has_legacy_desktop_height = true;
            break;
        }
    }

    if (!$has_legacy_desktop_height) {
        return false;
    }

    $non_legacy_height_keys = array(
        'ajnMinHeightTablet',
        'ajnMinHeightMobile',
        'ajnHeightTablet',
        'ajnHeightMobile',
    );

    foreach ($non_legacy_height_keys as $key) {
        if (isset($attrs[$key]) && '' !== trim((string) $attrs[$key])) {
            return false;
        }
    }

    return true;
}

/**
 * Add semantic layout classes to rendered hero blocks from saved block attrs.
 *
 * Older pages may already have AJNanda inline layout variables without the newer
 * state classes, so this keeps front-end behavior consistent without requiring
 * every page/post to be opened and saved again.
 */
function ajnanda_add_hero_layout_state_classes($block_content, $block) {
    if ('' === $block_content || false === strpos($block_content, 'builder-hero-section')) {
        return $block_content;
    }

    $height_keys = array(
        'ajnMinHeightDesktop',
        'ajnMinHeightTablet',
        'ajnMinHeightMobile',
        'ajnHeightDesktop',
        'ajnHeightTablet',
        'ajnHeightMobile',
    );

    $padding_keys = array(
        'ajnPaddingTopDesktop',
        'ajnPaddingRightDesktop',
        'ajnPaddingBottomDesktop',
        'ajnPaddingLeftDesktop',
        'ajnPaddingTopTablet',
        'ajnPaddingRightTablet',
        'ajnPaddingBottomTablet',
        'ajnPaddingLeftTablet',
        'ajnPaddingTopMobile',
        'ajnPaddingRightMobile',
        'ajnPaddingBottomMobile',
        'ajnPaddingLeftMobile',
    );

    $classes = array();
    $has_legacy_saved_hero_height = ajnanda_has_legacy_saved_hero_height($block);

    if (!$has_legacy_saved_hero_height && ajnanda_has_block_layout_value($block, $height_keys)) {
        $classes[] = 'ajn-has-height-override';
    }

    if (ajnanda_has_block_layout_value($block, $padding_keys)) {
        $classes[] = 'ajn-has-padding-override';
    }

    if (empty($classes) && !$has_legacy_saved_hero_height) {
        return $block_content;
    }

    if (class_exists('WP_HTML_Tag_Processor')) {
        $processor = new WP_HTML_Tag_Processor($block_content);

        if ($processor->next_tag()) {
            $existing_classes = $processor->get_attribute('class');

            if (false === $existing_classes || false === strpos($existing_classes, 'builder-hero-section')) {
                return $block_content;
            }

            foreach ($classes as $class_name) {
                $processor->add_class($class_name);
            }

            if ($has_legacy_saved_hero_height) {
                $processor->remove_class('ajn-has-height-override');
                $processor->remove_class('ajn-responsive-height');
                $processor->remove_class('ajn-responsive-min-height');

                $style = (string) $processor->get_attribute('style');
                $style = preg_replace('/(?:^|;)\s*--ajn-min-height-desktop\s*:\s*(?:450px|450)\s*/i', '', $style);
                $style = preg_replace('/(?:^|;)\s*--ajn-height-desktop\s*:\s*(?:450px|450)\s*/i', '', $style);
                $style = trim(preg_replace('/;{2,}/', ';', $style), " \t\n\r\0\x0B;");

                if ('' === $style) {
                    $processor->remove_attribute('style');
                } else {
                    $processor->set_attribute('style', $style);
                }
            }

            return $processor->get_updated_html();
        }
    }

    $class_string = implode(' ', array_map('sanitize_html_class', $classes));

    return preg_replace('/(<[a-z0-9:-]+[^>]*class="[^"]*builder-hero-section[^"]*)(")/i', '$1 ' . esc_attr($class_string) . '$2', $block_content, 1);
}
add_filter('render_block', 'ajnanda_add_hero_layout_state_classes', 10, 2);

/**
 * Footer column defaults and saved Customizer values.
 */
function ajnanda_get_footer_defaults() {
    $site_name = get_bloginfo('name');
    $site_description = get_bloginfo('description');

    return array(
        1 => array(
            'title' => $site_name ? $site_name : __('Your Site Name', 'ajnanda'),
            'text'  => $site_description ? $site_description : __('Add a short description for this website in the Customizer footer settings.', 'ajnanda'),
        ),
        2 => array(
            'title' => __('Quick Links', 'ajnanda'),
            'text'  => "Home|/\nAbout|/about/\nContact|/contact/",
        ),
        3 => array(
            'title' => __('Resources', 'ajnanda'),
            'text'  => __('Add useful links, services, or resource names here.', 'ajnanda'),
        ),
        4 => array(
            'title' => __('Contact', 'ajnanda'),
            'text'  => __('Add location, hours, phone, email, or other contact details here.', 'ajnanda'),
        ),
    );
}

function ajnanda_get_footer_bottom_default() {
    $site_name = get_bloginfo('name');

    return sprintf(
        /* translators: %s: Site name. */
        __('%s. All rights reserved.', 'ajnanda'),
        $site_name ? $site_name : __('Your Site Name', 'ajnanda')
    );
}

function ajnanda_get_footer_columns() {
    $defaults = ajnanda_get_footer_defaults();

    $columns = array();
    foreach ($defaults as $index => $default) {
        $columns[$index] = array(
            'title' => get_theme_mod('footer_column_' . $index . '_title', $default['title']),
            'text'  => get_theme_mod('footer_column_' . $index . '_text', $default['text']),
        );
    }

    return $columns;
}

/**
 * Render footer textarea lines. Use "Label|URL" to make a line a link.
 */
function ajnanda_render_footer_lines($text) {
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)));

    if (empty($lines)) {
        return;
    }

    if (1 === count($lines) && false === strpos($lines[0], '|')) {
        echo '<p>' . esc_html($lines[0]) . '</p>';
        return;
    }

    echo '<ul>';
    foreach ($lines as $line) {
        if (false !== strpos($line, '|')) {
            list($label, $url) = array_map('trim', explode('|', $line, 2));
            echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
        } else {
            echo '<li>' . esc_html($line) . '</li>';
        }
    }
    echo '</ul>';
}

/**
 * Optional badge/mark + tagline shown next to/under the logo. Both are
 * opt-in (empty theme_mods by default), so sites that never touch these
 * settings render nothing extra here — output stays identical to before
 * these controls existed.
 */
function ajnanda_render_branding_extras() {
    $badge   = trim((string) get_theme_mod('ajn_branding_badge_text', ''));
    $tagline = get_theme_mod('ajn_branding_show_tagline', false) ? get_bloginfo('description') : '';

    if ('' === $badge && '' === trim((string) $tagline)) {
        return;
    }

    if ('' !== $badge) {
        $shape = get_theme_mod('ajn_branding_badge_shape', 'compact-pill');
        echo '<span class="site-badge site-badge-' . esc_attr($shape) . '">' . esc_html($badge) . '</span>';
    }

    if ('' !== trim((string) $tagline)) {
        echo '<span class="site-tagline">' . esc_html($tagline) . '</span>';
    }
}

function ajnanda_render_builder_site_identity() {
    $has_extras = '' !== trim((string) get_theme_mod('ajn_branding_badge_text', ''))
        || get_theme_mod('ajn_branding_show_tagline', false);

    if (!$has_extras) {
        if (has_custom_logo()) {
            the_custom_logo();
            return;
        }

        echo '<a href="' . esc_url(home_url('/')) . '" class="site-logo" rel="home">' . esc_html(get_bloginfo('name')) . '</a>';
        return;
    }

    echo '<span class="site-branding-group">';
    if (has_custom_logo()) {
        the_custom_logo();
    } else {
        echo '<a href="' . esc_url(home_url('/')) . '" class="site-logo" rel="home">' . esc_html(get_bloginfo('name')) . '</a>';
    }
    ajnanda_render_branding_extras();
    echo '</span>';
}

/**
 * Appends the opt-in nav-container-style and active-menu-item-style
 * classes to the Primary Menu's <ul> class list. Both default to "none",
 * so existing sites keep their current, unstyled <ul class="nav-menu">
 * output until a site owner picks a style in the Customizer.
 */
function ajnanda_get_primary_menu_class($base_class) {
    $classes = array($base_class);

    $container_style = get_theme_mod('ajn_nav_container_style', 'none');
    if ('none' !== $container_style) {
        $classes[] = 'ajn-nav-container-' . sanitize_html_class($container_style);
    }

    $active_style = get_theme_mod('ajn_active_menu_style', 'none');
    if ('none' !== $active_style) {
        $classes[] = 'ajn-active-style-' . sanitize_html_class($active_style);
    }

    return implode(' ', $classes);
}

function ajnanda_render_builder_menu($location, $class_name) {
    $menu_id = 'primary' === $location ? 'primary-menu' : 'footer-menu';

    wp_nav_menu(array(
        'theme_location' => $location,
        'menu_id'        => $menu_id,
        'menu_class'     => 'primary' === $location ? ajnanda_get_primary_menu_class($class_name) : $class_name,
        'container'      => false,
        'fallback_cb'    => false,
        'depth'          => 3,
    ));
}

function ajnanda_social_icon_svg($url) {
    $host = strtolower(wp_parse_url($url, PHP_URL_HOST) ?? '');
    if (str_contains($host, 'facebook')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
    }
    if (str_contains($host, 'instagram')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>';
    }
    if (str_contains($host, 'linkedin')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>';
    }
    if (str_contains($host, 'twitter') || str_contains($host, 'x.com')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
    }
    if (str_contains($host, 'youtube')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>';
}

/**
 * Maps the "Nav CTA style" Customizer choice to an additional CSS class.
 * 'solid' (the historical default) adds nothing, so existing sites keep
 * their current button look until they explicitly pick Outline or Pill.
 */
function ajnanda_get_cta_style_class($style) {
    if ('outline' === $style) {
        return ' ajn-cta-style-outline';
    }
    if ('pill' === $style) {
        return ' ajn-cta-style-pill';
    }

    return '';
}

/**
 * Shared "theme palette" choices for header-builder Customizer controls
 * (nav container, active menu item, nav CTA, branding badge, accent strip).
 * Mirrors the 7 colors already declared in theme.json/editor-color-palette
 * so every new color control stays limited to theme presets instead of a
 * free-form picker. $none_label lets a control phrase the empty option to
 * fit its own meaning (e.g. "Theme Default" vs "Same as text").
 */
function ajnanda_get_theme_palette_choices($include_none = true, $none_label = '') {
    $choices = array();

    if ($include_none) {
        $choices[''] = $none_label ? $none_label : __('— Use style default —', 'ajnanda');
    }

    $choices['primary-blue'] = __('Primary Blue', 'ajnanda');
    $choices['deep-blue']    = __('Deep Blue', 'ajnanda');
    $choices['purple']       = __('Purple', 'ajnanda');
    $choices['gold']         = __('Gold', 'ajnanda');
    $choices['ink']          = __('Ink', 'ajnanda');
    $choices['soft-gray']    = __('Soft Gray', 'ajnanda');
    $choices['white']        = __('White', 'ajnanda');

    return $choices;
}

function ajnanda_get_theme_palette_color($slug) {
    $map = array(
        'primary-blue' => '#2563eb',
        'deep-blue'    => '#1e40af',
        'purple'       => '#7c3aed',
        'gold'         => '#f59e0b',
        'ink'          => '#111827',
        'soft-gray'    => '#f3f4f6',
        'white'        => '#ffffff',
    );

    return isset($map[$slug]) ? $map[$slug] : '';
}

/**
 * Top-level items of whatever menu is assigned to the "primary" theme
 * location, keyed by menu item ID. Used to populate the Nav CTA "select a
 * menu item" control — this only reads the ordinary WP nav menu, no
 * separate CTA-only menu system.
 */
function ajnanda_get_primary_menu_top_level_items() {
    $locations = get_nav_menu_locations();

    if (empty($locations['primary'])) {
        return array();
    }

    $items = wp_get_nav_menu_items($locations['primary']);

    if (!$items) {
        return array();
    }

    $top_level = array();
    foreach ($items as $item) {
        if (0 === (int) $item->menu_item_parent) {
            $top_level[$item->ID] = $item->title;
        }
    }

    return $top_level;
}

/**
 * Optional thin accent strip along the top of the header. Off by default
 * (opt-in); solid color or 2/3-color gradient, all from theme palette
 * slugs so nothing site-specific is hard-coded here.
 */
function ajnanda_render_header_accent_strip() {
    if (!get_theme_mod('ajn_header_accent_enable', false)) {
        return;
    }

    echo '<span class="ajn-header-accent" aria-hidden="true"></span>';
}

function ajnanda_get_nav_cta_style_class($style) {
    $style = in_array($style, array('solid', 'outline', 'pill'), true) ? $style : 'solid';

    return 'ajn-nav-cta-style-' . $style;
}

/**
 * Marks one ordinary Primary Menu top-level item as the Nav CTA by adding
 * a class to it — no separate menu or menu-item post meta involved, so it
 * keeps working with a plain WordPress navigation menu (including in the
 * mobile menu, which reuses the same <ul>). Off by default (no item
 * selected), so existing menus are unaffected until a site owner picks one
 * in the Customizer.
 */
function ajnanda_apply_nav_cta_menu_class($classes, $item, $args) {
    if (empty($args->theme_location) || 'primary' !== $args->theme_location) {
        return $classes;
    }

    $cta_item_id = (int) get_theme_mod('ajn_nav_cta_menu_item', '');

    if ($cta_item_id && (int) $item->ID === $cta_item_id) {
        $classes[] = 'ajn-nav-cta';
        $classes[] = ajnanda_get_nav_cta_style_class(get_theme_mod('ajn_nav_cta_style', 'solid'));
    }

    return $classes;
}
add_filter('nav_menu_css_class', 'ajnanda_apply_nav_cta_menu_class', 10, 3);

function ajnanda_render_builder_element($builder, $element) {
    switch ($element) {
        case 'site-logo':
            ajnanda_render_builder_site_identity();
            break;
        case 'primary-menu':
            ajnanda_render_builder_menu('primary', 'nav-menu');
            break;
        case 'footer-menu':
            ajnanda_render_builder_menu('footer', 'footer-menu');
            break;
        case 'search':
            get_search_form();
            break;
        case 'button':
        case 'button-1':
            $button_text_setting  = 'footer' === $builder ? 'ajn_footer_builder_button_text' : 'ajn_builder_button_text';
            $button_url_setting   = 'footer' === $builder ? 'ajn_footer_builder_button_url' : 'ajn_builder_button_url';
            $button_style_setting = 'footer' === $builder ? 'ajn_footer_builder_button_style' : 'ajn_builder_button_style';
            $button_style_class   = ajnanda_get_cta_style_class(get_theme_mod($button_style_setting, 'solid'));
            echo '<a class="btn btn-primary ajn-builder-button' . esc_attr($button_style_class) . '" href="' . esc_url(get_theme_mod($button_url_setting, home_url('/contact/'))) . '">' . esc_html(get_theme_mod($button_text_setting, __('Contact Us', 'ajnanda'))) . '</a>';
            break;
        case 'button-2':
            echo '<a class="btn btn-secondary ajn-builder-button ajn-builder-button-secondary" href="' . esc_url(get_theme_mod('ajn_builder_button_2_url', home_url('/contact/'))) . '">' . esc_html(get_theme_mod('ajn_builder_button_2_text', __('Learn More', 'ajnanda'))) . '</a>';
            break;
        case 'copyright':
            echo '<div class="ajn-builder-copyright">&copy; ' . esc_html(date('Y')) . ' ' . esc_html(get_theme_mod('footer_bottom_text', ajnanda_get_footer_bottom_default())) . '</div>';
            break;
        case 'divider-1':
        case 'divider-2':
        case 'divider-3':
            echo '<span class="ajn-builder-divider" aria-hidden="true"></span>';
            break;
        case 'html-1':
            echo '<div class="ajn-builder-html">' . wp_kses_post(get_theme_mod('ajn_builder_html_1', get_bloginfo('description'))) . '</div>';
            break;
        case 'html-2':
            echo '<div class="ajn-builder-html">' . wp_kses_post(get_theme_mod('ajn_builder_html_2', '')) . '</div>';
            break;
        case 'social':
            $social_url   = get_theme_mod('ajn_builder_social_1_url', '#');
            $social_label = get_theme_mod('ajn_builder_social_1_label', __('Social', 'ajnanda'));
            echo '<div class="ajn-builder-social">';
            echo '<a href="' . esc_url($social_url) . '" aria-label="' . esc_attr($social_label) . '" target="_blank" rel="noopener noreferrer">';
            echo ajnanda_social_icon_svg($social_url);
            echo '<span class="ajn-social-label">' . esc_html($social_label) . '</span>';
            echo '</a>';
            echo '</div>';
            break;
        case 'woo-cart':
            if (function_exists('WC') && WC()->cart) {
                $cart_count = WC()->cart->get_cart_contents_count();
                $cart_url   = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
                /* translators: %d: number of items in cart */
                $label = sprintf(_n('%d item in cart', '%d items in cart', $cart_count, 'ajnanda'), $cart_count);
                echo '<a class="ajn-builder-cart" href="' . esc_url($cart_url) . '" aria-label="' . esc_attr($label) . '">';
                echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
                echo '<span class="ajn-cart-count">' . esc_html($cart_count) . '</span>';
                echo '</a>';
            }
            break;
        case 'widget-1':
        case 'widget-2':
        case 'widget-3':
        case 'widget-4':
            $index = absint(str_replace('widget-', '', $element));
            dynamic_sidebar($builder . '-builder-' . $index);
            break;
    }
}

function ajnanda_render_builder_layout($builder, $start_row = 1, $end_row = null) {
    $row_count = ajnanda_get_builder_row_count($builder);
    $end_row   = null !== $end_row ? min($end_row, $row_count) : $row_count;

    for ($row = $start_row; $row <= $end_row; $row++) {
        $row_output = '';
        $column_count = ajnanda_get_builder_row_columns($builder, $row);

        ob_start();
        for ($cell = 1; $cell <= $column_count; $cell++) {
            $element = ajnanda_get_builder_value($builder, $row, $cell);

            if ('none' === $element) {
                continue;
            }

            $width_desktop = ajnanda_get_builder_width($builder, $row, $cell, 'desktop');
            $width_tablet = ajnanda_get_builder_width($builder, $row, $cell, 'tablet');
            $width_mobile = ajnanda_get_builder_width($builder, $row, $cell, 'mobile');
            $align = ajnanda_get_builder_align($builder, $row, $cell);
            $cell_class = 'ajn-builder-cell ajn-builder-cell-' . esc_attr($element);
            $cell_style = '--ajn-builder-width-desktop: ' . esc_attr($width_desktop) . '; --ajn-builder-width-tablet: ' . esc_attr($width_tablet) . '; --ajn-builder-width-mobile: ' . esc_attr($width_mobile) . ';';
            if ($align) {
                $cell_class .= ' ajn-builder-cell-has-align';
                $cell_style .= ' --ajn-builder-align: ' . esc_attr(ajnanda_builder_align_to_justify_content($align)) . ';';
            }
            ?>
            <div
                class="<?php echo esc_attr($cell_class); ?>"
                style="<?php echo esc_attr($cell_style); ?>"
            >
                <?php ajnanda_render_builder_element($builder, $element); ?>
            </div>
            <?php
        }
        $row_output = trim(ob_get_clean());

        if ($row_output) {
            echo '<div class="ajn-builder-row ajn-builder-row-' . esc_attr($row) . '">' . $row_output . '</div>';
        }
    }
}

/**
 * Render the editable site footer.
 */
function ajnanda_render_site_footer() {
    ob_start();
    ?>
    <footer class="site-footer footer-layout-builder">
        <div class="container">
            <div class="footer-builder-container">
                <?php ajnanda_render_builder_layout('footer'); ?>
            </div>
        </div>
    </footer>
    <?php
    return ob_get_clean();
}

/**
 * Render a lightweight Astra-style builder map in the Customizer preview.
 */
function ajnanda_builder_element_choices() {
    return array(
        'none'        => __('Empty', 'ajnanda'),
        'site-logo'   => __('Site Title & Logo', 'ajnanda'),
        'primary-menu'=> __('Primary Menu', 'ajnanda'),
        'footer-menu' => __('Footer Menu', 'ajnanda'),
        'search'      => __('Search', 'ajnanda'),
        'button'      => __('Button 1', 'ajnanda'),
        'button-1'    => __('Button 1', 'ajnanda'),
        'button-2'    => __('Button 2', 'ajnanda'),
        'copyright'   => __('Copyright', 'ajnanda'),
        'divider-1'   => __('Divider 1', 'ajnanda'),
        'divider-2'   => __('Divider 2', 'ajnanda'),
        'divider-3'   => __('Divider 3', 'ajnanda'),
        'html-1'      => __('HTML 1', 'ajnanda'),
        'html-2'      => __('HTML 2', 'ajnanda'),
        'social'      => __('Social', 'ajnanda'),
        'woo-cart'    => __('WooCommerce Cart', 'ajnanda'),
        'widget-1'    => __('Widget 1', 'ajnanda'),
        'widget-2'    => __('Widget 2', 'ajnanda'),
        'widget-3'    => __('Widget 3', 'ajnanda'),
        'widget-4'    => __('Widget 4', 'ajnanda'),
    );
}

function ajnanda_builder_default($builder, $row, $cell) {
    $defaults = array(
        'header' => array(
            1 => array(1 => 'site-logo', 2 => 'widget-1', 3 => 'primary-menu', 4 => 'none'),
            2 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
            3 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
        ),
        // Row 1 mirrors the header's non-empty default (brand, nav, and a
        // closing element) instead of shipping every cell as 'none' — a
        // fresh site otherwise renders a completely blank <footer> until
        // someone manually configures it, since ajnanda_render_builder_layout()
        // skips 'none' cells and empty rows outright.
        'footer' => array(
            1 => array(1 => 'site-logo', 2 => 'footer-menu', 3 => 'copyright', 4 => 'none'),
            2 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
            3 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
        ),
    );

    return isset($defaults[$builder][$row][$cell]) ? $defaults[$builder][$row][$cell] : 'none';
}

function ajnanda_builder_row_count_setting_id($builder) {
    return 'ajn_' . $builder . '_builder_row_count';
}

function ajnanda_builder_row_columns_setting_id($builder, $row) {
    return 'ajn_' . $builder . '_builder_row_' . $row . '_columns';
}

function ajnanda_builder_row_count_default($builder) {
    return 1;
}

function ajnanda_builder_row_columns_default($builder, $row) {
    if ('header' === $builder && 1 === (int) $row) {
        return 3;
    }

    if ('footer' === $builder && 1 === (int) $row) {
        return 3;
    }

    return 1;
}

function ajnanda_builder_setting_id($builder, $row, $cell) {
    return 'ajn_' . $builder . '_builder_' . $row . '_' . $cell;
}

function ajnanda_builder_width_setting_id($builder, $row, $cell, $device) {
    return 'ajn_' . $builder . '_builder_' . $row . '_' . $cell . '_width_' . $device;
}

function ajnanda_builder_width_default($builder, $row, $cell) {
    if ('header' === $builder && 1 === (int) $row && 3 === (int) $cell) {
        return 4;
    }

    if ('footer' === $builder && 3 === (int) $row && 2 === (int) $cell) {
        return 4;
    }

    return 2;
}

function ajnanda_get_builder_value($builder, $row, $cell) {
    return get_theme_mod(ajnanda_builder_setting_id($builder, $row, $cell), ajnanda_builder_default($builder, $row, $cell));
}

function ajnanda_get_builder_row_count($builder) {
    return ajnanda_sanitize_builder_row_count(get_theme_mod(ajnanda_builder_row_count_setting_id($builder), ajnanda_builder_row_count_default($builder)));
}

function ajnanda_get_builder_row_columns($builder, $row) {
    return ajnanda_sanitize_builder_count(get_theme_mod(ajnanda_builder_row_columns_setting_id($builder, $row), ajnanda_builder_row_columns_default($builder, $row)));
}

function ajnanda_get_builder_width($builder, $row, $cell, $device) {
    return absint(get_theme_mod(ajnanda_builder_width_setting_id($builder, $row, $cell, $device), ajnanda_builder_width_default($builder, $row, $cell)));
}

function ajnanda_builder_align_setting_id($builder, $row, $cell) {
    return 'ajn_' . $builder . '_builder_' . $row . '_' . $cell . '_align';
}

function ajnanda_get_builder_align($builder, $row, $cell) {
    $align = get_theme_mod(ajnanda_builder_align_setting_id($builder, $row, $cell), '');
    $valid = array('left', 'center', 'right', 'space-between');

    return in_array($align, $valid, true) ? $align : '';
}

/**
 * Maps the alignment choice to a justify-content value for
 * .ajn-builder-cell (which is a flex item container).
 */
function ajnanda_builder_align_to_justify_content($align) {
    $map = array(
        'left'          => 'flex-start',
        'center'        => 'center',
        'right'         => 'flex-end',
        'space-between' => 'space-between',
    );

    return isset($map[$align]) ? $map[$align] : '';
}

function ajnanda_builder_has_saved_layout($builder) {
    $theme_mods = get_theme_mods();
    $prefix = 'ajn_' . $builder . '_builder_';

    if (!is_array($theme_mods)) {
        return false;
    }

    foreach ($theme_mods as $setting_id => $value) {
        if (0 === strpos($setting_id, $prefix) && '' !== $value && null !== $value) {
            return true;
        }
    }

    return false;
}

function ajnanda_get_header_layout() {
    $header_layout = get_theme_mod('header_layout', 'logo-left-menu-right');

    if (empty($header_layout)) {
        $header_layout = 'logo-left-menu-right';
    }

    if ('builder' !== $header_layout && ajnanda_builder_has_saved_layout('header')) {
        return 'builder';
    }

    return $header_layout;
}

function ajnanda_builder_focus_control($builder, $element, $fallback_setting_id) {
    if ('site-logo' === $element) {
        return 'custom_logo';
    }

    if ('primary-menu' === $element) {
        return 'nav_menu_locations[primary]';
    }

    if ('footer-menu' === $element) {
        return 'nav_menu_locations[footer]';
    }

    if ('footer' === $builder && ('button' === $element || 'button-1' === $element)) {
        return 'ajn_footer_builder_button_text';
    }

    if ('button' === $element || 'button-1' === $element) {
        return 'ajn_builder_button_text';
    }

    if ('button-2' === $element) {
        return 'ajn_builder_button_2_text';
    }

    if ('copyright' === $element) {
        return 'footer_bottom_text';
    }

    if ('html-1' === $element) {
        return 'ajn_builder_html_1';
    }

    if ('html-2' === $element) {
        return 'ajn_builder_html_2';
    }

    if ('social' === $element) {
        return 'ajn_builder_social_1_url';
    }

    if (0 === strpos($element, 'widget-')) {
        $index = absint(str_replace('widget-', '', $element));
        return 'sidebar-widgets-' . $builder . '-builder-' . $index;
    }

    return $fallback_setting_id;
}

function ajnanda_builder_contains_element($builder, $elements) {
    $elements = (array) $elements;

    for ($row = 1; $row <= 6; $row++) {
        for ($cell = 1; $cell <= 4; $cell++) {
            if (in_array(ajnanda_get_builder_value($builder, $row, $cell), $elements, true)) {
                return true;
            }
        }
    }

    return false;
}

function ajnanda_footer_builder_button_1_active() {
    return ajnanda_builder_contains_element('footer', array('button', 'button-1'));
}

function ajnanda_header_builder_button_1_active() {
    return ajnanda_builder_contains_element('header', array('button', 'button-1'));
}

function ajnanda_footer_builder_button_2_active() {
    return ajnanda_builder_contains_element('footer', 'button-2');
}

function ajnanda_footer_builder_html_2_active() {
    return ajnanda_builder_contains_element('footer', 'html-2');
}

function ajnanda_footer_builder_social_active() {
    return ajnanda_builder_contains_element('footer', 'social');
}

function ajnanda_header_builder_social_active() {
    return ajnanda_builder_contains_element('header', 'social');
}

function ajnanda_header_builder_html_1_active() {
    return ajnanda_builder_contains_element('header', 'html-1');
}

function ajnanda_footer_builder_copyright_active() {
    return ajnanda_builder_contains_element('footer', 'copyright');
}

function ajnanda_builder_insert_choices($builder = '') {
    $choices = ajnanda_builder_element_choices();
    unset($choices['none'], $choices['button']);

    if ('footer' === $builder) {
        unset($choices['site-logo'], $choices['primary-menu'], $choices['search']);
    }

    return $choices;
}

function ajnanda_render_customizer_builder_chip($label, $setting_id, $focus_control_id) {
    ?>
    <button type="button" class="ajn-customizer-builder-chip" data-ajn-focus-control="<?php echo esc_attr($focus_control_id); ?>">
        <?php echo esc_html($label); ?>
        <span aria-hidden="true" class="ajn-customizer-builder-remove" data-ajn-clear-control="<?php echo esc_attr($setting_id); ?>">&times;</span>
    </button>
    <?php
}

function ajnanda_render_customizer_builder_row($builder, $row) {
    $choices = ajnanda_builder_element_choices();
    $column_count = ajnanda_get_builder_row_columns($builder, $row);
    $columns_setting_id = ajnanda_builder_row_columns_setting_id($builder, $row);
    ?>
    <div class="ajn-customizer-builder-row">
        <div class="ajn-customizer-builder-row-handle">
            <span aria-hidden="true" class="ajn-customizer-builder-gear">⚙</span>
            <span class="ajn-customizer-builder-split" aria-label="<?php esc_attr_e('Split row into columns', 'ajnanda'); ?>">
                <?php foreach (array(1, 2, 4) as $columns) : ?>
                    <button
                        type="button"
                        class="<?php echo $columns === $column_count ? 'is-active' : ''; ?>"
                        data-ajn-set-control="<?php echo esc_attr($columns_setting_id); ?>"
                        data-ajn-set-value="<?php echo esc_attr($columns); ?>"
                    ><?php echo esc_html($columns); ?></button>
                <?php endforeach; ?>
            </span>
        </div>
        <?php for ($cell = 1; $cell <= $column_count; $cell++) : ?>
            <?php
            $setting_id = ajnanda_builder_setting_id($builder, $row, $cell);
            $value = ajnanda_get_builder_value($builder, $row, $cell);
            $label = isset($choices[$value]) ? $choices[$value] : $choices['none'];
            $width_desktop = ajnanda_get_builder_width($builder, $row, $cell, 'desktop');
            $width_tablet = ajnanda_get_builder_width($builder, $row, $cell, 'tablet');
            $width_mobile = ajnanda_get_builder_width($builder, $row, $cell, 'mobile');
            $focus_control_id = ajnanda_builder_focus_control($builder, $value, $setting_id);
            ?>
            <div
                class="ajn-customizer-builder-cell"
                style="--ajn-builder-width-desktop: <?php echo esc_attr($width_desktop); ?>; --ajn-builder-width-tablet: <?php echo esc_attr($width_tablet); ?>; --ajn-builder-width-mobile: <?php echo esc_attr($width_mobile); ?>;"
            >
                <?php if ('none' !== $value) : ?>
                    <?php ajnanda_render_customizer_builder_chip($label, $setting_id, $focus_control_id); ?>
                <?php else : ?>
                    <button
                        type="button"
                        class="ajn-customizer-builder-add"
                        data-ajn-insert-control="<?php echo esc_attr($setting_id); ?>"
                        data-ajn-builder="<?php echo esc_attr($builder); ?>"
                    >+</button>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
    <?php
}

function ajnanda_render_customizer_builder_add_row($row_count_setting_id, $next_count) {
    if ($next_count > 6) {
        return;
    }
    ?>
    <button
        type="button"
        class="ajn-customizer-builder-add-row"
        data-ajn-set-control="<?php echo esc_attr($row_count_setting_id); ?>"
        data-ajn-set-value="<?php echo esc_attr($next_count); ?>"
        aria-label="<?php esc_attr_e('Add row', 'ajnanda'); ?>"
    >+</button>
    <?php
}

function ajnanda_render_customizer_builder_remove_row($row_count_setting_id, $previous_count) {
    if ($previous_count < 1) {
        return;
    }
    ?>
    <button
        type="button"
        class="ajn-customizer-builder-remove-row"
        data-ajn-set-control="<?php echo esc_attr($row_count_setting_id); ?>"
        data-ajn-set-value="<?php echo esc_attr($previous_count); ?>"
        aria-label="<?php esc_attr_e('Remove last row', 'ajnanda'); ?>"
    >&minus;</button>
    <?php
}

function ajnanda_render_header_builder_preview() {
    if (!is_customize_preview()) {
        return;
    }
    $row_count = ajnanda_get_builder_row_count('header');
    $row_count_setting_id = ajnanda_builder_row_count_setting_id('header');
    ?>
    <div class="ajn-customizer-builder-preview ajn-customizer-header-builder" aria-label="<?php esc_attr_e('Header Builder Preview', 'ajnanda'); ?>">
        <span class="ajn-customizer-builder-tooltip"><?php esc_html_e('Header Builder Preview', 'ajnanda'); ?></span>
        <?php
        for ($row = 1; $row <= $row_count; $row++) {
            ajnanda_render_customizer_builder_row('header', $row);
            if ($row === $row_count && $row_count > 1) {
                ajnanda_render_customizer_builder_remove_row($row_count_setting_id, $row_count - 1);
            }
            if ($row === $row_count) {
                ajnanda_render_customizer_builder_add_row($row_count_setting_id, $row_count + 1);
            }
        }
        ?>
    </div>
    <?php
}

function ajnanda_render_footer_builder_preview() {
    if (!is_customize_preview()) {
        return;
    }
    $row_count = ajnanda_get_builder_row_count('footer');
    $row_count_setting_id = ajnanda_builder_row_count_setting_id('footer');
    ?>
    <div class="ajn-customizer-builder-preview ajn-customizer-footer-builder" aria-label="<?php esc_attr_e('Footer Builder Preview', 'ajnanda'); ?>">
        <span class="ajn-customizer-builder-tooltip"><?php esc_html_e('Footer Builder Preview', 'ajnanda'); ?></span>
        <?php
        for ($row = 1; $row <= $row_count; $row++) {
            ajnanda_render_customizer_builder_row('footer', $row);
            if ($row === $row_count && $row_count > 1) {
                ajnanda_render_customizer_builder_remove_row($row_count_setting_id, $row_count - 1);
            }
            if ($row === $row_count) {
                ajnanda_render_customizer_builder_add_row($row_count_setting_id, $row_count + 1);
            }
        }
        ?>
    </div>
    <?php
}

function ajnanda_register_builder_controls($wp_customize, $builder, $section, $label_prefix, $width_section = '') {
    $choices = ajnanda_builder_element_choices();
    $show_width_controls = '' !== $width_section;
    $width_section = $show_width_controls ? $width_section : $section;
    $device_labels = array(
        'desktop' => __('Desktop', 'ajnanda'),
        'tablet'  => __('Tablet', 'ajnanda'),
        'mobile'  => __('Mobile', 'ajnanda'),
    );
    $row_count_setting_id = ajnanda_builder_row_count_setting_id($builder);

    $wp_customize->add_setting($row_count_setting_id, array(
        'default'           => ajnanda_builder_row_count_default($builder),
        'sanitize_callback' => 'ajnanda_sanitize_builder_row_count',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control($row_count_setting_id, array(
        'label'           => sprintf(__('%s Row Count', 'ajnanda'), $label_prefix),
        'section'         => $section,
        'type'            => 'number',
        'active_callback' => '__return_false',
    ));

    for ($row = 1; $row <= 6; $row++) {
        $row_columns_setting_id = ajnanda_builder_row_columns_setting_id($builder, $row);

        $wp_customize->add_setting($row_columns_setting_id, array(
            'default'           => ajnanda_builder_row_columns_default($builder, $row),
            'sanitize_callback' => 'ajnanda_sanitize_builder_count',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($row_columns_setting_id, array(
            'label'           => sprintf(
                /* translators: 1: builder label, 2: row number. */
                __('%1$s Row %2$d Columns', 'ajnanda'),
                $label_prefix,
                $row
            ),
            'section'         => $section,
            'type'            => 'number',
            'active_callback' => '__return_false',
        ));

        for ($cell = 1; $cell <= 4; $cell++) {
            $setting_id = ajnanda_builder_setting_id($builder, $row, $cell);

            $wp_customize->add_setting($setting_id, array(
                'default'           => ajnanda_builder_default($builder, $row, $cell),
                'sanitize_callback' => 'ajnanda_sanitize_choice',
                'transport'         => 'postMessage',
            ));

            $wp_customize->add_control($setting_id, array(
                'label'       => sprintf(
                    /* translators: 1: builder label, 2: row number, 3: cell number. */
                    __('%1$s Row %2$d Cell %3$d Element', 'ajnanda'),
                    $label_prefix,
                    $row,
                    $cell
                ),
                'section'     => $section,
                'type'        => 'select',
                'choices'     => $choices,
                'active_callback' => '__return_false',
            ));

            foreach ($device_labels as $device => $device_label) {
                $width_setting_id = ajnanda_builder_width_setting_id($builder, $row, $cell, $device);

                $wp_customize->add_setting($width_setting_id, array(
                    'default'           => ajnanda_builder_width_default($builder, $row, $cell),
                    'sanitize_callback' => 'ajnanda_sanitize_builder_width',
                    'transport'         => 'refresh',
                ));

                if ($show_width_controls) {
                    $wp_customize->add_control($width_setting_id, array(
                        'label'       => sprintf(
                            /* translators: 1: device label, 2: row number, 3: cell number. */
                            __('%1$s Width - Row %2$d Cell %3$d', 'ajnanda'),
                            $device_label,
                            $row,
                            $cell
                        ),
                        'description' => __('Relative width from 1 to 6. Larger numbers take more horizontal space.', 'ajnanda'),
                        'section'     => $width_section,
                        'type'        => 'number',
                        'input_attrs' => array(
                            'min'  => 1,
                            'max'  => 6,
                            'step' => 1,
                        ),
                    ));
                }
            }

            /*
             * Independent cell alignment (e.g. for a row holding Site
             * Identity + Primary Navigation + a CTA button). Defaults to ""
             * (no override), which keeps every existing site's layout
             * pixel-identical — .ajn-builder-cell-{element} rules in
             * style.css already give each element type a sensible default
             * alignment; this only overrides it when explicitly set.
             */
            $align_setting_id = ajnanda_builder_align_setting_id($builder, $row, $cell);

            $wp_customize->add_setting($align_setting_id, array(
                'default'           => '',
                'sanitize_callback' => 'ajnanda_sanitize_choice',
                'transport'         => 'refresh',
            ));

            $wp_customize->add_control($align_setting_id, array(
                'label'       => sprintf(
                    /* translators: 1: builder label, 2: row number, 3: cell number. */
                    __('%1$s Row %2$d Cell %3$d Alignment', 'ajnanda'),
                    $label_prefix,
                    $row,
                    $cell
                ),
                'description' => __('Leave as Default to keep this cell\'s built-in alignment for its element type.', 'ajnanda'),
                'section'     => $section,
                'type'        => 'select',
                'choices'     => array(
                    ''              => __('Default', 'ajnanda'),
                    'left'          => __('Left', 'ajnanda'),
                    'center'        => __('Center', 'ajnanda'),
                    'right'         => __('Right', 'ajnanda'),
                    'space-between' => __('Space Between', 'ajnanda'),
                ),
            ));
        }
    }
}

/**
 * Generic header-builder controls: navigation container style, active
 * menu item treatment, navigation CTA (built on an ordinary WP nav menu),
 * branding badge shape/color, and the optional accent strip. Everything
 * here is opt-in — every new setting defaults to "none"/"" so an existing
 * AJNanda header renders exactly as it did before these controls existed.
 * All colors are restricted to the theme.json palette (no hard-coded
 * hex values, no business-specific defaults).
 */
function ajnanda_register_header_builder_extras($wp_customize) {
    $palette = ajnanda_get_theme_palette_choices();

    // 1. Navigation container style.
    $wp_customize->add_setting('ajn_nav_container_style', array(
        'default'           => 'none',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_container_style', array(
        'label'   => __('Nav Container: Style', 'ajnanda'),
        'section' => 'ajnanda_header',
        'type'    => 'select',
        'choices' => array(
            'none'         => __('None', 'ajnanda'),
            'soft-panel'   => __('Soft Panel', 'ajnanda'),
            'rounded-rect' => __('Rounded Rectangle', 'ajnanda'),
            'pill'         => __('Pill', 'ajnanda'),
        ),
    ));

    $wp_customize->add_setting('ajn_nav_container_bg', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_container_bg', array(
        'label'       => __('Nav Container: Background Color', 'ajnanda'),
        'description' => __('Only used when Style above is not None.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'select',
        'choices'     => $palette,
    ));

    $wp_customize->add_setting('ajn_nav_container_border_color', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_container_border_color', array(
        'label'   => __('Nav Container: Border Color', 'ajnanda'),
        'section' => 'ajnanda_header',
        'type'    => 'select',
        'choices' => $palette,
    ));

    $wp_customize->add_setting('ajn_nav_container_border_width', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_css_size',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_container_border_width', array(
        'label'       => __('Nav Container: Border Width', 'ajnanda'),
        'description' => __('Example: 1px. Leave blank for the style default.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('ajn_nav_container_border_radius', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_css_size',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_container_border_radius', array(
        'label'       => __('Nav Container: Border Radius', 'ajnanda'),
        'description' => __('Example: 0.75rem or 999px for a full pill. Leave blank for the style default.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('ajn_nav_container_padding_x', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_css_size',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_container_padding_x', array(
        'label'       => __('Nav Container: Horizontal Padding', 'ajnanda'),
        'description' => __('Leave blank for the style default.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('ajn_nav_container_padding_y', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_css_size',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_container_padding_y', array(
        'label'       => __('Nav Container: Vertical Padding', 'ajnanda'),
        'description' => __('Leave blank for the style default.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('ajn_nav_container_shadow', array(
        'default'           => 0,
        'sanitize_callback' => 'ajnanda_sanitize_opacity',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_container_shadow', array(
        'label'       => __('Nav Container: Shadow Strength', 'ajnanda'),
        'description' => __('0 = no shadow, 1 = strongest.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'number',
        'input_attrs' => array('min' => 0, 'max' => 1, 'step' => 0.05),
    ));

    // 2. Active menu item treatment.
    $wp_customize->add_setting('ajn_active_menu_style', array(
        'default'           => 'none',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_active_menu_style', array(
        'label'       => __('Active Menu Item: Treatment', 'ajnanda'),
        'description' => __('Applies to whichever menu item matches the current page (WordPress\' own "current-menu-item" state).', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'select',
        'choices'     => array(
            'none'                => __('None', 'ajnanda'),
            'text-color'          => __('Text Color', 'ajnanda'),
            'underline'           => __('Underline', 'ajnanda'),
            'background-highlight'=> __('Background Highlight', 'ajnanda'),
            'pill-highlight'      => __('Pill Highlight', 'ajnanda'),
        ),
    ));

    $wp_customize->add_setting('ajn_active_menu_color', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_active_menu_color', array(
        'label'       => __('Active Menu Item: Color', 'ajnanda'),
        'description' => __('Only used when Treatment above is not None. Defaults to the theme primary color.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'select',
        'choices'     => $palette,
    ));

    // 3. Header navigation CTA (an ordinary top-level Primary Menu item).
    $cta_choices = array('' => __('None', 'ajnanda'));
    foreach (ajnanda_get_primary_menu_top_level_items() as $item_id => $item_title) {
        $cta_choices[$item_id] = $item_title;
    }

    $wp_customize->add_setting('ajn_nav_cta_menu_item', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_cta_menu_item', array(
        'label'       => __('Nav CTA: Menu Item', 'ajnanda'),
        'description' => __('Choose a top-level item from your Primary Menu to style as a call-to-action. Add/edit items in Appearance -> Menus.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'select',
        'choices'     => $cta_choices,
    ));

    $wp_customize->add_setting('ajn_nav_cta_style', array(
        'default'           => 'solid',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_cta_style', array(
        'label'   => __('Nav CTA: Style', 'ajnanda'),
        'section' => 'ajnanda_header',
        'type'    => 'select',
        'choices' => array(
            'solid'   => __('Solid', 'ajnanda'),
            'outline' => __('Outline', 'ajnanda'),
            'pill'    => __('Pill', 'ajnanda'),
        ),
    ));

    $nav_cta_color_fields = array(
        'ajn_nav_cta_bg'          => __('Nav CTA: Background Color', 'ajnanda'),
        'ajn_nav_cta_color'       => __('Nav CTA: Text Color', 'ajnanda'),
        'ajn_nav_cta_hover_bg'    => __('Nav CTA: Hover Background', 'ajnanda'),
        'ajn_nav_cta_hover_color' => __('Nav CTA: Hover Text Color', 'ajnanda'),
    );
    foreach ($nav_cta_color_fields as $setting_id => $label) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => '',
            'sanitize_callback' => 'ajnanda_sanitize_choice',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($setting_id, array(
            'label'   => $label,
            'section' => 'ajnanda_header',
            'type'    => 'select',
            'choices' => $palette,
        ));
    }

    $wp_customize->add_setting('ajn_nav_cta_border_radius', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_css_size',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_cta_border_radius', array(
        'label'       => __('Nav CTA: Border Radius', 'ajnanda'),
        'description' => __('Ignored when Style is Pill (always fully rounded).', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('ajn_nav_cta_shadow', array(
        'default'           => 0,
        'sanitize_callback' => 'ajnanda_sanitize_opacity',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_nav_cta_shadow', array(
        'label'       => __('Nav CTA: Shadow Strength', 'ajnanda'),
        'description' => __('0 = no shadow, 1 = strongest.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'number',
        'input_attrs' => array('min' => 0, 'max' => 1, 'step' => 0.05),
    ));

    // 4. Branding badge styling (extends the badge text/tagline controls
    // already registered in the Site Identity section).
    $wp_customize->add_setting('ajn_branding_badge_shape', array(
        'default'           => 'compact-pill',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_branding_badge_shape', array(
        'label'       => __('Branding Badge: Shape', 'ajnanda'),
        'description' => __('Only shown when a Branding Badge/Mark is set in Site Identity.', 'ajnanda'),
        'section'     => 'title_tagline',
        'type'        => 'select',
        'choices'     => array(
            'compact-pill'   => __('Compact Pill', 'ajnanda'),
            'circle'         => __('Circle', 'ajnanda'),
            'rounded-square' => __('Rounded Square', 'ajnanda'),
        ),
        'priority'    => 25,
    ));

    $badge_color_fields = array(
        'ajn_branding_badge_bg'    => __('Branding Badge: Background Color', 'ajnanda'),
        'ajn_branding_badge_color' => __('Branding Badge: Text Color', 'ajnanda'),
    );
    $badge_priority = 26;
    foreach ($badge_color_fields as $setting_id => $label) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => '',
            'sanitize_callback' => 'ajnanda_sanitize_choice',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($setting_id, array(
            'label'    => $label,
            'section'  => 'title_tagline',
            'type'     => 'select',
            'choices'  => $palette,
            'priority' => $badge_priority++,
        ));
    }

    $badge_size_fields = array(
        'ajn_branding_badge_width'  => array(__('Branding Badge: Width', 'ajnanda'), 'ajnanda_sanitize_css_size'),
        'ajn_branding_badge_height' => array(__('Branding Badge: Height', 'ajnanda'), 'ajnanda_sanitize_css_size'),
        'ajn_branding_badge_radius' => array(__('Branding Badge: Border Radius', 'ajnanda'), 'ajnanda_sanitize_css_size'),
        'ajn_branding_badge_font_size' => array(__('Branding Badge: Font Size', 'ajnanda'), 'ajnanda_sanitize_css_size'),
    );
    foreach ($badge_size_fields as $setting_id => $field) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => '',
            'sanitize_callback' => $field[1],
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($setting_id, array(
            'label'       => $field[0],
            'description' => __('Leave blank for the shape default.', 'ajnanda'),
            'section'     => 'title_tagline',
            'type'        => 'text',
            'priority'    => $badge_priority++,
        ));
    }

    $wp_customize->add_setting('ajn_branding_badge_font_weight', array(
        'default'           => '',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_branding_badge_font_weight', array(
        'label'       => __('Branding Badge: Font Weight', 'ajnanda'),
        'description' => __('Leave blank for the shape default.', 'ajnanda'),
        'section'     => 'title_tagline',
        'type'        => 'select',
        'choices'     => array(
            ''     => __('— Shape Default —', 'ajnanda'),
            '400'  => '400',
            '500'  => '500',
            '600'  => '600',
            '700'  => '700',
            '800'  => '800',
        ),
        'priority'    => $badge_priority++,
    ));

    // 5. Header accent strip.
    $wp_customize->add_setting('ajn_header_accent_enable', array(
        'default'           => false,
        'sanitize_callback' => 'ajnanda_sanitize_checkbox',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_header_accent_enable', array(
        'label'   => __('Accent Strip: Enable', 'ajnanda'),
        'section' => 'ajnanda_header',
        'type'    => 'checkbox',
    ));

    $wp_customize->add_setting('ajn_header_accent_type', array(
        'default'           => 'solid',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_header_accent_type', array(
        'label'   => __('Accent Strip: Type', 'ajnanda'),
        'section' => 'ajnanda_header',
        'type'    => 'select',
        'choices' => array(
            'solid'      => __('Solid Color', 'ajnanda'),
            'gradient-2' => __('Two-Color Gradient', 'ajnanda'),
            'gradient-3' => __('Three-Color Gradient', 'ajnanda'),
        ),
    ));

    $accent_color_fields = array(
        'ajn_header_accent_color_1' => array(__('Accent Strip: Color 1', 'ajnanda'), 'primary-blue'),
        'ajn_header_accent_color_2' => array(__('Accent Strip: Color 2', 'ajnanda'), 'purple'),
        'ajn_header_accent_color_3' => array(__('Accent Strip: Color 3', 'ajnanda'), 'gold'),
    );
    foreach ($accent_color_fields as $setting_id => $field) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $field[1],
            'sanitize_callback' => 'ajnanda_sanitize_choice',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($setting_id, array(
            'label'   => $field[0],
            'section' => 'ajnanda_header',
            'type'    => 'select',
            'choices' => ajnanda_get_theme_palette_choices(false),
        ));
    }

    $wp_customize->add_setting('ajn_header_accent_height', array(
        'default'           => '4px',
        'sanitize_callback' => 'ajnanda_sanitize_css_size',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('ajn_header_accent_height', array(
        'label'   => __('Accent Strip: Height', 'ajnanda'),
        'section' => 'ajnanda_header',
        'type'    => 'text',
    ));
}

/**
 * Fetch Google Business Profile reviews through the Google Places API.
 *
 * Add these constants in wp-config.php to enable live reviews:
 * define('NCLLC_GOOGLE_PLACES_API_KEY', 'your-api-key');
 * define('NCLLC_GOOGLE_PLACE_ID', 'your-place-id');
 */
function ajnanda_get_google_reviews() {
    $api_key = defined('NCLLC_GOOGLE_PLACES_API_KEY') ? NCLLC_GOOGLE_PLACES_API_KEY : '';
    $place_id = defined('NCLLC_GOOGLE_PLACE_ID') ? NCLLC_GOOGLE_PLACE_ID : '';
    // No site-specific fallback URL: every AJNanda site has a different
    // Google Business Profile, so an unconfigured site shows no link
    // rather than someone else's reviews page.
    $profile_url = '';
    $write_review_url = '';

    if (!$api_key || !$place_id) {
        return array(
            'configured' => false,
            'reviews' => array(),
            'rating' => '',
            'review_count' => '',
            'url' => $profile_url,
            'write_review_url' => $write_review_url,
        );
    }

    $cache_key = 'ajnanda_google_reviews_' . md5($place_id);
    $cached = get_transient($cache_key);

    if (false !== $cached) {
        return $cached;
    }

    $request_url = add_query_arg(
        array(
            'place_id' => $place_id,
            'fields' => 'name,rating,user_ratings_total,reviews,url',
            'key' => $api_key,
        ),
        'https://maps.googleapis.com/maps/api/place/details/json'
    );

    $response = wp_remote_get($request_url, array('timeout' => 12));

    if (is_wp_error($response)) {
        return array(
            'configured' => true,
            'reviews' => array(),
            'rating' => '',
            'review_count' => '',
            'url' => $profile_url,
            'write_review_url' => $write_review_url,
        );
    }

    $payload = json_decode(wp_remote_retrieve_body($response), true);
    $result = isset($payload['result']) && is_array($payload['result']) ? $payload['result'] : array();

    $reviews = array();
    if (!empty($result['reviews']) && is_array($result['reviews'])) {
        foreach (array_slice($result['reviews'], 0, 3) as $review) {
            $reviews[] = array(
                'author_name' => isset($review['author_name']) ? sanitize_text_field($review['author_name']) : '',
                'rating' => isset($review['rating']) ? absint($review['rating']) : 0,
                'relative_time_description' => isset($review['relative_time_description']) ? sanitize_text_field($review['relative_time_description']) : '',
                'text' => isset($review['text']) ? wp_kses_post($review['text']) : '',
            );
        }
    }

    $data = array(
        'configured' => true,
        'reviews' => $reviews,
        'rating' => isset($result['rating']) ? $result['rating'] : '',
        'review_count' => isset($result['user_ratings_total']) ? absint($result['user_ratings_total']) : '',
        'url' => isset($result['url']) ? esc_url_raw($result['url']) : $profile_url,
        'write_review_url' => $write_review_url,
    );

    set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);

    return $data;
}

/**
 * Render an editable Google reviews section.
 */
function ajnanda_google_reviews_shortcode() {
    $data = ajnanda_get_google_reviews();
    $profile_url = !empty($data['url']) ? $data['url'] : '';
    $write_review_url = !empty($data['write_review_url']) ? $data['write_review_url'] : '';

    ob_start();
    ?>
    <div class="google-reviews-block">
        <?php if (!empty($data['reviews'])) : ?>
            <div class="google-reviews-summary">
                <strong><?php echo esc_html($data['rating']); ?> on Google</strong>
                <?php if (!empty($data['review_count'])) : ?>
                    <span><?php echo esc_html($data['review_count']); ?> reviews</span>
                <?php endif; ?>
            </div>
            <div class="google-reviews-grid">
                <?php foreach (array_slice($data['reviews'], 0, 3) as $review) : ?>
                    <article class="google-review-card">
                        <div class="google-review-stars" aria-label="<?php echo esc_attr($review['rating']); ?> out of 5 stars">
                            <?php echo str_repeat('&#9733;', max(0, min(5, (int) $review['rating']))); ?>
                        </div>
                        <p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($review['text']), 34)); ?></p>
                        <footer>
                            <strong><?php echo esc_html($review['author_name']); ?></strong>
                            <?php if (!empty($review['relative_time_description'])) : ?>
                                <span><?php echo esc_html($review['relative_time_description']); ?></span>
                            <?php endif; ?>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="google-review-empty">
                <h3><?php esc_html_e('Read our Google reviews', 'ajnanda'); ?></h3>
                <p><?php esc_html_e('See what clients are saying on Google, or share your experience with us.', 'ajnanda'); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($profile_url || $write_review_url) : ?>
        <div class="google-review-actions">
            <?php if ($profile_url) : ?>
                <a class="button" href="<?php echo esc_url($profile_url); ?>" target="_blank" rel="noreferrer noopener"><?php esc_html_e('Read Google Reviews', 'ajnanda'); ?></a>
            <?php endif; ?>
            <?php if ($write_review_url) : ?>
                <a class="button button-secondary" href="<?php echo esc_url($write_review_url); ?>" target="_blank" rel="noreferrer noopener"><?php esc_html_e('Write a Review', 'ajnanda'); ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('ajnanda_google_reviews', 'ajnanda_google_reviews_shortcode');

/**
 * Performance optimizations
 */
function ajnanda_optimize() {
    // Remove emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    
    // Remove WordPress version
    remove_action('wp_head', 'wp_generator');
    
    // Remove RSD link
    remove_action('wp_head', 'rsd_link');
    
    // Remove wlwmanifest link
    remove_action('wp_head', 'wlwmanifest_link');
    
    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Remove REST API link
    remove_action('wp_head', 'rest_output_link_wp_head');
    
    // Remove oEmbed discovery links
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
}
add_action('init', 'ajnanda_optimize');

/**
 * Disable XML-RPC
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Keep the builder cart count fresh via WooCommerce fragment system.
 */
function ajnanda_cart_fragment($fragments) {
    if (!function_exists('WC') || !WC()->cart) {
        return $fragments;
    }

    $count = WC()->cart->get_cart_contents_count();
    $fragments['.ajn-cart-count'] = '<span class="ajn-cart-count">' . esc_html($count) . '</span>';

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'ajnanda_cart_fragment');

/**
 * Remove query strings from static resources
 */
function ajnanda_remove_query_strings($src, $handle = '') {
    $theme_uri = get_template_directory_uri();
    $stylesheet_uri = get_stylesheet_directory_uri();

    if (0 === strpos($src, $theme_uri) || 0 === strpos($src, $stylesheet_uri)) {
        return $src;
    }

    if (strpos($src, '?ver=')) {
        $src = remove_query_arg('ver', $src);
    }

    return $src;
}
add_filter('style_loader_src', 'ajnanda_remove_query_strings', 10, 2);
add_filter('script_loader_src', 'ajnanda_remove_query_strings', 10, 2);

/**
 * Add async/defer to scripts
 */
function ajnanda_add_async_defer($tag, $handle) {
    $async_scripts = array('ajnanda-pro-script');
    
    if (in_array($handle, $async_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'ajnanda_add_async_defer', 10, 2);

/**
 * Security headers
 */
function ajnanda_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
add_action('send_headers', 'ajnanda_security_headers');

/**
 * Custom image sizes
 */
add_image_size('ajnanda-featured', 1200, 600, true);
add_image_size('ajnanda-thumbnail', 400, 300, true);
add_image_size('ajnanda-square', 600, 600, true);

/**
 * Enable SVG uploads
 */
function ajnanda_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'ajnanda_mime_types');

/**
 * Sanitize SVG uploads
 */
function ajnanda_sanitize_svg($file) {
    if ($file['type'] === 'image/svg+xml') {
        $file['ext'] = 'svg';
        $file['type'] = 'image/svg+xml';
    }
    return $file;
}
add_filter('wp_check_filetype_and_ext', 'ajnanda_sanitize_svg', 10, 4);

/**
 * Customizer settings
 */
function ajnanda_header_color_scheme_labels() {
    return array(
        'A' => __('A - Midnight Navy', 'ajnanda'),
        'B' => __('B - Deep Slate', 'ajnanda'),
        'C' => __('C - Clear Sky', 'ajnanda'),
        'D' => __('D - Purple Lift', 'ajnanda'),
        'E' => __('E - Slate Indigo', 'ajnanda'),
        'F' => __('F - Blue & Gold', 'ajnanda'),
        'G' => __('G - Cool Gray', 'ajnanda'),
        'H' => __('H - Dark Navy', 'ajnanda'),
        'I' => __('I - Charcoal Cyan', 'ajnanda'),
        'J' => __('J - Forest Dark', 'ajnanda'),
        'K' => __('K - Editorial Black', 'ajnanda'),
        'L' => __('L - Warm Ivory', 'ajnanda'),
        'M' => __('M - Rose Graphite', 'ajnanda'),
        'N' => __('N - High Contrast', 'ajnanda'),
        'O' => __('O - Ocean Teal', 'ajnanda'),
        'P' => __('P - Sky Light', 'ajnanda'),
        'Q' => __('Q - Emerald Pro', 'ajnanda'),
        'R' => __('R - Soft Mint', 'ajnanda'),
        'S' => __('S - Royal Blue', 'ajnanda'),
        'T' => __('T - Executive Gray', 'ajnanda'),
    );
}

function ajnanda_header_color_scheme_values() {
    return array(
        'A' => array('colors' => array('#111827','#60A5FA','#1F2937','#0F172A','#F1F5F9','#93C5FD','#1E3A8A'), 'font' => array('#F9FAFB','inherit','1rem','normal')),
        'B' => array('colors' => array('#E0E7FF','#4F46E5','#C7D2FE','#FFFFFF','#111827','#4F46E5','#EEF2FF'), 'font' => array('#111827','Inter','1rem','bold')),
        'C' => array('colors' => array('#EFF6FF','#1D4ED8','#DBEAFE','#FFFFFF','#0F172A','#1D4ED8','#F0F9FF'), 'font' => array('#0F172A','system-ui','1rem','normal')),
        'D' => array('colors' => array('#F3E8FF','#7C3AED','#E9D5FF','#FFFFFF','#111827','#7C3AED','#FAF5FF'), 'font' => array('#111827','Poppins','1rem','bold')),
        'E' => array('colors' => array('#EEF2FF','#4338CA','#E0E7FF','#F8FAFC','#0F172A','#4338CA','#EEF2FF'), 'font' => array('#0F172A','Inter','1rem','normal')),
        'F' => array('colors' => array('#EFF6FF','#F59E0B','#DBEAFE','#FFFFFF','#0F172A','#F59E0B','#FEF3C7'), 'font' => array('#0F172A','Poppins','1rem','bold')),
        'G' => array('colors' => array('#F8FAFC','#2563EB','#E2E8F0','#FFFFFF','#0F172A','#2563EB','#EFF6FF'), 'font' => array('#0F172A','Inter','1rem','normal')),
        'H' => array('colors' => array('#0F172A','#93C5FD','#1E293B','#F8FAFC','#93C5FD','#93C5FD','#1E3A8A'), 'font' => array('#F8FAFC','Inter','1rem','bold')),
        'I' => array('colors' => array('#111827','#22D3EE','#164E63','#0F172A','#E5E7EB','#67E8F9','#083344'), 'font' => array('#F9FAFB','system-ui','1rem','normal')),
        'J' => array('colors' => array('#052E16','#86EFAC','#14532D','#ECFDF5','#BBF7D0','#BBF7D0','#166534'), 'font' => array('#F0FDF4','Inter','1rem','bold')),
        'K' => array('colors' => array('#030712','#FACC15','#27272A','#FAFAFA','#FDE047','#FDE047','#3F3F46'), 'font' => array('#FAFAFA','Georgia','1.02rem','normal')),
        'L' => array('colors' => array('#FFFBEB','#B45309','#FEF3C7','#FFFFFF','#1F2937','#92400E','#FDE68A'), 'font' => array('#1F2937','Georgia','1.02rem','normal')),
        'M' => array('colors' => array('#FFF1F2','#BE123C','#FFE4E6','#FFFFFF','#111827','#BE123C','#FFE4E6'), 'font' => array('#111827','Poppins','1rem','bold')),
        'N' => array('colors' => array('#000000','#FFFFFF','#1F2937','#FFFFFF','#FACC15','#FACC15','#111827'), 'font' => array('#FFFFFF','Arial','1rem','bold-underline')),
        'O' => array('colors' => array('#F0FDFA','#0D9488','#CCFBF1','#FFFFFF','#134E4A','#0F766E','#F0FDFA'), 'font' => array('#134E4A','Inter','1rem','normal')),
        'P' => array('colors' => array('#F0F9FF','#0284C7','#E0F2FE','#FFFFFF','#0C4A6E','#0369A1','#F0F9FF'), 'font' => array('#0C4A6E','Inter','1rem','normal')),
        'Q' => array('colors' => array('#ECFDF5','#059669','#D1FAE5','#FFFFFF','#065F46','#047857','#D1FAE5'), 'font' => array('#065F46','Inter','1rem','normal')),
        'R' => array('colors' => array('#F0FDF4','#16A34A','#DCFCE7','#FFFFFF','#14532D','#15803D','#DCFCE7'), 'font' => array('#14532D','Inter','1rem','normal')),
        'S' => array('colors' => array('#1D4ED8','#FBBF24','#1E40AF','#FFFFFF','#1D4ED8','#1E40AF','#EFF6FF'), 'font' => array('#FFFFFF','Inter','1rem','bold')),
        'T' => array('colors' => array('#374151','#60A5FA','#4B5563','#FFFFFF','#374151','#2563EB','#EFF6FF'), 'font' => array('#F9FAFB','Inter','1rem','normal')),
    );
}

function ajnanda_footer_settings_from_header_scheme($scheme_id) {
    $schemes = ajnanda_header_color_scheme_values();

    if (!isset($schemes[$scheme_id])) {
        return array();
    }

    $scheme = $schemes[$scheme_id];

    return array(
        'footer_background_color'         => $scheme['colors'][0],
        'footer_link_hover_color'         => $scheme['colors'][1],
        'footer_divider_color'            => $scheme['colors'][2],
        'footer_submenu_background'       => $scheme['colors'][3],
        'footer_submenu_text_color'       => $scheme['colors'][4],
        'footer_submenu_hover_color'      => $scheme['colors'][5],
        'footer_submenu_hover_background' => $scheme['colors'][6],
        'footer_text_color'               => $scheme['font'][0],
        'footer_font_family'              => $scheme['font'][1],
        'footer_font_size'                => $scheme['font'][2],
        'footer_font_weight'              => in_array($scheme['font'][3], array('bold', 'bold-italic', 'bold-underline'), true) ? '700' : '400',
        // font[3] is the same preset vocabulary as header_font_preset
        // (e.g. "bold-underline"); previously only the bold/700 half was
        // read out of it here, silently dropping any italic/underline the
        // scheme specified.
        'footer_font_style'               => in_array($scheme['font'][3], array('italic', 'bold-italic'), true) ? 'italic' : 'normal',
        'footer_text_decoration'          => in_array($scheme['font'][3], array('underline', 'bold-underline'), true) ? 'underline' : 'none',
    );
}

function ajnanda_customize_register($wp_customize) {
    if (class_exists('WP_Customize_Control') && !class_exists('NCLLC_Pro_Header_Font_Control')) {
        class NCLLC_Pro_Header_Font_Control extends WP_Customize_Control {
            public $type = 'ajnanda_header_font';

            public function render_content() {
                $manager = $this->manager;
                $family_setting = $manager->get_setting('header_font_family');
                $size_setting = $manager->get_setting('header_font_size');
                $color_setting = $manager->get_setting('header_text_color');
                $preset_setting = $manager->get_setting('header_font_preset');

                if (!$family_setting || !$size_setting || !$color_setting || !$preset_setting) {
                    return;
                }
                ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                <div class="ajnanda-header-font-control">
                    <label>
                        <span><?php esc_html_e('Font', 'ajnanda'); ?></span>
                        <select data-customize-setting-link="header_font_family">
                            <option value="inherit" <?php selected($family_setting->value(), 'inherit'); ?>><?php esc_html_e('Theme Default', 'ajnanda'); ?></option>
                            <option value="Inter" <?php selected($family_setting->value(), 'Inter'); ?>><?php esc_html_e('Inter', 'ajnanda'); ?></option>
                            <option value="Poppins" <?php selected($family_setting->value(), 'Poppins'); ?>><?php esc_html_e('Poppins', 'ajnanda'); ?></option>
                            <option value="Arial" <?php selected($family_setting->value(), 'Arial'); ?>><?php esc_html_e('Arial', 'ajnanda'); ?></option>
                            <option value="Georgia" <?php selected($family_setting->value(), 'Georgia'); ?>><?php esc_html_e('Georgia', 'ajnanda'); ?></option>
                            <option value="system-ui" <?php selected($family_setting->value(), 'system-ui'); ?>><?php esc_html_e('System UI', 'ajnanda'); ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?php esc_html_e('Size', 'ajnanda'); ?></span>
                        <input type="text" data-customize-setting-link="header_font_size" value="<?php echo esc_attr($size_setting->value()); ?>" placeholder="16px">
                    </label>
                    <label>
                        <span><?php esc_html_e('Color', 'ajnanda'); ?></span>
                        <input type="color" data-customize-setting-link="header_text_color" value="<?php echo esc_attr($color_setting->value()); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Style', 'ajnanda'); ?></span>
                        <select data-customize-setting-link="header_font_preset">
                            <option value="normal" <?php selected($preset_setting->value(), 'normal'); ?>><?php esc_html_e('Normal', 'ajnanda'); ?></option>
                            <option value="bold" <?php selected($preset_setting->value(), 'bold'); ?>><?php esc_html_e('Bold', 'ajnanda'); ?></option>
                            <option value="italic" <?php selected($preset_setting->value(), 'italic'); ?>><?php esc_html_e('Italic', 'ajnanda'); ?></option>
                            <option value="bold-italic" <?php selected($preset_setting->value(), 'bold-italic'); ?>><?php esc_html_e('Bold Italic', 'ajnanda'); ?></option>
                            <option value="underline" <?php selected($preset_setting->value(), 'underline'); ?>><?php esc_html_e('Underline', 'ajnanda'); ?></option>
                            <option value="bold-underline" <?php selected($preset_setting->value(), 'bold-underline'); ?>><?php esc_html_e('Bold Underline', 'ajnanda'); ?></option>
                        </select>
                    </label>
                </div>
                <?php
            }
        }

        class NCLLC_Pro_Header_Responsive_Value_Control extends WP_Customize_Control {
            public $type = 'ajnanda_header_responsive_value';
            public $setting_ids = array();
            public $device_labels = array();
            public $value_suffix = '';
            public $placeholder = '';

            public function render_content() {
                if (empty($this->setting_ids)) {
                    return;
                }
                ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                <div class="ajnanda-header-responsive-control" data-ajnanda-responsive-control>
                    <select class="ajnanda-header-responsive-device" aria-label="<?php esc_attr_e('Device', 'ajnanda'); ?>">
                        <?php foreach ($this->setting_ids as $device => $setting_id) : ?>
                            <?php if ($this->manager->get_setting($setting_id)) : ?>
                                <option value="<?php echo esc_attr($device); ?>"><?php echo esc_html(isset($this->device_labels[$device]) ? $this->device_labels[$device] : ucfirst($device)); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>

                    <div class="ajnanda-header-responsive-values">
                        <?php foreach ($this->setting_ids as $device => $setting_id) : ?>
                            <?php $setting = $this->manager->get_setting($setting_id); ?>
                            <?php if ($setting) : ?>
                                <label class="ajnanda-header-responsive-value" data-ajnanda-responsive-value="<?php echo esc_attr($device); ?>">
                                    <input type="text" data-customize-setting-link="<?php echo esc_attr($setting_id); ?>" value="<?php echo esc_attr($setting->value()); ?>" placeholder="<?php echo esc_attr($this->placeholder); ?>">
                                    <?php if ($this->value_suffix) : ?>
                                        <span><?php echo esc_html($this->value_suffix); ?></span>
                                    <?php endif; ?>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            }
        }

        class NCLLC_Pro_Header_Color_Schemes_Control extends WP_Customize_Control {
            public $type = 'ajnanda_header_color_schemes';

            public function render_content() {
                $schemes = ajnanda_header_color_scheme_labels();
                ?>
                <?php
                $saved_header_scheme = $this->manager->get_setting('header_color_scheme_picker');
                $saved_header_value  = $saved_header_scheme ? $saved_header_scheme->value() : '';
                ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                <select data-ajnanda-header-scheme-select data-customize-setting-link="header_color_scheme_picker">
                    <option value=""><?php esc_html_e('Choose a header scheme...', 'ajnanda'); ?></option>
                    <?php foreach ($schemes as $scheme_id => $scheme_label) : ?>
                        <option value="<?php echo esc_attr($scheme_id); ?>" <?php selected($saved_header_value, $scheme_id); ?>><?php echo esc_html($scheme_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php
            }
        }

        class NCLLC_Pro_Footer_Font_Control extends WP_Customize_Control {
            public $type = 'ajnanda_footer_font';

            public function render_content() {
                $manager        = $this->manager;
                $family_setting = $manager->get_setting('footer_font_family');
                $size_setting   = $manager->get_setting('footer_font_size');
                $color_setting  = $manager->get_setting('footer_text_color');
                $weight_setting = $manager->get_setting('footer_font_weight');

                if (!$family_setting || !$size_setting || !$color_setting || !$weight_setting) {
                    return;
                }
                ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                <div class="ajnanda-footer-font-control">
                    <label>
                        <span><?php esc_html_e('Font', 'ajnanda'); ?></span>
                        <select data-customize-setting-link="footer_font_family">
                            <option value="inherit" <?php selected($family_setting->value(), 'inherit'); ?>><?php esc_html_e('Theme Default', 'ajnanda'); ?></option>
                            <option value="Inter" <?php selected($family_setting->value(), 'Inter'); ?>><?php esc_html_e('Inter', 'ajnanda'); ?></option>
                            <option value="Poppins" <?php selected($family_setting->value(), 'Poppins'); ?>><?php esc_html_e('Poppins', 'ajnanda'); ?></option>
                            <option value="Arial" <?php selected($family_setting->value(), 'Arial'); ?>><?php esc_html_e('Arial', 'ajnanda'); ?></option>
                            <option value="Georgia" <?php selected($family_setting->value(), 'Georgia'); ?>><?php esc_html_e('Georgia', 'ajnanda'); ?></option>
                            <option value="system-ui" <?php selected($family_setting->value(), 'system-ui'); ?>><?php esc_html_e('System UI', 'ajnanda'); ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?php esc_html_e('Size', 'ajnanda'); ?></span>
                        <input type="text" data-customize-setting-link="footer_font_size" value="<?php echo esc_attr($size_setting->value()); ?>" placeholder="1rem">
                    </label>
                    <label>
                        <span><?php esc_html_e('Color', 'ajnanda'); ?></span>
                        <input type="color" data-customize-setting-link="footer_text_color" value="<?php echo esc_attr($color_setting->value()); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Weight', 'ajnanda'); ?></span>
                        <select data-customize-setting-link="footer_font_weight">
                            <option value="400" <?php selected($weight_setting->value(), '400'); ?>><?php esc_html_e('400 Normal', 'ajnanda'); ?></option>
                            <option value="500" <?php selected($weight_setting->value(), '500'); ?>><?php esc_html_e('500 Medium', 'ajnanda'); ?></option>
                            <option value="600" <?php selected($weight_setting->value(), '600'); ?>><?php esc_html_e('600 Semibold', 'ajnanda'); ?></option>
                            <option value="700" <?php selected($weight_setting->value(), '700'); ?>><?php esc_html_e('700 Bold', 'ajnanda'); ?></option>
                            <option value="800" <?php selected($weight_setting->value(), '800'); ?>><?php esc_html_e('800 Extrabold', 'ajnanda'); ?></option>
                        </select>
                    </label>
                </div>
                <?php
            }
        }

        class NCLLC_Pro_Footer_Color_Schemes_Control extends WP_Customize_Control {
            public $type = 'ajnanda_footer_color_schemes';

            public function render_content() {
                $schemes = ajnanda_header_color_scheme_labels();
                ?>
                <?php
                $saved_footer_scheme = $this->manager->get_setting('footer_color_scheme_picker');
                $saved_footer_value  = $saved_footer_scheme ? $saved_footer_scheme->value() : '';
                ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                <select data-ajnanda-footer-scheme-preview data-customize-setting-link="footer_color_scheme_picker">
                    <option value=""><?php esc_html_e('Choose a footer scheme...', 'ajnanda'); ?></option>
                    <?php foreach ($schemes as $scheme_id => $scheme_label) : ?>
                        <option value="<?php echo esc_attr($scheme_id); ?>" <?php selected($saved_footer_value, $scheme_id); ?>><?php echo esc_html($scheme_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php
            }
        }
    }

    $theme_color_controls = array(
        'theme_primary_color'      => array('label' => __('Primary Color', 'ajnanda'), 'default' => '#2563eb'),
        'theme_primary_dark_color' => array('label' => __('Primary Hover Color', 'ajnanda'), 'default' => '#1e40af'),
        'theme_secondary_color'    => array('label' => __('Secondary Color', 'ajnanda'), 'default' => '#7c3aed'),
        'theme_accent_color'       => array('label' => __('Accent Color', 'ajnanda'), 'default' => '#f59e0b'),
    );

    foreach ($theme_color_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ));

        if (class_exists('WP_Customize_Color_Control')) {
            $wp_customize->add_control(new WP_Customize_Color_Control(
                $wp_customize,
                $setting_id,
                array(
                    'label'   => $control['label'],
                    'section' => 'colors',
                )
            ));
        }
    }

    // Header Settings Section
    $wp_customize->add_section('ajnanda_header', array(
        'title'    => __('Header', 'ajnanda'),
        'priority' => 25,
    ));

    $wp_customize->add_setting('header_background_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control(
            $wp_customize,
            'header_background_color',
            array(
                'label'   => __('Header Background', 'ajnanda'),
                'section' => 'ajnanda_header',
            )
        ));
    }

    $wp_customize->add_setting('header_text_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $header_color_controls = array(
        'header_link_hover_color'        => array('label' => __('Header Link Hover Color', 'ajnanda'), 'default' => '#2563eb'),
        'header_link_hover_background'   => array('label' => __('Header Link Hover Background', 'ajnanda'), 'default' => '#f9fafb'),
        'header_submenu_background'      => array('label' => __('Header Submenu Background', 'ajnanda'), 'default' => '#ffffff'),
        'header_submenu_text_color'      => array('label' => __('Header Submenu Text Color', 'ajnanda'), 'default' => '#1f2937'),
        'header_submenu_hover_color'     => array('label' => __('Header Submenu Hover Text Color', 'ajnanda'), 'default' => '#2563eb'),
        'header_submenu_hover_background'=> array('label' => __('Header Submenu Hover Background', 'ajnanda'), 'default' => '#f9fafb'),
    );

    foreach ($header_color_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ));

        if (class_exists('WP_Customize_Color_Control')) {
            $wp_customize->add_control(new WP_Customize_Color_Control(
                $wp_customize,
                $setting_id,
                array(
                    'label'   => $control['label'],
                    'section' => 'ajnanda_header',
                )
            ));
        }
    }

    $wp_customize->add_setting('header_color_scheme_picker', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    if (class_exists('NCLLC_Pro_Header_Color_Schemes_Control')) {
        $wp_customize->add_control(new NCLLC_Pro_Header_Color_Schemes_Control(
            $wp_customize,
            'header_color_scheme_picker',
            array(
                'label'    => __('Header Color Schemes', 'ajnanda'),
                'section'  => 'ajnanda_header',
                'settings' => 'header_color_scheme_picker',
            )
        ));
    }

    $header_typography_controls = array(
        'header_font_family' => array(
            'label'    => __('Header Font Family', 'ajnanda'),
            'default'  => 'inherit',
            'type'     => 'select',
            'sanitize' => 'ajnanda_sanitize_font_family',
            'choices'  => array(
                'inherit'   => __('Theme Default', 'ajnanda'),
                'Inter'     => __('Inter', 'ajnanda'),
                'Poppins'   => __('Poppins', 'ajnanda'),
                'Arial'     => __('Arial', 'ajnanda'),
                'Georgia'   => __('Georgia', 'ajnanda'),
                'system-ui' => __('System UI', 'ajnanda'),
            ),
        ),
        'header_font_size' => array(
            'label'    => __('Header Text Size', 'ajnanda'),
            'default'  => '1rem',
            'type'     => 'text',
            'sanitize' => 'ajnanda_sanitize_css_size',
        ),
        'header_font_weight' => array(
            'label'    => __('Header Font Weight', 'ajnanda'),
            'default'  => '500',
            'type'     => 'select',
            'sanitize' => 'ajnanda_sanitize_font_weight',
            'choices'  => array('400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800'),
        ),
        'header_menu_gap' => array(
            'label'    => __('Header Menu Gap', 'ajnanda'),
            'default'  => '2rem',
            'type'     => 'text',
            'sanitize' => 'ajnanda_sanitize_css_size',
        ),
        'header_container_width' => array(
            'label'    => __('Header Container Width', 'ajnanda'),
            'default'  => '1400px',
            'type'     => 'select',
            'sanitize' => 'ajnanda_sanitize_choice',
            'choices'  => array(
                '1120px' => __('Compact', 'ajnanda'),
                '1400px' => __('Auto', 'ajnanda'),
                '1600px' => __('Wide', 'ajnanda'),
                '100%'   => __('Full Width', 'ajnanda'),
                '100vw'  => __('Full Screen', 'ajnanda'),
            ),
        ),
        'header_shadow_opacity' => array(
            'label'    => __('Header Shadow Opacity', 'ajnanda'),
            'default'  => '0.10',
            'type'     => 'number',
            'sanitize' => 'ajnanda_sanitize_opacity',
        ),
    );

    foreach ($header_typography_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => $control['sanitize'],
            'transport'         => 'refresh',
        ));

        if (in_array($setting_id, array('header_font_family', 'header_font_size', 'header_font_weight'), true)) {
            continue;
        }

        if ('header_shadow_opacity' === $setting_id) {
            continue;
        }

        $args = array(
            'label'       => $control['label'],
            'section'     => 'ajnanda_header',
            'type'        => $control['type'],
            'description' => 'text' === $control['type'] ? __('Examples: 1rem, 16px, 2rem.', 'ajnanda') : '',
        );

        if (!empty($control['choices'])) {
            $args['choices'] = $control['choices'];
        }

        if ('number' === $control['type']) {
            $args['input_attrs'] = array('min' => 0, 'max' => 1, 'step' => 0.05);
        }

        $wp_customize->add_control($setting_id, $args);
    }

    $wp_customize->add_setting('header_font_preset', array(
        'default'           => 'normal',
        'sanitize_callback' => 'ajnanda_sanitize_header_font_preset',
        'transport'         => 'refresh',
    ));

    if (class_exists('NCLLC_Pro_Header_Font_Control')) {
        $wp_customize->add_control(new NCLLC_Pro_Header_Font_Control(
            $wp_customize,
            'header_font_compact',
            array(
                'label'    => __('Header Font', 'ajnanda'),
                'section'  => 'ajnanda_header',
                'settings' => array(
                    'header_font_family',
                    'header_font_size',
                    'header_text_color',
                    'header_font_preset',
                ),
            )
        ));
    }

    /*
     * NOTE (architectural conflict, intentionally left as-is): this is one
     * of two independent sticky-header switches in the theme. The other
     * lives in Appearance -> Menus -> Menu Visibility Toggles
     * (AJNANDA_MENU_TOGGLES_OPTION['top_navigation_sticky'], applied in
     * ajnanda_menu_toggles_css()). Either one alone is enough to make the
     * header sticky; turning this one off does not override the other.
     * They were not merged here to avoid changing behavior on existing
     * sites that already rely on one or the other.
     */
    $wp_customize->add_setting('header_sticky', array(
        'default'           => true,
        'sanitize_callback' => 'ajnanda_sanitize_checkbox',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('header_sticky', array(
        'label'   => __('Sticky Header', 'ajnanda'),
        'section' => 'ajnanda_header',
        'type'    => 'checkbox',
    ));

    $wp_customize->add_setting('ajn_mobile_menu_style', array(
        'default'           => 'slide',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_mobile_menu_style', array(
        'label'       => __('Mobile Menu Style', 'ajnanda'),
        'description' => __('Slide Down opens a small panel under the header. Full Overlay opens a full-screen menu.', 'ajnanda'),
        'section'     => 'ajnanda_header',
        'type'        => 'select',
        'choices'     => array(
            'slide'   => __('Slide Down', 'ajnanda'),
            'overlay' => __('Full Overlay', 'ajnanda'),
        ),
    ));

    $wp_customize->add_setting('header_layout', array(
        'default'           => 'logo-left-menu-right',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('header_layout', array(
        'label'   => __('Header Layout', 'ajnanda'),
        'section' => 'ajnanda_header',
        'type'    => 'select',
        'choices' => array(
            'logo-left-menu-right' => __('Logo Left, Menu Right', 'ajnanda'),
            'centered-menu'        => __('Centered Menu Bar', 'ajnanda'),
            'stacked-center'       => __('Centered Logo, Menu Below', 'ajnanda'),
            'builder'              => __('Builder', 'ajnanda'),
        ),
    ));

    $old_logo_height = get_theme_mod('logo_height', '50');
    $old_header_padding = get_theme_mod('header_padding', '0.75');
    $device_labels = array(
        'desktop' => __('Desktop', 'ajnanda'),
        'tablet'  => __('Tablet', 'ajnanda'),
        'mobile'  => __('Mobile', 'ajnanda'),
    );

    foreach ($device_labels as $device => $label) {
        $logo_setting = 'logo_height_' . $device;

        $wp_customize->add_setting($logo_setting, array(
            'default'           => $old_logo_height,
            'sanitize_callback' => 'ajnanda_sanitize_logo_height',
            'transport'         => 'postMessage',
        ));
    }

    $wp_customize->add_setting('logo_vertical_align', array(
        'default'           => 'center',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('logo_height_desktop_site_identity', array(
        'label'       => __('Logo Height – Desktop (px)', 'ajnanda'),
        'section'     => 'title_tagline',
        'settings'    => 'logo_height_desktop',
        'type'        => 'number',
        'input_attrs' => array('min' => 10, 'max' => 300, 'step' => 1),
        'priority'    => 20,
    ));

    $wp_customize->add_control('logo_height_mobile_site_identity', array(
        'label'       => __('Logo Height – Mobile (px)', 'ajnanda'),
        'section'     => 'title_tagline',
        'settings'    => 'logo_height_mobile',
        'type'        => 'number',
        'input_attrs' => array('min' => 10, 'max' => 300, 'step' => 1),
        'priority'    => 21,
    ));

    $wp_customize->add_control('logo_vertical_align', array(
        'label'    => __('Logo Vertical Alignment', 'ajnanda'),
        'section'  => 'title_tagline',
        'type'     => 'select',
        'choices'  => array(
            'flex-start' => __('Top', 'ajnanda'),
            'center'     => __('Center', 'ajnanda'),
            'flex-end'   => __('Bottom', 'ajnanda'),
        ),
        'priority' => 22,
    ));

    /*
     * Generic branding extras: an optional short badge/mark next to the
     * logo, and an optional tagline under it. Both are opt-in (empty by
     * default) so existing sites keep their current header/footer
     * branding output until a site owner sets them. Rendered from
     * ajnanda_render_builder_site_identity(), which is shared by the
     * header, the header/footer builder "site-logo" cell, and the footer
     * branding cell — one setting affects every place the logo appears.
     */
    $wp_customize->add_setting('ajn_branding_badge_text', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_branding_badge_text', array(
        'label'       => __('Branding Badge/Mark (optional)', 'ajnanda'),
        'description' => __('A short mark shown next to the logo, e.g. initials or "Est. 2020". Leave blank to hide.', 'ajnanda'),
        'section'     => 'title_tagline',
        'type'        => 'text',
        'priority'    => 23,
    ));

    $wp_customize->add_setting('ajn_branding_show_tagline', array(
        'default'           => false,
        'sanitize_callback' => 'ajnanda_sanitize_checkbox',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_branding_show_tagline', array(
        'label'       => __('Show Site Tagline Under Logo', 'ajnanda'),
        'description' => __('Uses the tagline set above (Site Identity).', 'ajnanda'),
        'section'     => 'title_tagline',
        'type'        => 'checkbox',
        'priority'    => 24,
    ));

    foreach ($device_labels as $device => $label) {
        $padding_setting = 'header_padding_' . $device;

        $wp_customize->add_setting($padding_setting, array(
            'default'           => $old_header_padding,
            'sanitize_callback' => 'ajnanda_sanitize_header_padding',
            'transport'         => 'postMessage',
        ));
    }

    foreach ($device_labels as $device => $label) {
        $height_setting = 'header_height_' . $device;

        $wp_customize->add_setting($height_setting, array(
            'default'           => 'auto',
            'sanitize_callback' => 'ajnanda_sanitize_css_size_or_auto',
            'transport'         => 'postMessage',
        ));
    }

    if (class_exists('NCLLC_Pro_Header_Responsive_Value_Control')) {
        $wp_customize->add_control(new NCLLC_Pro_Header_Responsive_Value_Control(
            $wp_customize,
            'header_logo_height_compact',
            array(
                'label'         => __('Logo Height', 'ajnanda'),
                'section'       => 'ajnanda_header',
                'settings'      => array('logo_height_desktop', 'logo_height_tablet', 'logo_height_mobile'),
                'setting_ids'   => array(
                    'desktop' => 'logo_height_desktop',
                    'tablet'  => 'logo_height_tablet',
                    'mobile'  => 'logo_height_mobile',
                ),
                'device_labels' => array(
                    'desktop' => __('Logo Height - Desktop (px)', 'ajnanda'),
                    'tablet'  => __('Logo Height - Tablet (px)', 'ajnanda'),
                    'mobile'  => __('Logo Height - Mobile (px)', 'ajnanda'),
                ),
                'value_suffix'  => __('px', 'ajnanda'),
                'placeholder'   => '50',
            )
        ));

        $wp_customize->add_control(new NCLLC_Pro_Header_Responsive_Value_Control(
            $wp_customize,
            'header_padding_compact',
            array(
                'label'         => __('Header Padding', 'ajnanda'),
                'section'       => 'ajnanda_header',
                'settings'      => array('header_padding_desktop', 'header_padding_tablet', 'header_padding_mobile'),
                'setting_ids'   => array(
                    'desktop' => 'header_padding_desktop',
                    'tablet'  => 'header_padding_tablet',
                    'mobile'  => 'header_padding_mobile',
                ),
                'device_labels' => array(
                    'desktop' => __('Header Padding - Desktop (rem)', 'ajnanda'),
                    'tablet'  => __('Header Padding - Tablet (rem)', 'ajnanda'),
                    'mobile'  => __('Header Padding - Mobile (rem)', 'ajnanda'),
                ),
                'value_suffix'  => __('rem', 'ajnanda'),
                'placeholder'   => '0.75',
            )
        ));

        $wp_customize->add_control(new NCLLC_Pro_Header_Responsive_Value_Control(
            $wp_customize,
            'header_height_compact',
            array(
                'label'         => __('Header Height', 'ajnanda'),
                'section'       => 'ajnanda_header',
                'settings'      => array('header_height_desktop', 'header_height_tablet', 'header_height_mobile'),
                'setting_ids'   => array(
                    'desktop' => 'header_height_desktop',
                    'tablet'  => 'header_height_tablet',
                    'mobile'  => 'header_height_mobile',
                ),
                'device_labels' => array(
                    'desktop' => __('Header Height - Desktop', 'ajnanda'),
                    'tablet'  => __('Header Height - Tablet', 'ajnanda'),
                    'mobile'  => __('Header Height - Mobile', 'ajnanda'),
                ),
                'placeholder'   => 'auto, 80px, 5rem',
            )
        ));
    }

    ajnanda_register_builder_controls($wp_customize, 'header', 'ajnanda_header', __('Header', 'ajnanda'));
    ajnanda_register_header_builder_extras($wp_customize);

    // Navigation panel settings moved to Appearance → Menus (ajnanda_menu_toggles option).

    // Hero Defaults Section
    $wp_customize->add_section('ajnanda_hero_defaults', array(
        'title'       => __('Hero Defaults', 'ajnanda'),
        'priority'    => 26,
        'description' => __('Default hero design for editable page/post hero blocks.', 'ajnanda'),
    ));

    // Hero bg defaults follow the site's own brand colors (falling back to the
    // theme's stock blue/purple only if those are untouched too), so a site
    // that customized theme_primary_color/theme_secondary_color doesn't get a
    // hero that silently stays on the stock palette until hero_bg_1/hero_bg_2
    // are set explicitly.
    $hero_color_controls = array(
        'hero_bg_1' => array('label' => __('Hero Background Color 1', 'ajnanda'), 'default' => get_theme_mod('theme_primary_color', '#2563eb')),
        'hero_bg_2' => array('label' => __('Hero Background Color 2', 'ajnanda'), 'default' => get_theme_mod('theme_secondary_color', '#7c3aed')),
        'hero_heading_color' => array('label' => __('Hero Heading Color', 'ajnanda'), 'default' => '#ffffff'),
        'hero_subtitle_color' => array('label' => __('Hero Subtitle Color', 'ajnanda'), 'default' => 'rgba(255,255,255,0.94)'),
        'hero_badge_bg' => array('label' => __('Hero Badge Background', 'ajnanda'), 'default' => 'rgba(255,255,255,0.16)'),
        'hero_badge_text_color' => array('label' => __('Hero Badge Text Color', 'ajnanda'), 'default' => '#ffffff'),
        'hero_button_bg' => array('label' => __('Hero Primary Button Background', 'ajnanda'), 'default' => '#ffffff'),
        'hero_button_text_color' => array('label' => __('Hero Primary Button Text Color', 'ajnanda'), 'default' => '#2563eb'),
    );

    foreach ($hero_color_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'ajnanda_sanitize_css_color',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($setting_id, array(
            'label'       => $control['label'],
            'section'     => 'ajnanda_hero_defaults',
            'type'        => 'text',
            'description' => __('Use #2563eb or rgba(255,255,255,0.94).', 'ajnanda'),
        ));
    }

    $hero_size_controls = array(
        'hero_min_height_desktop' => array('label' => __('Hero Minimum Height - Desktop', 'ajnanda'), 'default' => '50px'),
        'hero_min_height_tablet' => array('label' => __('Hero Minimum Height - Tablet', 'ajnanda'), 'default' => '50px'),
        'hero_min_height_mobile' => array('label' => __('Hero Minimum Height - Mobile', 'ajnanda'), 'default' => '50px'),
        'hero_padding_top_desktop' => array('label' => __('Hero Padding Top - Desktop', 'ajnanda'), 'default' => '1rem'),
        'hero_padding_bottom_desktop' => array('label' => __('Hero Padding Bottom - Desktop', 'ajnanda'), 'default' => '1rem'),
        'hero_padding_top_tablet' => array('label' => __('Hero Padding Top - Tablet', 'ajnanda'), 'default' => '1rem'),
        'hero_padding_bottom_tablet' => array('label' => __('Hero Padding Bottom - Tablet', 'ajnanda'), 'default' => '1rem'),
        'hero_padding_top_mobile' => array('label' => __('Hero Padding Top - Mobile', 'ajnanda'), 'default' => '1rem'),
        'hero_padding_bottom_mobile' => array('label' => __('Hero Padding Bottom - Mobile', 'ajnanda'), 'default' => '1rem'),
    );

    foreach ($hero_size_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'ajnanda_sanitize_css_size',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($setting_id, array(
            'label'       => $control['label'],
            'section'     => 'ajnanda_hero_defaults',
            'type'        => 'text',
            'description' => __('Examples: 50px, 1rem, 60vh. Plain numbers save as px.', 'ajnanda'),
        ));
    }

    // Footer Section
    $wp_customize->add_section('ajnanda_footer', array(
        'title'    => __('Footer', 'ajnanda'),
        'priority' => 26,
    ));


    $wp_customize->add_setting('footer_background_color', array(
        'default'           => '#111827',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control(
            $wp_customize,
            'footer_background_color',
            array(
                'label'   => __('Footer Background', 'ajnanda'),
                'section' => 'ajnanda_footer',
            )
        ));
    }

    $wp_customize->add_setting('footer_text_color', array(
        'default'           => '#f9fafb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control(
            $wp_customize,
            'footer_text_color',
            array(
                'label'   => __('Footer Text Color', 'ajnanda'),
                'section' => 'ajnanda_footer',
            )
        ));
    }

    $footer_color_controls = array(
        'footer_link_hover_color'         => array('label' => __('Footer Link Hover Color', 'ajnanda'), 'default' => '#f59e0b'),
        'footer_divider_color'            => array('label' => __('Footer Divider Color', 'ajnanda'), 'default' => '#374151'),
        'footer_submenu_background'       => array('label' => __('Footer Submenu Background', 'ajnanda'), 'default' => '#ffffff'),
        'footer_submenu_text_color'       => array('label' => __('Footer Submenu Text Color', 'ajnanda'), 'default' => '#1f2937'),
        'footer_submenu_hover_color'      => array('label' => __('Footer Submenu Hover Text Color', 'ajnanda'), 'default' => '#2563eb'),
        'footer_submenu_hover_background' => array('label' => __('Footer Submenu Hover Background', 'ajnanda'), 'default' => '#f9fafb'),
    );

    foreach ($footer_color_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        if (class_exists('WP_Customize_Color_Control')) {
            $wp_customize->add_control(new WP_Customize_Color_Control(
                $wp_customize,
                $setting_id,
                array(
                    'label'   => $control['label'],
                    'section' => 'ajnanda_footer',
                )
            ));
        }
    }

    $wp_customize->add_setting('footer_color_scheme_picker', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    if (class_exists('NCLLC_Pro_Footer_Color_Schemes_Control')) {
        $wp_customize->add_control(new NCLLC_Pro_Footer_Color_Schemes_Control(
            $wp_customize,
            'footer_color_scheme_picker',
            array(
                'label'   => __('Footer Color Schemes', 'ajnanda'),
                'section' => 'ajnanda_footer',
            )
        ));
    }

    $footer_typography_controls = array(
        'footer_font_family' => array(
            'label'    => __('Footer Font Family', 'ajnanda'),
            'default'  => 'inherit',
            'type'     => 'select',
            'sanitize' => 'ajnanda_sanitize_font_family',
            'choices'  => array(
                'inherit'   => __('Theme Default', 'ajnanda'),
                'Inter'     => __('Inter', 'ajnanda'),
                'Poppins'   => __('Poppins', 'ajnanda'),
                'Arial'     => __('Arial', 'ajnanda'),
                'Georgia'   => __('Georgia', 'ajnanda'),
                'system-ui' => __('System UI', 'ajnanda'),
            ),
        ),
        'footer_font_size' => array(
            'label'    => __('Footer Text Size', 'ajnanda'),
            'default'  => '1rem',
            'type'     => 'text',
            'sanitize' => 'ajnanda_sanitize_css_size',
        ),
        'footer_font_weight' => array(
            'label'    => __('Footer Font Weight', 'ajnanda'),
            'default'  => '400',
            'type'     => 'select',
            'sanitize' => 'ajnanda_sanitize_font_weight',
            'choices'  => array('400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800'),
        ),
        // No standalone control: like the other compact keys below, these
        // are only ever set by picking a Footer Color Scheme (its font[3]
        // preset, e.g. "bold-underline", now carries italic/underline
        // through instead of that part being silently dropped) — see
        // ajnanda_footer_settings_from_header_scheme(). Manual entry isn't
        // exposed yet, so they default to matching the header's own
        // pre-preset defaults (normal / none).
        'footer_font_style' => array(
            'label'    => __('Footer Font Style', 'ajnanda'),
            'default'  => 'normal',
            'type'     => 'hidden',
            'sanitize' => 'ajnanda_sanitize_footer_font_style',
        ),
        'footer_text_decoration' => array(
            'label'    => __('Footer Text Decoration', 'ajnanda'),
            'default'  => 'none',
            'type'     => 'hidden',
            'sanitize' => 'ajnanda_sanitize_footer_text_decoration',
        ),
        'footer_menu_gap' => array(
            'label'    => __('Footer Menu Gap', 'ajnanda'),
            'default'  => '1.4rem',
            'type'     => 'text',
            'sanitize' => 'ajnanda_sanitize_css_size',
        ),
        'footer_container_width' => array(
            'label'    => __('Footer Container Width', 'ajnanda'),
            'default'  => '1280px',
            'type'     => 'text',
            'sanitize' => 'ajnanda_sanitize_css_size',
        ),
    );

    $footer_font_compact_keys = array('footer_font_family', 'footer_font_size', 'footer_font_weight', 'footer_font_style', 'footer_text_decoration');

    foreach ($footer_typography_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => $control['sanitize'],
            'transport'         => in_array($setting_id, $footer_font_compact_keys, true) ? 'postMessage' : 'refresh',
        ));

        if (in_array($setting_id, $footer_font_compact_keys, true)) {
            continue;
        }

        $args = array(
            'label'       => $control['label'],
            'section'     => 'ajnanda_footer',
            'type'        => $control['type'],
            'description' => 'text' === $control['type'] ? __('Examples: 1rem, 16px, 2rem.', 'ajnanda') : '',
        );

        if (!empty($control['choices'])) {
            $args['choices'] = $control['choices'];
        }

        $wp_customize->add_control($setting_id, $args);
    }

    if (class_exists('NCLLC_Pro_Footer_Font_Control')) {
        $wp_customize->add_control(new NCLLC_Pro_Footer_Font_Control(
            $wp_customize,
            'footer_font_family',
            array(
                'label'    => __('Footer Font', 'ajnanda'),
                'section'  => 'ajnanda_footer',
                'settings' => 'footer_font_family',
            )
        ));
    }

    // Responsive footer padding
    $footer_responsive_devices = array('desktop', 'tablet', 'mobile');
    $footer_padding_top_defaults    = array('desktop' => '4rem', 'tablet' => '3rem',   'mobile' => '2.5rem');
    $footer_padding_bottom_defaults = array('desktop' => '2rem', 'tablet' => '1.5rem', 'mobile' => '1.5rem');

    foreach ($footer_responsive_devices as $device) {
        $wp_customize->add_setting('footer_padding_top_' . $device, array(
            'default'           => $footer_padding_top_defaults[$device],
            'sanitize_callback' => 'ajnanda_sanitize_css_size',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_setting('footer_padding_bottom_' . $device, array(
            'default'           => $footer_padding_bottom_defaults[$device],
            'sanitize_callback' => 'ajnanda_sanitize_css_size',
            'transport'         => 'postMessage',
        ));
    }

    if (class_exists('NCLLC_Pro_Header_Responsive_Value_Control')) {
        $wp_customize->add_control(new NCLLC_Pro_Header_Responsive_Value_Control(
            $wp_customize,
            'footer_padding_top_compact',
            array(
                'label'         => __('Footer Padding Top', 'ajnanda'),
                'section'       => 'ajnanda_footer',
                'settings'      => array('footer_padding_top_desktop', 'footer_padding_top_tablet', 'footer_padding_top_mobile'),
                'setting_ids'   => array(
                    'desktop' => 'footer_padding_top_desktop',
                    'tablet'  => 'footer_padding_top_tablet',
                    'mobile'  => 'footer_padding_top_mobile',
                ),
                'device_labels' => array(
                    'desktop' => __('Padding Top - Desktop', 'ajnanda'),
                    'tablet'  => __('Padding Top - Tablet', 'ajnanda'),
                    'mobile'  => __('Padding Top - Mobile', 'ajnanda'),
                ),
                'placeholder'   => '4rem, 3rem, 2.5rem',
            )
        ));

        $wp_customize->add_control(new NCLLC_Pro_Header_Responsive_Value_Control(
            $wp_customize,
            'footer_padding_bottom_compact',
            array(
                'label'         => __('Footer Padding Bottom', 'ajnanda'),
                'section'       => 'ajnanda_footer',
                'settings'      => array('footer_padding_bottom_desktop', 'footer_padding_bottom_tablet', 'footer_padding_bottom_mobile'),
                'setting_ids'   => array(
                    'desktop' => 'footer_padding_bottom_desktop',
                    'tablet'  => 'footer_padding_bottom_tablet',
                    'mobile'  => 'footer_padding_bottom_mobile',
                ),
                'device_labels' => array(
                    'desktop' => __('Padding Bottom - Desktop', 'ajnanda'),
                    'tablet'  => __('Padding Bottom - Tablet', 'ajnanda'),
                    'mobile'  => __('Padding Bottom - Mobile', 'ajnanda'),
                ),
                'placeholder'   => '2rem, 1.5rem, 1.5rem',
            )
        ));
    }

    $wp_customize->add_setting('ajn_builder_button_text', array(
        'default'           => __('Contact Us', 'ajnanda'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_text', array(
        'label'           => __('Header Button Text', 'ajnanda'),
        'description'     => __('Shown only when Button 1 is added to the Header Builder.', 'ajnanda'),
        'section'         => 'ajnanda_header',
        'type'            => 'text',
        'active_callback' => 'ajnanda_header_builder_button_1_active',
    ));

    $wp_customize->add_setting('ajn_builder_button_url', array(
        'default'           => home_url('/contact/'),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_url', array(
        'label'           => __('Header Button URL', 'ajnanda'),
        'description'     => __('Shown only when Button 1 is added to the Header Builder.', 'ajnanda'),
        'section'         => 'ajnanda_header',
        'type'            => 'url',
        'active_callback' => 'ajnanda_header_builder_button_1_active',
    ));

    $wp_customize->add_setting('ajn_builder_button_style', array(
        'default'           => 'solid',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_style', array(
        'label'           => __('Header Button Style', 'ajnanda'),
        'description'     => __('Shown only when Button 1 is added to the Header Builder.', 'ajnanda'),
        'section'         => 'ajnanda_header',
        'type'            => 'select',
        'choices'         => array(
            'solid'   => __('Solid', 'ajnanda'),
            'outline' => __('Outline', 'ajnanda'),
            'pill'    => __('Pill', 'ajnanda'),
        ),
        'active_callback' => 'ajnanda_header_builder_button_1_active',
    ));

    $wp_customize->add_setting('ajn_footer_builder_button_text', array(
        'default'           => __('Contact Us', 'ajnanda'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_footer_builder_button_text', array(
        'label'           => __('Button 1 Text', 'ajnanda'),
        'section'         => 'ajnanda_footer',
        'type'            => 'text',
        'active_callback' => 'ajnanda_footer_builder_button_1_active',
    ));

    $wp_customize->add_setting('ajn_footer_builder_button_url', array(
        'default'           => home_url('/contact/'),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_footer_builder_button_url', array(
        'label'           => __('Button 1 URL', 'ajnanda'),
        'section'         => 'ajnanda_footer',
        'type'            => 'url',
        'active_callback' => 'ajnanda_footer_builder_button_1_active',
    ));

    $wp_customize->add_setting('ajn_footer_builder_button_style', array(
        'default'           => 'solid',
        'sanitize_callback' => 'ajnanda_sanitize_choice',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_footer_builder_button_style', array(
        'label'           => __('Button 1 Style', 'ajnanda'),
        'section'         => 'ajnanda_footer',
        'type'            => 'select',
        'choices'         => array(
            'solid'   => __('Solid', 'ajnanda'),
            'outline' => __('Outline', 'ajnanda'),
            'pill'    => __('Pill', 'ajnanda'),
        ),
        'active_callback' => 'ajnanda_footer_builder_button_1_active',
    ));

    $wp_customize->add_setting('ajn_builder_button_2_text', array(
        'default'           => __('Learn More', 'ajnanda'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_2_text', array(
        'label'           => __('Button 2 Text', 'ajnanda'),
        'section'         => 'ajnanda_footer',
        'type'            => 'text',
        'active_callback' => 'ajnanda_footer_builder_button_2_active',
    ));

    $wp_customize->add_setting('ajn_builder_button_2_url', array(
        'default'           => home_url('/contact/'),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_2_url', array(
        'label'           => __('Button 2 URL', 'ajnanda'),
        'section'         => 'ajnanda_footer',
        'type'            => 'url',
        'active_callback' => 'ajnanda_footer_builder_button_2_active',
    ));

    $wp_customize->add_setting('ajn_builder_html_1', array(
        'default'           => get_bloginfo('description'),
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_html_1', array(
        'label'           => __('Builder HTML 1', 'ajnanda'),
        'section'         => 'ajnanda_header',
        'type'            => 'textarea',
        'active_callback' => 'ajnanda_header_builder_html_1_active',
    ));

    $wp_customize->add_setting('ajn_builder_html_2', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_html_2', array(
        'label'           => __('HTML 2', 'ajnanda'),
        'section'         => 'ajnanda_footer',
        'type'            => 'textarea',
        'active_callback' => 'ajnanda_footer_builder_html_2_active',
    ));

    $wp_customize->add_setting('ajn_builder_social_1_label', array(
        'default'           => __('Social', 'ajnanda'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_social_1_label', array(
        'label'           => __('Social Label', 'ajnanda'),
        'section'         => 'ajnanda_header',
        'type'            => 'text',
        'active_callback' => 'ajnanda_header_builder_social_active',
    ));

    $wp_customize->add_setting('ajn_builder_social_1_url', array(
        'default'           => '#',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_social_1_url', array(
        'label'           => __('Social URL', 'ajnanda'),
        'section'         => 'ajnanda_header',
        'type'            => 'url',
        'active_callback' => 'ajnanda_header_builder_social_active',
    ));

    ajnanda_register_builder_controls($wp_customize, 'footer', 'ajnanda_footer', __('Footer', 'ajnanda'));

    $footer_columns = ajnanda_get_footer_columns();
    foreach ($footer_columns as $index => $column) {
        $wp_customize->add_setting('footer_column_' . $index . '_title', array(
            'default'           => $column['title'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control('footer_column_' . $index . '_title', array(
            'label'   => sprintf(__('Footer Column %d Title', 'ajnanda'), $index),
            'section' => 'ajnanda_footer',
            'type'    => 'text',
            'active_callback' => '__return_false',
        ));

        $wp_customize->add_setting('footer_column_' . $index . '_text', array(
            'default'           => $column['text'],
            'sanitize_callback' => 'ajnanda_sanitize_textarea',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control('footer_column_' . $index . '_text', array(
            'label'       => sprintf(__('Footer Column %d Text', 'ajnanda'), $index),
            'description' => __('One item per line. Use Label|URL for links.', 'ajnanda'),
            'section'     => 'ajnanda_footer',
            'type'        => 'textarea',
            'active_callback' => '__return_false',
        ));
    }

    $wp_customize->add_setting('footer_bottom_text', array(
        'default'           => ajnanda_get_footer_bottom_default(),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('footer_bottom_text', array(
        'label'           => __('Copyright Text', 'ajnanda'),
        'section'         => 'ajnanda_footer',
        'type'            => 'text',
        'active_callback' => 'ajnanda_footer_builder_copyright_active',
    ));

    if (isset($wp_customize->selective_refresh)) {
        $footer_settings = array(
            'footer_bottom_text',
            'ajn_footer_builder_button_text',
            'ajn_footer_builder_button_url',
            'ajn_builder_button_2_text',
            'ajn_builder_button_2_url',
            'ajn_builder_html_2',
            'ajn_builder_social_1_label',
            'ajn_builder_social_1_url',
            ajnanda_builder_row_count_setting_id('footer'),
        );
        for ($i = 1; $i <= 4; $i++) {
            $footer_settings[] = 'footer_column_' . $i . '_title';
            $footer_settings[] = 'footer_column_' . $i . '_text';
        }
        for ($row = 1; $row <= 6; $row++) {
            $footer_settings[] = ajnanda_builder_row_columns_setting_id('footer', $row);
            for ($cell = 1; $cell <= 4; $cell++) {
                $footer_settings[] = ajnanda_builder_setting_id('footer', $row, $cell);
            }
        }

        $wp_customize->selective_refresh->add_partial('ajnanda_footer_partial', array(
            'selector'        => '.site-footer',
            'settings'        => $footer_settings,
            'render_callback' => 'ajnanda_render_site_footer',
        ));
    }
    
    // ── Post Meta Display ───────────────────────────────────────────────────
    // Theme-native replacement for the "WP Meta and Date Remover" plugin — same toggles
    // (date / author / read time / category), read via get_theme_mod() at the render sites in
    // single.php and the blog listing in index.php, instead of a third-party plugin filtering
    // output at runtime. Defaults all false (unchecked/hidden) — explicit decision to match the
    // site's actual desired state (author/date/read-time/category hidden), matching what the old
    // plugin enforced.
    $wp_customize->add_section('ajnanda_post_meta', array(
        'title'    => __('Post Meta Display', 'ajnanda'),
        'priority' => 27,
    ));

    $post_meta_toggles = array(
        'post_meta_show_date'      => __('Show Date', 'ajnanda'),
        'post_meta_show_author'    => __('Show Author', 'ajnanda'),
        'post_meta_show_read_time' => __('Show Read Time', 'ajnanda'),
        'post_meta_show_category'  => __('Show Category', 'ajnanda'),
    );

    foreach ($post_meta_toggles as $setting_id => $label) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => false,
            'sanitize_callback' => 'ajnanda_sanitize_checkbox',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($setting_id, array(
            'label'   => $label,
            'section' => 'ajnanda_post_meta',
            'type'    => 'checkbox',
        ));
    }

    // ── Related Posts & Navigation ──────────────────────────────────────────
    // Controls for the two blocks that appear below a single post's content: the
    // "Related Articles" grid and the prev/next "Post Navigation" links. Both are
    // core-ish, always-on theme behavior today (no admin control existed before this),
    // so — unlike the Post Meta Display toggles above — these default to true
    // (shown), preserving current behavior until an admin opts out.
    $wp_customize->add_section('ajnanda_related_nav', array(
        'title'    => __('Related Posts & Navigation', 'ajnanda'),
        'priority' => 28,
    ));

    $related_nav_toggles = array(
        'show_related_articles' => __('Show Related Articles', 'ajnanda'),
        'show_post_navigation'  => __('Show Previous & Next Navigation', 'ajnanda'),
    );

    foreach ($related_nav_toggles as $setting_id => $label) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => true,
            'sanitize_callback' => 'ajnanda_sanitize_checkbox',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($setting_id, array(
            'label'   => $label,
            'section' => 'ajnanda_related_nav',
            'type'    => 'checkbox',
        ));
    }

    // ── Comments ─────────────────────────────────────────────────────────────
    // Theme-native replacement for manually visiting Settings → Discussion and
    // unchecking "Allow people to submit comments on new posts" every time the theme
    // is set up on a new site. Defaults to false (disabled) — comments are off out of
    // the box; an admin who wants them opts in here instead of remembering a separate
    // core settings screen. ajnanda_comments_open_filter() below enforces this
    // site-wide (blocks new submissions too, not just the single.php template render).
    $wp_customize->add_section('ajnanda_comments', array(
        'title'    => __('Comments', 'ajnanda'),
        'priority' => 29,
    ));

    $wp_customize->add_setting('enable_comments', array(
        'default'           => false,
        'sanitize_callback' => 'ajnanda_sanitize_checkbox',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('enable_comments', array(
        'label'       => __('Enable Comments', 'ajnanda'),
        'description' => __('Off by default. Turn on to allow visitors to view and submit comments on posts.', 'ajnanda'),
        'section'     => 'ajnanda_comments',
        'type'        => 'checkbox',
    ));

    // Add live preview JavaScript
    if ($wp_customize->is_preview()) {
        add_action('wp_footer', 'ajnanda_customizer_live_preview', 21);
    }

}
add_action('customize_register', 'ajnanda_customize_register');

// Enforce the "Enable Comments" customizer toggle site-wide: when off (the default),
// comments (and pingbacks/trackbacks) read as closed everywhere WordPress core checks
// comments_open()/pings_open() — including wp-comments-post.php's own submission guard
// and the XML-RPC pingback handler — not just in the single.php template.
add_filter('comments_open', 'ajnanda_comments_open_filter', 10, 2);
add_filter('pings_open', 'ajnanda_comments_open_filter', 10, 2);
function ajnanda_comments_open_filter($open, $post_id) {
    if (!get_theme_mod('enable_comments', false)) {
        return false;
    }
    return $open;
}

// Keep Settings → Discussion truthful instead of silently overridden: while the theme
// toggle is off, report the "Allow comments/pingbacks on new posts" options as closed
// (without overwriting whatever value is actually stored, so it's restored as-is the
// moment comments are re-enabled in Customize).
add_filter('option_default_comment_status', 'ajnanda_force_discussion_option_closed');
add_filter('option_default_ping_status', 'ajnanda_force_discussion_option_closed');
function ajnanda_force_discussion_option_closed($value) {
    if (!get_theme_mod('enable_comments', false)) {
        return 'closed';
    }
    return $value;
}

// A page-top notice on Settings → Discussion when the theme has comments turned off —
// every row below is about to be greyed out, and that's easy to miss without something
// visible above the fold pointing to where comments are actually controlled.
add_action('admin_notices', 'ajnanda_discussion_settings_notice');
function ajnanda_discussion_settings_notice() {
    $screen = get_current_screen();
    if (!$screen || 'options-discussion' !== $screen->id || get_theme_mod('enable_comments', false)) {
        return;
    }

    $customize_url = admin_url('customize.php?autofocus[section]=ajnanda_comments');
    ?>
    <div class="notice notice-warning">
        <p>
            <?php
            printf(
                /* translators: %s: "Enable them in Customize → Comments" link */
                esc_html__('Comments are turned off by the AJNanda theme, so the settings below have no effect. %s to turn them back on.', 'ajnanda'),
                '<a href="' . esc_url($customize_url) . '">' . esc_html__('Enable them in Customize → Comments', 'ajnanda') . '</a>'
            );
            ?>
        </p>
    </div>
    <?php
}

// Grey out every comment-only control on Settings → Discussion so the admin screen
// doesn't show a misleadingly "active" state for settings the theme has already made
// moot — everything here is pointless once nothing can be submitted. Avatars are left
// alone: they're used sitewide (e.g. the single-post author bio), not just for comments.
add_action('admin_footer-options-discussion.php', 'ajnanda_lock_discussion_comment_settings');
function ajnanda_lock_discussion_comment_settings() {
    if (get_theme_mod('enable_comments', false)) {
        return; // Theme-level comments are on — let core's own settings govern normally.
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // "Default post settings" row: only the two receive-side checkboxes are
        // theme-controlled. "Attempt to notify any blogs linked to from the post"
        // (outgoing pingbacks) is unrelated to receiving comments — left untouched.
        ['default_comment_status', 'default_ping_status'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) { return; }
            el.checked  = false;
            el.disabled = true;
            var label = el.closest('label');
            if (label) {
                label.style.opacity = '0.6';
                label.style.cursor  = 'not-allowed';
            }
        });

        // Everything else on this screen only matters once comments can actually be
        // submitted. Grey out each row wholesale by its label rather than hand-picking
        // individual field IDs, so any core fields added later under these same
        // headings (new notification types, etc.) get covered automatically too.
        var lockedRowLabels = [
            'Other comment settings',
            'Comment Pagination',
            'Email me whenever',
            'Before a comment appears',
            'Comment Moderation',
            'Disallowed Comment Keys'
        ];

        document.querySelectorAll('.form-table th').forEach(function (th) {
            if (lockedRowLabels.indexOf(th.textContent.trim()) === -1) { return; }
            var row = th.closest('tr');
            var td  = row ? row.querySelector('td') : null;
            if (!td) { return; }
            td.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = true;
            });
            td.style.opacity = '0.6';
        });
    });
    </script>
    <?php
}

// Remove the built-in Menus panel — all menu settings live in Appearance → Menus.
// Filtering out the nav_menus component before it loads (WordPress core's
// recommended approach) rather than calling remove_panel() after
// registration, which WP_Customize_Manager flags as incorrect usage
// because the nav_menus component has already hooked its own JS/behavior
// expecting the panel to exist.
add_filter('customize_loaded_components', function(array $components): array {
    return array_diff($components, array('nav_menus'));
});

function ajnanda_save_footer_color_scheme_settings($wp_customize) {
    $scheme_id = '';

    if ($wp_customize instanceof WP_Customize_Manager) {
        $posted_values = $wp_customize->unsanitized_post_values();

        if (!array_key_exists('footer_color_scheme_picker', $posted_values)) {
            return;
        }

        $scheme_id = sanitize_text_field($posted_values['footer_color_scheme_picker']);
    }

    if ('' === $scheme_id) {
        $scheme_id = get_theme_mod('footer_color_scheme_picker', '');
    }

    $settings = ajnanda_footer_settings_from_header_scheme($scheme_id);

    foreach ($settings as $setting_id => $value) {
        set_theme_mod($setting_id, $value);
    }
}
add_action('customize_save_after', 'ajnanda_save_footer_color_scheme_settings');

/**
 * Compact the Header Customizer controls so common design settings scan like a small table.
 */
function ajnanda_customizer_controls_css() {
    ?>
    <style type="text/css">
        #sub-accordion-section-ajnanda_header .customize-control {
            margin-bottom: 10px;
        }

        #sub-accordion-section-ajnanda_header .customize-control-description {
            margin-top: 4px;
            font-size: 12px;
        }

        #sub-accordion-section-ajnanda_header .customize-control-color {
            display: grid;
            grid-template-columns: minmax(120px, 1fr) auto;
            align-items: center;
            column-gap: 12px;
        }

        #sub-accordion-section-ajnanda_header .customize-control-color .customize-control-title {
            margin: 0;
            line-height: 1.25;
        }

        #sub-accordion-section-ajnanda_header .customize-control-color .wp-picker-container {
            justify-self: end;
        }

        #sub-accordion-section-ajnanda_header .customize-control-color .wp-picker-holder {
            grid-column: 1 / -1;
        }

        #sub-accordion-section-ajnanda_header .wp-color-result.button {
            min-height: 28px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-font-control {
            display: grid;
            gap: 8px;
            padding: 10px;
            border: 1px solid #dcdcde;
            background: #fff;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-font-control label {
            display: grid;
            grid-template-columns: 76px minmax(0, 1fr);
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-font-control span {
            font-size: 12px;
            font-weight: 600;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-font-control input,
        #sub-accordion-section-ajnanda_header .ajnanda-header-font-control select {
            width: 100%;
            min-height: 30px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-font-control input[type="color"] {
            width: 56px;
            padding: 0 2px;
            justify-self: end;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-responsive-control {
            display: grid;
            grid-template-columns: minmax(128px, 1fr) minmax(0, 1fr);
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #dcdcde;
            background: #fff;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-responsive-control select,
        #sub-accordion-section-ajnanda_header .ajnanda-header-responsive-control input {
            width: 100%;
            min-height: 30px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-responsive-value {
            display: none;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 6px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-responsive-value.is-active {
            display: grid;
        }

        #sub-accordion-section-ajnanda_header .ajnanda-header-responsive-value span {
            color: #646970;
            font-size: 12px;
            font-weight: 600;
        }

        #sub-accordion-section-ajnanda_header [data-ajnanda-header-scheme-select] {
            width: 100%;
            min-height: 34px;
            margin: 0;
        }

        /* Footer Section compact layout */
        #sub-accordion-section-ajnanda_footer .customize-control {
            margin-bottom: 10px;
        }

        #sub-accordion-section-ajnanda_footer .customize-control-description {
            margin-top: 4px;
            font-size: 12px;
        }

        #sub-accordion-section-ajnanda_footer .customize-control-color {
            display: grid;
            grid-template-columns: minmax(120px, 1fr) auto;
            align-items: center;
            column-gap: 12px;
        }

        #sub-accordion-section-ajnanda_footer .customize-control-color .customize-control-title {
            margin: 0;
            line-height: 1.25;
        }

        #sub-accordion-section-ajnanda_footer .customize-control-color .wp-picker-container {
            justify-self: end;
        }

        #sub-accordion-section-ajnanda_footer .customize-control-color .wp-picker-holder {
            grid-column: 1 / -1;
        }

        #sub-accordion-section-ajnanda_footer .wp-color-result.button {
            min-height: 28px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-footer-font-control {
            display: grid;
            gap: 8px;
            padding: 10px;
            border: 1px solid #dcdcde;
            background: #fff;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-footer-font-control label {
            display: grid;
            grid-template-columns: 76px minmax(0, 1fr);
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-footer-font-control span {
            font-size: 12px;
            font-weight: 600;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-footer-font-control input,
        #sub-accordion-section-ajnanda_footer .ajnanda-footer-font-control select {
            width: 100%;
            min-height: 30px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-footer-font-control input[type="color"] {
            width: 56px;
            padding: 0 2px;
            justify-self: end;
        }

        #sub-accordion-section-ajnanda_footer select[data-customize-setting-link="footer_color_scheme_picker"] {
            width: 100%;
            min-height: 34px;
            margin: 0;
        }

        /* Footer responsive controls — same layout as header */
        #sub-accordion-section-ajnanda_footer .ajnanda-header-responsive-control {
            display: grid;
            grid-template-columns: minmax(128px, 1fr) minmax(0, 1fr);
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #dcdcde;
            background: #fff;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-header-responsive-control select,
        #sub-accordion-section-ajnanda_footer .ajnanda-header-responsive-control input {
            width: 100%;
            min-height: 30px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-header-responsive-value {
            display: none;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 6px;
            margin: 0;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-header-responsive-value.is-active {
            display: grid;
        }

        #sub-accordion-section-ajnanda_footer .ajnanda-header-responsive-value span {
            color: #646970;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
    <?php
}
add_action('customize_controls_print_styles', 'ajnanda_customizer_controls_css');

function ajnanda_customizer_controls_js() {
    $header_color_schemes = ajnanda_header_color_scheme_values();
    $footer_color_scheme_settings = array();
    foreach (ajnanda_header_color_scheme_values() as $scheme_id => $scheme) {
        $footer_color_scheme_settings[$scheme_id] = ajnanda_footer_settings_from_header_scheme($scheme_id);
    }
    ?>
    <script type="text/javascript">
    (function() {
        // Header schemes — colors: [bg, linkHoverColor, linkHoverBg, submenuBg, submenuText, submenuHoverColor, submenuHoverBg]
        // font: [textColor, fontFamily, fontSize, fontPreset]
        var headerSchemes = <?php echo wp_json_encode($header_color_schemes); ?>;
        var headerSchemeSettings = [
            'header_background_color',
            'header_link_hover_color',
            'header_link_hover_background',
            'header_submenu_background',
            'header_submenu_text_color',
            'header_submenu_hover_color',
            'header_submenu_hover_background'
        ];
        function syncResponsiveControl(control) {
            var select = control.querySelector('.ajnanda-header-responsive-device');
            var values = control.querySelectorAll('[data-ajnanda-responsive-value]');
            if (!select || !values.length) {
                return;
            }

            values.forEach(function(value) {
                value.classList.toggle('is-active', value.getAttribute('data-ajnanda-responsive-value') === select.value);
            });
        }

        function setCustomizerValue(settingId, value) {
            var setting = wp.customize(settingId);
            var control = wp.customize.control(settingId);

            if (setting && setting.get() !== value) {
                setting.set(value);
            }

            if (control && control.container) {
                control.container.find('.color-picker-hex, input.wp-color-picker').val(value);
                control.container.find('.wp-color-result').css('background-color', value);
            }

            document.querySelectorAll('[data-customize-setting-link="' + settingId + '"]').forEach(function(input) {
                if (input.value !== value) {
                    input.value = value;
                }
            });
        }

        function previewFooterSchemeControls(schemeId) {
            var scheme = footerSchemeSettings[schemeId];
            if (!scheme) {
                return;
            }

            Object.keys(scheme).forEach(function(settingId) {
                var value = scheme[settingId];
                var control = wp.customize.control(settingId);

                document.querySelectorAll('[data-customize-setting-link="' + settingId + '"]').forEach(function(input) {
                    input.value = value;
                });

                if (control && control.container) {
                    control.container.find('.color-picker-hex, input.wp-color-picker').val(value);
                    control.container.find('.wp-color-result').css('background-color', value);
                }
            });
        }

        function applyHeaderScheme(schemeId) {
            var scheme = headerSchemes[schemeId];
            if (!scheme || !window.wp || !wp.customize) {
                return;
            }

            headerSchemeSettings.forEach(function(settingId, index) {
                setCustomizerValue(settingId, scheme.colors[index]);
            });

            [
                ['header_text_color', scheme.font[0]],
                ['header_font_family', scheme.font[1]],
                ['header_font_size', scheme.font[2]],
                ['header_font_preset', scheme.font[3]]
            ].forEach(function(item) {
                var settingId = item[0];
                var value = item[1];
                setCustomizerValue(settingId, value);
            });

            var schemeSetting = wp.customize('header_color_scheme_picker');
            if (schemeSetting && schemeSetting.get() !== schemeId) {
                schemeSetting.set(schemeId);
            }
        }

        function initResponsiveControls() {
            document.querySelectorAll('[data-ajnanda-responsive-control]').forEach(function(control) {
                var select = control.querySelector('.ajnanda-header-responsive-device');
                syncResponsiveControl(control);

                if (select && !select.dataset.ajnandaResponsiveReady) {
                    select.dataset.ajnandaResponsiveReady = '1';
                    select.addEventListener('change', function() {
                        syncResponsiveControl(control);
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initResponsiveControls);
        document.addEventListener('click', initResponsiveControls);
        document.addEventListener('change', function(event) {
            var select = event.target.closest('[data-ajnanda-header-scheme-select]');
            if (!select || !select.value) {
                return;
            }

            applyHeaderScheme(select.value);
        });
        var footerSchemeSettings = <?php echo wp_json_encode($footer_color_scheme_settings); ?>;
        document.addEventListener('change', function(event) {
            var select = event.target.closest('[data-ajnanda-footer-scheme-preview]');
            if (!select || !select.value) {
                return;
            }

            previewFooterSchemeControls(select.value);
        });
    })();
    </script>
    <?php
}
add_action('customize_controls_print_footer_scripts', 'ajnanda_customizer_controls_js');

function ajnanda_theme_mod_with_legacy_default($setting_id, $default, $legacy_defaults = array()) {
    $value = get_theme_mod($setting_id, $default);

    if (ajnanda_is_legacy_css_size_value($value, $legacy_defaults)) {
        return $default;
    }

    return $value;
}

/**
 * Live preview JavaScript for customizer
 */
function ajnanda_customizer_live_preview() {
    $builder_insert_choices = array(
        'header' => ajnanda_builder_insert_choices('header'),
        'footer' => ajnanda_builder_insert_choices('footer'),
    );
    ?>
    <script type="text/javascript">
    (function($) {
        var devices = ['desktop', 'tablet', 'mobile'];
        var builderInsertChoices = <?php echo wp_json_encode($builder_insert_choices); ?>;
        var activeInsertControl = '';
        var activeInsertBuilder = '';
        var activeInsertCell = null;
        var builderPreviews = {
            header: document.querySelector('.ajn-customizer-header-builder'),
            footer: document.querySelector('.ajn-customizer-footer-builder')
        };

        function normalizeCssSize(value) {
            value = (value || '').trim();
            if (!value || 'auto' === value.toLowerCase()) {
                return 'auto';
            }

            if (/^\d+(\.\d+)?$/.test(value)) {
                return value + 'px';
            }

            return value;
        }

        wp.customize('logo_vertical_align', function(value) {
            value.bind(function(newval) {
                document.documentElement.style.setProperty('--ajnanda-logo-vertical-align', newval);
            });
        });

        devices.forEach(function(device) {
            wp.customize('logo_height_' + device, function(value) {
                value.bind(function(newval) {
                    document.documentElement.style.setProperty('--ajnanda-logo-height-' + device, newval + 'px');
                });
            });

            wp.customize('header_padding_' + device, function(value) {
                value.bind(function(newval) {
                    document.documentElement.style.setProperty('--ajnanda-header-padding-' + device, newval + 'rem');
                });
            });

            wp.customize('header_height_' + device, function(value) {
                value.bind(function(newval) {
                    document.documentElement.style.setProperty('--ajnanda-header-height-' + device, normalizeCssSize(newval));
                });
            });

            wp.customize('footer_padding_top_' + device, function(value) {
                value.bind(function(newval) {
                    document.documentElement.style.setProperty('--ajn-footer-padding-top-' + device, newval);
                });
            });

            wp.customize('footer_padding_bottom_' + device, function(value) {
                value.bind(function(newval) {
                    document.documentElement.style.setProperty('--ajn-footer-padding-bottom-' + device, newval);
                });
            });
        });

        // Footer color and font settings — postMessage live preview via CSS vars
        var footerCssVarMap = {
            'footer_background_color':         '--ajn-footer-background',
            'footer_text_color':               '--ajn-footer-text-color',
            'footer_link_hover_color':         '--ajn-footer-link-hover-color',
            'footer_divider_color':            '--ajn-footer-divider-color',
            'footer_submenu_background':       '--ajn-footer-submenu-background',
            'footer_submenu_text_color':       '--ajn-footer-submenu-text-color',
            'footer_submenu_hover_color':      '--ajn-footer-submenu-hover-color',
            'footer_submenu_hover_background': '--ajn-footer-submenu-hover-background',
            'footer_font_family':              '--ajn-footer-font-family',
            'footer_font_size':                '--ajn-footer-font-size',
            'footer_font_weight':              '--ajn-footer-font-weight'
        };
        Object.keys(footerCssVarMap).forEach(function(settingId) {
            wp.customize(settingId, function(value) {
                value.bind(function(newval) {
                    document.documentElement.style.setProperty(footerCssVarMap[settingId], newval);
                });
            });
        });

        function hideBuilderPreviews() {
            Object.keys(builderPreviews).forEach(function(key) {
                if (builderPreviews[key]) {
                    builderPreviews[key].classList.remove('is-active');
                }
            });
        }

        function showBuilderPreview(type) {
            hideBuilderPreviews();

            if (builderPreviews[type]) {
                builderPreviews[type].classList.add('is-active');
            }
        }

        function getCustomizerManager() {
            if (window.parent && window.parent.wp && window.parent.wp.customize) {
                return window.parent.wp.customize;
            }

            if (window.wp && window.wp.customize) {
                return window.wp.customize;
            }

            return null;
        }

        function getCustomizerSetting(controlId) {
            var manager = getCustomizerManager();

            if (!manager || !controlId) {
                return null;
            }

            try {
                return manager(controlId);
            } catch (error) {
                return null;
            }
        }

        function getInsertPopover() {
            var popover = document.querySelector('.ajn-builder-insert-popover');

            if (popover) {
                return popover;
            }

            popover = document.createElement('div');
            popover.className = 'ajn-builder-insert-popover';

            var popoverHead = document.createElement('div');
            popoverHead.className = 'ajn-builder-insert-popover-head';

            var popoverTitle = document.createElement('span');
            popoverTitle.textContent = 'Insert Elements';
            popoverHead.appendChild(popoverTitle);

            var popoverGrid = document.createElement('div');
            popoverGrid.className = 'ajn-builder-insert-grid';

            popover.appendChild(popoverHead);
            popover.appendChild(popoverGrid);
            document.body.appendChild(popover);

            popover.addEventListener('click', function(event) {
                var choice = event.target.closest('[data-ajn-insert-value]');

                if (!choice || !getCustomizerSetting(activeInsertControl)) {
                    return;
                }

                event.preventDefault();
                setCustomizerControl(activeInsertControl, choice.getAttribute('data-ajn-insert-value'));
                renderBuilderCellElement(activeInsertCell, activeInsertBuilder || 'footer', activeInsertControl, choice.getAttribute('data-ajn-insert-value'));
                hideInsertPopover();
            });

            return popover;
        }

        function refreshCustomizerPreview() {
            window.setTimeout(function() {
                var manager = getCustomizerManager();

                if (manager && manager.previewer && manager.previewer.refresh) {
                    manager.previewer.refresh();
                    return;
                }

                if (window.wp && wp.customize && wp.customize.preview && wp.customize.preview.send) {
                    wp.customize.preview.send('refresh');
                }
            }, 120);
        }

        function hideInsertPopover() {
            var popover = document.querySelector('.ajn-builder-insert-popover');

            if (popover) {
                popover.classList.remove('is-active');
            }

            activeInsertControl = '';
            activeInsertBuilder = '';
            activeInsertCell = null;
        }

        function showInsertPopover(button) {
            var builder = button.getAttribute('data-ajn-builder') || 'footer';
            var choices = builderInsertChoices[builder] || builderInsertChoices.footer || {};
            var popover = getInsertPopover();
            var grid = popover.querySelector('.ajn-builder-insert-grid');

            activeInsertControl = button.getAttribute('data-ajn-insert-control') || '';
            activeInsertBuilder = builder;
            activeInsertCell = button.closest('.ajn-customizer-builder-cell');
            grid.innerHTML = '';

            Object.keys(choices).forEach(function(value) {
                var item = document.createElement('button');
                var icon = document.createElement('span');
                var label = document.createElement('span');

                item.type = 'button';
                item.className = 'ajn-builder-insert-choice ajn-builder-insert-choice-' + value;
                item.setAttribute('data-ajn-insert-value', value);

                icon.className = 'ajn-builder-insert-icon';
                icon.setAttribute('aria-hidden', 'true');
                label.textContent = choices[value];

                item.appendChild(icon);
                item.appendChild(label);
                grid.appendChild(item);
            });

            var rect = button.getBoundingClientRect();
            popover.style.left = Math.max(16, Math.min(rect.left - 220, window.innerWidth - 520)) + 'px';
            popover.style.bottom = Math.max(96, window.innerHeight - rect.top + 12) + 'px';
            popover.classList.add('is-active');
        }

        function focusCustomizerControl(controlId) {
            var manager = getCustomizerManager();

            if (!manager || !manager.control) {
                return;
            }

            if (controlId.indexOf('sidebar-widgets-') === 0) {
                focusWidgetSection(controlId, manager);
                return;
            }

            var control = manager.control(controlId);
            var section = manager.section ? manager.section(controlId) : null;
            var panel = manager.panel ? manager.panel(controlId) : null;

            if (control && control.focus) {
                control.focus();
                return;
            }

            if (section && section.focus) {
                section.focus();
                return;
            }

            if (panel && panel.focus) {
                panel.focus();
                return;
            }
        }

        function focusWidgetSection(sectionId, manager) {
            var panel = manager.panel ? manager.panel('widgets') : null;
            var section = manager.section ? manager.section(sectionId) : null;
            var focusSection = function() {
                section = manager.section ? manager.section(sectionId) : section;

                if (section && section.focus) {
                    section.focus();
                    return;
                }

                if (section && section.expand) {
                    section.expand();
                    return;
                }

                focusWidgetSetting(sectionId, manager);
            };

            if (section && section.focus) {
                section.focus();
                return;
            }

            if (section && section.expand) {
                section.expand();
                return;
            }

            if (panel && panel.focus) {
                panel.focus({
                    completeCallback: function() {
                        window.setTimeout(focusSection, 250);
                    }
                });
                return;
            }

            focusSection();
        }

        function focusWidgetSetting(sectionId, manager) {
            var sidebarId = sectionId.replace(/^sidebar-widgets-/, '');

            if (manager.previewer && manager.previewer.send) {
                manager.previewer.send('focus-control-for-setting', 'sidebars_widgets[' + sidebarId + ']');
            }
        }

        function getBuilderElementLabel(builder, value) {
            var choices = builderInsertChoices[builder] || builderInsertChoices.footer || {};

            return choices[value] || value;
        }

        function getBuilderElementFocusControl(builder, value, fallbackControlId) {
            if ('site-logo' === value) {
                return 'custom_logo';
            }

            if ('primary-menu' === value) {
                return 'nav_menu_locations[primary]';
            }

            if ('footer-menu' === value) {
                return 'nav_menu_locations[footer]';
            }

            if ('footer' === builder && ('button' === value || 'button-1' === value)) {
                return 'ajn_footer_builder_button_text';
            }

            if ('button' === value || 'button-1' === value) {
                return 'ajn_builder_button_text';
            }

            if ('button-2' === value) {
                return 'ajn_builder_button_2_text';
            }

            if ('copyright' === value) {
                return 'footer_bottom_text';
            }

            if ('html-1' === value) {
                return 'ajn_builder_html_1';
            }

            if ('html-2' === value) {
                return 'ajn_builder_html_2';
            }

            if ('social' === value) {
                return 'ajn_builder_social_1_url';
            }

            if (0 === value.indexOf('widget-')) {
                return 'sidebar-widgets-' + builder + '-builder-' + value.replace('widget-', '');
            }

            return fallbackControlId;
        }

        function renderBuilderCellAdd(cell, builder, controlId) {
            var addButton = document.createElement('button');

            if (!cell) {
                return;
            }

            addButton.type = 'button';
            addButton.className = 'ajn-customizer-builder-add';
            addButton.setAttribute('data-ajn-insert-control', controlId);
            addButton.setAttribute('data-ajn-builder', builder);
            addButton.textContent = '+';

            cell.textContent = '';
            cell.appendChild(addButton);
        }

        function renderBuilderCellElement(cell, builder, controlId, value) {
            var chip = document.createElement('button');
            var remove = document.createElement('span');

            if (!cell || 'none' === value) {
                renderBuilderCellAdd(cell, builder, controlId);
                return;
            }

            chip.type = 'button';
            chip.className = 'ajn-customizer-builder-chip';
            chip.setAttribute('data-ajn-focus-control', getBuilderElementFocusControl(builder, value, controlId));
            chip.appendChild(document.createTextNode(getBuilderElementLabel(builder, value)));

            remove.setAttribute('aria-hidden', 'true');
            remove.className = 'ajn-customizer-builder-remove';
            remove.setAttribute('data-ajn-clear-control', controlId);
            remove.textContent = '\u00d7';

            chip.appendChild(remove);
            cell.textContent = '';
            cell.appendChild(chip);
        }

        function clearCustomizerControl(controlId) {
            var setting = getCustomizerSetting(controlId);

            if (!setting) {
                return;
            }

            setting.set('none');
        }

        function setCustomizerControl(controlId, value) {
            var setting = getCustomizerSetting(controlId);

            if (!setting) {
                return;
            }

            setting.set(value);
        }

        document.addEventListener('click', function(event) {
            var clearButton = event.target.closest('[data-ajn-clear-control]');
            var insertButton = event.target.closest('[data-ajn-insert-control]');
            var setButton = event.target.closest('[data-ajn-set-control]');
            var focusButton = event.target.closest('[data-ajn-focus-control]');
            var shortcut = event.target.closest('.customize-partial-edit-shortcut, .customize-partial-edit-shortcut-button');

            if (clearButton) {
                event.preventDefault();
                event.stopPropagation();
                clearCustomizerControl(clearButton.getAttribute('data-ajn-clear-control'));
                renderBuilderCellAdd(
                    clearButton.closest('.ajn-customizer-builder-cell'),
                    clearButton.closest('.ajn-customizer-header-builder') ? 'header' : 'footer',
                    clearButton.getAttribute('data-ajn-clear-control')
                );
                return;
            }

            if (setButton) {
                event.preventDefault();
                event.stopPropagation();
                setCustomizerControl(setButton.getAttribute('data-ajn-set-control'), setButton.getAttribute('data-ajn-set-value'));
                hideInsertPopover();
                refreshCustomizerPreview();
                return;
            }

            if (insertButton) {
                event.preventDefault();
                event.stopPropagation();
                showInsertPopover(insertButton);
                return;
            }

            if (focusButton) {
                event.preventDefault();
                event.stopPropagation();
                focusCustomizerControl(focusButton.getAttribute('data-ajn-focus-control'));
                return;
            }

            if (!shortcut) {
                return;
            }

            if (shortcut.closest('.site-header') || shortcut.closest('.custom-logo-link') || shortcut.closest('.site-branding') || shortcut.closest('.main-navigation') || document.querySelector('.site-header:hover')) {
                showBuilderPreview('header');
                return;
            }

            if (shortcut.closest('.site-footer') || document.querySelector('.site-footer:hover')) {
                showBuilderPreview('footer');
            }
        }, true);

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideBuilderPreviews();
                hideInsertPopover();
            }
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * Output custom CSS for customizer settings
 */
function ajnanda_customizer_css() {
    $theme_primary_color = get_theme_mod('theme_primary_color', '#2563eb');
    $theme_primary_dark_color = get_theme_mod('theme_primary_dark_color', '#1e40af');
    $theme_secondary_color = get_theme_mod('theme_secondary_color', '#7c3aed');
    $theme_accent_color = get_theme_mod('theme_accent_color', '#f59e0b');
    $header_background_color = get_theme_mod('header_background_color', '#ffffff');
    $header_text_color = get_theme_mod('header_text_color', '#1f2937');
    $header_link_hover_color = get_theme_mod('header_link_hover_color', '#2563eb');
    $header_link_hover_background = get_theme_mod('header_link_hover_background', '#f9fafb');
    $header_submenu_background = get_theme_mod('header_submenu_background', '#ffffff');
    $header_submenu_text_color = get_theme_mod('header_submenu_text_color', '#1f2937');
    $header_submenu_hover_color = get_theme_mod('header_submenu_hover_color', '#2563eb');
    $header_submenu_hover_background = get_theme_mod('header_submenu_hover_background', '#f9fafb');
    $header_font_family = get_theme_mod('header_font_family', 'inherit');
    $header_font_size = get_theme_mod('header_font_size', '1rem');
    $header_font_weight = get_theme_mod('header_font_weight', '500');
    $header_font_style = 'normal';
    $header_text_decoration = 'none';
    $header_font_preset = get_theme_mod('header_font_preset', 'normal');
    if (in_array($header_font_preset, array('bold', 'bold-italic', 'bold-underline'), true)) {
        $header_font_weight = '700';
    }
    if (in_array($header_font_preset, array('italic', 'bold-italic'), true)) {
        $header_font_style = 'italic';
    }
    if (in_array($header_font_preset, array('underline', 'bold-underline'), true)) {
        $header_text_decoration = 'underline';
    }
    $header_menu_gap = get_theme_mod('header_menu_gap', '2rem');
    $header_container_width = get_theme_mod('header_container_width', '1400px');
    $header_shadow_opacity = get_theme_mod('header_shadow_opacity', '0.10');
    $header_sticky = get_theme_mod('header_sticky', true);
    $footer_background_color = get_theme_mod('footer_background_color', '#111827');
    $footer_text_color = get_theme_mod('footer_text_color', '#f9fafb');
    $footer_link_hover_color = get_theme_mod('footer_link_hover_color', '#f59e0b');
    $footer_divider_color = get_theme_mod('footer_divider_color', '#374151');
    $footer_submenu_background = get_theme_mod('footer_submenu_background', '#ffffff');
    $footer_submenu_text_color = get_theme_mod('footer_submenu_text_color', '#1f2937');
    $footer_submenu_hover_color = get_theme_mod('footer_submenu_hover_color', '#2563eb');
    $footer_submenu_hover_background = get_theme_mod('footer_submenu_hover_background', '#f9fafb');
    $footer_font_family = get_theme_mod('footer_font_family', 'inherit');
    $footer_font_size = get_theme_mod('footer_font_size', '1rem');
    $footer_font_weight = get_theme_mod('footer_font_weight', '400');
    $footer_font_style = get_theme_mod('footer_font_style', 'normal');
    $footer_text_decoration = get_theme_mod('footer_text_decoration', 'none');
    $footer_menu_gap = get_theme_mod('footer_menu_gap', '1.4rem');
    $footer_container_width = get_theme_mod('footer_container_width', '1280px');
    $footer_padding_top_desktop    = get_theme_mod('footer_padding_top_desktop',    '4rem');
    $footer_padding_top_tablet     = get_theme_mod('footer_padding_top_tablet',     '3rem');
    $footer_padding_top_mobile     = get_theme_mod('footer_padding_top_mobile',     '2.5rem');
    $footer_padding_bottom_desktop = get_theme_mod('footer_padding_bottom_desktop', '2rem');
    $footer_padding_bottom_tablet  = get_theme_mod('footer_padding_bottom_tablet',  '1.5rem');
    $footer_padding_bottom_mobile  = get_theme_mod('footer_padding_bottom_mobile',  '1.5rem');

    $footer_scheme_id = get_theme_mod('footer_color_scheme_picker', '');
    $shared_schemes = ajnanda_header_color_scheme_values();
    if (isset($shared_schemes[$footer_scheme_id])) {
        $footer_scheme = $shared_schemes[$footer_scheme_id];
        $footer_background_color = $footer_scheme['colors'][0];
        $footer_link_hover_color = $footer_scheme['colors'][1];
        $footer_divider_color = $footer_scheme['colors'][2];
        $footer_submenu_background = $footer_scheme['colors'][3];
        $footer_submenu_text_color = $footer_scheme['colors'][4];
        $footer_submenu_hover_color = $footer_scheme['colors'][5];
        $footer_submenu_hover_background = $footer_scheme['colors'][6];
        $footer_text_color = $footer_scheme['font'][0];
        $footer_font_family = $footer_scheme['font'][1];
        $footer_font_size = $footer_scheme['font'][2];
        $footer_font_weight = in_array($footer_scheme['font'][3], array('bold', 'bold-italic', 'bold-underline'), true) ? '700' : '400';
        $footer_font_style = in_array($footer_scheme['font'][3], array('italic', 'bold-italic'), true) ? 'italic' : 'normal';
        $footer_text_decoration = in_array($footer_scheme['font'][3], array('underline', 'bold-underline'), true) ? 'underline' : 'none';
    }

    $old_logo_height = get_theme_mod('logo_height', '50');
    $old_header_padding = get_theme_mod('header_padding', '0.75');

    $logo_height_desktop = get_theme_mod('logo_height_desktop', $old_logo_height);
    $logo_height_tablet = get_theme_mod('logo_height_tablet', $old_logo_height);
    $logo_height_mobile = get_theme_mod('logo_height_mobile', $old_logo_height);
    $logo_vertical_align = get_theme_mod('logo_vertical_align', 'center');

    $header_padding_desktop = get_theme_mod('header_padding_desktop', $old_header_padding);
    $header_padding_tablet = get_theme_mod('header_padding_tablet', $old_header_padding);
    $header_padding_mobile = get_theme_mod('header_padding_mobile', $old_header_padding);

    $header_height_desktop = get_theme_mod('header_height_desktop', 'auto');
    $header_height_tablet = get_theme_mod('header_height_tablet', 'auto');
    $header_height_mobile = get_theme_mod('header_height_mobile', 'auto');

    $hero_bg_1 = get_theme_mod('hero_bg_1', $theme_primary_color);
    $hero_bg_2 = get_theme_mod('hero_bg_2', $theme_secondary_color);
    $hero_heading_color = get_theme_mod('hero_heading_color', '#ffffff');
    $hero_subtitle_color = get_theme_mod('hero_subtitle_color', 'rgba(255,255,255,0.94)');
    $hero_badge_bg = get_theme_mod('hero_badge_bg', 'rgba(255,255,255,0.16)');
    $hero_badge_text_color = get_theme_mod('hero_badge_text_color', '#ffffff');
    $hero_button_bg = get_theme_mod('hero_button_bg', '#ffffff');
    $hero_button_text_color = get_theme_mod('hero_button_text_color', '#2563eb');

    $hero_min_height_desktop = ajnanda_theme_mod_with_legacy_default('hero_min_height_desktop', '50px', array('450px'));
    $hero_min_height_tablet = ajnanda_theme_mod_with_legacy_default('hero_min_height_tablet', '50px', array('400px'));
    $hero_min_height_mobile = ajnanda_theme_mod_with_legacy_default('hero_min_height_mobile', '50px', array('340px'));
    $hero_padding_top_desktop = ajnanda_theme_mod_with_legacy_default('hero_padding_top_desktop', '1rem', array('7rem', '8rem'));
    $hero_padding_bottom_desktop = ajnanda_theme_mod_with_legacy_default('hero_padding_bottom_desktop', '1rem', array('4rem'));
    $hero_padding_top_tablet = ajnanda_theme_mod_with_legacy_default('hero_padding_top_tablet', '1rem', array('6rem', '7rem'));
    $hero_padding_bottom_tablet = ajnanda_theme_mod_with_legacy_default('hero_padding_bottom_tablet', '1rem', array('3.5rem'));
    $hero_padding_top_mobile = ajnanda_theme_mod_with_legacy_default('hero_padding_top_mobile', '1rem', array('5rem', '6rem'));
    $hero_padding_bottom_mobile = ajnanda_theme_mod_with_legacy_default('hero_padding_bottom_mobile', '1rem', array('3rem'));

    // Nav container style.
    $nav_container_bg = ajnanda_get_theme_palette_color(get_theme_mod('ajn_nav_container_bg', ''));
    $nav_container_border_color = ajnanda_get_theme_palette_color(get_theme_mod('ajn_nav_container_border_color', ''));
    $nav_container_border_width = get_theme_mod('ajn_nav_container_border_width', '');
    $nav_container_border_radius = get_theme_mod('ajn_nav_container_border_radius', '');
    $nav_container_padding_x = get_theme_mod('ajn_nav_container_padding_x', '');
    $nav_container_padding_y = get_theme_mod('ajn_nav_container_padding_y', '');
    $nav_container_shadow = (float) get_theme_mod('ajn_nav_container_shadow', 0);

    // Active menu item treatment.
    $active_menu_color = ajnanda_get_theme_palette_color(get_theme_mod('ajn_active_menu_color', ''));

    // Nav CTA.
    $nav_cta_bg = ajnanda_get_theme_palette_color(get_theme_mod('ajn_nav_cta_bg', ''));
    $nav_cta_color = ajnanda_get_theme_palette_color(get_theme_mod('ajn_nav_cta_color', ''));
    $nav_cta_hover_bg = ajnanda_get_theme_palette_color(get_theme_mod('ajn_nav_cta_hover_bg', ''));
    $nav_cta_hover_color = ajnanda_get_theme_palette_color(get_theme_mod('ajn_nav_cta_hover_color', ''));
    $nav_cta_border_radius = get_theme_mod('ajn_nav_cta_border_radius', '');
    $nav_cta_shadow = (float) get_theme_mod('ajn_nav_cta_shadow', 0);

    // Branding badge.
    $badge_bg = ajnanda_get_theme_palette_color(get_theme_mod('ajn_branding_badge_bg', ''));
    $badge_color = ajnanda_get_theme_palette_color(get_theme_mod('ajn_branding_badge_color', ''));
    $badge_width = get_theme_mod('ajn_branding_badge_width', '');
    $badge_height = get_theme_mod('ajn_branding_badge_height', '');
    $badge_radius = get_theme_mod('ajn_branding_badge_radius', '');
    $badge_font_size = get_theme_mod('ajn_branding_badge_font_size', '');
    $badge_font_weight = get_theme_mod('ajn_branding_badge_font_weight', '');

    // Header accent strip.
    $accent_enabled = get_theme_mod('ajn_header_accent_enable', false);
    $accent_type = get_theme_mod('ajn_header_accent_type', 'solid');
    $accent_color_1 = ajnanda_get_theme_palette_color(get_theme_mod('ajn_header_accent_color_1', 'primary-blue'));
    $accent_color_2 = ajnanda_get_theme_palette_color(get_theme_mod('ajn_header_accent_color_2', 'purple'));
    $accent_color_3 = ajnanda_get_theme_palette_color(get_theme_mod('ajn_header_accent_color_3', 'gold'));
    $accent_height = get_theme_mod('ajn_header_accent_height', '4px');

    if ($accent_enabled && 'gradient-3' === $accent_type) {
        $accent_background = sprintf('linear-gradient(90deg, %s, %s, %s)', $accent_color_1, $accent_color_2, $accent_color_3);
    } elseif ($accent_enabled && 'gradient-2' === $accent_type) {
        $accent_background = sprintf('linear-gradient(90deg, %s, %s)', $accent_color_1, $accent_color_2);
    } else {
        $accent_background = $accent_color_1;
    }
    ?>
    <style type="text/css">
        :root {
            --primary: <?php echo esc_attr($theme_primary_color); ?>;
            --primary-dark: <?php echo esc_attr($theme_primary_dark_color); ?>;
            --secondary: <?php echo esc_attr($theme_secondary_color); ?>;
            --accent: <?php echo esc_attr($theme_accent_color); ?>;
            --ajn-header-background: <?php echo esc_attr($header_background_color); ?>;
            --ajn-header-text-color: <?php echo esc_attr($header_text_color); ?>;
            --ajn-header-link-hover-color: <?php echo esc_attr($header_link_hover_color); ?>;
            --ajn-header-link-hover-background: <?php echo esc_attr($header_link_hover_background); ?>;
            --ajn-header-submenu-background: <?php echo esc_attr($header_submenu_background); ?>;
            --ajn-header-submenu-text-color: <?php echo esc_attr($header_submenu_text_color); ?>;
            --ajn-header-submenu-hover-color: <?php echo esc_attr($header_submenu_hover_color); ?>;
            --ajn-header-submenu-hover-background: <?php echo esc_attr($header_submenu_hover_background); ?>;
            --ajn-header-font-family: <?php echo esc_attr($header_font_family); ?>;
            --ajn-header-font-size: <?php echo esc_attr($header_font_size); ?>;
            --ajn-header-font-weight: <?php echo esc_attr($header_font_weight); ?>;
            --ajn-header-font-style: <?php echo esc_attr($header_font_style); ?>;
            --ajn-header-text-decoration: <?php echo esc_attr($header_text_decoration); ?>;
            --ajn-header-menu-gap: <?php echo esc_attr($header_menu_gap); ?>;
            --ajn-header-container-width: <?php echo esc_attr($header_container_width); ?>;
            --ajn-header-shadow-opacity: <?php echo esc_attr($header_shadow_opacity); ?>;
            --ajn-header-position: <?php echo $header_sticky ? 'sticky' : 'relative'; ?>;
            --ajn-footer-background: <?php echo esc_attr($footer_background_color); ?>;
            --ajn-footer-text-color: <?php echo esc_attr($footer_text_color); ?>;
            --ajn-footer-link-hover-color: <?php echo esc_attr($footer_link_hover_color); ?>;
            --ajn-footer-divider-color: <?php echo esc_attr($footer_divider_color); ?>;
            --ajn-footer-submenu-background: <?php echo esc_attr($footer_submenu_background); ?>;
            --ajn-footer-submenu-text-color: <?php echo esc_attr($footer_submenu_text_color); ?>;
            --ajn-footer-submenu-hover-color: <?php echo esc_attr($footer_submenu_hover_color); ?>;
            --ajn-footer-submenu-hover-background: <?php echo esc_attr($footer_submenu_hover_background); ?>;
            --ajn-footer-font-family: <?php echo esc_attr($footer_font_family); ?>;
            --ajn-footer-font-size: <?php echo esc_attr($footer_font_size); ?>;
            --ajn-footer-font-weight: <?php echo esc_attr($footer_font_weight); ?>;
            --ajn-footer-font-style: <?php echo esc_attr($footer_font_style); ?>;
            --ajn-footer-text-decoration: <?php echo esc_attr($footer_text_decoration); ?>;
            --ajn-footer-menu-gap: <?php echo esc_attr($footer_menu_gap); ?>;
            --ajn-footer-container-width: <?php echo esc_attr($footer_container_width); ?>;
            --ajn-footer-padding-top-desktop: <?php echo esc_attr($footer_padding_top_desktop); ?>;
            --ajn-footer-padding-top-tablet: <?php echo esc_attr($footer_padding_top_tablet); ?>;
            --ajn-footer-padding-top-mobile: <?php echo esc_attr($footer_padding_top_mobile); ?>;
            --ajn-footer-padding-bottom-desktop: <?php echo esc_attr($footer_padding_bottom_desktop); ?>;
            --ajn-footer-padding-bottom-tablet: <?php echo esc_attr($footer_padding_bottom_tablet); ?>;
            --ajn-footer-padding-bottom-mobile: <?php echo esc_attr($footer_padding_bottom_mobile); ?>;
            --ajn-footer-padding-top: var(--ajn-footer-padding-top-desktop);
            --ajn-footer-padding-bottom: var(--ajn-footer-padding-bottom-desktop);
            --ast-global-color-0: <?php echo esc_attr($theme_primary_color); ?>;
            --ast-global-color-1: <?php echo esc_attr($theme_primary_dark_color); ?>;
            --ast-global-color-2: <?php echo esc_attr($theme_secondary_color); ?>;
            --ast-global-color-7: <?php echo esc_attr($theme_accent_color); ?>;

            --ajnanda-logo-height-desktop: <?php echo esc_attr($logo_height_desktop); ?>px;
            --ajnanda-logo-height-tablet: <?php echo esc_attr($logo_height_tablet); ?>px;
            --ajnanda-logo-height-mobile: <?php echo esc_attr($logo_height_mobile); ?>px;
            --ajnanda-logo-vertical-align: <?php echo esc_attr($logo_vertical_align); ?>;
            --ajnanda-header-padding-desktop: <?php echo esc_attr($header_padding_desktop); ?>rem;
            --ajnanda-header-padding-tablet: <?php echo esc_attr($header_padding_tablet); ?>rem;
            --ajnanda-header-padding-mobile: <?php echo esc_attr($header_padding_mobile); ?>rem;
            --ajnanda-header-height-desktop: <?php echo esc_attr($header_height_desktop); ?>;
            --ajnanda-header-height-tablet: <?php echo esc_attr($header_height_tablet); ?>;
            --ajnanda-header-height-mobile: <?php echo esc_attr($header_height_mobile); ?>;

            --ajn-hero-bg-1: <?php echo esc_attr($hero_bg_1); ?>;
            --ajn-hero-bg-2: <?php echo esc_attr($hero_bg_2); ?>;
            --ajn-hero-heading-color: <?php echo esc_attr($hero_heading_color); ?>;
            --ajn-hero-subtitle-color: <?php echo esc_attr($hero_subtitle_color); ?>;
            --ajn-hero-badge-bg: <?php echo esc_attr($hero_badge_bg); ?>;
            --ajn-hero-badge-text-color: <?php echo esc_attr($hero_badge_text_color); ?>;
            --ajn-hero-button-bg: <?php echo esc_attr($hero_button_bg); ?>;
            --ajn-hero-button-text-color: <?php echo esc_attr($hero_button_text_color); ?>;
            --ajn-hero-min-height-desktop: <?php echo esc_attr($hero_min_height_desktop); ?>;
            --ajn-hero-min-height-tablet: <?php echo esc_attr($hero_min_height_tablet); ?>;
            --ajn-hero-min-height-mobile: <?php echo esc_attr($hero_min_height_mobile); ?>;
            --ajn-hero-padding-top-desktop: <?php echo esc_attr($hero_padding_top_desktop); ?>;
            --ajn-hero-padding-bottom-desktop: <?php echo esc_attr($hero_padding_bottom_desktop); ?>;
            --ajn-hero-padding-top-tablet: <?php echo esc_attr($hero_padding_top_tablet); ?>;
            --ajn-hero-padding-bottom-tablet: <?php echo esc_attr($hero_padding_bottom_tablet); ?>;
            --ajn-hero-padding-top-mobile: <?php echo esc_attr($hero_padding_top_mobile); ?>;
            --ajn-hero-padding-bottom-mobile: <?php echo esc_attr($hero_padding_bottom_mobile); ?>;

            <?php if ($nav_container_bg) : ?>--ajn-nav-container-bg: <?php echo esc_attr($nav_container_bg); ?>;<?php endif; ?>
            <?php if ($nav_container_border_color) : ?>--ajn-nav-container-border-color: <?php echo esc_attr($nav_container_border_color); ?>;<?php endif; ?>
            <?php if ($nav_container_border_width) : ?>--ajn-nav-container-border-width: <?php echo esc_attr($nav_container_border_width); ?>;<?php endif; ?>
            <?php if ($nav_container_border_radius) : ?>--ajn-nav-container-border-radius: <?php echo esc_attr($nav_container_border_radius); ?>;<?php endif; ?>
            <?php if ($nav_container_padding_x) : ?>--ajn-nav-container-padding-x: <?php echo esc_attr($nav_container_padding_x); ?>;<?php endif; ?>
            <?php if ($nav_container_padding_y) : ?>--ajn-nav-container-padding-y: <?php echo esc_attr($nav_container_padding_y); ?>;<?php endif; ?>
            --ajn-nav-container-shadow-strength: <?php echo esc_attr($nav_container_shadow); ?>;

            <?php if ($active_menu_color) : ?>--ajn-active-menu-color: <?php echo esc_attr($active_menu_color); ?>;<?php endif; ?>

            <?php if ($nav_cta_bg) : ?>--ajn-nav-cta-bg: <?php echo esc_attr($nav_cta_bg); ?>;<?php endif; ?>
            <?php if ($nav_cta_color) : ?>--ajn-nav-cta-color: <?php echo esc_attr($nav_cta_color); ?>;<?php endif; ?>
            <?php if ($nav_cta_hover_bg) : ?>--ajn-nav-cta-hover-bg: <?php echo esc_attr($nav_cta_hover_bg); ?>;<?php endif; ?>
            <?php if ($nav_cta_hover_color) : ?>--ajn-nav-cta-hover-color: <?php echo esc_attr($nav_cta_hover_color); ?>;<?php endif; ?>
            <?php if ($nav_cta_border_radius) : ?>--ajn-nav-cta-border-radius: <?php echo esc_attr($nav_cta_border_radius); ?>;<?php endif; ?>
            --ajn-nav-cta-shadow-strength: <?php echo esc_attr($nav_cta_shadow); ?>;

            <?php if ($badge_bg) : ?>--ajn-badge-bg: <?php echo esc_attr($badge_bg); ?>;<?php endif; ?>
            <?php if ($badge_color) : ?>--ajn-badge-color: <?php echo esc_attr($badge_color); ?>;<?php endif; ?>
            <?php if ($badge_width) : ?>--ajn-badge-width: <?php echo esc_attr($badge_width); ?>;<?php endif; ?>
            <?php if ($badge_height) : ?>--ajn-badge-height: <?php echo esc_attr($badge_height); ?>;<?php endif; ?>
            <?php if ($badge_radius) : ?>--ajn-badge-radius: <?php echo esc_attr($badge_radius); ?>;<?php endif; ?>
            <?php if ($badge_font_size) : ?>--ajn-badge-font-size: <?php echo esc_attr($badge_font_size); ?>;<?php endif; ?>
            <?php if ($badge_font_weight) : ?>--ajn-badge-font-weight: <?php echo esc_attr($badge_font_weight); ?>;<?php endif; ?>

            --ajn-header-accent-bg: <?php echo esc_attr($accent_background); ?>;
            --ajn-header-accent-height: <?php echo esc_attr($accent_height); ?>;
        }

        .site-branding,
        .custom-logo-link {
            align-self: var(--ajnanda-logo-vertical-align, center);
        }

        .site-branding img,
        .custom-logo-link img {
            max-height: var(--ajnanda-logo-height-desktop) !important;
        }

        .header-container {
            max-width: var(--ajn-header-container-width) !important;
            min-height: var(--ajnanda-header-height-desktop) !important;
            padding-top: var(--ajnanda-header-padding-desktop) !important;
            padding-bottom: var(--ajnanda-header-padding-desktop) !important;
        }

        .header-builder-container {
            max-width: var(--ajn-header-container-width) !important;
            min-height: var(--ajnanda-header-height-desktop) !important;
        }

        @media (max-width: 1024px) {
            .site-branding img,
            .custom-logo-link img {
                max-height: var(--ajnanda-logo-height-tablet) !important;
            }

            .header-container {
                min-height: var(--ajnanda-header-height-tablet) !important;
                padding-top: var(--ajnanda-header-padding-tablet) !important;
                padding-bottom: var(--ajnanda-header-padding-tablet) !important;
            }

            .header-builder-container {
                min-height: var(--ajnanda-header-height-tablet) !important;
            }

            :root {
                --ajn-footer-padding-top: var(--ajn-footer-padding-top-tablet);
                --ajn-footer-padding-bottom: var(--ajn-footer-padding-bottom-tablet);
            }
        }

        @media (max-width: 768px) {
            .site-branding img,
            .custom-logo-link img {
                max-height: var(--ajnanda-logo-height-mobile) !important;
            }

            .header-container {
                min-height: var(--ajnanda-header-height-mobile) !important;
                padding-top: var(--ajnanda-header-padding-mobile) !important;
                padding-bottom: var(--ajnanda-header-padding-mobile) !important;
            }

            .header-builder-container {
                min-height: var(--ajnanda-header-height-mobile) !important;
            }

            :root {
                --ajn-footer-padding-top: var(--ajn-footer-padding-top-mobile);
                --ajn-footer-padding-bottom: var(--ajn-footer-padding-bottom-mobile);
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'ajnanda_customizer_css');

/**
 * Add theme support for Gutenberg
 */
function ajnanda_gutenberg_support() {
    add_theme_support('align-wide');
    add_theme_support('appearance-tools');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('custom-spacing');
    add_theme_support('custom-units', array('px', 'rem', 'em', '%', 'vh', 'vw'));
    add_theme_support('editor-color-palette', array(
        array(
            'name'  => __('Primary Blue', 'ajnanda'),
            'slug'  => 'primary-blue',
            'color' => '#2563eb',
        ),
        array(
            'name'  => __('Deep Blue', 'ajnanda'),
            'slug'  => 'deep-blue',
            'color' => '#1e40af',
        ),
        array(
            'name'  => __('Purple', 'ajnanda'),
            'slug'  => 'purple',
            'color' => '#7c3aed',
        ),
        array(
            'name'  => __('Gold', 'ajnanda'),
            'slug'  => 'gold',
            'color' => '#f59e0b',
        ),
        array(
            'name'  => __('Ink', 'ajnanda'),
            'slug'  => 'ink',
            'color' => '#111827',
        ),
        array(
            'name'  => __('Soft Gray', 'ajnanda'),
            'slug'  => 'soft-gray',
            'color' => '#f3f4f6',
        ),
        array(
            'name'  => __('White', 'ajnanda'),
            'slug'  => 'white',
            'color' => '#ffffff',
        ),
    ));
    add_editor_style('style.css');
}
add_action('after_setup_theme', 'ajnanda_gutenberg_support');

require_once get_template_directory() . '/inc/github-theme-updater.php';
require_once get_template_directory() . '/inc/duplicate-content.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/blocks/ajnanda-blocks/loader.php';

/**
 * AJNanda Site Builder: pattern categories/styles, page designs, starter
 * sites, admin UI, and WP-CLI commands. See docs/development.md.
 */
require_once get_template_directory() . '/inc/site-builder.php';

// =============================================================================
// Menu Visibility Toggles
// Show/hide menus by device, control floating vs inline, sticky behaviour.
// Settings appear in Appearance → Menus → Manage Locations tab.
// =============================================================================

define('AJNANDA_MENU_TOGGLES_OPTION', 'ajnanda_menu_toggles');

function ajnanda_menu_toggle_defaults(): array {
    return [
        'left_panel_enabled'             => 0,
        'left_panel_label'               => 'Left Floater Panel',
        'right_panel_enabled'            => 0,
        'right_panel_label'              => 'Right Floater Panel',
        'top_navigation'                 => 1,
        'top_navigation_desktop'         => 1,
        'top_navigation_tablet'          => 1,
        'top_navigation_mobile'          => 1,
        'top_navigation_sticky'          => 0,
        'bottom_navigation'              => 1,
        'bottom_navigation_desktop'      => 1,
        'bottom_navigation_tablet'       => 1,
        'bottom_navigation_mobile'       => 1,
        'bottom_navigation_sticky'       => 0,
        'office_shortcuts'               => 1,
        'office_shortcuts_desktop'       => 1,
        'office_shortcuts_tablet'        => 0,
        'office_shortcuts_mobile'        => 0,
        'office_shortcuts_mode'          => 'floating',
        'office_shortcuts_exclude_urls'  => '',
        'store_shortcuts'                => 1,
        'store_shortcuts_desktop'        => 1,
        'store_shortcuts_tablet'         => 0,
        'store_shortcuts_mobile'         => 0,
        'store_shortcuts_mode'           => 'floating',
        'store_shortcuts_exclude_urls'   => '',
        'panel_menu_width'               => 270,
        'panel_menu_font_size'           => 19,
        'panel_menu_radius'              => 22,
        'panel_menu_primary_color'       => '#2563eb',
        'panel_menu_primary_hover_color' => '#1d4ed8',
    ];
}

function ajnanda_get_menu_toggles(): array {
    $saved = get_option(AJNANDA_MENU_TOGGLES_OPTION, []);
    if (!is_array($saved)) {
        $saved = [];
    }
    // One-time migration from ncllc_menu_toggles (renamed to ajnanda_menu_toggles)
    if (empty($saved)) {
        $legacy = get_option('ncllc_menu_toggles', []);
        if (!empty($legacy) && is_array($legacy)) {
            $saved = $legacy;
        }
    }
    // One-time migration from original upos_menu_toggles option
    if (empty($saved)) {
        $legacy = get_option('upos_menu_toggles', []);
        if (!empty($legacy) && is_array($legacy)) {
            $saved = $legacy;
        }
    }

    $merged = wp_parse_args($saved, ajnanda_menu_toggle_defaults());

    // Fall back to theme_mods for panel enable/label until user saves from Appearance → Menus.
    if (!array_key_exists('left_panel_enabled', $saved)) {
        $merged['left_panel_enabled'] = get_theme_mod('ajnanda_left_panel_enabled', false) ? 1 : 0;
    }
    if (!array_key_exists('left_panel_label', $saved)) {
        $merged['left_panel_label'] = get_theme_mod('ajnanda_left_panel_label', 'Left Floater Panel');
    }
    if (!array_key_exists('right_panel_enabled', $saved)) {
        $merged['right_panel_enabled'] = get_theme_mod('ajnanda_right_panel_enabled', false) ? 1 : 0;
    }
    if (!array_key_exists('right_panel_label', $saved)) {
        $merged['right_panel_label'] = get_theme_mod('ajnanda_right_panel_label', 'Right Floater Panel');
    }

    return $merged;
}

function ajnanda_menu_toggle_enabled(string $key): bool {
    return !empty(ajnanda_get_menu_toggles()[$key]);
}

function ajnanda_menu_toggle_mode(string $key): string {
    $mode = ajnanda_get_menu_toggles()[$key] ?? 'floating';
    return in_array($mode, ['floating', 'inline'], true) ? $mode : 'floating';
}

function ajnanda_menu_toggle_visible_on_device(string $prefix, string $device): bool {
    return !empty(ajnanda_get_menu_toggles()["{$prefix}_{$device}"]);
}

function ajnanda_menu_toggle_visibility_classes(string $prefix): string {
    $classes = [];
    foreach (['desktop', 'tablet', 'mobile'] as $device) {
        if (ajnanda_menu_toggle_visible_on_device($prefix, $device)) {
            $classes[] = "upos-show-{$device}";
        }
    }
    return implode(' ', $classes);
}

function ajnanda_menu_toggle_url_matches_patterns(string $patterns): bool {
    if ('' === trim($patterns)) {
        return false;
    }

    $request_uri  = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
    $request_path = wp_parse_url($request_uri, PHP_URL_PATH);
    $request_path = '/' . trim((string) $request_path, '/');
    if ('/' !== $request_path) {
        $request_path = untrailingslashit($request_path);
    }

    foreach (preg_split('/\r\n|\r|\n/', $patterns) as $pattern) {
        $pattern = trim((string) $pattern);
        if ('' === $pattern) {
            continue;
        }

        $pattern_path = wp_parse_url($pattern, PHP_URL_PATH);
        $pattern_path = null === $pattern_path ? $pattern : $pattern_path;
        $pattern_path = '/' . trim((string) $pattern_path, '/');
        if ('/' !== $pattern_path) {
            $pattern_path = untrailingslashit($pattern_path);
        }

        $quoted = preg_quote($pattern_path, '#');
        $regex  = '#^' . str_replace('\*', '.*', $quoted) . '$#';
        if (preg_match($regex, $request_path)) {
            return true;
        }
    }

    return false;
}

function ajnanda_render_menu_device_checkboxes(string $prefix, array $settings): void {
    $option = AJNANDA_MENU_TOGGLES_OPTION;
    $labels = [
        'desktop' => __('Computer', 'ajnanda'),
        'tablet'  => __('Tablet', 'ajnanda'),
        'mobile'  => __('Phone', 'ajnanda'),
    ];
    ?>
    <p style="margin:10px 0 0;"><strong><?php esc_html_e('Display on:', 'ajnanda'); ?></strong></p>
    <p style="margin-top:8px;">
        <?php foreach ($labels as $device => $label) : ?>
            <label style="display:inline-block;margin:0 18px 8px 0;">
                <input type="checkbox"
                    name="<?php echo esc_attr($option); ?>[<?php echo esc_attr("{$prefix}_{$device}"); ?>]"
                    value="1"
                    <?php checked(!empty($settings["{$prefix}_{$device}"])); ?>>
                <?php echo esc_html($label); ?>
            </label>
        <?php endforeach; ?>
    </p>
    <?php
}

function ajnanda_render_menu_exclude_urls_field(string $prefix, array $settings): void {
    $option = AJNANDA_MENU_TOGGLES_OPTION;
    ?>
    <p style="margin-top:10px;">
        <label for="<?php echo esc_attr("ajnanda-{$prefix}-exclude-urls"); ?>"><strong><?php esc_html_e('Hide on URL paths:', 'ajnanda'); ?></strong></label>
        <textarea
            id="<?php echo esc_attr("ajnanda-{$prefix}-exclude-urls"); ?>"
            name="<?php echo esc_attr($option); ?>[<?php echo esc_attr("{$prefix}_exclude_urls"); ?>]"
            rows="3"
            style="display:block;width:100%;max-width:520px;margin-top:6px;"><?php echo esc_textarea($settings["{$prefix}_exclude_urls"] ?? ''); ?></textarea>
        <span style="display:block;margin-top:4px;color:#646970;font-size:12px;"><?php esc_html_e('One path per line. Use * as a wildcard, for example /client-portal* or /client-portal/*.', 'ajnanda'); ?></span>
    </p>
    <?php
}

function ajnanda_render_panel_menu_style_fields(array $settings): void {
    $option = AJNANDA_MENU_TOGGLES_OPTION;
    ?>
    <p style="margin-top:8px;color:#646970;"><?php esc_html_e('Shared styling for the left and right floating panel menus.', 'ajnanda'); ?></p>
    <p style="display:flex;flex-wrap:wrap;gap:14px;margin-top:10px;">
        <label>
            <?php esc_html_e('Width (px)', 'ajnanda'); ?><br>
            <input type="number" min="180" max="420" step="1" name="<?php echo esc_attr($option); ?>[panel_menu_width]" value="<?php echo esc_attr((int) ($settings['panel_menu_width'] ?? 270)); ?>" style="width:110px;">
        </label>
        <label>
            <?php esc_html_e('Font size (px)', 'ajnanda'); ?><br>
            <input type="number" min="12" max="32" step="1" name="<?php echo esc_attr($option); ?>[panel_menu_font_size]" value="<?php echo esc_attr((int) ($settings['panel_menu_font_size'] ?? 19)); ?>" style="width:110px;">
        </label>
        <label>
            <?php esc_html_e('Corner radius (px)', 'ajnanda'); ?><br>
            <input type="number" min="0" max="48" step="1" name="<?php echo esc_attr($option); ?>[panel_menu_radius]" value="<?php echo esc_attr((int) ($settings['panel_menu_radius'] ?? 22)); ?>" style="width:110px;">
        </label>
        <label>
            <?php esc_html_e('Primary color', 'ajnanda'); ?><br>
            <input type="color" name="<?php echo esc_attr($option); ?>[panel_menu_primary_color]" value="<?php echo esc_attr($settings['panel_menu_primary_color'] ?? '#2563eb'); ?>">
        </label>
        <label>
            <?php esc_html_e('Primary hover', 'ajnanda'); ?><br>
            <input type="color" name="<?php echo esc_attr($option); ?>[panel_menu_primary_hover_color]" value="<?php echo esc_attr($settings['panel_menu_primary_hover_color'] ?? '#1d4ed8'); ?>">
        </label>
    </p>
    <?php
}

function ajnanda_render_panel_menu(string $location, string $prefix, string $side, string $label): void {
    $settings = ajnanda_get_menu_toggles();

    if ('left' === $side && function_exists('upos_get_office_shortcuts_data')) {
        return;
    }
    if ('right' === $side && function_exists('upos_get_store_shortcuts_data')) {
        return;
    }

    if (empty($settings["{$side}_panel_enabled"]) || empty($settings[$prefix])) {
        return;
    }

    if (ajnanda_menu_toggle_url_matches_patterns((string) ($settings["{$prefix}_exclude_urls"] ?? ''))) {
        return;
    }

    if (!has_nav_menu($location)) {
        return;
    }

    $mode    = ajnanda_menu_toggle_mode($prefix);
    $classes = array_filter([
        'ajnanda-panel-menu',
        "ajnanda-{$side}-panel-menu",
        "ajnanda-{$side}-floating-panel",
        "{$side}-panel-menu",
        "{$side}-floating-panel",
        "floating-panel-{$side}",
        str_replace('_', '-', $prefix),
        "menu-" . str_replace('_', '-', $prefix),
        "ajnanda-panel-menu-{$mode}",
        ajnanda_menu_toggle_visibility_classes($prefix),
    ]);
    ?>
    <nav class="<?php echo esc_attr(implode(' ', $classes)); ?>" aria-label="<?php echo esc_attr($label); ?>">
        <?php if ('right' === $side) : ?>
            <span class="ajnanda-panel-menu-label"><?php echo esc_html($label); ?></span>
        <?php endif; ?>
        <?php
        wp_nav_menu(array(
            'theme_location' => $location,
            'menu_class'     => 'ajnanda-panel-menu-list',
            'container'      => false,
            'fallback_cb'    => false,
            'depth'          => 2,
        ));
        ?>
    </nav>
    <?php
}

function ajnanda_render_panel_menus(): void {
    $settings = ajnanda_get_menu_toggles();

    ajnanda_render_panel_menu(
        'office_shortcuts',
        'office_shortcuts',
        'left',
        (string) ($settings['left_panel_label'] ?? __('Left Floater Panel', 'ajnanda'))
    );

    ajnanda_render_panel_menu(
        'store_shortcuts',
        'store_shortcuts',
        'right',
        (string) ($settings['right_panel_label'] ?? __('Right Floater Panel', 'ajnanda'))
    );
}
add_action('wp_footer', 'ajnanda_render_panel_menus', 18);

add_action('admin_init', function (): void {
    register_setting(
        'ajnanda_menu_toggles',
        AJNANDA_MENU_TOGGLES_OPTION,
        [
            'type'              => 'array',
            'sanitize_callback' => static function ($value): array {
                $value     = is_array($value) ? $value : [];
                $defaults  = ajnanda_menu_toggle_defaults();
                $sanitized = [];
                foreach (array_keys($defaults) as $key) {
                    if (in_array($key, ['left_panel_label', 'right_panel_label'], true)) {
                        $sanitized[$key] = sanitize_text_field($value[$key] ?? $defaults[$key]);
                        continue;
                    }
                    if (str_ends_with($key, '_exclude_urls')) {
                        $lines = preg_split('/\r\n|\r|\n/', (string) ($value[$key] ?? ''));
                        $lines = array_filter(array_map(static function ($line): string {
                            return sanitize_text_field(trim((string) $line));
                        }, is_array($lines) ? $lines : []));

                        $sanitized[$key] = implode("\n", $lines);
                        continue;
                    }
                    if (in_array($key, ['panel_menu_width', 'panel_menu_font_size', 'panel_menu_radius'], true)) {
                        $limits = [
                            'panel_menu_width'     => [180, 420],
                            'panel_menu_font_size' => [12, 32],
                            'panel_menu_radius'    => [0, 48],
                        ];
                        $number = absint($value[$key] ?? $defaults[$key]);
                        $sanitized[$key] = max($limits[$key][0], min($limits[$key][1], $number));
                        continue;
                    }
                    if (in_array($key, ['panel_menu_primary_color', 'panel_menu_primary_hover_color'], true)) {
                        $color = sanitize_hex_color($value[$key] ?? $defaults[$key]);
                        $sanitized[$key] = $color ?: $defaults[$key];
                        continue;
                    }
                    if (str_ends_with($key, '_mode')) {
                        $sanitized[$key] = ($value[$key] ?? 'floating') === 'inline' ? 'inline' : 'floating';
                        continue;
                    }
                    $sanitized[$key] = empty($value[$key]) ? 0 : 1;
                }
                return $sanitized;
            },
            'default' => ajnanda_menu_toggle_defaults(),
        ]
    );
});

add_action('admin_notices', function (): void {
    $screen = get_current_screen();
    if (!$screen || 'nav-menus' !== $screen->id) {
        return;
    }
    if (!empty($_GET['settings-updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>'
            . esc_html__('Menu visibility settings saved.', 'ajnanda')
            . '</p></div>';
    }
});

// When the menu toggles option is saved, sync panel enable/label back to theme_mods
// so that mu-plugins (which read theme_mods during after_setup_theme) stay in sync.
add_action('update_option_' . AJNANDA_MENU_TOGGLES_OPTION, function ($old_value, $new_value): void {
    if (!is_array($new_value)) {
        return;
    }
    if (array_key_exists('left_panel_enabled', $new_value)) {
        set_theme_mod('ajnanda_left_panel_enabled', !empty($new_value['left_panel_enabled']));
    }
    if (array_key_exists('left_panel_label', $new_value) && '' !== trim((string) $new_value['left_panel_label'])) {
        set_theme_mod('ajnanda_left_panel_label', sanitize_text_field($new_value['left_panel_label']));
    }
    if (array_key_exists('right_panel_enabled', $new_value)) {
        set_theme_mod('ajnanda_right_panel_enabled', !empty($new_value['right_panel_enabled']));
    }
    if (array_key_exists('right_panel_label', $new_value) && '' !== trim((string) $new_value['right_panel_label'])) {
        set_theme_mod('ajnanda_right_panel_label', sanitize_text_field($new_value['right_panel_label']));
    }
}, 10, 2);

add_action('admin_footer-nav-menus.php', function (): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings    = ajnanda_get_menu_toggles();
    $option      = AJNANDA_MENU_TOGGLES_OPTION;
    $group       = 'ajnanda_menu_toggles';
    $positions   = [
        'top_left'     => __('Top Left', 'ajnanda'),
        'top_right'    => __('Top Right', 'ajnanda'),
        'bottom_left'  => __('Bottom Left', 'ajnanda'),
        'bottom_right' => __('Bottom Right', 'ajnanda'),
    ];
    ?>
    <div id="ajnanda-menu-toggles-panel" style="display:none;">
        <hr style="margin:28px 0 18px;border-top:1px solid #ddd;">
        <h3 style="margin:0 0 6px;color:#1d2327;font-size:14px;"><?php esc_html_e('Menu Visibility &amp; Behaviour', 'ajnanda'); ?></h3>
        <p style="margin:0 0 16px;color:#646970;"><?php esc_html_e('Control which menus appear and how they behave on different devices.', 'ajnanda'); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields($group); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><strong><?php esc_html_e('Top Navigation', 'ajnanda'); ?></strong></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($option); ?>[top_navigation]" value="1" <?php checked(!empty($settings['top_navigation'])); ?>> <?php esc_html_e('Show the main top menu', 'ajnanda'); ?></label>
                            <?php ajnanda_render_menu_device_checkboxes('top_navigation', $settings); ?>
                            <p style="margin-top:8px;"><label><input type="checkbox" name="<?php echo esc_attr($option); ?>[top_navigation_sticky]" value="1" <?php checked(!empty($settings['top_navigation_sticky'])); ?>> <?php esc_html_e('Keep the top navigation sticky while scrolling', 'ajnanda'); ?></label></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><strong><?php esc_html_e('Left Panel Menu', 'ajnanda'); ?></strong></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($option); ?>[left_panel_enabled]" value="1" <?php checked(!empty($settings['left_panel_enabled'])); ?>> <?php esc_html_e('Enable left floating panel menu', 'ajnanda'); ?></label>
                            <p style="margin-top:8px;">
                                <label><?php esc_html_e('Panel label:', 'ajnanda'); ?> <input type="text" name="<?php echo esc_attr($option); ?>[left_panel_label]" value="<?php echo esc_attr($settings['left_panel_label']); ?>" style="width:260px;"></label>
                                <span style="display:block;margin-top:4px;color:#646970;font-size:12px;"><?php esc_html_e('Shown as the menu location name in Appearance → Menus → Manage Locations.', 'ajnanda'); ?></span>
                            </p>
                            <label style="margin-top:8px;display:block;"><input type="checkbox" name="<?php echo esc_attr($option); ?>[office_shortcuts]" value="1" <?php checked(!empty($settings['office_shortcuts'])); ?>> <?php esc_html_e('Show on site', 'ajnanda'); ?></label>
                            <?php ajnanda_render_menu_device_checkboxes('office_shortcuts', $settings); ?>
                            <p style="margin-top:8px;">
                                <label style="margin-right:16px;"><input type="radio" name="<?php echo esc_attr($option); ?>[office_shortcuts_mode]" value="floating" <?php checked(($settings['office_shortcuts_mode'] ?? 'floating') === 'floating'); ?>> <?php esc_html_e('Floating', 'ajnanda'); ?></label>
                                <label><input type="radio" name="<?php echo esc_attr($option); ?>[office_shortcuts_mode]" value="inline" <?php checked(($settings['office_shortcuts_mode'] ?? 'floating') === 'inline'); ?>> <?php esc_html_e('Inline / non-floating', 'ajnanda'); ?></label>
                            </p>
                            <?php ajnanda_render_menu_exclude_urls_field('office_shortcuts', $settings); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><strong><?php esc_html_e('Right Panel Menu', 'ajnanda'); ?></strong></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($option); ?>[right_panel_enabled]" value="1" <?php checked(!empty($settings['right_panel_enabled'])); ?>> <?php esc_html_e('Enable right floating panel menu', 'ajnanda'); ?></label>
                            <p style="margin-top:8px;">
                                <label><?php esc_html_e('Panel label:', 'ajnanda'); ?> <input type="text" name="<?php echo esc_attr($option); ?>[right_panel_label]" value="<?php echo esc_attr($settings['right_panel_label']); ?>" style="width:260px;"></label>
                                <span style="display:block;margin-top:4px;color:#646970;font-size:12px;"><?php esc_html_e('Shown as the eyebrow text above the primary button on the right panel.', 'ajnanda'); ?></span>
                            </p>
                            <label style="margin-top:8px;display:block;"><input type="checkbox" name="<?php echo esc_attr($option); ?>[store_shortcuts]" value="1" <?php checked(!empty($settings['store_shortcuts'])); ?>> <?php esc_html_e('Show on site', 'ajnanda'); ?></label>
                            <?php ajnanda_render_menu_device_checkboxes('store_shortcuts', $settings); ?>
                            <p style="margin-top:8px;">
                                <label style="margin-right:16px;"><input type="radio" name="<?php echo esc_attr($option); ?>[store_shortcuts_mode]" value="floating" <?php checked(($settings['store_shortcuts_mode'] ?? 'floating') === 'floating'); ?>> <?php esc_html_e('Floating', 'ajnanda'); ?></label>
                                <label><input type="radio" name="<?php echo esc_attr($option); ?>[store_shortcuts_mode]" value="inline" <?php checked(($settings['store_shortcuts_mode'] ?? 'floating') === 'inline'); ?>> <?php esc_html_e('Inline / non-floating', 'ajnanda'); ?></label>
                            </p>
                            <?php ajnanda_render_menu_exclude_urls_field('store_shortcuts', $settings); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><strong><?php esc_html_e('Bottom Navigation', 'ajnanda'); ?></strong></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($option); ?>[bottom_navigation]" value="1" <?php checked(!empty($settings['bottom_navigation'])); ?>> <?php esc_html_e('Show the footer menu', 'ajnanda'); ?></label>
                            <?php ajnanda_render_menu_device_checkboxes('bottom_navigation', $settings); ?>
                            <p style="margin-top:8px;"><label><input type="checkbox" name="<?php echo esc_attr($option); ?>[bottom_navigation_sticky]" value="1" <?php checked(!empty($settings['bottom_navigation_sticky'])); ?>> <?php esc_html_e('Keep the footer menu visible at the bottom of the screen', 'ajnanda'); ?></label></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><strong><?php esc_html_e('Panel Menu Style', 'ajnanda'); ?></strong></th>
                        <td>
                            <?php ajnanda_render_panel_menu_style_fields($settings); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(__('Save Visibility Settings', 'ajnanda')); ?>
        </form>
    </div>
    <script>
    jQuery(function ($) {
        var $panel  = $('#ajnanda-menu-toggles-panel');
        var $target = $('#menu-locations-wrap, #manage-locations, form[action*="action=locations"]').first();
        if ($target.length) {
            $target.after($panel.show());
        } else {
            $('#wpbody-content .wrap').append($panel.show());
        }
    });
    </script>
    <?php
});

add_action('wp_head', function (): void {
    $nav  = '.main-navigation, .ajn-builder-cell-primary-menu, #mobile-menu-toggle';
    $foot = '.ajn-builder-cell-footer-menu, .site-footer .nav-menu';
    $left_panel = implode(', ', [
        '.upos-office-shortcuts',
        '.upos-left-panel',
        '.upos-floating-left',
        '.ajnanda-left-panel-menu',
        '.ajnanda-left-floating-panel',
        '.left-panel-menu',
        '.left-floating-panel',
        '.floating-panel-left',
        '.office-shortcuts',
        '.menu-office-shortcuts',
    ]);
    $right_panel = implode(', ', [
        '.upos-store-shortcuts',
        '.upos-right-panel',
        '.upos-floating-right',
        '.ajnanda-right-panel-menu',
        '.ajnanda-right-floating-panel',
        '.right-panel-menu',
        '.right-floating-panel',
        '.floating-panel-right',
        '.store-shortcuts',
        '.menu-store-shortcuts',
    ]);
    $settings = ajnanda_get_menu_toggles();
    $panel_width         = max(180, min(420, absint($settings['panel_menu_width'] ?? 270)));
    $panel_font_size     = max(12, min(32, absint($settings['panel_menu_font_size'] ?? 19)));
    $panel_radius        = max(0, min(48, absint($settings['panel_menu_radius'] ?? 22)));
    $panel_primary       = sanitize_hex_color($settings['panel_menu_primary_color'] ?? '#2563eb') ?: '#2563eb';
    $panel_primary_hover = sanitize_hex_color($settings['panel_menu_primary_hover_color'] ?? '#1d4ed8') ?: '#1d4ed8';
    $panel_inner_radius  = max(0, $panel_radius - 6);
    $css  = ".ajnanda-panel-menu { --ajn-panel-width: {$panel_width}px; --ajn-panel-font-size: {$panel_font_size}px; --ajn-panel-radius: {$panel_radius}px; --ajn-panel-inner-radius: {$panel_inner_radius}px; --ajn-panel-primary: {$panel_primary}; --ajn-panel-primary-hover: {$panel_primary_hover}; z-index:145; width:var(--ajn-panel-width); max-width:min(var(--ajn-panel-width), calc(100vw - 32px)); padding:20px; border-radius:var(--ajn-panel-radius); font-family:inherit; backdrop-filter:blur(12px); }\n";
    $css .= ".ajnanda-panel-menu-floating { position:fixed; top:330px; }\n";
    $css .= ".ajnanda-left-panel-menu.ajnanda-panel-menu-floating { left:max(16px, calc((100vw - 1200px) / 2 - 300px)); }\n";
    $css .= ".ajnanda-right-panel-menu.ajnanda-panel-menu-floating { right:max(16px, calc((100vw - 1200px) / 2 - 300px)); }\n";
    $css .= "body.admin-bar .ajnanda-panel-menu-floating { top:362px; }\n";
    $css .= ".ajnanda-panel-menu-inline { position:static; margin:24px auto; }\n";
    $css .= ".ajnanda-left-panel-menu { background:rgba(36,45,48,0.94); border:1px solid rgba(220,230,226,0.12); box-shadow:0 16px 36px rgba(0,0,0,0.18); }\n";
    $css .= ".ajnanda-right-panel-menu { background:rgba(255,255,255,0.94); border:1px solid rgba(2,71,81,0.12); box-shadow:0 16px 36px rgba(2,71,81,0.12); }\n";
    $css .= ".ajnanda-panel-menu-label { display:block; margin:0 0 12px; font-size:18px; font-weight:800; letter-spacing:0.12em; line-height:1.2; text-transform:uppercase; }\n";
    $css .= ".ajnanda-left-panel-menu .ajnanda-panel-menu-label { color:#dce6e2; }\n";
    $css .= ".ajnanda-right-panel-menu .ajnanda-panel-menu-label { color:#4b5563; }\n";
    $css .= ".ajnanda-panel-menu-list, .ajnanda-panel-menu-list ul { display:grid; gap:10px; margin:0; padding:0; list-style:none; }\n";
    $css .= ".ajnanda-panel-menu-list ul { margin-top:10px; padding-left:12px; }\n";
    $css .= ".ajnanda-panel-menu-list a { display:block; padding:14px 16px; border-radius:var(--ajn-panel-inner-radius); font-weight:600; font-size:var(--ajn-panel-font-size); line-height:1.35; text-decoration:none; }\n";
    $css .= ".ajnanda-panel-menu-list > li:first-child > a { margin-bottom:2px; padding:16px 18px; background:var(--ajn-panel-primary); border:1px solid var(--ajn-panel-primary); color:#ffffff; font-weight:700; line-height:1.2; text-align:center; }\n";
    $css .= ".ajnanda-panel-menu-list > li:first-child > a:hover, .ajnanda-panel-menu-list > li:first-child > a:focus { background:var(--ajn-panel-primary-hover); border-color:var(--ajn-panel-primary-hover); color:#ffffff; }\n";
    $css .= ".ajnanda-left-panel-menu .ajnanda-panel-menu-list li:not(:first-child) > a, .ajnanda-left-panel-menu .ajnanda-panel-menu-list ul a { background:#2d383b; border:1px solid rgba(220,230,226,0.08); color:#dce6e2; }\n";
    $css .= ".ajnanda-left-panel-menu .ajnanda-panel-menu-list li:not(:first-child) > a:hover, .ajnanda-left-panel-menu .ajnanda-panel-menu-list li:not(:first-child) > a:focus { background:#37464a; border-color:rgba(147,197,253,0.22); color:#ffffff; }\n";
    $css .= ".ajnanda-right-panel-menu .ajnanda-panel-menu-list li:not(:first-child) > a, .ajnanda-right-panel-menu .ajnanda-panel-menu-list ul a { background:#f8fbff; border:1px solid rgba(2,71,81,0.1); color:#0f172a; }\n";
    $css .= ".ajnanda-right-panel-menu .ajnanda-panel-menu-list li:not(:first-child) > a:hover, .ajnanda-right-panel-menu .ajnanda-panel-menu-list li:not(:first-child) > a:focus { background:#eff6ff; border-color:rgba(37,99,235,0.24); color:var(--ajn-panel-primary-hover); }\n";
    $css .= "@media (max-width:1200px) and (min-width:922px) { .ajnanda-left-panel-menu.ajnanda-panel-menu-floating { left:16px; } .ajnanda-right-panel-menu.ajnanda-panel-menu-floating { right:16px; } .ajnanda-panel-menu { --ajn-panel-width:min({$panel_width}px, 230px); } }\n";
    $css .= "@media (min-width:768px) and (max-width:921px) { .ajnanda-panel-menu-floating { top:220px; } .ajnanda-left-panel-menu.ajnanda-panel-menu-floating { left:16px; } .ajnanda-right-panel-menu.ajnanda-panel-menu-floating { right:16px; } }\n";
    $css .= "@media (max-width:767px) { .ajnanda-panel-menu-floating, body.admin-bar .ajnanda-panel-menu-floating { position:static; width:auto; max-width:none; margin:24px 16px; } }\n";

    if (!ajnanda_menu_toggle_enabled('top_navigation')) {
        $css .= "$nav { display:none !important; }\n";
    } else {
        if (!ajnanda_menu_toggle_visible_on_device('top_navigation', 'desktop')) {
            $css .= "@media (min-width:922px) { $nav { display:none !important; } }\n";
        }
        if (!ajnanda_menu_toggle_visible_on_device('top_navigation', 'tablet')) {
            $css .= "@media (min-width:768px) and (max-width:921px) { $nav { display:none !important; } }\n";
        }
        if (!ajnanda_menu_toggle_visible_on_device('top_navigation', 'mobile')) {
            $css .= "@media (max-width:767px) { $nav { display:none !important; } }\n";
        }
    }

    if (ajnanda_menu_toggle_enabled('top_navigation_sticky')) {
        $css .= "header.site-header { position:sticky; top:0; z-index:150; }\n";
        $css .= "body.admin-bar header.site-header { top:32px; }\n";
        $css .= "@media (max-width:782px) { body.admin-bar header.site-header { top:46px; } }\n";
    }

    if (!ajnanda_menu_toggle_enabled('bottom_navigation')) {
        $css .= "$foot { display:none !important; }\n";
    } else {
        if (!ajnanda_menu_toggle_visible_on_device('bottom_navigation', 'desktop')) {
            $css .= "@media (min-width:922px) { $foot { display:none !important; } }\n";
        }
        if (!ajnanda_menu_toggle_visible_on_device('bottom_navigation', 'tablet')) {
            $css .= "@media (min-width:768px) and (max-width:921px) { $foot { display:none !important; } }\n";
        }
        if (!ajnanda_menu_toggle_visible_on_device('bottom_navigation', 'mobile')) {
            $css .= "@media (max-width:767px) { $foot { display:none !important; } }\n";
        }
    }

    if (ajnanda_menu_toggle_enabled('bottom_navigation_sticky')) {
        $css .= ".ajn-builder-cell-footer-menu { position:fixed; left:50%; bottom:16px; transform:translateX(-50%); z-index:140; padding:10px 18px; border-radius:999px; background:rgba(36,45,48,0.92); border:1px solid rgba(220,230,226,0.1); box-shadow:0 18px 34px rgba(3,19,22,0.28); backdrop-filter:blur(12px); }\n";
    }

    if (
        !ajnanda_menu_toggle_enabled('left_panel_enabled')
        || !ajnanda_menu_toggle_enabled('office_shortcuts')
        || ajnanda_menu_toggle_url_matches_patterns((string) ($settings['office_shortcuts_exclude_urls'] ?? ''))
    ) {
        $css .= "$left_panel { display:none !important; }\n";
    } else {
        if (!ajnanda_menu_toggle_visible_on_device('office_shortcuts', 'desktop')) {
            $css .= "@media (min-width:922px) { $left_panel { display:none !important; } }\n";
        }
        if (!ajnanda_menu_toggle_visible_on_device('office_shortcuts', 'tablet')) {
            $css .= "@media (min-width:768px) and (max-width:921px) { $left_panel { display:none !important; } }\n";
        }
        if (!ajnanda_menu_toggle_visible_on_device('office_shortcuts', 'mobile')) {
            $css .= "@media (max-width:767px) { $left_panel { display:none !important; } }\n";
        }
    }

    if (
        !ajnanda_menu_toggle_enabled('right_panel_enabled')
        || !ajnanda_menu_toggle_enabled('store_shortcuts')
        || ajnanda_menu_toggle_url_matches_patterns((string) ($settings['store_shortcuts_exclude_urls'] ?? ''))
    ) {
        $css .= "$right_panel { display:none !important; }\n";
    } else {
        if (!ajnanda_menu_toggle_visible_on_device('store_shortcuts', 'desktop')) {
            $css .= "@media (min-width:922px) { $right_panel { display:none !important; } }\n";
        }
        if (!ajnanda_menu_toggle_visible_on_device('store_shortcuts', 'tablet')) {
            $css .= "@media (min-width:768px) and (max-width:921px) { $right_panel { display:none !important; } }\n";
        }
        if (!ajnanda_menu_toggle_visible_on_device('store_shortcuts', 'mobile')) {
            $css .= "@media (max-width:767px) { $right_panel { display:none !important; } }\n";
        }
    }

    // A floating side panel sits fixed 16px off the viewport edge (full $panel_width wide above
    // 1200px, min($panel_width, 230) between 922-1200px per the media query above) — but
    // constrained content only gets var(--wp--style--global--content-size) (950px by default)
    // centered in the viewport, and that centered margin is narrower than the panel's footprint
    // across a wide, very common band of laptop/desktop widths (roughly 922-1522px at the default
    // 270px panel width). Content near the edge of that centered box — e.g. the last column of a
    // 4-up stats row — silently renders underneath the panel instead of wrapping or making room.
    //
    // Deliberately scoped to .home-stats-section rather than overriding
    // --wp--style--global--content-size sitewide: shrinking that variable globally also crushed
    // unrelated sections that were never near the panel's edge (the 3-column feature-card grid
    // wrapped to one word per line at ~1024px) for no benefit, since they weren't the ones
    // colliding with anything. Apply the same clearance per-section, on whichever ones are
    // actually alignfull rows with content that reaches toward the edge, as those get reported.
    $left_floating = ajnanda_menu_toggle_enabled('left_panel_enabled')
        && ajnanda_menu_toggle_enabled('office_shortcuts')
        && !ajnanda_menu_toggle_url_matches_patterns((string) ($settings['office_shortcuts_exclude_urls'] ?? ''))
        && ajnanda_menu_toggle_visible_on_device('office_shortcuts', 'desktop')
        && ('floating' === ($settings['office_shortcuts_mode'] ?? 'floating'));
    $right_floating = ajnanda_menu_toggle_enabled('right_panel_enabled')
        && ajnanda_menu_toggle_enabled('store_shortcuts')
        && !ajnanda_menu_toggle_url_matches_patterns((string) ($settings['store_shortcuts_exclude_urls'] ?? ''))
        && ajnanda_menu_toggle_visible_on_device('store_shortcuts', 'desktop')
        && ('floating' === ($settings['store_shortcuts_mode'] ?? 'floating'));

    if ($left_floating || $right_floating) {
        // +32px beyond the panel's bare edge: the exact-minimum boundary leaves zero gap (content's
        // computed edge lands precisely on the panel's computed edge), which reads as touching or
        // overlapping in practice once real glyph rendering (bold gradient text, antialiasing) is
        // involved rather than as clean clearance.
        $clearance_buffer = 32;
        $tier1_footprint = min($panel_width, 230) + 16 + $clearance_buffer; // 922-1199px, panel width capped per the media query above
        $tier2_footprint = $panel_width + 16 + $clearance_buffer;           // 1200px+, panel at its full configured width
        $tier2_safe_vw   = 950 + (2 * $tier2_footprint); // viewport width where default 950px content-size is already clear

        // Sections known to run a wide row of content close to the edge. Add more selectors here
        // as other alignfull sections turn out to need it -- keep it opt-in per section rather
        // than global.
        $edge_sensitive_selectors = '.home-stats-section .wp-block-columns';

        $css .= "@media (min-width:922px) and (max-width:1199px) { {$edge_sensitive_selectors} { max-width: calc(100vw - " . (2 * $tier1_footprint) . "px) !important; } }\n";
        $css .= "@media (min-width:1200px) and (max-width:{$tier2_safe_vw}px) { {$edge_sensitive_selectors} { max-width: calc(100vw - " . (2 * $tier2_footprint) . "px) !important; } }\n";
    }

    if ($css) {
        echo '<style id="ajnanda-menu-toggles-css">' . $css . '</style>' . "\n";
    }
}, 99);
