<?php
/** Content-policy integration for WordPress core XML sitemaps. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Sitemap_Policy {
    public static function init() {
        if (! AJNanda_Search_AI_Capability_Ownership::ajnanda_owns('sitemap')) { return; }
        add_filter('wp_sitemaps_post_types', array(__CLASS__, 'post_types'));
        add_filter('wp_sitemaps_posts_query_args', array(__CLASS__, 'query_args'), 10, 2);
    }

    public static function post_types($post_types) {
        $settings = AJNanda_Search_AI_Content_Policy::settings();
        if (empty($settings['effects']['sitemap'])) { return $post_types; }
        foreach ($settings['excluded_post_types'] as $post_type) {
            unset($post_types[$post_type]);
        }
        return $post_types;
    }

    public static function query_args($args, $post_type) {
        $settings = AJNanda_Search_AI_Content_Policy::settings();
        if (empty($settings['effects']['sitemap'])) { return $args; }
        $excluded = $settings['excluded_post_ids'];
        foreach ($settings['excluded_paths'] as $path) {
            if (false === strpos($path, '*')) {
                $post_id = url_to_postid(home_url($path));
                if ($post_id) { $excluded[] = $post_id; }
            }
        }
        if ($excluded) {
            $args['post__not_in'] = array_values(array_unique(array_merge((array) ($args['post__not_in'] ?? array()), array_map('absint', $excluded))));
        }
        return $args;
    }
}
