<?php
/**
 * AJNanda admin: Theme Settings screen.
 *
 * Intentionally does not re-implement anything — it links out to the
 * existing Customizer (header/footer/hero builder) and the existing
 * Appearance → Update AJNanda screen, which keeps working exactly as it
 * always has, "direct update" sidebar link included.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Theme Settings', 'ajnanda'); ?></h1>
        <p><?php esc_html_e('AJNanda\'s site-wide controls live in their existing locations — nothing has moved, this is just a shortcut to both.', 'ajnanda'); ?></p>
    </div>

    <div class="ajnanda-admin-grid">
        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Customizer', 'ajnanda'); ?></h2>
            <p><?php esc_html_e('Header and footer layout, hero defaults, and site identity.', 'ajnanda'); ?></p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('customize.php')); ?>"><?php esc_html_e('Open Customizer', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Colors', 'ajnanda'); ?></h2>
            <p><?php esc_html_e('Apply a color scheme site-wide, or browse the 20 presets first.', 'ajnanda'); ?></p>
            <div class="ajnanda-admin-actions">
                <a class="button" href="<?php echo esc_url(admin_url('customize.php?autofocus[section]=colors')); ?>"><?php esc_html_e('Open Colors', 'ajnanda'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-color-schemes')); ?>"><?php esc_html_e('Browse Color Schemes', 'ajnanda'); ?></a>
            </div>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Theme Updates', 'ajnanda'); ?></h2>
            <p><?php esc_html_e('Check for and install AJNanda theme updates.', 'ajnanda'); ?></p>
            <a class="button" href="<?php echo esc_url(admin_url('themes.php?page=ajnanda-theme-updater')); ?>"><?php esc_html_e('Open Update AJNanda', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Menus', 'ajnanda'); ?></h2>
            <p><?php esc_html_e('Edit navigation menus created by a starter site import, or build your own.', 'ajnanda'); ?></p>
            <a class="button" href="<?php echo esc_url(admin_url('nav-menus.php')); ?>"><?php esc_html_e('Open Menus', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('Reading Settings', 'ajnanda'); ?></h2>
            <p><?php esc_html_e('Choose or change the site homepage and posts page.', 'ajnanda'); ?></p>
            <a class="button" href="<?php echo esc_url(admin_url('options-reading.php')); ?>"><?php esc_html_e('Open Reading Settings', 'ajnanda'); ?></a>
        </div>

        <div class="ajnanda-admin-card">
            <h2><?php esc_html_e('SEO', 'ajnanda'); ?></h2>
            <p><?php esc_html_e('Meta descriptions, schema markup, GEO/AEO crawler access, and Site Kit suggestions.', 'ajnanda'); ?></p>
            <div class="ajnanda-admin-actions">
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-seo-settings')); ?>"><?php esc_html_e('Open SEO Settings', 'ajnanda'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ajnanda-seo-insights')); ?>"><?php esc_html_e('Open SEO Insights', 'ajnanda'); ?></a>
            </div>
        </div>
    </div>
</div>
