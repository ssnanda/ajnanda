<?php
/**
 * AJNanda admin: Overview screen.
 *
 * @package AJNanda
 * @var int $patterns_count
 * @var int $page_designs_count
 * @var int $starter_sites_count
 * @var array{scheme_label:string,pages_created:int,starter_labels:string[],primary_menu_set:bool} $site_status
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda Site Builder', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Build websites with patterns, page designs, and starter sites', 'ajnanda'); ?></h1>
    </div>

    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('Your site', 'ajnanda'); ?></h2>
        <ul class="ajnanda-admin-status-list">
            <li>
                <span class="ajnanda-admin-status-icon" aria-hidden="true">🎨</span>
                <?php if (!empty($site_status['kit_label'])) : ?>
                    <?php
                    printf(
                        /* translators: %s: site kit label */
                        esc_html__('Site Kit: %s', 'ajnanda'),
                        '<strong>' . esc_html($site_status['kit_label']) . '</strong>'
                    );
                    ?>
                <?php else : ?>
                    <?php
                    printf(
                        /* translators: %s: color scheme label */
                        esc_html__('Color scheme: %s', 'ajnanda'),
                        '<strong>' . esc_html($site_status['scheme_label']) . '</strong>'
                    );
                    ?>
                <?php endif; ?>
                — <a href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-site-kits')); ?>"><?php esc_html_e('browse Site Kits', 'ajnanda'); ?></a>
            </li>
            <li>
                <span class="ajnanda-admin-status-icon" aria-hidden="true"><?php echo empty($site_status['starter_labels']) ? '⬜' : '✅'; ?></span>
                <?php if (empty($site_status['starter_labels'])) : ?>
                    <?php esc_html_e('No starter site imported yet', 'ajnanda'); ?> —
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-starter-sites')); ?>"><?php esc_html_e('browse Starter Sites', 'ajnanda'); ?></a>
                <?php else : ?>
                    <?php
                    printf(
                        /* translators: %s: comma-separated list of imported starter site labels */
                        esc_html__('Starter site imported: %s', 'ajnanda'),
                        '<strong>' . esc_html(implode(', ', $site_status['starter_labels'])) . '</strong>'
                    );
                    ?>
                <?php endif; ?>
            </li>
            <li>
                <span class="ajnanda-admin-status-icon" aria-hidden="true"><?php echo $site_status['pages_created'] > 0 ? '✅' : '⬜'; ?></span>
                <?php
                printf(
                    /* translators: %d: number of pages built from an AJNanda page design */
                    esc_html(_n('%d page built with AJNanda so far', '%d pages built with AJNanda so far', $site_status['pages_created'], 'ajnanda')),
                    (int) $site_status['pages_created']
                );
                ?>
                — <a href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-page-library')); ?>"><?php esc_html_e('browse Page Library', 'ajnanda'); ?></a>
            </li>
            <li>
                <span class="ajnanda-admin-status-icon" aria-hidden="true"><?php echo $site_status['primary_menu_set'] ? '✅' : '⬜'; ?></span>
                <?php echo $site_status['primary_menu_set'] ? esc_html__('Primary navigation menu is set up', 'ajnanda') : esc_html__('Primary navigation menu is not set up yet', 'ajnanda'); ?>
                — <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>"><?php esc_html_e('open Menus', 'ajnanda'); ?></a>
            </li>
        </ul>
    </div>

    <div class="ajnanda-admin-grid">
        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Starter Sites', 'ajnanda'); ?></h2>
            <span class="ajnanda-admin-pill"><?php echo esc_html(number_format_i18n($starter_sites_count)); ?></span>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-starter-sites')); ?>"><?php esc_html_e('Browse Starter Sites', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Page Library', 'ajnanda'); ?></h2>
            <span class="ajnanda-admin-pill"><?php echo esc_html(number_format_i18n($page_designs_count)); ?></span>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-page-library')); ?>"><?php esc_html_e('Browse Page Library', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Site Kits', 'ajnanda'); ?></h2>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-site-kits')); ?>"><?php esc_html_e('Browse Site Kits', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Section Patterns', 'ajnanda'); ?></h2>
            <span class="ajnanda-admin-pill"><?php echo esc_html(number_format_i18n($patterns_count)); ?></span>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-patterns')); ?>"><?php esc_html_e('View Pattern Reference', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Theme Settings', 'ajnanda'); ?></h2>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-settings')); ?>"><?php esc_html_e('Open Theme Settings', 'ajnanda'); ?></a>
        </div>
    </div>

</div>
