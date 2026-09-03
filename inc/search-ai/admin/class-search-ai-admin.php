<?php
/**
 * Search & AI admin shell.
 *
 * @package AJNanda
 */

if (! defined('ABSPATH')) {
    exit;
}

class AJNanda_Search_AI_Admin {

    const PAGE_SLUG = 'ajnanda-search-ai';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'), 25);
        add_action('admin_menu', array(__CLASS__, 'hide_legacy_menu_items'), 99);
        add_action('admin_post_ajnanda_save_search_ai_profile', array(__CLASS__, 'save_profile'));
        add_action('admin_post_ajnanda_save_search_ai_policy', array(__CLASS__, 'save_policy'));
        add_action('admin_post_ajnanda_save_ai_discovery', array(__CLASS__, 'save_ai_discovery'));
        add_action('admin_post_ajnanda_save_llms_important_pages', array(__CLASS__, 'save_llms_important_pages'));
        add_action('admin_post_ajnanda_save_crawler_log_settings', array(__CLASS__, 'save_crawler_log_settings'));
        add_action('wp_ajax_ajnanda_search_ai_find_content', array(__CLASS__, 'find_content'));
    }

    public static function tabs() {
        return array(
            'overview'        => __('Overview', 'ajnanda'),
            'site-profile'    => __('Site Profile', 'ajnanda'),
            'seo'             => __('SEO', 'ajnanda'),
            'ai-discovery'    => __('AI Discovery', 'ajnanda'),
            'content-access'  => __('Content Access', 'ajnanda'),
            'discovery-files' => __('Discovery Files', 'ajnanda'),
            'insights'        => __('Insights', 'ajnanda'),
            'crawler-log'     => __('Crawler Log', 'ajnanda'),
            'settings'        => __('Settings', 'ajnanda'),
        );
    }

    public static function register_menu() {
        $hook = add_submenu_page(
            'ajnanda',
            __('Search & AI', 'ajnanda'),
            __('Search & AI', 'ajnanda'),
            'manage_options',
            self::PAGE_SLUG,
            array(__CLASS__, 'render')
        );
        if ($hook) {
            add_action('load-' . $hook, 'wp_enqueue_media');
        }
    }

    public static function hide_legacy_menu_items() {
        remove_submenu_page('ajnanda', 'ajnanda-seo-settings');
        remove_submenu_page('ajnanda', 'ajnanda-seo-insights');
    }

    public static function render() {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'ajnanda'));
        }

        $tabs = self::tabs();
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';
        if (! isset($tabs[$tab])) {
            $tab = 'overview';
        }

        $profile = AJNanda_Search_AI_Site_Profile::get();
        $policy = AJNanda_Search_AI_Content_Policy::settings();
        $crawler_registry = AJNanda_Search_AI_Crawler_Registry::all();
        $discovery_status = AJNanda_Search_AI_Discovery_Files::status('discovery-files' === $tab);
        $readiness = 'overview' === $tab ? AJNanda_Search_AI_Readiness::report() : array();
        $insights = 'insights' === $tab ? AJNanda_Search_AI_Insights::report() : array();
        $crawler_log = array();
        $crawler_event = null;
        if ('crawler-log' === $tab) {
            $crawler_log = AJNanda_Search_AI_Crawler_Log_Store::table_exists() ? array(
                'query' => AJNanda_Search_AI_Crawler_Log_Store::query($_GET),
                'aggregates' => AJNanda_Search_AI_Crawler_Log_Store::aggregates($_GET),
            ) : array('query' => array('rows' => array(), 'total' => 0, 'pages' => 1, 'filters' => AJNanda_Search_AI_Crawler_Log_Store::sanitize_filters($_GET)), 'aggregates' => array());
            if (! empty($_GET['event'])) { $crawler_event = AJNanda_Search_AI_Crawler_Log_Store::get(absint($_GET['event'])); }
        }
        $public_post_types = get_post_types(array('public' => true, 'show_ui' => true), 'objects');
        unset($public_post_types['attachment']);
        $selected_content = array();
        if ('content-access' === $tab) {
            if ($policy['excluded_post_ids']) {
                $selected_content = get_posts(array(
                    'post_type' => 'any',
                    'post_status' => 'publish',
                    'post__in' => $policy['excluded_post_ids'],
                    'orderby' => 'post__in',
                    'numberposts' => count($policy['excluded_post_ids']),
                ));
            }
        }
        $selected_important_pages = array();
        if ('discovery-files' === $tab) {
            $important_ids = AJNanda_Search_AI_Discovery_Files::important_page_ids();
            if ($important_ids) {
                $selected_important_pages = get_posts(array(
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post__in' => $important_ids,
                    'orderby' => 'post__in',
                    'numberposts' => count($important_ids),
                ));
            }
        }
        $ownership = array();
        foreach (AJNanda_Search_AI_Capability_Ownership::capabilities() as $capability) {
            $ownership[$capability] = AJNanda_Search_AI_Capability_Ownership::get($capability);
        }

        include get_template_directory() . '/inc/search-ai/admin/views/search-ai.php';
    }

    private static function authorize($nonce_action) {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'ajnanda'));
        }
        check_admin_referer($nonce_action);
    }

    private static function redirect($tab) {
        wp_safe_redirect(add_query_arg(array('page' => self::PAGE_SLUG, 'tab' => $tab, 'updated' => '1'), admin_url('admin.php')));
        exit;
    }

    public static function save_profile() {
        self::authorize('ajnanda_save_search_ai_profile');
        $text_fields = array(
            'search_ai_profile_name', 'search_ai_profile_alternate_name', 'search_ai_profile_industry',
            'search_ai_profile_phone', 'search_ai_profile_address_street', 'search_ai_profile_address_city',
            'search_ai_profile_address_state', 'search_ai_profile_address_postal', 'search_ai_profile_address_country',
        );
        foreach ($text_fields as $key) {
            set_theme_mod($key, sanitize_text_field(wp_unslash($_POST[$key] ?? '')));
        }
        set_theme_mod('search_ai_profile_description', sanitize_textarea_field(wp_unslash($_POST['search_ai_profile_description'] ?? '')));
        set_theme_mod('search_ai_profile_website', esc_url_raw(wp_unslash($_POST['search_ai_profile_website'] ?? '')));
        set_theme_mod('search_ai_profile_email', sanitize_email(wp_unslash($_POST['search_ai_profile_email'] ?? '')));
        set_theme_mod('search_ai_profile_logo_id', absint($_POST['search_ai_profile_logo_id'] ?? 0));

        $types = AJNanda_Search_AI_Site_Profile::organization_types();
        $type = sanitize_text_field(wp_unslash($_POST['search_ai_profile_organization_type'] ?? 'Organization'));
        set_theme_mod('search_ai_profile_organization_type', isset($types[$type]) ? $type : 'Organization');
        $location_modes = array('physical', 'service_area', 'regional_national', 'none');
        $mode = sanitize_key(wp_unslash($_POST['search_ai_profile_location_mode'] ?? 'none'));
        set_theme_mod('search_ai_profile_location_mode', in_array($mode, $location_modes, true) ? $mode : 'none');

        $submitted_records=(array)wp_unslash($_POST['search_ai_service_area_records']??array());
        $records=AJNanda_Search_AI_Service_Area_Registry::sanitize_records($submitted_records);
        $default_ids=array();
        foreach($submitted_records as $record)if(!empty($record['default'])&&!empty($record['id']))$default_ids[]=sanitize_key($record['id']);
        $valid_ids=wp_list_pluck($records,'id');
        $default_ids=array_values(array_intersect(AJNanda_Search_AI_Service_Area_Registry::sanitize_ids($default_ids),$valid_ids));
        set_theme_mod(AJNanda_Search_AI_Service_Area_Registry::RECORDS_MOD,$records);
        set_theme_mod(AJNanda_Search_AI_Service_Area_Registry::DEFAULT_IDS_MOD,$default_ids);
        set_theme_mod('search_ai_profile_service_areas',AJNanda_Search_AI_Service_Area_Registry::public_names(AJNanda_Search_AI_Service_Area_Registry::select($default_ids)));
        set_theme_mod('search_ai_profile_identity_urls', self::sanitize_url_lines($_POST['search_ai_profile_identity_urls'] ?? ''));

        // Keep legacy consumers supplied until the schema layer moves to Site Profile.
        set_theme_mod('seo_business_phone', get_theme_mod('search_ai_profile_phone', ''));
        if ('physical' === $mode) {
            $address_parts = array(
                get_theme_mod('search_ai_profile_address_street', ''),
                get_theme_mod('search_ai_profile_address_city', ''),
                get_theme_mod('search_ai_profile_address_state', ''),
                get_theme_mod('search_ai_profile_address_postal', ''),
                get_theme_mod('search_ai_profile_address_country', ''),
            );
            set_theme_mod('seo_business_address', implode(', ', array_filter($address_parts)));
        } else {
            set_theme_mod('seo_business_address', '');
        }
        self::redirect('site-profile');
    }

    public static function save_policy() {
        self::authorize('ajnanda_save_search_ai_policy');
        $post_ids = array_map('absint', (array) ($_POST['search_ai_excluded_post_ids'] ?? array()));
        set_theme_mod('search_ai_excluded_post_ids', array_values(array_filter(array_unique($post_ids))));

        $allowed_types = get_post_types(array('public' => true), 'names');
        $post_types = array_map('sanitize_key', (array) ($_POST['search_ai_excluded_post_types'] ?? array()));
        set_theme_mod('search_ai_excluded_post_types', array_values(array_intersect($post_types, $allowed_types)));

        set_theme_mod('search_ai_excluded_paths', AJNanda_Search_AI_Content_Policy::normalize_paths(self::sanitize_lines($_POST['search_ai_excluded_paths'] ?? '')));
        if (isset($_POST['search_ai_default_exclusion'])) {
            $effects = AJNanda_Search_AI_Content_Policy::default_effects();
        } else {
            $effects = array();
            foreach (AJNanda_Search_AI_Content_Policy::default_effects() as $key => $default) {
                $effects[$key] = isset($_POST['search_ai_exclusion_effects'][$key]);
            }
        }
        set_theme_mod('search_ai_exclusion_effects', $effects);
        self::redirect('content-access');
    }

    public static function save_ai_discovery() {
        self::authorize('ajnanda_save_ai_discovery');
        $ai_search = isset($_POST['search_ai_allow_ai_search']);
        $ai_training = isset($_POST['search_ai_allow_ai_training']);
        AJNanda_Search_AI_Settings::set('search_ai_allow_ai_search', $ai_search);
        AJNanda_Search_AI_Settings::set('search_ai_allow_ai_training', $ai_training);
        AJNanda_Search_AI_Settings::set('search_ai_allow_user_retrieval', isset($_POST['search_ai_allow_user_retrieval']));
        // Preserve the best possible value for legacy integrations with only one combined switch.
        set_theme_mod('seo_allow_ai_crawlers', $ai_search || $ai_training);
        self::redirect('ai-discovery');
    }

    public static function save_llms_important_pages() {
        self::authorize('ajnanda_save_llms_important_pages');
        $ids = array_values(array_filter(array_unique(array_map('absint', (array) ($_POST['search_ai_llms_important_page_ids'] ?? array())))));
        $valid_ids = array();
        foreach ($ids as $id) {
            if ('page' === get_post_type($id) && 'publish' === get_post_status($id)) { $valid_ids[] = $id; }
        }
        set_theme_mod('search_ai_llms_important_page_ids', $valid_ids);
        self::redirect('discovery-files');
    }

    public static function save_crawler_log_settings() {
        self::authorize('ajnanda_save_crawler_log_settings');
        AJNanda_Search_AI_Settings::set('search_ai_crawler_logging_enabled', isset($_POST['search_ai_crawler_logging_enabled']));
        $retention = absint($_POST['search_ai_log_retention_days'] ?? 90);
        AJNanda_Search_AI_Settings::set('search_ai_log_retention_days', in_array($retention, array(7, 30, 90, 180, 365), true) ? $retention : 90);
        $ip_mode = sanitize_key(wp_unslash($_POST['search_ai_crawler_ip_mode'] ?? 'anonymized'));
        AJNanda_Search_AI_Settings::set('search_ai_crawler_ip_mode', in_array($ip_mode, array('anonymized', 'hashed', 'full'), true) ? $ip_mode : 'anonymized');
        AJNanda_Search_AI_Crawler_Log_Store::ensure_schedules();
        self::redirect('settings');
    }

    private static function sanitize_lines($value) {
        $lines = preg_split('/\r\n|\r|\n/', wp_unslash((string) $value));
        return array_values(array_filter(array_map('sanitize_text_field', $lines)));
    }

    private static function sanitize_url_lines($value) {
        return array_values(array_filter(array_map('esc_url_raw', self::sanitize_lines($value))));
    }

    public static function find_content() {
        self::authorize('ajnanda_search_ai_find_content');
        $search = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        if (strlen($search) < 2) {
            wp_send_json_success(array());
        }
        $requested_type = sanitize_key(wp_unslash($_GET['post_type'] ?? ''));
        if ('page' === $requested_type) {
            $types = array('page');
        } else {
            $types = get_post_types(array('public' => true, 'show_ui' => true), 'names');
            unset($types['attachment']);
        }
        $posts = get_posts(array(
            'post_type' => array_values($types),
            'post_status' => 'publish',
            's' => $search,
            'numberposts' => 20,
            'orderby' => 'relevance',
            'suppress_filters' => false,
        ));
        $results = array();
        foreach ($posts as $post) {
            $type = get_post_type_object($post->post_type);
            $results[] = array(
                'id' => (int) $post->ID,
                'title' => get_the_title($post) ?: __('(no title)', 'ajnanda'),
                'type' => $type ? $type->labels->singular_name : $post->post_type,
                'url' => get_permalink($post),
            );
        }
        wp_send_json_success($results);
    }
}
