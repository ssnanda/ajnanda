'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Small DOM harness exercises the real controller, without a browser or third-party dependencies.
class Element {
    constructor() { this.dataset = {}; this.children = []; this.attributes = {}; this.events = {}; this.isConnected = true; this.disabled = false; this.hidden = false; this.textContent = ''; this.classList = { add() {}, remove() {} }; }
    setAttribute(key, value) { this.attributes[key] = value; }
    addEventListener(name, callback) { (this.events[name] ||= []).push(callback); }
    emit(name, event = {}) { (this.events[name] || []).forEach(callback => callback({ target: this, ...event })); }
    appendChild(child) { this.children.push(child); }
    contains(node) { return node === this || this.children.includes(node); }
}
function setup({ autoplay = false, reduced = false, loop = true } = {}) {
    const root = new Element(), track = new Element(), controls = new Element(), previous = new Element(), next = new Element(), dots = new Element(), position = new Element(), pause = new Element();
    const media = new Element(); media.matches = reduced;
    root.dataset = { autoplay: String(autoplay), interval: '6000', loop: String(loop) };
    root.children = [track, previous, next, pause];
    let scroll = 0, lastBehavior;
    track.getBoundingClientRect = () => ({ left: 0, right: 100 });
    track.scrollBy = ({ left, behavior }) => { scroll += left; lastBehavior = behavior; };
    track.children = [0, 1, 2].map(i => { const item = new Element(); item.getBoundingClientRect = () => ({ left: i * 100 - scroll, right: (i + 1) * 100 - scroll }); return item; });
    position.dataset.slideLabel = 'Slide %1$d of %2$d';
    pause.dataset = { playLabel: 'Start automatic rotation', pauseLabel: 'Pause automatic rotation' };
    const elements = { '.aj-carousel__track': track, '.aj-carousel__controls': controls, '[data-prev]': previous, '[data-next]': next, '[data-pause]': autoplay ? pause : null, '[data-dots]': dots, '[data-position]': position };
    root.querySelector = selector => elements[selector];
    const document = new Element(); document.readyState = 'complete'; document.hidden = false; document.querySelectorAll = () => [root]; document.createElement = () => new Element();
    const timers = new Map(); let nextTimer = 1;
    const window = { matchMedia: () => media, setTimeout: (fn, delay) => { const id = nextTimer++; timers.set(id, { fn, delay }); return id; }, clearTimeout: id => timers.delete(id) };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../blocks/ajnanda-blocks/carousel.js'), 'utf8'), { window, document, getComputedStyle: () => ({ direction: 'ltr' }) });
    function tick() { const entry = [...timers.entries()][0]; if (entry) { timers.delete(entry[0]); entry[1].fn(); } }
    return { root, track, controls, previous, next, dots, pause, media, document, timers, tick, scroll: () => scroll, behavior: () => lastBehavior };
}
test('default carousel never autoplays and supplies working navigation and pagination', () => {
    const h = setup(); assert.equal(h.timers.size, 0); assert.equal(h.controls.hidden, false); assert.equal(h.dots.children.length, 3);
    h.next.emit('click'); assert.equal(h.scroll(), 100); assert.equal(h.dots.children[1].attributes['aria-current'], 'true');
    h.previous.emit('click'); assert.equal(h.scroll(), 0);
});
test('keyboard navigation does not intercept keys in interactive slide content', () => {
    const h = setup(); let prevented = false;
    h.track.emit('keydown', { key: 'End', preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true); assert.equal(h.scroll(), 200);
    h.track.emit('keydown', { key: 'ArrowLeft', target: h.track.children[0], preventDefault: () => assert.fail('Trapped content key') });
    assert.equal(h.scroll(), 200); assert.equal(h.track.children[0].attributes['aria-hidden'], undefined);
});
test('autoplay pauses on hover, focus, hidden tabs and explicit pause', () => {
    const h = setup({ autoplay: true }); assert.equal(h.timers.size, 1);
    h.root.emit('mouseenter'); assert.equal(h.timers.size, 0);
    h.root.emit('mouseleave'); assert.equal(h.timers.size, 1);
    h.root.emit('focusin'); assert.equal(h.timers.size, 0);
    h.document.activeElement = null; h.root.emit('focusout'); h.tick(); assert.equal(h.timers.size, 1);
    h.pause.emit('click'); assert.equal(h.timers.size, 0);
    h.pause.emit('click'); assert.equal(h.timers.size, 1);
    h.document.hidden = true; h.document.emit('visibilitychange'); assert.equal(h.timers.size, 0);
});
test('reduced motion prevents autoplay and smooth scrolling, including preference changes', () => {
    const h = setup({ autoplay: true, reduced: true }); assert.equal(h.timers.size, 0); assert.equal(h.pause.disabled, true);
    h.next.emit('click'); assert.equal(h.behavior(), 'auto');
    const live = setup({ autoplay: true }); live.media.matches = true; live.media.emit('change'); assert.equal(live.timers.size, 0);
});
test('touch interaction stops autoplay and finite carousels disable boundary buttons', () => {
    const h = setup({ autoplay: true, loop: false }); assert.equal(h.previous.disabled, true);
    h.track.emit('pointerdown'); assert.equal(h.timers.size, 0);
    h.dots.children[2].emit('click'); assert.equal(h.next.disabled, true);
});
test('autoplay stops scheduling after a collection is removed at expiry', () => {
    const h = setup({ autoplay: true }); h.root.isConnected = false; h.tick(); assert.equal(h.timers.size, 0);
});
