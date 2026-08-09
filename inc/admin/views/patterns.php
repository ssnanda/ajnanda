<?php
/**
 * AJNanda admin: Patterns reference screen (read-only).
 *
 * @package AJNanda
 * @var array<string,array> $patterns
 */

if (!defined('ABSPATH')) {
    exit;
}

$category_labels = array();
if (class_exists('WP_Block_Pattern_Categories_Registry')) {
    foreach (WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered() as $category) {
        $category_labels[$category['name']] = $category['label'];
    }
}

$groups = array();
foreach ($patterns as $slug => $pattern) {
    $categories = !empty($pattern['categories']) ? (array) $pattern['categories'] : array(__('Uncategorized', 'ajnanda'));
    $primary = $categories[0];
    $label = isset($category_labels[$primary]) ? $category_labels[$primary] : $primary;
    $groups[$label][$slug] = $pattern;
}
ksort($groups);
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Section Patterns', 'ajnanda'); ?></h1>
        <p><?php esc_html_e('Reference list of every AJNanda section pattern. This screen is read-only — insert a section from any page or post by opening the block inserter\'s Patterns tab and searching "AJNanda", or by typing "/" and the pattern name.', 'ajnanda'); ?></p>
    </div>

    <?php foreach ($groups as $group_label => $group_patterns) : ?>
        <div class="ajnanda-admin-section">
            <h2><?php echo esc_html($group_label); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'ajnanda'); ?></th>
                        <th><?php esc_html_e('Slug', 'ajnanda'); ?></th>
                        <th><?php esc_html_e('Description', 'ajnanda'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($group_patterns as $slug => $pattern) : ?>
                        <tr>
                            <td><?php echo esc_html(isset($pattern['title']) ? $pattern['title'] : ''); ?></td>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td><?php echo esc_html(isset($pattern['description']) ? $pattern['description'] : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
