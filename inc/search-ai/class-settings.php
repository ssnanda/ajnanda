<?php
/**
 * Search & AI settings and legacy migration.
 *
 * @package AJNanda
 */

if (! defined('ABSPATH')) {
    exit;
}

class AJNanda_Search_AI_Settings {

    const MIGRATION_VERSION = 2;
    const VERSION_MOD = 'search_ai_migration_version';

    /**
     * New setting defaults. Values remain theme-native theme mods.
     */
    public static function defaults() {
        return array(
            'search_ai_enabled'                  => true,
            'search_ai_allow_traditional_search'=> true,
            'search_ai_allow_ai_search'          => true,
            'search_ai_allow_ai_training'        => true,
            'search_ai_allow_user_retrieval'     => true,
            'search_ai_crawler_logging_enabled'  => false,
            'search_ai_log_retention_days'       => 30,
        );
    }

    public static function get($key, $default = null) {
        $defaults = self::defaults();
        if (null === $default && array_key_exists($key, $defaults)) {
            $default = $defaults[$key];
        }
        return get_theme_mod($key, $default);
    }

    public static function set($key, $value) {
        set_theme_mod($key, $value);
    }

    /**
     * Copy legacy intent into the expanded controls once. Legacy values are
     * retained and remain authoritative for the Phase 1 frontend.
     */
    public static function maybe_migrate() {
        if ((int) get_theme_mod(self::VERSION_MOD, 0) >= self::MIGRATION_VERSION) {
            return;
        }

        $legacy_ai = (bool) get_theme_mod('seo_allow_ai_crawlers', true);
        $mappings = array(
            'search_ai_allow_ai_search'      => $legacy_ai,
            'search_ai_allow_ai_training'    => $legacy_ai,
            'search_ai_allow_user_retrieval' => $legacy_ai,
            'search_ai_profile_phone'        => get_theme_mod('seo_business_phone', ''),
            'search_ai_profile_address_street' => get_theme_mod('seo_business_address', ''),
        );

        foreach ($mappings as $key => $value) {
            if ('__ajnanda_unset__' === get_theme_mod($key, '__ajnanda_unset__')) {
                set_theme_mod($key, $value);
            }
        }

        set_theme_mod(self::VERSION_MOD, self::MIGRATION_VERSION);
    }
}
