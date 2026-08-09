<?php
/**
 * AJNanda admin: Page Library screen.
 *
 * @package AJNanda
 * @var array<string,array> $designs
 * @var array|null $notice
 */

if (!defined('ABSPATH')) {
    exit;
}

// Group by the part of the title before an em dash ("Home — Super Bold" -> "Home"),
// purely for display — the underlying data is a flat, category-filtered list of patterns.
$groups = array();
foreach ($designs as $slug => $pattern) {
    $title = isset($pattern['title']) ? $pattern['title'] : $slug;
    $group = strpos($title, '—') !== false ? trim(strstr($title, '—', true)) : __('Other', 'ajnanda');
    $groups[$group][$slug] = $pattern;
}
ksort($groups);

$color_schemes = function_exists('ajnanda_get_color_schemes') ? ajnanda_get_color_schemes() : array();
$site_scheme    = function_exists('ajnanda_get_active_color_scheme_slug') ? ajnanda_get_active_color_scheme_slug() : 'blue';
$site_scheme_label = isset($color_schemes[$site_scheme]) ? $color_schemes[$site_scheme]['label'] : __('Custom', 'ajnanda');
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Page Library', 'ajnanda'); ?></h1>
        <p><?php esc_html_e('Complete page designs built from AJNanda section patterns. These also appear automatically in the "Choose a pattern" screen when you go to Pages → Add New — use this screen when you want to browse first, or to insert one directly.', 'ajnanda'); ?></p>
        <?php if (!empty($color_schemes)) : ?>
            <p>
                <?php
                printf(
                    /* translators: 1: current site brand colors label, 2: link to Customizer */
                    esc_html__('Your site\'s current brand colors are %1$s. New pages below default to matching them. %2$s', 'ajnanda'),
                    '<strong>' . esc_html($site_scheme_label) . '</strong>',
                    '<a href="' . esc_url(admin_url('customize.php?autofocus[section]=colors')) . '">' . esc_html__('Change them in the Customizer', 'ajnanda') . '</a>'
                );
                ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if ($notice && !empty($notice['error'])) : ?>
        <div class="notice notice-error"><p><?php echo esc_html($notice['error']); ?></p></div>
    <?php endif; ?>

    <?php foreach ($groups as $group_label => $group_designs) : ?>
        <div class="ajnanda-admin-section">
            <h2><?php echo esc_html($group_label); ?></h2>
            <div class="ajnanda-admin-grid">
                <?php foreach ($group_designs as $slug => $pattern) : ?>
                    <div class="ajnanda-admin-card">
                        <h2><?php echo esc_html($pattern['title']); ?></h2>
                        <p><?php echo esc_html(isset($pattern['description']) ? $pattern['description'] : ''); ?></p>
                        <p class="description"><code><?php echo esc_html($slug); ?></code></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ajnanda-page-design-form">
                            <?php wp_nonce_field(AJNanda_Admin::NONCE_ACTION); ?>
                            <input type="hidden" name="action" value="ajnanda_insert_page_design">
                            <input type="hidden" name="page_design" value="<?php echo esc_attr($slug); ?>">
                            <p>
                                <input type="text" name="title" value="<?php echo esc_attr($pattern['title']); ?>" class="regular-text" aria-label="<?php esc_attr_e('New page title', 'ajnanda'); ?>">
                            </p>
                            <?php if (!empty($color_schemes)) : ?>
                                <p>
                                    <label>
                                        <?php esc_html_e('Color scheme', 'ajnanda'); ?><br>
                                        <select name="color_scheme" class="ajnanda-scheme-select" data-site-scheme="<?php echo esc_attr($site_scheme); ?>">
                                            <?php foreach ($color_schemes as $scheme_slug => $scheme) : ?>
                                                <option value="<?php echo esc_attr($scheme_slug); ?>" <?php selected($scheme_slug, $site_scheme); ?>>
                                                    <?php echo esc_html($scheme['label']); ?><?php echo $scheme_slug === $site_scheme ? ' — ' . esc_html__('site default', 'ajnanda') : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </p>
                                <div class="notice notice-warning inline ajnanda-scheme-warning" hidden>
                                    <p>
                                        ⚠️ <?php esc_html_e('This is different from your site-wide color scheme. This one page will look different from the rest of your site — that\'s fine if it\'s intentional (e.g. a campaign landing page), but if you want the whole site to match, change the scheme in the Customizer instead of here.', 'ajnanda'); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <div class="ajnanda-admin-actions">
                                <button type="submit" class="button button-primary"><?php esc_html_e('Add as New Page', 'ajnanda'); ?></button>
                                <?php if (function_exists('ajnanda_get_preview_url')) : ?>
                                    <a
                                        class="button ajnanda-preview-link"
                                        target="_blank"
                                        rel="noopener"
                                        data-preview-base="<?php echo esc_url(ajnanda_get_preview_url($slug)); ?>"
                                        href="<?php echo esc_url(ajnanda_get_preview_url($slug, $site_scheme)); ?>"
                                    ><?php esc_html_e('Preview', 'ajnanda'); ?> ↗</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<script>
(function () {
    // Show a warning on any card where the chosen scheme differs from the
    // site-wide default — a lightweight nudge toward consistency, not a
    // hard block. Vanilla JS, no build step, scoped to this admin screen.
    document.querySelectorAll('.ajnanda-page-design-form').forEach(function (form) {
        var select = form.querySelector('.ajnanda-scheme-select');
        var warning = form.querySelector('.ajnanda-scheme-warning');
        var previewLink = form.querySelector('.ajnanda-preview-link');
        if (!select) {
            return;
        }
        var siteScheme = select.getAttribute('data-site-scheme');
        var update = function () {
            if (warning) {
                warning.hidden = (select.value === siteScheme);
            }
            if (previewLink) {
                var url = new URL(previewLink.getAttribute('data-preview-base'), window.location.href);
                url.searchParams.set('scheme', select.value);
                previewLink.href = url.toString();
            }
        };
        select.addEventListener('change', update);
        update();
    });
})();
</script>
