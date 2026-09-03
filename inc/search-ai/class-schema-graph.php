<?php
/** AJNanda's connected, profile-backed schema graph. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Schema_Graph {
    public static function render($is_singular, $post_id) {
        if (! AJNanda_Search_AI_Capability_Ownership::ajnanda_owns('schema')) {
            return;
        }
        $subject_id = $post_id ?: get_queried_object_id();
        if ($subject_id && empty(AJNanda_Search_AI_Content_Policy::evaluate($subject_id)['advertise']['schema_relationships'])) {
            return;
        }
        $nodes = self::nodes($is_singular, $post_id);
        if (! $nodes) { return; }
        $graph = array('@context' => 'https://schema.org', '@graph' => $nodes);
        echo '<script type="application/ld+json">' . wp_json_encode($graph, JSON_UNESCAPED_SLASHES) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public static function nodes($is_singular, $post_id) {
        $nodes = array();
        $entity_id = home_url('/#identity');
        if (is_front_page() || is_home()) {
            $nodes[] = self::identity_node($entity_id);
        }
        if ($is_singular && 'post' === get_post_type($post_id)) {
            $author_name = get_the_author_meta('display_name', get_post_field('post_author', $post_id));
            $article = array(
                '@type' => 'Article', '@id' => get_permalink($post_id) . '#article',
                'url' => get_permalink($post_id), 'headline' => html_entity_decode(get_the_title($post_id), ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8'),
                'datePublished' => get_the_date('c', $post_id),
                'dateModified' => get_the_modified_date('c', $post_id),
                'publisher' => array('@id' => $entity_id),
            );
            if ($author_name) { $article['author'] = array('@type' => 'Person', 'name' => $author_name); }
            $image = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'large') : get_theme_mod('seo_default_social_image', '');
            if ($image) { $article['image'] = $image; }
            $nodes[] = $article;
            $faq = ajnanda_seo_extract_faq_schema($post_id);
            if ($faq) { unset($faq['@context']); $faq['@id'] = get_permalink($post_id) . '#faq'; $nodes[] = $faq; }
        } elseif (is_front_page() || is_home()) {
            $faq = ajnanda_seo_extract_faq_schema($post_id ?: get_queried_object_id(), false);
            if ($faq) { unset($faq['@context']); $faq['@id'] = home_url('/#faq'); $nodes[] = $faq; }
        }
        return apply_filters('ajnanda_search_ai_schema_nodes', array_values(array_filter($nodes)), $is_singular, $post_id);
    }

    private static function identity_node($entity_id) {
        $profile = AJNanda_Search_AI_Site_Profile::get();
        $allowed_types = array_keys(AJNanda_Search_AI_Site_Profile::organization_types());
        $type = in_array($profile['organization_type'], $allowed_types, true) ? $profile['organization_type'] : 'Organization';
        $node = array('@type' => $type, '@id' => $entity_id, 'name' => $profile['name'], 'url' => $profile['website']);
        if ($profile['alternate_name']) { $node['alternateName'] = $profile['alternate_name']; }
        if ($profile['description']) { $node['description'] = $profile['description']; }
        if ($profile['industry']) { $node['knowsAbout'] = $profile['industry']; }
        if ($profile['logo_url']) { $node['logo'] = array('@type' => 'ImageObject', 'url' => $profile['logo_url']); }
        if ($profile['phone']) { $node['telephone'] = $profile['phone']; }
        if ($profile['email']) { $node['email'] = $profile['email']; }
        if ($profile['identity_urls']) { $node['sameAs'] = array_values($profile['identity_urls']); }
        if ($profile['service_areas']) { $node['areaServed'] = array_values($profile['service_areas']); }
        if ('physical' === $profile['location_mode'] && array_filter($profile['address'])) {
            $node['address'] = array_filter(array(
                '@type' => 'PostalAddress',
                'streetAddress' => $profile['address']['street'],
                'addressLocality' => $profile['address']['city'],
                'addressRegion' => $profile['address']['state'],
                'postalCode' => $profile['address']['postal'],
                'addressCountry' => $profile['address']['country'],
            ));
        }
        return $node;
    }
}
