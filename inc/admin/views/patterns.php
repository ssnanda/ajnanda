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
        <p><?php esc_html_e('Reference list of every AJNanda section pattern. Insert a section from any page or post by opening the block inserter\'s Patterns tab and searching "AJNanda", or by typing "/" and the pattern name — "Preview" here just shows what a section looks like on its own before you go looking for it.', 'ajnanda'); ?></p>
    </div>

    <p class="ajnanda-admin-search">
        <input
            type="search"
            class="regular-text"
            placeholder="<?php esc_attr_e('Filter by title, slug, or description…', 'ajnanda'); ?>"
            aria-label="<?php esc_attr_e('Filter section patterns', 'ajnanda'); ?>"
            data-ajnanda-filter
            data-ajnanda-filter-scope="#ajnanda-patterns-list"
        >
    </p>

    <div id="ajnanda-patterns-list">
        <?php foreach ($groups as $group_label => $group_patterns) : ?>
            <div class="ajnanda-admin-section" data-ajnanda-filter-group>
                <h2><?php echo esc_html($group_label); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Title', 'ajnanda'); ?></th>
                            <th><?php esc_html_e('Slug', 'ajnanda'); ?></th>
                            <th><?php esc_html_e('Description', 'ajnanda'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group_patterns as $slug => $pattern) :
                            $title = isset($pattern['title']) ? $pattern['title'] : '';
                            $description = isset($pattern['description']) ? $pattern['description'] : '';
                        ?>
                            <tr data-ajnanda-filter-item data-ajnanda-filter-text="<?php echo esc_attr(strtolower($title . ' ' . $slug . ' ' . $description)); ?>">
                                <td><?php echo esc_html($title); ?></td>
                                <td><code><?php echo esc_html($slug); ?></code></td>
                                <td><?php echo esc_html($description); ?></td>
                                <td>
                                    <?php if (function_exists('ajnanda_get_preview_url')) : ?>
                                        <a
                                            class="button button-small ajnanda-preview-link"
                                            target="_blank"
                                            rel="noopener"
                                            href="<?php echo esc_url(ajnanda_get_preview_url($slug)); ?>"
                                        ><?php esc_html_e('Preview', 'ajnanda'); ?> ↗</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>
