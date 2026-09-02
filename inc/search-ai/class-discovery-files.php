<?php
/** Discovery output helpers and diagnostics. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Discovery_Files {
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
        self::append_content_section($lines, __('Important Pages', 'ajnanda'), get_pages(array('sort_column' => 'menu_order,post_title', 'number' => 30)), 20);
        self::append_content_section($lines, __('Recent Articles', 'ajnanda'), get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 20)), 10);
        return apply_filters('ajnanda_search_ai_llms_txt', implode("\n", $lines) . "\n", $profile);
    }

    private static function append_content_section(&$lines, $heading, $posts, $limit) {
        $entries = array();
        foreach ($posts as $post) {
            $decision = AJNanda_Search_AI_Content_Policy::evaluate($post->ID);
            if (empty($decision['advertise']['llms_txt'])) { continue; }
            $title = trim(wp_specialchars_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES));
            if (! $title) { continue; }
            $entries[] = '- [' . str_replace(array('[', ']'), '', $title) . '](' . get_permalink($post) . ')';
            if (count($entries) >= $limit) { break; }
        }
        if ($entries) { $lines[] = '## ' . $heading; $lines = array_merge($lines, $entries, array('')); }
    }

    public static function status() {
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
            'robots' => array('url' => home_url('/robots.txt'), 'policy_active' => true),
            'llms_txt' => array('url' => home_url('/llms.txt'), 'enabled' => self::llms_enabled(), 'ownership' => AJNanda_Search_AI_Capability_Ownership::get('llms_txt')),
            'schema' => array(
                'enabled' => (bool) get_theme_mod('seo_schema_enabled', true),
                'active' => (bool) get_theme_mod('seo_schema_enabled', true) && AJNanda_Search_AI_Capability_Ownership::ajnanda_owns('schema'),
                'ownership' => AJNanda_Search_AI_Capability_Ownership::get('schema'),
            ),
            'policy_count' => count($policy['excluded_post_ids']) + count($policy['excluded_post_types']) + count($policy['excluded_paths']),
        );
    }
}
