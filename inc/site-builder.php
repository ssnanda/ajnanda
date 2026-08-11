<?php
/**
 * AJNanda Site Builder — loader.
 *
 * Wires together the pattern/page-design/starter-site system (the
 * site-builder work, started at version 1.2.7). This is the single place
 * that knows the load order; individual pieces stay in their own small
 * files. See docs/development.md for the architecture and how to extend
 * each layer.
 *
 * Note: files under /patterns are NOT required here — WordPress core
 * auto-registers every *.php file in a theme's /patterns directory from
 * its file-header comment. Nothing in this loader needs to change when a
 * pattern or page design is added.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/patterns.php';
require_once __DIR__ . '/color-schemes.php';
require_once __DIR__ . '/dark-surface-mode.php';
require_once __DIR__ . '/font-pairings.php';
require_once __DIR__ . '/site-kits.php';
require_once __DIR__ . '/page-designs.php';
require_once __DIR__ . '/starter-sites/class-ajnanda-starter-sites.php';
require_once __DIR__ . '/starter-sites/class-ajnanda-starter-importer.php';

// Admin screens (and the preview engine — its request also lands under
// wp-admin/admin-post.php, so is_admin() is true there too) are only
// needed in wp-admin — nothing here touches the front end, so it's kept
// out of the frontend request entirely.
if (is_admin()) {
    require_once __DIR__ . '/admin/class-ajnanda-admin.php';
    require_once __DIR__ . '/preview.php';
}

// The CLI command file self-guards on WP_CLI, but there's no reason to
// even open it on a normal request.
if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/cli/class-ajnanda-cli.php';
}
