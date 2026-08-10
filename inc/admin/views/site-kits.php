<?php
/**
 * AJNanda admin: Site Kits screen — visual reference for the presets in
 * ajnanda_get_site_kits() (inc/site-kits.php: a color scheme + a font
 * pairing, bundled), plus a standalone reference for font pairings alone
 * (inc/font-pairings.php) for when only the fonts should change.
 *
 * Read-only, same as Color Schemes — to actually apply a kit or a
 * pairing, use the "Quick Kits" / "Font Pairing" controls in
 * Appearance → Customize.
 *
 * @package AJNanda
 * @var array<string,array> $kits
 * @var array<string,array> $pairings
 * @var string $active_kit
 * @var string $active_font
 * @var string $preview_page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Site Kits', 'ajnanda'); ?></h1>
        <p>
            <?php esc_html_e('A Site Kit is a color scheme and a font pairing, bundled under one name — a complete look in one click, not a new design system. This screen is read-only. Click "Preview" on any card to see a real page rendered with that kit\'s colors and fonts, in a new tab — nothing is saved.', 'ajnanda'); ?>
        </p>
        <p>
            <?php
            printf(
                /* translators: %s: link to Customizer */
                esc_html__('To actually apply a kit: open %s and use the "Quick Kits" swatches at the top of the Colors panel.', 'ajnanda'),
                '<a href="' . esc_url(admin_url('customize.php?autofocus[section]=colors')) . '">' . esc_html__('Appearance → Customize', 'ajnanda') . '</a>'
            );
            ?>
        </p>
    </div>

    <div class="ajnanda-admin-grid">
        <?php foreach ($kits as $slug => $kit) :
            $color_schemes = function_exists('ajnanda_get_color_schemes') ? ajnanda_get_color_schemes() : array();
            $scheme  = isset($color_schemes[$kit['color_scheme']]) ? $color_schemes[$kit['color_scheme']] : null;
            $pairing = isset($pairings[$kit['font_pairing']]) ? $pairings[$kit['font_pairing']] : null;
            if (!$scheme || !$pairing) {
                continue;
            }
        ?>
            <div class="ajnanda-admin-card">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <span style="display:inline-block;width:32px;height:32px;border-radius:50%;background:<?php echo esc_attr($scheme['swatch']); ?>;border:2px solid #fff;box-shadow:0 0 0 1px #dcdcde;flex-shrink:0;" aria-hidden="true"></span>
                    <h2 style="margin:0;font-family:<?php echo esc_attr($pairing['heading_stack']); ?>;">
                        <?php echo esc_html($kit['label']); ?>
                        <?php if ($slug === $active_kit) : ?>
                            <span class="ajnanda-admin-pill is-success"><?php esc_html_e('Active now', 'ajnanda'); ?></span>
                        <?php endif; ?>
                    </h2>
                </div>

                <p class="description"><?php echo esc_html($kit['mood']); ?></p>

                <div style="display:flex;gap:6px;margin-bottom:8px;">
                    <?php foreach (array('primary', 'primary_dark', 'secondary', 'accent') as $role) : ?>
                        <span
                            title="<?php echo esc_attr(ucwords(str_replace('_', ' ', $role)) . ': ' . $scheme[$role]); ?>"
                            style="display:inline-block;width:100%;height:20px;border-radius:4px;background:<?php echo esc_attr($scheme[$role]); ?>;"
                        ></span>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php echo esc_html($scheme['label']); ?> + <?php echo esc_html($pairing['label']); ?></p>

                <div class="ajnanda-admin-actions">
                    <a
                        class="button button-primary ajnanda-preview-link"
                        target="_blank"
                        rel="noopener"
                        href="<?php echo esc_url(ajnanda_get_preview_url($preview_page, $kit['color_scheme'], $kit['font_pairing'])); ?>"
                    ><?php esc_html_e('Preview', 'ajnanda'); ?> ↗</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('Font pairings on their own', 'ajnanda'); ?></h2>
        <p class="description"><?php esc_html_e('Want to keep your current colors and only change the fonts? These are the same pairings used above, previewable without a color change. Apply one from the "Font Pairing" control under Appearance → Customize → Typography.', 'ajnanda'); ?></p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Pairing', 'ajnanda'); ?></th>
                    <th><?php esc_html_e('Heading / Body', 'ajnanda'); ?></th>
                    <th><?php esc_html_e('Mood', 'ajnanda'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pairings as $slug => $pairing) : ?>
                    <tr>
                        <td style="font-family:<?php echo esc_attr($pairing['heading_stack']); ?>;font-size:16px;font-weight:700;">
                            <?php echo esc_html($pairing['label']); ?>
                            <?php if ($slug === $active_font) : ?>
                                <span class="ajnanda-admin-pill is-success"><?php esc_html_e('Active now', 'ajnanda'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($pairing['heading_font']); ?> / <?php echo esc_html($pairing['body_font']); ?></td>
                        <td class="description"><?php echo esc_html($pairing['mood']); ?></td>
                        <td>
                            <a
                                class="button button-small ajnanda-preview-link"
                                target="_blank"
                                rel="noopener"
                                href="<?php echo esc_url(ajnanda_get_preview_url($preview_page, '', $slug)); ?>"
                            ><?php esc_html_e('Preview', 'ajnanda'); ?> ↗</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
