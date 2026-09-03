<?php
/** Provider-neutral page-level semantic intent. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Page_Semantic_Intent {
    const META_KEY = '_ajnanda_primary_entity_type';

    public static function init() {
        register_post_meta('page', self::META_KEY, array(
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => array(__CLASS__, 'sanitize'),
            'auth_callback' => static function () { return current_user_can('edit_pages'); },
        ));
    }

    public static function choices() {
        return apply_filters('ajnanda_search_ai_page_entity_types', array(
            'webpage' => __('General page', 'ajnanda'),
            'service' => __('A service', 'ajnanda'),
            'product' => __('A product', 'ajnanda'),
            'primary_location' => __('The primary business location', 'ajnanda'),
        ));
    }

    public static function sanitize($value) {
        $value = sanitize_key((string) $value);
        return isset(self::choices()[$value]) ? $value : '';
    }

    public static function stored($post_id) {
        return (string) get_post_meta(absint($post_id), self::META_KEY, true);
    }

    public static function requested($post_id) {
        if ('page' !== get_post_type($post_id)) { return 'webpage'; }
        $stored = self::stored($post_id);
        return isset(self::choices()[$stored]) ? $stored : 'webpage';
    }

    public static function evaluate($post_id) {
        $stored = self::stored($post_id);
        $requested = self::requested($post_id);
        $effective = $requested;
        $valid = true;
        $reason = '';

        if ($stored && ! isset(self::choices()[$stored])) {
            $valid = false;
            $reason = __('The saved page meaning is not recognized.', 'ajnanda');
        } elseif (in_array($requested, array('service', 'product'), true) && ! trim((string) get_the_title($post_id))) {
            $valid = false;
            $reason = __('The selected page meaning requires a page title.', 'ajnanda');
        } elseif ('primary_location' === $requested && ! self::physical_profile_complete()) {
            $valid = false;
            $reason = __('Primary business location requires a Physical location Site Profile with a complete structured address.', 'ajnanda');
        }

        if (! $valid) { $effective = 'webpage'; }
        return apply_filters('ajnanda_search_ai_page_semantic_intent', compact('stored', 'requested', 'effective', 'valid', 'reason'), absint($post_id));
    }

    public static function physical_profile_complete() {
        $profile = AJNanda_Search_AI_Site_Profile::get();
        if ('physical' !== $profile['location_mode']) { return false; }
        foreach (array('street', 'city', 'state', 'postal', 'country') as $field) {
            if (empty($profile['address'][$field])) { return false; }
        }
        return true;
    }

    public static function diagnostic_issues() {
        $issues = array();
        $ids = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'meta_key' => self::META_KEY, 'fields' => 'ids', 'numberposts' => -1, 'no_found_rows' => true));
        foreach ($ids as $post_id) {
            $status = self::evaluate($post_id);
            if (! $status['valid']) {
                $issues[] = array('post_id' => $post_id, 'title' => get_the_title($post_id) ?: '#' . $post_id, 'reason' => $status['reason']);
            }
        }
        return $issues;
    }
}

