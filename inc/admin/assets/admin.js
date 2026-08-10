/**
 * Shared vanilla-JS behavior for every AJNanda admin screen. No build step,
 * no dependency — enqueued once (AJNanda_Admin::enqueue_assets()) rather
 * than repeated per-view inline <script> blocks like the earlier one-off
 * scripts on Page Library/Starter Sites.
 *
 * Three independent, opt-in-by-markup behaviors:
 *  1. Preview modal — any `.ajnanda-preview-link` opens in an in-page
 *     iframe overlay instead of a new tab, reusing the exact same
 *     non-destructive preview URL (inc/preview.php) either way; the
 *     modal keeps an "Open in new tab" link so nothing is lost.
 *  2. Select-driven panel switcher — a `select[data-ajnanda-panel-select]`
 *     shows the `[data-ajnanda-panel]` matching its value inside the
 *     container its data attribute points at; `iframe[data-src]` elements
 *     inside a panel only get their real `src` set the first time that
 *     panel becomes active (Starter Sites' thumbnail previews).
 *  3. Live filter — any `input[data-ajnanda-filter]` filters elements
 *     tagged `[data-ajnanda-filter-item]` inside the container named by
 *     its `data-ajnanda-filter-scope` selector, and hides
 *     `[data-ajnanda-filter-group]` wrappers left with no visible items.
 *
 * @package AJNanda
 */
(function () {
    'use strict';

    /* ---------------------------------------------------------------
     * 1. Preview modal
     * ------------------------------------------------------------- */

    function openPreviewModal(url, title) {
        var overlay = document.createElement('div');
        overlay.className = 'ajnanda-preview-modal-overlay';
        overlay.innerHTML =
            '<div class="ajnanda-preview-modal" role="dialog" aria-modal="true" aria-label="' + (title || 'AJNanda preview') + '">' +
                '<div class="ajnanda-preview-modal-bar">' +
                    '<span>' + (title || 'AJNanda Preview') + '</span>' +
                    '<span class="ajnanda-preview-modal-bar-actions">' +
                        '<a class="ajnanda-preview-modal-newtab" href="' + url + '" target="_blank" rel="noopener">Open in new tab ↗</a>' +
                        '<button type="button" class="ajnanda-preview-modal-close" aria-label="Close preview">&times;</button>' +
                    '</span>' +
                '</div>' +
                '<iframe src="' + url + '" title="' + (title || 'AJNanda preview') + '"></iframe>' +
            '</div>';
        document.body.appendChild(overlay);
        document.body.classList.add('ajnanda-modal-open');

        function close() {
            overlay.remove();
            document.body.classList.remove('ajnanda-modal-open');
            document.removeEventListener('keydown', onKeydown);
        }

        function onKeydown(e) {
            if (e.key === 'Escape') {
                close();
            }
        }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                close();
            }
        });
        overlay.querySelector('.ajnanda-preview-modal-close').addEventListener('click', close);
        document.addEventListener('keydown', onKeydown);
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest('.ajnanda-preview-link') : null;
        if (!link) {
            return;
        }
        e.preventDefault();
        var card = link.closest('.ajnanda-admin-card, tr');
        var title = card ? card.querySelector('h2, td') : null;
        openPreviewModal(link.href, title ? title.textContent.trim() : '');
    });

    /* ---------------------------------------------------------------
     * 2. Select-driven panel switcher (Starter Sites: one starter's
     *    panel visible at a time instead of every starter stacked on
     *    one long page) — any select[data-ajnanda-panel-select] shows
     *    the [data-ajnanda-panel] whose value matches, inside the
     *    container its data attribute points at. Thumbnail iframes
     *    inside a panel (iframe[data-src]) only get their real src set
     *    the first time that panel becomes active, so switching the
     *    dropdown doesn't fetch every starter's pages up front.
     * ------------------------------------------------------------- */

    document.querySelectorAll('[data-ajnanda-panel-select]').forEach(function (select) {
        var container = document.querySelector(select.getAttribute('data-ajnanda-panel-select'));
        if (!container) {
            return;
        }
        var panels = container.querySelectorAll('[data-ajnanda-panel]');

        function activate(key) {
            panels.forEach(function (panel) {
                var isMatch = panel.getAttribute('data-ajnanda-panel') === key;
                panel.classList.toggle('is-active', isMatch);
                if (isMatch) {
                    panel.querySelectorAll('iframe[data-src]').forEach(function (iframe) {
                        if (!iframe.getAttribute('src')) {
                            iframe.setAttribute('src', iframe.getAttribute('data-src'));
                        }
                    });
                }
            });
        }

        select.addEventListener('change', function () {
            activate(select.value);
        });

        activate(select.value);
    });

    /* ---------------------------------------------------------------
     * 3. Live filter
     * ------------------------------------------------------------- */

    document.querySelectorAll('[data-ajnanda-filter]').forEach(function (input) {
        var scopeSelector = input.getAttribute('data-ajnanda-filter-scope');
        var container = scopeSelector ? document.querySelector(scopeSelector) : document;
        if (!container) {
            return;
        }
        var items = container.querySelectorAll('[data-ajnanda-filter-item]');
        var groups = container.querySelectorAll('[data-ajnanda-filter-group]');

        input.addEventListener('input', function () {
            var term = input.value.trim().toLowerCase();

            items.forEach(function (item) {
                var haystack = (item.getAttribute('data-ajnanda-filter-text') || item.textContent || '').toLowerCase();
                var matches = !term || haystack.indexOf(term) !== -1;
                item.classList.toggle('ajnanda-filtered-out', !matches);
            });

            groups.forEach(function (group) {
                var anyVisible = group.querySelector('[data-ajnanda-filter-item]:not(.ajnanda-filtered-out)');
                group.classList.toggle('ajnanda-filtered-out', !anyVisible);
            });
        });
    });
})();
