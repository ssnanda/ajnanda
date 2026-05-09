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

    function init() {
        Array.prototype.slice.call(document.querySelectorAll('.aj-faq')).forEach(initFaq);
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
