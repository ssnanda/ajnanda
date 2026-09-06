(function () {
    'use strict';
    function init(root) {
        if (root.dataset.ajReady) return;
        root.dataset.ajReady = 'true';
        var track = root.querySelector('.aj-carousel__track');
        var slides = Array.prototype.slice.call(track.children);
        var controls = root.querySelector('.aj-carousel__controls');
        if (slides.length < 2) return;
        controls.hidden = false;
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        var index = 0, timer = null, hover = false, focused = false;
        var stopped = root.dataset.autoplay !== 'true' || reduced.matches;
        var interval = Math.max(3000, Math.min(30000, Number(root.dataset.interval) || 6000));
        var previous = root.querySelector('[data-prev]'), next = root.querySelector('[data-next]');
        var pause = root.querySelector('[data-pause]'), dots = root.querySelector('[data-dots]');
        var position = root.querySelector('[data-position]');
        var loop = root.dataset.loop !== 'false';
        var duration = Math.max(0, Math.min(2000, Number(root.dataset.speed || 400)));
        var frame = null, fade = null;
        function cancelMotion() {
            if (frame !== null) window.cancelAnimationFrame(frame);
            if (fade) fade.cancel();
            frame = null; fade = null;
        }
        function slideLabel(i) { return position.dataset.slideLabel.replace('%1$d', i + 1).replace('%2$d', slides.length); }
        slides.forEach(function (slide, i) {
            // All slides remain in the accessibility tree and tab order, including offscreen links.
            slide.setAttribute('role', 'group');
            slide.setAttribute('aria-label', slideLabel(i));
            if (dots) {
                var dot = document.createElement('button');
                dot.type = 'button'; dot.textContent = String(i + 1);
                dot.setAttribute('aria-label', slideLabel(i));
                dot.addEventListener('click', function () { stopped = true; go(i); schedule(); });
                dots.appendChild(dot);
            }
        });
        function update(announce) {
            if (announce) position.textContent = slideLabel(index);
            previous.disabled = !loop && index === 0;
            next.disabled = !loop && index === slides.length - 1;
            if (dots) Array.prototype.forEach.call(dots.children, function (dot, i) { dot.setAttribute('aria-current', i === index ? 'true' : 'false'); });
        }
        function go(target, automatic) {
            index = loop ? (target + slides.length) % slides.length : Math.max(0, Math.min(slides.length - 1, target));
            var bounds = track.getBoundingClientRect(), item = slides[index].getBoundingClientRect();
            // Relative geometry also supports RTL scrolling and nested scroll containers.
            var delta = getComputedStyle(track).direction === 'rtl' ? item.right - bounds.right : item.left - bounds.left;
            cancelMotion();
            if (reduced.matches || duration === 0) {
                track.scrollBy({ left: delta, behavior: 'auto' });
            } else if (root.dataset.effect === 'fade') {
                track.scrollBy({ left: delta, behavior: 'auto' });
                if (slides[index].animate) fade = slides[index].animate([{ opacity: 0 }, { opacity: 1 }], { duration: duration });
            } else if (window.requestAnimationFrame) {
                var started = null, moved = 0;
                track.classList.add('is-moving');
                function move(now) {
                    if (!root.isConnected) { track.classList.remove('is-moving'); frame = null; return; }
                    if (started === null) started = now;
                    var progress = Math.min(1, (now - started) / duration);
                    var target = delta * (1 - Math.pow(1 - progress, 3));
                    track.scrollBy({ left: target - moved, behavior: 'auto' }); moved = target;
                    if (progress < 1) frame = window.requestAnimationFrame(move);
                    else { frame = null; track.classList.remove('is-moving'); }
                }
                frame = window.requestAnimationFrame(move);
            } else {
                track.scrollBy({ left: delta, behavior: 'smooth' });
            }
            update(!automatic);
        }
        function schedule() {
            window.clearTimeout(timer);
            if (pause) pause.textContent = stopped || reduced.matches ? pause.dataset.playLabel : pause.dataset.pauseLabel;
            if (pause) pause.disabled = reduced.matches;
            if (stopped || reduced.matches || hover || focused || document.hidden || !root.isConnected) return;
            timer = window.setTimeout(function () { go(index + 1, true); schedule(); }, interval);
        }
        previous.addEventListener('click', function () { stopped = true; go(index - 1); schedule(); });
        next.addEventListener('click', function () { stopped = true; go(index + 1); schedule(); });
        if (pause) pause.addEventListener('click', function () { stopped = !stopped; schedule(); });
        track.addEventListener('keydown', function (event) {
            if (event.target !== track || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault(); stopped = true;
            var rtl = getComputedStyle(track).direction === 'rtl';
            go(event.key === 'Home' ? 0 : event.key === 'End' ? slides.length - 1 : index + ((event.key === 'ArrowRight') !== rtl ? 1 : -1)); schedule();
        });
        // Native overflow supplies touch/swipe; update pagination after touch or keyboard focus scrolls.
        var scrollTimer;
        track.addEventListener('scroll', function () {
            window.clearTimeout(scrollTimer);
            scrollTimer = window.setTimeout(function () {
                var bounds = track.getBoundingClientRect(), rtl = getComputedStyle(track).direction === 'rtl';
                var distance = Infinity;
                slides.forEach(function (slide, i) { var box = slide.getBoundingClientRect(); var d = Math.abs(rtl ? box.right - bounds.right : box.left - bounds.left); if (d < distance) { distance = d; index = i; } });
                update(false);
            }, 100);
        }, { passive: true });
        track.addEventListener('pointerdown', function () { stopped = true; cancelMotion(); track.classList.remove('is-moving'); schedule(); }, { passive: true });
        root.addEventListener('mouseenter', function () { hover = true; schedule(); });
        root.addEventListener('mouseleave', function () { hover = false; schedule(); });
        root.addEventListener('focusin', function () { focused = true; schedule(); });
        root.addEventListener('focusout', function () { window.setTimeout(function () { focused = root.contains(document.activeElement); schedule(); }, 0); });
        document.addEventListener('visibilitychange', schedule);
        function motionChanged() { if (reduced.matches) { stopped = true; cancelMotion(); track.classList.remove('is-moving'); } schedule(); }
        if (reduced.addEventListener) reduced.addEventListener('change', motionChanged);
        else reduced.addListener(motionChanged);
        update(false); schedule();
    }
    function mount() { document.querySelectorAll('[data-aj-carousel]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount); else mount();
    window.AJNandaCarousel = { init: init, mount: mount };
}());
