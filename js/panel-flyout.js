/**
 * AJNanda floater panel flyout submenus.
 *
 * Enqueued only when a panel uses Appearance → Menus → Submenu style →
 * Flyout. The CSS in functions.php already opens flyouts on :hover and
 * :focus-within; this adds:
 * - aria-expanded kept in sync on every item with sub-items;
 * - "#" heading items (role="button") toggle on click/tap, Enter and Space;
 * - keyboard focus opens the focused item's sub-items at every width
 *   (below 922px sub-items only open via .is-open once this script runs);
 * - Escape closes the innermost open flyout and returns focus to its item.
 */
(function () {
    'use strict';

    var desktop = window.matchMedia ? window.matchMedia('(min-width: 922px)') : { matches: true };

    function triggerOf(li) {
        for (var i = 0; i < li.children.length; i++) {
            var child = li.children[i];
            if ('A' === child.tagName || child.classList.contains('ajnanda-panel-menu-placeholder')) {
                return child;
            }
        }
        return null;
    }

    function parentItems(nav, node) {
        var items = [];
        while (node && node !== nav) {
            if (node.classList && node.classList.contains('menu-item-has-children')) {
                items.push(node);
            }
            node = node.parentNode;
        }
        return items;
    }

    function isOpen(li) {
        if (li.classList.contains('is-dismissed')) {
            return false;
        }
        if (li.classList.contains('is-open')) {
            return true;
        }
        return desktop.matches && (li.matches(':hover') || li.contains(document.activeElement));
    }

    function init(nav) {
        var items = nav.querySelectorAll('.menu-item-has-children');
        var pointerActive = false;

        function sync() {
            for (var i = 0; i < items.length; i++) {
                var trigger = triggerOf(items[i]);
                if (trigger) {
                    trigger.setAttribute('aria-expanded', isOpen(items[i]) ? 'true' : 'false');
                }
            }
        }

        function syncSoon() {
            window.setTimeout(sync, 0);
        }

        function close(li) {
            li.classList.remove('is-open');
            var open = li.querySelectorAll('.is-open');
            for (var i = 0; i < open.length; i++) {
                open[i].classList.remove('is-open');
            }
        }

        function toggle(li) {
            if (isOpen(li)) {
                close(li);
                if (desktop.matches) {
                    li.classList.add('is-dismissed');
                }
            } else {
                li.classList.remove('is-dismissed');
                li.classList.add('is-open');
            }
            sync();
        }

        nav.classList.add('ajnanda-panel-menu-js');

        nav.addEventListener('pointerdown', function () {
            pointerActive = true;
        });
        document.addEventListener('pointerup', function () {
            window.setTimeout(function () {
                pointerActive = false;
            }, 0);
        });

        nav.addEventListener('focusin', function (event) {
            if (!pointerActive) {
                parentItems(nav, event.target).forEach(function (li) {
                    if (!li.classList.contains('is-dismissed')) {
                        li.classList.add('is-open');
                    }
                });
            }
            syncSoon();
        });

        nav.addEventListener('focusout', function () {
            window.setTimeout(function () {
                for (var i = 0; i < items.length; i++) {
                    if (!items[i].contains(document.activeElement)) {
                        items[i].classList.remove('is-open', 'is-dismissed');
                    }
                }
                sync();
            }, 0);
        });

        nav.addEventListener('click', function (event) {
            var heading = event.target.closest ? event.target.closest('.ajnanda-panel-menu-placeholder[role="button"]') : null;
            if (heading && nav.contains(heading)) {
                toggle(heading.parentNode);
            }
        });

        nav.addEventListener('keydown', function (event) {
            var target = event.target;

            if (('Enter' === event.key || ' ' === event.key) && target.matches('.ajnanda-panel-menu-placeholder[role="button"]')) {
                event.preventDefault();
                toggle(target.parentNode);
                return;
            }

            if ('Escape' === event.key) {
                var open = parentItems(nav, target).filter(isOpen);
                // When focus is on an item's own trigger, its flyout is the innermost one.
                if (!open.length) {
                    return;
                }
                var li = open[0];
                close(li);
                li.classList.add('is-dismissed');
                var trigger = triggerOf(li);
                if (trigger) {
                    trigger.focus();
                }
                sync();
            }
        });

        for (var i = 0; i < items.length; i++) {
            items[i].addEventListener('mouseenter', syncSoon);
            items[i].addEventListener('mouseleave', function () {
                if (desktop.matches) {
                    this.classList.remove('is-dismissed');
                    if (!this.contains(document.activeElement)) {
                        close(this);
                    }
                }
                syncSoon();
            });
        }

        var onBreakpoint = function () {
            for (var i = 0; i < items.length; i++) {
                items[i].classList.remove('is-open', 'is-dismissed');
            }
            sync();
        };
        if (desktop.addEventListener) {
            desktop.addEventListener('change', onBreakpoint);
        } else if (desktop.addListener) {
            desktop.addListener(onBreakpoint);
        }

        sync();
    }

    var navs = document.querySelectorAll('.ajnanda-panel-menu-flyout');
    for (var n = 0; n < navs.length; n++) {
        init(navs[n]);
    }
})();
