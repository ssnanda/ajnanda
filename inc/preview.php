<?php
/**
 * AJNanda Preview — non-destructive visual preview for Section Patterns/
 * Page Designs (optionally in any Color Scheme) and, by extension, every
 * page inside a Starter Site (each one is just a page_design slug).
 *
 * Deliberately reuses the existing registries rather than introducing a
 * parallel preview/design system:
 *   - Content comes from ajnanda_get_pattern_content() (inc/page-designs.php),
 *     the exact same function the real importer/insert path uses.
 *   - Color overrides come from ajnanda_get_color_scheme_preset_css()
 *     (inc/color-schemes.php), the exact same 20-preset registry the
 *     Customizer swatches and Page Library picker use.
 *   - Page chrome comes from the theme's own get_header()/page.php/
 *     get_footer() — the same rendering path a real page uses — driven by
 *     an in-memory WP_Post that is never inserted into the database.
 *
 * Nothing here calls wp_insert_post(), update_option(), or set_theme_mod().
 * A preview request cannot change the site.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Build a nonce-protected preview URL for a registered pattern (a Section
 * Pattern, a Page Design, or — since Starter Site manifest pages are just
 * page_design references — any Starter Site page).
 *
 * @param string $pattern_slug  A slug registered in WP_Block_Patterns_Registry.
 * @param string $color_scheme  Optional color scheme slug (see ajnanda_get_color_schemes()).
 *                                Omit to preview with the site's real, current colors.
 * @param string $font_pairing    Optional font pairing slug (see ajnanda_get_font_pairings()).
 *                                  Omit to preview with the site's real, current fonts. Combine
 *                                  with $color_scheme to preview a full Site Kit (inc/site-kits.php).
 * @param array|null $starter_context  Optional array{starter:string,page_key:string}. When set,
 *                                       the rendered preview gets a connected click-through nav
 *                                       listing every other page in that Starter Site — see
 *                                       ajnanda_get_starter_preview_url(), the normal way to build
 *                                       this rather than passing it by hand.
 * @return string
 */
function ajnanda_get_preview_url($pattern_slug, $color_scheme = '', $font_pairing = '', $starter_context = null) {
    $args = array(
        'action' => 'ajnanda_preview',
        'slug'   => $pattern_slug,
    );
    if ($color_scheme) {
        $args['scheme'] = $color_scheme;
    }
    if ($font_pairing) {
        $args['font'] = $font_pairing;
    }
    if (is_array($starter_context) && !empty($starter_context['starter']) && !empty($starter_context['page_key'])) {
        $args['starter']  = $starter_context['starter'];
        $args['page_key'] = $starter_context['page_key'];
    }
    return wp_nonce_url(
        add_query_arg($args, admin_url('admin-post.php')),
        'ajnanda_preview_' . $pattern_slug
    );
}

/**
 * Build the preview URL for one page within a Starter Site, pre-wired
 * into the connected click-through nav (every page in the starter links
 * to every other one while previewing). Omit $page_key to start on the
 * starter's home page.
 *
 * @param string $starter_slug  A slug from AJNanda_Starter_Sites::get_all().
 * @param string $page_key      A page 'key' from that starter's manifest, or '' for its home page.
 * @param string $color_scheme  Optional color scheme slug, carried to every page as you click through.
 * @param string $font_pairing  Optional font pairing slug, carried to every page as you click through.
 * @return string|null Null if the starter slug or page key doesn't exist.
 */
function ajnanda_get_starter_preview_url($starter_slug, $page_key = '', $color_scheme = '', $font_pairing = '') {
    if (!class_exists('AJNanda_Starter_Sites')) {
        return null;
    }

    $manifest = AJNanda_Starter_Sites::get($starter_slug);
    if (!$manifest || empty($manifest['pages'])) {
        return null;
    }

    if (!$page_key) {
        $page_key = !empty($manifest['home_page_key'])
            ? $manifest['home_page_key']
            : (isset($manifest['pages'][0]['key']) ? $manifest['pages'][0]['key'] : '');
    }

    foreach ($manifest['pages'] as $page) {
        if ($page['key'] === $page_key) {
            return ajnanda_get_preview_url(
                $page['page_design'],
                $color_scheme,
                $font_pairing,
                array('starter' => $starter_slug, 'page_key' => $page_key)
            );
        }
    }

    return null;
}

/**
 * Render a preview page. Reached via a "Preview" link/button from the
 * Page Library, Starter Sites, or Color Schemes admin screens — never a
 * form submission, so this only ever reads state, it doesn't write any.
 */
function ajnanda_handle_preview_request() {
    if (!current_user_can('edit_theme_options')) {
        wp_die(esc_html__('You do not have permission to preview AJNanda content.', 'ajnanda'), '', array('response' => 403));
    }

    $slug = isset($_GET['slug']) ? sanitize_text_field(wp_unslash($_GET['slug'])) : '';

    if (!$slug || !isset($_GET['_wpnonce']) || !wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'ajnanda_preview_' . $slug)) {
        wp_die(esc_html__('This preview link has expired. Go back and open it again.', 'ajnanda'));
    }

    if (!class_exists('WP_Block_Patterns_Registry') || !WP_Block_Patterns_Registry::get_instance()->is_registered($slug)) {
        wp_die(esc_html__('Unknown pattern — it may have been renamed or removed.', 'ajnanda'));
    }

    // The admin toolbar's "Edit Page" node building (wp_admin_bar_edit_menu(),
    // wp-includes/admin-bar.php) does `if ( is_admin() ) { $current_screen =
    // get_current_screen(); ...->base }`. is_admin() is true for any
    // /wp-admin/ request including this one, but admin-post.php never calls
    // set_current_screen() the way a real admin *page* load does, so
    // get_current_screen() returns null there and that code throws a
    // "read property on null" warning once the toolbar renders in
    // wp_footer() later in this request. A lightweight 'front' screen
    // (matching neither the 'post' nor 'edit' branches it checks) is the
    // targeted fix — confirmed against the actual core source, not assumed.
    if (function_exists('set_current_screen')) {
        set_current_screen('front');
    }

    $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered($slug);
    $title   = isset($pattern['title']) ? $pattern['title'] : $slug;
    $content = ajnanda_get_pattern_content($slug);

    $schemes     = function_exists('ajnanda_get_color_schemes') ? ajnanda_get_color_schemes() : array();
    $scheme_slug = isset($_GET['scheme']) ? sanitize_key(wp_unslash($_GET['scheme'])) : '';
    if ($scheme_slug && !isset($schemes[$scheme_slug])) {
        $scheme_slug = '';
    }

    // Font override: unlike color (4 separate settings, so a body-class +
    // CSS-class override), font pairing is a single theme_mod, so
    // filtering it directly is simpler and automatically flows through to
    // every place that reads it (header.php's font <link>, the wp_head
    // CSS var output) — no separate preview-only code path needed there.
    $pairings     = function_exists('ajnanda_get_font_pairings') ? ajnanda_get_font_pairings() : array();
    $pairing_slug = isset($_GET['font']) ? sanitize_key(wp_unslash($_GET['font'])) : '';
    if ($pairing_slug && isset($pairings[$pairing_slug])) {
        add_filter('theme_mod_theme_font_pairing', function () use ($pairing_slug) {
            return $pairing_slug;
        });
    } else {
        $pairing_slug = '';
    }

    // Connected click-through nav: if this preview was reached via a
    // Starter Site's page (ajnanda_get_starter_preview_url()), build a
    // link to every other page in that same starter, carrying the same
    // scheme/font overrides forward — lets someone click Home → Music →
    // Shows → About like a real visitor before anything is imported.
    $starter_slug     = isset($_GET['starter']) ? sanitize_key(wp_unslash($_GET['starter'])) : '';
    $starter_page_key = isset($_GET['page_key']) ? sanitize_key(wp_unslash($_GET['page_key'])) : '';
    $starter_manifest = ($starter_slug && class_exists('AJNanda_Starter_Sites')) ? AJNanda_Starter_Sites::get($starter_slug) : null;
    $starter_nav       = array();

    if ($starter_manifest) {
        foreach ($starter_manifest['pages'] as $starter_page) {
            $starter_nav[] = array(
                'title'  => $starter_page['title'],
                'active' => $starter_page['key'] === $starter_page_key,
                'url'    => ajnanda_get_preview_url(
                    $starter_page['page_design'],
                    $scheme_slug,
                    $pairing_slug,
                    array('starter' => $starter_slug, 'page_key' => $starter_page['key'])
                ),
            );
        }
    }

    if ($scheme_slug) {
        // Reuses the exact same .ajnanda-scheme-{slug} classes style.css
        // already defines for the Page Library's per-page override — a
        // body class is all that's needed, no new CSS is generated here.
        add_filter('body_class', function ($classes) use ($scheme_slug) {
            $classes[] = 'ajnanda-preview';
            $classes[] = 'ajnanda-scheme-' . $scheme_slug;
            return $classes;
        });
    } else {
        add_filter('body_class', function ($classes) {
            $classes[] = 'ajnanda-preview';
            return $classes;
        });
    }

    $fake_post = new WP_Post((object) array(
        'ID'                    => 0,
        'post_author'           => get_current_user_id(),
        'post_date'             => current_time('mysql'),
        'post_date_gmt'         => current_time('mysql', true),
        'post_content'          => $content,
        'post_title'            => $title,
        'post_excerpt'          => '',
        'post_status'           => 'publish',
        'comment_status'        => 'closed',
        'ping_status'           => 'closed',
        'post_password'         => '',
        'post_name'             => 'ajnanda-preview',
        'post_type'             => 'page',
        'post_mime_type'        => '',
        'comment_count'         => 0,
        'filter'                => 'raw',
    ));

    // WP_Query::the_post() (run inside page.php's have_posts()/the_post()
    // loop) reads query_vars['update_post_term_cache'/'update_post_meta_cache']
    // directly — normally defaulted inside WP_Query::get_posts(), which we
    // never call here since the post list is hand-built, not queried.
    // fill_query_vars() doesn't cover these two, so they're set explicitly.
    global $wp_query, $wp_the_query, $post;
    $wp_query = new WP_Query();
    $wp_query->query_vars = $wp_query->fill_query_vars(array('page_id' => 0));
    $wp_query->query_vars['update_post_term_cache'] = true;
    $wp_query->query_vars['update_post_meta_cache'] = true;
    $wp_query->post          = $fake_post;
    $wp_query->posts         = array($fake_post);
    $wp_query->post_count    = 1;
    $wp_query->current_post  = -1;
    $wp_query->found_posts   = 1;
    $wp_query->max_num_pages = 0;
    $wp_query->is_page       = true;
    $wp_query->is_singular   = true;
    $wp_query->queried_object    = $fake_post;
    $wp_query->queried_object_id = 0;
    // admin-post.php requests never run WP::main(), which is what
    // normally points $wp_the_query (the "main query" WordPress core code
    // like the admin bar reads directly) at the same object as $wp_query.
    // Without this, core code reading $wp_the_query sees the untouched,
    // empty default query instead of our populated one.
    $wp_the_query = $wp_query;
    $post = $fake_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride

    // page.php calls get_header()/get_footer() and its own have_posts()
    // loop itself — reuse it wholesale (same builder-canvas detection,
    // same wrappers a real page gets) rather than re-implementing a
    // simplified version of it here. The banner is injected via the
    // wp_body_open hook, header.php's own extension point for exactly
    // this kind of "right after <body>" insertion, instead of calling
    // get_header() a second time.
    add_action('wp_body_open', function () use ($title, $scheme_slug, $schemes, $pairing_slug, $pairings, $starter_manifest, $starter_nav) {
        $back_url = admin_url($starter_manifest ? 'admin.php?page=ajnanda-starter-sites' : 'admin.php?page=ajnanda');
        ?>
        <div style="position:sticky;top:0;z-index:99999;background:#111827;color:#fff;font:600 13px/1.4 -apple-system,BlinkMacSystemFont,sans-serif;">
            <div style="padding:10px 20px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;">
                <span>
                    🔍 <?php esc_html_e('AJNanda Preview', 'ajnanda'); ?> —
                    <?php if ($starter_manifest) : ?>
                        <?php echo esc_html($starter_manifest['label']); ?> — <?php echo esc_html($title); ?>
                    <?php else : ?>
                        <?php echo esc_html($title); ?>
                    <?php endif; ?>
                    <?php if ($scheme_slug) : ?> — <?php echo esc_html($schemes[$scheme_slug]['label']); ?> <?php esc_html_e('colors', 'ajnanda'); ?><?php endif; ?>
                    <?php if ($pairing_slug) : ?> — <?php echo esc_html($pairings[$pairing_slug]['label']); ?> <?php esc_html_e('fonts', 'ajnanda'); ?><?php endif; ?>
                    — <?php esc_html_e('nothing here is saved', 'ajnanda'); ?>
                </span>
                <a href="<?php echo esc_url($back_url); ?>" style="color:#fff;">← <?php esc_html_e('Back to AJNanda', 'ajnanda'); ?></a>
            </div>
            <?php if (!empty($starter_nav)) : ?>
                <div style="padding:6px 20px 10px;display:flex;flex-wrap:wrap;gap:8px;border-top:1px solid rgba(255,255,255,0.15);">
                    <?php foreach ($starter_nav as $nav_page) : ?>
                        <a
                            href="<?php echo esc_url($nav_page['url']); ?>"
                            style="text-decoration:none;font-weight:700;padding:4px 12px;border-radius:999px;<?php echo $nav_page['active'] ? 'color:#111827;background:#fbbf24;' : 'color:#fff;background:rgba(255,255,255,0.12);'; ?>"
                        ><?php echo esc_html($nav_page['title']); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    });

    include get_theme_file_path('page.php');

    wp_reset_postdata();
    exit;
}
add_action('admin_post_ajnanda_preview', 'ajnanda_handle_preview_request');
add_action('admin_post_nopriv_ajnanda_preview', function () {
    wp_die(esc_html__('You must be logged in to preview AJNanda content.', 'ajnanda'), '', array('response' => 401));
});
