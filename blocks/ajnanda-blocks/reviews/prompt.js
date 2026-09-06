(function () {
    'use strict';
    function mount() {
        document.querySelectorAll('[data-aj-rate-us]').forEach(function (root) {
            if (root.dataset.ready) return;
            var stars = root.querySelector('.aj-rate-us__stars');
            var panel = root.querySelector('.aj-rate-us__choices');
            var status = root.querySelector('.aj-rate-us__status');
            var closeButton = root.querySelector('.aj-rate-us__close');
            var buttons = Array.from(stars.querySelectorAll('[data-rating]'));
            var selected = null;
            function close(restoreFocus) {
                panel.hidden = true;
                buttons.forEach(function (button) { button.setAttribute('aria-expanded', 'false'); });
                if (restoreFocus && selected) selected.focus();
            }
            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    selected = button;
                    buttons.forEach(function (other) {
                        other.setAttribute('aria-expanded', other === button ? 'true' : 'false');
                        other.classList.toggle('is-selected', Number(other.dataset.rating) <= Number(button.dataset.rating));
                    });
                    // Only the explanatory text changes. Destination URLs never depend on rating.
                    panel.hidden = false;
                    status.textContent = button.dataset.message;
                });
            });
            closeButton.addEventListener('click', function () { close(true); });
            root.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !panel.hidden) { event.preventDefault(); close(true); }
            });
            root.addEventListener('focusout', function () {
                window.setTimeout(function () { if (!root.contains(document.activeElement)) close(false); }, 0);
            });
            document.addEventListener('click', function (event) { if (!root.contains(event.target)) close(false); });
            root.dataset.ready = 'true';
            root.classList.add('is-enhanced');
            stars.hidden = false;
            closeButton.hidden = false;
            close(false);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount); else mount();
}());
