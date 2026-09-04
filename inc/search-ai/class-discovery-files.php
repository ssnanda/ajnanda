<?php
/** Discovery output helpers and diagnostics. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Discovery_Files {
    public static function important_page_ids() {
        return array_values(array_filter(array_unique(array_map('absint', (array) get_theme_mod('search_ai_llms_important_page_ids', array())))));
    }

    /**
     * Whether a page/post is currently a legitimate target for generated AI
     * discovery outputs (llms.txt, Important Pages, custom schema references).
     *
     * A URL qualifies only when it is published, publicly viewable, search
     * indexable (not noindex), allowed by the central Content Access policy for
     * the requested channel, canonical, and backed by a real object. WordPress
     * still knowing that a page once existed is never sufficient on its own.
     * This is the single eligibility gate every discovery output shares.
     *
     * @param int|string $subject Post ID or public URL.
     * @param string     $channel Content Access advertise channel: llms_txt,
     *                             schema_relationships, sitemap, traditional_search, ai_search.
     * @return array{eligible: bool, reasons: string[], post_id: int}
     */
    public static function eligible_for_discovery($subject, $channel = 'llms_txt') {
        $decision = AJNanda_Search_AI_Content_Policy::evaluate($subject);
        $post_id  = (int) $decision['post_id'];
        $post     = $post_id ? get_post($post_id) : null;
        $reasons  = array();

        if (! $post) {
            return array('eligible' => false, 'reasons' => array('missing'), 'post_id' => 0);
        }
        if ('publish' !== $post->post_status) { $reasons[] = 'not_published'; }
        if (! is_post_type_viewable($post->post_type)) { $reasons[] = 'not_public_type'; }
        if (empty($decision['publicly_accessible'])) { $reasons[] = 'not_public'; }
        if (empty($decision['search_indexable'])) { $reasons[] = 'noindex'; }
        if (! empty($decision['excluded'])) { $reasons[] = 'content_access_excluded'; }
        if (array_key_exists($channel, $decision['advertise']) && empty($decision['advertise'][$channel])) { $reasons[] = 'channel_excluded'; }

        // Canonical / redirect guard: the object's own permalink must resolve
        // back to the same published object. Catches trashed slugs, attachment
        // IDs, and IDs whose public URL now 301s elsewhere. Skipped when the
        // object is the one currently being viewed (WordPress already resolved
        // it canonically) and for the static front page and posts page, whose
        // URLs legitimately resolve to an archive query rather than the page.
        $rewrite_pages = array_filter(array((int) get_option('page_on_front'), (int) get_option('page_for_posts')));
        $is_current    = ! is_admin() && is_singular() && get_queried_object_id() === $post_id;
        if (empty($reasons) && ! $is_current && ! in_array($post_id, $rewrite_pages, true)) {
            $permalink = get_permalink($post_id);
            if (! $permalink || url_to_postid($permalink) !== $post_id) { $reasons[] = 'noncanonical'; }
        }

        $reasons = apply_filters('ajnanda_search_ai_discovery_eligibility', array_values(array_unique($reasons)), $post_id, $channel);
        return array('eligible' => empty($reasons), 'reasons' => $reasons, 'post_id' => $post_id);
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
        $important_ids = AJNanda_Search_AI_Important_Pages::discovery_ids();
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
            if (! self::eligible_for_discovery($post->ID, 'llms_txt')['eligible']) { continue; }
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
        $cache_key = 'ajnanda_search_ai_endpoint_v2_' . md5($cache_suffix . '|' . $url);
        $cached = get_transient($cache_key);
        if (is_array($cached)) { return $cached; }
        $response = wp_remote_get($url, array(
            'timeout' => 3, 'redirection' => 1, 'limit_response_size' => 2048,
            'user-agent' => 'AJNanda-Discovery-Diagnostic/1.0',
        ));
        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            $result = preg_match('/ssl|tls|certificate|curl error 60|unable to get local issuer/i', $message) ? 'tls_error' : 'transport_error';
            $status = array(
                'result' => $result,
                'reachable' => false,
                'code' => 0,
                'error_code' => $response->get_error_code(),
                'message' => $message,
            );
        } else {
            $code = (int) wp_remote_retrieve_response_code($response);
            $status = array(
                'result' => $code >= 200 && $code < 300 ? 'success' : 'http_error',
                'reachable' => $code >= 200 && $code < 300,
                'code' => $code,
                'error_code' => '',
                'message' => wp_remote_retrieve_response_message($response),
            );
        }
        set_transient($cache_key, $status, 5 * MINUTE_IN_SECONDS);
        return $status;
    }
}
