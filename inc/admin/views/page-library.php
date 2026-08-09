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
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Page Library', 'ajnanda'); ?></h1>
        <p><?php esc_html_e('Complete page designs built from AJNanda section patterns. These also appear automatically in the "Choose a pattern" screen when you go to Pages → Add New — use this screen when you want to browse first, or to insert one directly.', 'ajnanda'); ?></p>
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
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field(AJNanda_Admin::NONCE_ACTION); ?>
                            <input type="hidden" name="action" value="ajnanda_insert_page_design">
                            <input type="hidden" name="page_design" value="<?php echo esc_attr($slug); ?>">
                            <p>
                                <input type="text" name="title" value="<?php echo esc_attr($pattern['title']); ?>" class="regular-text" aria-label="<?php esc_attr_e('New page title', 'ajnanda'); ?>">
                            </p>
                            <div class="ajnanda-admin-actions">
                                <button type="submit" class="button button-primary"><?php esc_html_e('Add as New Page', 'ajnanda'); ?></button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
