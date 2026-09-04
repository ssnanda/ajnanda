<?php
/** Privacy-conscious Search & AI diagnostic handoff export. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Export {
    public static function report() {
        $readiness = AJNanda_Search_AI_Readiness::report();
        $insights = AJNanda_Search_AI_Insights::report($readiness);
        $suspicious = AJNanda_Search_AI_Suspicious_Bot_Detector::report();
        $crawler = AJNanda_Search_AI_Crawler_Log_Store::table_exists() ? AJNanda_Search_AI_Crawler_Log_Store::aggregates(array('days' => 30)) : array();
        $policy = AJNanda_Search_AI_Content_Policy::settings();
        $profile = AJNanda_Search_AI_Site_Profile::get();
        $ownership = array();
        foreach (AJNanda_Search_AI_Capability_Ownership::capabilities() as $capability) {
            $owner = AJNanda_Search_AI_Capability_Ownership::get($capability);
            $ownership[$capability] = array('status' => $owner['status'], 'ajnanda' => (bool) $owner['ajnanda'], 'external' => array_values($owner['external']));
        }
        $excluded = array();
        foreach ($policy['excluded_post_ids'] as $post_id) {
            $excluded[] = array('id' => $post_id, 'title' => get_the_title($post_id), 'url' => get_permalink($post_id), 'type' => get_post_type($post_id));
        }
        $resolved_important = AJNanda_Search_AI_Important_Pages::resolve();
        $important = array();
        foreach ($resolved_important['valid'] as $post_id => $post) {
            $important[] = array('id' => (int) $post_id, 'title' => get_the_title($post), 'url' => get_permalink($post), 'status' => 'valid');
        }
        foreach ($resolved_important['invalid'] as $post_id => $info) {
            $important[] = array('id' => (int) $post_id, 'title' => $info['title'], 'url' => $info['post'] ? get_permalink($info['post']) : '', 'status' => 'invalid', 'reasons' => array_values($info['reasons']));
        }
        $stale_references = AJNanda_Search_AI_Stale_References::scan();
        $suspicious_events = array_map(static function ($event) {
            return array(
                'observed_at_utc' => $event['observed_at'],
                'reported_identity' => $event['reported_identity'],
                'verification_state' => $event['verification_state'],
                'requested_path' => $event['request_path'],
                'http_status' => $event['http_status'] ? (int) $event['http_status'] : null,
                'severity' => $event['suspicion']['severity'],
                'reasons' => array_values($event['suspicion']['reasons']),
            );
        }, $suspicious['recent']);
        $action_items = array(
            'readiness' => array_values(array_map(array(__CLASS__, 'readiness_action'), $readiness['issues'])),
            'stale_ai_references' => array_values($stale_references['findings']),
            'search_and_performance' => array_values($insights['opportunities']),
            'suspicious_activity' => array_values($suspicious['guidance']),
        );
        return apply_filters('ajnanda_search_ai_export_report', array(
            'report' => array(
                'format' => 'AJNanda Search & AI Handoff',
                'format_version' => 2,
                'generated_at_utc' => gmdate('c'),
                'site_url' => home_url('/'),
                'site_name' => get_bloginfo('name'),
                'notice' => 'This report contains WordPress-local observations and connected-provider evidence. It does not prove indexing, rankings, AI understanding, citation, or crawler identity unless explicitly marked verified.',
                'privacy' => 'API credentials, stored IP values, raw User-Agent strings, and individual non-suspicious crawler events are excluded.',
                'handoff_instructions' => 'Attach this JSON file in VS Code and ask Codex to review action_items first, validate each recommendation against the supporting evidence, and propose or implement the appropriate website changes.',
            ),
            'action_items' => $action_items,
            'technical_readiness' => array('score' => $readiness['score'], 'checks' => array_values($readiness['checks'])),
            'readiness' => array('deprecated' => true, 'replacement' => 'technical_readiness', 'score' => $readiness['score'], 'checks' => array_values($readiness['checks'])),
            'insights' => $insights,
            'site_profile' => array(
                'name' => $profile['name'], 'alternate_name' => $profile['alternate_name'], 'description' => $profile['description'],
                'organization_type' => $profile['organization_type'], 'industry' => $profile['industry'], 'website' => $profile['website'],
                'location_mode' => $profile['location_mode'], 'public_address' => $profile['address'], 'service_areas' => $profile['service_areas'], 'identity_urls' => $profile['identity_urls'],
            ),
            'content_access' => array('excluded_content' => $excluded, 'excluded_post_types' => $policy['excluded_post_types'], 'excluded_paths' => $policy['excluded_paths'], 'effects' => $policy['effects']),
            'ai_discovery_policy' => array(
                'traditional_search' => (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_traditional_search'),
                'ai_search' => (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_ai_search'),
                'ai_training' => (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_ai_training'),
                'user_initiated_retrieval' => (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_user_retrieval'),
            ),
            'discovery' => array('status' => AJNanda_Search_AI_Discovery_Files::status(false), 'important_pages' => $important, 'stale_references' => $stale_references['findings']),
            'capability_ownership' => $ownership,
            'crawler_activity_30_days' => $crawler,
            'suspicious_activity' => array(
                'enabled' => $suspicious['enabled'], 'period_days' => $suspicious['days'], 'events_scanned' => $suspicious['scanned'], 'scan_limited' => $suspicious['scan_limited'],
                'suspicious_requests' => $suspicious['total'], 'critical' => $suspicious['critical'], 'high' => $suspicious['high'], 'medium' => $suspicious['medium'],
                'successful_sensitive_responses' => $suspicious['successful_sensitive'], 'unverified_crawler_claims' => $suspicious['claimed_crawlers'],
                'top_paths' => $suspicious['top_paths'], 'recent_evidence' => $suspicious_events,
            ),
        ));
    }

    public static function download() {
        if (! current_user_can('manage_options')) { wp_die(esc_html__('Insufficient permissions.', 'ajnanda')); }
        check_admin_referer('ajnanda_export_search_ai');
        $host = sanitize_file_name((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="ajnanda-search-ai-' . ($host ?: 'site') . '-' . gmdate('Y-m-d') . '.json"');
        echo wp_json_encode(self::report(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    public static function readiness_action($check) {
        return array('state' => $check['state'], 'title' => $check['label'], 'why_it_matters' => $check['message'], 'review_tab' => $check['tab']);
    }
}
