<?php
/** AJNanda's connected, profile-backed schema graph. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Schema_Graph {
    public static function render($is_singular, $post_id) {
        if (! AJNanda_Search_AI_Capability_Ownership::ajnanda_owns('schema')) { return; }
        $subject_id = $post_id ?: get_queried_object_id();
        if ($subject_id && ! AJNanda_Search_AI_Discovery_Files::eligible_for_discovery($subject_id, 'schema_relationships')['eligible']) { return; }
        $nodes = self::nodes($is_singular, $post_id);
        if (! $nodes) { return; }
        echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $nodes), JSON_UNESCAPED_SLASHES) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public static function nodes($is_singular, $post_id) {
        $subject_id = absint($post_id ?: get_queried_object_id());
        if ($subject_id && ! AJNanda_Search_AI_Discovery_Files::eligible_for_discovery($subject_id, 'schema_relationships')['eligible']) { return array(); }
        $is_article = $is_singular && 'post' === get_post_type($subject_id);
        $context = new AJNanda_Search_AI_Schema_Context($subject_id, $is_article);
        $default_areas=AJNanda_Search_AI_Service_Area_Registry::defaults();
        $nodes = array_merge(array(self::identity_node($context->identity_id), self::website_node($context), self::webpage_node($context)), AJNanda_Search_AI_Service_Area_Registry::schema_nodes($default_areas));
        if ($is_article) { $nodes[] = self::article_node($subject_id, $context); }

        $contributions = array('nodes' => array(), 'relationships' => array(), 'explicit_faq' => false);
        if ($subject_id && $is_singular && ! $is_article) {
            $page_entity = AJNanda_Search_AI_Schema_Page_Entity_Contributor::contribute($context);
            $nodes = array_merge($nodes, $page_entity['nodes']);
            $contributions['relationships'] = array_merge($contributions['relationships'], $page_entity['relationships']);
        }
        $entries = array();
        if ($subject_id && $is_singular) {
            $entries = AJNanda_Search_AI_Schema_Block_Walker::walk_post($subject_id);
            $block_contributions = AJNanda_Search_AI_Schema_Contributors::collect($entries, $context);
            $nodes = array_merge($nodes, $block_contributions['nodes']);
            $contributions['nodes'] = array_merge($contributions['nodes'], $block_contributions['nodes']);
            $contributions['relationships'] = array_merge($contributions['relationships'], $block_contributions['relationships']);
            $contributions['explicit_faq'] = $block_contributions['explicit_faq'];
        }
        if ($subject_id && empty($contributions['explicit_faq'])) {
            $legacy = AJNanda_Search_AI_Schema_Contributors::legacy_faq($entries, $context);
            if (! empty($legacy['nodes'])) {
                $nodes = array_merge($nodes, $legacy['nodes']);
                $contributions['relationships'] = array_merge($contributions['relationships'], $legacy['relationships']);
            }
        }
        $nodes = self::apply_relationships(self::merge_nodes($nodes), $contributions['relationships']);
        return apply_filters('ajnanda_search_ai_schema_nodes', array_values($nodes), $is_singular, $post_id, $context);
    }

    private static function website_node($context) {
        return array_filter(array('@type' => 'WebSite', '@id' => $context->website_id, 'url' => home_url('/'), 'name' => $context->profile['name'], 'description' => $context->profile['description'], 'publisher' => array('@id' => $context->identity_id)));
    }

    private static function webpage_node($context) {
        $node = array('@type' => 'WebPage', '@id' => $context->webpage_id, 'url' => $context->url, 'name' => $context->title, 'isPartOf' => array('@id' => $context->website_id), 'about' => array('@id' => $context->identity_id));
        if ($context->is_article) { $node['mainEntity'] = array('@id' => $context->primary_id); }
        if ($context->description) { $node['description'] = $context->description; }
        return $node;
    }

    private static function article_node($post_id, $context) {
        $article = array('@type' => 'Article', '@id' => $context->primary_id, 'url' => $context->url, 'headline' => html_entity_decode(get_the_title($post_id), ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8'), 'datePublished' => get_the_date('c', $post_id), 'dateModified' => get_the_modified_date('c', $post_id), 'mainEntityOfPage' => array('@id' => $context->webpage_id), 'isPartOf' => array('@id' => $context->website_id), 'publisher' => array('@id' => $context->identity_id));
        $author_name = get_the_author_meta('display_name', get_post_field('post_author', $post_id));
        if ($author_name) { $article['author'] = array('@type' => 'Person', 'name' => $author_name); }
        $image = $context->image ?: get_theme_mod('seo_default_social_image', '');
        if ($image) { $article['image'] = $image; }
        return $article;
    }

    private static function merge_nodes($nodes) {
        $merged = array();
        foreach (array_filter($nodes) as $node) {
            $id = $node['@id'] ?? '';
            if (! $id) { $merged[] = $node; continue; }
            $merged[$id] = isset($merged[$id]) ? array_replace_recursive($merged[$id], $node) : $node;
        }
        return $merged;
    }

    private static function apply_relationships($nodes, $relationships) {
        foreach ($relationships as $relationship) {
            if (count($relationship) !== 3 || ! isset($nodes[$relationship[0]])) { continue; }
            list($source, $property, $target) = $relationship;
            $reference = array('@id' => $target);
            if (! isset($nodes[$source][$property])) { $nodes[$source][$property] = $reference; continue; }
            $current = $nodes[$source][$property];
            if (isset($current['@id'])) { $current = array($current); }
            foreach ($current as $item) { if (($item['@id'] ?? '') === $target) { continue 2; } }
            $current[] = $reference;
            $nodes[$source][$property] = $current;
        }
        return $nodes;
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
        $areas=AJNanda_Search_AI_Service_Area_Registry::defaults();
        if ($areas) { $node['areaServed'] = AJNanda_Search_AI_Service_Area_Registry::schema_values($areas); }
        if ('physical' === $profile['location_mode'] && array_filter($profile['address'])) {
            $node['address'] = array_filter(array('@type' => 'PostalAddress', 'streetAddress' => $profile['address']['street'], 'addressLocality' => $profile['address']['city'], 'addressRegion' => $profile['address']['state'], 'postalCode' => $profile['address']['postal'], 'addressCountry' => $profile['address']['country']));
        }
        return $node;
    }
}
