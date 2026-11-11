<?php
/**
 * wp-admin UI polish for the screens that do not get PHP-built tiles.
 *
 * 1. List screens (Posts, Pages, Plugins, Users, Comments): the plain "All (12) | Active (3) | ..."
 *    status links become the same colored tiles used on the Posts list. Done in the browser from the
 *    links WordPress already prints, so the markup still works without JavaScript and other plugins'
 *    views are kept. Empty tiles are dropped, the current view is highlighted.
 * 2. Posts/Pages lists: the tiles and chips move under the page title, the search box shares a row
 *    with the bulk actions instead of leaving a blank band, and an Image column that is empty for
 *    every visible row is hidden.
 * 3. All admin screens except the block editors: tighter spacing (headings, notices, form tables,
 *    cards). Colors, fonts and behavior are not changed.
 *
 * Admin only: nothing here is loaded on the public site.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Screens whose status links become tiles. */
function ajnanda_ui_tile_screens() {
    return array('edit', 'plugins', 'plugins-network', 'users', 'users-network', 'edit-comments');
}

/** True on admin screens where spacing polish applies (everything except the block/site editors). */
function ajnanda_ui_polish_screen() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen) {
        return false;
    }
    if ((method_exists($screen, 'is_block_editor') && $screen->is_block_editor()) || in_array($screen->base, array('site-editor', 'customize', 'widgets'), true)) {
        return false;
    }
    return true;
}

function ajnanda_ui_print_assets() {
    if (!ajnanda_ui_polish_screen()) {
        return;
    }
    $screen = get_current_screen();
    ?>
    <style id="ajn-admin-ui">
        /* ---- spacing polish (all admin screens) ---- */
        .wrap{margin-top:10px}
        .wrap > h1,.wrap h1.wp-heading-inline{margin:6px 8px 8px 0;padding:0}
        .wrap .notice,.wrap div.updated,.wrap div.error{margin:8px 0}
        .form-table th{padding:12px 10px 12px 0}
        .form-table td{padding:10px 10px}
        .wrap .card{max-width:none;margin-top:12px;padding:.6em 1.2em .8em}
        .postbox .inside{margin:10px 0 0}
        .tablenav{margin:6px 0}
        /* ---- status links as colored tiles ---- */
        .ajn-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:8px;margin:10px 0 8px}
        .ajn-tile{display:flex;flex-direction:column;gap:2px;padding:10px 14px;background:color-mix(in srgb,var(--c) 12%,#fff);border:1px solid color-mix(in srgb,var(--c) 35%,#fff);border-left:5px solid var(--c);border-radius:4px;text-decoration:none;color:#1d2327}
        .ajn-tile b{font-size:24px;line-height:1.1;font-weight:700;color:var(--c)}
        .ajn-tile span{font-size:12px;font-weight:600;color:#3c434a}
        .ajn-tile:hover{background:color-mix(in srgb,var(--c) 22%,#fff);color:#1d2327}
        .ajn-tile.is-active{background:var(--c);border-color:var(--c)}
        .ajn-tile.is-active b,.ajn-tile.is-active span{color:#fff}
        .ajn-chips{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 8px}
        .ajn-chip{padding:3px 10px;background:#fff;border:1px solid #dcdcde;border-radius:999px;font-size:12px;text-decoration:none;color:#3c434a}
        .ajn-chip:hover{border-color:#2271b1;color:#2271b1}
        .ajn-chip.is-active{background:#2271b1;border-color:#2271b1;color:#fff}
        <?php if (in_array($screen->base, ajnanda_ui_tile_screens(), true)) : ?>
        /* ---- list screens: search box shares the bulk-actions row (no blank band) ---- */
        @media (min-width:783px){
            .wrap form > p.search-box{margin:0 0 0 12px;float:right}
            .wrap form > .tablenav.top{clear:none}
        }
        <?php endif; ?>
    </style>
    <?php if (in_array($screen->base, ajnanda_ui_tile_screens(), true)) : ?>
    <script id="ajn-admin-ui-js">
    document.addEventListener('DOMContentLoaded', function () {
        var palette = ['#4f46e5', '#0891b2', '#7c3aed', '#db2777', '#0d9488', '#d97706', '#64748b'];
        var byClass = {
            all: '#2563eb', publish: '#16a34a', active: '#16a34a', approved: '#16a34a', 'auto-update-enabled': '#16a34a',
            draft: '#d97706', inactive: '#d97706', moderated: '#d97706', pending: '#d97706', 'auto-update-disabled': '#64748b',
            update: '#dc2626', 'update-plugins': '#dc2626', spam: '#ea580c', trash: '#dc2626',
            mine: '#4f46e5', recently_activated: '#0d9488', mustuse: '#64748b', dropins: '#64748b',
            upgrade: '#ea580c', 'search-results': '#2563eb'
        };

        // 1. Status links -> tiles (only where the PHP tiles of the Posts list are not already present).
        var list = document.querySelector('.wrap ul.subsubsub');
        if (list && !document.querySelector('.ajn-tiles')) {
            var tiles = document.createElement('div');
            tiles.className = 'ajn-tiles';
            var idx = 0;
            list.querySelectorAll('li').forEach(function (li) {
                var a = li.querySelector('a');
                if (!a) { return; }
                var countEl = a.querySelector('.count') || li.querySelector('.count');
                var n = countEl ? parseInt(countEl.textContent.replace(/[^0-9]/g, ''), 10) : NaN;
                var labelEl = a.cloneNode(true);
                var c = labelEl.querySelector('.count'); if (c) { c.remove(); }
                var label = labelEl.textContent.replace(/\s+/g, ' ').trim();
                var active = a.classList.contains('current') || li.classList.contains('current');
                var first = idx === 0; idx++;
                if (n === 0 && !active && !first) { return; }   // empty tiles are dropped
                var key = (li.className || '').split(' ')[0];
                var color = byClass[key] || palette[(idx - 1) % palette.length];
                var tile = document.createElement('a');
                tile.className = 'ajn-tile' + (active ? ' is-active' : '');
                tile.href = a.href;
                tile.style.setProperty('--c', color);
                var b = document.createElement('b'); b.textContent = isNaN(n) ? '•' : n.toLocaleString();
                var s = document.createElement('span'); s.textContent = label;
                tile.appendChild(b); tile.appendChild(s);
                tiles.appendChild(tile);
            });
            if (tiles.children.length) { list.replaceWith(tiles); }
        }

        // 2. Posts/Pages lists: tiles and chips go under the page title.
        var end = document.querySelector('.wrap .wp-header-end');
        if (end && document.body.classList.contains('edit-php')) {
            var anchor = end;
            var seen = [];
            document.querySelectorAll('.ajn-tiles, .ajn-chips').forEach(function (el) {
                if (seen.indexOf(el) !== -1 || end.contains(el)) { return; }
                seen.push(el);
                anchor.insertAdjacentElement('afterend', el);
                anchor = el;
            });
        }

        // 3. An Image column that is empty for every visible row only takes space: hide it.
        var th = document.querySelector('th.column-ajn_thumb');
        if (th) {
            var any = false;
            document.querySelectorAll('td.column-ajn_thumb').forEach(function (td) { if (td.querySelector('img')) { any = true; } });
            if (!any) {
                document.querySelectorAll('.column-ajn_thumb').forEach(function (el) { el.style.display = 'none'; });
            }
        }
    });
    </script>
    <?php endif; ?>
    <?php
}
add_action('admin_head', 'ajnanda_ui_print_assets', 30);
