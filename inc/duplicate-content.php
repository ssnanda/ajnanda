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

    $actions['ajnanda_duplicate'] = '<a href="' . esc_url($url) . '" title="Duplicate this item">Duplicate</a>';

    return $actions;
}
add_filter('post_row_actions', 'ajnanda_duplicate_content_action_link', 10, 2);
add_filter('page_row_actions', 'ajnanda_duplicate_content_action_link', 10, 2);

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
