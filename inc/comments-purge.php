<?php
/**
 * AJNanda Comments Purge
 *
 * Adds a "Delete all comments" tool under Customize → Comments, shown only while
 * the "Enable Comments" toggle is unchecked. Turning comments off (functions.php,
 * ajnanda_comments_open_filter() and friends) only hides and blocks them — every
 * existing row stays in wp_comments. On a site that has accumulated thousands of
 * (usually spam) comments, this clears them out for good.
 *
 * Deletion runs in small AJAX batches through wp_delete_comment() rather than one
 * raw DELETE, so comment meta, child re-parenting, object caches, post comment
 * counts and plugin hooks (delete_comment / deleted_comment) are all handled the
 * same way core's own "Delete Permanently" does, and no single request can hit
 * PHP's max_execution_time however many comments there are.
 *
 * Only the comment types this theme's toggle is about are touched — regular
 * comments, pingbacks and trackbacks. Plugins also store their own data in
 * wp_comments under custom types (WooCommerce order notes and product reviews,
 * block-editor notes, …); those are left alone.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comment types the purge is allowed to delete. '' is how core stored regular
 * comments before WordPress 5.5 switched to 'comment'; older rows still carry it.
 */
function ajnanda_purgeable_comment_types() {
    return array('', 'comment', 'pingback', 'trackback');
}

function ajnanda_purgeable_comment_types_sql() {
    global $wpdb;
    $types = ajnanda_purgeable_comment_types();
    return $wpdb->prepare(implode(', ', array_fill(0, count($types), '%s')), $types);
}

/**
 * Count across every status (approved, pending, spam, trash) — the purge removes all
 * of them, so the number shown before confirming has to include all of them too.
 */
function ajnanda_count_purgeable_comments() {
    global $wpdb;
    return (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type IN (" . ajnanda_purgeable_comment_types_sql() . ')'
    );
}

function ajnanda_register_comments_purge_control($wp_customize) {
    if (!class_exists('WP_Customize_Control') || !current_user_can('manage_options')) {
        return;
    }

    if (!class_exists('AJNanda_Comments_Purge_Control')) {
        class AJNanda_Comments_Purge_Control extends WP_Customize_Control {
            public $type = 'ajnanda_comments_purge';

            public function render_content() {
                $count = ajnanda_count_purgeable_comments();
                ?>
                <div class="ajnanda-comments-purge" data-count="<?php echo esc_attr($count); ?>">
                    <span class="customize-control-title"><?php esc_html_e('Delete Existing Comments', 'ajnanda'); ?></span>
                    <p class="description">
                        <?php esc_html_e('Disabling comments only hides them — existing ones stay in the database. This permanently deletes every comment, pingback and trackback (approved, pending, spam and trash). It cannot be undone.', 'ajnanda'); ?>
                    </p>
                    <p class="ajnanda-comments-purge-status" aria-live="polite">
                        <?php
                        printf(
                            /* translators: %s: number of comments */
                            esc_html(_n('%s comment on this site.', '%s comments on this site.', $count, 'ajnanda')),
                            '<strong class="ajnanda-comments-purge-count">' . esc_html(number_format_i18n($count)) . '</strong>'
                        );
                        ?>
                    </p>
                    <button type="button" class="button ajnanda-comments-purge-button" <?php disabled($count, 0); ?>>
                        <?php esc_html_e('Delete all comments…', 'ajnanda'); ?>
                    </button>
                </div>
                <?php
            }
        }
    }

    $wp_customize->add_setting('ajnanda_comments_purge_noop', array(
        'sanitize_callback' => '__return_empty_string',
    ));

    $wp_customize->add_control(new AJNanda_Comments_Purge_Control(
        $wp_customize,
        'ajnanda_comments_purge_noop',
        array(
            'section'  => 'ajnanda_comments',
            'priority' => 20, // below the "Enable Comments" checkbox (default priority 10).
        )
    ));
}
add_action('customize_register', 'ajnanda_register_comments_purge_control', 20);

function ajnanda_comments_purge_control_assets() {
    if (!current_user_can('manage_options')) {
        return;
    }

    wp_add_inline_style('customize-controls', '
        .ajnanda-comments-purge { margin-top: 12px; padding-top: 16px; border-top: 1px solid #dcdcde; }
        .ajnanda-comments-purge-button.button { color: #b32d2e; border-color: #b32d2e; }
        .ajnanda-comments-purge-button.button:hover, .ajnanda-comments-purge-button.button:focus { color: #fff; background: #b32d2e; border-color: #b32d2e; }
        .ajnanda-comments-purge-status.is-error { color: #b32d2e; }
    ');

    $handle = 'ajnanda-comments-purge-control';
    wp_register_script($handle, false, array('customize-controls', 'jquery'), false, true);
    wp_enqueue_script($handle);
    wp_localize_script($handle, 'ajnandaCommentsPurge', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ajnanda_purge_comments'),
        'i18n'    => array(
            /* translators: %s: number of comments */
            'confirm'  => __('This will permanently delete %s comments (including pending, spam and trash). This cannot be undone.', 'ajnanda'),
            'typeWord' => __('Type DELETE to confirm.', 'ajnanda'),
            /* translators: 1: comments deleted so far, 2: total comments */
            'progress' => __('Deleting… %1$s of %2$s', 'ajnanda'),
            /* translators: %s: number of comments deleted */
            'done'     => __('Deleted %s comments.', 'ajnanda'),
            /* translators: %s: number of comments deleted before the error */
            'error'    => __('Something went wrong after deleting %s comments. Reload and try again to continue.', 'ajnanda'),
        ),
    ));
    wp_add_inline_script($handle, "
        (function ($, api) {
            var cfg = window.ajnandaCommentsPurge;
            var fmt = function (s) {
                var args = Array.prototype.slice.call(arguments, 1);
                var i = 0;
                return s.replace(/%(\\d\\$)?s/g, function (m, pos) {
                    return pos ? args[parseInt(pos, 10) - 1] : args[i++];
                });
            };
            var num = function (n) { return Number(n).toLocaleString(); };

            api.control('ajnanda_comments_purge_noop', function (control) {
                // Only offer the purge while the toggle reads as off — never next to
                // a checked \"Enable Comments\" box.
                // Goes through control.active (not a bare show/hide) so the preview's
                // own active-state sync on refresh can't flip it back on.
                api('enable_comments', function (setting) {
                    control.active.validate = function () { return !setting.get(); };
                    var sync = function () { control.active.set(!setting.get()); };
                    sync();
                    setting.bind(sync);
                });
            });

            $(document).on('click', '.ajnanda-comments-purge-button', function () {
                var btn    = $(this);
                var wrap   = btn.closest('.ajnanda-comments-purge');
                var status = wrap.find('.ajnanda-comments-purge-status');
                var total  = parseInt(wrap.attr('data-count'), 10) || 0;
                var deleted = 0;

                if (!total) { return; }

                var answer = window.prompt(fmt(cfg.i18n.confirm, num(total)) + '\\n\\n' + cfg.i18n.typeWord);
                if (answer === null || answer.trim() !== 'DELETE') { return; }

                btn.prop('disabled', true);
                status.removeClass('is-error').text(fmt(cfg.i18n.progress, 0, num(total)));

                var step = function () {
                    $.post(cfg.ajaxUrl, { action: 'ajnanda_purge_comments', nonce: cfg.nonce })
                        .done(function (res) {
                            if (!res || !res.success) { return fail(); }
                            deleted += res.data.deleted;
                            var remaining = res.data.remaining;
                            if (remaining > 0 && res.data.deleted > 0) {
                                status.text(fmt(cfg.i18n.progress, num(deleted), num(deleted + remaining)));
                                step();
                                return;
                            }
                            wrap.attr('data-count', remaining);
                            status.text(fmt(cfg.i18n.done, num(deleted)));
                            btn.prop('disabled', remaining === 0);
                        })
                        .fail(fail);
                };
                var fail = function () {
                    status.addClass('is-error').text(fmt(cfg.i18n.error, num(deleted)));
                    btn.prop('disabled', false);
                    wrap.attr('data-count', Math.max(total - deleted, 0));
                };

                step();
            });
        })(jQuery, wp.customize);
    ");
}
add_action('customize_controls_enqueue_scripts', 'ajnanda_comments_purge_control_assets');

/**
 * Delete one batch and report how many are left; the Customizer control keeps
 * calling until nothing remains. Each request also stops early after a few seconds
 * so a slow host (or heavy delete_comment hooks) never times out mid-batch.
 */
function ajnanda_ajax_purge_comments() {
    check_ajax_referer('ajnanda_purge_comments', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You are not allowed to do this.', 'ajnanda')), 403);
    }

    global $wpdb;

    $batch_size = 250;
    $time_limit = 15; // seconds
    $started    = microtime(true);
    $deleted    = 0;

    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_type IN (" . ajnanda_purgeable_comment_types_sql() . ') ORDER BY comment_ID DESC LIMIT %d',
        $batch_size
    ));

    // Defer per-post comment_count recalculation until the batch is done, so a post
    // with 500 comments is recounted once rather than 500 times.
    wp_defer_comment_counting(true);

    foreach ($ids as $id) {
        if (wp_delete_comment((int) $id, true)) {
            $deleted++;
        }
        if ((microtime(true) - $started) > $time_limit) {
            break;
        }
    }

    wp_defer_comment_counting(false);

    $remaining = ajnanda_count_purgeable_comments();

    // Every row in the batch failed to delete — stop the client loop instead of
    // retrying the same IDs forever.
    if (0 === $deleted && $remaining > 0) {
        wp_send_json_error(array('message' => __('Comments could not be deleted.', 'ajnanda'), 'remaining' => $remaining), 500);
    }

    wp_send_json_success(array(
        'deleted'   => $deleted,
        'remaining' => $remaining,
    ));
}
add_action('wp_ajax_ajnanda_purge_comments', 'ajnanda_ajax_purge_comments');
