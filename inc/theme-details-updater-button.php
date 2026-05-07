<?php
/**
 * AJNanda Theme Details Updater Button
 *
 * Adds an "Update AJNanda" button inside the WordPress theme details modal.
 *
 * Put this file at:
 *   inc/theme-details-updater-button.php
 *
 * functions.php:
 *   require_once get_template_directory() . '/inc/theme-details-updater-button.php';
 */

if (!defined('ABSPATH')) {
    exit;
}

function ajnanda_theme_details_updater_button_admin_footer() {
    global $pagenow;

    if ('themes.php' !== $pagenow) {
        return;
    }

    $current_theme = wp_get_theme();

    if ('AJNanda' !== $current_theme->get('Name')) {
        return;
    }

    $updater_url = add_query_arg(
        array(
            'action'   => 'ajnanda_theme_update_now',
            '_wpnonce' => wp_create_nonce('ajnanda_theme_update_now'),
        ),
        admin_url('admin-post.php')
    );
    ?>
    <script>
    (function() {
        var updaterUrl = <?php echo wp_json_encode($updater_url); ?>;

        function isAjnandaModalOpen() {
            var nameNodes = document.querySelectorAll('.theme-overlay .theme-name, .theme-overlay .theme-author, .theme-overlay .theme-version');
            var modalText = '';

            nameNodes.forEach(function(node) {
                modalText += ' ' + (node.textContent || '');
            });

            return modalText.indexOf('AJNanda') !== -1;
        }

        function addUpdaterButton() {
            var actions = document.querySelector('.theme-overlay .theme-actions');

            if (!actions) {
                return;
            }

            if (!isAjnandaModalOpen()) {
                return;
            }

            if (actions.querySelector('.ajnanda-updater-button')) {
                return;
            }

            var button = document.createElement('a');
            button.className = 'button button-secondary ajnanda-updater-button';
            button.href = updaterUrl;
            button.textContent = 'Update AJNanda';

            actions.appendChild(button);
        }

        document.addEventListener('click', function() {
            setTimeout(addUpdaterButton, 100);
            setTimeout(addUpdaterButton, 300);
            setTimeout(addUpdaterButton, 800);
        });

        document.addEventListener('keydown', function() {
            setTimeout(addUpdaterButton, 100);
            setTimeout(addUpdaterButton, 300);
        });

        if (window.MutationObserver) {
            var observer = new MutationObserver(function() {
                addUpdaterButton();
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        setInterval(addUpdaterButton, 1000);
    })();
    </script>

    <style>
        .theme-overlay .theme-actions .ajnanda-updater-button {
            margin-left: 6px;
        }
    </style>
    <?php
}
add_action('admin_footer', 'ajnanda_theme_details_updater_button_admin_footer');
