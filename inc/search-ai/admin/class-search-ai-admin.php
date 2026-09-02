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
        $ownership = array();
        foreach (AJNanda_Search_AI_Capability_Ownership::capabilities() as $capability) {
            $ownership[$capability] = AJNanda_Search_AI_Capability_Ownership::get($capability);
        }

        include get_template_directory() . '/inc/search-ai/admin/views/search-ai.php';
    }
}

