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

    private static function value($key, $fallback = '') {
        $value = get_theme_mod($key, '__ajnanda_unset__');
        return '__ajnanda_unset__' === $value ? $fallback : $value;
    }

    /**
     * Return a normalized profile. Explicit Search & AI values take priority,
     * followed by existing AJNanda/WordPress values for upgrade compatibility.
     */
    public static function get() {
        $logo_id = (int) get_theme_mod('search_ai_profile_logo_id', get_theme_mod('custom_logo', 0));
        $location_mode = get_theme_mod('search_ai_profile_location_mode', 'none');
        $stored_address = array(
            'street'  => self::value('search_ai_profile_address_street', get_theme_mod('seo_business_address', '')),
            'city'    => self::value('search_ai_profile_address_city', ''),
            'state'   => self::value('search_ai_profile_address_state', ''),
            'postal'  => self::value('search_ai_profile_address_postal', ''),
            'country' => self::value('search_ai_profile_address_country', ''),
        );
        $profile = array(
            'name'              => self::value('search_ai_profile_name', get_bloginfo('name')),
            'alternate_name'    => self::value('search_ai_profile_alternate_name', ''),
            'description'       => self::value('search_ai_profile_description', get_bloginfo('description')),
            'organization_type' => self::value('search_ai_profile_organization_type', 'Organization'),
            'industry'          => self::value('search_ai_profile_industry', ''),
            'logo_id'           => $logo_id,
            'logo_url'          => $logo_id ? ajnanda_seo_normalize_site_url(wp_get_attachment_image_url($logo_id, 'full')) : '',
            // The current WordPress URL is authoritative. A copied environment
            // must never publish the source environment's stored origin.
            'website'           => home_url('/'),
            'phone'             => self::value('search_ai_profile_phone', get_theme_mod('seo_business_phone', '')),
            'email'             => self::value('search_ai_profile_email', ''),
            // Only a physical-location profile exposes a PostalAddress. Stored
            // values remain available to the editor if the mode changes later.
            'address'           => 'physical' === $location_mode ? $stored_address : array_fill_keys(array_keys($stored_address), ''),
            'stored_address'    => $stored_address,
            'location_mode'     => $location_mode,
            'service_areas'     => class_exists('AJNanda_Search_AI_Service_Area_Registry') ? AJNanda_Search_AI_Service_Area_Registry::public_names() : (array) get_theme_mod('search_ai_profile_service_areas', array()),
            'stored_service_areas' => (array) get_theme_mod('search_ai_profile_service_areas', array()),
            'identity_urls'     => (array) get_theme_mod('search_ai_profile_identity_urls', array()),
            'services'          => (array) get_theme_mod('search_ai_profile_services', array()),
        );

        $profile['identity_urls'] = array_values(array_filter(array_map('ajnanda_seo_normalize_site_url', $profile['identity_urls'])));
        return apply_filters('ajnanda_search_ai_site_profile', $profile);
    }

    public static function organization_types() {
        return apply_filters('ajnanda_search_ai_organization_types', array(
            'Organization' => __('Organization', 'ajnanda'),
            'Corporation' => __('Corporation', 'ajnanda'),
            'LocalBusiness' => __('Local business', 'ajnanda'),
            'ProfessionalService' => __('Professional service', 'ajnanda'),
            'HomeAndConstructionBusiness' => __('Home and construction business', 'ajnanda'),
            'MedicalBusiness' => __('Medical business', 'ajnanda'),
            'Store' => __('Store', 'ajnanda'),
            'EducationalOrganization' => __('Educational organization', 'ajnanda'),
            'NonprofitOrganization' => __('Nonprofit organization', 'ajnanda'),
            'Person' => __('Person / personal website', 'ajnanda'),
        ));
    }
}
