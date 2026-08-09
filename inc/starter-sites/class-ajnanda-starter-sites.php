<?php
/**
 * AJNanda_Starter_Sites — starter-site manifest registry.
 *
 * Loads every manifest file under inc/starter-sites/manifests/*.php. Each
 * manifest file returns a plain PHP array describing one starter site (see
 * docs/starter-sites.md for the schema). This class is the single source
 * of truth used by the admin UI, the importer, and the WP-CLI commands.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJNanda_Starter_Sites {

    /** @var array<string,array>|null */
    private static $manifests = null;

    /**
     * @return array<string,array> slug => manifest.
     */
    public static function get_all() {
        if (null === self::$manifests) {
            self::load();
        }
        return self::$manifests;
    }

    /**
     * @param string $slug Starter site slug.
     * @return array|null
     */
    public static function get($slug) {
        $all = self::get_all();
        return isset($all[$slug]) ? $all[$slug] : null;
    }

    private static function load() {
        self::$manifests = array();

        $dir = get_template_directory() . '/inc/starter-sites/manifests';
        $files = glob($dir . '/*.php');

        if (!$files) {
            return;
        }

        sort($files);

        foreach ($files as $file) {
            $manifest = include $file;

            if (!is_array($manifest) || empty($manifest['slug']) || empty($manifest['pages'])) {
                continue;
            }

            self::$manifests[$manifest['slug']] = $manifest;
        }
    }
}
