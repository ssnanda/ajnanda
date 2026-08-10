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
        <p><?php esc_html_e('Everything here becomes ordinary, fully editable Gutenberg blocks once it lands on a page — there is nothing to "eject" and no separate builder to learn.', 'ajnanda'); ?></p>
    </div>

    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('Your site', 'ajnanda'); ?></h2>
        <p class="description"><?php esc_html_e('What this install has actually done so far — separate from the library counts below, which are just what\'s available.', 'ajnanda'); ?></p>
        <ul class="ajnanda-admin-status-list">
            <li>
                <span class="ajnanda-admin-status-icon" aria-hidden="true">🎨</span>
                <?php
                printf(
                    /* translators: %s: color scheme label */
                    esc_html__('Color scheme: %s', 'ajnanda'),
                    '<strong>' . esc_html($site_status['scheme_label']) . '</strong>'
                );
                ?>
                — <a href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-color-schemes')); ?>"><?php esc_html_e('browse schemes', 'ajnanda'); ?></a>
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
            <p><?php
                printf(
                    /* translators: %d: number of starter sites */
                    esc_html(_n('%d complete starter site, ready to import.', '%d complete starter sites, ready to import.', $starter_sites_count, 'ajnanda')),
                    (int) $starter_sites_count
                );
            ?></p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-starter-sites')); ?>"><?php esc_html_e('Browse Starter Sites', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Page Library', 'ajnanda'); ?></h2>
            <p><?php
                printf(
                    /* translators: %d: number of page designs */
                    esc_html(_n('%d complete page design, insertable as a normal page.', '%d complete page designs, insertable as a normal page.', $page_designs_count, 'ajnanda')),
                    (int) $page_designs_count
                );
            ?></p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-page-library')); ?>"><?php esc_html_e('Browse Page Library', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Section Patterns', 'ajnanda'); ?></h2>
            <p><?php
                printf(
                    /* translators: %d: number of section patterns */
                    esc_html(_n('%d reusable section available in the block inserter.', '%d reusable sections available in the block inserter.', $patterns_count, 'ajnanda')),
                    (int) $patterns_count
                );
            ?></p>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-patterns')); ?>"><?php esc_html_e('View Pattern Reference', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Theme Settings', 'ajnanda'); ?></h2>
            <p><?php esc_html_e('Customizer, header/footer layout, and theme updates.', 'ajnanda'); ?></p>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-settings')); ?>"><?php esc_html_e('Open Theme Settings', 'ajnanda'); ?></a>
        </div>
    </div>

    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('Getting started', 'ajnanda'); ?></h2>
        <ol>
            <li><?php esc_html_e('Pick your colors first: browse Color Schemes and either apply one site-wide from the Customizer\'s Colors panel, or just note which one you like — every page and starter site automatically follows whatever is active, so choosing first means nothing needs recoloring later.', 'ajnanda'); ?></li>
            <li><?php esc_html_e('New site: go to Starter Sites, review the pages it will create, then import.', 'ajnanda'); ?></li>
            <li><?php esc_html_e('One new page: go to Pages → Add New — AJNanda page designs appear automatically in the "Choose a pattern" screen — or use the Page Library screen here.', 'ajnanda'); ?></li>
            <li><?php esc_html_e('One new section: open the block inserter on any page and search "AJNanda" — sections are grouped by type (Hero, Services, CTA, etc.).', 'ajnanda'); ?></li>
        </ol>
        <p class="description"><?php esc_html_e('See /docs in the theme for the full pattern, page design, and starter site reference, including how to add new ones.', 'ajnanda'); ?></p>
    </div>
</div>
