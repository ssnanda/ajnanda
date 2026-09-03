<?php
/** Discovery output helpers and diagnostics. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Discovery_Files {
    public static function important_page_ids() {
        return array_values(array_filter(array_unique(array_map('absint', (array) get_theme_mod('search_ai_llms_important_page_ids', array())))));
    }

    public static function llms_enabled() {
        return (bool) get_theme_mod('seo_llms_txt_enabled', true) && AJNanda_Search_AI_Capability_Ownership::ajnanda_owns('llms_txt');
    }

    public static function render_llms_txt() {
        $profile = AJNanda_Search_AI_Site_Profile::get();
        $lines = array('# ' . ($profile['name'] ?: wp_parse_url(home_url(), PHP_URL_HOST)), '');
        if ($profile['description']) { $lines[] = '> ' . preg_replace('/\s+/', ' ', $profile['description']); $lines[] = ''; }
        $details = array_filter(array(
            $profile['alternate_name'] ? 'Also known as: ' . $profile['alternate_name'] : '',
            $profile['industry'] ? 'Industry: ' . $profile['industry'] : '',
            $profile['service_areas'] ? 'Service areas: ' . implode(', ', $profile['service_areas']) : '',
            $profile['phone'] ? 'Phone: ' . $profile['phone'] : '',
            $profile['email'] ? 'Email: ' . $profile['email'] : '',
        ));
        if ($details) { foreach ($details as $detail) { $lines[] = $detail; } $lines[] = ''; }
        $important_ids = self::important_page_ids();
        foreach (array((int) get_option('page_on_front'), (int) get_option('page_for_posts')) as $foundational_id) {
            if ($foundational_id && ! in_array($foundational_id, $important_ids, true)) { $important_ids[] = $foundational_id; }
        }
        $important_pages = $important_ids ? get_posts(array(
            'post_type' => 'page', 'post_status' => 'publish', 'post__in' => $important_ids,
            'orderby' => 'post__in', 'numberposts' => count($important_ids),
        )) : array();
        self::append_content_section($lines, __('Important Pages', 'ajnanda'), $important_pages, 20);
        self::append_content_section($lines, __('Recent Articles', 'ajnanda'), get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 20)), 10);
        return apply_filters('ajnanda_search_ai_llms_txt', implode("\n", $lines) . "\n", $profile);
    }

    private static function append_content_section(&$lines, $heading, $posts, $limit) {
        $entries = array();
        foreach ($posts as $post) {
            $decision = AJNanda_Search_AI_Content_Policy::evaluate($post->ID);
            if (empty($decision['advertise']['llms_txt'])) { continue; }
            $title = trim(html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8'));
            if (! $title) { continue; }
            $entries[] = '- [' . str_replace(array('[', ']'), '', $title) . '](' . get_permalink($post) . ')';
            if (count($entries) >= $limit) { break; }
        }
        if ($entries) { $lines[] = '## ' . $heading; $lines = array_merge($lines, $entries, array('')); }
    }

    public static function status($probe_endpoints = false) {
        $policy = AJNanda_Search_AI_Content_Policy::settings();
        $sitemap_ownership = AJNanda_Search_AI_Capability_Ownership::get('sitemap');
        $sitemap_url = home_url('/wp-sitemap.xml');
        if (! empty($sitemap_ownership['external'])) {
            $owner = key($sitemap_ownership['external']);
            $paths = array('yoast' => '/sitemap_index.xml', 'rank_math' => '/sitemap_index.xml', 'seopress' => '/sitemaps.xml', 'aioseo' => '/sitemap.xml');
            if (isset($paths[$owner])) { $sitemap_url = home_url($paths[$owner]); }
        }
        return array(
            'sitemap' => array('url' => $sitemap_url, 'ownership' => $sitemap_ownership),
            'robots' => array(
                'url' => home_url('/robots.txt'),
                'policy_active' => true,
                'endpoint' => $probe_endpoints ? self::endpoint_status(home_url('/robots.txt'), 'robots') : null,
            ),
            'llms_txt' => array(
                'url' => home_url('/llms.txt'),
                'enabled' => self::llms_enabled(),
                'ownership' => AJNanda_Search_AI_Capability_Ownership::get('llms_txt'),
                'endpoint' => $probe_endpoints && self::llms_enabled() ? self::endpoint_status(home_url('/llms.txt'), 'llms') : null,
            ),
            'schema' => array(
                'enabled' => (bool) get_theme_mod('seo_schema_enabled', true),
                'active' => (bool) get_theme_mod('seo_schema_enabled', true) && AJNanda_Search_AI_Capability_Ownership::ajnanda_owns('schema'),
                'ownership' => AJNanda_Search_AI_Capability_Ownership::get('schema'),
            ),
            'policy_count' => count($policy['excluded_post_ids']) + count($policy['excluded_post_types']) + count($policy['excluded_paths']),
        );
    }

    public static function endpoint_status($url, $cache_suffix = '') {
        $cache_key = 'ajnanda_search_ai_endpoint_' . md5($cache_suffix . '|' . $url);
        $cached = get_transient($cache_key);
        if (is_array($cached)) { return $cached; }
        $response = wp_remote_get($url, array(
            'timeout' => 3, 'redirection' => 1, 'limit_response_size' => 2048,
            'user-agent' => 'AJNanda-Discovery-Diagnostic/1.0',
        ));
        $status = is_wp_error($response) ? array('reachable' => false, 'code' => 0, 'message' => $response->get_error_message()) : array(
            'reachable' => 200 === wp_remote_retrieve_response_code($response),
            'code' => (int) wp_remote_retrieve_response_code($response),
            'message' => '',
        );
        set_transient($cache_key, $status, 5 * MINUTE_IN_SECONDS);
        return $status;
    }
}
