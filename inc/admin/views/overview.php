<?php
/**
 * AJNanda admin: Overview screen.
 *
 * @package AJNanda
 * @var int $patterns_count
 * @var int $page_designs_count
 * @var int $starter_sites_count
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
            <li><?php esc_html_e('New site: go to Starter Sites, review the pages it will create, then import.', 'ajnanda'); ?></li>
            <li><?php esc_html_e('One new page: go to Pages → Add New — AJNanda page designs appear automatically in the "Choose a pattern" screen — or use the Page Library screen here.', 'ajnanda'); ?></li>
            <li><?php esc_html_e('One new section: open the block inserter on any page and search "AJNanda" — sections are grouped by type (Hero, Services, CTA, etc.).', 'ajnanda'); ?></li>
        </ol>
        <p class="description"><?php esc_html_e('See /docs in the theme for the full pattern, page design, and starter site reference, including how to add new ones.', 'ajnanda'); ?></p>
    </div>
</div>
