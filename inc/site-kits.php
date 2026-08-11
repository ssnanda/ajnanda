<?php
/**
 * AJNanda Site Kits.
 *
 * A "kit" is nothing more than a named pairing of one Color Scheme
 * (inc/color-schemes.php) with one Font Pairing (inc/font-pairings.php) —
 * both systems already exist independently; this file only adds the
 * "apply both together, one click" layer on top, the same way the color
 * presets already quick-fill 4 existing settings rather than introducing
 * a new one. No new design system, no builder — just a curated shortcut
 * through two registries that were already there.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string,array{label:string,color_scheme:string,font_pairing:string,mood:string}>
 */
function ajnanda_get_site_kits() {
    return array(
        'corporate-blue' => array(
            'label'        => __('Corporate Blue', 'ajnanda'),
            'color_scheme' => 'blue',
            'font_pairing' => 'classic',
            'mood'         => __('Balanced and professional — the original AJNanda default.', 'ajnanda'),
        ),
        'elegant-gold' => array(
            'label'        => __('Elegant Gold', 'ajnanda'),
            'color_scheme' => 'gold',
            'font_pairing' => 'elegant-serif',
            'mood'         => __('Premium and editorial — boutique, hospitality, professional services.', 'ajnanda'),
        ),
        'modern-tech' => array(
            'label'        => __('Modern Tech', 'ajnanda'),
            'color_scheme' => 'indigo',
            'font_pairing' => 'modern-sans',
            'mood'         => __('Clean and understated — SaaS, startups, technology.', 'ajnanda'),
        ),
        'bold-startup' => array(
            'label'        => __('Bold Startup', 'ajnanda'),
            'color_scheme' => 'orange',
            'font_pairing' => 'bold-display',
            'mood'         => __('High-energy and confident — product launches, growth-stage brands.', 'ajnanda'),
        ),
        'minimal-slate' => array(
            'label'        => __('Minimal Slate', 'ajnanda'),
            'color_scheme' => 'slate',
            'font_pairing' => 'modern-sans',
            'mood'         => __('Quiet and understated — portfolios, consultancies.', 'ajnanda'),
        ),
        'dark-premium' => array(
            'label'        => __('Dark Premium', 'ajnanda'),
            'color_scheme' => 'dark',
            'font_pairing' => 'elegant-serif',
            'mood'         => __('Moody and high-end — luxury, real estate, finance.', 'ajnanda'),
        ),
        'neon-night' => array(
            'label'        => __('Neon Night', 'ajnanda'),
            'color_scheme' => 'neon-funk',
            'font_pairing' => 'bold-display',
            'mood'         => __('Loud, dark, electric — bands, DJs, nightlife, events.', 'ajnanda'),
        ),
        'bubblegum-pop' => array(
            'label'        => __('Bubblegum Pop', 'ajnanda'),
            'color_scheme' => 'bubblegum',
            'font_pairing' => 'playful-rounded',
            'mood'         => __('Soft, pastel, playful — kids, hobby, community sites.', 'ajnanda'),
        ),
        'little-one' => array(
            'label'        => __('Little One', 'ajnanda'),
            'color_scheme' => 'nursery',
            'font_pairing' => 'playful-rounded',
            'mood'         => __('Soft and sweet, gender-neutral — birth announcements, nurseries, baby milestones.', 'ajnanda'),
        ),
        'family-warmth' => array(
            'label'        => __('Family Warmth', 'ajnanda'),
            'color_scheme' => 'amber',
            'font_pairing' => 'elegant-serif',
            'mood'         => __('Warm and personal, journal-like — family blogs, updates, community pages.', 'ajnanda'),
        ),
        'developer-portfolio' => array(
            'label'        => __('Developer Portfolio', 'ajnanda'),
            'color_scheme' => 'sky',
            'font_pairing' => 'developer-mono',
            'mood'         => __('Clean, spacious, tech-forward — developer portfolios, technical/coding sites.', 'ajnanda'),
        ),
        'ubuntu-terminal' => array(
            'label'        => __('Ubuntu Terminal', 'ajnanda'),
            'color_scheme' => 'aubergine',
            'font_pairing' => 'developer-mono',
            'dark_surface' => true,
            'mood'         => __('Deep charcoal with a warm terminal-orange glow — Ubuntu/GNOME-desktop-inspired, for developer portfolios that mean it.', 'ajnanda'),
        ),
    );
}

/**
 * Which kit (if any) matches the site's current, actually-saved color
 * scheme AND font pairing. Same idea as
 * ajnanda_get_active_color_scheme_slug() — returns 'custom' the moment
 * either half has been hand-tuned away from a registered kit.
 *
 * @return string A key from ajnanda_get_site_kits(), or 'custom'.
 */
function ajnanda_get_active_site_kit_slug() {
    $active_color = function_exists('ajnanda_get_active_color_scheme_slug') ? ajnanda_get_active_color_scheme_slug() : '';
    $active_font  = function_exists('ajnanda_get_active_font_pairing_slug') ? ajnanda_get_active_font_pairing_slug() : '';
    $active_dark  = function_exists('ajnanda_is_dark_surface_mode_active') ? ajnanda_is_dark_surface_mode_active() : false;

    foreach (ajnanda_get_site_kits() as $slug => $kit) {
        $kit_dark = !empty($kit['dark_surface']);
        if ($kit['color_scheme'] === $active_color && $kit['font_pairing'] === $active_font && $kit_dark === $active_dark) {
            return $slug;
        }
    }

    return 'custom';
}

/**
 * Apply a kit's underlying color scheme + font pairing to the real, saved
 * site settings — the one implementation used by `wp ajnanda site-kit
 * set` and the Starter Sites import form's opt-in "Apply this Site Kit"
 * checkbox. NOT used by the "Quick Kits" Customizer control below, which
 * deliberately stays client-side/non-committal (fills in the controls,
 * still requires Publish) like every other quick-preset in this theme —
 * this function is for the two places that *do* mean to commit the
 * change immediately.
 *
 * @param string $kit_slug A key from ajnanda_get_site_kits().
 * @return bool True on success, false for an unknown kit or color scheme.
 */
function ajnanda_apply_site_kit($kit_slug) {
    $kits = ajnanda_get_site_kits();
    if (!isset($kits[$kit_slug])) {
        return false;
    }

    $kit     = $kits[$kit_slug];
    $schemes = function_exists('ajnanda_get_color_schemes') ? ajnanda_get_color_schemes() : array();
    if (!isset($schemes[$kit['color_scheme']])) {
        return false;
    }

    $scheme = $schemes[$kit['color_scheme']];
    set_theme_mod('theme_primary_color', $scheme['primary']);
    set_theme_mod('theme_primary_dark_color', $scheme['primary_dark']);
    set_theme_mod('theme_secondary_color', $scheme['secondary']);
    set_theme_mod('theme_accent_color', $scheme['accent']);
    set_theme_mod('theme_font_pairing', $kit['font_pairing']);
    // Explicitly set (not skipped when absent) so applying a kit is fully
    // deterministic — a kit with no 'dark_surface' key means "light
    // surfaces," the same as every kit defined before this setting existed.
    set_theme_mod('ajnanda_dark_surface_mode', !empty($kit['dark_surface']));

    return true;
}

/* -----------------------------------------------------------------------
 * "Quick Kits" — one more control at the very top of the native Colors
 * panel (above the existing color-only "Quick presets" swatches), whose
 * click sets 5 things at once: the 4 real color settings (exactly what the
 * color presets already do) plus theme_font_pairing. Still just fills in
 * existing Customizer controls via wp.customize(id).set() — nothing is
 * saved until Publish, same as every other quick-preset in this theme.
 * ------------------------------------------------------------------- */

function ajnanda_register_site_kit_control($wp_customize) {
    if (!class_exists('WP_Customize_Control')) {
        return;
    }

    if (!class_exists('AJNanda_Site_Kit_Control')) {
        class AJNanda_Site_Kit_Control extends WP_Customize_Control {
            public $type = 'ajnanda_site_kits';

            public function render_content() {
                $color_schemes = ajnanda_get_color_schemes();
                $font_pairings = ajnanda_get_font_pairings();
                ?>
                <span class="customize-control-title"><?php esc_html_e('Quick Kits', 'ajnanda'); ?></span>
                <p class="description"><?php esc_html_e('A color scheme and a font pairing together — a complete look in one click. Fine-tune with the presets and pickers below afterward if you like.', 'ajnanda'); ?></p>
                <div class="ajnanda-site-kit-options">
                    <?php foreach (ajnanda_get_site_kits() as $slug => $kit) :
                        $scheme  = isset($color_schemes[$kit['color_scheme']]) ? $color_schemes[$kit['color_scheme']] : null;
                        $pairing = isset($font_pairings[$kit['font_pairing']]) ? $font_pairings[$kit['font_pairing']] : null;
                        if (!$scheme || !$pairing) {
                            continue;
                        }
                    ?>
                        <button
                            type="button"
                            class="ajnanda-site-kit"
                            data-primary="<?php echo esc_attr($scheme['primary']); ?>"
                            data-primary-dark="<?php echo esc_attr($scheme['primary_dark']); ?>"
                            data-secondary="<?php echo esc_attr($scheme['secondary']); ?>"
                            data-accent="<?php echo esc_attr($scheme['accent']); ?>"
                            data-font-pairing="<?php echo esc_attr($kit['font_pairing']); ?>"
                            data-dark-surface="<?php echo !empty($kit['dark_surface']) ? '1' : '0'; ?>"
                        >
                            <span class="ajnanda-site-kit-swatch" style="background:<?php echo esc_attr($scheme['swatch']); ?>;"></span>
                            <span class="ajnanda-site-kit-info">
                                <span class="ajnanda-site-kit-label" style="font-family:<?php echo esc_attr($pairing['heading_stack']); ?>;"><?php echo esc_html($kit['label']); ?></span>
                                <span class="ajnanda-site-kit-mood"><?php echo esc_html($kit['mood']); ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php
            }
        }
    }

    $wp_customize->add_setting('ajnanda_site_kits_noop', array(
        'sanitize_callback' => '__return_empty_string',
    ));

    $wp_customize->add_control(new AJNanda_Site_Kit_Control(
        $wp_customize,
        'ajnanda_site_kits_noop',
        array(
            'section'  => 'colors',
            'priority' => -10, // above the color-scheme "Quick presets" swatches (priority 0).
        )
    ));
}
add_action('customize_register', 'ajnanda_register_site_kit_control');

function ajnanda_site_kit_control_assets() {
    wp_add_inline_style('customize-controls', '
        .ajnanda-site-kit-options { display: flex; flex-direction: column; gap: 8px; margin: 8px 0 16px; }
        .ajnanda-site-kit { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border: 1px solid #dcdcde; border-radius: 8px; background: #fff; cursor: pointer; text-align: left; }
        .ajnanda-site-kit:hover, .ajnanda-site-kit:focus { border-color: #2563eb; background: #eff6ff; outline: none; }
        .ajnanda-site-kit-swatch { width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0; border: 2px solid #fff; box-shadow: 0 0 0 1px #dcdcde; }
        .ajnanda-site-kit-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .ajnanda-site-kit-label { font-size: 13px; font-weight: 700; color: #111827; }
        .ajnanda-site-kit-mood { font-size: 11px; color: #6b7280; }
    ');

    $handle = 'ajnanda-site-kit-controls';
    wp_register_script($handle, false, array('customize-controls', 'jquery'), false, true);
    wp_enqueue_script($handle);
    wp_add_inline_script($handle, "
        (function ($) {
            $(document).on('click', '.ajnanda-site-kit', function () {
                var btn = this;
                ['primary', 'primary-dark', 'secondary', 'accent'].forEach(function (role) {
                    var settingId = 'theme_' + (role === 'primary-dark' ? 'primary_dark' : role) + '_color';
                    var value = btn.getAttribute('data-' + role);
                    var setting = wp.customize(settingId);
                    if (setting && value) {
                        setting.set(value);
                    }
                });
                var fontSetting = wp.customize('theme_font_pairing');
                var fontValue = btn.getAttribute('data-font-pairing');
                if (fontSetting && fontValue) {
                    fontSetting.set(fontValue);
                }
                var darkSetting = wp.customize('ajnanda_dark_surface_mode');
                if (darkSetting) {
                    darkSetting.set(btn.getAttribute('data-dark-surface') === '1');
                }
            });
        })(jQuery);
    ");
}
add_action('customize_controls_enqueue_scripts', 'ajnanda_site_kit_control_assets');
