<?php
/** Central Search & AI content-access policy. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Content_Policy {
    public static function settings() {
        return array(
            'excluded_post_ids' => array_values(array_filter(array_map('absint', (array) get_theme_mod('search_ai_excluded_post_ids', array())))),
            'excluded_post_types' => array_values(array_filter(array_map('sanitize_key', (array) get_theme_mod('search_ai_excluded_post_types', array())))),
            'excluded_paths' => self::normalize_paths((array) get_theme_mod('search_ai_excluded_paths', array())),
            'effects' => wp_parse_args((array) get_theme_mod('search_ai_exclusion_effects', array()), self::default_effects()),
        );
    }

    public static function default_effects() {
        return array(
            'noindex' => true,
            'automated_crawlers' => false,
            'traditional_search' => true,
            'ai_search' => true,
            'ai_training' => true,
            'user_retrieval' => true,
            'sitemap' => true,
            'llms_txt' => true,
            'schema_relationships' => true,
        );
    }

    public static function normalize_paths($paths) {
        $normalized = array();
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ('' === $path) { continue; }
            if (false !== strpos($path, '://')) {
                $path = (string) wp_parse_url($path, PHP_URL_PATH);
            }
            $normalized[] = '/' . ltrim($path, '/');
        }
        return array_values(array_unique($normalized));
    }

    private static function path_matches($path, $pattern) {
        $path = '/' . ltrim((string) $path, '/');
        if (false !== strpos($pattern, '*')) {
            return (bool) preg_match('#^' . str_replace('\\*', '.*', preg_quote($pattern, '#')) . '$#', $path);
        }
        return rtrim($path, '/') === rtrim($pattern, '/') || 0 === strpos($path, rtrim($pattern, '/') . '/');
    }

    public static function evaluate($subject = null) {
        $post_id = is_numeric($subject) ? absint($subject) : 0;
        $url = is_string($subject) ? $subject : ($post_id ? get_permalink($post_id) : '');
        if (! $post_id && $url) { $post_id = url_to_postid($url); }
        $path = $url ? (string) wp_parse_url($url, PHP_URL_PATH) : '';
        $post = $post_id ? get_post($post_id) : null;
        $is_public = $post ? ('publish' === $post->post_status && is_post_type_viewable($post->post_type)) : true;
        $settings = self::settings();
        $reasons = array();

        if ($post_id && in_array($post_id, $settings['excluded_post_ids'], true)) { $reasons[] = 'excluded_post'; }
        if ($post && in_array($post->post_type, $settings['excluded_post_types'], true)) { $reasons[] = 'excluded_post_type'; }
        foreach ($settings['excluded_paths'] as $pattern) {
            if ($path && self::path_matches($path, $pattern)) { $reasons[] = 'excluded_path'; break; }
        }
        if (! $is_public) { $reasons[] = 'not_public'; }

        $excluded = (bool) array_intersect($reasons, array('excluded_post', 'excluded_post_type', 'excluded_path'));
        $effects = $settings['effects'];
        $automated_allowed = $is_public && ! ($excluded && ! empty($effects['automated_crawlers']));
        $legacy_noindex = $post_id && '1' === get_post_meta($post_id, '_ajnanda_seo_noindex', true);
        if ($legacy_noindex) { $reasons[] = 'legacy_noindex'; }

        $decision = array(
            'subject' => $subject,
            'post_id' => $post_id,
            'url' => $url,
            'excluded' => $excluded,
            'publicly_accessible' => (bool) $is_public,
            'search_indexable' => $is_public && ! $legacy_noindex && ! ($excluded && ! empty($effects['noindex'])),
            'crawler_access' => array(
                'automated' => $automated_allowed,
                'traditional_search' => $automated_allowed && (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_traditional_search') && ! ($excluded && ! empty($effects['traditional_search'])),
                'ai_search' => $automated_allowed && (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_ai_search') && ! ($excluded && ! empty($effects['ai_search'])),
                'ai_training' => $automated_allowed && (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_ai_training') && ! ($excluded && ! empty($effects['ai_training'])),
                'user_retrieval' => $is_public && (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_user_retrieval') && ! ($excluded && ! empty($effects['user_retrieval'])),
            ),
            'advertise' => array(
                'traditional_search' => $is_public && ! ($excluded && ! empty($effects['traditional_search'])),
                'ai_search' => $is_public && ! ($excluded && ! empty($effects['ai_search'])),
                'sitemap' => $is_public && ! ($excluded && ! empty($effects['sitemap'])),
                'llms_txt' => $is_public && ! ($excluded && ! empty($effects['llms_txt'])),
                'markdown' => false,
                'schema_relationships' => $is_public && ! ($excluded && ! empty($effects['schema_relationships'])),
            ),
            'reasons' => empty($reasons) ? array('site_default') : array_values(array_unique($reasons)),
        );
        return apply_filters('ajnanda_search_ai_content_policy_decision', $decision, $subject);
    }
}
