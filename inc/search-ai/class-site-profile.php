<?php
/**
 * Canonical machine-readable site profile.
 *
 * @package AJNanda
 */

if (! defined('ABSPATH')) {
    exit;
}

class AJNanda_Search_AI_Site_Profile {

    /**
     * Return a normalized profile. Explicit Search & AI values take priority,
     * followed by existing AJNanda/WordPress values for upgrade compatibility.
     */
    public static function get() {
        $logo_id = (int) get_theme_mod('search_ai_profile_logo_id', get_theme_mod('custom_logo', 0));
        $profile = array(
            'name'              => get_theme_mod('search_ai_profile_name', get_bloginfo('name')),
            'alternate_name'    => get_theme_mod('search_ai_profile_alternate_name', ''),
            'description'       => get_theme_mod('search_ai_profile_description', get_bloginfo('description')),
            'organization_type' => get_theme_mod('search_ai_profile_organization_type', 'Organization'),
            'industry'          => get_theme_mod('search_ai_profile_industry', ''),
            'logo_id'           => $logo_id,
            'logo_url'          => $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '',
            'website'           => home_url('/'),
            'phone'             => get_theme_mod('search_ai_profile_phone', get_theme_mod('seo_business_phone', '')),
            'email'             => get_theme_mod('search_ai_profile_email', ''),
            'address'           => array(
                'street'  => get_theme_mod('search_ai_profile_address_street', get_theme_mod('seo_business_address', '')),
                'city'    => get_theme_mod('search_ai_profile_address_city', ''),
                'state'   => get_theme_mod('search_ai_profile_address_state', ''),
                'postal'  => get_theme_mod('search_ai_profile_address_postal', ''),
                'country' => get_theme_mod('search_ai_profile_address_country', ''),
            ),
            'location_mode'     => get_theme_mod('search_ai_profile_location_mode', 'unspecified'),
            'service_areas'     => (array) get_theme_mod('search_ai_profile_service_areas', array()),
            'identity_urls'     => (array) get_theme_mod('search_ai_profile_identity_urls', array()),
            'services'          => (array) get_theme_mod('search_ai_profile_services', array()),
        );

        return apply_filters('ajnanda_search_ai_site_profile', $profile);
    }
}

