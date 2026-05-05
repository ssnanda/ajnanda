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
    wp_enqueue_style('ncllc-pro-style', get_stylesheet_uri(), array(), '1.0.22');
    
    // Enqueue custom JavaScript
    wp_enqueue_script('ncllc-pro-script', get_template_directory_uri() . '/js/main.js', array('jquery'), '1.0.22', true);
    
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
    wp_enqueue_style('ncllc-pro-editor-style', get_stylesheet_uri(), array(), '1.0.22');
    wp_enqueue_script(
        'ncllc-pro-editor-controls',
        get_template_directory_uri() . '/js/editor-controls.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks'),
        '1.0.22',
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
}
add_action('widgets_init', 'ncllc_pro_widgets_init');

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
 * Footer column defaults and saved Customizer values.
 */
function ncllc_pro_get_footer_columns() {
    $defaults = array(
        1 => array(
            'title' => 'NC LLC Agents Inc',
            'text'  => "Professional registered agent services for North Carolina businesses. Reliable, affordable, and compliant.",
        ),
        2 => array(
            'title' => 'Quick Links',
            'text'  => "Home|/\nAbout|/about/\nKnowledge Base|/knowledge-base/\nContact|/contact/",
        ),
        3 => array(
            'title' => 'Services',
            'text'  => "Registered Agent\nDocument Access\nCompliance Support",
        ),
        4 => array(
            'title' => 'Location',
            'text'  => "Charlotte, NC\nServing all of North Carolina\nSame day setup available",
        ),
    );

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

/**
 * Render the editable site footer.
 */
function ncllc_pro_render_site_footer() {
    ob_start();
    ?>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <?php foreach (ncllc_pro_get_footer_columns() as $column) : ?>
                    <div class="footer-section">
                        <?php if (!empty($column['title'])) : ?>
                            <h3><?php echo esc_html($column['title']); ?></h3>
                        <?php endif; ?>
                        <?php ncllc_pro_render_footer_lines($column['text']); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo esc_html(date('Y')); ?> <?php echo esc_html(get_theme_mod('footer_bottom_text', 'NC LLC Agents Inc. All rights reserved. | Serving North Carolina Businesses')); ?></p>
            </div>
        </div>
    </footer>
    <?php
    return ob_get_clean();
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
    // Header Settings Section
    $wp_customize->add_section('ncllc_header', array(
        'title'    => __('Header', 'ncllc-pro'),
        'priority' => 25,
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

    // Footer Section
    $wp_customize->add_section('ncllc_footer', array(
        'title'       => __('Footer', 'ncllc-pro'),
        'priority'    => 26,
        'description' => __('Edit each footer column here. For linked lines, use Label|URL, for example Home|/.', 'ncllc-pro'),
    ));

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
        ));
    }

    $wp_customize->add_setting('footer_bottom_text', array(
        'default'           => 'NC LLC Agents Inc. All rights reserved. | Serving North Carolina Businesses',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('footer_bottom_text', array(
        'label'   => __('Footer Bottom Text', 'ncllc-pro'),
        'section' => 'ncllc_footer',
        'type'    => 'text',
    ));

    if (isset($wp_customize->selective_refresh)) {
        $footer_settings = array('footer_bottom_text');
        for ($i = 1; $i <= 4; $i++) {
            $footer_settings[] = 'footer_column_' . $i . '_title';
            $footer_settings[] = 'footer_column_' . $i . '_text';
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

/**
 * Live preview JavaScript for customizer
 */
function ncllc_pro_customizer_live_preview() {
    ?>
    <script type="text/javascript">
    (function($) {
        var devices = ['desktop', 'tablet', 'mobile'];

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
    })(jQuery);
    </script>
    <?php
}

/**
 * Output custom CSS for customizer settings
 */
function ncllc_pro_customizer_css() {
    $old_logo_height = get_theme_mod('logo_height', '50');
    $old_header_padding = get_theme_mod('header_padding', '0.75');
    $logo_height_desktop = get_theme_mod('logo_height_desktop', $old_logo_height);
    $logo_height_tablet = get_theme_mod('logo_height_tablet', $old_logo_height);
    $logo_height_mobile = get_theme_mod('logo_height_mobile', $old_logo_height);
    $header_padding_desktop = get_theme_mod('header_padding_desktop', $old_header_padding);
    $header_padding_tablet = get_theme_mod('header_padding_tablet', $old_header_padding);
    $header_padding_mobile = get_theme_mod('header_padding_mobile', $old_header_padding);
    ?>
    <style type="text/css">
        :root {
            --ncllc-logo-height-desktop: <?php echo esc_attr($logo_height_desktop); ?>px;
            --ncllc-logo-height-tablet: <?php echo esc_attr($logo_height_tablet); ?>px;
            --ncllc-logo-height-mobile: <?php echo esc_attr($logo_height_mobile); ?>px;
            --ncllc-header-padding-desktop: <?php echo esc_attr($header_padding_desktop); ?>rem;
            --ncllc-header-padding-tablet: <?php echo esc_attr($header_padding_tablet); ?>rem;
            --ncllc-header-padding-mobile: <?php echo esc_attr($header_padding_mobile); ?>rem;
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
        'title'       => __('Editable Blue Hero', 'ncllc-pro'),
        'description' => __('A full-width hero section that lives inside page content.', 'ncllc-pro'),
        'categories'  => array('ncllc-builder'),
        'content'     => '<!-- wp:group {"align":"full","className":"builder-hero-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull builder-hero-section"><!-- wp:paragraph {"align":"center","className":"builder-hero-badge"} --><p class="has-text-align-center builder-hero-badge">NC LLC Agents Inc</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":1} --><h1 class="wp-block-heading has-text-align-center">Reliable North Carolina Registered Agent Service</h1><!-- /wp:heading --><!-- wp:paragraph {"align":"center","className":"builder-hero-subtitle"} --><p class="has-text-align-center builder-hero-subtitle">Edit this section directly in the page editor, including the headline, supporting text, buttons, colors, and spacing.</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Get Started</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/services/">View Services</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->',
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

require_once get_template_directory() . '/inc/theme-details-updater-button.php';
require_once get_template_directory() . '/inc/github-theme-updater.php';
require_once get_template_directory() . '/inc/duplicate-content.php';