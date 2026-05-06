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

function ajnanda_updater_admin_menu() {
    add_theme_page(
        'AJNanda Theme Updater',
        'AJNanda Updater',
        'manage_options',
        'ajnanda-theme-updater',
        'ajnanda_updater_admin_page'
    );
}
add_action('admin_menu', 'ajnanda_updater_admin_menu');

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

    if ($release && empty($release['_ajnanda_error'])) {
        $latest_version = ajnanda_updater_clean_version($release['tag_name']);
        $asset = ajnanda_updater_find_zip_asset($release, $latest_version);

        if ($asset) {
            $asset_name = $asset['name'];
            $asset_url = $asset['browser_download_url'];
        }

        if ($latest_version && version_compare($latest_version, $current_version, '>') && $asset) {
            $update_available = 'Yes';
        }
    }

    $message = isset($_GET['ajnanda-message']) ? sanitize_key($_GET['ajnanda-message']) : '';
    ?>
    <div class="wrap">
        <h1>AJNanda Theme Updater</h1>

        <?php if ('cache-cleared' === $message) : ?>
            <div class="notice notice-success"><p>AJNanda update cache cleared.</p></div>
        <?php elseif ('force-checked' === $message) : ?>
            <div class="notice notice-success"><p>AJNanda update check completed.</p></div>
        <?php endif; ?>

        <table class="widefat striped" style="max-width: 980px;">
            <tbody>
                <tr><th scope="row">Theme Name</th><td><?php echo esc_html($theme_name); ?></td></tr>
                <tr><th scope="row">Installed Theme Folder / Slug</th><td><code><?php echo esc_html($theme_slug); ?></code></td></tr>
                <tr><th scope="row">Installed Version from style.css</th><td><code><?php echo esc_html($current_version); ?></code></td></tr>
                <tr><th scope="row">GitHub API URL</th><td><code><?php echo esc_html(ajnanda_updater_latest_release_url()); ?></code></td></tr>
                <tr><th scope="row">Latest GitHub Version</th><td><code><?php echo esc_html($latest_version ?: 'Not found'); ?></code></td></tr>
                <tr><th scope="row">Expected ZIP Asset</th><td><code><?php echo esc_html($latest_version ? 'ajnanda-' . $latest_version . '.zip' : 'Not found'); ?></code></td></tr>
                <tr><th scope="row">Found ZIP Asset</th><td><code><?php echo esc_html($asset_name ?: 'Not found'); ?></code></td></tr>
                <tr><th scope="row">Update Available</th><td><strong><?php echo esc_html($update_available); ?></strong></td></tr>
                <?php if (!empty($release['_ajnanda_error'])) : ?>
                    <tr><th scope="row">Error</th><td><code><?php echo esc_html($release['_ajnanda_error']); ?></code></td></tr>
                <?php endif; ?>
                <?php if ($asset_url) : ?>
                    <tr><th scope="row">Download URL</th><td><code style="word-break: break-all;"><?php echo esc_html($asset_url); ?></code></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 20px;">
            <?php wp_nonce_field('ajnanda_theme_update_tools'); ?>
            <input type="hidden" name="action" value="ajnanda_theme_update_tools">

            <button type="submit" class="button" name="ajnanda_tool_action" value="clear_cache">
                Clear AJNanda Update Cache
            </button>

            <button type="submit" class="button button-primary" name="ajnanda_tool_action" value="force_check">
                Force Check for AJNanda Update
            </button>

            <a class="button" href="<?php echo esc_url(admin_url('update-core.php?force-check=1')); ?>">
                Open WordPress Updates
            </a>
        </form>
    </div>
    <?php
}
