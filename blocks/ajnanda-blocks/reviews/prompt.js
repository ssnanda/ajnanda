(function () {
    'use strict';
    // Phone-only, and only when the phone position is "top": keep the Rate Us bar
    // out of the way at the top of the page and slide it in as a fixed strip once
    // the visitor scrolls past it. Every other position (bottom / left / right,
    // and all of desktop/tablet) is static and handled entirely in CSS.
    var bar = document.querySelector('.aj-review-prompt-bar');
    if (!bar || bar.getAttribute('data-m-position') !== 'top') { return; }

    var body = document.body;
    var phone = window.matchMedia('(max-width: 640px)');
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

    function apply(mql) {
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

    apply(phone);
    if (phone.addEventListener) { phone.addEventListener('change', apply); }
    else if (phone.addListener) { phone.addListener(apply); }
}());
