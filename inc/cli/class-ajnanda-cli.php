<?php
/**
 * AJNanda WP-CLI commands.
 *
 * Every command here is a thin wrapper around the same registry/importer
 * classes the admin UI uses (ajnanda_get_page_designs(),
 * AJNanda_Starter_Sites, AJNanda_Starter_Importer) — there is exactly one
 * implementation of each behavior, callable from wp-admin or scripts.
 *
 * Only loaded when WP-CLI is running. See docs/starter-sites.md and
 * docs/development.md for command examples, including the
 * "wp ajnanda starter import technology" style workflow this is designed
 * to support for automated site creation.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * Inspect and insert AJNanda section patterns.
 */
class AJNanda_CLI_Pattern_Command {

    /**
     * List registered AJNanda section patterns.
     *
     * ## OPTIONS
     *
     * [--category=<slug>]
     * : Only show patterns in this pattern category (e.g. ajnanda-hero).
     *
     * [--format=<format>]
     * : Render output as table, csv, json, or count. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp ajnanda pattern list
     *     wp ajnanda pattern list --category=ajnanda-cta --format=json
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        if (!class_exists('WP_Block_Patterns_Registry')) {
            WP_CLI::error('Block patterns registry is not available.');
        }

        $category = isset($assoc_args['category']) ? $assoc_args['category'] : '';
        $rows = array();

        // get_all_registered() returns array_values()'d entries — the
        // array key is NOT the slug (that's a common trap). The real
        // slug is $pattern['name'].
        foreach (WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern) {
            $slug = isset($pattern['name']) ? $pattern['name'] : '';
            if (!$slug || 0 !== strpos($slug, 'ajnanda')) {
                continue; // AJNanda's own patterns only, not core's bundled ones.
            }

            $categories = !empty($pattern['categories']) ? (array) $pattern['categories'] : array();

            if (in_array('ajnanda-page-designs', $categories, true)) {
                continue; // page designs have their own command.
            }
            if ($category && !in_array($category, $categories, true)) {
                continue;
            }

            $rows[] = array(
                'slug'       => $slug,
                'title'      => isset($pattern['title']) ? $pattern['title'] : '',
                'categories' => implode(',', $categories),
            );
        }

        \WP_CLI\Utils\format_items($assoc_args['format'] ?? 'table', $rows, array('slug', 'title', 'categories'));
    }
}

/**
 * Inspect and insert AJNanda Page Designs.
 */
class AJNanda_CLI_PageDesign_Command {

    /**
     * List registered AJNanda page designs.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Render output as table, csv, json, or count. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp ajnanda page-design list
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        $designs = function_exists('ajnanda_get_page_designs') ? ajnanda_get_page_designs() : array();
        $rows = array();

        foreach ($designs as $slug => $pattern) {
            $rows[] = array(
                'slug'  => $slug,
                'title' => isset($pattern['title']) ? $pattern['title'] : '',
            );
        }

        \WP_CLI\Utils\format_items($assoc_args['format'] ?? 'table', $rows, array('slug', 'title'));
    }

    /**
     * Insert a page design as a new WordPress page.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Page design slug, e.g. ajnanda/page-home-super-bold.
     *
     * --title=<title>
     * : Title for the new page.
     *
     * [--status=<status>]
     * : Page status: draft (default) or publish.
     *
     * [--color-scheme=<scheme>]
     * : Optional color scheme slug (blue, purple, gold, dark, amber — see `wp ajnanda color-scheme list`).
     *   Defaults to the site's current brand colors, in which case no per-page override is added.
     *
     * ## EXAMPLES
     *
     *     wp ajnanda page-design insert ajnanda/page-home-super-bold --title="Home" --status=draft
     *     wp ajnanda page-design insert ajnanda/page-home-super-bold --title="Home" --color-scheme=gold
     *
     * @when after_wp_load
     */
    public function insert($args, $assoc_args) {
        list($slug) = $args;
        $title         = isset($assoc_args['title']) ? $assoc_args['title'] : __('New Page', 'ajnanda');
        $status        = isset($assoc_args['status']) && 'publish' === $assoc_args['status'] ? 'publish' : 'draft';
        $color_scheme  = isset($assoc_args['color-scheme']) ? sanitize_key($assoc_args['color-scheme']) : '';

        $post_id = ajnanda_insert_page_design($slug, $title, $status, $color_scheme);

        if (is_wp_error($post_id)) {
            WP_CLI::error($post_id->get_error_message());
        }

        WP_CLI::success(sprintf('Created page #%d ("%s") from %s.', $post_id, $title, $slug));
    }
}

/**
 * List, preview, and import AJNanda starter sites.
 */
class AJNanda_CLI_Starter_Command {

    /**
     * List available starter sites.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Render output as table, csv, json, or count. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp ajnanda starter list
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        $rows = array();

        foreach (AJNanda_Starter_Sites::get_all() as $slug => $starter) {
            $rows[] = array(
                'slug'  => $slug,
                'label' => $starter['label'],
                'pages' => count($starter['pages']),
            );
        }

        \WP_CLI\Utils\format_items($assoc_args['format'] ?? 'table', $rows, array('slug', 'label', 'pages'));
    }

    /**
     * Preview what importing a starter site would do, without changing anything.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Starter site slug, e.g. technology.
     *
     * ## EXAMPLES
     *
     *     wp ajnanda starter preview technology
     *
     * @when after_wp_load
     */
    public function preview($args, $assoc_args) {
        list($slug) = $args;
        $report = AJNanda_Starter_Importer::preview($slug);

        if (is_wp_error($report)) {
            WP_CLI::error($report->get_error_message());
        }

        $rows = array();
        foreach ($report as $key => $row) {
            $rows[] = array(
                'key'    => $key,
                'title'  => $row['title'],
                'status' => $row['status'],
            );
        }

        \WP_CLI\Utils\format_items('table', $rows, array('key', 'title', 'status'));
    }

    /**
     * Import a starter site.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Starter site slug, e.g. technology.
     *
     * [--pages=<pages>]
     * : Comma-separated page keys to import, or "all" (default).
     *
     * [--status=<status>]
     * : Page status: draft (default) or publish.
     *
     * [--set-homepage]
     * : Set the imported home page as the site's front page (only if the current front page is unset or was itself created by AJNanda).
     *
     * [--overwrite-menu]
     * : Replace an existing, non-empty primary navigation menu instead of leaving it alone.
     *
     * [--no-menu]
     * : Skip building/updating the primary navigation menu entirely.
     *
     * ## EXAMPLES
     *
     *     wp ajnanda starter import technology
     *     wp ajnanda starter import corporate --pages=home,about,contact --status=publish --set-homepage
     *
     * @when after_wp_load
     */
    public function import($args, $assoc_args) {
        list($slug) = $args;

        $pages = isset($assoc_args['pages']) && 'all' !== $assoc_args['pages']
            ? array_map('trim', explode(',', $assoc_args['pages']))
            : 'all';

        $results = AJNanda_Starter_Importer::import($slug, array(
            'pages'          => $pages,
            'status'         => (isset($assoc_args['status']) && 'publish' === $assoc_args['status']) ? 'publish' : 'draft',
            'set_homepage'   => isset($assoc_args['set-homepage']),
            'create_menu'    => !isset($assoc_args['no-menu']),
            'overwrite_menu' => isset($assoc_args['overwrite-menu']),
        ));

        if (is_wp_error($results)) {
            WP_CLI::error($results->get_error_message());
        }

        foreach ($results as $key => $row) {
            WP_CLI::log(sprintf('%s: %s%s', $key, $row['status'], !empty($row['post_id']) ? " (#{$row['post_id']})" : ''));
        }

        WP_CLI::success(sprintf('Starter site "%s" import complete.', $slug));
    }
}

/**
 * Inspect and set the site-wide AJNanda color scheme.
 */
class AJNanda_CLI_ColorScheme_Command {

    /**
     * List available color schemes.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Render output as table, csv, json, or count. Default: table.
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        $active = ajnanda_get_active_color_scheme_slug();
        $rows = array();

        foreach (ajnanda_get_color_schemes() as $slug => $scheme) {
            $rows[] = array(
                'slug'   => $slug,
                'label'  => $scheme['label'],
                'swatch' => $scheme['swatch'],
                'active' => $slug === $active ? 'yes' : '',
            );
        }

        \WP_CLI\Utils\format_items($assoc_args['format'] ?? 'table', $rows, array('slug', 'label', 'swatch', 'active'));
    }

    /**
     * Fill in the theme's 4 real color settings (Primary/Primary Hover/
     * Secondary/Accent — Appearance → Customize → Colors) from a named
     * preset. Equivalent to clicking the preset swatch in the Customizer.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Color scheme slug: blue, purple, gold, dark, or amber.
     *
     * @when after_wp_load
     */
    public function set($args, $assoc_args) {
        list($slug) = $args;
        $schemes = ajnanda_get_color_schemes();

        if (!isset($schemes[$slug])) {
            WP_CLI::error(sprintf('Unknown color scheme "%s". Available: %s', $slug, implode(', ', array_keys($schemes))));
        }

        $scheme = $schemes[$slug];
        set_theme_mod('theme_primary_color', $scheme['primary']);
        set_theme_mod('theme_primary_dark_color', $scheme['primary_dark']);
        set_theme_mod('theme_secondary_color', $scheme['secondary']);
        set_theme_mod('theme_accent_color', $scheme['accent']);

        WP_CLI::success(sprintf('Primary/Secondary/Accent colors set to the "%s" preset.', $slug));
    }
}

/**
 * See docs/site-kits.md.
 */
class AJNanda_CLI_FontPairing_Command {

    /**
     * List available font pairings.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Render output as table, csv, json, or count. Default: table.
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        $active = ajnanda_get_active_font_pairing_slug();
        $rows = array();

        foreach (ajnanda_get_font_pairings() as $slug => $pairing) {
            $rows[] = array(
                'slug'    => $slug,
                'label'   => $pairing['label'],
                'heading' => $pairing['heading_font'],
                'body'    => $pairing['body_font'],
                'active'  => $slug === $active ? 'yes' : '',
            );
        }

        \WP_CLI\Utils\format_items($assoc_args['format'] ?? 'table', $rows, array('slug', 'label', 'heading', 'body', 'active'));
    }

    /**
     * Set the site-wide font pairing (Appearance → Customize → Typography).
     * Equivalent to picking it in that control.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Font pairing slug — see `wp ajnanda font-pairing list`.
     *
     * @when after_wp_load
     */
    public function set($args, $assoc_args) {
        list($slug) = $args;
        $pairings = ajnanda_get_font_pairings();

        if (!isset($pairings[$slug])) {
            WP_CLI::error(sprintf('Unknown font pairing "%s". Available: %s', $slug, implode(', ', array_keys($pairings))));
        }

        set_theme_mod('theme_font_pairing', $slug);

        WP_CLI::success(sprintf('Font pairing set to "%s".', $slug));
    }
}

/**
 * See docs/site-kits.md.
 */
class AJNanda_CLI_SiteKit_Command {

    /**
     * List available site kits (color scheme + font pairing bundles).
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Render output as table, csv, json, or count. Default: table.
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        $active = ajnanda_get_active_site_kit_slug();
        $rows = array();

        foreach (ajnanda_get_site_kits() as $slug => $kit) {
            $rows[] = array(
                'slug'         => $slug,
                'label'        => $kit['label'],
                'color_scheme' => $kit['color_scheme'],
                'font_pairing' => $kit['font_pairing'],
                'active'       => $slug === $active ? 'yes' : '',
            );
        }

        \WP_CLI\Utils\format_items($assoc_args['format'] ?? 'table', $rows, array('slug', 'label', 'color_scheme', 'font_pairing', 'active'));
    }

    /**
     * Apply a site kit: sets the 4 color settings and the font pairing
     * together. Equivalent to clicking its "Quick Kits" swatch in the
     * Customizer.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Site kit slug — see `wp ajnanda site-kit list`.
     *
     * @when after_wp_load
     */
    public function set($args, $assoc_args) {
        list($slug) = $args;
        $kits = ajnanda_get_site_kits();

        if (!isset($kits[$slug])) {
            WP_CLI::error(sprintf('Unknown site kit "%s". Available: %s', $slug, implode(', ', array_keys($kits))));
        }

        $kit     = $kits[$slug];
        $schemes = ajnanda_get_color_schemes();

        if (!isset($schemes[$kit['color_scheme']])) {
            WP_CLI::error(sprintf('Site kit "%s" references unknown color scheme "%s".', $slug, $kit['color_scheme']));
        }

        $scheme = $schemes[$kit['color_scheme']];
        set_theme_mod('theme_primary_color', $scheme['primary']);
        set_theme_mod('theme_primary_dark_color', $scheme['primary_dark']);
        set_theme_mod('theme_secondary_color', $scheme['secondary']);
        set_theme_mod('theme_accent_color', $scheme['accent']);
        set_theme_mod('theme_font_pairing', $kit['font_pairing']);

        WP_CLI::success(sprintf('Site kit "%s" applied (%s colors + %s fonts).', $slug, $kit['color_scheme'], $kit['font_pairing']));
    }
}

WP_CLI::add_command('ajnanda pattern', 'AJNanda_CLI_Pattern_Command');
WP_CLI::add_command('ajnanda page-design', 'AJNanda_CLI_PageDesign_Command');
WP_CLI::add_command('ajnanda starter', 'AJNanda_CLI_Starter_Command');
WP_CLI::add_command('ajnanda color-scheme', 'AJNanda_CLI_ColorScheme_Command');
WP_CLI::add_command('ajnanda font-pairing', 'AJNanda_CLI_FontPairing_Command');
WP_CLI::add_command('ajnanda site-kit', 'AJNanda_CLI_SiteKit_Command');
