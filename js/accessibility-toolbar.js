/**
 * AJNanda accessibility toolbar.
 *
 * Builds a floating button + panel offering per-visitor display adjustments.
 * State is kept in localStorage on the visitor's own device — nothing is sent
 * anywhere. Each adjustment is a class on <html> (or a CSS variable for text
 * size); accessibility-toolbar.css does the visual work.
 */
(function () {
    'use strict';

    var CONFIG = window.ajnandaA11y || {};
    var T = CONFIG.i18n || {};
    var STORAGE_KEY = 'ajnandaA11y';
    var ROOT = document.documentElement;

    // Text-size steps, as a multiplier applied to the root font size.
    var FONT_STEPS = [1, 1.1, 1.2, 1.35, 1.5];

    var DEFAULT_STATE = {
        fontStep: 0,
        grayscale: false,
        invert: false,
        underlineLinks: false,
        highlightLinks: false,
        readableFont: false
    };

    function readState() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) { return Object.assign({}, DEFAULT_STATE); }
            var parsed = JSON.parse(raw);
            return Object.assign({}, DEFAULT_STATE, parsed && typeof parsed === 'object' ? parsed : {});
        } catch (e) {
            return Object.assign({}, DEFAULT_STATE);
        }
    }

    function writeState(state) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) { /* private mode / storage disabled — session-only is fine */ }
    }

    var state = readState();

    // Greyscale / invert are done with a fixed full-viewport overlay using
    // backdrop-filter rather than `filter` on <html> — a filter on <html> makes
    // it the containing block for position:fixed and would break this toolbar
    // (and any other fixed UI). The overlay is pointer-events:none and only
    // exists while one of the two is active.
    var fxEl = null;
    function applyFx() {
        var parts = [];
        if (state.grayscale) { parts.push('grayscale(1)'); }
        if (state.invert) { parts.push('invert(1)'); }

        if (!parts.length) {
            if (fxEl && fxEl.parentNode) { fxEl.parentNode.removeChild(fxEl); }
            fxEl = null;
            return;
        }
        if (!fxEl) {
            fxEl = document.createElement('div');
            fxEl.id = 'ajn-a11y-fx';
            fxEl.setAttribute('aria-hidden', 'true');
            document.body.appendChild(fxEl);
        }
        var value = parts.join(' ');
        fxEl.style.webkitBackdropFilter = value;
        fxEl.style.backdropFilter = value;
    }

    function apply() {
        var step = Math.max(0, Math.min(FONT_STEPS.length - 1, state.fontStep | 0));
        ROOT.style.setProperty('--ajn-a11y-font-scale', String(FONT_STEPS[step]));
        ROOT.classList.toggle('ajn-a11y-font-scaled', step > 0);
        ROOT.classList.toggle('ajn-a11y-underline-links', !!state.underlineLinks);
        ROOT.classList.toggle('ajn-a11y-highlight-links', !!state.highlightLinks);
        ROOT.classList.toggle('ajn-a11y-readable-font', !!state.readableFont);
        applyFx();
        writeState(state);
        syncButtons();
    }

    // --- Build the widget ---------------------------------------------------

    var buttons = {};
    var panel;
    var toggleBtn;
    var isOpen = false;

    function svg(paths) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + paths + '</svg>';
    }

    var ICONS = {
        person: svg('<circle cx="12" cy="4.5" r="2"/><path d="M12 7v8"/><path d="M5 8.5c2 1 4.5 1.5 7 1.5s5-.5 7-1.5"/><path d="M9 21l3-6 3 6"/>'),
        increase: svg('<path d="M4 19V7"/><path d="M4 7l4 6"/><path d="M8 13l4-6v12"/><path d="M17 8h5"/><path d="M19.5 5.5v5"/>'),
        decrease: svg('<path d="M6 19V7"/><path d="M6 7l4 6"/><path d="M10 13l4-6v12"/><path d="M17 8h5"/>'),
        grayscale: svg('<circle cx="12" cy="12" r="9"/><path d="M12 3v18"/><path d="M12 3a9 9 0 0 1 0 18" fill="currentColor" stroke="none"/>'),
        invert: svg('<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 0 0 18z" fill="currentColor" stroke="none"/>'),
        underline: svg('<path d="M6 4v7a6 6 0 0 0 12 0V4"/><path d="M5 20h14"/>'),
        highlight: svg('<path d="M9 11l-4 4 4 4"/><path d="M5 15h10a4 4 0 0 0 4-4V5"/><rect x="3" y="4" width="7" height="5" rx="1" fill="currentColor" stroke="none"/>'),
        readable: svg('<path d="M4 7V5h16v2"/><path d="M9 19h6"/><path d="M12 5v14"/>'),
        reset: svg('<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>')
    };

    function makeToggle(key, label, icon) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'ajn-a11y__item';
        b.setAttribute('aria-pressed', 'false');
        b.innerHTML = '<span class="ajn-a11y__ico">' + icon + '</span><span class="ajn-a11y__label">' + label + '</span>';
        buttons[key] = b;
        return b;
    }

    function build() {
        var wrap = document.getElementById('ajn-a11y');
        if (!wrap) { return; }
        wrap.hidden = false;
        if (CONFIG.position === 'left') { wrap.classList.add('ajn-a11y--left'); }

        toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'ajn-a11y__toggle';
        toggleBtn.setAttribute('aria-expanded', 'false');
        toggleBtn.setAttribute('aria-controls', 'ajn-a11y-panel');
        toggleBtn.setAttribute('aria-label', T.open || 'Accessibility tools');
        toggleBtn.innerHTML = ICONS.person;

        panel = document.createElement('div');
        panel.className = 'ajn-a11y__panel';
        panel.id = 'ajn-a11y-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', T.heading || 'Accessibility Tools');
        panel.hidden = true;

        var h = document.createElement('p');
        h.className = 'ajn-a11y__heading';
        h.textContent = T.heading || 'Accessibility Tools';
        panel.appendChild(h);

        var list = document.createElement('div');
        list.className = 'ajn-a11y__list';

        // Text size — two buttons that step a shared scale.
        var inc = document.createElement('button');
        inc.type = 'button';
        inc.className = 'ajn-a11y__item';
        inc.innerHTML = '<span class="ajn-a11y__ico">' + ICONS.increase + '</span><span class="ajn-a11y__label">' + (T.increase || 'Increase Text') + '</span>';
        inc.addEventListener('click', function () {
            state.fontStep = Math.min(FONT_STEPS.length - 1, (state.fontStep | 0) + 1);
            apply();
        });

        var dec = document.createElement('button');
        dec.type = 'button';
        dec.className = 'ajn-a11y__item';
        dec.innerHTML = '<span class="ajn-a11y__ico">' + ICONS.decrease + '</span><span class="ajn-a11y__label">' + (T.decrease || 'Decrease Text') + '</span>';
        dec.addEventListener('click', function () {
            state.fontStep = Math.max(0, (state.fontStep | 0) - 1);
            apply();
        });

        list.appendChild(inc);
        list.appendChild(dec);

        var toggles = [
            ['grayscale', T.grayscale || 'Grayscale', ICONS.grayscale],
            ['invert', T.invert || 'Invert Colors', ICONS.invert],
            ['underlineLinks', T.underline || 'Underline Links', ICONS.underline],
            ['highlightLinks', T.highlight || 'Highlight Links', ICONS.highlight],
            ['readableFont', T.readable || 'Readable Font', ICONS.readable]
        ];
        toggles.forEach(function (t) {
            var b = makeToggle(t[0], t[1], t[2]);
            b.addEventListener('click', function () {
                state[t[0]] = !state[t[0]];
                apply();
            });
            list.appendChild(b);
        });

        var reset = document.createElement('button');
        reset.type = 'button';
        reset.className = 'ajn-a11y__item ajn-a11y__reset';
        reset.innerHTML = '<span class="ajn-a11y__ico">' + ICONS.reset + '</span><span class="ajn-a11y__label">' + (T.reset || 'Reset') + '</span>';
        reset.addEventListener('click', function () {
            state = Object.assign({}, DEFAULT_STATE);
            apply();
        });
        list.appendChild(reset);

        panel.appendChild(list);
        wrap.appendChild(toggleBtn);
        wrap.appendChild(panel);

        toggleBtn.addEventListener('click', function () { setOpen(!isOpen); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) { setOpen(false); toggleBtn.focus(); }
        });
        document.addEventListener('click', function (e) {
            if (isOpen && !wrap.contains(e.target)) { setOpen(false); }
        });
    }

    function setOpen(open) {
        isOpen = open;
        panel.hidden = !open;
        toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggleBtn.setAttribute('aria-label', open ? (T.close || 'Close accessibility tools') : (T.open || 'Accessibility tools'));
        document.getElementById('ajn-a11y').classList.toggle('is-open', open);
        if (open) {
            var first = panel.querySelector('button');
            if (first) { first.focus(); }
        }
    }

    function syncButtons() {
        Object.keys(buttons).forEach(function (key) {
            buttons[key].setAttribute('aria-pressed', state[key] ? 'true' : 'false');
            buttons[key].classList.toggle('is-active', !!state[key]);
        });
    }

    function start() {
        build();
        apply();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());
