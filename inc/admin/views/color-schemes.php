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
                        class="button button-primary ajnanda-preview-link"
                        target="_blank"
                        rel="noopener"
                        href="<?php echo esc_url(ajnanda_get_preview_url($preview_page, $slug)); ?>"
                    ><?php esc_html_e('Preview', 'ajnanda'); ?> ↗</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
