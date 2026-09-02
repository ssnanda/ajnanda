<?php
/**
 * Search & AI module bootstrap.
 *
 * Phase 1 deliberately leaves legacy frontend hooks in inc/seo.php active.
 * The classes loaded here establish the shared contracts those hooks will
 * move behind in later phases without changing existing output today.
 *
 * @package AJNanda
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-settings.php';
require_once __DIR__ . '/class-site-profile.php';
require_once __DIR__ . '/class-content-policy.php';
require_once __DIR__ . '/class-crawler-registry.php';
require_once __DIR__ . '/class-capability-ownership.php';
require_once __DIR__ . '/admin/class-search-ai-admin.php';

add_action('after_setup_theme', array('AJNanda_Search_AI_Settings', 'maybe_migrate'), 20);
AJNanda_Search_AI_Admin::init();
