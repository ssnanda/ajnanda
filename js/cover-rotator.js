/**
 * AJNanda Cover background rotation (front end).
 *
 * Enqueued by inc/cover-rotator.php only when a Cover block on the page has
 * "Rotate background images" turned on. Settings come from the Cover's
 * data-ajn-cover-rotate attribute. The Cover's own background is slide 1;
 * every other image is preloaded before rotation starts.
 *
 * Never rotates for visitors who prefer reduced motion, skips ticks while the
 * tab is hidden, and pauses briefly when a light/dark theme switch dispatches
 * `ajnanda:theme-change` on document.
 */
(function () {
    'use strict';

    var THEME_EVENT = 'ajnanda:theme-change';
    var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    function prefersReducedMotion() {
        return !!(reduceMotion && reduceMotion.matches);
    }

    function backgroundOf(cover) {
        for (var i = 0; i < cover.children.length; i++) {
            if (cover.children[i].classList.contains('wp-block-cover__image-background')) {
                return cover.children[i];
            }
        }
        return null;
    }

    function preload(slides, sizes, done) {
        var pending = slides.length;
        if (!pending) {
            done();
            return;
        }
        slides.forEach(function (slide) {
            var img = new Image();
            img.onload = img.onerror = function () {
                pending--;
                if (0 === pending) {
                    done();
                }
            };
            if (slide.srcset) {
                if (sizes) {
                    img.sizes = sizes;
                }
                img.srcset = slide.srcset;
            }
            img.src = slide.src;
        });
    }

    function init(cover) {
        var config;
        try {
            config = JSON.parse(cover.getAttribute('data-ajn-cover-rotate'));
        } catch (e) {
            return;
        }

        var target = backgroundOf(cover);
        if (!config || !target || !Array.isArray(config.images) || !config.images.length) {
            return;
        }

        var isImg = 'IMG' === target.tagName;
        var first = isImg
            ? { src: target.getAttribute('src'), srcset: target.getAttribute('srcset') || '' }
            : { background: target.style.backgroundImage };
        var slides = [first].concat(config.images);
        var fade = Math.max(0, parseInt(config.fade, 10) || 0);
        var interval = Math.max(2000, parseInt(config.interval, 10) || 5000);
        var sizes = isImg ? target.getAttribute('sizes') : '';

        preload(config.images, sizes, function () {
            var index = 0;
            var pausedUntil = 0;

            if (fade) {
                target.style.transition = 'opacity ' + fade + 'ms ease';
            }

            document.addEventListener(THEME_EVENT, function () {
                pausedUntil = Date.now() + Math.max(600, fade);
            });

            function apply(slide) {
                if (!isImg) {
                    target.style.backgroundImage = slide.background || 'url(' + JSON.stringify(slide.src) + ')';
                    return;
                }
                if (slide.srcset) {
                    target.setAttribute('srcset', slide.srcset);
                } else {
                    target.removeAttribute('srcset');
                }
                target.setAttribute('src', slide.src);
            }

            window.setInterval(function () {
                if (prefersReducedMotion() || document.hidden || Date.now() < pausedUntil) {
                    return;
                }
                index = (index + 1) % slides.length;
                var slide = slides[index];

                if (!fade) {
                    apply(slide);
                    return;
                }

                target.style.opacity = '0';
                window.setTimeout(function () {
                    window.requestAnimationFrame(function () {
                        apply(slide);
                        target.style.opacity = '';
                    });
                }, fade);
            }, interval);
        });
    }

    function start() {
        if (prefersReducedMotion()) {
            return;
        }
        var covers = document.querySelectorAll('.wp-block-cover[data-ajn-cover-rotate]');
        for (var i = 0; i < covers.length; i++) {
            init(covers[i]);
        }
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
