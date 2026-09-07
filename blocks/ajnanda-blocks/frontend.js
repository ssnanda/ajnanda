(function() {
    function initFaq(faq) {
        var icon = faq.getAttribute('data-icon') || '+';
        var activeIcon = faq.getAttribute('data-active-icon') || '-';
        var collapseOther = faq.getAttribute('data-collapse-other-items') === 'true';
        var expandFirst = faq.getAttribute('data-expand-first-item') === 'true';
        var enableToggle = faq.getAttribute('data-enable-toggle') !== 'false';
        var items = Array.prototype.slice.call(faq.querySelectorAll(':scope > details'));

        items.forEach(function(item, index) {
            var summary = item.querySelector('summary');

            if (summary) {
                summary.setAttribute('data-aj-icon', icon);
                summary.setAttribute('data-aj-active-icon', activeIcon);
            }

            if (!enableToggle) {
                item.setAttribute('open', 'open');
            } else if (expandFirst && index === 0) {
                item.setAttribute('open', 'open');
            } else if (index > 0) {
                item.removeAttribute('open');
            }

            item.addEventListener('toggle', function() {
                if (!enableToggle && !item.open) {
                    item.setAttribute('open', 'open');
                    return;
                }

                if (!collapseOther || !item.open) {
                    return;
                }

                items.forEach(function(other) {
                    if (other !== item) {
                        other.removeAttribute('open');
                    }
                });
            });
        });
    }

    // ---- Countdown (ajnanda/countdown) --------------------------------------
    // Saved markup carries data-target-date; turn the static <strong> date into
    // a live "Nd HH:MM:SS" tick. No target / past target => a settled zero.
    function initCountdown(node) {
        var target = Date.parse(node.getAttribute('data-target-date') || '');
        var out = node.querySelector('strong');
        if (!out || isNaN(target)) { return; }

        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function tick() {
            var left = Math.max(0, target - Date.now());
            var s = Math.floor(left / 1000);
            var d = Math.floor(s / 86400);
            out.textContent = d + 'd ' + pad(Math.floor(s % 86400 / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
            if (left > 0) { window.setTimeout(tick, 1000 - (Date.now() % 1000)); }
            else { node.setAttribute('data-elapsed', 'true'); }
        }
        tick();
    }

    // ---- Reveal-on-scroll helpers for Counter / Progress Bar ----------------
    function onceVisible(node, run) {
        if (!('IntersectionObserver' in window)) { run(); return; }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { io.disconnect(); run(); }
            });
        }, { threshold: 0.4 });
        io.observe(node);
    }

    // ajnanda/counter — count up to the number already saved in <strong>.
    function initCounter(node) {
        var out = node.querySelector('strong');
        if (!out) { return; }
        var endText = out.textContent.trim();
        var end = parseFloat(endText.replace(/[^0-9.\-]/g, ''));
        if (isNaN(end)) { return; }
        var decimals = (endText.split('.')[1] || '').length;
        var suffix = endText.replace(/^[\s0-9.,\-]+/, '');
        var prefix = endText.replace(/[\s0-9.,\-].*$/, '').replace(/[0-9.,\-]+$/, '');

        onceVisible(node, function () {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
            var start = null;
            var dur = 1200;
            function frame(now) {
                if (start === null) { start = now; }
                var p = Math.min(1, (now - start) / dur);
                var eased = 1 - Math.pow(1 - p, 3);
                out.textContent = prefix + (end * eased).toFixed(decimals) + suffix;
                if (p < 1) { window.requestAnimationFrame(frame); }
                else { out.textContent = endText; }
            }
            out.textContent = prefix + (0).toFixed(decimals) + suffix;
            window.requestAnimationFrame(frame);
        });
    }

    // ajnanda/progress-bar — grow the fill from 0 to its saved width.
    function initProgress(node) {
        var fill = node.querySelector('.aj-progress__track > i');
        if (!fill) { return; }
        var toWidth = fill.style.width || '0%';
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
        fill.style.width = '0%';
        fill.style.transition = 'width 1s ease';
        onceVisible(node, function () {
            window.requestAnimationFrame(function () { fill.style.width = toWidth; });
        });
    }

    // ---- Modal (ajnanda/modal) --------------------------------------------
    // render_block wrapped the saved content in a trigger button + native
    // <dialog>; native showModal() handles focus-trap and Esc for us.
    function initModal(wrap) {
        var dlg = wrap.querySelector('dialog');
        var opener = wrap.querySelector('[data-aj-modal-open]');
        if (!dlg || !opener) { return; }
        opener.addEventListener('click', function () {
            if (typeof dlg.showModal === 'function') { dlg.showModal(); }
            else { dlg.setAttribute('open', ''); }
        });
        dlg.addEventListener('click', function (e) {
            if (e.target === dlg && typeof dlg.close === 'function') { dlg.close(); }
        });
    }

    // ---- Tabs (ajnanda/tabs) ---------------------------------------------
    function initTabs(root) {
        var tabs = Array.prototype.slice.call(root.querySelectorAll('.aj-tabs__list > [role="tab"]'));
        if (tabs.length < 2) { return; }
        var panels = tabs.map(function (tab) {
            return document.getElementById(tab.getAttribute('aria-controls'));
        });

        function select(i, focus) {
            tabs.forEach(function (tab, j) {
                var on = i === j;
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                tab.tabIndex = on ? 0 : -1;
                if (panels[j]) { panels[j].hidden = !on; }
            });
            if (focus && tabs[i]) { tabs[i].focus(); }
        }

        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { select(i); });
            tab.addEventListener('keydown', function (e) {
                var last = tabs.length - 1;
                var next = e.key === 'ArrowRight' ? (i === last ? 0 : i + 1)
                    : e.key === 'ArrowLeft' ? (i === 0 ? last : i - 1)
                    : e.key === 'Home' ? 0
                    : e.key === 'End' ? last
                    : -1;
                if (next !== -1) { e.preventDefault(); select(next, true); }
            });
        });
    }

    // ---- Social share (ajnanda/social-share) ------------------------------
    function initSocialShare(node) {
        var url = window.location.href;
        var enc = encodeURIComponent(url);
        var title = encodeURIComponent(document.title || '');
        var map = {
            facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + enc,
            linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + enc,
            x: 'https://twitter.com/intent/tweet?url=' + enc + '&text=' + title,
            twitter: 'https://twitter.com/intent/tweet?url=' + enc + '&text=' + title,
            email: 'mailto:?subject=' + title + '&body=' + enc
        };

        Array.prototype.slice.call(node.querySelectorAll('a')).forEach(function (link) {
            var key = (link.textContent || '').trim().toLowerCase();
            if (map[key]) {
                link.setAttribute('href', map[key]);
                if (key !== 'email') { link.setAttribute('target', '_blank'); link.setAttribute('rel', 'noopener'); }
                return;
            }
            if (key === 'share') {
                link.setAttribute('href', url);
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (navigator.share) { navigator.share({ url: url, title: document.title }).catch(function () {}); }
                    else if (navigator.clipboard) { navigator.clipboard.writeText(url); }
                });
            }
        });
    }

    function init() {
        // ajnanda/accordion shares the details-based markup and gets the same
        // data attributes injected server-side (render_block), so it reuses initFaq.
        Array.prototype.slice.call(document.querySelectorAll('.aj-faq, .aj-accordion')).forEach(initFaq);
        Array.prototype.slice.call(document.querySelectorAll('.aj-countdown[data-target-date]')).forEach(initCountdown);
        Array.prototype.slice.call(document.querySelectorAll('.aj-counter')).forEach(initCounter);
        Array.prototype.slice.call(document.querySelectorAll('.aj-progress')).forEach(initProgress);
        Array.prototype.slice.call(document.querySelectorAll('.aj-modal[data-aj-modal]')).forEach(initModal);
        Array.prototype.slice.call(document.querySelectorAll('.aj-tabs[data-aj-tabs]')).forEach(initTabs);
        Array.prototype.slice.call(document.querySelectorAll('.aj-social-share')).forEach(initSocialShare);
        Array.prototype.slice.call(document.querySelectorAll('.aj-icon-list')).forEach(function(list) {
            var parentType = list.getAttribute('data-icon-type') || 'icon';
            var parentIcon = list.getAttribute('data-icon') || '→';
            var parentImage = list.getAttribute('data-icon-image') || '';

            Array.prototype.slice.call(list.querySelectorAll('.aj-icon-list-item__marker')).forEach(function(marker) {
                var type = marker.getAttribute('data-icon-type') || 'inherit';
                var image = marker.getAttribute('data-icon-image') || '';

                if (type === 'inherit') {
                    marker.setAttribute('data-icon-type', parentType);
                    marker.setAttribute('data-icon', parentIcon);
                    image = parentImage;
                }

                if ((marker.getAttribute('data-icon-type') || '') === 'image' && image) {
                    marker.style.backgroundImage = 'url("' + image.replace(/"/g, '\\"') + '")';
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
