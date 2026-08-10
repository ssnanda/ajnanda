<?php
/**
 * AJNanda admin: Starter Sites screen.
 *
 * @package AJNanda
 * @var array<string,array> $starters
 * @var array<string,array> $reports Starter slug => AJNanda_Starter_Importer::preview() report, computed for every starter up front so "already imported" is visible without an extra click.
 * @var array{slug:string,report:array}|null $preview
 * @var array|null $notice
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-hero">
        <p class="ajnanda-admin-eyebrow"><?php esc_html_e('AJNanda', 'ajnanda'); ?></p>
        <h1><?php esc_html_e('Starter Sites', 'ajnanda'); ?></h1>
        <p><?php esc_html_e('Each starter site creates a coordinated set of pages built from AJNanda Page Designs, plus a primary navigation menu. Nothing already on your site is overwritten — re-running an import safely skips pages you already have, and content with a slug AJNanda does not own is never taken over.', 'ajnanda'); ?></p>
    </div>

    <?php if ($notice) : ?>
        <?php if (!empty($notice['error'])) : ?>
            <div class="notice notice-error"><p><?php echo esc_html($notice['error']); ?></p></div>
        <?php else : ?>
            <div class="notice notice-success">
                <p><strong><?php echo esc_html(sprintf(__('Import finished for "%s":', 'ajnanda'), $notice['slug'])); ?></strong></p>
                <ul style="margin:0 0 1em 1.5em;">
                    <?php foreach ((array) $notice['results'] as $key => $row) : ?>
                        <li>
                            <code><?php echo esc_html($key); ?></code> —
                            <span class="ajnanda-admin-status-<?php echo esc_attr($row['status']); ?>"><?php echo esc_html(str_replace('_', ' ', $row['status'])); ?></span>
                            <?php if (!empty($row['post_id'])) : ?>
                                (<a href="<?php echo esc_url(get_edit_post_link($row['post_id'])); ?>"><?php esc_html_e('edit', 'ajnanda'); ?></a>)
                            <?php endif; ?>
                            <?php if (!empty($row['message'])) : ?>
                                — <?php echo esc_html($row['message']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php foreach ($starters as $slug => $starter) : ?>
        <div class="ajnanda-admin-section" id="starter-<?php echo esc_attr($slug); ?>">
            <h2><?php echo esc_html($starter['label']); ?> <span class="ajnanda-admin-pill"><?php echo esc_html($slug); ?></span></h2>
            <p><?php echo esc_html($starter['description']); ?></p>

            <ul class="ajnanda-admin-page-list">
                <?php foreach ($starter['pages'] as $page) :
                    $status = isset($reports[$slug][$page['key']]['status']) ? $reports[$slug][$page['key']]['status'] : '';
                ?>
                    <li>
                        <span>
                            <?php echo esc_html($page['title']); ?> <code>/<?php echo esc_html($page['slug']); ?>/</code>
                            <?php if ($status) : ?>
                                <span class="ajnanda-admin-pill <?php echo esc_attr('already_imported' === $status ? 'is-success' : ('slug_conflict' === $status ? 'is-warning' : '')); ?>">
                                    <?php
                                    switch ($status) {
                                        case 'already_imported':
                                            esc_html_e('Already imported', 'ajnanda');
                                            break;
                                        case 'slug_conflict':
                                            esc_html_e('URL conflict', 'ajnanda');
                                            break;
                                        default:
                                            esc_html_e('Not imported yet', 'ajnanda');
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span style="display:flex;align-items:center;gap:10px;">
                            <span class="description"><?php echo esc_html($page['page_design']); ?></span>
                            <?php if (function_exists('ajnanda_get_preview_url')) : ?>
                                <a
                                    class="button button-small ajnanda-preview-link"
                                    target="_blank"
                                    rel="noopener"
                                    href="<?php echo esc_url(ajnanda_get_preview_url($page['page_design'])); ?>"
                                ><?php esc_html_e('Preview', 'ajnanda'); ?> ↗</a>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'ajnanda-starter-sites', 'ajnanda_preview' => $slug), admin_url('admin.php')) . '#starter-' . $slug); ?>" class="button">
                    <?php esc_html_e('Preview Import (no changes made)', 'ajnanda'); ?>
                </a>
            </p>

            <?php if ($preview && $preview['slug'] === $slug) : ?>
                <table class="ajnanda-admin-diff-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Page', 'ajnanda'); ?></th>
                            <th><?php esc_html_e('What will happen', 'ajnanda'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ((array) $preview['report'] as $key => $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['title']); ?> <code><?php echo esc_html($key); ?></code></td>
                                <td class="ajnanda-admin-status-<?php echo esc_attr($row['status']); ?>">
                                    <?php
                                    switch ($row['status']) {
                                        case 'create':
                                            esc_html_e('Will be created', 'ajnanda');
                                            break;
                                        case 'already_imported':
                                            esc_html_e('Already imported — will be skipped', 'ajnanda');
                                            break;
                                        case 'slug_conflict':
                                            esc_html_e('An unrelated page already uses this URL — a new page will be created with a different URL instead', 'ajnanda');
                                            break;
                                        default:
                                            echo esc_html($row['status']);
                                    }
                                    ?>
                                    <?php if (!empty($row['post_id'])) : ?>
                                        (<a href="<?php echo esc_url(get_edit_post_link($row['post_id'])); ?>"><?php esc_html_e('view existing', 'ajnanda'); ?></a>)
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field(AJNanda_Admin::NONCE_ACTION); ?>
                <input type="hidden" name="action" value="ajnanda_import_starter">
                <input type="hidden" name="starter" value="<?php echo esc_attr($slug); ?>">

                <div class="ajnanda-admin-options">
                    <div>
                        <strong><?php esc_html_e('Pages to import', 'ajnanda'); ?></strong><br>
                        <?php foreach ($starter['pages'] as $page) : ?>
                            <label><input type="checkbox" name="pages[]" value="<?php echo esc_attr($page['key']); ?>" checked> <?php echo esc_html($page['title']); ?></label><br>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <strong><?php esc_html_e('Status', 'ajnanda'); ?></strong><br>
                        <label><input type="radio" name="status" value="draft" checked> <?php esc_html_e('Draft (review before publishing)', 'ajnanda'); ?></label><br>
                        <label><input type="radio" name="status" value="publish"> <?php esc_html_e('Publish immediately', 'ajnanda'); ?></label>
                    </div>
                    <div>
                        <strong><?php esc_html_e('Site setup', 'ajnanda'); ?></strong><br>
                        <label><input type="checkbox" name="create_menu" value="1" checked> <?php esc_html_e('Build primary navigation menu (only if empty)', 'ajnanda'); ?></label><br>
                        <label><input type="checkbox" name="overwrite_menu" value="1"> <?php esc_html_e('Replace an existing primary menu', 'ajnanda'); ?></label><br>
                        <label><input type="checkbox" name="set_homepage" value="1"> <?php esc_html_e('Set as site homepage (only if homepage is unset or AJNanda-created)', 'ajnanda'); ?></label>
                    </div>
                </div>

                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: link to Color Schemes screen */
                        esc_html__('Tip: pick your colors first — every page below automatically follows whatever scheme is active, so there\'s nothing to recolor after importing. %s', 'ajnanda'),
                        '<a href="' . esc_url(admin_url('admin.php?page=ajnanda-color-schemes')) . '">' . esc_html__('Browse Color Schemes', 'ajnanda') . '</a>'
                    );
                    ?>
                </p>

                <div class="ajnanda-admin-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Import', 'ajnanda'); ?></button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
</div>
