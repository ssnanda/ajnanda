<?php
/**
 * AJNanda_Starter_Importer — safe, idempotent starter-site import engine.
 *
 * Used by both the Starter Sites admin screen (inc/admin/class-ajnanda-admin.php)
 * and the `wp ajnanda starter` CLI commands (inc/cli/class-ajnanda-cli.php),
 * so the two stay in sync automatically and there is exactly one place that
 * touches the database for starter-site imports.
 *
 * Safety rules (see docs/starter-sites.md for the reasoning):
 *  - Every page created is tagged with post meta so re-running an import
 *    never creates duplicates — it reports the page as already imported.
 *  - A manifest page whose intended slug is already used by unrelated
 *    content is never overwritten; the new page gets a suffixed slug
 *    instead.
 *  - The primary nav menu is only built/replaced if that location is
 *    currently empty, unless the caller explicitly opts into overwriting it.
 *  - The site homepage/posts page are only changed if the caller explicitly
 *    asks for it, and never if the current homepage is unrelated content
 *    the site owner set up themselves.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJNanda_Starter_Importer {

    const META_STARTER = '_ajnanda_starter_site';
    const META_DESIGN   = '_ajnanda_page_design';
    const META_KEY       = '_ajnanda_starter_page_key';

    /**
     * Dry-run: report what an import would do without changing anything.
     *
     * @param string $slug Starter site slug.
     * @return array<string,array>|WP_Error page key => {status,title,post_id?}
     */
    public static function preview($slug) {
        $manifest = AJNanda_Starter_Sites::get($slug);

        if (!$manifest) {
            return new WP_Error('ajnanda_unknown_starter', __('Unknown starter site.', 'ajnanda'));
        }

        $report = array();

        foreach ($manifest['pages'] as $page) {
            $existing = self::find_imported_page($slug, $page['key']);

            if ($existing) {
                $report[$page['key']] = array(
                    'status'  => 'already_imported',
                    'title'   => $page['title'],
                    'post_id' => $existing->ID,
                );
                continue;
            }

            $conflict = get_page_by_path($page['slug']);

            if ($conflict && self::owning_starter($conflict->ID) !== $slug) {
                $report[$page['key']] = array(
                    'status'  => 'slug_conflict',
                    'title'   => $page['title'],
                    'post_id' => $conflict->ID,
                );
                continue;
            }

            $report[$page['key']] = array(
                'status' => 'create',
                'title'  => $page['title'],
            );
        }

        return $report;
    }

    /**
     * Import (a subset of) a starter site's pages.
     *
     * @param string $slug Starter site slug.
     * @param array  $args {
     *     @type string|string[] pages          Page keys to import, or 'all'.
     *     @type string          status          'draft' (default) or 'publish'.
     *     @type bool            set_homepage    Set the imported home page as the site front page. Default false.
     *     @type bool            create_menu     Build/extend the primary nav menu. Default true.
     *     @type bool            overwrite_menu  Replace an existing non-empty primary menu. Default false.
     * }
     * @return array<string,array>|WP_Error page key => {status,post_id?,message?}
     */
    public static function import($slug, array $args = array()) {
        $manifest = AJNanda_Starter_Sites::get($slug);

        if (!$manifest) {
            return new WP_Error('ajnanda_unknown_starter', __('Unknown starter site.', 'ajnanda'));
        }

        $args = wp_parse_args($args, array(
            'pages'          => 'all',
            'status'         => 'draft',
            'set_homepage'   => false,
            'create_menu'    => true,
            'overwrite_menu' => false,
            'apply_kit'      => false,
        ));

        if (!in_array($args['status'], array('draft', 'publish'), true)) {
            $args['status'] = 'draft';
        }

        $selected_keys = ('all' === $args['pages'])
            ? wp_list_pluck($manifest['pages'], 'key')
            : array_map('sanitize_key', (array) $args['pages']);

        $results     = array();
        $created_ids = array();

        foreach ($manifest['pages'] as $page) {
            if (!in_array($page['key'], $selected_keys, true)) {
                continue;
            }

            $existing = self::find_imported_page($slug, $page['key']);

            if ($existing) {
                $results[$page['key']]     = array('status' => 'skipped_existing', 'post_id' => $existing->ID);
                $created_ids[$page['key']] = $existing->ID;
                continue;
            }

            $post_name = $page['slug'];
            $conflict  = get_page_by_path($post_name);

            if ($conflict && self::owning_starter($conflict->ID) !== $slug) {
                // Never take over unrelated content's URL — suffix instead.
                $post_name = $page['slug'] . '-' . $slug;
            }

            $content = function_exists('ajnanda_get_pattern_content')
                ? ajnanda_get_pattern_content($page['page_design'])
                : '';

            $post_id = wp_insert_post(array(
                'post_type'    => 'page',
                'post_title'   => $page['title'],
                'post_name'    => $post_name,
                'post_content' => $content,
                'post_status'  => $args['status'],
            ), true);

            if (is_wp_error($post_id)) {
                $results[$page['key']] = array('status' => 'error', 'message' => $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, self::META_STARTER, $slug);
            update_post_meta($post_id, self::META_DESIGN, $page['page_design']);
            update_post_meta($post_id, self::META_KEY, $page['key']);

            $results[$page['key']]     = array('status' => 'created', 'post_id' => $post_id);
            $created_ids[$page['key']] = $post_id;
        }

        if ($args['create_menu'] && !empty($manifest['menu'])) {
            self::build_menu($manifest, $created_ids, (bool) $args['overwrite_menu']);
        }

        if ($args['set_homepage']) {
            self::maybe_set_homepage($manifest, $created_ids);
        }

        // Opt-in only: unlike everything else this method does, this
        // changes site-wide settings (colors/fonts), not just this
        // starter's own pages — so it's never on by default, only when
        // explicitly requested (admin screen checkbox / --apply-kit).
        if ($args['apply_kit'] && !empty($manifest['site_kit']) && function_exists('ajnanda_apply_site_kit')) {
            ajnanda_apply_site_kit($manifest['site_kit']);
        }

        return $results;
    }

    /**
     * @param int $post_id
     * @return string Starter slug that owns this post, or '' if none.
     */
    private static function owning_starter($post_id) {
        return (string) get_post_meta($post_id, self::META_STARTER, true);
    }

    /**
     * @param string $starter_slug
     * @param string $page_key
     * @return WP_Post|null
     */
    private static function find_imported_page($starter_slug, $page_key) {
        $query = new WP_Query(array(
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
            'meta_query'     => array(
                array('key' => self::META_STARTER, 'value' => $starter_slug),
                array('key' => self::META_KEY, 'value' => $page_key),
            ),
        ));

        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Build (or extend) the primary nav menu from the pages that were just
     * created/found. Never touches a primary menu that already has items
     * unless $overwrite is explicitly true.
     */
    private static function build_menu(array $manifest, array $created_ids, $overwrite) {
        $locations         = get_theme_mod('nav_menu_locations', array());
        $existing_menu_id  = isset($locations['primary']) ? (int) $locations['primary'] : 0;

        if ($existing_menu_id && !$overwrite) {
            $existing_items = wp_get_nav_menu_items($existing_menu_id);
            if (!empty($existing_items)) {
                return;
            }
        }

        $menu_name = sprintf(__('AJNanda: %s', 'ajnanda'), $manifest['label']);
        $menu      = wp_get_nav_menu_object($menu_name);

        if ($menu) {
            $menu_id = $menu->term_id;
            if ($overwrite) {
                foreach ((array) wp_get_nav_menu_items($menu_id) as $item) {
                    wp_delete_post($item->ID, true);
                }
            }
        } else {
            $menu_id = wp_create_nav_menu($menu_name);
            if (is_wp_error($menu_id)) {
                return;
            }
        }

        $menu_pages = !empty($manifest['menu']['pages'])
            ? $manifest['menu']['pages']
            : wp_list_pluck($manifest['pages'], 'key');

        $position = 1;
        foreach ($menu_pages as $key) {
            if (empty($created_ids[$key])) {
                continue;
            }
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title'     => get_the_title($created_ids[$key]),
                'menu-item-object-id' => $created_ids[$key],
                'menu-item-object'    => 'page',
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => $position++,
            ));
        }

        $locations['primary'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    /**
     * Set the imported home/posts pages as the site front page — only if
     * explicitly requested AND the current front page isn't unrelated,
     * presumably-intentional content.
     */
    private static function maybe_set_homepage(array $manifest, array $created_ids) {
        if (empty($manifest['home_page_key']) || empty($created_ids[$manifest['home_page_key']])) {
            return;
        }

        $current_front_id = (int) get_option('page_on_front');
        if ($current_front_id && !self::owning_starter($current_front_id)) {
            return;
        }

        update_option('show_on_front', 'page');
        update_option('page_on_front', $created_ids[$manifest['home_page_key']]);

        if (!empty($manifest['posts_page_key']) && !empty($created_ids[$manifest['posts_page_key']])) {
            $current_posts_id = (int) get_option('page_for_posts');
            if (!$current_posts_id || self::owning_starter($current_posts_id)) {
                update_option('page_for_posts', $created_ids[$manifest['posts_page_key']]);
            }
        }
    }
}
