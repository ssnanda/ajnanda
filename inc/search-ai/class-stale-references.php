<?php
/** Detects stale URLs still promoted through Search & AI discovery. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

/**
 * "Stale AI References" check surfaced on Search & AI -> Overview.
 *
 * It looks at the URLs AJNanda actively promotes to search engines and AI
 * systems - Important Pages, the foundational pages added to llms.txt, custom
 * schema identity references, and AJNanda-controlled Content Access selections -
 * and reports any that are no longer appropriate discovery targets. Intentional
 * external authoritative links are never flagged simply for being external.
 *
 * Healthy state: 0 findings.
 */
class AJNanda_Search_AI_Stale_References {

    /** Human-readable explanation for each eligibility failure key. */
    public static function reason_label($key) {
        $labels = array(
            'missing'                 => __('The referenced page no longer exists.', 'ajnanda'),
            'not_published'           => __('The page is not published (draft, pending, private, or trashed).', 'ajnanda'),
            'not_public'              => __('The page is not publicly accessible.', 'ajnanda'),
            'not_public_type'         => __('The content type is not publicly viewable.', 'ajnanda'),
            'noindex'                 => __('The page is set to noindex.', 'ajnanda'),
            'content_access_excluded' => __('The page is excluded through Content Access.', 'ajnanda'),
            'channel_excluded'        => __('Content Access blocks this page from this discovery channel.', 'ajnanda'),
            'noncanonical'            => __('The URL is not canonical or now redirects elsewhere.', 'ajnanda'),
        );
        return $labels[$key] ?? $key;
    }

    /**
     * @return array{count: int, findings: array<int, array{
     *   source: string, source_label: string, url: string, label: string,
     *   reason: string, reason_key: string, tab: string
     * }>}
     */
    public static function scan() {
        $findings = array();

        // 1. Important Pages: administrator selections that are no longer eligible.
        foreach (AJNanda_Search_AI_Important_Pages::resolve()['invalid'] as $info) {
            $findings[] = self::finding(
                'important_page',
                __('Important Pages', 'ajnanda'),
                $info['post'] ? (string) get_permalink($info['post']) : '',
                $info['title'],
                $info['reasons'],
                'discovery-files'
            );
        }

        // 2. Foundational pages auto-added to llms.txt that are not discoverable.
        foreach (array('page_on_front' => __('Homepage', 'ajnanda'), 'page_for_posts' => __('Posts page', 'ajnanda')) as $option => $label) {
            $id = (int) get_option($option);
            if (! $id) { continue; }
            $eligibility = AJNanda_Search_AI_Discovery_Files::eligible_for_discovery($id, 'llms_txt');
            if (! $eligibility['eligible']) {
                $findings[] = self::finding('foundational', $label, (string) get_permalink($id), get_the_title($id) ?: $label, $eligibility['reasons'], 'content-access');
            }
        }

        // 3. Custom schema identity references pointing at internal URLs that no
        //    longer resolve. External identity links are intentional and skipped.
        $profile = AJNanda_Search_AI_Site_Profile::get();
        foreach ((array) $profile['identity_urls'] as $url) {
            if (! is_string($url) || '' === trim($url) || ! self::is_internal($url)) { continue; }
            if (! url_to_postid($url) && ! self::is_home_url($url)) {
                $findings[] = self::finding('schema', __('Schema identity links', 'ajnanda'), $url, $url, array('noncanonical'), 'site-profile');
            }
        }

        // 4. AJNanda-controlled Content Access exclusions referencing deleted content.
        foreach (AJNanda_Search_AI_Content_Policy::settings()['excluded_post_ids'] as $id) {
            if (! get_post($id)) {
                $findings[] = self::finding('content_access', __('Content Access exclusions', 'ajnanda'), '', sprintf(__('Removed content #%d', 'ajnanda'), $id), array('missing'), 'content-access');
            }
        }

        $findings = array_values((array) apply_filters('ajnanda_search_ai_stale_references', $findings));
        return array('count' => count($findings), 'findings' => $findings);
    }

    private static function finding($source, $source_label, $url, $label, $reasons, $tab) {
        $key = is_array($reasons) && $reasons ? (string) reset($reasons) : 'noncanonical';
        return array(
            'source'       => $source,
            'source_label' => $source_label,
            'url'          => (string) $url,
            'label'        => (string) $label,
            'reason'       => self::reason_label($key),
            'reason_key'   => $key,
            'tab'          => $tab,
        );
    }

    private static function is_internal($url) {
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (! $host) { return true; } // relative path
        return strtolower($host) === strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    }

    private static function is_home_url($url) {
        $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
        $home = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        return $path === $home;
    }
}
