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
require_once __DIR__ . '/class-page-semantic-intent.php';
require_once __DIR__ . '/class-schema-context.php';
require_once __DIR__ . '/class-schema-block-walker.php';
require_once __DIR__ . '/class-schema-validator.php';
require_once __DIR__ . '/class-schema-contributors.php';
require_once __DIR__ . '/class-schema-page-entity-contributor.php';
require_once __DIR__ . '/class-schema-graph.php';
require_once __DIR__ . '/class-sitemap-policy.php';
require_once __DIR__ . '/class-discovery-files.php';
require_once __DIR__ . '/class-readiness.php';
require_once __DIR__ . '/class-insights.php';
require_once __DIR__ . '/class-crawler-log-store.php';
require_once __DIR__ . '/class-crawler-verifier.php';
require_once __DIR__ . '/class-crawler-logger.php';
require_once __DIR__ . '/admin/class-search-ai-admin.php';

add_action('after_setup_theme', array('AJNanda_Search_AI_Settings', 'maybe_migrate'), 20);
add_action('init', array('AJNanda_Search_AI_Page_Semantic_Intent', 'init'));
AJNanda_Search_AI_Admin::init();
AJNanda_Search_AI_Sitemap_Policy::init();
AJNanda_Search_AI_Crawler_Log_Store::init();
AJNanda_Search_AI_Crawler_Logger::init();
add_action('switch_theme', array('AJNanda_Search_AI_Crawler_Log_Store', 'unschedule'));
