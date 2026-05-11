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
function ncllc_pro_setup() {
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
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'ncllc-pro'),
        'footer'  => __('Footer Menu', 'ncllc-pro'),
    ));
}
add_action('after_setup_theme', 'ncllc_pro_setup');

/**
 * Enqueue scripts and styles
 */
function ncllc_pro_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('ncllc-pro-style', get_stylesheet_uri(), array(), '1.1.1');
    
    // Enqueue custom JavaScript
    wp_enqueue_script('ncllc-pro-script', get_template_directory_uri() . '/js/main.js', array('jquery'), '1.1.1', true);
    
    // Localize script
    wp_localize_script('ncllc-pro-script', 'ncllcData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ncllc-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'ncllc_pro_scripts');

/**
 * Load the same page-section styling inside the block editor.
 */
function ncllc_pro_block_editor_assets() {
    wp_enqueue_style(
        'ncllc-pro-editor-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&display=swap',
        array(),
        null
    );

    wp_enqueue_style('ncllc-pro-editor-style', get_stylesheet_uri(), array(), '1.1.1');
    wp_enqueue_script(
        'ncllc-pro-editor-controls',
        get_template_directory_uri() . '/js/editor-controls.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks'),
        '1.1.1',
        true
    );
}
add_action('enqueue_block_editor_assets', 'ncllc_pro_block_editor_assets');

/**
 * Register widget areas
 */
function ncllc_pro_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar', 'ncllc-pro'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here.', 'ncllc-pro'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    for ($i = 1; $i <= 4; $i++) {
        register_sidebar(array(
            'name'          => sprintf(__('Header Builder Widget %d', 'ncllc-pro'), $i),
            'id'            => 'header-builder-' . $i,
            'description'   => __('Use this widget area inside AJNanda header builder slots.', 'ncllc-pro'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));

        register_sidebar(array(
            'name'          => sprintf(__('Footer Builder Widget %d', 'ncllc-pro'), $i),
            'id'            => 'footer-builder-' . $i,
            'description'   => __('Use this widget area inside AJNanda footer builder slots.', 'ncllc-pro'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));
    }
}
add_action('widgets_init', 'ncllc_pro_widgets_init');

/**
 * Keep builder widget areas available while editing the Customizer.
 */
function ncllc_pro_keep_builder_widget_sections_active($active, $section) {
    if (empty($section->id)) {
        return $active;
    }

    if (0 === strpos($section->id, 'sidebar-widgets-header-builder-') || 0 === strpos($section->id, 'sidebar-widgets-footer-builder-')) {
        return true;
    }

    return $active;
}
add_filter('customize_section_active', 'ncllc_pro_keep_builder_widget_sections_active', 10, 2);

/**
 * Custom excerpt length
 */
function ncllc_pro_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'ncllc_pro_excerpt_length', 999);

/**
 * Custom excerpt more
 */
function ncllc_pro_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'ncllc_pro_excerpt_more');

/**
 * Convert saved Spectra markup to native WordPress block markup when Spectra is inactive.
 */
function ncllc_pro_convert_spectra_markup_to_core($content) {
    if (false === strpos($content, 'wp-block-uagb-') || ncllc_pro_is_spectra_active()) {
        return $content;
    }

    if (!class_exists('DOMDocument')) {
        return ncllc_pro_convert_spectra_markup_to_core_basic($content);
    }

    $previous_errors = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $charset = get_bloginfo('charset') ? get_bloginfo('charset') : 'UTF-8';
    $encoded_content = function_exists('mb_convert_encoding') ? mb_convert_encoding($content, 'HTML-ENTITIES', $charset) : $content;
    $wrapped = '<!DOCTYPE html><html><body>' . $encoded_content . '</body></html>';

    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-buttons ")]') as $node) {
        ncllc_pro_replace_spectra_buttons_node($dom, $xpath, $node);
    }

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-marketing-button ")]') as $node) {
        ncllc_pro_replace_spectra_marketing_button_node($dom, $xpath, $node);
    }

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-advanced-heading ")]') as $node) {
        ncllc_pro_unwrap_spectra_node($dom, $node);
    }

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-image ")]') as $node) {
        ncllc_pro_replace_spectra_image_node($dom, $xpath, $node);
    }

    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-block-uagb-container ")]') as $node) {
        ncllc_pro_replace_spectra_container_node($dom, $node);
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
add_filter('the_content', 'ncllc_pro_convert_spectra_markup_to_core', 8);

function ncllc_pro_is_spectra_active() {
    return defined('UAGB_VER') || defined('UAGB_FILE') || class_exists('UAGB_Loader') || class_exists('UAGB_Init');
}

function ncllc_pro_replace_spectra_buttons_node($dom, $xpath, $node) {
    $buttons = $dom->createElement('div');
    $buttons->setAttribute('class', 'wp-block-buttons is-content-justification-center is-layout-flex');

    foreach ($xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " wp-block-button__link ")]', $node) as $link) {
        $button = $dom->createElement('div');
        $button->setAttribute('class', 'wp-block-button');
        $button->appendChild(ncllc_pro_clone_anchor_as_core_button($dom, $xpath, $link, './/*[contains(concat(" ", normalize-space(@class), " "), " uagb-button__link ")]'));
        $buttons->appendChild($button);
    }

    $node->parentNode->replaceChild($buttons, $node);
}

function ncllc_pro_replace_spectra_marketing_button_node($dom, $xpath, $node) {
    $buttons = $dom->createElement('div');
    $buttons->setAttribute('class', 'wp-block-buttons is-content-justification-center is-layout-flex');
    $link = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " wp-block-button__link ")]', $node)->item(0);

    if ($link) {
        $button = $dom->createElement('div');
        $button->setAttribute('class', 'wp-block-button');
        $button->appendChild(ncllc_pro_clone_anchor_as_core_button($dom, $xpath, $link, './/*[contains(concat(" ", normalize-space(@class), " "), " uagb-marketing-btn__title ")]'));
        $buttons->appendChild($button);
    }

    $node->parentNode->replaceChild($buttons, $node);
}

function ncllc_pro_clone_anchor_as_core_button($dom, $xpath, $link, $label_selector) {
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

function ncllc_pro_unwrap_spectra_node($dom, $node) {
    $fragment = $dom->createDocumentFragment();

    while ($node->firstChild) {
        $fragment->appendChild($node->firstChild);
    }

    $node->parentNode->replaceChild($fragment, $node);
}

function ncllc_pro_replace_spectra_image_node($dom, $xpath, $node) {
    $figure = $xpath->query('.//figure', $node)->item(0);

    if (!$figure) {
        ncllc_pro_unwrap_spectra_node($dom, $node);
        return;
    }

    $new_figure = $figure->cloneNode(true);
    $new_figure->setAttribute('class', 'wp-block-image');
    $node->parentNode->replaceChild($new_figure, $node);
}

function ncllc_pro_replace_spectra_container_node($dom, $node) {
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

function ncllc_pro_convert_spectra_markup_to_core_basic($content) {
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
function ncllc_pro_body_classes($classes) {
    if (!is_singular()) {
        $classes[] = 'hfeed';
    }
    
    if (is_front_page()) {
        $classes[] = 'ncllc-home';
    }
    
    return $classes;
}
add_filter('body_class', 'ncllc_pro_body_classes');

/**
 * Sanitize multiline Customizer text while preserving simple line breaks.
 */
function ncllc_pro_sanitize_textarea($value) {
    return implode("\n", array_map('sanitize_text_field', explode("\n", $value)));
}

/**
 * Sanitize responsive logo height values.
 */
function ncllc_pro_sanitize_logo_height($value) {
    $value = absint($value);

    return min(100, max(20, $value));
}

/**
 * Sanitize responsive header padding values.
 */
function ncllc_pro_sanitize_header_padding($value) {
    $value = (float) $value;

    return (string) min(2, max(0.5, $value));
}

/**
 * Sanitize Customizer select values.
 */
function ncllc_pro_sanitize_choice($value, $setting) {
    $control = $setting->manager->get_control($setting->id);
    $choices = $control && isset($control->choices) ? $control->choices : array();

    return array_key_exists($value, $choices) ? $value : $setting->default;
}

function ncllc_pro_sanitize_builder_width($value) {
    $value = absint($value);

    return min(6, max(1, $value));
}

function ncllc_pro_sanitize_builder_count($value) {
    $value = absint($value);

    return min(4, max(1, $value));
}

function ncllc_pro_sanitize_builder_row_count($value) {
    $value = absint($value);

    return min(6, max(1, $value));
}

/**
 * Sanitize CSS size values used by Customizer controls.
 */
function ncllc_pro_sanitize_css_size($value) {
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

/**
 * Sanitize CSS color values used by Customizer controls.
 */
function ncllc_pro_sanitize_css_color($value) {
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
function ncllc_pro_sanitize_css_background($value) {
    $value = trim((string) $value);

    if ('' === $value) {
        return '';
    }

    $color = ncllc_pro_sanitize_css_color($value);
    if ($color) {
        return $color;
    }

    if (preg_match('/^(linear|radial)-gradient\([#%.,\s0-9a-zA-Z-]+\)$/', $value) && false === stripos($value, 'url') && false === stripos($value, 'expression')) {
        return $value;
    }

    return '';
}

function ncllc_pro_sanitize_font_family($value) {
    $allowed = array('inherit', 'Inter', 'Poppins', 'Arial', 'Georgia', 'system-ui');

    return in_array($value, $allowed, true) ? $value : 'inherit';
}

function ncllc_pro_sanitize_font_weight($value) {
    $allowed = array('400', '500', '600', '700', '800');

    return in_array((string) $value, $allowed, true) ? (string) $value : '500';
}

function ncllc_pro_sanitize_checkbox($value) {
    return (bool) $value;
}

function ncllc_pro_sanitize_opacity($value) {
    $value = (float) $value;

    return (string) min(1, max(0, $value));
}


/**
 * Keep the Builder Canvas template visible in the page editor template picker.
 */
function ncllc_pro_register_page_templates($templates) {
    $templates['page-builder.php'] = __('Builder Canvas', 'ncllc-pro');

    return $templates;
}
add_filter('theme_page_templates', 'ncllc_pro_register_page_templates');

/**
 * Load the Builder Canvas file when it is selected for a page.
 */
function ncllc_pro_load_page_template($template) {
    if (is_page() && 'page-builder.php' === get_page_template_slug()) {
        $builder_template = locate_template('page-builder.php');

        if ($builder_template) {
            return $builder_template;
        }
    }

    return $template;
}
add_filter('template_include', 'ncllc_pro_load_page_template');

/**
 * Split an editable leading hero from the rest of a page's block content.
 */
function ncllc_pro_split_leading_builder_hero($content) {
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
 * Check whether a saved block layout attribute has a meaningful value.
 */
function ncllc_pro_has_block_layout_value($block, $keys) {
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
function ncllc_pro_normalize_css_size_value($value) {
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
function ncllc_pro_is_legacy_css_size_value($value, $legacy_defaults = array()) {
    $normalized_value = ncllc_pro_normalize_css_size_value($value);

    foreach ($legacy_defaults as $legacy_default) {
        if ($normalized_value === ncllc_pro_normalize_css_size_value($legacy_default)) {
            return true;
        }
    }

    return false;
}

/**
 * Old editor controls saved the former theme default height into some pages.
 * Treat those as theme defaults again so new compact Hero Defaults can win.
 */
function ncllc_pro_has_legacy_saved_hero_height($block) {
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
        if (isset($attrs[$key]) && ncllc_pro_is_legacy_css_size_value($attrs[$key], array('450px'))) {
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
function ncllc_pro_add_hero_layout_state_classes($block_content, $block) {
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
    $has_legacy_saved_hero_height = ncllc_pro_has_legacy_saved_hero_height($block);

    if (!$has_legacy_saved_hero_height && ncllc_pro_has_block_layout_value($block, $height_keys)) {
        $classes[] = 'ajn-has-height-override';
    }

    if (ncllc_pro_has_block_layout_value($block, $padding_keys)) {
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
add_filter('render_block', 'ncllc_pro_add_hero_layout_state_classes', 10, 2);

/**
 * Footer column defaults and saved Customizer values.
 */
function ncllc_pro_get_footer_defaults() {
    $site_name = get_bloginfo('name');
    $site_description = get_bloginfo('description');

    return array(
        1 => array(
            'title' => $site_name ? $site_name : __('Your Site Name', 'ncllc-pro'),
            'text'  => $site_description ? $site_description : __('Add a short description for this website in the Customizer footer settings.', 'ncllc-pro'),
        ),
        2 => array(
            'title' => __('Quick Links', 'ncllc-pro'),
            'text'  => "Home|/\nAbout|/about/\nContact|/contact/",
        ),
        3 => array(
            'title' => __('Resources', 'ncllc-pro'),
            'text'  => __('Add useful links, services, or resource names here.', 'ncllc-pro'),
        ),
        4 => array(
            'title' => __('Contact', 'ncllc-pro'),
            'text'  => __('Add location, hours, phone, email, or other contact details here.', 'ncllc-pro'),
        ),
    );
}

function ncllc_pro_get_footer_bottom_default() {
    $site_name = get_bloginfo('name');

    return sprintf(
        /* translators: %s: Site name. */
        __('%s. All rights reserved.', 'ncllc-pro'),
        $site_name ? $site_name : __('Your Site Name', 'ncllc-pro')
    );
}

function ncllc_pro_get_footer_columns() {
    $defaults = ncllc_pro_get_footer_defaults();

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
function ncllc_pro_render_footer_lines($text) {
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

function ncllc_pro_render_builder_site_identity() {
    if (has_custom_logo()) {
        the_custom_logo();
        return;
    }

    echo '<a href="' . esc_url(home_url('/')) . '" class="site-logo" rel="home">' . esc_html(get_bloginfo('name')) . '</a>';
}

function ncllc_pro_render_builder_menu($location, $class_name) {
    wp_nav_menu(array(
        'theme_location' => $location,
        'menu_class'     => $class_name,
        'container'      => false,
        'fallback_cb'    => false,
        'depth'          => 2,
    ));
}

function ncllc_pro_render_builder_element($builder, $element) {
    switch ($element) {
        case 'site-logo':
            ncllc_pro_render_builder_site_identity();
            break;
        case 'primary-menu':
            ncllc_pro_render_builder_menu('primary', 'nav-menu');
            break;
        case 'footer-menu':
            ncllc_pro_render_builder_menu('footer', 'footer-menu');
            break;
        case 'search':
            get_search_form();
            break;
        case 'button':
        case 'button-1':
            $button_text_setting = 'footer' === $builder ? 'ajn_footer_builder_button_text' : 'ajn_builder_button_text';
            $button_url_setting = 'footer' === $builder ? 'ajn_footer_builder_button_url' : 'ajn_builder_button_url';
            echo '<a class="btn btn-primary ajn-builder-button" href="' . esc_url(get_theme_mod($button_url_setting, home_url('/contact/'))) . '">' . esc_html(get_theme_mod($button_text_setting, __('Contact Us', 'ncllc-pro'))) . '</a>';
            break;
        case 'button-2':
            echo '<a class="btn btn-secondary ajn-builder-button ajn-builder-button-secondary" href="' . esc_url(get_theme_mod('ajn_builder_button_2_url', home_url('/contact/'))) . '">' . esc_html(get_theme_mod('ajn_builder_button_2_text', __('Learn More', 'ncllc-pro'))) . '</a>';
            break;
        case 'copyright':
            echo '<div class="ajn-builder-copyright">&copy; ' . esc_html(date('Y')) . ' ' . esc_html(get_theme_mod('footer_bottom_text', ncllc_pro_get_footer_bottom_default())) . '</div>';
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
            echo '<div class="ajn-builder-social">';
            echo '<a href="' . esc_url(get_theme_mod('ajn_builder_social_1_url', '#')) . '">' . esc_html(get_theme_mod('ajn_builder_social_1_label', __('Social', 'ncllc-pro'))) . '</a>';
            echo '</div>';
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

function ncllc_pro_render_builder_layout($builder) {
    $row_count = ncllc_pro_get_builder_row_count($builder);

    for ($row = 1; $row <= $row_count; $row++) {
        $row_output = '';
        $column_count = ncllc_pro_get_builder_row_columns($builder, $row);

        ob_start();
        for ($cell = 1; $cell <= $column_count; $cell++) {
            $element = ncllc_pro_get_builder_value($builder, $row, $cell);

            if ('none' === $element) {
                continue;
            }

            $width_desktop = ncllc_pro_get_builder_width($builder, $row, $cell, 'desktop');
            $width_tablet = ncllc_pro_get_builder_width($builder, $row, $cell, 'tablet');
            $width_mobile = ncllc_pro_get_builder_width($builder, $row, $cell, 'mobile');
            ?>
            <div
                class="ajn-builder-cell ajn-builder-cell-<?php echo esc_attr($element); ?>"
                style="--ajn-builder-width-desktop: <?php echo esc_attr($width_desktop); ?>; --ajn-builder-width-tablet: <?php echo esc_attr($width_tablet); ?>; --ajn-builder-width-mobile: <?php echo esc_attr($width_mobile); ?>;"
            >
                <?php ncllc_pro_render_builder_element($builder, $element); ?>
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
function ncllc_pro_render_site_footer() {
    ob_start();
    ?>
    <footer class="site-footer footer-layout-builder">
        <div class="container">
            <div class="footer-builder-container">
                <?php ncllc_pro_render_builder_layout('footer'); ?>
            </div>
        </div>
    </footer>
    <?php
    return ob_get_clean();
}

/**
 * Render a lightweight Astra-style builder map in the Customizer preview.
 */
function ncllc_pro_builder_element_choices() {
    return array(
        'none'        => __('Empty', 'ncllc-pro'),
        'site-logo'   => __('Site Title & Logo', 'ncllc-pro'),
        'primary-menu'=> __('Primary Menu', 'ncllc-pro'),
        'footer-menu' => __('Footer Menu', 'ncllc-pro'),
        'search'      => __('Search', 'ncllc-pro'),
        'button'      => __('Button 1', 'ncllc-pro'),
        'button-1'    => __('Button 1', 'ncllc-pro'),
        'button-2'    => __('Button 2', 'ncllc-pro'),
        'copyright'   => __('Copyright', 'ncllc-pro'),
        'divider-1'   => __('Divider 1', 'ncllc-pro'),
        'divider-2'   => __('Divider 2', 'ncllc-pro'),
        'divider-3'   => __('Divider 3', 'ncllc-pro'),
        'html-1'      => __('HTML 1', 'ncllc-pro'),
        'html-2'      => __('HTML 2', 'ncllc-pro'),
        'social'      => __('Social', 'ncllc-pro'),
        'widget-1'    => __('Widget 1', 'ncllc-pro'),
        'widget-2'    => __('Widget 2', 'ncllc-pro'),
        'widget-3'    => __('Widget 3', 'ncllc-pro'),
        'widget-4'    => __('Widget 4', 'ncllc-pro'),
    );
}

function ncllc_pro_builder_default($builder, $row, $cell) {
    $defaults = array(
        'header' => array(
            1 => array(1 => 'site-logo', 2 => 'widget-1', 3 => 'primary-menu', 4 => 'none'),
            2 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
            3 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
        ),
        'footer' => array(
            1 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
            2 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
            3 => array(1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'),
        ),
    );

    return isset($defaults[$builder][$row][$cell]) ? $defaults[$builder][$row][$cell] : 'none';
}

function ncllc_pro_builder_row_count_setting_id($builder) {
    return 'ajn_' . $builder . '_builder_row_count';
}

function ncllc_pro_builder_row_columns_setting_id($builder, $row) {
    return 'ajn_' . $builder . '_builder_row_' . $row . '_columns';
}

function ncllc_pro_builder_row_count_default($builder) {
    return 1;
}

function ncllc_pro_builder_row_columns_default($builder, $row) {
    if ('header' === $builder && 1 === (int) $row) {
        return 3;
    }

    return 1;
}

function ncllc_pro_builder_setting_id($builder, $row, $cell) {
    return 'ajn_' . $builder . '_builder_' . $row . '_' . $cell;
}

function ncllc_pro_builder_width_setting_id($builder, $row, $cell, $device) {
    return 'ajn_' . $builder . '_builder_' . $row . '_' . $cell . '_width_' . $device;
}

function ncllc_pro_builder_width_default($builder, $row, $cell) {
    if ('header' === $builder && 1 === (int) $row && 3 === (int) $cell) {
        return 4;
    }

    if ('footer' === $builder && 3 === (int) $row && 2 === (int) $cell) {
        return 4;
    }

    return 2;
}

function ncllc_pro_get_builder_value($builder, $row, $cell) {
    return get_theme_mod(ncllc_pro_builder_setting_id($builder, $row, $cell), ncllc_pro_builder_default($builder, $row, $cell));
}

function ncllc_pro_get_builder_row_count($builder) {
    return ncllc_pro_sanitize_builder_row_count(get_theme_mod(ncllc_pro_builder_row_count_setting_id($builder), ncllc_pro_builder_row_count_default($builder)));
}

function ncllc_pro_get_builder_row_columns($builder, $row) {
    return ncllc_pro_sanitize_builder_count(get_theme_mod(ncllc_pro_builder_row_columns_setting_id($builder, $row), ncllc_pro_builder_row_columns_default($builder, $row)));
}

function ncllc_pro_get_builder_width($builder, $row, $cell, $device) {
    return absint(get_theme_mod(ncllc_pro_builder_width_setting_id($builder, $row, $cell, $device), ncllc_pro_builder_width_default($builder, $row, $cell)));
}

function ncllc_pro_builder_focus_control($builder, $element, $fallback_setting_id) {
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

function ncllc_pro_builder_contains_element($builder, $elements) {
    $elements = (array) $elements;

    for ($row = 1; $row <= 6; $row++) {
        for ($cell = 1; $cell <= 4; $cell++) {
            if (in_array(ncllc_pro_get_builder_value($builder, $row, $cell), $elements, true)) {
                return true;
            }
        }
    }

    return false;
}

function ncllc_pro_footer_builder_button_1_active() {
    return ncllc_pro_builder_contains_element('footer', array('button', 'button-1'));
}

function ncllc_pro_footer_builder_button_2_active() {
    return ncllc_pro_builder_contains_element('footer', 'button-2');
}

function ncllc_pro_footer_builder_html_2_active() {
    return ncllc_pro_builder_contains_element('footer', 'html-2');
}

function ncllc_pro_footer_builder_social_active() {
    return ncllc_pro_builder_contains_element('footer', 'social');
}

function ncllc_pro_footer_builder_copyright_active() {
    return ncllc_pro_builder_contains_element('footer', 'copyright');
}

function ncllc_pro_builder_insert_choices($builder = '') {
    $choices = ncllc_pro_builder_element_choices();
    unset($choices['none'], $choices['button']);

    if ('footer' === $builder) {
        unset($choices['site-logo'], $choices['primary-menu'], $choices['search']);
    }

    return $choices;
}

function ncllc_pro_render_customizer_builder_chip($label, $setting_id, $focus_control_id) {
    ?>
    <button type="button" class="ajn-customizer-builder-chip" data-ajn-focus-control="<?php echo esc_attr($focus_control_id); ?>">
        <?php echo esc_html($label); ?>
        <span aria-hidden="true" class="ajn-customizer-builder-remove" data-ajn-clear-control="<?php echo esc_attr($setting_id); ?>">&times;</span>
    </button>
    <?php
}

function ncllc_pro_render_customizer_builder_row($builder, $row) {
    $choices = ncllc_pro_builder_element_choices();
    $column_count = ncllc_pro_get_builder_row_columns($builder, $row);
    $columns_setting_id = ncllc_pro_builder_row_columns_setting_id($builder, $row);
    ?>
    <div class="ajn-customizer-builder-row">
        <div class="ajn-customizer-builder-row-handle">
            <span aria-hidden="true" class="ajn-customizer-builder-gear">⚙</span>
            <span class="ajn-customizer-builder-split" aria-label="<?php esc_attr_e('Split row into columns', 'ncllc-pro'); ?>">
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
            $setting_id = ncllc_pro_builder_setting_id($builder, $row, $cell);
            $value = ncllc_pro_get_builder_value($builder, $row, $cell);
            $label = isset($choices[$value]) ? $choices[$value] : $choices['none'];
            $width_desktop = ncllc_pro_get_builder_width($builder, $row, $cell, 'desktop');
            $width_tablet = ncllc_pro_get_builder_width($builder, $row, $cell, 'tablet');
            $width_mobile = ncllc_pro_get_builder_width($builder, $row, $cell, 'mobile');
            $focus_control_id = ncllc_pro_builder_focus_control($builder, $value, $setting_id);
            ?>
            <div
                class="ajn-customizer-builder-cell"
                style="--ajn-builder-width-desktop: <?php echo esc_attr($width_desktop); ?>; --ajn-builder-width-tablet: <?php echo esc_attr($width_tablet); ?>; --ajn-builder-width-mobile: <?php echo esc_attr($width_mobile); ?>;"
            >
                <?php if ('none' !== $value) : ?>
                    <?php ncllc_pro_render_customizer_builder_chip($label, $setting_id, $focus_control_id); ?>
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

function ncllc_pro_render_customizer_builder_add_row($row_count_setting_id, $next_count) {
    if ($next_count > 6) {
        return;
    }
    ?>
    <button
        type="button"
        class="ajn-customizer-builder-add-row"
        data-ajn-set-control="<?php echo esc_attr($row_count_setting_id); ?>"
        data-ajn-set-value="<?php echo esc_attr($next_count); ?>"
        aria-label="<?php esc_attr_e('Add row', 'ncllc-pro'); ?>"
    >+</button>
    <?php
}

function ncllc_pro_render_customizer_builder_remove_row($row_count_setting_id, $previous_count) {
    if ($previous_count < 1) {
        return;
    }
    ?>
    <button
        type="button"
        class="ajn-customizer-builder-remove-row"
        data-ajn-set-control="<?php echo esc_attr($row_count_setting_id); ?>"
        data-ajn-set-value="<?php echo esc_attr($previous_count); ?>"
        aria-label="<?php esc_attr_e('Remove last row', 'ncllc-pro'); ?>"
    >&minus;</button>
    <?php
}

function ncllc_pro_render_header_builder_preview() {
    if (!is_customize_preview()) {
        return;
    }
    $row_count = ncllc_pro_get_builder_row_count('header');
    $row_count_setting_id = ncllc_pro_builder_row_count_setting_id('header');
    ?>
    <div class="ajn-customizer-builder-preview ajn-customizer-header-builder" aria-label="<?php esc_attr_e('Header Builder Preview', 'ncllc-pro'); ?>">
        <span class="ajn-customizer-builder-tooltip"><?php esc_html_e('Header Builder Preview', 'ncllc-pro'); ?></span>
        <?php
        for ($row = 1; $row <= $row_count; $row++) {
            ncllc_pro_render_customizer_builder_row('header', $row);
            if ($row === $row_count && $row_count > 1) {
                ncllc_pro_render_customizer_builder_remove_row($row_count_setting_id, $row_count - 1);
            }
            if ($row === $row_count) {
                ncllc_pro_render_customizer_builder_add_row($row_count_setting_id, $row_count + 1);
            }
        }
        ?>
    </div>
    <?php
}

function ncllc_pro_render_footer_builder_preview() {
    if (!is_customize_preview()) {
        return;
    }
    $row_count = ncllc_pro_get_builder_row_count('footer');
    $row_count_setting_id = ncllc_pro_builder_row_count_setting_id('footer');
    ?>
    <div class="ajn-customizer-builder-preview ajn-customizer-footer-builder" aria-label="<?php esc_attr_e('Footer Builder Preview', 'ncllc-pro'); ?>">
        <span class="ajn-customizer-builder-tooltip"><?php esc_html_e('Footer Builder Preview', 'ncllc-pro'); ?></span>
        <?php
        for ($row = 1; $row <= $row_count; $row++) {
            ncllc_pro_render_customizer_builder_row('footer', $row);
            if ($row === $row_count && $row_count > 1) {
                ncllc_pro_render_customizer_builder_remove_row($row_count_setting_id, $row_count - 1);
            }
            if ($row === $row_count) {
                ncllc_pro_render_customizer_builder_add_row($row_count_setting_id, $row_count + 1);
            }
        }
        ?>
    </div>
    <?php
}

function ncllc_pro_register_builder_controls($wp_customize, $builder, $section, $label_prefix, $width_section = '') {
    $choices = ncllc_pro_builder_element_choices();
    $width_section = $width_section ? $width_section : $section;
    $device_labels = array(
        'desktop' => __('Desktop', 'ncllc-pro'),
        'tablet'  => __('Tablet', 'ncllc-pro'),
        'mobile'  => __('Mobile', 'ncllc-pro'),
    );
    $row_count_setting_id = ncllc_pro_builder_row_count_setting_id($builder);

    $wp_customize->add_setting($row_count_setting_id, array(
        'default'           => ncllc_pro_builder_row_count_default($builder),
        'sanitize_callback' => 'ncllc_pro_sanitize_builder_row_count',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control($row_count_setting_id, array(
        'label'           => sprintf(__('%s Row Count', 'ncllc-pro'), $label_prefix),
        'section'         => $section,
        'type'            => 'number',
        'active_callback' => '__return_false',
    ));

    for ($row = 1; $row <= 6; $row++) {
        $row_columns_setting_id = ncllc_pro_builder_row_columns_setting_id($builder, $row);

        $wp_customize->add_setting($row_columns_setting_id, array(
            'default'           => ncllc_pro_builder_row_columns_default($builder, $row),
            'sanitize_callback' => 'ncllc_pro_sanitize_builder_count',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($row_columns_setting_id, array(
            'label'           => sprintf(
                /* translators: 1: builder label, 2: row number. */
                __('%1$s Row %2$d Columns', 'ncllc-pro'),
                $label_prefix,
                $row
            ),
            'section'         => $section,
            'type'            => 'number',
            'active_callback' => '__return_false',
        ));

        for ($cell = 1; $cell <= 4; $cell++) {
            $setting_id = ncllc_pro_builder_setting_id($builder, $row, $cell);

            $wp_customize->add_setting($setting_id, array(
                'default'           => ncllc_pro_builder_default($builder, $row, $cell),
                'sanitize_callback' => 'ncllc_pro_sanitize_choice',
                'transport'         => 'postMessage',
            ));

            $wp_customize->add_control($setting_id, array(
                'label'       => sprintf(
                    /* translators: 1: builder label, 2: row number, 3: cell number. */
                    __('%1$s Row %2$d Cell %3$d Element', 'ncllc-pro'),
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
                $width_setting_id = ncllc_pro_builder_width_setting_id($builder, $row, $cell, $device);

                $wp_customize->add_setting($width_setting_id, array(
                    'default'           => ncllc_pro_builder_width_default($builder, $row, $cell),
                    'sanitize_callback' => 'ncllc_pro_sanitize_builder_width',
                    'transport'         => 'refresh',
                ));

                $wp_customize->add_control($width_setting_id, array(
                    'label'       => sprintf(
                        /* translators: 1: device label, 2: row number, 3: cell number. */
                        __('%1$s Width - Row %2$d Cell %3$d', 'ncllc-pro'),
                        $device_label,
                        $row,
                        $cell
                    ),
                    'description' => __('Relative width from 1 to 6. Larger numbers take more horizontal space.', 'ncllc-pro'),
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
    }
}

/**
 * Fetch Google Business Profile reviews through the Google Places API.
 *
 * Add these constants in wp-config.php to enable live reviews:
 * define('NCLLC_GOOGLE_PLACES_API_KEY', 'your-api-key');
 * define('NCLLC_GOOGLE_PLACE_ID', 'your-place-id');
 */
function ncllc_pro_get_google_reviews() {
    $api_key = defined('NCLLC_GOOGLE_PLACES_API_KEY') ? NCLLC_GOOGLE_PLACES_API_KEY : '';
    $place_id = defined('NCLLC_GOOGLE_PLACE_ID') ? NCLLC_GOOGLE_PLACE_ID : '';
    $profile_url = 'https://www.google.com/search?q=NC+LLC+Agents+Inc+Charlotte+NC+reviews#mpd=~9888847900513167101/customers/reviews';
    $write_review_url = 'https://g.page/r/Cej2Nr9egmkYEAE/review';

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

    $cache_key = 'ncllc_google_reviews_' . md5($place_id);
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
function ncllc_pro_google_reviews_shortcode() {
    $data = ncllc_pro_get_google_reviews();
    $profile_url = !empty($data['url']) ? $data['url'] : 'https://www.google.com/search?q=NC+LLC+Agents+Inc+Charlotte+NC+reviews#mpd=~9888847900513167101/customers/reviews';
    $write_review_url = !empty($data['write_review_url']) ? $data['write_review_url'] : 'https://g.page/r/Cej2Nr9egmkYEAE/review';

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
                <h3>Read our Google reviews</h3>
                <p>See what clients are saying on Google, or share your experience with NC LLC Agents Inc.</p>
            </div>
        <?php endif; ?>

        <div class="google-review-actions">
            <a class="button" href="<?php echo esc_url($profile_url); ?>" target="_blank" rel="noreferrer noopener">Read Google Reviews</a>
            <a class="button button-secondary" href="<?php echo esc_url($write_review_url); ?>" target="_blank" rel="noreferrer noopener">Write a Review</a>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('ncllc_google_reviews', 'ncllc_pro_google_reviews_shortcode');

/**
 * Performance optimizations
 */
function ncllc_pro_optimize() {
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
add_action('init', 'ncllc_pro_optimize');

/**
 * Disable XML-RPC
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Remove query strings from static resources
 */
function ncllc_pro_remove_query_strings($src, $handle = '') {
    if (in_array($handle, array('ncllc-pro-style', 'ncllc-pro-script'), true)) {
        return $src;
    }

    if (strpos($src, '?ver=')) {
        $src = remove_query_arg('ver', $src);
    }

    return $src;
}
add_filter('style_loader_src', 'ncllc_pro_remove_query_strings', 10, 2);
add_filter('script_loader_src', 'ncllc_pro_remove_query_strings', 10, 2);

/**
 * Add async/defer to scripts
 */
function ncllc_pro_add_async_defer($tag, $handle) {
    $async_scripts = array('ncllc-pro-script');
    
    if (in_array($handle, $async_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'ncllc_pro_add_async_defer', 10, 2);

/**
 * Security headers
 */
function ncllc_pro_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
add_action('send_headers', 'ncllc_pro_security_headers');

/**
 * Custom image sizes
 */
add_image_size('ncllc-featured', 1200, 600, true);
add_image_size('ncllc-thumbnail', 400, 300, true);
add_image_size('ncllc-square', 600, 600, true);

/**
 * Enable SVG uploads
 */
function ncllc_pro_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'ncllc_pro_mime_types');

/**
 * Sanitize SVG uploads
 */
function ncllc_pro_sanitize_svg($file) {
    if ($file['type'] === 'image/svg+xml') {
        $file['ext'] = 'svg';
        $file['type'] = 'image/svg+xml';
    }
    return $file;
}
add_filter('wp_check_filetype_and_ext', 'ncllc_pro_sanitize_svg', 10, 4);

/**
 * Customizer settings
 */
function ncllc_pro_customize_register($wp_customize) {
    $theme_color_controls = array(
        'theme_primary_color'      => array('label' => __('Primary Color', 'ncllc-pro'), 'default' => '#2563eb'),
        'theme_primary_dark_color' => array('label' => __('Primary Hover Color', 'ncllc-pro'), 'default' => '#1e40af'),
        'theme_secondary_color'    => array('label' => __('Secondary Color', 'ncllc-pro'), 'default' => '#7c3aed'),
        'theme_accent_color'       => array('label' => __('Accent Color', 'ncllc-pro'), 'default' => '#f59e0b'),
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
    $wp_customize->add_section('ncllc_header', array(
        'title'    => __('Header', 'ncllc-pro'),
        'priority' => 25,
    ));

    $wp_customize->add_section('ncllc_header_builder_widths', array(
        'title'       => __('Header Builder Widths', 'ncllc-pro'),
        'priority'    => 25,
        'description' => __('Advanced responsive width controls for Header Builder slots.', 'ncllc-pro'),
    ));

    $wp_customize->add_setting('header_background_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'ncllc_pro_sanitize_css_background',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('header_background_color', array(
        'label'       => __('Header Background', 'ncllc-pro'),
        'description' => __('Use a color or gradient. Example: linear-gradient(90deg, #ffffff, #eef6ff).', 'ncllc-pro'),
        'section'     => 'ncllc_header',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('header_text_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control(
            $wp_customize,
            'header_text_color',
            array(
                'label'       => __('Header Text Color', 'ncllc-pro'),
                'description' => __('Set the header logo text and top-level menu link color.', 'ncllc-pro'),
                'section'     => 'ncllc_header',
            )
        ));
    }

    $header_color_controls = array(
        'header_link_hover_color'        => array('label' => __('Header Link Hover Color', 'ncllc-pro'), 'default' => '#2563eb'),
        'header_link_hover_background'   => array('label' => __('Header Link Hover Background', 'ncllc-pro'), 'default' => '#f9fafb'),
        'header_submenu_background'      => array('label' => __('Header Submenu Background', 'ncllc-pro'), 'default' => '#ffffff'),
        'header_submenu_text_color'      => array('label' => __('Header Submenu Text Color', 'ncllc-pro'), 'default' => '#1f2937'),
        'header_submenu_hover_color'     => array('label' => __('Header Submenu Hover Text Color', 'ncllc-pro'), 'default' => '#2563eb'),
        'header_submenu_hover_background'=> array('label' => __('Header Submenu Hover Background', 'ncllc-pro'), 'default' => '#f9fafb'),
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
                    'section' => 'ncllc_header',
                )
            ));
        }
    }

    $header_typography_controls = array(
        'header_font_family' => array(
            'label'    => __('Header Font Family', 'ncllc-pro'),
            'default'  => 'inherit',
            'type'     => 'select',
            'sanitize' => 'ncllc_pro_sanitize_font_family',
            'choices'  => array(
                'inherit'   => __('Theme Default', 'ncllc-pro'),
                'Inter'     => __('Inter', 'ncllc-pro'),
                'Poppins'   => __('Poppins', 'ncllc-pro'),
                'Arial'     => __('Arial', 'ncllc-pro'),
                'Georgia'   => __('Georgia', 'ncllc-pro'),
                'system-ui' => __('System UI', 'ncllc-pro'),
            ),
        ),
        'header_font_size' => array(
            'label'    => __('Header Text Size', 'ncllc-pro'),
            'default'  => '1rem',
            'type'     => 'text',
            'sanitize' => 'ncllc_pro_sanitize_css_size',
        ),
        'header_font_weight' => array(
            'label'    => __('Header Font Weight', 'ncllc-pro'),
            'default'  => '500',
            'type'     => 'select',
            'sanitize' => 'ncllc_pro_sanitize_font_weight',
            'choices'  => array('400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800'),
        ),
        'header_menu_gap' => array(
            'label'    => __('Header Menu Gap', 'ncllc-pro'),
            'default'  => '2rem',
            'type'     => 'text',
            'sanitize' => 'ncllc_pro_sanitize_css_size',
        ),
        'header_container_width' => array(
            'label'    => __('Header Container Width', 'ncllc-pro'),
            'default'  => '1400px',
            'type'     => 'text',
            'sanitize' => 'ncllc_pro_sanitize_css_size',
        ),
        'header_shadow_opacity' => array(
            'label'    => __('Header Shadow Opacity', 'ncllc-pro'),
            'default'  => '0.10',
            'type'     => 'number',
            'sanitize' => 'ncllc_pro_sanitize_opacity',
        ),
    );

    foreach ($header_typography_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => $control['sanitize'],
            'transport'         => 'refresh',
        ));

        $args = array(
            'label'       => $control['label'],
            'section'     => 'ncllc_header',
            'type'        => $control['type'],
            'description' => 'text' === $control['type'] ? __('Examples: 1rem, 16px, 2rem.', 'ncllc-pro') : '',
        );

        if (!empty($control['choices'])) {
            $args['choices'] = $control['choices'];
        }

        if ('number' === $control['type']) {
            $args['input_attrs'] = array('min' => 0, 'max' => 1, 'step' => 0.05);
        }

        $wp_customize->add_control($setting_id, $args);
    }

    $wp_customize->add_setting('header_sticky', array(
        'default'           => true,
        'sanitize_callback' => 'ncllc_pro_sanitize_checkbox',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('header_sticky', array(
        'label'   => __('Sticky Header', 'ncllc-pro'),
        'section' => 'ncllc_header',
        'type'    => 'checkbox',
    ));

    $wp_customize->add_setting('header_layout', array(
        'default'           => 'logo-left-menu-right',
        'sanitize_callback' => 'ncllc_pro_sanitize_choice',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('header_layout', array(
        'label'       => __('Header Layout', 'ncllc-pro'),
        'description' => __('Choose a simple header arrangement. Existing sites keep the current logo-left/menu-right layout.', 'ncllc-pro'),
        'section'     => 'ncllc_header',
        'type'        => 'select',
        'choices'     => array(
            'logo-left-menu-right' => __('Logo Left, Menu Right', 'ncllc-pro'),
            'centered-menu'        => __('Centered Menu Bar', 'ncllc-pro'),
            'stacked-center'       => __('Centered Logo, Menu Below', 'ncllc-pro'),
            'builder'              => __('Builder', 'ncllc-pro'),
        ),
    ));

    $old_logo_height = get_theme_mod('logo_height', '50');
    $old_header_padding = get_theme_mod('header_padding', '0.75');
    $device_labels = array(
        'desktop' => __('Desktop', 'ncllc-pro'),
        'tablet'  => __('Tablet', 'ncllc-pro'),
        'mobile'  => __('Mobile', 'ncllc-pro'),
    );

    foreach ($device_labels as $device => $label) {
        $logo_setting = 'logo_height_' . $device;

        $wp_customize->add_setting($logo_setting, array(
            'default'           => $old_logo_height,
            'sanitize_callback' => 'ncllc_pro_sanitize_logo_height',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control($logo_setting, array(
            'label'       => sprintf(__('Logo Height - %s (px)', 'ncllc-pro'), $label),
            'description' => __('Adjust the height of your logo for this device size (20-100px).', 'ncllc-pro'),
            'section'     => 'ncllc_header',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 20,
                'max'  => 100,
                'step' => 5,
            ),
        ));
    }

    foreach ($device_labels as $device => $label) {
        $padding_setting = 'header_padding_' . $device;

        $wp_customize->add_setting($padding_setting, array(
            'default'           => $old_header_padding,
            'sanitize_callback' => 'ncllc_pro_sanitize_header_padding',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control($padding_setting, array(
            'label'       => sprintf(__('Header Padding - %s (rem)', 'ncllc-pro'), $label),
            'description' => __('Adjust header top/bottom padding for this device size (0.5-2rem).', 'ncllc-pro'),
            'section'     => 'ncllc_header',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 0.5,
                'max'  => 2,
                'step' => 0.25,
            ),
        ));
    }

    ncllc_pro_register_builder_controls($wp_customize, 'header', 'ncllc_header', __('Header', 'ncllc-pro'), 'ncllc_header_builder_widths');

    // Hero Defaults Section
    $wp_customize->add_section('ncllc_hero_defaults', array(
        'title'       => __('Hero Defaults', 'ncllc-pro'),
        'priority'    => 26,
        'description' => __('Default hero design for editable page/post hero blocks.', 'ncllc-pro'),
    ));

    $hero_color_controls = array(
        'hero_bg_1' => array('label' => __('Hero Background Color 1', 'ncllc-pro'), 'default' => '#2563eb'),
        'hero_bg_2' => array('label' => __('Hero Background Color 2', 'ncllc-pro'), 'default' => '#7c3aed'),
        'hero_heading_color' => array('label' => __('Hero Heading Color', 'ncllc-pro'), 'default' => '#ffffff'),
        'hero_subtitle_color' => array('label' => __('Hero Subtitle Color', 'ncllc-pro'), 'default' => 'rgba(255,255,255,0.94)'),
        'hero_badge_bg' => array('label' => __('Hero Badge Background', 'ncllc-pro'), 'default' => 'rgba(255,255,255,0.16)'),
        'hero_badge_text_color' => array('label' => __('Hero Badge Text Color', 'ncllc-pro'), 'default' => '#ffffff'),
        'hero_button_bg' => array('label' => __('Hero Primary Button Background', 'ncllc-pro'), 'default' => '#ffffff'),
        'hero_button_text_color' => array('label' => __('Hero Primary Button Text Color', 'ncllc-pro'), 'default' => '#2563eb'),
    );

    foreach ($hero_color_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'ncllc_pro_sanitize_css_color',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($setting_id, array(
            'label'       => $control['label'],
            'section'     => 'ncllc_hero_defaults',
            'type'        => 'text',
            'description' => __('Use #2563eb or rgba(255,255,255,0.94).', 'ncllc-pro'),
        ));
    }

    $hero_size_controls = array(
        'hero_min_height_desktop' => array('label' => __('Hero Minimum Height - Desktop', 'ncllc-pro'), 'default' => '50px'),
        'hero_min_height_tablet' => array('label' => __('Hero Minimum Height - Tablet', 'ncllc-pro'), 'default' => '50px'),
        'hero_min_height_mobile' => array('label' => __('Hero Minimum Height - Mobile', 'ncllc-pro'), 'default' => '50px'),
        'hero_padding_top_desktop' => array('label' => __('Hero Padding Top - Desktop', 'ncllc-pro'), 'default' => '1rem'),
        'hero_padding_bottom_desktop' => array('label' => __('Hero Padding Bottom - Desktop', 'ncllc-pro'), 'default' => '1rem'),
        'hero_padding_top_tablet' => array('label' => __('Hero Padding Top - Tablet', 'ncllc-pro'), 'default' => '1rem'),
        'hero_padding_bottom_tablet' => array('label' => __('Hero Padding Bottom - Tablet', 'ncllc-pro'), 'default' => '1rem'),
        'hero_padding_top_mobile' => array('label' => __('Hero Padding Top - Mobile', 'ncllc-pro'), 'default' => '1rem'),
        'hero_padding_bottom_mobile' => array('label' => __('Hero Padding Bottom - Mobile', 'ncllc-pro'), 'default' => '1rem'),
    );

    foreach ($hero_size_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'ncllc_pro_sanitize_css_size',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($setting_id, array(
            'label'       => $control['label'],
            'section'     => 'ncllc_hero_defaults',
            'type'        => 'text',
            'description' => __('Examples: 50px, 1rem, 60vh. Plain numbers save as px.', 'ncllc-pro'),
        ));
    }

    // Footer Section
    $wp_customize->add_section('ncllc_footer', array(
        'title'       => __('Footer', 'ncllc-pro'),
        'priority'    => 26,
        'description' => __('Use the footer builder preview to add, remove, and arrange footer elements.', 'ncllc-pro'),
    ));

    $wp_customize->add_section('ncllc_footer_builder_widths', array(
        'title'       => __('Footer Builder Widths', 'ncllc-pro'),
        'priority'    => 27,
        'description' => __('Advanced responsive width controls for Footer Builder slots.', 'ncllc-pro'),
    ));

    $wp_customize->add_setting('footer_background_color', array(
        'default'           => '#111827',
        'sanitize_callback' => 'ncllc_pro_sanitize_css_background',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('footer_background_color', array(
        'label'       => __('Footer Background', 'ncllc-pro'),
        'description' => __('Use a color or gradient. Example: linear-gradient(90deg, #111827, #1f2937).', 'ncllc-pro'),
        'section'     => 'ncllc_footer',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('footer_text_color', array(
        'default'           => '#f9fafb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control(
            $wp_customize,
            'footer_text_color',
            array(
                'label'       => __('Footer Text Color', 'ncllc-pro'),
                'description' => __('Set the footer text and menu link color.', 'ncllc-pro'),
                'section'     => 'ncllc_footer',
            )
        ));
    }

    $footer_color_controls = array(
        'footer_link_hover_color'         => array('label' => __('Footer Link Hover Color', 'ncllc-pro'), 'default' => '#f59e0b'),
        'footer_divider_color'            => array('label' => __('Footer Divider Color', 'ncllc-pro'), 'default' => '#374151'),
        'footer_submenu_background'       => array('label' => __('Footer Submenu Background', 'ncllc-pro'), 'default' => '#ffffff'),
        'footer_submenu_text_color'       => array('label' => __('Footer Submenu Text Color', 'ncllc-pro'), 'default' => '#1f2937'),
        'footer_submenu_hover_color'      => array('label' => __('Footer Submenu Hover Text Color', 'ncllc-pro'), 'default' => '#2563eb'),
        'footer_submenu_hover_background' => array('label' => __('Footer Submenu Hover Background', 'ncllc-pro'), 'default' => '#f9fafb'),
    );

    foreach ($footer_color_controls as $setting_id => $control) {
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
                    'section' => 'ncllc_footer',
                )
            ));
        }
    }

    $footer_typography_controls = array(
        'footer_font_family' => array(
            'label'    => __('Footer Font Family', 'ncllc-pro'),
            'default'  => 'inherit',
            'type'     => 'select',
            'sanitize' => 'ncllc_pro_sanitize_font_family',
            'choices'  => array(
                'inherit'   => __('Theme Default', 'ncllc-pro'),
                'Inter'     => __('Inter', 'ncllc-pro'),
                'Poppins'   => __('Poppins', 'ncllc-pro'),
                'Arial'     => __('Arial', 'ncllc-pro'),
                'Georgia'   => __('Georgia', 'ncllc-pro'),
                'system-ui' => __('System UI', 'ncllc-pro'),
            ),
        ),
        'footer_font_size' => array(
            'label'    => __('Footer Text Size', 'ncllc-pro'),
            'default'  => '1rem',
            'type'     => 'text',
            'sanitize' => 'ncllc_pro_sanitize_css_size',
        ),
        'footer_font_weight' => array(
            'label'    => __('Footer Font Weight', 'ncllc-pro'),
            'default'  => '400',
            'type'     => 'select',
            'sanitize' => 'ncllc_pro_sanitize_font_weight',
            'choices'  => array('400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800'),
        ),
        'footer_menu_gap' => array(
            'label'    => __('Footer Menu Gap', 'ncllc-pro'),
            'default'  => '1.4rem',
            'type'     => 'text',
            'sanitize' => 'ncllc_pro_sanitize_css_size',
        ),
        'footer_container_width' => array(
            'label'    => __('Footer Container Width', 'ncllc-pro'),
            'default'  => '1280px',
            'type'     => 'text',
            'sanitize' => 'ncllc_pro_sanitize_css_size',
        ),
        'footer_padding_top' => array(
            'label'    => __('Footer Padding Top', 'ncllc-pro'),
            'default'  => '4rem',
            'type'     => 'text',
            'sanitize' => 'ncllc_pro_sanitize_css_size',
        ),
        'footer_padding_bottom' => array(
            'label'    => __('Footer Padding Bottom', 'ncllc-pro'),
            'default'  => '2rem',
            'type'     => 'text',
            'sanitize' => 'ncllc_pro_sanitize_css_size',
        ),
    );

    foreach ($footer_typography_controls as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control['default'],
            'sanitize_callback' => $control['sanitize'],
            'transport'         => 'refresh',
        ));

        $args = array(
            'label'       => $control['label'],
            'section'     => 'ncllc_footer',
            'type'        => $control['type'],
            'description' => 'text' === $control['type'] ? __('Examples: 1rem, 16px, 2rem.', 'ncllc-pro') : '',
        );

        if (!empty($control['choices'])) {
            $args['choices'] = $control['choices'];
        }

        $wp_customize->add_control($setting_id, $args);
    }

    $wp_customize->add_setting('ajn_builder_button_text', array(
        'default'           => __('Contact Us', 'ncllc-pro'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_text', array(
        'label'   => __('Builder Button Text', 'ncllc-pro'),
        'section' => 'ncllc_header',
        'type'    => 'text',
    ));

    $wp_customize->add_setting('ajn_builder_button_url', array(
        'default'           => home_url('/contact/'),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_url', array(
        'label'   => __('Builder Button URL', 'ncllc-pro'),
        'section' => 'ncllc_header',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('ajn_footer_builder_button_text', array(
        'default'           => __('Contact Us', 'ncllc-pro'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_footer_builder_button_text', array(
        'label'           => __('Button 1 Text', 'ncllc-pro'),
        'section'         => 'ncllc_footer',
        'type'            => 'text',
        'active_callback' => 'ncllc_pro_footer_builder_button_1_active',
    ));

    $wp_customize->add_setting('ajn_footer_builder_button_url', array(
        'default'           => home_url('/contact/'),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_footer_builder_button_url', array(
        'label'           => __('Button 1 URL', 'ncllc-pro'),
        'section'         => 'ncllc_footer',
        'type'            => 'url',
        'active_callback' => 'ncllc_pro_footer_builder_button_1_active',
    ));

    $wp_customize->add_setting('ajn_builder_button_2_text', array(
        'default'           => __('Learn More', 'ncllc-pro'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_2_text', array(
        'label'           => __('Button 2 Text', 'ncllc-pro'),
        'section'         => 'ncllc_footer',
        'type'            => 'text',
        'active_callback' => 'ncllc_pro_footer_builder_button_2_active',
    ));

    $wp_customize->add_setting('ajn_builder_button_2_url', array(
        'default'           => home_url('/contact/'),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_button_2_url', array(
        'label'           => __('Button 2 URL', 'ncllc-pro'),
        'section'         => 'ncllc_footer',
        'type'            => 'url',
        'active_callback' => 'ncllc_pro_footer_builder_button_2_active',
    ));

    $wp_customize->add_setting('ajn_builder_html_1', array(
        'default'           => get_bloginfo('description'),
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_html_1', array(
        'label'   => __('Builder HTML 1', 'ncllc-pro'),
        'section' => 'ncllc_header',
        'type'    => 'textarea',
    ));

    $wp_customize->add_setting('ajn_builder_html_2', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_html_2', array(
        'label'           => __('HTML 2', 'ncllc-pro'),
        'section'         => 'ncllc_footer',
        'type'            => 'textarea',
        'active_callback' => 'ncllc_pro_footer_builder_html_2_active',
    ));

    $wp_customize->add_setting('ajn_builder_social_1_label', array(
        'default'           => __('Social', 'ncllc-pro'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_social_1_label', array(
        'label'           => __('Social Label', 'ncllc-pro'),
        'section'         => 'ncllc_footer',
        'type'            => 'text',
        'active_callback' => 'ncllc_pro_footer_builder_social_active',
    ));

    $wp_customize->add_setting('ajn_builder_social_1_url', array(
        'default'           => '#',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('ajn_builder_social_1_url', array(
        'label'           => __('Social URL', 'ncllc-pro'),
        'section'         => 'ncllc_footer',
        'type'            => 'url',
        'active_callback' => 'ncllc_pro_footer_builder_social_active',
    ));

    ncllc_pro_register_builder_controls($wp_customize, 'footer', 'ncllc_footer', __('Footer', 'ncllc-pro'), 'ncllc_footer_builder_widths');

    $footer_columns = ncllc_pro_get_footer_columns();
    foreach ($footer_columns as $index => $column) {
        $wp_customize->add_setting('footer_column_' . $index . '_title', array(
            'default'           => $column['title'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control('footer_column_' . $index . '_title', array(
            'label'   => sprintf(__('Footer Column %d Title', 'ncllc-pro'), $index),
            'section' => 'ncllc_footer',
            'type'    => 'text',
            'active_callback' => '__return_false',
        ));

        $wp_customize->add_setting('footer_column_' . $index . '_text', array(
            'default'           => $column['text'],
            'sanitize_callback' => 'ncllc_pro_sanitize_textarea',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control('footer_column_' . $index . '_text', array(
            'label'       => sprintf(__('Footer Column %d Text', 'ncllc-pro'), $index),
            'description' => __('One item per line. Use Label|URL for links.', 'ncllc-pro'),
            'section'     => 'ncllc_footer',
            'type'        => 'textarea',
            'active_callback' => '__return_false',
        ));
    }

    $wp_customize->add_setting('footer_bottom_text', array(
        'default'           => ncllc_pro_get_footer_bottom_default(),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('footer_bottom_text', array(
        'label'           => __('Copyright Text', 'ncllc-pro'),
        'section'         => 'ncllc_footer',
        'type'            => 'text',
        'active_callback' => 'ncllc_pro_footer_builder_copyright_active',
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
            ncllc_pro_builder_row_count_setting_id('footer'),
        );
        for ($i = 1; $i <= 4; $i++) {
            $footer_settings[] = 'footer_column_' . $i . '_title';
            $footer_settings[] = 'footer_column_' . $i . '_text';
        }
        for ($row = 1; $row <= 6; $row++) {
            $footer_settings[] = ncllc_pro_builder_row_columns_setting_id('footer', $row);
            for ($cell = 1; $cell <= 4; $cell++) {
                $footer_settings[] = ncllc_pro_builder_setting_id('footer', $row, $cell);
            }
        }

        $wp_customize->selective_refresh->add_partial('ncllc_footer_partial', array(
            'selector'        => '.site-footer',
            'settings'        => $footer_settings,
            'render_callback' => 'ncllc_pro_render_site_footer',
        ));
    }
    
    // Add live preview JavaScript
    if ($wp_customize->is_preview()) {
        add_action('wp_footer', 'ncllc_pro_customizer_live_preview', 21);
    }
}
add_action('customize_register', 'ncllc_pro_customize_register');

function ncllc_pro_theme_mod_with_legacy_default($setting_id, $default, $legacy_defaults = array()) {
    $value = get_theme_mod($setting_id, $default);

    if (ncllc_pro_is_legacy_css_size_value($value, $legacy_defaults)) {
        return $default;
    }

    return $value;
}

/**
 * Live preview JavaScript for customizer
 */
function ncllc_pro_customizer_live_preview() {
    $builder_insert_choices = array(
        'header' => ncllc_pro_builder_insert_choices('header'),
        'footer' => ncllc_pro_builder_insert_choices('footer'),
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

        devices.forEach(function(device) {
            wp.customize('logo_height_' + device, function(value) {
                value.bind(function(newval) {
                    document.documentElement.style.setProperty('--ncllc-logo-height-' + device, newval + 'px');
                });
            });

            wp.customize('header_padding_' + device, function(value) {
                value.bind(function(newval) {
                    document.documentElement.style.setProperty('--ncllc-header-padding-' + device, newval + 'rem');
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
function ncllc_pro_customizer_css() {
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
    $footer_menu_gap = get_theme_mod('footer_menu_gap', '1.4rem');
    $footer_container_width = get_theme_mod('footer_container_width', '1280px');
    $footer_padding_top = get_theme_mod('footer_padding_top', '4rem');
    $footer_padding_bottom = get_theme_mod('footer_padding_bottom', '2rem');

    $old_logo_height = get_theme_mod('logo_height', '50');
    $old_header_padding = get_theme_mod('header_padding', '0.75');

    $logo_height_desktop = get_theme_mod('logo_height_desktop', $old_logo_height);
    $logo_height_tablet = get_theme_mod('logo_height_tablet', $old_logo_height);
    $logo_height_mobile = get_theme_mod('logo_height_mobile', $old_logo_height);

    $header_padding_desktop = get_theme_mod('header_padding_desktop', $old_header_padding);
    $header_padding_tablet = get_theme_mod('header_padding_tablet', $old_header_padding);
    $header_padding_mobile = get_theme_mod('header_padding_mobile', $old_header_padding);

    $hero_bg_1 = get_theme_mod('hero_bg_1', '#2563eb');
    $hero_bg_2 = get_theme_mod('hero_bg_2', '#7c3aed');
    $hero_heading_color = get_theme_mod('hero_heading_color', '#ffffff');
    $hero_subtitle_color = get_theme_mod('hero_subtitle_color', 'rgba(255,255,255,0.94)');
    $hero_badge_bg = get_theme_mod('hero_badge_bg', 'rgba(255,255,255,0.16)');
    $hero_badge_text_color = get_theme_mod('hero_badge_text_color', '#ffffff');
    $hero_button_bg = get_theme_mod('hero_button_bg', '#ffffff');
    $hero_button_text_color = get_theme_mod('hero_button_text_color', '#2563eb');

    $hero_min_height_desktop = ncllc_pro_theme_mod_with_legacy_default('hero_min_height_desktop', '50px', array('450px'));
    $hero_min_height_tablet = ncllc_pro_theme_mod_with_legacy_default('hero_min_height_tablet', '50px', array('400px'));
    $hero_min_height_mobile = ncllc_pro_theme_mod_with_legacy_default('hero_min_height_mobile', '50px', array('340px'));
    $hero_padding_top_desktop = ncllc_pro_theme_mod_with_legacy_default('hero_padding_top_desktop', '1rem', array('7rem', '8rem'));
    $hero_padding_bottom_desktop = ncllc_pro_theme_mod_with_legacy_default('hero_padding_bottom_desktop', '1rem', array('4rem'));
    $hero_padding_top_tablet = ncllc_pro_theme_mod_with_legacy_default('hero_padding_top_tablet', '1rem', array('6rem', '7rem'));
    $hero_padding_bottom_tablet = ncllc_pro_theme_mod_with_legacy_default('hero_padding_bottom_tablet', '1rem', array('3.5rem'));
    $hero_padding_top_mobile = ncllc_pro_theme_mod_with_legacy_default('hero_padding_top_mobile', '1rem', array('5rem', '6rem'));
    $hero_padding_bottom_mobile = ncllc_pro_theme_mod_with_legacy_default('hero_padding_bottom_mobile', '1rem', array('3rem'));
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
            --ajn-footer-menu-gap: <?php echo esc_attr($footer_menu_gap); ?>;
            --ajn-footer-container-width: <?php echo esc_attr($footer_container_width); ?>;
            --ajn-footer-padding-top: <?php echo esc_attr($footer_padding_top); ?>;
            --ajn-footer-padding-bottom: <?php echo esc_attr($footer_padding_bottom); ?>;
            --ast-global-color-0: <?php echo esc_attr($theme_primary_color); ?>;
            --ast-global-color-1: <?php echo esc_attr($theme_primary_dark_color); ?>;
            --ast-global-color-2: <?php echo esc_attr($theme_secondary_color); ?>;
            --ast-global-color-7: <?php echo esc_attr($theme_accent_color); ?>;

            --ncllc-logo-height-desktop: <?php echo esc_attr($logo_height_desktop); ?>px;
            --ncllc-logo-height-tablet: <?php echo esc_attr($logo_height_tablet); ?>px;
            --ncllc-logo-height-mobile: <?php echo esc_attr($logo_height_mobile); ?>px;
            --ncllc-header-padding-desktop: <?php echo esc_attr($header_padding_desktop); ?>rem;
            --ncllc-header-padding-tablet: <?php echo esc_attr($header_padding_tablet); ?>rem;
            --ncllc-header-padding-mobile: <?php echo esc_attr($header_padding_mobile); ?>rem;

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
        }

        .site-branding img,
        .custom-logo-link img {
            max-height: var(--ncllc-logo-height-desktop) !important;
        }

        .header-container {
            padding-top: var(--ncllc-header-padding-desktop) !important;
            padding-bottom: var(--ncllc-header-padding-desktop) !important;
        }

        @media (max-width: 1024px) {
            .site-branding img,
            .custom-logo-link img {
                max-height: var(--ncllc-logo-height-tablet) !important;
            }

            .header-container {
                padding-top: var(--ncllc-header-padding-tablet) !important;
                padding-bottom: var(--ncllc-header-padding-tablet) !important;
            }
        }

        @media (max-width: 768px) {
            .site-branding img,
            .custom-logo-link img {
                max-height: var(--ncllc-logo-height-mobile) !important;
            }

            .header-container {
                padding-top: var(--ncllc-header-padding-mobile) !important;
                padding-bottom: var(--ncllc-header-padding-mobile) !important;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'ncllc_pro_customizer_css');

/**
 * Add theme support for Gutenberg
 */
function ncllc_pro_gutenberg_support() {
    add_theme_support('align-wide');
    add_theme_support('appearance-tools');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('custom-spacing');
    add_theme_support('custom-units', array('px', 'rem', 'em', '%', 'vh', 'vw'));
    add_theme_support('editor-color-palette', array(
        array(
            'name'  => __('Primary Blue', 'ncllc-pro'),
            'slug'  => 'primary-blue',
            'color' => '#2563eb',
        ),
        array(
            'name'  => __('Deep Blue', 'ncllc-pro'),
            'slug'  => 'deep-blue',
            'color' => '#1e40af',
        ),
        array(
            'name'  => __('Purple', 'ncllc-pro'),
            'slug'  => 'purple',
            'color' => '#7c3aed',
        ),
        array(
            'name'  => __('Gold', 'ncllc-pro'),
            'slug'  => 'gold',
            'color' => '#f59e0b',
        ),
        array(
            'name'  => __('Ink', 'ncllc-pro'),
            'slug'  => 'ink',
            'color' => '#111827',
        ),
        array(
            'name'  => __('Soft Gray', 'ncllc-pro'),
            'slug'  => 'soft-gray',
            'color' => '#f3f4f6',
        ),
        array(
            'name'  => __('White', 'ncllc-pro'),
            'slug'  => 'white',
            'color' => '#ffffff',
        ),
    ));
    add_editor_style('style.css');
}
add_action('after_setup_theme', 'ncllc_pro_gutenberg_support');

/**
 * Add editor patterns for lightweight page-builder workflows.
 */
function ncllc_pro_register_block_patterns() {
    if (!function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern_category('ncllc-builder', array(
        'label' => __('NCLLC Builder Sections', 'ncllc-pro'),
    ));

    register_block_pattern('ncllc-pro/editable-hero', array(
        'title'       => __('Theme Hero', 'ncllc-pro'),
        'description' => __('A full-width page or post hero that uses the theme hero defaults until you override it on the block.', 'ncllc-pro'),
        'categories'  => array('ncllc-builder'),
        'keywords'    => array(__('hero', 'ncllc-pro'), __('page header', 'ncllc-pro'), __('post header', 'ncllc-pro')),
        'content'     => '<!-- wp:group {"align":"full","className":"builder-hero-section hero-height-standard hero-width-standard","layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center","flexWrap":"nowrap"}} --><div class="wp-block-group alignfull builder-hero-section hero-height-standard hero-width-standard"><!-- wp:heading {"textAlign":"center","level":1} --><h1 class="wp-block-heading has-text-align-center">Page Hero</h1><!-- /wp:heading --></div><!-- /wp:group -->',
    ));

    register_block_pattern('ncllc-pro/three-feature-cards', array(
        'title'       => __('Three Feature Cards', 'ncllc-pro'),
        'description' => __('A three-column card section for service highlights.', 'ncllc-pro'),
        'categories'  => array('ncllc-builder'),
        'content'     => '<!-- wp:group {"align":"full","className":"builder-section builder-section-soft","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull builder-section builder-section-soft"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">What You Can Edit</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center","className":"builder-section-intro"} --><p class="has-text-align-center builder-section-intro">Use columns, cards, buttons, images, lists, forms, and reusable sections without touching code.</p><!-- /wp:paragraph --><!-- wp:columns {"className":"builder-card-grid"} --><div class="wp-block-columns builder-card-grid"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"builder-card","layout":{"type":"constrained"}} --><div class="wp-block-group builder-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Service Detail</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Add or change service descriptions, calls to action, and supporting copy directly on the page.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"builder-card","layout":{"type":"constrained"}} --><div class="wp-block-group builder-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Trust Builder</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Use this card for testimonials, compliance details, process steps, or proof points.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"builder-card","layout":{"type":"constrained"}} --><div class="wp-block-group builder-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Fast CTA</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Point visitors to your contact page, phone number, quote form, or service comparison.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->',
    ));

    register_block_pattern('ncllc-pro/split-content-cta', array(
        'title'       => __('Split Content CTA', 'ncllc-pro'),
        'description' => __('A two-column section with copy and a call to action.', 'ncllc-pro'),
        'categories'  => array('ncllc-builder'),
        'content'     => '<!-- wp:group {"align":"full","className":"builder-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull builder-section"><!-- wp:columns {"verticalAlignment":"center","className":"builder-split"} --><div class="wp-block-columns are-vertically-aligned-center builder-split"><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading --><h2 class="wp-block-heading">Build Pages Visually</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Select this section, duplicate it, drag it above or below other sections, and edit the text/buttons in place.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"verticalAlignment":"center","className":"builder-cta-panel"} --><div class="wp-block-column is-vertically-aligned-center builder-cta-panel"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Need a registered agent?</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Keep your North Carolina LLC compliant with a reliable local registered agent.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Contact Us</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->',
    ));
}
add_action('init', 'ncllc_pro_register_block_patterns');
require_once get_template_directory() . '/inc/github-theme-updater.php';
require_once get_template_directory() . '/inc/duplicate-content.php';
require_once get_template_directory() . '/blocks/ajnanda-blocks/loader.php';
