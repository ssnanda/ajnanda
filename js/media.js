/**
 * Media helpers: sticky-stage step swap + keyboard access for media reels.
 * Vanilla JS; no-JS fallbacks live in style.css.
 */
(function () {
    'use strict';

    document.querySelectorAll('.is-style-ajnanda-media-reel').forEach(function (reel) {
        if (!reel.hasAttribute('tabindex')) {
            reel.setAttribute('tabindex', '0');
        }
    });

    var stages = document.querySelectorAll('.is-style-ajnanda-sticky-stage.has-stage-swap');
    if (!stages.length || !('IntersectionObserver' in window)) {
        return;
    }

    var desktop = window.matchMedia('(min-width: 782px)');

    stages.forEach(function (stage) {
        var media = stage.querySelectorAll('.ajn-stage-sticky > figure');
        var steps = stage.querySelectorAll('.ajn-stage-step');
        if (media.length < 2 || !steps.length) {
            return;
        }

        function show(index) {
            media.forEach(function (figure, i) {
                figure.classList.toggle('is-active', i === Math.min(index, media.length - 1));
            });
        }

        show(0);
        stage.classList.add('is-stage-ready');

        // Active step = the one crossing the viewport's middle band.
        var observer = new IntersectionObserver(function (entries) {
            if (!desktop.matches) {
                return;
            }
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    show(Array.prototype.indexOf.call(steps, entry.target));
                }
            });
        }, { rootMargin: '-45% 0px -45% 0px' });

        steps.forEach(function (step) { observer.observe(step); });
    });
})();
