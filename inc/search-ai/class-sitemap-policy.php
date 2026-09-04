<?php
/** Content-policy integration for WordPress core XML sitemaps. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Sitemap_Policy {
    public static function init() {
        if (! AJNanda_Search_AI_Capability_Ownership::ajnanda_owns('sitemap')) { return; }
        add_filter('wp_sitemaps_post_types', array(__CLASS__, 'post_types'));
        add_filter('wp_sitemaps_posts_query_args', array(__CLASS__, 'query_args'), 10, 2);
        add_filter('wp_sitemaps_add_provider', array(__CLASS__, 'provider'), 10, 2);
        add_filter('wp_sitemaps_taxonomies', array(__CLASS__, 'taxonomies'));
    }

    public static function provider($provider, $name) {
        return 'users' === $name ? false : $provider;
    }

    public static function taxonomies($taxonomies) {
        $included = (array) apply_filters('ajnanda_search_ai_sitemap_taxonomies', array());
        foreach (array_keys($taxonomies) as $taxonomy) {
            if (! in_array($taxonomy, $included, true)) { unset($taxonomies[$taxonomy]); }
        }
        return $taxonomies;
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
        $utility_slugs = apply_filters('ajnanda_search_ai_utility_page_slugs', array('login', 'password-reset', 'edit-profile', 'member-home', 'account', 'my-account', 'client-portal', 'products-sandbox'));
        $excluded = array_merge($excluded, get_posts(array(
            'post_type' => $post_type, 'post_status' => 'publish', 'numberposts' => -1,
            'post_name__in' => $utility_slugs, 'fields' => 'ids', 'no_found_rows' => true,
        )));
        foreach ($settings['excluded_paths'] as $path) {
            if (false === strpos($path, '*')) {
                $post_id = url_to_postid(home_url($path));
                if ($post_id) { $excluded[] = $post_id; }
            }
        }
        if ($excluded) {
            $args['post__not_in'] = array_values(array_unique(array_merge((array) ($args['post__not_in'] ?? array()), array_map('absint', $excluded))));
        }
        $args['meta_query'] = array_merge((array) ($args['meta_query'] ?? array()), array(array(
            'relation' => 'OR',
            array('key' => '_ajnanda_seo_noindex', 'compare' => 'NOT EXISTS'),
            array('key' => '_ajnanda_seo_noindex', 'value' => '1', 'compare' => '!='),
        )));
        return $args;
    }
}
