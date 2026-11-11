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
        .ajn-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,170px));gap:8px;margin:12px 0 8px}
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
    <script>
        // admin_notices output lands above the page title; move tiles/chips under it.
        (function () {
            var h = document.querySelector('.wp-header-end'), els = document.querySelectorAll('.ajn-tiles, .ajn-chips');
            if (!h || !els.length) { return; }
            var w = document.createElement('div');
            els.forEach(function (e) { w.appendChild(e); });
            h.after(w);
        })();
    </script>
    <?php
}
add_action('admin_notices', 'ajnanda_tiles_render', 20);

/* ---- List columns: ID, image, SEO, words, updated. Drops Author, Tags, Comments. ---- */

/** Columns removed from every handled list screen. */
function ajnanda_tiles_junk_columns() {
    return array('author', 'tags', 'comments');
}

function ajnanda_tiles_columns($cols, $pt) {
    $new = array();
    foreach ($cols as $key => $label) {
        if (in_array($key, ajnanda_tiles_junk_columns(), true)) {
            continue;
        }
        if ('date' === $key) {
            if (get_post_type_object($pt)->public) {
                $new['ajn_seo'] = __('SEO', 'ajnanda');
            }
            $new['ajn_words'] = __('Words', 'ajnanda');
            $new['ajn_mod']   = __('Updated', 'ajnanda');
        }
        $new[$key] = $label;
        if ('cb' === $key) {
            $new['ajn_id'] = __('ID', 'ajnanda');
            if (post_type_supports($pt, 'thumbnail')) {
                $new['ajn_thumb'] = __('Image', 'ajnanda');
            }
        }
    }
    return $new;
}

function ajnanda_tiles_register_columns() {
    foreach (get_post_types(array('show_ui' => true)) as $pt) {
        if ('attachment' === $pt) {
            continue;
        }
        add_filter("manage_{$pt}_posts_columns", function ($cols) use ($pt) {
            return ajnanda_tiles_columns($cols, $pt);
        }, 20);
        add_filter("manage_edit-{$pt}_sortable_columns", function ($cols) {
            $cols['ajn_id']  = array('ID', false);
            $cols['ajn_mod'] = array('modified', false);
            return $cols;
        });
        add_action("manage_{$pt}_posts_custom_column", 'ajnanda_tiles_column_cell', 10, 2);
    }
}
add_action('admin_init', 'ajnanda_tiles_register_columns');

function ajnanda_tiles_column_cell($column, $post_id) {
    switch ($column) {
        case 'ajn_id':
            printf('<button type="button" class="ajn-id" data-id="%1$d" title="%2$s">#%1$d</button>', (int) $post_id, esc_attr__('Click to copy ID', 'ajnanda'));
            break;
        case 'ajn_thumb':
            echo has_post_thumbnail($post_id)
                ? get_the_post_thumbnail($post_id, array(96, 54), array('class' => 'ajn-col-thumb'))
                : '<span class="ajn-col-none">&mdash;</span>';
            break;
        case 'ajn_seo':
            $len = strlen(trim((string) get_post_meta($post_id, '_ajnanda_seo_description', true)));
            if (!$len) {
                echo '<span class="ajn-col-bad">&mdash;</span>';
            } elseif ($len < 70 || $len > 170) {
                echo '<span class="ajn-col-warn" title="' . esc_attr__('Aim for 70-170 characters', 'ajnanda') . '">! ' . (int) $len . '</span>';
            } else {
                echo '<span class="ajn-col-ok">&#10003; ' . (int) $len . '</span>';
            }
            break;
        case 'ajn_words':
            echo esc_html(number_format_i18n(str_word_count(wp_strip_all_tags((string) get_post_field('post_content', $post_id)))));
            break;
        case 'ajn_mod':
            $ts = (int) get_post_modified_time('U', true, $post_id);
            printf('<span title="%s">%s</span>', esc_attr(get_post_modified_time('Y/m/d g:i a', false, $post_id)), esc_html(sprintf(__('%s ago', 'ajnanda'), human_time_diff($ts))));
            break;
    }
}

function ajnanda_tiles_column_styles() {
    if (!ajnanda_tiles_post_type()) {
        return;
    }
    echo '<style>
        .column-ajn_id{width:72px}
        .ajn-id{font:600 12px/1 ui-monospace,SFMono-Regular,Menlo,monospace;padding:4px 8px;border:1px solid #dcdcde;border-radius:999px;background:#f6f7f7;color:#3c434a;cursor:pointer}
        .ajn-id:hover{border-color:#2271b1;color:#2271b1;background:#fff}
        .ajn-id.is-copied{background:#16a34a;border-color:#16a34a;color:#fff}
        .column-ajn_thumb{width:104px}.ajn-col-thumb{display:block;width:96px;height:54px;object-fit:cover;border-radius:4px}
        .column-ajn_seo,.column-ajn_words{width:72px}.column-ajn_mod{width:110px}
        .ajn-col-ok{color:#16a34a;font-weight:600}.ajn-col-warn{color:#d97706;font-weight:600}.ajn-col-bad{color:#dc2626;font-weight:600}.ajn-col-none{color:#a7aaad}
    </style>
    <script>
        document.addEventListener("click", function (e) {
            var b = e.target.closest(".ajn-id");
            if (!b || !navigator.clipboard) { return; }
            navigator.clipboard.writeText(b.dataset.id).then(function () {
                var t = b.textContent; b.textContent = "Copied"; b.classList.add("is-copied");
                setTimeout(function () { b.textContent = t; b.classList.remove("is-copied"); }, 900);
            });
        });
    </script>';
}
add_action('admin_head-edit.php', 'ajnanda_tiles_column_styles');
