<?php
/**
 * AJNanda Color Schemes.
 *
 * AJNanda already has a real, native color system: 4 WP_Customize_Color_Control
 * pickers (Primary Color, Primary Hover Color, Secondary Color, Accent Color)
 * registered under the built-in Appearance → Customize → Colors panel
 * (functions.php, theme_primary_color / theme_primary_dark_color /
 * theme_secondary_color / theme_accent_color, output as :root custom
 * properties by ajnanda_customizer_css() on wp_head). That system already
 * gives unlimited custom colors via WordPress's native color picker — this
 * file does NOT duplicate it with a second setting. It:
 *
 *  1. Closes the one real gap in that system: ajnanda_customizer_css() only
 *     hooks `wp_head`, so its colors never reach the block editor's iframe
 *     — confirmed against a real AJNanda site (fed.ddev.site) whose actual
 *     brand color (#B45309) was correctly live on the frontend but the
 *     "Choose a pattern" modal still previewed default blue, because those
 *     previews render inside the iframed editor, which the frontend-only
 *     hook never reaches.
 *  2. Adds one-click preset swatches (Blue/Purple/Gold/Dark/Amber) inside
 *     that same native Colors panel — clicking one just fills in the 4
 *     existing color pickers via the Customizer JS API, it does not
 *     introduce a new stored setting of its own.
 *  3. Lets a single page in the Page Library use a different preset than
 *     the site-wide one, by wrapping that page's content in a matching
 *     .ajnanda-scheme-{slug} class (see style.css) — scoped to one page,
 *     with a warning that it won't match the rest of the site.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Named quick-fill presets for the theme's 4 real brand-color settings.
 * Primary/secondary/accent values reuse the theme's existing theme.json
 * palette colors (primary-blue, deep-blue, purple, gold, ink); "amber"
 * matches a real AJNanda site's existing hand-set brand color exactly.
 * Only the "-dark" hover shades are new, matching the theme's existing
 * practice of keeping hover shades in style.css rather than theme.json.
 *
 * @return array<string,array{label:string,swatch:string,primary:string,primary_dark:string,secondary:string,accent:string}>
 */
function ajnanda_get_color_schemes() {
    return array(
        'blue' => array(
            'label' => __('Blue (Default)', 'ajnanda'), 'swatch' => '#2563eb',
            'primary' => '#2563eb', 'primary_dark' => '#1e40af', 'secondary' => '#7c3aed', 'accent' => '#f59e0b',
        ),
        'purple' => array(
            'label' => __('Purple', 'ajnanda'), 'swatch' => '#7c3aed',
            'primary' => '#7c3aed', 'primary_dark' => '#6d28d9', 'secondary' => '#2563eb', 'accent' => '#f59e0b',
        ),
        'gold' => array(
            'label' => __('Gold', 'ajnanda'), 'swatch' => '#f59e0b',
            'primary' => '#f59e0b', 'primary_dark' => '#b45309', 'secondary' => '#111827', 'accent' => '#2563eb',
        ),
        'dark' => array(
            'label' => __('Dark', 'ajnanda'), 'swatch' => '#111827',
            'primary' => '#111827', 'primary_dark' => '#000000', 'secondary' => '#f59e0b', 'accent' => '#f59e0b',
        ),
        'amber' => array(
            'label' => __('Amber', 'ajnanda'), 'swatch' => '#b45309',
            'primary' => '#b45309', 'primary_dark' => '#92400e', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'teal' => array(
            'label' => __('Teal', 'ajnanda'), 'swatch' => '#0d9488',
            'primary' => '#0d9488', 'primary_dark' => '#0f766e', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'emerald' => array(
            'label' => __('Emerald', 'ajnanda'), 'swatch' => '#059669',
            'primary' => '#059669', 'primary_dark' => '#047857', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'red' => array(
            'label' => __('Red', 'ajnanda'), 'swatch' => '#dc2626',
            'primary' => '#dc2626', 'primary_dark' => '#b91c1c', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'rose' => array(
            'label' => __('Rose', 'ajnanda'), 'swatch' => '#e11d48',
            'primary' => '#e11d48', 'primary_dark' => '#be123c', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'pink' => array(
            'label' => __('Pink', 'ajnanda'), 'swatch' => '#db2777',
            'primary' => '#db2777', 'primary_dark' => '#be185d', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'orange' => array(
            'label' => __('Orange', 'ajnanda'), 'swatch' => '#ea580c',
            'primary' => '#ea580c', 'primary_dark' => '#c2410c', 'secondary' => '#111827', 'accent' => '#2563eb',
        ),
        'indigo' => array(
            'label' => __('Indigo', 'ajnanda'), 'swatch' => '#4f46e5',
            'primary' => '#4f46e5', 'primary_dark' => '#4338ca', 'secondary' => '#7c3aed', 'accent' => '#f59e0b',
        ),
        'sky' => array(
            'label' => __('Sky', 'ajnanda'), 'swatch' => '#0284c7',
            'primary' => '#0284c7', 'primary_dark' => '#0369a1', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'cyan' => array(
            'label' => __('Cyan', 'ajnanda'), 'swatch' => '#0891b2',
            'primary' => '#0891b2', 'primary_dark' => '#0e7490', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'violet' => array(
            'label' => __('Violet', 'ajnanda'), 'swatch' => '#6d28d9',
            'primary' => '#6d28d9', 'primary_dark' => '#5b21b6', 'secondary' => '#2563eb', 'accent' => '#f59e0b',
        ),
        'fuchsia' => array(
            'label' => __('Fuchsia', 'ajnanda'), 'swatch' => '#c026d3',
            'primary' => '#c026d3', 'primary_dark' => '#a21caf', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'lime' => array(
            'label' => __('Lime', 'ajnanda'), 'swatch' => '#65a30d',
            'primary' => '#65a30d', 'primary_dark' => '#4d7c0f', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
        'slate' => array(
            'label' => __('Slate', 'ajnanda'), 'swatch' => '#334155',
            'primary' => '#334155', 'primary_dark' => '#1e293b', 'secondary' => '#64748b', 'accent' => '#f59e0b',
        ),
        'navy' => array(
            'label' => __('Navy', 'ajnanda'), 'swatch' => '#1e3a8a',
            'primary' => '#1e3a8a', 'primary_dark' => '#172554', 'secondary' => '#2563eb', 'accent' => '#f59e0b',
        ),
        'crimson' => array(
            'label' => __('Crimson', 'ajnanda'), 'swatch' => '#991b1b',
            'primary' => '#991b1b', 'primary_dark' => '#7f1d1d', 'secondary' => '#111827', 'accent' => '#f59e0b',
        ),
    );
}

/**
 * The theme's 4 real brand-color settings, as currently saved — same
 * theme_mod names and defaults as functions.php's ajnanda_customizer_css(),
 * so this always reflects exactly what the frontend already shows,
 * whether that came from a preset, the native color pickers, or a value
 * set before this file existed.
 *
 * @return array{primary:string,primary_dark:string,secondary:string,accent:string}
 */
function ajnanda_get_active_brand_colors() {
    return array(
        'primary'      => get_theme_mod('theme_primary_color', '#2563eb'),
        'primary_dark' => get_theme_mod('theme_primary_dark_color', '#1e40af'),
        'secondary'    => get_theme_mod('theme_secondary_color', '#7c3aed'),
        'accent'       => get_theme_mod('theme_accent_color', '#f59e0b'),
    );
}

/**
 * Which preset (if any) matches the current brand colors exactly.
 *
 * @return string A key from ajnanda_get_color_schemes(), or 'custom' if the
 *                 saved colors don't exactly match any preset (e.g. picked
 *                 by hand in the native color pickers).
 */
function ajnanda_get_active_color_scheme_slug() {
    $active = array_map('strtolower', ajnanda_get_active_brand_colors());

    foreach (ajnanda_get_color_schemes() as $slug => $scheme) {
        if (
            strtolower($scheme['primary']) === $active['primary']
            && strtolower($scheme['primary_dark']) === $active['primary_dark']
            && strtolower($scheme['secondary']) === $active['secondary']
            && strtolower($scheme['accent']) === $active['accent']
        ) {
            return $slug;
        }
    }

    return 'custom';
}

/**
 * @param array{primary:string,primary_dark:string,secondary:string,accent:string} $c
 * @return string A `:root{...}` CSS custom-property override.
 */
function ajnanda_format_brand_colors_css(array $c) {
    return sprintf(
        ':root{--primary:%1$s;--primary-dark:%2$s;--secondary:%3$s;--accent:%4$s;}',
        esc_attr($c['primary']),
        esc_attr($c['primary_dark']),
        esc_attr($c['secondary']),
        esc_attr($c['accent'])
    );
}

/**
 * @return string A `:root{...}` CSS custom-property override for the
 *                theme's 4 real brand-color settings, from their current
 *                saved values (not tied to a preset).
 */
function ajnanda_get_active_brand_colors_css() {
    return ajnanda_format_brand_colors_css(ajnanda_get_active_brand_colors());
}

/**
 * Same idea as ajnanda_get_active_brand_colors_css(), but for any named
 * preset regardless of what's currently saved — used by the non-destructive
 * preview system (inc/preview.php) so a scheme can be previewed without
 * changing the site's actual colors.
 *
 * @param string $slug A key from ajnanda_get_color_schemes().
 * @return string `:root{...}` CSS, or '' for an unknown slug.
 */
function ajnanda_get_color_scheme_preset_css($slug) {
    $schemes = ajnanda_get_color_schemes();
    if (!isset($schemes[$slug])) {
        return '';
    }
    return ajnanda_format_brand_colors_css($schemes[$slug]);
}

/**
 * Wrap a block of page content in a per-page color-preset class. Used by
 * the Page Library "Add as New Page" action when the chosen preset differs
 * from the site's current brand colors. No-op when it matches, to avoid an
 * unnecessary wrapper group on the common path.
 *
 * @param string $content Composed block markup.
 * @param string $slug    Color scheme slug.
 * @return string
 */
function ajnanda_wrap_content_with_color_scheme($content, $slug) {
    $schemes = ajnanda_get_color_schemes();
    if (!isset($schemes[$slug]) || $slug === ajnanda_get_active_color_scheme_slug()) {
        return $content;
    }

    $class = 'ajnanda-scheme-' . $slug;

    return '<!-- wp:group {"className":"' . $class . '","layout":{"type":"constrained"}} -->'
        . '<div class="wp-block-group ' . $class . '">' . $content . '</div>'
        . '<!-- /wp:group -->';
}

/* -----------------------------------------------------------------------
 * Close the editor gap: push the theme's real, already-saved brand colors
 * into the classic editor stylesheet and the iframed editor/pattern
 * previews. Does not touch the frontend — ajnanda_customizer_css() already
 * covers it on wp_head, this would just be a redundant duplicate there.
 * ------------------------------------------------------------------- */

function ajnanda_enqueue_color_scheme_editor_css() {
    if (!wp_style_is('ajnanda-pro-editor-style', 'registered')) {
        return;
    }
    wp_add_inline_style('ajnanda-pro-editor-style', ajnanda_get_active_brand_colors_css());
}
add_action('enqueue_block_editor_assets', 'ajnanda_enqueue_color_scheme_editor_css', 20);

/**
 * Reaches the iframed block editor canvas and, critically, the "Choose a
 * pattern" modal's own preview thumbnails — neither is covered by a normal
 * enqueued stylesheet, only by being added to the editor's own settings.
 */
function ajnanda_add_color_scheme_to_iframed_editor($settings) {
    if (!isset($settings['styles']) || !is_array($settings['styles'])) {
        $settings['styles'] = array();
    }
    $settings['styles'][] = array('css' => ajnanda_get_active_brand_colors_css());
    return $settings;
}
add_filter('block_editor_settings_all', 'ajnanda_add_color_scheme_to_iframed_editor');

/* -----------------------------------------------------------------------
 * One-click preset swatches inside the native Colors panel — set the 4
 * real, already-registered color settings via the Customizer JS API.
 * Introduces no new stored setting.
 * ------------------------------------------------------------------- */

function ajnanda_register_color_preset_control($wp_customize) {
    // WP_Customize_Control only exists on customize.php requests. This hook
    // only ever fires there too, but the class must still be declared
    // inside it (not at file scope, which loads on every request including
    // WP-CLI/frontend/REST) — same pattern the theme's own Customizer code
    // already uses for its other custom control subclasses.
    if (!class_exists('WP_Customize_Control')) {
        return;
    }

    if (!class_exists('AJNanda_Color_Preset_Control')) {
        class AJNanda_Color_Preset_Control extends WP_Customize_Control {
            public $type = 'ajnanda_color_presets';

            public function render_content() {
                ?>
                <span class="customize-control-title"><?php esc_html_e('Quick presets', 'ajnanda'); ?></span>
                <p class="description"><?php esc_html_e('Fills in the 4 color pickers below. You can still fine-tune or pick any custom color afterward.', 'ajnanda'); ?></p>
                <div class="ajnanda-preset-swatches">
                    <?php foreach (ajnanda_get_color_schemes() as $slug => $scheme) : ?>
                        <button
                            type="button"
                            class="ajnanda-preset-swatch"
                            style="background:<?php echo esc_attr($scheme['swatch']); ?>"
                            data-primary="<?php echo esc_attr($scheme['primary']); ?>"
                            data-primary-dark="<?php echo esc_attr($scheme['primary_dark']); ?>"
                            data-secondary="<?php echo esc_attr($scheme['secondary']); ?>"
                            data-accent="<?php echo esc_attr($scheme['accent']); ?>"
                            title="<?php echo esc_attr($scheme['label']); ?>"
                        ><span class="screen-reader-text"><?php echo esc_html($scheme['label']); ?></span></button>
                    <?php endforeach; ?>
                </div>
                <?php
            }
        }
    }

    $wp_customize->add_setting('ajnanda_color_presets_noop', array(
        'sanitize_callback' => '__return_empty_string',
    ));

    $wp_customize->add_control(new AJNanda_Color_Preset_Control(
        $wp_customize,
        'ajnanda_color_presets_noop',
        array(
            'section'  => 'colors',
            'priority' => 0, // above the 4 real color pickers.
        )
    ));
}
add_action('customize_register', 'ajnanda_register_color_preset_control');

function ajnanda_color_preset_control_assets() {
    wp_add_inline_style('customize-controls', '
        .ajnanda-preset-swatches { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 16px; }
        .ajnanda-preset-swatch { width: 32px; height: 32px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #dcdcde; cursor: pointer; padding: 0; }
        .ajnanda-preset-swatch:hover, .ajnanda-preset-swatch:focus { box-shadow: 0 0 0 2px #2563eb; outline: none; }
    ');

    $handle = 'ajnanda-color-preset-controls';
    wp_register_script($handle, false, array('customize-controls', 'jquery'), false, true);
    wp_enqueue_script($handle);
    wp_add_inline_script($handle, "
        (function ($) {
            $(document).on('click', '.ajnanda-preset-swatch', function () {
                var btn = this;
                ['primary', 'primary-dark', 'secondary', 'accent'].forEach(function (role) {
                    var settingId = 'theme_' + (role === 'primary-dark' ? 'primary_dark' : role) + '_color';
                    var value = btn.getAttribute('data-' + role);
                    var setting = wp.customize(settingId);
                    if (setting && value) {
                        setting.set(value);
                    }
                });
            });
        })(jQuery);
    ");
}
add_action('customize_controls_enqueue_scripts', 'ajnanda_color_preset_control_assets');
