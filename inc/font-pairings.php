<?php
/**
 * AJNanda Font Pairings.
 *
 * Mirrors inc/color-schemes.php on purpose — same shape, same mechanism,
 * same non-destructive preview integration — because it's solving the same
 * problem for typography that that file already solved for color: a
 * curated set of named presets that set two real CSS custom properties
 * (--font-heading / --font-body), rather than a new page-builder-style
 * "design system." The 8 underlying font families are also registered in
 * theme.json (settings.typography.fontFamilies) so they show up as normal,
 * per-block choices in the native block editor Typography panel too — this
 * file only adds the "apply one everywhere" layer on top.
 *
 * 'classic' (Poppins headings / Inter body) matches AJNanda's original
 * hardcoded fonts exactly, byte for byte — every existing site keeps
 * rendering identically unless a different pairing is deliberately chosen.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Named font pairings. Each sets --font-heading and --font-body (style.css
 * already reads both, with the 'classic' values as the CSS fallback too).
 *
 * @return array<string,array{label:string,heading_font:string,body_font:string,heading_stack:string,body_stack:string,google_families:string,mood:string}>
 */
function ajnanda_get_font_pairings() {
    return array(
        'classic' => array(
            'label'           => __('Classic (Default)', 'ajnanda'),
            'heading_font'    => 'Poppins',
            'body_font'       => 'Inter',
            'heading_stack'   => "'Poppins', sans-serif",
            'body_stack'      => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            'google_families' => 'Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900',
            'mood'            => __('Balanced, professional — the original AJNanda look.', 'ajnanda'),
        ),
        'modern-sans' => array(
            'label'           => __('Modern Sans', 'ajnanda'),
            'heading_font'    => 'Manrope',
            'body_font'       => 'Manrope',
            'heading_stack'   => "'Manrope', sans-serif",
            'body_stack'      => "'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            'google_families' => 'Manrope:wght@400;500;600;700;800',
            'mood'            => __('Clean, single-family, understated tech/SaaS feel.', 'ajnanda'),
        ),
        'elegant-serif' => array(
            'label'           => __('Elegant Serif', 'ajnanda'),
            'heading_font'    => 'Playfair Display',
            'body_font'       => 'Lora',
            'heading_stack'   => "'Playfair Display', serif",
            'body_stack'      => "'Lora', Georgia, serif",
            'google_families' => 'Playfair+Display:wght@600;700;800&family=Lora:wght@400;500;600',
            'mood'            => __('Premium, editorial — professional services, boutique brands.', 'ajnanda'),
        ),
        'bold-display' => array(
            'label'           => __('Bold Display', 'ajnanda'),
            'heading_font'    => 'Bebas Neue',
            'body_font'       => 'Inter',
            'heading_stack'   => "'Bebas Neue', sans-serif",
            'body_stack'      => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            'google_families' => 'Bebas+Neue&family=Inter:wght@400;500;600;700;800',
            'mood'            => __('Loud, poster-style headings — events, music, energetic brands.', 'ajnanda'),
        ),
        'playful-rounded' => array(
            'label'           => __('Playful Rounded', 'ajnanda'),
            'heading_font'    => 'Baloo 2',
            'body_font'       => 'Nunito',
            'heading_stack'   => "'Baloo 2', sans-serif",
            'body_stack'      => "'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            'google_families' => 'Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;500;600;700',
            'mood'            => __('Soft, rounded, friendly — kids, hobby, community sites.', 'ajnanda'),
        ),
    );
}

/**
 * @return string A key from ajnanda_get_font_pairings(). Always a valid
 *                key — 'classic' if the saved theme_mod is empty/unknown.
 */
function ajnanda_get_active_font_pairing_slug() {
    $pairings = ajnanda_get_font_pairings();
    $slug     = get_theme_mod('theme_font_pairing', 'classic');
    return isset($pairings[$slug]) ? $slug : 'classic';
}

/**
 * @param array{heading_stack:string,body_stack:string} $pairing
 * @return string A `:root{...}` CSS custom-property override.
 */
function ajnanda_format_font_pairing_css(array $pairing) {
    // Not esc_attr(): these values contain quoted font names ('Bebas
    // Neue', sans-serif) and are printed inside a <style> element, not an
    // HTML attribute — esc_attr() would HTML-entity-encode the quotes
    // (&#039;) into invalid CSS, silently breaking every font-family
    // declaration. The values only ever come from the fixed array in
    // ajnanda_get_font_pairings() (never user input), so no escaping is
    // needed here — same reasoning WP core applies to its own hardcoded
    // theme.json-derived CSS output.
    return sprintf(
        ':root{--font-heading:%1$s;--font-body:%2$s;}',
        $pairing['heading_stack'],
        $pairing['body_stack']
    );
}

/**
 * @return string `:root{...}` CSS for the site's currently saved pairing.
 */
function ajnanda_get_active_font_pairing_css() {
    $pairings = ajnanda_get_font_pairings();
    return ajnanda_format_font_pairing_css($pairings[ajnanda_get_active_font_pairing_slug()]);
}

/**
 * Same idea as ajnanda_get_active_font_pairing_css(), but for any named
 * pairing regardless of what's currently saved — used by the
 * non-destructive preview system (inc/preview.php) and by Site Kits
 * (inc/site-kits.php) so a pairing can be previewed without changing the
 * site's actual typography.
 *
 * @param string $slug A key from ajnanda_get_font_pairings().
 * @return string `:root{...}` CSS, or '' for an unknown slug.
 */
function ajnanda_get_font_pairing_preset_css($slug) {
    $pairings = ajnanda_get_font_pairings();
    if (!isset($pairings[$slug])) {
        return '';
    }
    return ajnanda_format_font_pairing_css($pairings[$slug]);
}

/**
 * The Google Fonts CSS2 URL for one pairing — identical shape to the URL
 * AJNanda already hardcoded in header.php for 'classic', now built
 * dynamically for whichever pairing is active.
 *
 * @param string $slug A key from ajnanda_get_font_pairings().
 * @return string
 */
function ajnanda_get_font_pairing_google_fonts_url($slug) {
    $pairings = ajnanda_get_font_pairings();
    if (!isset($pairings[$slug])) {
        $slug = 'classic';
    }
    return 'https://fonts.googleapis.com/css2?family=' . $pairings[$slug]['google_families'] . '&display=swap';
}

/**
 * The Google Fonts URL for the site's currently saved pairing — what
 * header.php loads on every real frontend request.
 *
 * @return string
 */
function ajnanda_get_active_font_pairing_google_fonts_url() {
    return ajnanda_get_font_pairing_google_fonts_url(ajnanda_get_active_font_pairing_slug());
}

/**
 * The union of every registered pairing's Google Fonts URL, one <link>
 * each. Used only in the block editor (enqueue_block_editor_assets) and
 * the non-destructive preview route — not the real frontend — so that
 * manually picking any theme.json font family in the block Typography
 * panel, or previewing any pairing/kit, always has its font file
 * available, without loading 5 stylesheets' worth of weight for every
 * real site visitor (the frontend only ever loads the one active pairing).
 *
 * @return string[]
 */
function ajnanda_get_all_font_pairing_google_fonts_urls() {
    $urls = array();
    foreach (array_keys(ajnanda_get_font_pairings()) as $slug) {
        $urls[$slug] = ajnanda_get_font_pairing_google_fonts_url($slug);
    }
    return $urls;
}

/* -----------------------------------------------------------------------
 * Output the active pairing's CSS vars on the frontend. Separate hook from
 * ajnanda_customizer_css() (functions.php) rather than editing that
 * ~1230-line function — same reasoning as color-schemes.php staying its
 * own file instead of growing the existing Customizer callback.
 * ------------------------------------------------------------------- */

function ajnanda_output_active_font_pairing_css() {
    echo '<style id="ajnanda-font-pairing-css">' . ajnanda_get_active_font_pairing_css() . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
// Default priority (10) — matters: WP core prints enqueued stylesheets
// (style.css's own :root defaults) on wp_head at priority 8, via
// wp_print_styles(). This must run *after* that so the override actually
// wins the cascade instead of being clobbered by style.css's hardcoded
// fallback — same priority ajnanda_customizer_css() already uses in
// functions.php for the equivalent color override, for the same reason.
add_action('wp_head', 'ajnanda_output_active_font_pairing_css');

/* -----------------------------------------------------------------------
 * Close the same editor/iframe gap color-schemes.php closes for color:
 * push the active pairing's CSS vars into the classic editor stylesheet
 * and the iframed editor/pattern previews, and load every pairing's
 * Google Fonts so any font choice (manual or previewed) actually renders.
 * ------------------------------------------------------------------- */

function ajnanda_enqueue_font_pairing_editor_assets() {
    foreach (ajnanda_get_all_font_pairing_google_fonts_urls() as $slug => $url) {
        wp_enqueue_style('ajnanda-font-pairing-' . $slug, $url, array(), null);
    }

    if (wp_style_is('ajnanda-pro-editor-style', 'registered')) {
        wp_add_inline_style('ajnanda-pro-editor-style', ajnanda_get_active_font_pairing_css());
    }
}
add_action('enqueue_block_editor_assets', 'ajnanda_enqueue_font_pairing_editor_assets', 20);

function ajnanda_add_font_pairing_to_iframed_editor($settings) {
    if (!isset($settings['styles']) || !is_array($settings['styles'])) {
        $settings['styles'] = array();
    }
    $settings['styles'][] = array('css' => ajnanda_get_active_font_pairing_css());
    return $settings;
}
add_filter('block_editor_settings_all', 'ajnanda_add_font_pairing_to_iframed_editor');

/* -----------------------------------------------------------------------
 * A real, new Customizer setting (unlike the color presets, which just
 * quick-fill 4 already-existing settings) — theme_font_pairing is the
 * thing itself, so this is a normal radio-card control bound straight to
 * it via $this->get_link(), no custom JS needed for save behavior.
 * ------------------------------------------------------------------- */

function ajnanda_sanitize_font_pairing_slug($slug) {
    $pairings = ajnanda_get_font_pairings();
    return isset($pairings[$slug]) ? $slug : 'classic';
}

function ajnanda_register_font_pairing_control($wp_customize) {
    // WP_Customize_Control/Manager only exist on customize.php requests —
    // same guard as ajnanda_register_color_preset_control().
    if (!class_exists('WP_Customize_Control')) {
        return;
    }

    $wp_customize->add_section('ajnanda_typography', array(
        'title'    => __('Typography', 'ajnanda'),
        'priority' => 35, // near the native Colors section.
    ));

    $wp_customize->add_setting('theme_font_pairing', array(
        'default'           => 'classic',
        'sanitize_callback' => 'ajnanda_sanitize_font_pairing_slug',
        'transport'         => 'refresh',
    ));

    if (!class_exists('AJNanda_Font_Pairing_Control')) {
        class AJNanda_Font_Pairing_Control extends WP_Customize_Control {
            public $type = 'ajnanda_font_pairing';

            public function render_content() {
                $value = $this->value();
                ?>
                <span class="customize-control-title"><?php esc_html_e('Font Pairing', 'ajnanda'); ?></span>
                <p class="description"><?php esc_html_e('Sets the site-wide heading and body fonts. Every registered font is also available per-block in the editor\'s Typography panel.', 'ajnanda'); ?></p>
                <div class="ajnanda-font-pairing-options">
                    <?php foreach (ajnanda_get_font_pairings() as $slug => $pairing) : ?>
                        <label class="ajnanda-font-pairing-option">
                            <input type="radio" <?php $this->link(); ?> value="<?php echo esc_attr($slug); ?>" <?php checked($value, $slug); ?>>
                            <span class="ajnanda-font-pairing-preview" style="font-family:<?php echo esc_attr($pairing['heading_stack']); ?>;">
                                <?php echo esc_html($pairing['label']); ?>
                            </span>
                            <span class="description"><?php echo esc_html($pairing['mood']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php
            }
        }
    }

    $wp_customize->add_control(new AJNanda_Font_Pairing_Control(
        $wp_customize,
        'theme_font_pairing',
        array('section' => 'ajnanda_typography')
    ));
}
add_action('customize_register', 'ajnanda_register_font_pairing_control');

function ajnanda_font_pairing_control_assets() {
    wp_add_inline_style('customize-controls', '
        .ajnanda-font-pairing-options { display: flex; flex-direction: column; gap: 10px; margin: 10px 0; }
        .ajnanda-font-pairing-option { display: block; padding: 10px 12px; border: 1px solid #dcdcde; border-radius: 8px; cursor: pointer; }
        .ajnanda-font-pairing-option:hover, .ajnanda-font-pairing-option:has(input:checked) { border-color: #2563eb; background: #eff6ff; }
        .ajnanda-font-pairing-option input { margin-right: 8px; }
        .ajnanda-font-pairing-preview { font-size: 15px; font-weight: 600; }
        .ajnanda-font-pairing-option .description { display: block; margin: 2px 0 0 22px; font-size: 12px; }
    ');
}
add_action('customize_controls_enqueue_scripts', 'ajnanda_font_pairing_control_assets');
