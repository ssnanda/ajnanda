<?php
/**
 * Dashboard-style tiles + chips on every wp-admin list screen (Posts, Pages, other UI post types).
 *
 * Replaces core's status links ("All | Published | Draft"), the month dropdown and the category
 * dropdown with clickable tiles/chips. Core filters used (no CSS hiding): views_edit-{type},
 * disable_months_dropdown, disable_categories_dropdown. Bulk actions and search are untouched.
 * The "audit" tiles use the `ajn_view` query var, applied in pre_get_posts on these screens only.
 * Counts are live queries (ids only); nothing is stored.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @return string Post type of the current list screen, or '' when not a list screen we handle. */
function ajnanda_tiles_post_type() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || 'edit' !== $screen->base || empty($screen->post_type)) {
        return '';
    }
    $obj = get_post_type_object($screen->post_type);
    return ($obj && $obj->show_ui) ? $screen->post_type : '';
}

/** Query args for each audit filter. */
function ajnanda_tiles_view_args($view) {
    switch ($view) {
        case 'no_image':
            return array('meta_query' => array(array('key' => '_thumbnail_id', 'compare' => 'NOT EXISTS')));
        case 'no_seo':
            return array('meta_query' => array(
                'relation' => 'OR',
                array('key' => '_ajnanda_seo_description', 'compare' => 'NOT EXISTS'),
                array('key' => '_ajnanda_seo_description', 'value' => '', 'compare' => '='),
            ));
        case 'recent':
            return array('date_query' => array(array('after' => '30 days ago')));
    }
    return array();
}

/** One color per tile, same palette as the AJCore Media Library and Files tiles. */
function ajnanda_tiles_color($key) {
    $colors = array(
        'all'      => '#2563eb',
        'publish'  => '#16a34a',
        'draft'    => '#d97706',
        'pending'  => '#0891b2',
        'future'   => '#7c3aed',
        'private'  => '#64748b',
        'trash'    => '#dc2626',
        'mine'     => '#4f46e5',
        'recent'   => '#0d9488',
        'no_image' => '#ea580c',
        'no_seo'   => '#ea580c',
    );
    return isset($colors[$key]) ? $colors[$key] : '#64748b';
}

function ajnanda_tiles_count($post_type, $extra = array(), $status = 'publish') {
    $q = new WP_Query(array_merge(array(
        'post_type'              => $post_type,
        'post_status'            => $status,
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ), $extra));
    return (int) $q->found_posts;
}

/** Apply ?ajn_view= to the main list query. */
function ajnanda_tiles_filter_query($query) {
    if (!is_admin() || !$query->is_main_query() || empty($_GET['ajn_view']) || !ajnanda_tiles_post_type()) { // phpcs:ignore WordPress.Security.NonceVerification
        return;
    }
    foreach (ajnanda_tiles_view_args(sanitize_key(wp_unslash($_GET['ajn_view']))) as $key => $val) { // phpcs:ignore WordPress.Security.NonceVerification
        $query->set($key, $val);
    }
}
add_action('pre_get_posts', 'ajnanda_tiles_filter_query');

/** Core status links are replaced by tiles. Also drops views added by other plugins. */
function ajnanda_tiles_register_filters() {
    foreach (get_post_types(array('show_ui' => true)) as $pt) {
        add_filter("views_edit-{$pt}", '__return_empty_array');
    }
}
add_action('admin_init', 'ajnanda_tiles_register_filters');
add_filter('disable_months_dropdown', function ($disable, $post_type) {
    return ($disable || (is_admin() && ajnanda_tiles_post_type())) ? true : $disable;
}, 10, 2);
add_filter('disable_categories_dropdown', function ($disable, $post_type) {
    return ($disable || (is_admin() && ajnanda_tiles_post_type())) ? true : $disable;
}, 10, 2);

function ajnanda_tiles_render() {
    $pt = ajnanda_tiles_post_type();
    if (!$pt || !current_user_can(get_post_type_object($pt)->cap->edit_posts)) {
        return;
    }
    $base   = add_query_arg('post_type', $pt, admin_url('edit.php'));
    $counts = wp_count_posts($pt);
    $get    = function ($k) { return isset($_GET[$k]) ? sanitize_text_field(wp_unslash($_GET[$k])) : ''; }; // phpcs:ignore WordPress.Security.NonceVerification
    $cur    = array('view' => $get('ajn_view'), 'status' => $get('post_status'), 'cat' => $get('cat'), 'm' => $get('m'), 'author' => $get('author'));
    $any    = array_filter($cur);

    $tiles = array();
    $total = 0;
    foreach (get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $name => $st) {
        $n = isset($counts->$name) ? (int) $counts->$name : 0;
        if ('trash' !== $name) { $total += $n; }
        if ($n) {
            $tiles[] = array($st->label, $n, add_query_arg('post_status', $name, $base), $cur['status'] === $name && !$cur['view'], $name);
        }
    }
    array_unshift($tiles, array(__('All', 'ajnanda'), $total, $base, !$any, 'all'));
    $mine = ajnanda_tiles_count($pt, array('author' => get_current_user_id()), array('publish', 'draft', 'pending', 'future', 'private'));
    $tiles[] = array(__('Mine', 'ajnanda'), $mine, add_query_arg('author', get_current_user_id(), $base), (string) get_current_user_id() === $cur['author'], 'mine');
    $tiles[] = array(__('Last 30 days', 'ajnanda'), ajnanda_tiles_count($pt, ajnanda_tiles_view_args('recent')), add_query_arg('ajn_view', 'recent', $base), 'recent' === $cur['view'], 'recent');
    if (post_type_supports($pt, 'thumbnail')) {
        $tiles[] = array(__('No image', 'ajnanda'), ajnanda_tiles_count($pt, ajnanda_tiles_view_args('no_image')), add_query_arg('ajn_view', 'no_image', $base), 'no_image' === $cur['view'], 'no_image');
    }
    if (get_post_type_object($pt)->public) {
        $tiles[] = array(__('No SEO desc', 'ajnanda'), ajnanda_tiles_count($pt, ajnanda_tiles_view_args('no_seo')), add_query_arg('ajn_view', 'no_seo', $base), 'no_seo' === $cur['view'], 'no_seo');
    }

    echo '<div class="ajn-tiles">';
    foreach ($tiles as $t) {
        list($label, $n, $url, $active, $key) = $t;
        if (0 === (int) $n && !$active && 'all' !== $key) {
            continue; // Empty tiles are hidden.
        }
        printf(
            '<a class="ajn-tile%s" style="--c:%s" href="%s"><b>%s</b><span>%s</span></a>',
            $active ? ' is-active' : '',
            esc_attr(ajnanda_tiles_color($key)),
            esc_url($url),
            esc_html(number_format_i18n($n)),
            esc_html($label)
        );
    }
    echo '</div>';

    // Chips: categories (types that use them) and recent months.
    $chips = array();
    if (is_object_in_taxonomy($pt, 'category')) {
        $terms = get_terms(array('taxonomy' => 'category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 14));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $chips[] = array($term->name . ' ' . (int) $term->count, add_query_arg('cat', $term->term_id, $base), (string) $term->term_id === $cur['cat']);
            }
        }
    }
    global $wpdb;
    $months = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT YEAR(post_date) y, MONTH(post_date) m FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft' AND post_status != 'trash' ORDER BY post_date DESC LIMIT 12",
        $pt
    ));
    $mchips = array();
    foreach ((array) $months as $r) {
        $val      = sprintf('%04d%02d', $r->y, $r->m);
        $mchips[] = array(date_i18n('M Y', mktime(0, 0, 0, (int) $r->m, 1, (int) $r->y)), add_query_arg('m', $val, $base), $val === $cur['m']);
    }
    foreach (array($chips, $mchips) as $row) {
        if (!$row) { continue; }
        echo '<div class="ajn-chips">';
        foreach ($row as $c) {
            printf('<a class="ajn-chip%s" href="%s">%s</a>', $c[2] ? ' is-active' : '', esc_url($c[1]), esc_html($c[0]));
        }
        echo '</div>';
    }
    ?>
    <style>
        .ajn-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:8px;margin:12px 0 8px}
        .ajn-tile{display:flex;flex-direction:column;gap:2px;padding:10px 14px;background:color-mix(in srgb,var(--c) 12%,#fff);border:1px solid color-mix(in srgb,var(--c) 35%,#fff);border-left:5px solid var(--c);border-radius:4px;text-decoration:none;color:#1d2327}
        .ajn-tile b{font-size:24px;line-height:1.1;font-weight:700;color:var(--c)}
        .ajn-tile span{font-size:12px;font-weight:600;color:#3c434a}
        .ajn-tile:hover{background:color-mix(in srgb,var(--c) 22%,#fff);color:#1d2327}
        .ajn-tile.is-active{background:var(--c);border-color:var(--c)}
        .ajn-tile.is-active b,.ajn-tile.is-active span{color:#fff}
        .ajn-chips{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 8px}
        .ajn-chip{padding:3px 10px;background:#fff;border:1px solid #dcdcde;border-radius:999px;font-size:12px;text-decoration:none;color:#3c434a}
        .ajn-chip:hover{border-color:#2271b1;color:#2271b1}
        .ajn-chip.is-active{background:#2271b1;border-color:#2271b1;color:#fff}
    </style>
    <?php
}
add_action('admin_notices', 'ajnanda_tiles_render', 20);
