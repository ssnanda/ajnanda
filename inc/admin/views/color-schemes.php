<?php
/**
 * AJNanda admin: Color Schemes screen — visual reference for the presets
 * in ajnanda_get_color_schemes() (inc/color-schemes.php), with a
 * non-destructive live preview link for each (inc/preview.php).
 *
 * This screen doesn't set anything — to actually apply a scheme site-wide,
 * use the "Quick presets" swatches in Appearance → Customize → Colors; to
 * use one on a single page, use the color picker on the Page Library screen.
 *
 * @package AJNanda
 * @var array<string,array> $schemes
 * @var string $active_slug
 * @var string $preview_page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Color Schemes', 'ajnanda'); ?></h1>
        <p>
            <?php esc_html_e('A visual reference for the 20 preset color schemes. This screen is read-only — it changes nothing. Click "Preview" on any card to see a real page rendered with that scheme\'s colors, in a new tab, exactly as a visitor would see it — nothing is saved.', 'ajnanda'); ?>
        </p>
        <p>
            <?php
            printf(
                /* translators: 1: link to Customizer, 2: link to Page Library */
                esc_html__('To actually use a scheme: apply one site-wide from the swatches in %1$s, or apply one to a single page from %2$s.', 'ajnanda'),
                '<a href="' . esc_url(admin_url('customize.php?autofocus[section]=colors')) . '">' . esc_html__('Appearance → Customize → Colors', 'ajnanda') . '</a>',
                '<a href="' . esc_url(admin_url('admin.php?page=ajnanda-page-library')) . '">' . esc_html__('Page Library', 'ajnanda') . '</a>'
            );
            ?>
        </p>
    </div>

    <div class="ajnanda-admin-grid">
        <?php foreach ($schemes as $slug => $scheme) : ?>
            <div class="ajnanda-admin-card">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <span style="display:inline-block;width:32px;height:32px;border-radius:50%;background:<?php echo esc_attr($scheme['swatch']); ?>;border:2px solid #fff;box-shadow:0 0 0 1px #dcdcde;flex-shrink:0;" aria-hidden="true"></span>
                    <h2 style="margin:0;">
                        <?php echo esc_html($scheme['label']); ?>
                        <?php if ($slug === $active_slug) : ?>
                            <span class="ajnanda-admin-pill is-success"><?php esc_html_e('Active now', 'ajnanda'); ?></span>
                        <?php endif; ?>
                    </h2>
                </div>

                <div style="display:flex;gap:6px;margin-bottom:12px;">
                    <?php foreach (array('primary', 'primary_dark', 'secondary', 'accent') as $role) : ?>
                        <span
                            title="<?php echo esc_attr(ucwords(str_replace('_', ' ', $role)) . ': ' . $scheme[$role]); ?>"
                            style="display:inline-block;width:100%;height:24px;border-radius:4px;background:<?php echo esc_attr($scheme[$role]); ?>;"
                        ></span>
                    <?php endforeach; ?>
                </div>

                <div class="ajnanda-admin-actions">
                    <a
                        class="button button-primary"
                        target="_blank"
                        rel="noopener"
                        href="<?php echo esc_url(ajnanda_get_preview_url($preview_page, $slug)); ?>"
                    ><?php esc_html_e('Preview', 'ajnanda'); ?> ↗</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
