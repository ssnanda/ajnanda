<?php
/**
 * AJNanda Duplicate Posts and Pages
 *
 * Adds a "Duplicate" action under Pages and Posts in wp-admin.
 *
 * Put this file at:
 *   inc/duplicate-content.php
 *
 * Then add this to functions.php:
 *   require_once get_template_directory() . '/inc/duplicate-content.php';
 */

if (!defined('ABSPATH')) {
    exit;
}

function ajnanda_duplicate_content_action_link($actions, $post) {
    if (!current_user_can('edit_posts')) {
        return $actions;
    }

    if (!in_array($post->post_type, array('post', 'page'), true)) {
        return $actions;
    }

    $url = wp_nonce_url(
        admin_url('admin.php?action=ajnanda_duplicate_content&post=' . absint($post->ID)),
        'ajnanda_duplicate_content_' . absint($post->ID)
    );

    // Inline copy-icon SVG (Feather "copy", MIT) rather than guessing at a
    // Dashicon name — styled as a small pill button by list-tables.css
    // (targets .row-actions .ajnanda_duplicate a) instead of a bare text link.
    $icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>';

    $actions['ajnanda_duplicate'] = '<a href="' . esc_url($url) . '" title="Duplicate this item">' . $icon . 'Duplicate</a>';

    return $actions;
}
add_filter('post_row_actions', 'ajnanda_duplicate_content_action_link', 10, 2);
add_filter('page_row_actions', 'ajnanda_duplicate_content_action_link', 10, 2);

// Modernized bulk-action/filter/search controls + the Duplicate pill button
// above — only loaded on the Posts and Pages list screens they affect.
add_action('admin_enqueue_scripts', 'ajnanda_enqueue_list_table_assets');
function ajnanda_enqueue_list_table_assets($hook_suffix) {
    if ('edit.php' !== $hook_suffix) {
        return;
    }

    $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'post';
    if (!in_array($post_type, array('post', 'page'), true)) {
        return;
    }

    wp_enqueue_style(
        'ajnanda-list-tables',
        get_template_directory_uri() . '/inc/admin/assets/list-tables.css',
        array(),
        ajnanda_asset_version('inc/admin/assets/list-tables.css')
    );
}

function ajnanda_duplicate_content() {
    if (empty($_GET['post'])) {
        wp_die('Missing post ID.');
    }

    $post_id = absint($_GET['post']);

    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ajnanda_duplicate_content_' . $post_id)) {
        wp_die('Invalid duplicate request.');
    }

    $post = get_post($post_id);

    if (!$post || !in_array($post->post_type, array('post', 'page'), true)) {
        wp_die('Invalid post.');
    }

    if (!current_user_can('edit_post', $post_id)) {
        wp_die('You do not have permission to duplicate this item.');
    }

    $current_user = wp_get_current_user();

    $new_post_args = array(
        'post_author'           => $current_user->ID,
        'post_content'          => $post->post_content,
        'post_content_filtered' => $post->post_content_filtered,
        'post_title'            => $post->post_title . ' Copy',
        'post_excerpt'          => $post->post_excerpt,
        'post_status'           => 'draft',
        'post_type'             => $post->post_type,
        'comment_status'        => $post->comment_status,
        'ping_status'           => $post->ping_status,
        'post_password'         => $post->post_password,
        'post_parent'           => $post->post_parent,
        'menu_order'            => $post->menu_order,
        'to_ping'               => $post->to_ping,
        'pinged'                => $post->pinged,
    );

    $new_post_id = wp_insert_post($new_post_args, true);

    if (is_wp_error($new_post_id)) {
        wp_die($new_post_id->get_error_message());
    }

    $taxonomies = get_object_taxonomies($post->post_type);
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
        if (!is_wp_error($terms)) {
            wp_set_object_terms($new_post_id, $terms, $taxonomy, false);
        }
    }

    $meta = get_post_meta($post_id);
    foreach ($meta as $meta_key => $meta_values) {
        if ('_wp_old_slug' === $meta_key) {
            continue;
        }

        foreach ($meta_values as $meta_value) {
            add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
        }
    }

    wp_safe_redirect(admin_url('post.php?action=edit&post=' . absint($new_post_id)));
    exit;
}
add_action('admin_action_ajnanda_duplicate_content', 'ajnanda_duplicate_content');
