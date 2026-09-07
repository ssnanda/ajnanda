(function () {
    'use strict';
    function init(root) {
        if (root.dataset.ajReady) return;
        root.dataset.ajReady = 'true';
        var track = root.querySelector('.aj-carousel__track');
        var slides = Array.prototype.slice.call(track.children);
        var controls = root.querySelector('.aj-carousel__controls');
        var overlay = root.querySelector('.aj-carousel__overlay');
        if (slides.length < 2) return;
        controls.hidden = false;
        if (overlay) overlay.hidden = false;
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        var index = 0, timer = null, countdownTimer = null, hover = false, focused = false, pressing = false;
        // wantsAutoplay: the carousel is configured to rotate.
        // userStopped: the visitor deliberately stopped it (pause button, an arrow/
        //   dot/keyboard move, or a real drag) — sticky for the session.
        // hover / focused / pressing / document.hidden are *transient* pauses that
        //   resume on their own. The old code collapsed all of this into one
        //   `stopped` flag, so a single stray pointerdown (a tap on a CTA, a
        //   trackpad brush) or an init-time transient latched autoplay off with no
        //   way back except the pause button — that's items 16 + 17.
        var wantsAutoplay = root.dataset.autoplay === 'true';
        var userStopped = false;
        var pointerStartX = 0, pointerStartY = 0, dragLatched = false;
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
                dot.addEventListener('click', function () { userStopped = true; go(i); schedule(); });
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
        // Autoplay may run right now: configured, not deliberately stopped, motion
        // allowed, and no transient pause in effect.
        function autoplayAllowed() {
            return wantsAutoplay && !userStopped && !reduced.matches
                && !hover && !focused && !pressing && !document.hidden && root.isConnected;
        }
        function renderCountdown(endAt) {
            var el = root.querySelector('[data-countdown]');
            window.clearInterval(countdownTimer);
            countdownTimer = null;
            if (!el) return;
            if (endAt === null || reduced.matches) { el.hidden = true; return; }
            var template = el.dataset.countdown || '%d';
            function paint() {
                var secs = Math.max(0, Math.ceil((endAt - Date.now()) / 1000));
                el.textContent = template.replace('%d', secs);
            }
            el.hidden = false;
            paint();
            countdownTimer = window.setInterval(paint, 1000);
        }
        function schedule() {
            window.clearTimeout(timer);
            timer = null;
            if (pause) {
                // The button reflects the *sticky* state only — a hover pause still
                // shows "Pause" because it will resume on its own.
                var showPlay = userStopped || reduced.matches;
                var label = showPlay ? pause.dataset.playLabel : pause.dataset.pauseLabel;
                var pauseText = pause.querySelector('[data-pause-text]');
                if (pauseText) { pauseText.textContent = label; } else { pause.textContent = label; }
                pause.setAttribute('aria-label', label);
                pause.setAttribute('aria-pressed', showPlay ? 'true' : 'false');
                pause.toggleAttribute('data-paused', showPlay);
                pause.disabled = reduced.matches;
            }
            if (!autoplayAllowed()) { renderCountdown(null); return; }
            var endAt = Date.now() + interval;
            timer = window.setTimeout(function () { go(index + 1, true); schedule(); }, interval);
            renderCountdown(endAt);
        }
        previous.addEventListener('click', function () { userStopped = true; go(index - 1); schedule(); });
        next.addEventListener('click', function () { userStopped = true; go(index + 1); schedule(); });
        if (pause) pause.addEventListener('click', function () { userStopped = !userStopped; schedule(); });
        track.addEventListener('keydown', function (event) {
            if (event.target !== track || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault(); userStopped = true;
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
        // A press pauses rotation only while the pointer is down. It becomes a
        // sticky stop only once the pointer travels far enough to count as a
        // deliberate horizontal drag — so tapping a CTA or the photo no longer
        // kills autoplay for the visit (item 17).
        track.addEventListener('pointerdown', function (event) {
            pressing = true; dragLatched = false;
            pointerStartX = event.clientX; pointerStartY = event.clientY;
            cancelMotion(); track.classList.remove('is-moving'); schedule();
        }, { passive: true });
        track.addEventListener('pointermove', function (event) {
            if (!pressing || dragLatched) return;
            var dx = event.clientX - pointerStartX, dy = event.clientY - pointerStartY;
            if (Math.abs(dx) > 12 && Math.abs(dx) > Math.abs(dy)) {
                dragLatched = true; userStopped = true; schedule();
            }
        }, { passive: true });
        function endPress() { if (!pressing) return; pressing = false; schedule(); }
        track.addEventListener('pointerup', endPress, { passive: true });
        track.addEventListener('pointercancel', endPress, { passive: true });
        track.addEventListener('pointerleave', endPress, { passive: true });
        root.addEventListener('mouseenter', function () { hover = true; schedule(); });
        root.addEventListener('mouseleave', function () { hover = false; schedule(); });
        root.addEventListener('focusin', function () { focused = true; schedule(); });
        root.addEventListener('focusout', function () { window.setTimeout(function () { focused = root.contains(document.activeElement); schedule(); }, 0); });
        document.addEventListener('visibilitychange', schedule);
        function motionChanged() { if (reduced.matches) { cancelMotion(); track.classList.remove('is-moving'); } schedule(); }
        if (reduced.addEventListener) reduced.addEventListener('change', motionChanged);
        else reduced.addListener(motionChanged);
        update(false); schedule();
        // Belt-and-braces for item 16: the synchronous schedule() above can land
        // while the page is still a prerender (document.hidden), mid-layout, or
        // with a transient flag left set by the load sequence / a bfcache restore.
        // Re-sync the transient flags from the live DOM and re-arm once the page
        // is genuinely ready.
        function resync() {
            focused = root.contains(document.activeElement);
            try { hover = root.matches(':hover'); } catch (e) { hover = false; }
            pressing = false;
            schedule();
        }
        window.addEventListener('load', resync);
        window.addEventListener('pageshow', resync);
        if (document.readyState === 'complete') window.setTimeout(resync, 0);
    }
    function mount() { document.querySelectorAll('[data-aj-carousel]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount); else mount();
    window.AJNandaCarousel = { init: init, mount: mount };
}());
