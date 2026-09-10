(function () {
    'use strict';
    var bar = document.querySelector('.aj-review-prompt-bar');
    if (!bar) { return; }

    var body = document.body;
    var phone = window.matchMedia('(max-width: 640px)');
    // Positions that render as a compact card, and so can be collapsed.
    // Mirrors ajnanda_review_prompt_card_positions() in prompt.php.
    var CARDS = ['top-card', 'bottom-card', 'left', 'right'];
    var STORAGE_KEY = 'ajnandaReviewPrompt';

    function activePosition() {
        return bar.getAttribute(phone.matches ? 'data-m-position' : 'data-d-position') || 'top';
    }

    /* ---- Collapse -------------------------------------------------------
       Compact cards get a handle that folds the card away, remembered in the
       visitor's own browser. Desktop and phones can be set to different
       positions, so whether a handle applies at all is decided per breakpoint;
       CSS keys off the marks left here. */
    var toggle = bar.querySelector('.aj-review-prompt-bar__collapse');
    var collapsed = readCollapsed();

    function readCollapsed() {
        try { return window.localStorage.getItem(STORAGE_KEY) === 'collapsed'; }
        catch (e) { return false; }
    }

    function writeCollapsed(value) {
        try {
            if (value) { window.localStorage.setItem(STORAGE_KEY, 'collapsed'); }
            else { window.localStorage.removeItem(STORAGE_KEY); }
        } catch (e) { /* private mode / storage disabled — session-only is fine */ }
    }

    function syncCollapse() {
        var position = activePosition();
        var isCard = CARDS.indexOf(position) !== -1;

        if (isCard) { bar.setAttribute('data-active-card', position); }
        else { bar.removeAttribute('data-active-card'); }
        bar.classList.toggle('aj-review-prompt-bar--collapsible', isCard && !!toggle);

        if (!toggle) { return; }
        var isCollapsed = isCard && collapsed;
        bar.classList.toggle('is-collapsed', isCollapsed);
        toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        toggle.setAttribute('aria-label', isCollapsed
            ? (toggle.getAttribute('data-label-expand') || 'Show the review invitation')
            : (toggle.getAttribute('data-label-collapse') || 'Hide the review invitation'));
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            collapsed = !collapsed;
            writeCollapsed(collapsed);
            syncCollapse();
        });
    }

    /* ---- Phone `top` reveal ---------------------------------------------
       Phone-only, and only when the phone position is "top": keep the Rate Us
       bar out of the way at the top of the page and slide it in as a fixed
       strip once the visitor scrolls past it. Every other position is static
       and handled entirely in CSS. */
    var revealable = bar.getAttribute('data-m-position') === 'top';
    var REVEAL_AT = 64;
    var pending = false;

    function sync() {
        pending = false;
        var y = window.pageYOffset || document.documentElement.scrollTop || 0;
        var show = y > REVEAL_AT;
        bar.classList.toggle('is-visible', show);
        body.classList.toggle('aj-review-prompt-visible', show);
    }

    function onScroll() {
        if (pending) { return; }
        pending = true;
        window.requestAnimationFrame(sync);
    }

    function applyReveal(mql) {
        if (mql.matches) {
            bar.classList.add('aj-review-prompt-bar--reveal');
            body.style.setProperty('--aj-review-prompt-h', bar.offsetHeight + 'px');
            window.addEventListener('scroll', onScroll, { passive: true });
            sync();
        } else {
            window.removeEventListener('scroll', onScroll);
            bar.classList.remove('aj-review-prompt-bar--reveal', 'is-visible');
            body.classList.remove('aj-review-prompt-visible');
            body.style.removeProperty('--aj-review-prompt-h');
        }
    }

    function apply(mql) {
        syncCollapse();
        if (revealable) { applyReveal(mql); }
    }

    apply(phone);
    if (phone.addEventListener) { phone.addEventListener('change', apply); }
    else if (phone.addListener) { phone.addListener(apply); }
}());
