(function () {
    'use strict';
    // Remove an already-open Google collection at expiry, including on return from a suspended tab.
    function mount() {
        document.querySelectorAll('[data-google-expires]').forEach(function (element) {
            var timer;
            function expire() {
                window.clearTimeout(timer);
                if (!element.isConnected) return;
                var remaining = Number(element.dataset.googleExpires) * 1000 - Date.now();
                if (!Number.isFinite(remaining) || remaining <= 0) { element.remove(); return; }
                timer = window.setTimeout(expire, Math.min(remaining, 2147483647));
            }
            document.addEventListener('visibilitychange', expire);
            window.addEventListener('pageshow', expire);
            expire();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount); else mount();
}());
