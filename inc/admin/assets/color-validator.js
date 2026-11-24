/**
 * AJNanda Color Scheme Validator — rendered comparison + report.
 *
 * Flow: POST ajnanda_color_validator_plan (roles, targets, source scan) →
 * for every target/viewport load the baseline render and the comparison
 * render in an off-screen same-origin iframe → compare computed colors
 * element by element → for every brand-colored property that did not
 * follow the comparison palette, find the winning declaration in the live
 * comparison document and trace its var() chain → group by root cause →
 * render the report here and offer it as a standalone HTML download.
 *
 * Read-only: no request made here writes anything (see inc/color-validator.php).
 *
 * @package AJNanda
 */
(function () {
    'use strict';

    var cfg = window.ajnandaColorValidator;
    var root = document.getElementById('ajnanda-cv');
    if (!cfg || !root) { return; }

    var VIEWPORTS = { desktop: 1366, mobile: 390 };
    var MAX_ELEMENTS = 5000;
    var SETTLE_MS = 900;
    var LOAD_TIMEOUT = 45000;
    var TRACE_TOKENS = ['--primary', '--primary-dark', '--secondary', '--accent', '--ast-global-color-0', '--wp--preset--color--primary-blue', '--wp--preset--color--deep-blue', '--wp--preset--color--purple', '--ajn-hero-bg-1', '--ajn-hero-bg-2', '--ajn-header-background', '--ajn-header-link-hover-color', '--ajn-hero-button-text-color'];
    var CORE_TOKENS = { '--primary': 'primary', '--primary-dark': 'primary_dark', '--secondary': 'secondary', '--accent': 'accent' };
    // Tokens printed by ajnanda_customizer_css() from a theme_mod (functions.php).
    var TOKEN_MODS = {
        '--ajn-header-background': 'header_background_color', '--ajn-header-text-color': 'header_text_color',
        '--ajn-header-link-hover-color': 'header_link_hover_color', '--ajn-header-link-hover-background': 'header_link_hover_background',
        '--ajn-header-submenu-background': 'header_submenu_background', '--ajn-header-submenu-text-color': 'header_submenu_text_color',
        '--ajn-header-submenu-hover-color': 'header_submenu_hover_color', '--ajn-header-submenu-hover-background': 'header_submenu_hover_background',
        '--ajn-footer-background': 'footer_background_color', '--ajn-footer-text-color': 'footer_text_color',
        '--ajn-footer-link-hover-color': 'footer_link_hover_color', '--ajn-footer-submenu-hover-color': 'footer_submenu_hover_color',
        '--ajn-hero-bg-1': 'hero_bg_1', '--ajn-hero-bg-2': 'hero_bg_2', '--ajn-hero-button-text-color': 'hero_button_text_color',
        '--ajn-hero-button-bg': 'hero_button_bg', '--ajn-header-accent-bg': 'ajn_header_accent_color_1/2/3',
        '--ast-global-color-0': 'theme_primary_color', '--ast-global-color-1': 'theme_primary_dark_color', '--ast-global-color-2': 'theme_secondary_color'
    };
    var SHORTHANDS = {
        'background-color': ['background-color', 'background'],
        'background-image': ['background-image', 'background'],
        'border-top-color': ['border-top-color', 'border-color', 'border-top', 'border'],
        'border-left-color': ['border-left-color', 'border-color', 'border-left', 'border'],
        'border-bottom-color': ['border-bottom-color', 'border-color', 'border-bottom', 'border'],
        'outline-color': ['outline-color', 'outline'],
        'color': ['color'], 'fill': ['fill'], 'stroke': ['stroke'], 'box-shadow': ['box-shadow']
    };
    var PLUGIN_HINTS = [
        [/freesiem/i, 'Plugin: freesiem-sentinel'],
        [/ajcore|ajforms|aj-chat|aj-portal|ajchat|\bajp-|--ajp-/i, 'Plugin: ajcore'],
        [/ajcore-ra|ajra-/i, 'Plugin: ajcore-ra'],
        [/hostinger|hts-/i, 'Plugin: hostinger'],
        [/googlesitekit/i, 'Plugin: google-site-kit']
    ];

    var form = root.querySelector('.ajnanda-cv-controls');
    var runBtn = root.querySelector('[data-cv-run]');
    var dlBtn = root.querySelector('[data-cv-download]');
    var progressWrap = root.querySelector('.ajnanda-cv-progress');
    var progressBar = progressWrap.querySelector('progress');
    var statusEl = root.querySelector('[data-cv-status]');
    var reportEl = root.querySelector('[data-cv-report]');
    var stage = root.querySelector('[data-cv-stage]');
    var last = null;

    runBtn.addEventListener('click', function () { run().catch(function (e) { setStatus('Failed: ' + (e && e.message ? e.message : e)); runBtn.disabled = false; }); });
    dlBtn.addEventListener('click', download);

    function setStatus(text, pct) {
        progressWrap.hidden = false;
        statusEl.textContent = text;
        if (typeof pct === 'number') { progressBar.value = Math.round(pct); }
    }

    /* ------------------------------------------------------------ *
     * Orchestration
     * ------------------------------------------------------------ */

    async function run() {
        runBtn.disabled = true;
        dlBtn.hidden = true;
        reportEl.innerHTML = '';
        window.ajnandaColorValidatorLastReport = null;
        setStatus('Scanning source…', 1);

        var body = new FormData();
        body.append('action', 'ajnanda_color_validator_plan');
        body.append('nonce', cfg.nonce);
        body.append('baseline', form.baseline.value);
        body.append('comparison', form.comparison.value);
        body.append('articles', form.articles.value || '5');
        var res = await fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body });
        var json = await res.json();
        if (!json || !json.success) { throw new Error(json && json.data && json.data.message ? json.data.message : 'Plan request failed'); }
        var plan = json.data;
        var ctx = makeContext(plan);

        var jobs = [];
        plan.targets.forEach(function (t) {
            jobs.push({ target: t, viewport: 'desktop' });
            if (t.mobile || form.mobile_all.checked) { jobs.push({ target: t, viewport: 'mobile' }); }
        });

        var results = [];
        for (var i = 0; i < jobs.length; i++) {
            var job = jobs[i];
            setStatus('Rendering ' + (i + 1) + '/' + jobs.length + ': ' + job.target.label + ' (' + job.viewport + ')', 3 + (i / jobs.length) * 94);
            results.push(await runJob(job, ctx));
        }

        setStatus('Building report…', 98);
        var report = buildReport(plan, ctx, results);
        reportEl.innerHTML = report.html;
        last = report;
        window.ajnandaColorValidatorLastReport = { html: standalone(report), data: report.data };
        dlBtn.hidden = false;
        runBtn.disabled = false;
        setStatus('Done — ' + results.length + ' renders, ' + report.data.groups.length + ' root causes, ' + report.data.findings.length + ' findings.', 100);
    }

    async function runJob(job, ctx) {
        var out = { target: job.target, viewport: job.viewport, status: 'verified', notes: [], follows: 0, bypassed: 0, findings: [], working: [], unverifiable: [] };
        var base, comp;
        try {
            var b = await loadFrame(job.target.baseline_url, VIEWPORTS[job.viewport]);
            base = snapshot(b.doc, b.win, ctx, false);
            out.baselineTokens = base.tokens;
            b.iframe.remove();
        } catch (e) {
            out.status = 'failed'; out.notes.push('Baseline render failed: ' + e.message); return out;
        }
        var c;
        try {
            c = await loadFrame(job.target.comparison_url, VIEWPORTS[job.viewport]);
            await prefetchSources(c.doc);
            comp = snapshot(c.doc, c.win, ctx, true);
            out.comparisonTokens = comp.tokens;
        } catch (e) {
            out.status = 'failed'; out.notes.push('Comparison render failed: ' + e.message); return out;
        }
        try {
            out.httpNote = base.title + ' / ' + comp.title;
            out.unverifiable = comp.idx.unverifiable.concat(comp.opaque);
            // Two separate questions: did the selected scheme reach the page (resolver
            // input present), and does <body> still resolve the palette to it?
            var expected = ctx.comparison.primary.toLowerCase();
            var bodyPrimary = hexOf(comp.tokens.body['--primary']);
            var rootPrimary = hexOf(comp.tokens.root['--primary']);
            var reached = job.target.resolver === 'apply' ? rootPrimary === expected : (' ' + comp.bodyClass + ' ').indexOf(' ajnanda-scheme-' + ctx.comparison.slug + ' ') > -1;
            out.loaded = { body: bodyPrimary, root: rootPrimary, reached: reached };
            if (!reached) {
                out.status = 'scheme-not-loaded';
                out.notes.push('The selected scheme never reached this render (' + (job.target.resolver === 'apply' ? ':root --primary = ' + rootPrimary : 'body class ajnanda-scheme-' + ctx.comparison.slug + ' missing') + ').');
                c.iframe.remove();
                return out;
            }
            if (bodyPrimary !== expected) {
                var bd = winning(c.doc.body, '--primary', comp.idx, '');
                out.status = 'scheme-overridden';
                out.override = bd ? { selector: bd.selector, value: bd.value, source: bd.sheet } : null;
                out.notes.push('Scheme loaded but <body> re-declares --primary = ' + bodyPrimary + (bd ? ' via ' + bd.selector + ' [' + bd.sheet.label + ']' : '') + '.');
            }
            if (job.target.kind === 'demo') { out.navigation = navigationCheck(c.doc); }
            compare(base, comp, ctx, job, out);
            if (out.unverifiable.length && out.status === 'verified') { out.status = 'partial'; }
        } catch (e) {
            out.status = 'failed'; out.notes.push('Analysis failed: ' + e.message);
        }
        c.iframe.remove();
        return out;
    }

    function loadFrame(url, width) {
        return new Promise(function (resolve, reject) {
            var iframe = document.createElement('iframe');
            iframe.setAttribute('aria-hidden', 'true');
            iframe.tabIndex = -1;
            iframe.style.cssText = 'position:absolute;left:0;top:0;border:0;width:' + width + 'px;height:900px;';
            var timer = setTimeout(function () { iframe.remove(); reject(new Error('timed out loading ' + url)); }, LOAD_TIMEOUT);
            iframe.addEventListener('load', function () {
                setTimeout(function () {
                    clearTimeout(timer);
                    var doc;
                    try { doc = iframe.contentDocument; } catch (e) { doc = null; }
                    if (!doc || !doc.body) { iframe.remove(); reject(new Error('could not read the rendered document')); return; }
                    resolve({ iframe: iframe, doc: doc, win: iframe.contentWindow });
                }, SETTLE_MS);
            }, { once: true });
            iframe.src = url;
            stage.appendChild(iframe);
        });
    }

    /* ------------------------------------------------------------ *
     * Colors
     * ------------------------------------------------------------ */

    function parseColors(str) {
        var out = [];
        if (!str || str === 'none') { return out; }
        var re = /rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:\s*[,/]\s*([\d.]+%?))?\s*\)/g, m;
        while ((m = re.exec(str))) {
            var a = m[4] === undefined ? 1 : (m[4].indexOf('%') > -1 ? parseFloat(m[4]) / 100 : parseFloat(m[4]));
            out.push({ hex: toHex(+m[1], +m[2], +m[3]), a: a, raw: m[0] });
        }
        return out;
    }
    function toHex(r, g, b) { return '#' + [r, g, b].map(function (v) { var s = Math.round(v).toString(16); return s.length < 2 ? '0' + s : s; }).join(''); }
    function hexOf(v) {
        v = (v || '').trim().toLowerCase();
        if (/^#[0-9a-f]{3}$/.test(v)) { return '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3]; }
        if (/^#[0-9a-f]{6}/.test(v)) { return v.slice(0, 7); }
        var p = parseColors(v);
        return p.length ? p[0].hex : '';
    }
    function hsl(hex) {
        var r = parseInt(hex.substr(1, 2), 16) / 255, g = parseInt(hex.substr(3, 2), 16) / 255, b = parseInt(hex.substr(5, 2), 16) / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, h = 0, s = 0;
        if (max !== min) {
            var d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
            h *= 60;
        }
        return [h, s, l];
    }

    function makeContext(plan) {
        var roles = {};
        plan.roles.forEach(function (r) { if (r.differs) { roles[r.baseline] = r; } });
        var bh = hsl(plan.baseline.primary.toLowerCase());
        var saved = {};
        (plan.source.items || []).forEach(function (it) { if (it.type === 'theme_mod') { saved[it.ref.replace('theme_mod ', '')] = it.hex; } });
        return { plan: plan, roles: roles, baseline: plan.baseline, comparison: plan.comparison, baseHue: bh[0], savedMods: saved };
    }
    function roleOf(hex, ctx) { return ctx.roles[hex] || null; }
    function isTint(hex, ctx) {
        var x = hsl(hex), dh = Math.abs(x[0] - ctx.baseHue);
        dh = Math.min(dh, 360 - dh);
        // Mid and light blues only; near-black navies (#0f172a, #111827) are neutral ink.
        return x[1] >= 0.3 && dh <= 22 && x[2] >= 0.2 && x[2] <= 0.97;
    }

    /* ------------------------------------------------------------ *
     * Stylesheet index (winning declaration + token chain)
     * ------------------------------------------------------------ */

    function splitSelectors(text) {
        var out = [], depth = 0, cur = '';
        for (var i = 0; i < text.length; i++) {
            var ch = text[i];
            if (ch === '(' || ch === '[') { depth++; } else if (ch === ')' || ch === ']') { depth--; }
            if (ch === ',' && depth === 0) { out.push(cur.trim()); cur = ''; } else { cur += ch; }
        }
        if (cur.trim()) { out.push(cur.trim()); }
        return out;
    }
    function specificity(s) {
        var noWhere = s.replace(/:where\([^)]*\)/g, '');
        var a = (noWhere.match(/#[\w-]+/g) || []).length;
        var b = (noWhere.match(/\.[\w-]+|\[[^\]]+\]|:(?!:)(?!not\(|is\()[\w-]+(\([^)]*\))?/g) || []).length;
        var c = (noWhere.replace(/\[[^\]]*\]|\([^)]*\)/g, '').match(/(^|[\s>+~])[a-zA-Z][\w-]*|::[\w-]+/g) || []).length;
        return a * 10000 + b * 100 + c;
    }
    var STATE_RE = /:(hover|focus-visible|focus-within|focus|active)\b/g;

    function sheetInfo(sheet, n) {
        var info = sheetInfoBase(sheet, n);
        info.key = sheet.href || ('inline:' + n);
        if (!sheet.href && sheet.ownerNode) { SOURCE[info.key] = sheet.ownerNode.textContent || ''; }
        return info;
    }
    function sheetInfoBase(sheet, n) {
        var node = sheet.ownerNode, href = sheet.href;
        if (href) {
            var path = href.replace(/^https?:\/\/[^/]+/, '').split('?')[0], m;
            if ((m = path.match(/\/themes\/([^/]+)\/(.*)$/))) { return { kind: 'file', owner: m[1] === 'ajnanda' ? 'AJNanda' : 'Theme: ' + m[1], file: m[2], label: (m[1] === 'ajnanda' ? '' : m[1] + '/') + m[2] }; }
            if ((m = path.match(/\/mu-plugins\/(.*)$/))) { return { kind: 'file', owner: 'Site mu-plugin: ' + m[1].split('/')[0].replace(/\.php$/, ''), file: 'wp-content/mu-plugins/' + m[1], label: 'mu-plugins/' + m[1] }; }
            if ((m = path.match(/\/plugins\/([^/]+)\/(.*)$/))) { return { kind: 'file', owner: 'Plugin: ' + m[1], file: m[1] + '/' + m[2], label: 'plugins/' + m[1] + '/' + m[2] }; }
            if (path.indexOf('/wp-includes/') > -1) { return { kind: 'file', owner: 'WordPress core', file: path, label: path }; }
            return { kind: 'file', owner: 'External', file: href, label: href };
        }
        var id = node && node.id ? node.id : '', text = node ? (node.textContent || '') : '';
        if (id === 'global-styles-inline-css') { return { kind: 'inline', owner: 'WordPress (theme.json presets + Global Styles)', file: 'theme.json + inc/template-slots.php ajnanda_sync_brand_palette()', label: '<style#global-styles-inline-css>' }; }
        if (id === 'wp-custom-css') { return { kind: 'inline', owner: 'Saved setting', file: 'Customizer → Additional CSS', label: '<style#wp-custom-css>' }; }
        if (id === 'core-block-supports-inline-css') { return { kind: 'inline', owner: 'Saved content', file: 'Block attributes in post content', label: '<style#core-block-supports-inline-css>' }; }
        if (text.indexOf('--ajn-hero-bg-1') > -1) { return { kind: 'inline', owner: 'AJNanda', file: 'functions.php ajnanda_customizer_css() (theme_mods)', label: '<style> ajnanda_customizer_css()' }; }
        if (/ajnanda-scheme-|ajnanda/i.test(id)) { return { kind: 'inline', owner: 'AJNanda', file: 'wp_add_inline_style ' + id, label: '<style#' + id + '>' }; }
        for (var i = 0; i < PLUGIN_HINTS.length; i++) { if (PLUGIN_HINTS[i][0].test(id + ' ' + text.slice(0, 400))) { return { kind: 'inline', owner: PLUGIN_HINTS[i][1], file: 'inline <style' + (id ? '#' + id : '') + '>', label: '<style' + (id ? '#' + id : ' #' + n) + '>' }; } }
        return { kind: 'inline', owner: /ajn|ajnanda/i.test(text.slice(0, 2000)) ? 'AJNanda (inline)' : 'Unknown inline', file: 'inline <style' + (id ? '#' + id : ' #' + n) + '>', label: '<style' + (id ? '#' + id : ' #' + n) + '> ' + text.trim().slice(0, 60) };
    }

    function buildIndex(doc, win) {
        var idx = { byProp: {}, tokens: {}, state: [], unverifiable: [], order: 0 };
        function add(rule, info) {
            var parts = splitSelectors(rule.selectorText).filter(function (s) { return s.indexOf('::') < 0; }).map(function (s) {
                var hasState = STATE_RE.test(s); STATE_RE.lastIndex = 0;
                return { s: s, spec: specificity(s), state: hasState, base: hasState ? (s.replace(STATE_RE, '').trim() || '*') : s, stateName: hasState ? (s.match(STATE_RE) || [''])[0].slice(1) : '' };
            });
            if (!parts.length) { return; }
            var entry = { parts: parts, style: rule.style, info: info, order: idx.order++, selectorText: rule.selectorText };
            var seen = {};
            for (var i = 0; i < rule.style.length; i++) {
                var name = rule.style[i];
                if (name.indexOf('--') === 0) { (idx.tokens[name] = idx.tokens[name] || []).push(entry); continue; }
                if (!seen[name]) { seen[name] = 1; (idx.byProp[name] = idx.byProp[name] || []).push(entry); }
            }
            if (parts.some(function (p) { return p.state; })) { idx.state.push(entry); }
        }
        function walk(list, info) {
            for (var i = 0; i < list.length; i++) {
                var r = list[i];
                if (r.selectorText !== undefined && r.style) { add(r, info); if (r.cssRules && r.cssRules.length) { /* nested CSS not traced */ } continue; }
                if (r.media && r.cssRules) { if (win.matchMedia(r.media.mediaText).matches) { walk(r.cssRules, info); } continue; }
                if (r.styleSheet) { try { walk(r.styleSheet.cssRules, info); } catch (e) { idx.unverifiable.push(info.label + ' @import'); } continue; }
                if (r.cssRules) { walk(r.cssRules, info); }
            }
        }
        var sheets = doc.styleSheets;
        for (var s = 0; s < sheets.length; s++) {
            var info = sheetInfo(sheets[s], s), rules;
            try { rules = sheets[s].cssRules; } catch (e) { idx.unverifiable.push('Cross-origin stylesheet: ' + info.label); continue; }
            walk(rules, info);
        }
        return idx;
    }

    /* Authored source text per stylesheet. Chrome's CSSOM serializes some var()-bearing
       shorthands (e.g. `background: linear-gradient(var(--primary)…)` next to background-clip)
       as empty strings, so the declared value is recovered from the source. */
    var SOURCE = {};
    async function prefetchSources(doc) {
        var jobs = [];
        Array.prototype.forEach.call(doc.styleSheets, function (sh, n) {
            if (sh.href && SOURCE[sh.href] === undefined && sh.href.indexOf(location.host) > -1) {
                SOURCE[sh.href] = '';
                jobs.push(fetch(sh.href, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) { SOURCE[sh.href] = t; }).catch(function () {}));
            }
        });
        await Promise.all(jobs);
    }
    function sourceValue(entry, names) {
        var text = SOURCE[entry.info.key];
        if (!text || !entry.selectorText) { return ''; }
        var first = splitSelectors(entry.selectorText)[0];
        var pat = first.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s*');
        var re = new RegExp(pat + '\\s*[,{]', 'g'), m;
        while ((m = re.exec(text))) {
            var open = text.indexOf('{', m.index), close = text.indexOf('}', open);
            if (open < 0 || close < 0) { break; }
            var block = text.slice(open + 1, close);
            for (var i = 0; i < names.length; i++) {
                var dm = block.match(new RegExp('(?:^|[;\\s])' + names[i] + '\\s*:\\s*([^;]+)'));
                if (dm) { return dm[1].replace(/\/\*[\s\S]*?\*\//g, '').replace(/\s+/g, ' ').trim(); }
            }
        }
        return '';
    }

    function safeMatches(el, sel) { try { return el.matches(sel); } catch (e) { return false; } }
    function better(a, b) {
        if (!b) { return true; }
        if (a.important !== b.important) { return a.important; }
        if (a.spec !== b.spec) { return a.spec > b.spec; }
        return a.order > b.order;
    }

    /** Winning declaration for one property on one element (state: '' for normal, or hover/focus…). */
    function winning(el, prop, idx, state) {
        var names = SHORTHANDS[prop] || [prop];
        var pool = prop.indexOf('--') === 0 ? (idx.tokens[prop] || []) : (state ? idx.state : collect(idx, names));
        var best = null;
        for (var i = 0; i < pool.length; i++) {
            var r = pool[i];
            var val = '', used = '', pending = false;
            for (var n = 0; n < names.length; n++) { val = r.style.getPropertyValue(names[n]); if (val) { used = names[n]; break; } }
            if (!val) {
                // Property is listed but serialized empty: pending var() substitution.
                for (var q = 0; q < r.style.length && !pending; q++) { pending = names.indexOf(r.style[q]) > -1; }
                if (!pending) { continue; }
                used = names[0];
            }
            for (var p = 0; p < r.parts.length; p++) {
                var part = r.parts[p];
                if (state) { if (!part.state || part.stateName.indexOf(state) !== 0) { continue; } } else if (part.state) { continue; }
                if (!safeMatches(el, state ? part.base : part.s)) { continue; }
                if (pending && !val) {
                    var ck = names.join(',');
                    r.srcCache = r.srcCache || {};
                    if (r.srcCache[ck] === undefined) { r.srcCache[ck] = sourceValue(r, names) || 'var(…) [pending substitution; source text not found]'; }
                    val = r.srcCache[ck];
                }
                var cand = { selector: part.s, value: val.trim(), prop: used, important: r.style.getPropertyPriority(used) === 'important', spec: part.spec, order: r.order, sheet: r.info };
                if (better(cand, best)) { best = cand; }
            }
        }
        if (!state && el.getAttribute && el.getAttribute('style')) {
            for (var k = 0; k < names.length; k++) {
                var v = el.style.getPropertyValue(names[k]);
                if (v) {
                    var inl = { selector: 'style="…"', value: v.trim(), prop: names[k], important: el.style.getPropertyPriority(names[k]) === 'important', spec: 1e9, order: 1e9, sheet: { kind: 'attr', owner: '', file: 'inline style attribute', label: 'style attribute' } };
                    if (!best || !best.important || inl.important) { best = inl; }
                    break;
                }
            }
        }
        return best;
    }
    function collect(idx, names) {
        var out = [], seen = new Set();
        names.forEach(function (n) { (idx.byProp[n] || []).forEach(function (r) { if (!seen.has(r)) { seen.add(r); out.push(r); } }); });
        // Shorthands are indexed by their longhands; make sure shorthand-only rules are included.
        if (names.indexOf('background') > -1) { (idx.byProp['background-color'] || []).concat(idx.byProp['background-image'] || []).forEach(function (r) { if (!seen.has(r)) { seen.add(r); out.push(r); } }); }
        return out.sort(function (a, b) { return a.order - b.order; });
    }

    function describeNode(el) {
        if (!el || !el.tagName) { return ''; }
        if (el === el.ownerDocument.documentElement) { return 'html (:root)'; }
        var s = el.tagName.toLowerCase();
        if (el.id) { s += '#' + el.id; }
        var cls = (typeof el.className === 'string' ? el.className : (el.getAttribute('class') || '')).trim().split(/\s+/).filter(function (c) { return c && !/^(wp-elements-|has-text-align)/.test(c); }).slice(0, 3);
        if (cls.length && cls[0]) { s += '.' + cls.join('.'); }
        return s;
    }

    /** Follow var() references from a declared value to the declarations that supply them. */
    function tokenChain(el, raw, idx, depth, seen) {
        depth = depth || 0; seen = seen || {};
        var chain = [], re = /var\(\s*(--[\w-]+)\s*(?:,\s*([^()]*(?:\([^()]*\))?[^()]*))?\)/g, m;
        if (depth > 6) { return chain; }
        while ((m = re.exec(raw || ''))) {
            var name = m[1];
            if (seen[name]) { continue; }
            seen[name] = 1;
            var node = el, decl = null, owner = null;
            while (node && node.nodeType === 1) {
                decl = winning(node, name, idx, '');
                if (decl) { owner = node; break; }
                node = node.parentElement;
            }
            var computed = el.ownerDocument.defaultView.getComputedStyle(el).getPropertyValue(name).trim();
            var link = { token: name, computed: computed, declaredOn: owner ? describeNode(owner) : '', atRoot: owner === el.ownerDocument.documentElement, value: decl ? decl.value : '', selector: decl ? decl.selector : '', source: decl ? decl.sheet : null, fallback: m[2] ? m[2].trim() : '', unresolved: !decl };
            chain.push(link);
            if (decl && decl.value.indexOf('var(') > -1) { chain = chain.concat(tokenChain(owner, decl.value, idx, depth + 1, seen)); }
        }
        return chain;
    }

    function resolveVars(el, raw) {
        var cs = el.ownerDocument.defaultView.getComputedStyle(el), guard = 0;
        while (raw.indexOf('var(') > -1 && guard++ < 10) {
            raw = raw.replace(/var\(\s*(--[\w-]+)\s*(?:,\s*([^()]*(?:\([^()]*\))?[^()]*))?\)/g, function (all, name, fb) { var v = cs.getPropertyValue(name).trim(); return v || (fb || '').trim(); });
        }
        return raw;
    }
    function literalToRgb(str, doc) {
        // Resolve any CSS color string to rgb() using the document's own engine.
        var probe = doc.createElement('span');
        probe.style.color = 'rgba(0,0,0,0)';
        probe.style.color = str;
        doc.body.appendChild(probe);
        var v = doc.defaultView.getComputedStyle(probe).color;
        probe.remove();
        return v;
    }

    /* ------------------------------------------------------------ *
     * Snapshot of one render
     * ------------------------------------------------------------ */

    function keyOf(el, body) {
        var parts = [];
        while (el && el !== body) {
            var i = 1, sib = el;
            while ((sib = sib.previousElementSibling)) { if (sib.tagName === el.tagName) { i++; } }
            parts.unshift(el.tagName.toLowerCase() + ':' + i);
            el = el.parentElement;
        }
        return parts.join('/');
    }
    function region(el) {
        if (el.closest('header, .site-header, #masthead, .ajnanda-header')) { return 'Site header'; }
        if (el.closest('footer, .site-footer, #colophon')) { return 'Site footer'; }
        if (el.closest('main, .entry-content, .site-main, article')) { return 'Content'; }
        return 'Page chrome / widget';
    }
    function ownerOf(el) {
        var n = el;
        while (n && n.nodeType === 1) {
            var sig = (n.id || '') + ' ' + (typeof n.className === 'string' ? n.className : '');
            for (var i = 0; i < PLUGIN_HINTS.length; i++) { if (PLUGIN_HINTS[i][0].test(sig)) { return PLUGIN_HINTS[i][1]; } }
            if (/ajnanda-a11y|ajn-a11y|accessibility-toolbar/i.test(sig)) { return 'AJNanda (inc/accessibility-toolbar.php)'; }
            n = n.parentElement;
        }
        return '';
    }
    function textOf(el) {
        var t = '';
        for (var i = 0; i < el.childNodes.length; i++) { if (el.childNodes[i].nodeType === 3) { t += el.childNodes[i].textContent; } }
        t = t.replace(/\s+/g, ' ').trim();
        if (!t) { t = (el.getAttribute('aria-label') || el.getAttribute('title') || el.getAttribute('alt') || '').trim(); }
        return t.slice(0, 60);
    }
    var INTERACTIVE = 'a[href], button, input:not([type=hidden]), select, textarea, summary, [role=button], .wp-block-button__link, .wp-element-button';

    function snapshot(doc, win, ctx, live) {
        var snap = { title: doc.title, tokens: { root: {}, body: {} }, els: {}, opaque: [], idx: null };
        var rs = win.getComputedStyle(doc.documentElement), bs = win.getComputedStyle(doc.body);
        TRACE_TOKENS.forEach(function (t) { snap.tokens.root[t] = rs.getPropertyValue(t).trim(); snap.tokens.body[t] = bs.getPropertyValue(t).trim(); });
        snap.bodyClass = doc.body.className;
        snap.tokens.bodyClass = doc.body.className;
        snap.idx = buildIndex(doc, win);

        var banner = null;
        Array.prototype.forEach.call(doc.body.children, function (c) { if (!banner && c.tagName === 'DIV' && /AJNanda Preview/.test(c.textContent || '') && /99999/.test(c.getAttribute('style') || '')) { banner = c; } });

        var all = doc.body.getElementsByTagName('*'), n = 0;
        for (var i = 0; i < all.length && n < MAX_ELEMENTS; i++) {
            var el = all[i], tag = el.tagName;
            if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT' || tag === 'TEMPLATE' || tag === 'BR') { continue; }
            if (banner && banner.contains(el)) { continue; }
            if (tag === 'IFRAME') { snap.opaque.push('Embedded iframe (' + (el.getAttribute('src') || '').slice(0, 80) + ') — ' + region(el)); continue; }
            if (el.shadowRoot) { snap.opaque.push('Shadow DOM: ' + describeNode(el)); }
            var cs = win.getComputedStyle(el);
            if (cs.display === 'none' || cs.visibility === 'hidden') { continue; }
            var rect = el.getBoundingClientRect();
            if (rect.width < 1 || rect.height < 1) { continue; }
            n++;
            var props = {}, isSvg = el instanceof win.SVGElement, text = textOf(el);
            if (text || tag === 'A' || tag === 'BUTTON') { props.color = cs.color; }
            if (parseColors(cs.backgroundColor).some(function (c) { return c.a > 0; })) { props['background-color'] = cs.backgroundColor; }
            if (cs.backgroundImage.indexOf('gradient') > -1) { props['background-image'] = cs.backgroundImage; }
            ['top', 'left', 'bottom'].some(function (side) {
                if (parseFloat(cs['border-' + side + '-width']) > 0 && cs['border-' + side + '-style'] !== 'none') { props['border-' + side + '-color'] = cs['border-' + side + '-color']; return true; }
                return false;
            });
            if (cs.boxShadow && cs.boxShadow !== 'none') { props['box-shadow'] = cs.boxShadow; }
            if (isSvg) {
                if (cs.fill && cs.fill !== 'none' && cs.fill.indexOf('url(') < 0) { props.fill = cs.fill; }
                if (cs.stroke && cs.stroke !== 'none' && cs.stroke.indexOf('url(') < 0) { props.stroke = cs.stroke; }
            }
            // Hover / focus: resolved from matching state rules against this element's own custom properties.
            if (el.matches(INTERACTIVE)) {
                ['hover', 'focus'].forEach(function (state) {
                    ['color', 'background-color', 'border-top-color', 'outline-color', 'box-shadow'].forEach(function (prop) {
                        var w = winning(el, prop, snap.idx, state);
                        if (!w) { return; }
                        var resolved = resolveVars(el, w.value);
                        var rgb = parseColors(resolved).length ? resolved : literalToRgb(resolved.split(/\s+/).filter(function (x) { return /^#|^rgb|^[a-z]+$/i.test(x); }).pop() || resolved, doc);
                        props[state + ':' + prop] = rgb;
                        if (live) { (snap.stateDecls = snap.stateDecls || {})[keyOf(el, doc.body) + '|' + state + ':' + prop] = w; }
                    });
                });
            }
            var keys = Object.keys(props);
            if (!keys.length) { continue; }
            var k = keyOf(el, doc.body);
            snap.els[k] = { props: props, desc: describeNode(el), text: text, region: region(el), owner: ownerOf(el), el: live ? el : null, isLogo: !!el.closest('.custom-logo-link, .site-logo, .wp-block-site-logo') || tag === 'IMG' };
        }
        snap.count = n;
        return snap;
    }

    function navigationCheck(doc) {
        var links = doc.querySelectorAll('a[href]'), internal = 0, leaves = 0, sample = [];
        Array.prototype.forEach.call(links, function (a) {
            var href = a.getAttribute('href') || '';
            if (href.charAt(0) === '#' || /^(mailto|tel|javascript):/.test(href)) { return; }
            var u; try { u = new URL(href, doc.baseURI); } catch (e) { return; }
            if (u.host !== location.host) { return; }
            internal++;
            if (u.search.indexOf('action=ajnanda_preview') < 0 || u.search.indexOf('scheme=') < 0) { leaves++; if (sample.length < 5) { sample.push(u.pathname + u.search.slice(0, 40)); } }
        });
        return { internal: internal, leaves: leaves, sample: sample };
    }

    /* ------------------------------------------------------------ *
     * Comparison
     * ------------------------------------------------------------ */

    function compare(base, comp, ctx, job, out) {
        Object.keys(comp.els).forEach(function (key) {
            var b = base.els[key], c = comp.els[key];
            if (!b) { return; }
            Object.keys(c.props).forEach(function (prop) {
                if (b.props[prop] === undefined) { return; }
                var bc = parseColors(b.props[prop]), cc = parseColors(c.props[prop]);
                bc.forEach(function (bcol, i) {
                    if (bcol.a === 0) { return; }
                    var ccol = cc[i] || null, role = roleOf(bcol.hex, ctx), kind = null;
                    if (role) {
                        if (ccol && ccol.hex === role.comparison) { out.follows++; if (out.working.length < 3) { out.working.push(record(key, prop, i, bcol, ccol, role, 'follows', c, comp, job)); } return; }
                        kind = ccol && ccol.hex === bcol.hex ? 'bypassed' : 'wrong-token';
                    } else if (isTint(bcol.hex, ctx) && ccol && ccol.hex === bcol.hex) {
                        kind = 'tint-fixed';
                    }
                    if (!kind) { return; }
                    out.bypassed++;
                    out.findings.push(record(key, prop, i, bcol, ccol, role, kind, c, comp, job));
                });
            });
        });
    }

    function record(key, prop, stop, bcol, ccol, role, kind, c, comp, job) {
        var state = prop.indexOf(':') > -1 ? prop.split(':')[0] : '';
        var baseProp = state ? prop.split(':')[1] : prop;
        var el = c.el, decl = null, chain = [], inheritedFrom = '';
        if (el) {
            if (state) { decl = (comp.stateDecls || {})[key + '|' + prop] || null; }
            else {
                decl = winning(el, baseProp, comp.idx, '');
                if (decl && baseProp === 'color' && /^(inherit|currentcolor|unset)$/i.test(decl.value)) { inheritedFrom = 'value ' + decl.value; decl = null; }
                if (!decl && baseProp === 'color') {
                    var p = el.parentElement;
                    while (p && !decl) { decl = winning(p, 'color', comp.idx, ''); if (decl && /^(inherit|currentcolor|unset)$/i.test(decl.value)) { decl = null; } if (decl) { inheritedFrom = describeNode(p); el = p; } p = p.parentElement; }
                }
            }
            if (decl && decl.value.indexOf('var(') > -1) { chain = tokenChain(el, decl.value, comp.idx); }
        }
        return {
            target: job.target.id, label: job.target.label, url: job.target.url, group: job.target.group || 'Demo', kind: job.target.kind, resolver: job.target.resolver, viewport: job.viewport,
            element: c.desc, text: c.text, region: c.region, owner: c.owner, isLogo: c.isLogo, key: key,
            property: baseProp, state: state, stop: stop, status: kind, role: role ? role.role : '',
            expectedBase: role ? role.baseline : bcol.hex, observedBase: bcol.hex,
            expectedComp: role ? role.comparison : '(palette-derived tint)', observedComp: ccol ? ccol.hex : '(none)',
            decl: decl ? { selector: decl.selector, value: decl.value, important: decl.important, source: decl.sheet } : null,
            inheritedFrom: inheritedFrom, chain: chain
        };
    }

    /* ------------------------------------------------------------ *
     * Root cause classification
     * ------------------------------------------------------------ */

    function classify(f, ctx) {
        var d = f.decl, src = d ? d.source : null;
        var owner = f.owner || (src && src.owner) || '';
        var editable = src ? src.file : 'unknown';
        var cause, fix;
        if (!d) {
            cause = 'untraced';
            fix = 'Winning declaration not found (user-agent default, nested CSS, cross-origin sheet or script-applied style). Inspect manually.';
        } else if (src.kind === 'attr') {
            cause = f.region === 'Content' ? 'content-inline' : 'template-inline';
            editable = f.kind === 'demo' ? (ctx.plan.targets[0] && ctx.plan.targets[0].source) + ' (demo pattern markup)' : (f.region === 'Content' ? 'Saved block content on this page' : 'Template / widget markup (style attribute)');
            fix = 'Replace the inline ' + f.property + ' literal with a palette preset or var(--' + (f.role || 'primary').replace('_', '-') + ').';
        } else if (/mu-plugin/.test(src.owner)) {
            cause = d.value.indexOf('var(') < 0 ? 'site-hardcoded' : 'overridden';
            fix = cause === 'site-hardcoded' ? 'Site mu-plugin hardcodes ' + d.value + ' (' + d.selector + '). Replace with var(--' + (f.role || 'primary').replace('_', '-') + ') or remove the rule.' : 'Site mu-plugin rule ' + d.selector + ' re-declares the palette; remove the redeclaration.';
        } else if (/Plugin/.test(src.owner) || /Plugin/.test(f.owner)) {
            cause = 'plugin';
            fix = 'Plugin-owned color. Configure it in the plugin\'s settings or map it to the AJNanda palette with a token (e.g. var(--primary)).';
        } else {
            var rootLink = null, literalLink = null, unresolved = null, overridden = null;
            f.chain.forEach(function (l) {
                if (l.unresolved && !unresolved) { unresolved = l; }
                if (l.atRoot && /var\(\s*--(primary|primary-dark|secondary|accent)\b/.test(l.value) && f.resolver !== 'apply' && !rootLink) { rootLink = l; }
                if (!literalLink && l.value && l.value.indexOf('var(') < 0 && !CORE_TOKENS[l.token]) { literalLink = l; }
                if (CORE_TOKENS[l.token] && hexOf(l.computed) && hexOf(l.computed) !== ctx.comparison[CORE_TOKENS[l.token]].toLowerCase()) { overridden = l; }
            });
            var siteOverride = overridden && overridden.source && /mu-plugin|Plugin/.test(overridden.source.owner);
            if (siteOverride && !literalLink) {
                cause = 'overridden';
                editable = overridden.source.file + ' — ' + overridden.token + ' re-declared on ' + overridden.declaredOn + ' (' + overridden.selector + ' = ' + overridden.value + ')';
                fix = 'Remove the palette redeclaration (' + overridden.selector + ' { ' + overridden.token + ': ' + overridden.value + ' }) from ' + overridden.source.file + ' so --primary/--secondary come from the AJNanda palette.';
            } else if (d.value.indexOf('var(') < 0) {
                cause = 'hardcoded';
                fix = 'Replace the literal in ' + src.file + ' (' + d.selector + ' { ' + f.property + ': ' + d.value + ' }) with var(--' + (f.role || 'primary').replace('_', '-') + ') or a documented alias.';
            } else if (rootLink) {
                cause = 'scope-trap';
                editable = (rootLink.source ? rootLink.source.file : src.file) + ' — ' + rootLink.token + ' declared on ' + rootLink.declaredOn;
                fix = rootLink.token + ' is declared on :root as ' + rootLink.value + ', so it resolves to the :root palette before the body.ajnanda-scheme-* class applies. Make the preview override the brand theme_mods for the request (the validator\'s apply resolver) or redeclare derived tokens on the scheme scope.';
            } else if (literalLink) {
                var mod = TOKEN_MODS[literalLink.token];
                var saved = mod && ctx.savedMods[mod];
                cause = mod ? (saved ? 'saved-override' : 'php-literal') : (/global-styles/.test(literalLink.source ? literalLink.source.label : '') ? 'preset-literal' : 'token-literal');
                editable = (literalLink.source ? literalLink.source.file : '') + ' — ' + literalLink.token + (mod ? ' ← theme_mod ' + mod + (saved ? ' (saved: ' + saved + ')' : ' (unsaved; PHP default)') : '');
                if (cause === 'saved-override') { fix = 'theme_mod ' + mod + ' is saved as ' + saved + '. Clear it (or re-pick it from the palette) so the token falls back to the brand role.'; }
                else if (cause === 'php-literal') { fix = 'functions.php ajnanda_customizer_css(): ' + literalLink.token + ' prints a literal default for theme_mod ' + mod + '. When the mod is unset, print var(--' + (f.role || 'primary').replace('_', '-') + ') (or omit it so style.css\'s var() default applies).'; }
                else if (cause === 'preset-literal') { fix = literalLink.token + ' is a theme.json preset printed as a literal (synced from theme_mods by ajnanda_sync_brand_palette()). It follows a real apply but not the body-class preview; preview must filter the theme_mods.'; }
                else { fix = literalLink.token + ' is declared as the literal ' + literalLink.value + ' in ' + (literalLink.source ? literalLink.source.file : '?') + '. Declare it as var(--' + (f.role || 'primary').replace('_', '-') + ').'; }
            } else if (unresolved) {
                cause = 'unresolved';
                fix = unresolved.token + ' is not defined for this element; the fallback ' + (unresolved.fallback || '(none)') + ' renders. Define the token in the palette or use an existing one.';
            } else if (overridden) {
                cause = 'overridden';
                editable = (overridden.source ? overridden.source.file : '') + ' — ' + overridden.token + ' redeclared on ' + overridden.declaredOn;
                fix = overridden.token + ' is redeclared closer to the element (' + overridden.selector + '), overriding the selected scheme.';
            } else {
                cause = 'wrong-token';
                fix = 'Uses a token that does not map to the ' + (f.role || 'expected') + ' role; switch to the matching semantic token.';
            }
        }
        if (f.status === 'tint-fixed' && cause === 'hardcoded') { cause = 'hardcoded-tint'; fix = 'Blue-family tint hardcoded in ' + editable + ' (' + (d ? d.selector : '') + '). Derive it from the palette (e.g. color-mix(in srgb, var(--primary) 12%, white)).'; }
        if (f.isLogo) { cause = 'intentional'; fix = 'Logo / artwork — intentionally fixed.'; }
        return { cause: cause, owner: owner || (src ? src.owner : ''), editable: editable, fix: fix };
    }

    var CAUSE_LABEL = {
        'scope-trap': 'Derived token resolved at :root (preview scope trap)',
        'php-literal': 'Token printed as a PHP literal default',
        'saved-override': 'Saved setting overrides the palette',
        'preset-literal': 'theme.json preset printed as a literal',
        'token-literal': 'Token declared as a literal',
        'hardcoded': 'Hardcoded brand color',
        'hardcoded-tint': 'Hardcoded blue-family tint',
        'content-inline': 'Inline color in saved content',
        'template-inline': 'Inline color in template/demo markup',
        'plugin': 'Plugin-owned color',
        'unresolved': 'Unresolved token (fallback used)',
        'overridden': 'Palette token overridden',
        'site-hardcoded': 'Hardcoded color in site mu-plugin',
        'wrong-token': 'Incorrect token usage',
        'untraced': 'Not traceable',
        'intentional': 'Intentional fixed color'
    };
    var CAUSE_RANK = ['overridden', 'site-hardcoded', 'scope-trap', 'php-literal', 'saved-override', 'preset-literal', 'token-literal', 'hardcoded', 'hardcoded-tint', 'template-inline', 'content-inline', 'overridden', 'unresolved', 'wrong-token', 'plugin', 'untraced', 'intentional'];

    /* ------------------------------------------------------------ *
     * Report
     * ------------------------------------------------------------ */

    function esc(s) { return String(s === undefined || s === null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function sw(hex) { return /^#[0-9a-f]{6}$/i.test(hex || '') ? '<span class="cv-sw" style="background:' + hex + '"></span>' + esc(hex) : esc(hex); }
    function chainText(chain) {
        if (!chain || !chain.length) { return '—'; }
        return chain.map(function (l) { return l.token + (l.unresolved ? ' (undefined → fallback ' + (l.fallback || 'none') + ')' : ' = ' + l.value + ' @ ' + (l.declaredOn || '?') + (l.source ? ' [' + l.source.label + ']' : '')) + ' ⇒ ' + l.computed; }).join('  →  ');
    }

    function buildReport(plan, ctx, results) {
        var findings = [], working = [];
        results.forEach(function (r) { findings = findings.concat(r.findings); working = working.concat(r.working); });
        findings.forEach(function (f) { var c = classify(f, ctx); f.cause = c.cause; f.ownerResolved = c.owner; f.editable = c.editable; f.fix = c.fix; });

        // Group by root cause + winning declaration/token.
        var groups = {};
        findings.forEach(function (f) {
            var tokenKey = f.chain.length ? f.chain.filter(function (l) { return !CORE_TOKENS[l.token]; }).map(function (l) { return l.token; }).join('>') : '';
            var key = f.cause + '|' + (f.decl ? (f.decl.source.label + '|' + (f.cause === 'content-inline' ? f.target : f.decl.selector) + '|' + f.decl.value) : f.element) + '|' + tokenKey + '|' + f.property + (f.state ? ':' + f.state : '');
            var g = groups[key] || (groups[key] = { key: key, cause: f.cause, label: CAUSE_LABEL[f.cause] || f.cause, fix: f.fix, editable: f.editable, owner: f.ownerResolved, decl: f.decl, chain: f.chain, property: f.property, state: f.state, items: [], pages: {}, resolvers: {}, roles: {} });
            g.items.push(f);
            g.pages[f.label] = f.url;
            g.resolvers[f.resolver] = 1;
            if (f.role) { g.roles[f.role] = 1; }
        });
        var groupList = Object.keys(groups).map(function (k) { return groups[k]; });
        groupList.forEach(function (g) {
            g.pageCount = Object.keys(g.pages).length;
            g.score = (CAUSE_RANK.length - CAUSE_RANK.indexOf(g.cause)) * 0 + g.items.length + g.pageCount * 5 + (g.roles.primary ? 20 : 0);
        });
        groupList.sort(function (a, b) {
            if (a.cause === 'intentional' || b.cause === 'intentional') { return a.cause === 'intentional' ? 1 : -1; }
            return b.score - a.score;
        });

        // Verdict numbers per resolver.
        var byResolver = {};
        results.forEach(function (r) {
            var k = r.target.resolver, x = byResolver[k] || (byResolver[k] = { follows: 0, bypassed: 0, renders: 0, notLoaded: 0, failed: 0 });
            x.follows += r.follows; x.bypassed += r.bypassed; x.renders++;
            if (r.status === 'scheme-not-loaded') { x.notLoaded++; }
            if (r.status === 'failed') { x.failed++; }
        });
        var causeTotals = {};
        groupList.forEach(function (g) { if (g.cause !== 'intentional') { causeTotals[g.cause] = (causeTotals[g.cause] || 0) + g.items.length; } });
        var topCauses = Object.keys(causeTotals).sort(function (a, b) { return causeTotals[b] - causeTotals[a]; }).slice(0, 4);
        var demo = results.filter(function (r) { return r.target.kind === 'demo' && r.viewport === 'desktop'; })[0];

        var B = plan.baseline, C = plan.comparison, html = '';
        html += '<div class="cv-report">';
        html += '<h2>' + esc(B.label) + ' vs ' + esc(C.label) + ' — validation report</h2>';
        html += '<p class="cv-meta">' + esc(plan.site) + ' · AJNanda ' + esc(plan.theme) + ' · generated ' + esc(new Date().toLocaleString()) + ' · active scheme: <strong>' + esc(plan.active.slug) + '</strong> (unchanged; read-only run)</p>';

        // Verdict
        html += '<h3>Verdict</h3><div class="cv-verdict">';
        var rolesTxt = plan.roles.map(function (r) { return r.token + ' ' + r.baseline + (r.differs ? ' → ' + r.comparison : ' (same in both)'); }).join(', ');
        html += '<p>Palette roles: ' + esc(rolesTxt) + '.</p>';
        Object.keys(byResolver).forEach(function (k) {
            var x = byResolver[k], tot = x.follows + x.bypassed, pct = tot ? Math.round(100 * x.bypassed / tot) : 0;
            var name = k === 'preview' ? 'Existing preview (Corporate Home demo)' : k === 'apply' ? 'Real pages, simulated apply (theme_mods filtered for the request)' : 'Real homepage with the preview mechanism (body class only)';
            html += '<p><strong>' + esc(name) + ':</strong> ' + x.bypassed + ' of ' + tot + ' brand-colored property values (' + pct + '%) kept their ' + esc(B.label) + ' value or a blue-family tint; ' + x.follows + ' followed ' + esc(C.label) + '.' + (x.notLoaded ? ' ' + x.notLoaded + ' render(s) did not load the selected scheme.' : '') + (x.failed ? ' ' + x.failed + ' render(s) failed.' : '') + '</p>';
        });
        if (demo && demo.loaded) {
            html += demo.loaded.reached
                ? '<p>The preview <strong>does</strong> load ' + esc(C.label) + ' (body.ajnanda-scheme-' + esc(C.slug) + ' is present), but <body> resolves --primary to ' + sw(demo.loaded.body) + ' (:root ' + sw(demo.loaded.root) + ')' + (demo.override ? ' because <code>' + esc(demo.override.selector + ' { --primary: ' + demo.override.value + ' }') + '</code> in ' + esc(demo.override.source.label) + ' wins on specificity' : '') + '. This is a component/site layer bypassing a correctly loaded scheme, not a failed preview.</p>'
                : '<p>The preview did <strong>not</strong> load ' + esc(C.label) + '.</p>';
        }
        var realOverride = results.filter(function (r) { return r.target.resolver === 'apply' && r.override; })[0];
        if (realOverride) {
            html += '<p>Real pages (simulated apply): :root --primary becomes ' + sw(realOverride.loaded.root) + ', but <body> re-declares it as ' + sw(realOverride.loaded.body) + ' via <code>' + esc(realOverride.override.selector) + '</code> [' + esc(realOverride.override.source.label) + '] on ' + results.filter(function (r) { return r.override; }).length + ' of ' + results.length + ' renders, so an actual Blue → ' + esc(C.label) + ' apply would also stay mostly blue.</p>';
        }
        if (topCauses.length) { html += '<p>Main reasons: ' + topCauses.map(function (c) { return esc(CAUSE_LABEL[c]) + ' (' + causeTotals[c] + ')'; }).join('; ') + '.</p>'; }
        if (demo && demo.navigation) { html += '<p>Preview navigation: ' + demo.navigation.leaves + ' of ' + demo.navigation.internal + ' internal links in the demo leave the preview and lose the scheme' + (demo.navigation.sample.length ? ' (e.g. ' + esc(demo.navigation.sample.join(', ')) + ')' : '') + '.</p>'; }
        html += '<p>Apply path vs preview: ' + esc(plan.resolvers.preview) + ' ' + esc(plan.resolvers.apply) + '</p>';
        html += '</div>';

        // Coverage
        html += '<h3>Coverage</h3><table class="widefat striped cv-table"><thead><tr><th>View</th><th>Group</th><th>Resolver</th><th>Viewport</th><th>Status</th><th>Followed</th><th>Bypassed</th><th>Notes</th></tr></thead><tbody>';
        results.forEach(function (r) {
            html += '<tr><td><a href="' + esc(r.target.url) + '" target="_blank" rel="noopener">' + esc(r.target.label) + '</a></td><td>' + esc(r.target.group || 'Demo') + '</td><td>' + esc(r.target.resolver) + '</td><td>' + esc(r.viewport) + '</td><td><span class="cv-st cv-st-' + esc(r.status) + '">' + esc(r.status) + '</span></td><td>' + r.follows + '</td><td>' + r.bypassed + '</td><td>' + esc(r.notes.concat(r.unverifiable.slice(0, 3)).join(' · ')) + (r.unverifiable.length > 3 ? ' (+' + (r.unverifiable.length - 3) + ' more unverifiable)' : '') + '</td></tr>';
        });
        html += '</tbody></table>';
        html += '<p><strong>Not verified:</strong></p><ul class="cv-list">' + plan.gaps.map(function (g) { return '<li><strong>' + esc(g.view) + '</strong> — ' + esc(g.reason) + '</li>'; }).join('') + '</ul>';

        // Prioritized fixes
        html += '<h3>Prioritized fix list (grouped by root cause)</h3><ol class="cv-fixes">';
        groupList.forEach(function (g) {
            html += '<li><strong>' + esc(g.label) + '</strong> — ' + g.items.length + ' value(s) on ' + g.pageCount + ' view(s)' + (g.state ? ' [' + esc(g.state) + ' state, rule-resolved]' : '') + '<br>';
            html += '<span class="cv-k">Winning declaration:</span> ' + (g.decl ? '<code>' + esc(g.decl.selector) + ' { ' + esc((g.decl.important ? g.decl.value + ' !important' : g.decl.value)) + ' }</code> in ' + esc(g.decl.source.label) : '—') + '<br>';
            html += '<span class="cv-k">Token chain:</span> <code>' + esc(chainText(g.chain)) + '</code><br>';
            html += '<span class="cv-k">Editable source:</span> ' + esc(g.editable) + ' · <span class="cv-k">Owner:</span> ' + esc(g.owner || '—') + '<br>';
            html += '<span class="cv-k">Fix:</span> ' + esc(g.fix) + '<br>';
            html += '<details><summary>Affected views (' + g.pageCount + ')</summary><ul>' + Object.keys(g.pages).map(function (p) { return '<li>' + esc(p) + ' — <code>' + esc(g.pages[p]) + '</code></li>'; }).join('') + '</ul></details></li>';
        });
        html += '</ol>';

        // Examples
        html += '<h3>Examples</h3><ul class="cv-list">';
        var w = working.filter(function (x) { return x.decl; })[0];
        if (!w && working[0]) { w = working[0]; }
        if (w) {
            var wd = w.decl || null;
            var wel = null;
            html += '<li><strong>Working token:</strong> ' + esc(w.label) + ' — ' + esc(w.element) + ' ' + esc(w.property) + ' renders ' + sw(w.observedBase) + ' → ' + sw(w.observedComp) + (wd ? ' via <code>' + esc(wd.selector + ' { ' + wd.value + ' }') + '</code> [' + esc(wd.source.label) + ']' : '') + '.</li>';
        }
        var broken = groupList.filter(function (g) { return g.cause !== 'intentional' && g.cause !== 'plugin'; })[0];
        if (broken) { html += '<li><strong>Broken / overridden token:</strong> ' + esc(broken.label) + ' — ' + esc(broken.items[0].element) + ' ' + esc(broken.property) + ' stays ' + sw(broken.items[0].observedComp) + ' under ' + esc(C.label) + '; chain <code>' + esc(chainText(broken.chain)) + '</code>.</li>'; }
        var fixed = groupList.filter(function (g) { return g.cause === 'intentional'; })[0];
        var same = plan.roles.filter(function (r) { return !r.differs; }).map(function (r) { return r.token + ' ' + r.baseline; });
        html += '<li><strong>Intentional fixed color:</strong> ' + (fixed ? esc(fixed.items[0].element) + ' (logo/artwork). ' : 'Logo images are raster artwork and are not recolored. ') + (same.length ? 'Roles defined identically in both palettes legitimately look the same: ' + esc(same.join(', ')) + '.' : '') + '</li>';
        html += '</ul>';

        // Findings table
        html += '<h3>Findings</h3><p class="cv-meta">' + findings.length + ' property values. Expected/observed shown as ' + esc(B.label) + ' | ' + esc(C.label) + '.</p>';
        html += '<div class="cv-scroll"><table class="widefat striped cv-table cv-findings"><thead><tr><th>View</th><th>Element</th><th>Property</th><th>Expected</th><th>Observed</th><th>Winning declaration</th><th>Token chain</th><th>Editable source</th><th>Owner</th><th>Cause / fix</th></tr></thead><tbody>';
        findings.slice(0, 400).forEach(function (f) {
            html += '<tr><td>' + esc(f.label) + '<br><small>' + esc(f.viewport) + ' · ' + esc(f.resolver) + '</small></td><td><code>' + esc(f.element) + '</code><br><small>' + esc(f.region) + (f.text ? ' · “' + esc(f.text) + '”' : '') + '</small></td><td>' + esc((f.state ? f.state + ':' : '') + f.property) + (f.stop ? ' [stop ' + (f.stop + 1) + ']' : '') + '</td><td>' + sw(f.expectedBase) + '<br>' + sw(f.expectedComp) + '</td><td>' + sw(f.observedBase) + '<br>' + sw(f.observedComp) + '</td><td>' + (f.decl ? '<code>' + esc(f.decl.selector) + '</code><br><small>' + esc(f.decl.value) + ' — ' + esc(f.decl.source.label) + '</small>' : '—') + (f.inheritedFrom ? '<br><small>inherited from ' + esc(f.inheritedFrom) + '</small>' : '') + '</td><td><small>' + esc(chainText(f.chain)) + '</small></td><td><small>' + esc(f.editable) + '</small></td><td><small>' + esc(f.ownerResolved || '—') + '</small></td><td><small><strong>' + esc(CAUSE_LABEL[f.cause] || f.cause) + '</strong><br>' + esc(f.fix) + '</small></td></tr>';
        });
        html += '</tbody></table></div>' + (findings.length > 400 ? '<p class="cv-meta">First 400 shown; all ' + findings.length + ' are in the downloaded report data.</p>' : '');

        // Source scan
        var s = plan.source, counts = s.counts || {};
        html += '<h3>Source scan</h3><p class="cv-meta">' + s.files + ' theme files plus saved theme_mods, Additional CSS, Global Styles, published content and widgets. Literal counts: ' + Object.keys(counts).map(function (k) { return esc(k) + ' ' + counts[k]; }).join(' · ') + '. Active plugins (styles not scanned in source, see rendered findings): ' + esc((s.plugins || []).join(', ')) + '.</p>';
        html += '<div class="cv-scroll"><table class="widefat striped cv-table"><thead><tr><th>Class</th><th>Location</th><th>Selector / context</th><th>Literal</th><th>Role</th></tr></thead><tbody>';
        (s.items || []).slice(0, 250).forEach(function (it) {
            html += '<tr><td>' + esc(it.class) + '</td><td>' + esc(it.ref) + (it.line ? ':' + it.line : '') + '</td><td><small>' + esc(it.selector ? it.selector + ' → ' : '') + esc(it.snippet) + '</small></td><td>' + sw(it.hex) + (it.literal.toLowerCase() !== it.hex ? ' <small>(' + esc(it.literal) + ')</small>' : '') + '</td><td>' + esc(it.role || '—') + '</td></tr>';
        });
        html += '</tbody></table></div>' + (s.total > 250 ? '<p class="cv-meta">First 250 of ' + s.total + ' source findings shown; all are in the downloaded report data.</p>' : '');
        html += '</div>';

        var data = {
            plan: { generated: plan.generated, site: plan.site, theme: plan.theme, baseline: B, comparison: C, active: plan.active, roles: plan.roles, gaps: plan.gaps, resolvers: plan.resolvers },
            coverage: results.map(function (r) { return { override: r.override || null, id: r.target.id, label: r.target.label, url: r.target.url, group: r.target.group || 'Demo', resolver: r.target.resolver, viewport: r.viewport, status: r.status, follows: r.follows, bypassed: r.bypassed, notes: r.notes, unverifiable: r.unverifiable, loaded: r.loaded || null, navigation: r.navigation || null, baselineTokens: r.baselineTokens || null, comparisonTokens: r.comparisonTokens || null }; }),
            groups: groupList.map(function (g) { return { cause: g.cause, label: g.label, count: g.items.length, pages: g.pages, decl: g.decl, chain: g.chain, editable: g.editable, owner: g.owner, fix: g.fix, property: g.property, state: g.state }; }),
            findings: findings.map(function (f) { var o = Object.assign({}, f); delete o.key; return o; }),
            working: working,
            source: plan.source
        };
        return { html: html, data: data, baseline: B, comparison: C };
    }

    var REPORT_CSS = '.cv-report{font:13px/1.45 -apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;color:#1f2937}.cv-report h2{margin:.2em 0}.cv-report h3{margin:1.4em 0 .4em}.cv-meta{color:#6b7280}.cv-verdict p{margin:.35em 0}.cv-table{border-collapse:collapse;width:100%}.cv-table th,.cv-table td{text-align:left;vertical-align:top;padding:4px 6px;border-bottom:1px solid #e5e7eb}.cv-table code,.cv-fixes code{font-size:11px;word-break:break-word}.cv-sw{display:inline-block;width:11px;height:11px;border-radius:2px;margin-right:4px;vertical-align:-1px;box-shadow:0 0 0 1px rgba(0,0,0,.2)}.cv-st{padding:1px 6px;border-radius:9px;background:#f3f4f6}.cv-st-verified{background:#ecfdf5;color:#047857}.cv-st-scheme-overridden{background:#fff7ed;color:#9a3412}.cv-st-failed,.cv-st-scheme-not-loaded{background:#fef2f2;color:#b91c1c}.cv-st-partial{background:#fffbeb;color:#92400e}.cv-fixes li{margin-bottom:10px}.cv-k{color:#6b7280}.cv-scroll{max-height:520px;overflow:auto;border:1px solid #e5e7eb}.cv-list li{margin:.25em 0}';

    function standalone(report) {
        var title = 'AJNanda color validation — ' + report.baseline.label + ' vs ' + report.comparison.label;
        return '<!doctype html><html><head><meta charset="utf-8"><title>' + esc(title) + '</title><style>body{margin:24px;max-width:1500px}' + REPORT_CSS + '</style></head><body>' + report.html +
            '<script type="application/json" id="ajnanda-color-validation-data">' + JSON.stringify(report.data).replace(/</g, '\\u003c') + '</' + 'script></body></html>';
    }

    function download() {
        if (!last) { return; }
        var stamp = new Date().toISOString().slice(0, 16).replace(/[-:T]/g, '');
        var blob = new Blob([standalone(last)], { type: 'text/html' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'ajnanda-color-validation-' + last.baseline.slug + '-vs-' + last.comparison.slug + '-' + stamp + '.html';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
    }

    // Report styles on the admin screen too.
    var st = document.createElement('style');
    st.textContent = REPORT_CSS;
    document.head.appendChild(st);
})();
