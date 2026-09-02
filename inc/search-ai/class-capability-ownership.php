<?php
/**
 * SEO/discovery plugin capability ownership detection.
 *
 * @package AJNanda
 */

if (! defined('ABSPATH')) {
    exit;
}

class AJNanda_Search_AI_Capability_Ownership {

    public static function capabilities() {
        return array('title', 'meta_description', 'canonical', 'social', 'robots_meta', 'schema', 'sitemap', 'llms_txt', 'indexnow');
    }

    public static function detected_plugins() {
        $plugins = array();

        if (defined('WPSEO_VERSION')) {
            $plugins['yoast'] = array('label' => 'Yoast SEO', 'capabilities' => array('title', 'meta_description', 'canonical', 'social', 'robots_meta', 'schema', 'sitemap'));
        }
        if (defined('RANK_MATH_VERSION') || class_exists('RankMath')) {
            $plugins['rank_math'] = array('label' => 'Rank Math', 'capabilities' => array('title', 'meta_description', 'canonical', 'social', 'robots_meta', 'schema', 'sitemap'));
        }
        if (defined('SEOPRESS_VERSION')) {
            $plugins['seopress'] = array('label' => 'SEOPress', 'capabilities' => array('title', 'meta_description', 'canonical', 'social', 'robots_meta', 'schema', 'sitemap'));
        }
        if (defined('AIOSEO_VERSION')) {
            $plugins['aioseo'] = array('label' => 'All in One SEO', 'capabilities' => array('title', 'meta_description', 'canonical', 'social', 'robots_meta', 'schema', 'sitemap'));
        }

        return apply_filters('ajnanda_search_ai_detected_plugins', $plugins);
    }

    /**
     * Describe likely ownership without changing output in Phase 1.
     * Later integrations may safely supplement a plugin-owned graph.
     */
    public static function get($capability) {
        $owners = array();
        foreach (self::detected_plugins() as $slug => $plugin) {
            if (in_array($capability, $plugin['capabilities'], true)) {
                $owners[$slug] = $plugin['label'];
            }
        }

        $result = array(
            'capability' => $capability,
            'ajnanda'    => true,
            'external'   => $owners,
            'status'     => empty($owners) ? 'ajnanda' : 'review_required',
        );

        return apply_filters('ajnanda_search_ai_capability_ownership', $result, $capability);
    }
}

