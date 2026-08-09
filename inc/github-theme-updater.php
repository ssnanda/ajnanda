<?php
/**
 * AJNanda GitHub Theme Updater + Admin Cache Tools
 *
 * Put this file at:
 *   inc/github-theme-updater.php
 *
 * functions.php:
 *   require_once get_template_directory() . '/inc/github-theme-updater.php';
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AJNANDA_GITHUB_OWNER', 'ssnanda');
define('AJNANDA_GITHUB_REPO', 'ajnanda');
define('AJNANDA_GITHUB_ASSET_PREFIX', 'ajnanda-');
define('AJNANDA_GITHUB_ASSET_SUFFIX', '.zip');

function ajnanda_updater_theme() {
    return wp_get_theme(get_stylesheet());
}

function ajnanda_updater_theme_slug() {
    return get_stylesheet();
}

function ajnanda_updater_current_version() {
    return ajnanda_updater_theme()->get('Version');
}

function ajnanda_updater_clean_version($version) {
    return ltrim((string) $version, 'vV');
}

function ajnanda_updater_latest_release_url() {
    return sprintf(
        'https://api.github.com/repos/%s/%s/releases/latest',
        rawurlencode(AJNANDA_GITHUB_OWNER),
        rawurlencode(AJNANDA_GITHUB_REPO)
    );
}

function ajnanda_updater_get_latest_release($force = false) {
    $cache_key = 'ajnanda_github_latest_release';

    if (!$force) {
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }
    }

    $headers = array(
        'User-Agent' => 'AJNanda-WordPress-Theme-Updater',
        'Accept'     => 'application/vnd.github+json',
    );

    if (defined('AJNANDA_GITHUB_TOKEN') && AJNANDA_GITHUB_TOKEN) {
        $headers['Authorization'] = 'Bearer ' . AJNANDA_GITHUB_TOKEN;
    }

    $response = wp_remote_get(ajnanda_updater_latest_release_url(), array(
        'timeout' => 20,
        'headers' => $headers,
    ));

    if (is_wp_error($response)) {
        return array('_ajnanda_error' => $response->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if (200 !== $code) {
        return array(
            '_ajnanda_error' => 'GitHub API returned HTTP ' . $code,
            '_ajnanda_body'  => $body,
        );
    }

    $release = json_decode($body, true);

    if (!is_array($release) || empty($release['tag_name'])) {
        return array(
            '_ajnanda_error' => 'Invalid GitHub release response.',
            '_ajnanda_body'  => $body,
        );
    }

    set_transient($cache_key, $release, HOUR_IN_SECONDS);

    return $release;
}

function ajnanda_updater_find_zip_asset($release, $version) {
    if (empty($release['assets']) || !is_array($release['assets'])) {
        return false;
    }

    $expected_name = AJNANDA_GITHUB_ASSET_PREFIX . $version . AJNANDA_GITHUB_ASSET_SUFFIX;

    foreach ($release['assets'] as $asset) {
        if (!empty($asset['name']) && !empty($asset['browser_download_url']) && $expected_name === $asset['name']) {
            return $asset;
        }
    }

    return false;
}

function ajnanda_updater_update_payload($force = false) {
    $theme_slug = ajnanda_updater_theme_slug();
    $current_version = ajnanda_updater_current_version();
    $release = ajnanda_updater_get_latest_release($force);

    if (!$release || !empty($release['_ajnanda_error'])) {
        return false;
    }

    $latest_version = ajnanda_updater_clean_version($release['tag_name']);

    if (!version_compare($latest_version, $current_version, '>')) {
        return false;
    }

    $asset = ajnanda_updater_find_zip_asset($release, $latest_version);

    if (!$asset) {
        return false;
    }

    return array(
        'theme'        => $theme_slug,
        'new_version'  => $latest_version,
        'url'          => !empty($release['html_url']) ? $release['html_url'] : 'https://github.com/ssnanda/ajnanda',
        'package'      => $asset['browser_download_url'],
        'requires'     => '',
        'requires_php' => '',
    );
}

function ajnanda_updater_check_for_update($transient) {
    if (!is_object($transient)) {
        $transient = new stdClass();
    }

    if (empty($transient->checked) || !is_array($transient->checked)) {
        return $transient;
    }

    $theme_slug = ajnanda_updater_theme_slug();

    if (empty($transient->checked[$theme_slug])) {
        return $transient;
    }

    $payload = ajnanda_updater_update_payload();

    if ($payload) {
        $transient->response[$theme_slug] = $payload;
    }

    return $transient;
}
add_filter('pre_set_site_transient_update_themes', 'ajnanda_updater_check_for_update');

function ajnanda_updater_theme_info($result, $action, $args) {
    if ('theme_information' !== $action) {
        return $result;
    }

    $theme_slug = ajnanda_updater_theme_slug();

    if (empty($args->slug) || $theme_slug !== $args->slug) {
        return $result;
    }

    $release = ajnanda_updater_get_latest_release();

    if (!$release || !empty($release['_ajnanda_error'])) {
        return $result;
    }

    $latest_version = ajnanda_updater_clean_version($release['tag_name']);
    $asset = ajnanda_updater_find_zip_asset($release, $latest_version);

    if (!$asset) {
        return $result;
    }

    return (object) array(
        'name'          => ajnanda_updater_theme()->get('Name'),
        'slug'          => $theme_slug,
        'version'       => $latest_version,
        'author'        => ajnanda_updater_theme()->get('Author'),
        'homepage'      => !empty($release['html_url']) ? $release['html_url'] : 'https://github.com/ssnanda/ajnanda',
        'download_link' => $asset['browser_download_url'],
        'sections'      => array(
            'description' => !empty($release['body']) ? wp_kses_post(wpautop($release['body'])) : 'AJNanda WordPress theme release.',
        ),
    );
}
add_filter('themes_api', 'ajnanda_updater_theme_info', 10, 3);

function ajnanda_updater_clear_all_update_cache() {
    delete_transient('ajnanda_github_latest_release');
    delete_site_transient('update_themes');

    if (function_exists('wp_clean_themes_cache')) {
        wp_clean_themes_cache(true);
    }
}

function ajnanda_updater_force_check_now() {
    ajnanda_updater_clear_all_update_cache();

    if (!function_exists('wp_update_themes')) {
        require_once ABSPATH . 'wp-includes/update.php';
    }

    wp_update_themes();

    return ajnanda_updater_update_payload(true);
}

function ajnanda_updater_run_direct_theme_update() {
    $payload = ajnanda_updater_force_check_now();

    if (!$payload) {
        return new WP_Error('ajnanda_no_update', 'AJNanda is already up to date, or no valid update ZIP was found.');
    }

    if (!class_exists('Theme_Upgrader')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    }

    if (!function_exists('request_filesystem_credentials')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $skin     = new Automatic_Upgrader_Skin();
    $upgrader = new Theme_Upgrader($skin);
    $result   = $upgrader->upgrade(ajnanda_updater_theme_slug());

    ajnanda_updater_clear_all_update_cache();

    if (is_wp_error($result)) {
        return $result;
    }

    if (is_wp_error($skin->result)) {
        return $skin->result;
    }

    if (false === $result || null === $result) {
        $error = $skin->get_errors();
        if (is_wp_error($error) && $error->has_errors()) {
            return $error;
        }

        return new WP_Error('ajnanda_update_failed', 'WordPress could not complete the AJNanda theme update.');
    }

    return true;
}

function ajnanda_updater_handle_admin_action() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to manage theme updates.');
    }

    check_admin_referer('ajnanda_theme_update_tools');

    $tool_action = isset($_POST['ajnanda_tool_action']) ? sanitize_key($_POST['ajnanda_tool_action']) : '';

    if ('clear_cache' === $tool_action) {
        ajnanda_updater_clear_all_update_cache();
        $redirect = add_query_arg('ajnanda-message', 'cache-cleared', admin_url('themes.php?page=ajnanda-theme-updater'));
    } elseif ('force_check' === $tool_action) {
        ajnanda_updater_force_check_now();
        $redirect = add_query_arg('ajnanda-message', 'force-checked', admin_url('themes.php?page=ajnanda-theme-updater'));
    } else {
        $redirect = admin_url('themes.php?page=ajnanda-theme-updater');
    }

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_ajnanda_theme_update_tools', 'ajnanda_updater_handle_admin_action');

function ajnanda_updater_redirect_to_theme_update($message = '') {
    $redirect = admin_url('themes.php?page=ajnanda-theme-updater');

    if ($message) {
        $redirect = add_query_arg('ajnanda-message', $message, $redirect);
    }

    wp_safe_redirect($redirect);
    exit;
}

function ajnanda_updater_handle_update_now() {
    if (!current_user_can('update_themes')) {
        wp_die('You do not have permission to update themes.');
    }

    check_admin_referer('ajnanda_theme_update_now');

    $result = ajnanda_updater_run_direct_theme_update();

    if (is_wp_error($result)) {
        if ('ajnanda_no_update' === $result->get_error_code()) {
            ajnanda_updater_redirect_to_theme_update('no-update');
        }

        set_transient('ajnanda_update_error_message', $result->get_error_message(), MINUTE_IN_SECONDS);
        ajnanda_updater_redirect_to_theme_update('update-failed');
    }

    ajnanda_updater_redirect_to_theme_update('updated');
}
add_action('admin_post_ajnanda_theme_update_now', 'ajnanda_updater_handle_update_now');

function ajnanda_updater_admin_menu() {
    $hook = add_submenu_page(
        'themes.php',
        'Update AJNanda',
        'Update AJNanda',
        'manage_options',
        'ajnanda-theme-updater',
        'ajnanda_updater_admin_page',
        1
    );

    // ajnanda_updater_make_menu_item_direct_update() below rewrites this page's
    // $submenu entry (index 2, normally the menu slug) to a direct admin-post.php
    // action URL so the sidebar link performs the update immediately. That means
    // core's get_admin_page_title() can no longer match the current page against
    // $submenu to resolve a title, so it falls through and $title stays null,
    // producing a strip_tags(null) deprecation notice in admin-header.php on
    // PHP 8.1+ when this settings page is loaded (e.g. after the post-update
    // redirect). Set $title ourselves before core computes it so the lookup is
    // short-circuited.
    if ($hook) {
        add_action("load-$hook", 'ajnanda_updater_set_admin_page_title');
    }
}
add_action('admin_menu', 'ajnanda_updater_admin_menu');

function ajnanda_updater_set_admin_page_title() {
    global $title;
    $title = 'Update AJNanda';
}

function ajnanda_updater_make_menu_item_direct_update(): void {
    global $submenu;

    if (empty($submenu['themes.php']) || !is_array($submenu['themes.php'])) {
        return;
    }

    foreach ($submenu['themes.php'] as $index => $item) {
        if (empty($item[2]) || 'ajnanda-theme-updater' !== $item[2]) {
            continue;
        }

        $submenu['themes.php'][$index][2] = ajnanda_updater_update_now_url();
        break;
    }
}
add_action('admin_menu', 'ajnanda_updater_make_menu_item_direct_update', 999);

function ajnanda_updater_admin_menu_child_style() {
    ?>
    <style>
        #adminmenu .wp-submenu a[href*="ajnanda_theme_update_now"] {
            position: relative;
            padding-left: 28px !important;
            font-size: 12px;
            opacity: 0.86;
        }

        #adminmenu .wp-submenu a[href*="ajnanda_theme_update_now"]::before {
            content: "↳";
            position: absolute;
            left: 14px;
            color: currentColor;
            opacity: 0.65;
        }
    </style>
    <?php
}
add_action('admin_head', 'ajnanda_updater_admin_menu_child_style');

function ajnanda_updater_update_now_url() {
    return add_query_arg(
        array(
            'action'   => 'ajnanda_theme_update_now',
            '_wpnonce' => wp_create_nonce('ajnanda_theme_update_now'),
        ),
        admin_url('admin-post.php')
    );
}

function ajnanda_updater_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $release = ajnanda_updater_get_latest_release(true);
    $current_version = ajnanda_updater_current_version();
    $theme_slug = ajnanda_updater_theme_slug();
    $theme_name = ajnanda_updater_theme()->get('Name');
    $latest_version = '';
    $asset_name = '';
    $asset_url = '';
    $update_available = 'No';
    $status_class = 'is-current';
    $status_label = 'Up to date';
    $status_help = 'The installed version matches the latest GitHub release, or no newer valid ZIP was found.';

    if ($release && empty($release['_ajnanda_error'])) {
        $latest_version = ajnanda_updater_clean_version($release['tag_name']);
        $asset = ajnanda_updater_find_zip_asset($release, $latest_version);

        if ($asset) {
            $asset_name = $asset['name'];
            $asset_url = $asset['browser_download_url'];
        }

        if ($latest_version && version_compare($latest_version, $current_version, '>') && $asset) {
            $update_available = 'Yes';
            $status_class = 'has-update';
            $status_label = 'Update available';
            $status_help = 'A newer AJNanda release ZIP was found on GitHub.';
        }
    } elseif (!empty($release['_ajnanda_error'])) {
        $status_class = 'has-error';
        $status_label = 'Check failed';
        $status_help = 'WordPress could not read the latest GitHub release.';
    }

    $message = isset($_GET['ajnanda-message']) ? sanitize_key($_GET['ajnanda-message']) : '';
    ?>
    <div class="wrap ajnanda-update-wrap">
        <style>
            .ajnanda-update-wrap {
                max-width: 1180px;
            }
            .ajnanda-update-hero {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 24px;
                align-items: center;
                margin: 20px 0 22px;
                padding: 28px 30px;
                border: 1px solid #dcdcde;
                border-radius: 16px;
                background: linear-gradient(135deg, #ffffff 0%, #f6f8ff 100%);
                box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
            }
            .ajnanda-update-eyebrow {
                margin: 0 0 8px;
                color: #3858e9;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }
            .ajnanda-update-hero h1 {
                margin: 0;
                color: #111827;
                font-size: 34px;
                line-height: 1.15;
            }
            .ajnanda-update-hero p {
                max-width: 720px;
                margin: 10px 0 0;
                color: #4b5563;
                font-size: 15px;
            }
            .ajnanda-update-status {
                min-width: 210px;
                padding: 18px;
                border-radius: 14px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                text-align: center;
            }
            .ajnanda-update-status strong {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 32px;
                padding: 0 14px;
                border-radius: 999px;
                background: #ecfdf5;
                color: #047857;
                font-size: 13px;
            }
            .ajnanda-update-status.has-update strong {
                background: #eff6ff;
                color: #1d4ed8;
            }
            .ajnanda-update-status.has-error strong {
                background: #fef2f2;
                color: #b91c1c;
            }
            .ajnanda-update-status span {
                display: block;
                margin-top: 10px;
                color: #6b7280;
                font-size: 13px;
                line-height: 1.45;
            }
            .ajnanda-update-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
                margin-bottom: 18px;
            }
            .ajnanda-update-card {
                padding: 18px;
                border: 1px solid #dcdcde;
                border-radius: 14px;
                background: #fff;
            }
            .ajnanda-update-card h2 {
                margin: 0 0 8px;
                color: #374151;
                font-size: 13px;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
            }
            .ajnanda-update-card code {
                display: inline-block;
                max-width: 100%;
                padding: 5px 8px;
                border-radius: 7px;
                background: #f3f4f6;
                color: #111827;
                font-size: 14px;
                word-break: break-word;
            }
            .ajnanda-update-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                align-items: center;
                margin: 18px 0 0;
            }
            .ajnanda-update-actions .button {
                min-height: 40px;
                padding: 4px 16px;
                border-radius: 8px;
                font-weight: 600;
            }
            .ajnanda-update-details {
                margin-top: 18px;
                overflow: hidden;
                border: 1px solid #dcdcde;
                border-radius: 14px;
                background: #fff;
            }
            .ajnanda-update-details table {
                border: 0;
                box-shadow: none;
            }
            .ajnanda-update-details th {
                width: 260px;
            }
            @media (max-width: 900px) {
                .ajnanda-update-hero,
                .ajnanda-update-grid {
                    grid-template-columns: 1fr;
                }
                .ajnanda-update-status {
                    text-align: left;
                }
            }
        </style>

        <?php if ('cache-cleared' === $message) : ?>
            <div class="notice notice-success"><p>AJNanda update cache cleared.</p></div>
        <?php elseif ('force-checked' === $message) : ?>
            <div class="notice notice-success"><p>AJNanda update check completed.</p></div>
        <?php elseif ('updated' === $message) : ?>
            <div class="notice notice-success"><p>AJNanda was updated successfully.</p></div>
        <?php elseif ('update-failed' === $message) : ?>
            <?php $error_message = get_transient('ajnanda_update_error_message'); ?>
            <?php delete_transient('ajnanda_update_error_message'); ?>
            <div class="notice notice-error"><p><?php echo esc_html($error_message ?: 'AJNanda update failed.'); ?></p></div>
        <?php elseif ('no-update' === $message) : ?>
            <div class="notice notice-info"><p>AJNanda is already up to date, or no valid update ZIP was found.</p></div>
        <?php endif; ?>

        <div class="ajnanda-update-hero">
            <div>
                <p class="ajnanda-update-eyebrow">AJNanda Theme Updater</p>
                <h1>Update AJNanda</h1>
                <p>Check the latest GitHub release, confirm the expected ZIP asset, clear WordPress update cache, and run the AJNanda theme update directly from one clean screen.</p>
            </div>
            <div class="ajnanda-update-status <?php echo esc_attr($status_class); ?>">
                <strong><?php echo esc_html($status_label); ?></strong>
                <span><?php echo esc_html($status_help); ?></span>
            </div>
        </div>

        <div class="ajnanda-update-grid">
            <div class="ajnanda-update-card">
                <h2>Installed Version</h2>
                <code><?php echo esc_html($current_version); ?></code>
            </div>
            <div class="ajnanda-update-card">
                <h2>Latest GitHub Version</h2>
                <code><?php echo esc_html($latest_version ?: 'Not found'); ?></code>
            </div>
            <div class="ajnanda-update-card">
                <h2>Update Available</h2>
                <code><?php echo esc_html($update_available); ?></code>
            </div>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ajnanda-update-actions">
            <?php wp_nonce_field('ajnanda_theme_update_tools'); ?>
            <input type="hidden" name="action" value="ajnanda_theme_update_tools">

            <button type="submit" class="button" name="ajnanda_tool_action" value="clear_cache">
                Clear Update Cache
            </button>

            <button type="submit" class="button button-primary" name="ajnanda_tool_action" value="force_check">
                Force Check
            </button>

            <a class="button button-primary" href="<?php echo esc_url(ajnanda_updater_update_now_url()); ?>">
                Update Now
            </a>

            <a class="button" href="<?php echo esc_url(admin_url('themes.php')); ?>">
                Open Themes
            </a>

            <a class="button" href="<?php echo esc_url(admin_url('update-core.php?force-check=1')); ?>">
                WordPress Updates
            </a>
        </form>

        <div class="ajnanda-update-details">
            <table class="widefat striped">
                <tbody>
                    <tr><th scope="row">Theme Name</th><td><?php echo esc_html($theme_name); ?></td></tr>
                    <tr><th scope="row">Installed Theme Folder / Slug</th><td><code><?php echo esc_html($theme_slug); ?></code></td></tr>
                    <tr><th scope="row">GitHub API URL</th><td><code><?php echo esc_html(ajnanda_updater_latest_release_url()); ?></code></td></tr>
                    <tr><th scope="row">Expected ZIP Asset</th><td><code><?php echo esc_html($latest_version ? 'ajnanda-' . $latest_version . '.zip' : 'Not found'); ?></code></td></tr>
                    <tr><th scope="row">Found ZIP Asset</th><td><code><?php echo esc_html($asset_name ?: 'Not found'); ?></code></td></tr>
                    <?php if (!empty($release['_ajnanda_error'])) : ?>
                        <tr><th scope="row">Error</th><td><code><?php echo esc_html($release['_ajnanda_error']); ?></code></td></tr>
                    <?php endif; ?>
                    <?php if ($asset_url) : ?>
                        <tr><th scope="row">Download URL</th><td><code style="word-break: break-all;"><?php echo esc_html($asset_url); ?></code></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
