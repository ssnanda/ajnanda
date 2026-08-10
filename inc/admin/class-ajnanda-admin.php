<?php
/**
 * AJNanda admin: top-level "AJNanda" menu, Starter Sites import handling,
 * and Page Library "insert as page" handling.
 *
 * Visual style intentionally mirrors the existing Appearance → Update
 * AJNanda screen (inc/github-theme-updater.php) — gradient hero card, card
 * grid, 14-16px radii — so the whole AJNanda admin area feels like one
 * product. The Theme Settings submenu links out to that existing screen
 * and to the Customizer rather than re-registering/rewriting it, so the
 * delicate "direct update" sidebar-link behavior it already implements is
 * left completely untouched.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJNanda_Admin {

    const CAPABILITY = 'manage_options';
    const NONCE_ACTION = 'ajnanda_admin_action';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('admin_post_ajnanda_import_starter', array(__CLASS__, 'handle_import_starter'));
        add_action('admin_post_ajnanda_insert_page_design', array(__CLASS__, 'handle_insert_page_design'));
    }

    public static function register_menu() {
        add_menu_page(
            __('AJNanda', 'ajnanda'),
            __('AJNanda', 'ajnanda'),
            self::CAPABILITY,
            'ajnanda',
            array(__CLASS__, 'render_overview'),
            'dashicons-layout',
            59
        );

        add_submenu_page('ajnanda', __('Overview', 'ajnanda'), __('Overview', 'ajnanda'), self::CAPABILITY, 'ajnanda', array(__CLASS__, 'render_overview'));
        add_submenu_page('ajnanda', __('Starter Sites', 'ajnanda'), __('Starter Sites', 'ajnanda'), self::CAPABILITY, 'ajnanda-starter-sites', array(__CLASS__, 'render_starter_sites'));
        add_submenu_page('ajnanda', __('Page Library', 'ajnanda'), __('Page Library', 'ajnanda'), self::CAPABILITY, 'ajnanda-page-library', array(__CLASS__, 'render_page_library'));
        add_submenu_page('ajnanda', __('Patterns', 'ajnanda'), __('Patterns', 'ajnanda'), self::CAPABILITY, 'ajnanda-patterns', array(__CLASS__, 'render_patterns'));
        add_submenu_page('ajnanda', __('Color Schemes', 'ajnanda'), __('Color Schemes', 'ajnanda'), self::CAPABILITY, 'ajnanda-color-schemes', array(__CLASS__, 'render_color_schemes'));
        add_submenu_page('ajnanda', __('Site Kits', 'ajnanda'), __('Site Kits', 'ajnanda'), self::CAPABILITY, 'ajnanda-site-kits', array(__CLASS__, 'render_site_kits'));
        add_submenu_page('ajnanda', __('Theme Settings', 'ajnanda'), __('Theme Settings', 'ajnanda'), self::CAPABILITY, 'ajnanda-settings', array(__CLASS__, 'render_settings'));
    }

    /**
     * Only load the shared admin stylesheet on AJNanda's own screens.
     */
    public static function enqueue_assets($hook_suffix) {
        if (empty($_GET['page']) || strpos((string) $_GET['page'], 'ajnanda') !== 0) {
            return;
        }

        wp_enqueue_style(
            'ajnanda-admin',
            get_template_directory_uri() . '/inc/admin/assets/admin.css',
            array(),
            ajnanda_asset_version('inc/admin/assets/admin.css')
        );

        // Shared vanilla-JS behavior for every AJNanda admin screen: turns
        // any .ajnanda-preview-link into an in-page modal instead of a new
        // tab, and powers the live text filters on Page Library/Patterns.
        // No build step, no dependency — matches the inline-<script> style
        // already used on individual views, just shared instead of repeated.
        wp_enqueue_script(
            'ajnanda-admin',
            get_template_directory_uri() . '/inc/admin/assets/admin.js',
            array(),
            ajnanda_asset_version('inc/admin/assets/admin.js'),
            true
        );
    }

    private static function view($file, array $vars = array()) {
        extract($vars);
        include get_template_directory() . '/inc/admin/views/' . $file . '.php';
    }

    public static function render_overview() {
        self::view('overview', array(
            'patterns_count'      => count(self::all_section_patterns()),
            'page_designs_count'  => count(function_exists('ajnanda_get_page_designs') ? ajnanda_get_page_designs() : array()),
            'starter_sites_count' => count(AJNanda_Starter_Sites::get_all()),
            'site_status'         => self::get_site_status(),
        ));
    }

    public static function render_starter_sites() {
        $preview = null;
        $preview_slug = isset($_GET['ajnanda_preview']) ? sanitize_key(wp_unslash($_GET['ajnanda_preview'])) : '';
        if ($preview_slug) {
            $preview = array('slug' => $preview_slug, 'report' => AJNanda_Starter_Importer::preview($preview_slug));
        }

        // Compact "already imported" badges shown inline on every starter's
        // page list by default, so that's answered without an extra click —
        // the explicit "Preview Import" button still exists for the full
        // create/skip/conflict table when someone wants the detail.
        $reports = array();
        foreach (AJNanda_Starter_Sites::get_all() as $slug => $starter) {
            $reports[$slug] = AJNanda_Starter_Importer::preview($slug);
        }

        self::view('starter-sites', array(
            'starters' => AJNanda_Starter_Sites::get_all(),
            'reports'  => $reports,
            'preview'  => $preview,
            'notice'   => self::consume_notice('ajnanda_import_result'),
        ));
    }

    public static function render_page_library() {
        self::view('page-library', array(
            'designs' => self::all_page_designs(),
            'notice'  => self::consume_notice('ajnanda_insert_result'),
        ));
    }

    public static function render_patterns() {
        self::view('patterns', array(
            'patterns' => self::all_section_patterns(),
        ));
    }

    public static function render_settings() {
        self::view('settings', array());
    }

    /**
     * Representative page design used for the "Preview" link on each
     * Color Schemes swatch. Any page design can be previewed in any color
     * from the Page Library screen — this just needs one sensible default
     * so the swatch grid has something concrete to show.
     */
    const PREVIEW_DEFAULT_PAGE_DESIGN = 'ajnanda/page-home-corporate';

    public static function render_color_schemes() {
        self::view('color-schemes', array(
            'schemes'      => function_exists('ajnanda_get_color_schemes') ? ajnanda_get_color_schemes() : array(),
            'active_slug'  => function_exists('ajnanda_get_active_color_scheme_slug') ? ajnanda_get_active_color_scheme_slug() : '',
            'preview_page' => self::PREVIEW_DEFAULT_PAGE_DESIGN,
        ));
    }

    public static function render_site_kits() {
        self::view('site-kits', array(
            'kits'          => function_exists('ajnanda_get_site_kits') ? ajnanda_get_site_kits() : array(),
            'pairings'      => function_exists('ajnanda_get_font_pairings') ? ajnanda_get_font_pairings() : array(),
            'active_kit'    => function_exists('ajnanda_get_active_site_kit_slug') ? ajnanda_get_active_site_kit_slug() : '',
            'active_font'   => function_exists('ajnanda_get_active_font_pairing_slug') ? ajnanda_get_active_font_pairing_slug() : '',
            'preview_page'  => self::PREVIEW_DEFAULT_PAGE_DESIGN,
        ));
    }

    /**
     * AJNanda's own registered section patterns (ajnanda/* and the legacy
     * ajnanda-pro/* slugs) except the ones tagged as Page Designs — i.e.
     * the section library shown on the read-only Patterns screen. Core's
     * own bundled patterns (query loop variations, etc.) are deliberately
     * excluded — this screen is a reference for AJNanda's library, not
     * every pattern registered on the site.
     */
    private static function all_section_patterns() {
        if (!class_exists('WP_Block_Patterns_Registry')) {
            return array();
        }

        $patterns = array();
        // get_all_registered() returns array_values()'d entries — the
        // array key is NOT the slug (that's a common trap). The real
        // slug is $pattern['name'].
        foreach (WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern) {
            $slug = isset($pattern['name']) ? $pattern['name'] : '';
            if (!$slug || 0 !== strpos($slug, 'ajnanda')) {
                continue;
            }
            $categories = isset($pattern['categories']) ? (array) $pattern['categories'] : array();
            if (!in_array('ajnanda-page-designs', $categories, true)) {
                $patterns[$slug] = $pattern;
            }
        }
        return $patterns;
    }

    private static function all_page_designs() {
        return function_exists('ajnanda_get_page_designs') ? ajnanda_get_page_designs() : array();
    }

    /**
     * "Your site" status shown on Overview — distinct from the library
     * counts above (what's *available*), this is what's actually been
     * *done* on this install: current color scheme, whether a starter site
     * has been imported, how many pages AJNanda has built, and whether the
     * primary menu is set up. Cheap: two aggregate postmeta queries plus
     * two theme_mod reads, no per-post loading.
     *
     * @return array{scheme_label:string,pages_created:int,starter_labels:string[],primary_menu_set:bool}
     */
    private static function get_site_status() {
        global $wpdb;

        $pages_created = class_exists('AJNanda_Starter_Importer')
            ? (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                AJNanda_Starter_Importer::META_DESIGN
            ))
            : 0;

        $starter_labels = array();
        if (class_exists('AJNanda_Starter_Importer')) {
            $imported_slugs = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
                AJNanda_Starter_Importer::META_STARTER
            ));
            $all_starters = AJNanda_Starter_Sites::get_all();
            foreach ($imported_slugs as $imported_slug) {
                if (isset($all_starters[$imported_slug])) {
                    $starter_labels[] = $all_starters[$imported_slug]['label'];
                }
            }
        }

        $locations = get_nav_menu_locations();

        $scheme_slug  = function_exists('ajnanda_get_active_color_scheme_slug') ? ajnanda_get_active_color_scheme_slug() : '';
        $schemes      = function_exists('ajnanda_get_color_schemes') ? ajnanda_get_color_schemes() : array();
        $scheme_label = isset($schemes[$scheme_slug]) ? $schemes[$scheme_slug]['label'] : __('Custom', 'ajnanda');

        $kit_slug  = function_exists('ajnanda_get_active_site_kit_slug') ? ajnanda_get_active_site_kit_slug() : '';
        $kits      = function_exists('ajnanda_get_site_kits') ? ajnanda_get_site_kits() : array();
        $kit_label = isset($kits[$kit_slug]) ? $kits[$kit_slug]['label'] : '';

        return array(
            'scheme_label'     => $scheme_label,
            'kit_label'        => $kit_label,
            'pages_created'    => $pages_created,
            'starter_labels'   => $starter_labels,
            'primary_menu_set' => !empty($locations['primary']),
        );
    }

    /* -----------------------------------------------------------------
     * Form handlers
     * --------------------------------------------------------------- */

    public static function handle_import_starter() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to do this.', 'ajnanda'));
        }
        check_admin_referer(self::NONCE_ACTION);

        $slug = isset($_POST['starter']) ? sanitize_key(wp_unslash($_POST['starter'])) : '';
        $pages = isset($_POST['pages']) && is_array($_POST['pages'])
            ? array_map('sanitize_key', wp_unslash($_POST['pages']))
            : 'all';

        $results = AJNanda_Starter_Importer::import($slug, array(
            'pages'          => $pages,
            'status'         => (isset($_POST['status']) && 'publish' === $_POST['status']) ? 'publish' : 'draft',
            'set_homepage'   => !empty($_POST['set_homepage']),
            'create_menu'    => !empty($_POST['create_menu']),
            'overwrite_menu' => !empty($_POST['overwrite_menu']),
        ));

        self::store_notice('ajnanda_import_result', array('slug' => $slug, 'results' => $results));

        wp_safe_redirect(add_query_arg(array('page' => 'ajnanda-starter-sites', 'ajnanda_imported' => 1), admin_url('admin.php')));
        exit;
    }

    public static function handle_insert_page_design() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to do this.', 'ajnanda'));
        }
        check_admin_referer(self::NONCE_ACTION);

        $slug          = isset($_POST['page_design']) ? sanitize_text_field(wp_unslash($_POST['page_design'])) : '';
        $title         = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : __('New Page', 'ajnanda');
        $status        = (isset($_POST['status']) && 'publish' === $_POST['status']) ? 'publish' : 'draft';
        $color_scheme  = isset($_POST['color_scheme']) ? sanitize_key(wp_unslash($_POST['color_scheme'])) : '';

        $post_id = ajnanda_insert_page_design($slug, $title, $status, $color_scheme);

        if (is_wp_error($post_id)) {
            self::store_notice('ajnanda_insert_result', array('error' => $post_id->get_error_message()));
            wp_safe_redirect(add_query_arg('page', 'ajnanda-page-library', admin_url('admin.php')));
            exit;
        }

        wp_safe_redirect(get_edit_post_link($post_id, 'redirect'));
        exit;
    }

    /* -----------------------------------------------------------------
     * Tiny transient-backed "flash notice" helper — avoids stuffing
     * import results into the URL, keeps redirects clean.
     * --------------------------------------------------------------- */

    private static function store_notice($key, $data) {
        set_transient($key . '_' . get_current_user_id(), $data, MINUTE_IN_SECONDS * 5);
    }

    private static function consume_notice($key) {
        $transient_key = $key . '_' . get_current_user_id();
        $data = get_transient($transient_key);
        if (false !== $data) {
            delete_transient($transient_key);
        }
        return $data ?: null;
    }
}
AJNanda_Admin::init();
