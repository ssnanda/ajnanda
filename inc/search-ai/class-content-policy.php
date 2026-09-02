<?php
/**
 * Central Search & AI content-access policy.
 *
 * @package AJNanda
 */

if (! defined('ABSPATH')) {
    exit;
}

class AJNanda_Search_AI_Content_Policy {

    /**
     * Evaluate the effective policy for a post or URL.
     *
     * Phase 1 reflects existing noindex metadata and establishes independent
     * crawl/index/advertising dimensions. Exclusion editors and consumers are
     * added in later phases.
     */
    public static function evaluate($subject = null) {
        $post_id = is_numeric($subject) ? (int) $subject : 0;
        $url = is_string($subject) ? $subject : ($post_id ? get_permalink($post_id) : '');
        $is_public = true;

        if ($post_id) {
            $post = get_post($post_id);
            $is_public = $post && 'publish' === $post->post_status && is_post_type_viewable($post->post_type);
        }

        $legacy_noindex = $post_id && '1' === get_post_meta($post_id, '_ajnanda_seo_noindex', true);
        $decision = array(
            'subject'             => $subject,
            'post_id'             => $post_id,
            'url'                 => $url,
            'publicly_accessible' => (bool) $is_public,
            'search_indexable'    => $is_public && ! $legacy_noindex,
            'crawler_access'      => array(
                'traditional_search' => $is_public && (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_traditional_search'),
                'ai_search'          => $is_public && (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_ai_search'),
                'ai_training'        => $is_public && (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_ai_training'),
                'user_retrieval'     => $is_public && (bool) AJNanda_Search_AI_Settings::get('search_ai_allow_user_retrieval'),
            ),
            'advertise'           => array(
                'sitemap'              => $is_public && ! $legacy_noindex,
                'llms_txt'             => $is_public && ! $legacy_noindex,
                'markdown'             => false,
                'schema_relationships' => $is_public && ! $legacy_noindex,
            ),
            'reasons'             => $legacy_noindex ? array('legacy_noindex') : array('site_default'),
        );

        return apply_filters('ajnanda_search_ai_content_policy_decision', $decision, $subject);
    }
}

