<?php
if ( PHP_SAPI !== 'cli' ) { exit; }
$ajnanda_tests_dir = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';
if (!is_file($ajnanda_tests_dir . '/includes/functions.php')) {
    fwrite(STDERR, "Set WP_TESTS_DIR to an existing WordPress PHPUnit test library.\n"); exit(1);
}
require_once $ajnanda_tests_dir . '/includes/functions.php';
tests_add_filter('muplugins_loaded', function () {
    add_filter('pre_http_request', function () { return new WP_Error('unexpected_test_http', 'All network disabled in tests.'); }, 1, 3);
    $theme = dirname(__DIR__, 2);
    add_filter('template_directory', function () use ($theme) { return $theme; });
    if (getenv('AJCORE_TESTS_DISABLED') !== '1') {
        define('AJCORE_PLUGIN_DIR', (getenv('AJCORE_TEST_PLUGIN_DIR') ?: dirname($theme) . '/ajcore') . '/');
        require_once AJCORE_PLUGIN_DIR . 'includes/settings-encryption.php';
        require_once AJCORE_PLUGIN_DIR . 'modules/reviews/bootstrap.php';
        require_once AJCORE_PLUGIN_DIR . 'tests/reviews/fixtures.php';
    }
    require_once $theme . '/blocks/ajnanda-blocks/loader.php';
    require_once $theme . '/blocks/ajnanda-blocks/reviews/loader.php';
});
require $ajnanda_tests_dir . '/includes/bootstrap.php';
