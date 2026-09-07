<?php
/**
 * AJNanda admin: Starter Sites screen.
 *
 * One starter site's panel is shown at a time, chosen from a dropdown
 * (data-ajnanda-panel-select, inc/admin/assets/admin.js) instead of every
 * starter's full detail stacked on one very long page. Each page within
 * the active panel is a card with a live thumbnail — a scaled-down
 * iframe of the exact same non-destructive preview URL the "Preview"
 * button already opens (inc/preview.php) — not a new screenshot system,
 * just the existing preview engine shown small instead of full-size.
 * Thumbnails only load for the currently active panel (lazy, via
 * iframe[data-src]) so switching the dropdown doesn't fetch every
 * starter's pages at once.
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

// Default to whichever starter's status-preview diff table is being shown
// (so following "Preview Import" from elsewhere doesn't land on a hidden
// panel), otherwise the first starter in the list.
$initial_slug = ($preview && isset($starters[$preview['slug']])) ? $preview['slug'] : (string) array_key_first($starters);
?>
<div class="wrap ajnanda-admin-wrap">
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

    <div class="ajnanda-starter-select-wrap">
        <label for="ajnanda-starter-select"><strong><?php esc_html_e('Choose a starter site', 'ajnanda'); ?></strong></label><br>
        <select id="ajnanda-starter-select" data-ajnanda-panel-select="#ajnanda-starter-panels">
            <?php foreach ($starters as $slug => $starter) : ?>
                <option value="<?php echo esc_attr($slug); ?>" <?php selected($slug, $initial_slug); ?>><?php echo esc_html($starter['label']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="ajnanda-starter-panels">
    <?php foreach ($starters as $slug => $starter) : ?>
        <div
            class="ajnanda-starter-panel<?php echo $slug === $initial_slug ? ' is-active' : ''; ?>"
            data-ajnanda-panel="<?php echo esc_attr($slug); ?>"
            id="starter-<?php echo esc_attr($slug); ?>"
        >
        <?php
        // Every thumbnail/preview link for this starter defaults to its
        // paired Site Kit's colors/fonts (if it has one) instead of the
        // site's real current colors — otherwise every starter previews
        // identically in whatever's actually saved (usually the default
        // blue), which defeats the point of a starter designed around a
        // specific look.
        $kit_colors   = '';
        $kit_font     = '';
        $kit_label    = '';
        if (!empty($starter['site_kit']) && function_exists('ajnanda_get_site_kits')) {
            $all_kits = ajnanda_get_site_kits();
            if (isset($all_kits[$starter['site_kit']])) {
                $kit_colors = $all_kits[$starter['site_kit']]['color_scheme'];
                $kit_font   = $all_kits[$starter['site_kit']]['font_pairing'];
                $kit_label  = $all_kits[$starter['site_kit']]['label'];
            }
        }
        ?>
        <div class="ajnanda-admin-section">
            <h2><?php echo esc_html($starter['label']); ?> <span class="ajnanda-admin-pill"><?php echo esc_html($slug); ?></span></h2>

            <div class="ajnanda-admin-grid">
                <?php foreach ($starter['pages'] as $page) :
                    $status     = isset($reports[$slug][$page['key']]['status']) ? $reports[$slug][$page['key']]['status'] : '';
                    $insite_url = function_exists('ajnanda_get_preview_url')
                        ? ajnanda_get_preview_url($page['page_design'], $kit_colors, $kit_font, array('starter' => $slug, 'page_key' => $page['key']))
                        : '';
                    $fresh_url  = function_exists('ajnanda_get_preview_url')
                        ? ajnanda_get_preview_url($page['page_design'], $kit_colors, $kit_font, array('starter' => $slug, 'page_key' => $page['key'], 'mode' => 'fresh'))
                        : '';
                    // The thumbnail shows the fresh-site view — that's the one for judging the starter itself.
                    $thumb_url  = $fresh_url ?: $insite_url;
                ?>
                    <div class="ajnanda-admin-card ajnanda-starter-page-tile">
                        <?php if ($thumb_url) : ?>
                            <div class="ajnanda-thumb">
                                <iframe data-src="<?php echo esc_url($thumb_url); ?>" title="<?php echo esc_attr($page['title']); ?>" tabindex="-1"></iframe>
                                <a
                                    class="ajnanda-thumb-overlay ajnanda-preview-link"
                                    href="<?php echo esc_url($thumb_url); ?>"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="<?php echo esc_attr(sprintf(__('Preview %s', 'ajnanda'), $page['title'])); ?>"
                                ></a>
                            </div>
                        <?php endif; ?>
                        <div class="ajnanda-starter-page-tile-body">
                            <strong><?php echo esc_html($page['title']); ?></strong> <code>/<?php echo esc_html($page['slug']); ?>/</code>
                            <?php if ($status) : ?>
                                <div style="margin-top:6px;">
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
                                </div>
                            <?php endif; ?>
                            <?php if ($fresh_url || $insite_url) : ?>
                                <div class="ajnanda-starter-page-tile-actions">
                                    <?php if ($fresh_url) : ?>
                                        <a class="button button-small ajnanda-preview-link" target="_blank" rel="noopener" href="<?php echo esc_url($fresh_url); ?>">
                                            <?php esc_html_e('Fresh site', 'ajnanda'); ?> ↗
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($insite_url) : ?>
                                        <a class="button button-small ajnanda-preview-link" target="_blank" rel="noopener" href="<?php echo esc_url($insite_url); ?>">
                                            <?php esc_html_e('In this site', 'ajnanda'); ?> ↗
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php
            $whole_fresh  = function_exists('ajnanda_get_starter_preview_url') ? ajnanda_get_starter_preview_url($slug, '', '', '', 'fresh') : null;
            $whole_insite = function_exists('ajnanda_get_starter_preview_url') ? ajnanda_get_starter_preview_url($slug, '', '', '', 'insite') : null;
            ?>
            <?php if ($whole_fresh || $whole_insite) : ?>
                <div class="ajnanda-preview-modes">
                    <p class="ajnanda-preview-modes-title"><?php esc_html_e('Preview this starter two ways — nothing is saved either way', 'ajnanda'); ?></p>
                    <div class="ajnanda-preview-modes-grid">
                        <div>
                            <?php if ($whole_fresh) : ?>
                                <a href="<?php echo esc_url($whole_fresh); ?>" class="button button-hero button-primary ajnanda-preview-link" target="_blank" rel="noopener">
                                    <?php esc_html_e('Preview as a brand-new site', 'ajnanda'); ?> ↗
                                </a>
                            <?php endif; ?>
                            <p>
                                <?php esc_html_e('The starter\'s own navigation menu, no logo, demo content on every page — what a fresh WordPress install with this starter looks like. Click through every page from the menu.', 'ajnanda'); ?>
                                <em><?php esc_html_e('Caveat: your site title, footer, widget areas and any header buttons still come from this install — only the menu and logo are swapped out.', 'ajnanda'); ?></em>
                            </p>
                        </div>
                        <div>
                            <?php if ($whole_insite) : ?>
                                <a href="<?php echo esc_url($whole_insite); ?>" class="button button-hero ajnanda-preview-link" target="_blank" rel="noopener">
                                    <?php esc_html_e('Preview inside your current site', 'ajnanda'); ?> ↗
                                </a>
                            <?php endif; ?>
                            <p><?php esc_html_e('The starter\'s pages wrapped in this site\'s real header, menu, logo and footer — how they would sit in your site exactly as it is now. Starter pages your site has no equivalent of just show the demo content.', 'ajnanda'); ?></p>
                        </div>
                    </div>
                    <p class="ajnanda-preview-modes-note"><?php esc_html_e('Both apply the starter\'s Site Kit colors and fonts for the preview only. The per-page tiles above have the same two options.', 'ajnanda'); ?></p>
                </div>
            <?php endif; ?>

            <p style="display:flex;flex-wrap:wrap;gap:10px;">
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
                        <?php if ($kit_label) : ?>
                            <br>
                            <label>
                                <input type="checkbox" name="apply_kit" value="1">
                                <?php
                                printf(
                                    /* translators: %s: site kit label */
                                    esc_html__('Also apply the "%s" Site Kit site-wide', 'ajnanda'),
                                    esc_html($kit_label)
                                );
                                ?>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ajnanda-admin-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Import', 'ajnanda'); ?></button>
                </div>
            </form>
        </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
