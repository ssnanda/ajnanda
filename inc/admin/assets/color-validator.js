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
    root.setAttribute('data-cv-ready', '1');

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

        setStatus('Tracing fixes to files and settings…', 97);
        var model = buildModel(plan, ctx, results);
        await locate(model);
        finishModel(model);
        setStatus('Building report…', 99);
        var report = renderReport(model);
        reportEl.innerHTML = report.html;
        last = report;
        window.ajnandaColorValidatorLastReport = { html: standalone(report), data: report.data };
        dlBtn.hidden = false;
        runBtn.disabled = false;
        var sum = report.data.summary;
        setStatus('Done — ' + results.length + ' renders · ' + sum.uniqueFixes + ' unique fixes · ' + sum.components + ' components · ' + sum.observations + ' observations.', 100);
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
            out.adminSkipped = comp.adminSkipped || 0;
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
        return { plan: plan, roles: roles, baseline: plan.baseline, comparison: plan.comparison, baseHue: bh[0], savedMods: saved, mods: plan.mods || {} };
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

    var INHERITED_RE = /^(inherit|currentcolor|unset|revert|revert-layer)$/i;
    /** Nearest element (self or ancestor) with a real `color` declaration. */
    function traceColor(node, idx) {
        while (node && node.nodeType === 1) {
            var d = winning(node, 'color', idx, '');
            if (d && !INHERITED_RE.test(d.value)) { return { decl: d, el: node }; }
            node = node.parentElement;
        }
        return null;
    }
    function firstClass(el) {
        var c = (typeof el.className === 'string' ? el.className : (el.getAttribute('class') || '')).trim().split(/\s+/);
        return c.filter(function (x) { return x && !/^(wp-elements-|has-|is-layout|wp-container|is-content-justification|are-vertically)/.test(x); })[0] || '';
    }
    var TAG_NAME = { a: 'Links', button: 'Buttons', h1: 'Headings', h2: 'Headings', h3: 'Headings', h4: 'Headings', input: 'Form fields', select: 'Form fields', textarea: 'Form fields', svg: 'Icons', path: 'Icons', span: 'Text', p: 'Text', li: 'List items', div: 'Blocks', body: 'Page body', strong: 'Text', summary: 'Disclosure' };
    function humanize(cls) {
        var c = cls.replace(/^(wp-block-|is-style-|ajnanda-|ajn-)/, '').replace(/^ajp-/, 'AJCore product ').replace(/^ajcore-/, 'AJCore ').replace(/^ajforms-/, 'AJForms ').replace(/__|--|[-_]+/g, ' ').replace(/\s+/g, ' ').trim();
        return c.charAt(0).toUpperCase() + c.slice(1);
    }
    /** Human component name from the rule that styles the element (or the element itself). */
    function componentOf(sel, el) {
        var name = '';
        if (sel && sel.indexOf('style=') < 0) {
            var clean = sel.replace(STATE_RE, '').replace(/:(?:where|is)\(([^()]*)\)/g, '$1').split(',')[0].replace(/:not\([^()]*\)/g, '').replace(/::?[\w-]+(\([^()]*\))?/g, '').trim();
            var parts = clean.split(/\s*[>+~]\s*|\s+/).filter(Boolean);
            var withCls = parts.filter(function (p) { return /\.[\w-]/.test(p); });
            var pick = withCls.length ? withCls[withCls.length - 1] : (parts[parts.length - 1] || '');
            var cls = (pick.match(/\.([\w-]+)/) || [])[1], tail = parts[parts.length - 1] || '', tailTag = (tail.match(/^[a-z][\w-]*/i) || [''])[0].toLowerCase();
            if (cls) { name = humanize(cls) + (tail !== pick && !/\./.test(tail) && TAG_NAME[tailTag] ? ' ' + TAG_NAME[tailTag].toLowerCase() : ''); }
            else if (tailTag) { name = TAG_NAME[tailTag] || tailTag; }
        }
        if (!name && el) {
            var fc = firstClass(el);
            name = fc ? humanize(fc) : (TAG_NAME[el.tagName.toLowerCase()] || el.tagName.toLowerCase());
        }
        return name;
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
        // Admin chrome (toolbar) is not part of the website; the preview resolver still prints it.
        var adminBar = doc.getElementById('wpadminbar');
        snap.adminSkipped = 0;

        var all = doc.body.getElementsByTagName('*'), n = 0;
        for (var i = 0; i < all.length && n < MAX_ELEMENTS; i++) {
            var el = all[i], tag = el.tagName;
            if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT' || tag === 'TEMPLATE' || tag === 'BR') { continue; }
            if (banner && banner.contains(el)) { continue; }
            if (adminBar && (adminBar === el || adminBar.contains(el))) { snap.adminSkipped++; continue; }
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
        var links = doc.querySelectorAll('a[href]'), internal = 0, leaves = 0, admin = 0, sample = [];
        var adminBar = doc.getElementById('wpadminbar');
        Array.prototype.forEach.call(links, function (a) {
            if (adminBar && adminBar.contains(a)) { admin++; return; }
            var href = a.getAttribute('href') || '';
            if (href.charAt(0) === '#' || /^(mailto|tel|javascript):/.test(href)) { return; }
            var u; try { u = new URL(href, doc.baseURI); } catch (e) { return; }
            if (u.host !== location.host) { return; }
            if (/^\/wp-(admin|login)/.test(u.pathname)) { admin++; return; }
            internal++;
            if (u.search.indexOf('action=ajnanda_preview') < 0 || u.search.indexOf('scheme=') < 0) { leaves++; if (sample.length < 5) { sample.push(u.pathname + u.search.slice(0, 40)); } }
        });
        return { internal: internal, leaves: leaves, admin: admin, sample: sample };
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
        var el = c.el, decl = null, chain = [], inheritedFrom = '', component = '', elClass = '';
        if (el) {
            elClass = firstClass(el);
            if (state) { decl = (comp.stateDecls || {})[key + '|' + prop] || null; }
            else { decl = winning(el, baseProp, comp.idx, ''); }
            component = componentOf(decl ? decl.selector : '', el);
            // Resolve inherited values before classifying: the editable source is
            // whatever declares the color they stand for.
            var t = null;
            if (decl && baseProp === 'color' && INHERITED_RE.test(decl.value)) {
                inheritedFrom = 'color: ' + decl.value;
                t = traceColor(el.parentElement, comp.idx);
            } else if (!decl && baseProp === 'color') {
                t = traceColor(el.parentElement, comp.idx);
            } else if (decl && baseProp !== 'color' && /currentcolor/i.test(decl.value)) {
                inheritedFrom = baseProp + ': ' + decl.value + ' → color';
                t = traceColor(el, comp.idx);
            } else if (!decl && /border|outline/.test(baseProp)) {
                inheritedFrom = baseProp + ' initial currentcolor → color';
                t = traceColor(el, comp.idx);
            }
            if (t) { decl = t.decl; el = t.el; inheritedFrom += (inheritedFrom ? ' · ' : '') + 'declared on ' + describeNode(t.el); }
            else if (inheritedFrom && (INHERITED_RE.test(decl ? decl.value : '') || /currentcolor/i.test(decl ? decl.value : ''))) { decl = null; }
            if (decl && decl.value.indexOf('var(') > -1) { chain = tokenChain(el, decl.value, comp.idx); }
        }
        return {
            target: job.target.id, label: job.target.label, url: job.target.url, group: job.target.group || 'Demo', kind: job.target.kind, resolver: job.target.resolver, viewport: job.viewport,
            object: job.target.object || '', component: component || 'Element', elClass: elClass,
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

    /* ------------------------------------------------------------ *
     * Fix model: one fix per editable source
     *
     * Every observation (one property value on one element in one render)
     * is mapped to the single place you would edit to make it follow the
     * scheme: a CSS declaration, a Customizer setting, a block attribute, a
     * Global Styles entry or a plugin setting. Observations sharing that
     * place are one fix; components, pages, viewports and states are listed
     * under it. Website totals use the real-page "apply" renders only; fixes
     * seen only through the preview mechanism are reported separately.
     * ------------------------------------------------------------ */

    var CATEGORY = {
        bypass: { label: 'Confirmed palette bypass', short: 'Bypass', weight: 1 },
        custom: { label: 'Explicit custom color', short: 'Custom', weight: 0.7 },
        unverified: { label: 'Unverified (blue-family value, not a palette color)', short: 'Unverified', weight: 0.5 },
        investigate: { label: 'Needs investigation (source not traced)', short: 'Investigate', weight: 0.4 },
        intentional: { label: 'Intentional fixed color', short: 'Intentional', weight: 0 }
    };
    var CATEGORY_ORDER = ['bypass', 'custom', 'unverified', 'investigate', 'intentional'];
    var ROLE_LABEL = { primary: 'Primary', primary_dark: 'Primary dark', secondary: 'Secondary', accent: 'Accent' };
    var ROLE_TOKEN = { primary: '--primary', primary_dark: '--primary-dark', secondary: '--secondary', accent: '--accent' };
    // Customizer location of the color theme_mods printed by ajnanda_customizer_css() (functions.php).
    var FIELD = {
        header_background_color: ['Header', 'Header Background', '#ffffff'], header_text_color: ['Header', 'Header Text Color', '#1f2937'],
        header_link_hover_color: ['Header', 'Header Link Hover Color', '#2563eb'], header_link_hover_background: ['Header', 'Header Link Hover Background', '#f9fafb'],
        header_submenu_background: ['Header', 'Header Submenu Background', '#ffffff'], header_submenu_text_color: ['Header', 'Header Submenu Text Color', '#1f2937'],
        header_submenu_hover_color: ['Header', 'Header Submenu Hover Text Color', '#2563eb'], header_submenu_hover_background: ['Header', 'Header Submenu Hover Background', '#f9fafb'],
        footer_background_color: ['Footer', 'Footer Background', '#111827'], footer_text_color: ['Footer', 'Footer Text Color', ''],
        footer_link_hover_color: ['Footer', 'Footer Link Hover Color', '#f59e0b'], footer_submenu_hover_color: ['Footer', 'Footer Submenu Hover Text Color', '#2563eb'],
        hero_bg_1: ['Hero Defaults', 'Hero Background Color 1', 'Primary'], hero_bg_2: ['Hero Defaults', 'Hero Background Color 2', 'Secondary'],
        hero_button_text_color: ['Hero Defaults', 'Hero Primary Button Text Color', '#2563eb'], hero_button_bg: ['Hero Defaults', 'Hero Primary Button Background', '#ffffff']
    };

    function colorsIn(v) {
        v = v || '';
        var out = parseColors(v);
        (v.match(/#[0-9a-f]{3,8}\b/gi) || []).forEach(function (h) { var x = hexOf(h); if (x) { out.push({ hex: x, a: h.length === 9 ? parseInt(h.slice(7), 16) / 255 : 1 }); } });
        return out;
    }
    /** Differing baseline role whose exact RGB appears in the value (any alpha). */
    function exactRole(v, ctx) {
        var cs = colorsIn(v);
        for (var i = 0; i < cs.length; i++) { var r = ctx.roles[cs[i].hex]; if (r) { return { role: r.role, alpha: cs[i].a < 1 }; } }
        return null;
    }
    function tintIn(v, ctx) { return colorsIn(v).some(function (c) { return isTint(c.hex, ctx); }); }
    function nearestRole(hex, ctx) {
        if (!/^#[0-9a-f]{6}$/.test(hex || '')) { return 'primary'; }
        var best = 'primary', bd = 1e9, h = hsl(hex);
        ctx.plan.roles.forEach(function (r) {
            if (!r.differs) { return; }
            var x = hsl(r.baseline), dh = Math.abs(x[0] - h[0]);
            dh = Math.min(dh, 360 - dh);
            var d = dh + Math.abs(x[2] - h[2]) * 60;
            if (d < bd) { bd = d; best = r.role; }
        });
        return best;
    }
    /** The value rewritten in palette terms (roles → var(), tints → color-mix()). */
    function suggestValue(v, ctx) {
        return String(v).replace(/rgba?\([^)]*\)|#[0-9a-f]{3,8}\b/gi, function (c) {
            var p = colorsIn(c)[0];
            if (!p) { return c; }
            var r = ctx.roles[p.hex];
            if (r) { return p.a < 1 ? 'color-mix(in srgb, var(' + ROLE_TOKEN[r.role] + ') ' + Math.round(p.a * 100) + '%, transparent)' : 'var(' + ROLE_TOKEN[r.role] + ')'; }
            if (isTint(p.hex, ctx)) {
                var nr = nearestRole(p.hex, ctx), l = hsl(p.hex)[2], bl = hsl(String(ctx.baseline[nr]).toLowerCase())[2], tok = 'var(' + ROLE_TOKEN[nr] + ')';
                var mix = l > bl ? 'color-mix(in srgb, ' + tok + ' ' + Math.max(4, Math.round(100 * (1 - (l - bl) / (1 - bl)))) + '%, white)' : 'color-mix(in srgb, ' + tok + ' ' + Math.max(10, Math.round(100 * l / bl)) + '%, black)';
                if (/100%/.test(mix)) { mix = tok; }
                return p.a < 1 ? 'color-mix(in srgb, ' + tok + ' ' + Math.round(p.a * 100) + '%, transparent)' : mix;
            }
            return c;
        });
    }
    function variants(v) {
        v = String(v || '');
        var toForm = function (lead) {
            return v.replace(/rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)\s*(?:[,/]\s*([\d.]+%?))?\s*\)/g, function (all, r, g, b, a) {
                if (a === undefined || +a === 1) { return toHex(+r, +g, +b); }
                var al = String(+a); if (!lead) { al = al.replace(/^0\./, '.'); }
                return 'rgba(' + r + ',' + g + ',' + b + ',' + al + ')';
            });
        };
        var out = [v, toForm(true), toForm(false)];
        out.push(out[1].replace(/#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3\b/gi, '#$1$2$3'));
        return out.filter(function (x, i) { return x && out.indexOf(x) === i; });
    }
    function propNames(prop) { return SHORTHANDS[prop] || [prop]; }
    function declNeedles(prop, value) {
        var out = [];
        (prop.indexOf('--') === 0 ? [prop] : propNames(prop).concat(prop)).forEach(function (p) { variants(value).forEach(function (v) { out.push(p + ':' + v); }); });
        return out.filter(function (x, i) { return out.indexOf(x) === i; }).slice(0, 12);
    }
    function colorNeedles(value, prop) {
        var out = [];
        colorsIn(value).forEach(function (c) {
            var short = c.hex.replace(/^#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3$/i, '#$1$2$3');
            propNames(prop).forEach(function (p) { out.push(p + ':' + c.hex); if (short !== c.hex) { out.push(p + ':' + short); } });
            out.push(c.hex);
        });
        return out.filter(function (x, i) { return out.indexOf(x) === i; }).slice(0, 12);
    }
    function srcScope(src) {
        if (!src) { return null; }
        if (src.kind === 'file') {
            if (src.owner === 'AJNanda') { return { scope: 'theme', rel: src.file }; }
            if (/^Site mu-plugin/.test(src.owner)) { return { scope: 'mu', rel: src.file.replace(/^wp-content\/mu-plugins\//, '') }; }
            if (/^Plugin: /.test(src.owner)) { return { scope: 'plugin', rel: src.file }; }
            return null;
        }
        if (/customizer_css/.test(src.file || '')) { return { scope: 'theme', rel: 'functions.php' }; }
        if (src.kind === 'inline' && !/Global Styles|Additional CSS|Block attributes/.test(src.owner + ' ' + src.file)) { return { scope: 'code', rel: '' }; }
        return null;
    }
    function locateReq(src, needles, selector) {
        var s = srcScope(src);
        return s ? { scope: s.scope, rel: s.rel, needles: needles, selector: selector && selector.indexOf('style=') < 0 ? selector : '' } : null;
    }
    function ownerLabel(src, elOwner) {
        if (!src) { return elOwner || 'Unknown'; }
        if (src.kind === 'attr') { return elOwner || 'Saved content'; }
        if (src.owner === 'AJNanda' || src.owner === 'AJNanda (inline)') { return 'AJNanda theme'; }
        if (src.owner === 'Unknown inline') { return elOwner || 'Unknown inline style'; }
        return src.owner;
    }
    function whereLabel(src) {
        if (!src) { return ''; }
        if (src.kind === 'file') { return src.owner === 'AJNanda' ? 'themes/ajnanda/' + src.file : src.label; }
        if (src.kind === 'inline' && /^inline/.test(src.file)) { return src.label; }
        return src.file;
    }
    function ownerFromPath(p) {
        var m;
        if ((m = /^themes\/([^/]+)/.exec(p))) { return m[1] === 'ajnanda' ? 'AJNanda theme' : 'Theme: ' + m[1]; }
        if ((m = /^mu-plugins\/([^/]+)/.exec(p))) { return 'Site mu-plugin: ' + m[1].replace(/\.php$/, ''); }
        if ((m = /^plugins\/([^/]+)/.exec(p))) { return 'Plugin: ' + m[1]; }
        return '';
    }
    function roleFor(f, v, ctx) {
        var ex = exactRole(v, ctx);
        if (ex) { return { role: ex.role, exact: true }; }
        return { role: f.role || nearestRole(hexOf(v) || f.observedBase, ctx), exact: !!f.role && !tintIn(v, ctx) };
    }

    function fixOf(f, ctx) {
        var d = f.decl, src = d ? d.source : null, r;
        if (f.isLogo) {
            return { key: 'intentional|' + f.element, category: 'intentional', type: 'artwork', sub: 'artwork', owner: 'Artwork', where: f.region + ' · ' + f.element, field: f.property, current: f.observedBase, role: '', exact: false, suggestion: 'None. Logo / artwork keeps its own colors.' };
        }
        if (!d) {
            return { key: 'investigate|' + f.region + '|' + f.element + '|' + f.property, category: 'investigate', type: 'unknown', sub: 'unknown', owner: f.owner || 'Unknown', where: 'Not traced', field: f.element + ' · ' + f.property, current: f.observedBase, role: f.role || nearestRole(f.observedBase, ctx), exact: !!f.role,
                suggestion: 'No author rule found (user-agent default, nested CSS, cross-origin sheet or script-set style). Inspect the element in DevTools.' };
        }
        var stuck = f.observedComp, brk = null, lit = null, unresolved = null;
        (f.chain || []).forEach(function (l) {
            if (l.unresolved) { if (!unresolved) { unresolved = l; } return; }
            var role = CORE_TOKENS[l.token];
            if (role) {
                var pr = ctx.plan.roles.filter(function (q) { return q.role === role; })[0], got = hexOf(l.computed);
                // A palette token that resolves to the wrong value below :root is redeclared by a component/site layer.
                if (!brk && pr && pr.differs && got && got !== pr.comparison && l.source && !l.atRoot) { brk = l; }
                return;
            }
            if (!lit && l.value && l.value.indexOf('var(') < 0 && colorsIn(l.computed || l.value).some(function (c) { return c.hex === stuck; })) { lit = l; }
        });
        if (brk) {
            return { key: 'css|' + brk.source.label + '|' + brk.selector + '|' + brk.token, category: 'bypass', type: 'css', sub: 'palette', owner: ownerLabel(brk.source, f.owner), where: whereLabel(brk.source), selector: brk.selector, field: brk.token, current: brk.value, role: CORE_TOKENS[brk.token], exact: true,
                suggestion: 'Delete `' + brk.token + ': ' + brk.value + '` from `' + brk.selector + '` so ' + brk.token + ' comes from the active scheme.',
                locate: locateReq(brk.source, declNeedles(brk.token, brk.value), brk.selector) };
        }
        if (lit) {
            var mod = TOKEN_MODS[lit.token], fromMods = lit.source && /customizer_css/.test(lit.source.file || '');
            r = roleFor(f, lit.value, ctx);
            if (mod && fromMods && ctx.mods[mod] !== undefined) {
                var fld = FIELD[mod] || ['', mod, ''], saved = ctx.mods[mod], cat = exactRole(saved, ctx) ? 'bypass' : 'custom';
                return { key: 'mod|' + mod, category: cat, type: 'setting', sub: 'customizer', owner: 'Saved Customizer setting', where: 'Appearance → Customize' + (fld[0] ? ' → ' + fld[0] : ''), field: fld[1] + ' (theme_mod ' + mod + ')', current: saved, role: r.role, exact: r.exact,
                    suggestion: cat === 'bypass' ? 'Reset “' + fld[1] + '” to default so it follows ' + ROLE_LABEL[r.role] + '.' : 'Saved custom color. Reset “' + fld[1] + '” to default' + (fld[2] ? ' (' + fld[2] + ')' : '') + ', or keep the tint by deriving it in CSS as ' + suggestValue(saved, ctx) + '.' };
            }
            if (mod && fromMods) {
                return { key: 'php|' + lit.token, category: r.exact ? 'bypass' : 'unverified', type: 'php', sub: 'php', owner: 'AJNanda theme', where: 'themes/ajnanda/functions.php ajnanda_customizer_css()', field: lit.token + ' ← theme_mod ' + mod + ' (unsaved; PHP default)', current: lit.value, role: r.role, exact: r.exact,
                    suggestion: 'When ' + mod + ' is unset, print ' + suggestValue(lit.value, ctx) + ' instead of the literal default.', locate: { scope: 'theme', rel: 'functions.php', needles: ["get_theme_mod('" + mod + "'"], selector: '' } };
            }
            if (lit.source && /global-styles/.test(lit.source.label || '')) {
                return { key: 'gs|' + lit.token, category: r.exact ? 'bypass' : 'custom', type: 'global-styles', sub: 'gs', owner: 'Global Styles / theme.json', where: 'Appearance → Editor → Styles → Colors', field: lit.token, current: lit.value, role: r.role, exact: r.exact,
                    suggestion: 'Preset synced from the brand theme_mods by ajnanda_sync_brand_palette(). If it is saved in Global Styles, reset it there so it follows ' + ROLE_LABEL[r.role] + '.' };
            }
            if (lit.source && lit.source.kind === 'attr') {
                var plug = f.owner || 'Plugin markup', forms = lit.token === '--ajforms-primary';
                return { key: 'attr-token|' + plug + '|' + lit.token + '|' + hexOf(lit.value), category: r.exact ? 'bypass' : 'custom', type: 'plugin-setting', sub: 'plugin-setting', owner: forms ? 'Plugin setting: AJForms (ajcore)' : plug, where: forms ? 'AJForms → form → Design → Primary color' : 'style attribute printed by ' + plug, field: lit.token + (forms ? ' (form setting primary_color)' : ''), current: lit.value, role: r.role, exact: r.exact,
                    suggestion: forms ? 'Set the form Primary color to the palette, or have AJForms print var(' + ROLE_TOKEN[r.role] + ') when the form keeps its default.' : 'Print var(' + ROLE_TOKEN[r.role] + ') instead of ' + lit.value + '.',
                    locate: { scope: 'code', rel: '', needles: forms ? ["'primary_color'=>"] : declNeedles(lit.token, lit.value), selector: '' } };
            }
            return { key: 'css|' + (lit.source ? lit.source.label : '?') + '|' + lit.selector + '|' + lit.token, category: r.exact ? 'bypass' : (tintIn(lit.value, ctx) ? 'unverified' : 'custom'), type: 'css', sub: 'token', owner: ownerLabel(lit.source, f.owner), where: whereLabel(lit.source), selector: lit.selector, field: lit.token, current: lit.value, role: r.role, exact: r.exact,
                suggestion: 'Change `' + lit.token + ': ' + lit.value + '` to `' + lit.token + ': ' + suggestValue(lit.value, ctx) + '`.', locate: locateReq(lit.source, declNeedles(lit.token, lit.value), lit.selector) };
        }
        var prop = d.prop || f.property;
        r = roleFor(f, d.value, ctx);
        if (src.kind === 'attr') {
            var m = /^([\w-]+):(\d+)$/.exec(f.object || ''), plugOwner = /Plugin/.test(f.owner || '') ? f.owner : '';
            if (f.kind === 'demo') {
                return { key: 'attr|demo|' + prop + '|' + (hexOf(d.value) || d.value), category: r.exact ? 'bypass' : 'custom', type: 'block', sub: 'pattern', owner: 'AJNanda theme (demo pattern)', where: 'themes/ajnanda/' + ((ctx.plan.targets[0] && ctx.plan.targets[0].source) || 'patterns'), field: f.element + ' style ' + prop, current: d.value, role: r.role, exact: r.exact,
                    suggestion: 'Replace the inline literal in the pattern with var(' + ROLE_TOKEN[r.role] + ').' };
            }
            return { key: 'attr|' + (plugOwner ? plugOwner + '|' + (f.elClass || f.element) : (f.object || f.target)) + '|' + prop + '|' + (hexOf(d.value) || d.value), category: r.exact ? 'bypass' : (plugOwner && tintIn(d.value, ctx) ? 'unverified' : 'custom'), type: plugOwner ? 'markup' : 'block', sub: plugOwner ? 'markup' : 'block',
                owner: plugOwner || 'Saved content', where: plugOwner ? plugOwner + ' template markup' : f.label, field: (f.elClass ? '.' + f.elClass : f.element) + ' style ' + prop, current: d.value, role: r.role, exact: r.exact,
                suggestion: plugOwner ? 'Replace the inline ' + prop + ' literal in the template with var(' + ROLE_TOKEN[r.role] + ') or a plugin color setting.' : 'Block → Styles → Color: replace custom ' + (hexOf(d.value) || d.value) + ' with the ' + ROLE_LABEL[r.role] + ' palette color (or clear it).',
                locate: { scope: m && !plugOwner ? 'post' : 'code', post_id: m ? +m[2] : 0, rel: '', needles: colorNeedles(d.value, prop), selector: f.elClass || '' } };
        }
        if (d.value.indexOf('var(') < 0 || colorsIn(d.value).some(function (c) { return c.hex === stuck; })) {
            return { key: 'css|' + src.label + '|' + d.selector + '|' + prop, category: r.exact ? 'bypass' : (tintIn(d.value, ctx) ? 'unverified' : 'custom'), type: 'css', sub: 'literal', owner: ownerLabel(src, f.owner), where: whereLabel(src), selector: d.selector, field: prop, current: d.value, role: r.role, exact: r.exact,
                suggestion: 'Change `' + prop + ': ' + d.value + '` to `' + prop + ': ' + suggestValue(d.value, ctx) + '`.', locate: locateReq(src, declNeedles(prop, d.value), d.selector) };
        }
        return { key: 'css|' + src.label + '|' + d.selector + '|' + prop, category: unresolved ? 'unverified' : 'investigate', type: 'css', sub: 'chain', owner: ownerLabel(src, f.owner), where: whereLabel(src), selector: d.selector, field: prop, current: d.value, role: r.role, exact: r.exact,
            suggestion: unresolved ? unresolved.token + ' is undefined here, so its fallback ' + (unresolved.fallback || '(none)') + ' renders. Define it from var(' + ROLE_TOKEN[r.role] + ') or use ' + ROLE_TOKEN[r.role] + ' directly.' : 'The chain resolves through palette tokens but the color did not change. Check the token chain below.',
            locate: locateReq(src, declNeedles(prop, d.value), d.selector) };
    }

    function buildModel(plan, ctx, results) {
        var findings = [], working = [];
        results.forEach(function (r) { findings = findings.concat(r.findings); working = working.concat(r.working); });
        var byKey = {}, fixes = [];
        findings.forEach(function (f, i) {
            f.n = i + 1;
            var c = classify(f, ctx);
            f.cause = c.cause; f.ownerResolved = c.owner; f.editable = c.editable; f.fix = c.fix;
            var x = fixOf(f, ctx), fx = byKey[x.key];
            if (!fx) { fx = byKey[x.key] = x; x.obs = []; x.chain = f.chain; x.decl = f.decl; x.inheritedFrom = f.inheritedFrom; fixes.push(x); }
            fx.obs.push(f);
        });
        return { plan: plan, ctx: ctx, results: results, findings: findings, working: working, fixes: fixes };
    }

    async function locate(model) {
        var reqs = [];
        model.fixes.forEach(function (fx, i) { if (fx.locate) { fx.lid = 'f' + i; reqs.push(Object.assign({ id: fx.lid }, fx.locate)); } });
        if (!reqs.length) { return; }
        try {
            var body = new FormData();
            body.append('action', 'ajnanda_color_validator_locate');
            body.append('nonce', cfg.nonce);
            body.append('items', JSON.stringify(reqs));
            var json = await (await fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })).json();
            if (!json || !json.success) { model.locateError = 'lookup failed'; return; }
            var map = json.data || {};
            model.fixes.forEach(function (fx) {
                var hit = fx.lid ? map[fx.lid] : null;
                if (!fx.lid) { return; }
                if (!hit) {
                    fx.unlocated = true;
                    if (fx.type === 'block') { fx.category = 'investigate'; fx.where += ' (literal not found in saved content or code)'; }
                    return;
                }
                if (hit.post) {
                    fx.type = 'block'; fx.sub = 'block'; fx.owner = 'Saved content';
                    fx.where = 'Block editor → ' + hit.type + ' “' + hit.title + '” (#' + hit.post + ')';
                    fx.edit = hit.edit;
                    return;
                }
                fx.file = hit.file + ':' + hit.line; fx.fileStrong = !!hit.strong;
                if (fx.type === 'plugin-setting') { fx.ref = fx.file; return; }
                if (fx.type === 'block' || fx.type === 'markup') {
                    fx.type = 'markup'; fx.sub = 'markup'; fx.owner = ownerFromPath(hit.file) || fx.owner;
                    if (fx.category === 'custom' && tintIn(fx.current, model.ctx)) { fx.category = 'unverified'; }
                    fx.suggestion = 'Template markup, not saved content: replace the inline literal with ' + suggestValue(fx.current, model.ctx) + ' (or a plugin color setting).';
                }
                fx.where = fx.file;
                if (/Unknown/.test(fx.owner || '') || /^Plugin markup/.test(fx.owner || '')) { fx.owner = ownerFromPath(hit.file) || fx.owner; }
            });
        } catch (e) {
            model.locateError = e && e.message ? e.message : String(e);
        }
    }

    function finishModel(model) {
        // Distinct rendered rules can point at the same source line: that is one fix.
        var seen = {}, merged = [];
        model.fixes.forEach(function (fx) {
            var k = fx.file && (fx.type === 'markup' || fx.type === 'css') && fx.fileStrong !== false ? fx.type + '|' + fx.file + '|' + fx.field + '|' + (hexOf(fx.current) || fx.current) : '';
            if (k && seen[k]) { seen[k].obs = seen[k].obs.concat(fx.obs); return; }
            if (k) { seen[k] = fx; }
            merged.push(fx);
        });
        merged.forEach(fixStats);
        var site = merged.filter(function (fx) { return fx.siteCount > 0 && fx.category !== 'intentional'; });
        var preview = merged.filter(function (fx) { return fx.siteCount === 0 && fx.category !== 'intentional'; });
        var intentional = merged.filter(function (fx) { return fx.category === 'intentional'; });

        // Batches: fixes edited in the same place (file + selector group, or Customizer section).
        var batches = {}, list = [];
        site.forEach(function (fx) {
            var place = fx.type === 'setting' ? fx.where : fx.type === 'block' ? fx.where : (fx.file || fx.where).replace(/:\d+$/, '') + (fx.sub === 'palette' || fx.sub === 'token' ? ' ' + (fx.selector || '') : '');
            var k = fx.category + '|' + fx.sub + '|' + place;
            var b = batches[k] || (batches[k] = { key: k, place: place, category: fx.category, sub: fx.sub, owner: fx.owner, fixes: [], pages: {}, comps: {}, count: 0 });
            if (!b.fixes.length) { list.push(b); }
            b.fixes.push(fx);
            Object.keys(fx.pages).forEach(function (p) { b.pages[p] = fx.pages[p]; });
            fx.components.forEach(function (c) { b.comps[c.name + '|' + c.region] = 1; });
            b.count += fx.siteCount;
        });
        list.forEach(function (b) {
            b.pageCount = Object.keys(b.pages).length;
            b.compCount = Object.keys(b.comps).length;
            b.impact = Math.round((b.pageCount * 10 + b.compCount * 4 + Math.min(b.count, 600) / 5) * CATEGORY[b.category].weight);
            b.fixes.sort(function (a, c) { return c.impact - a.impact; });
        });
        list.sort(function (a, b) { return b.impact - a.impact; });
        var n = 0;
        list.forEach(function (b, i) { b.n = i + 1; b.fixes.forEach(function (fx) { fx.id = 'F' + (++n); fx.batch = b.n; }); });
        site.sort(function (a, b) { return +a.id.slice(1) - +b.id.slice(1); });
        preview.sort(function (a, b) { return b.impact - a.impact; });
        preview.forEach(function (fx, i) { fx.id = 'P' + (i + 1); });
        intentional.forEach(function (fx, i) { fx.id = 'I' + (i + 1); });
        merged.forEach(function (fx) { fx.obs.forEach(function (o) { o.fixId = fx.id; }); });

        var comps = {}, obs = 0, byCat = {};
        site.forEach(function (fx) {
            fx.components.forEach(function (c) { comps[c.name + '|' + c.region] = 1; });
            obs += fx.siteCount;
            byCat[fx.category] = (byCat[fx.category] || 0) + 1;
        });
        model.site = site; model.preview = preview; model.intentional = intentional; model.batches = list;
        model.summary = { uniqueFixes: site.length, components: Object.keys(comps).length, observations: obs, byCategory: byCat, previewFixes: preview.length, intentional: intentional.length };
        model.candidates = sourceCandidates(model);
    }

    function fixStats(fx) {
        var siteObs = fx.obs.filter(function (o) { return o.resolver === 'apply'; });
        var use = siteObs.length ? siteObs : fx.obs, comps = {}, pages = {};
        use.forEach(function (o) {
            var ck = o.component + '|' + o.region;
            var c = comps[ck] || (comps[ck] = { name: o.component, region: o.region, pages: {}, viewports: {}, states: {}, props: {}, elements: {}, count: 0 });
            c.pages[o.label] = o.url; c.viewports[o.viewport] = 1; c.states[o.state || 'default'] = 1; c.props[o.property] = 1; c.elements[o.element] = 1; c.count++;
            pages[o.label] = o.url;
        });
        fx.siteCount = siteObs.length;
        fx.previewCount = fx.obs.length - siteObs.length;
        fx.components = Object.keys(comps).map(function (k) { return comps[k]; }).sort(function (a, b) { return b.count - a.count; });
        fx.pages = pages;
        fx.pageCount = Object.keys(pages).length;
        fx.count = use.length;
        fx.impact = Math.round((fx.pageCount * 10 + fx.components.length * 4 + Math.min(fx.count, 600) / 5) * CATEGORY[fx.category].weight);
    }

    /** Source-scan literals with no rendered fix: candidates only, never in totals. */
    function sourceCandidates(model) {
        var files = {}, sels = {}, mods = {}, posts = {};
        model.site.concat(model.preview).forEach(function (fx) {
            if (fx.file) { files[fx.file.replace(/^themes\/ajnanda\//, '')] = 1; }
            if (fx.selector) { sels[(fx.where || '').replace(/^themes\/ajnanda\//, '').replace(/:\d+$/, '') + '|' + fx.selector.replace(/\s+/g, '')] = 1; }
            if (/^mod\|/.test(fx.key)) { mods[fx.key.slice(4)] = 1; }
            if (fx.edit) { var m = /post=(\d+)/.exec(fx.edit); if (m) { posts[m[1]] = 1; } }
        });
        return (model.plan.source.items || []).filter(function (it) {
            if (it.class === 'fallback') { return false; }
            if (it.type === 'theme_file' && (files[it.ref + ':' + it.line] || sels[it.ref + '|' + (it.selector || '').replace(/\s+/g, '')])) { return false; }
            if (it.type === 'theme_mod' && mods[it.ref.replace('theme_mod ', '')]) { return false; }
            if (it.type === 'content' && posts[it.post_id]) { return false; }
            return true;
        });
    }

    /* ------------------------------------------------------------ *
     * Report
     * ------------------------------------------------------------ */

    function cat(c) { return '<span class="cv-cat cv-cat-' + c + '" title="' + esc(CATEGORY[c].label) + '">' + esc(CATEGORY[c].short) + '</span>'; }
    function md(s) { return esc(s).replace(/`([^`]+)`/g, '<code>$1</code>'); }
    function roleCell(fx, ctx) {
        if (!fx.role) { return '—'; }
        var r = ctx.plan.roles.filter(function (q) { return q.role === fx.role; })[0];
        return esc(ROLE_LABEL[fx.role] + (fx.exact ? '' : ' (tint)')) + (r ? '<br><small>' + sw(r.baseline) + ' → ' + sw(r.comparison) + '</small>' : '');
    }
    function whereCell(fx) {
        var h = fx.edit ? '<a href="' + esc(fx.edit) + '" target="_blank" rel="noopener">' + esc(fx.where) + '</a>' : esc(fx.where || '—');
        if (fx.file && fx.file !== fx.where) { h += '<br><small>' + esc(fx.file) + '</small>'; }
        if (fx.selector) { h += '<br><code>' + esc(fx.selector) + '</code>'; }
        if (fx.field) { h += '<br><small>' + esc(fx.field) + '</small>'; }
        if (fx.unlocated && fx.type !== 'block') { h += '<br><small class="cv-warn">line not found (selector serialized differently)</small>'; }
        return h;
    }
    function compsCell(fx) {
        var names = fx.components.map(function (c) { return c.name; }).filter(function (x, i, a) { return a.indexOf(x) === i; });
        return esc(names.slice(0, 3).join(', ')) + (names.length > 3 ? ' <small>+' + (names.length - 3) + '</small>' : '');
    }
    function drill(fx) {
        var h = '<details class="cv-drill"><summary>' + fx.components.length + ' component(s) · ' + fx.pageCount + ' page(s) · ' + fx.count + ' observation(s)' + (fx.previewCount && fx.siteCount ? ' · +' + fx.previewCount + ' in preview' : '') + '</summary>';
        h += '<table class="cv-table cv-mini"><thead><tr><th>Component</th><th>Region</th><th>Pages</th><th>Viewports</th><th>States</th><th>Properties</th><th>Obs.</th></tr></thead><tbody>';
        fx.components.forEach(function (c) {
            var pages = Object.keys(c.pages);
            h += '<tr><td>' + esc(c.name) + '<br><small><code>' + esc(Object.keys(c.elements).slice(0, 2).join(' · ')) + '</code></small></td><td>' + esc(c.region) + '</td><td><details><summary>' + pages.length + '</summary>' + pages.map(function (p) { return '<a href="' + esc(c.pages[p]) + '" target="_blank" rel="noopener">' + esc(p) + '</a>'; }).join('<br>') + '</details></td><td>' + esc(Object.keys(c.viewports).join(', ')) + '</td><td>' + esc(Object.keys(c.states).join(', ')) + '</td><td>' + esc(Object.keys(c.props).join(', ')) + '</td><td>' + c.count + '</td></tr>';
        });
        h += '</tbody></table>';
        if (fx.decl) { h += '<p class="cv-meta">Example winning declaration: <code>' + esc(fx.decl.selector + ' { ' + fx.decl.value + (fx.decl.important ? ' !important' : '') + ' }') + '</code> [' + esc(fx.decl.source.label) + ']' + (fx.inheritedFrom ? ' · ' + esc(fx.inheritedFrom) : '') + '</p>'; }
        if (fx.chain && fx.chain.length) { h += '<p class="cv-meta">Token chain: <code>' + esc(chainText(fx.chain)) + '</code></p>'; }
        return h + '</details>';
    }
    function fixTable(list, ctx, opts) {
        opts = opts || {};
        var h = '<div class="cv-scroll"><table class="widefat striped cv-table cv-fixtable"><thead><tr><th>#</th><th>Class</th><th>Component</th><th>Owner</th><th>Where to edit</th><th>Current</th><th>Intended role</th><th>Suggested change</th><th>Impact</th></tr></thead><tbody>';
        list.forEach(function (fx) {
            h += '<tr><td><strong>' + esc(fx.id) + '</strong></td><td>' + cat(fx.category) + '</td><td>' + compsCell(fx) + '</td><td>' + esc(fx.owner || '—') + '</td><td>' + whereCell(fx) + '</td><td>' + sw(hexOf(fx.current) || '') + (hexOf(fx.current) && fx.current.toLowerCase() === hexOf(fx.current) ? '' : '<br><small><code>' + esc(fx.current) + '</code></small>') + '</td><td>' + roleCell(fx, ctx) + '</td><td>' + md(fx.suggestion) + '</td><td>' + fx.pageCount + ' pg · ' + fx.components.length + ' comp · ' + fx.count + ' obs</td></tr>';
            if (!opts.noDrill) { h += '<tr class="cv-drillrow"><td></td><td colspan="8">' + drill(fx) + '</td></tr>'; }
        });
        return h + '</tbody></table></div>';
    }

    function renderReport(model) {
        var plan = model.plan, ctx = model.ctx, results = model.results, findings = model.findings, sum = model.summary;
        var B = plan.baseline, C = plan.comparison, html = '';
        var apply = results.filter(function (r) { return r.target.resolver === 'apply'; });
        var tot = { follows: 0, bypassed: 0 };
        apply.forEach(function (r) { tot.follows += r.follows; tot.bypassed += r.bypassed; });
        var same = plan.roles.filter(function (r) { return !r.differs; });
        var adminSkipped = 0; results.forEach(function (r) { adminSkipped += r.adminSkipped || 0; });

        html += '<div class="cv-report">';
        html += '<h2>' + esc(B.label) + ' vs ' + esc(C.label) + ' — cleanup list</h2>';
        html += '<p class="cv-meta">' + esc(plan.site) + ' · AJNanda ' + esc(plan.theme) + ' · ' + esc(new Date().toLocaleString()) + ' · active scheme <strong>' + esc(plan.active.slug) + '</strong> (unchanged; read-only run)</p>';
        html += '<div class="cv-kpis"><div><b>' + sum.uniqueFixes + '</b>unique fixes</div><div><b>' + sum.components + '</b>affected components</div><div><b>' + sum.observations + '</b>repeated observations</div></div>';
        html += '<p class="cv-meta">Website only: ' + apply.length + ' real-page renders with ' + esc(C.label) + ' applied for the request; ' + tot.bypassed + ' of ' + (tot.follows + tot.bypassed) + ' brand-colored values stayed ' + esc(B.label) + '. ' +
            CATEGORY_ORDER.filter(function (c) { return sum.byCategory[c]; }).map(function (c) { return cat(c) + ' ' + sum.byCategory[c]; }).join(' ') +
            '. Passing (same in both palettes): ' + (same.length ? esc(same.map(function (r) { return r.token + ' ' + r.baseline; }).join(', ')) : 'none') +
            '. Not counted: admin chrome' + (adminSkipped ? ' (' + adminSkipped + ' elements)' : '') + ', ' + sum.intentional + ' intentional, ' + sum.previewFixes + ' preview-only.' + (model.locateError ? ' <span class="cv-warn">File lookup failed: ' + esc(model.locateError) + '</span>' : '') + '</p>';

        // Start here
        html += '<h3>Start here</h3><ol class="cv-batches">';
        model.batches.slice(0, 6).forEach(function (b) {
            html += '<li><strong>' + esc(b.place) + '</strong> ' + cat(b.category) + ' <span class="cv-meta">' + esc(b.owner) + ' · ' + b.fixes.length + ' fix' + (b.fixes.length > 1 ? 'es' : '') + ' (' + esc(b.fixes.map(function (f) { return f.id; }).join(', ')) + ') · ' + b.pageCount + ' pages · ' + b.compCount + ' components · ' + b.count + ' observations</span>';
            html += '<ul class="cv-list">' + b.fixes.map(function (fx) { return '<li><strong>' + esc(fx.id) + '</strong> ' + esc(fx.field || '') + (fx.current ? ' = ' + sw(hexOf(fx.current) || '') + (hexOf(fx.current) === String(fx.current).toLowerCase() ? '' : ' <code>' + esc(fx.current) + '</code>') : '') + ' → ' + md(fx.suggestion) + '</li>'; }).join('') + '</ul></li>';
        });
        html += '</ol>';

        // All website fixes
        html += '<h3>All website fixes (' + model.site.length + ')</h3>';
        html += '<p class="cv-meta">' + CATEGORY_ORDER.slice(0, 4).map(function (c) { return cat(c) + ' ' + esc(CATEGORY[c].label); }).join(' · ') + '. Ordered by batch impact; the drill-down lists every component, page, viewport and state.</p>';
        html += fixTable(model.site, ctx);

        // Preview & navigation
        html += '<h3>Preview &amp; navigation (not in website totals)</h3>';
        var byResolver = {};
        results.forEach(function (r) {
            var k = r.target.resolver, x = byResolver[k] || (byResolver[k] = { follows: 0, bypassed: 0, renders: 0 });
            x.follows += r.follows; x.bypassed += r.bypassed; x.renders++;
        });
        ['preview', 'class'].forEach(function (k) {
            var x = byResolver[k];
            if (!x) { return; }
            var name = k === 'preview' ? 'Existing preview (Corporate Home demo)' : 'Real homepage with the preview mechanism (body class only)';
            html += '<p><strong>' + esc(name) + ':</strong> ' + x.bypassed + ' of ' + (x.follows + x.bypassed) + ' brand-colored values stayed ' + esc(B.label) + '; ' + x.follows + ' followed ' + esc(C.label) + '.</p>';
        });
        var demo = results.filter(function (r) { return r.target.kind === 'demo' && r.viewport === 'desktop'; })[0];
        if (demo && demo.loaded) {
            html += demo.loaded.reached
                ? '<p>The preview loads ' + esc(C.label) + ' (body.ajnanda-scheme-' + esc(C.slug) + ' present); &lt;body&gt; resolves --primary to ' + sw(demo.loaded.body) + (demo.override ? ' because <code>' + esc(demo.override.selector + ' { --primary: ' + demo.override.value + ' }') + '</code> [' + esc(demo.override.source.label) + '] wins' : '') + '. Same root cause as the first website batch.</p>'
                : '<p class="cv-warn">The preview did not load ' + esc(C.label) + '.</p>';
        }
        if (demo && demo.navigation) {
            var nv = demo.navigation;
            html += '<p><strong>Preview navigation:</strong> ' + nv.leaves + ' of ' + nv.internal + ' internal site links in the demo leave the preview and drop the scheme' + (nv.sample.length ? ' (e.g. ' + esc(nv.sample.join(', ')) + ')' : '') + '. ' + (nv.admin || 0) + ' admin/toolbar links excluded.</p>';
        }
        html += '<p class="cv-meta">Resolvers: ' + esc(plan.resolvers.preview) + ' ' + esc(plan.resolvers.apply) + '</p>';
        if (model.preview.length) {
            html += '<details><summary>Preview-only differences (' + model.preview.length + ' fixes, ' + model.preview.reduce(function (a, f) { return a + f.count; }, 0) + ' observations)</summary>' + fixTable(model.preview, ctx) + '</details>';
        }
        if (model.intentional.length) {
            html += '<details><summary>Intentional fixed colors (' + model.intentional.length + ')</summary>' + fixTable(model.intentional, ctx, { noDrill: true }) + '</details>';
        }

        // Source-scan candidates
        var cands = model.candidates, s = plan.source, counts = s.counts || {};
        html += '<h3>Source-scan candidates (' + cands.length + ')</h3><p class="cv-meta">Literals in theme files and saved settings/content that no rendered fix accounts for — unconfirmed, not in totals. ' + s.files + ' theme files scanned; literal counts: ' + Object.keys(counts).map(function (k) { return esc(k) + ' ' + counts[k]; }).join(' · ') + '.</p>';
        html += '<details><summary>Show candidates</summary><div class="cv-scroll"><table class="widefat striped cv-table"><thead><tr><th>Class</th><th>Location</th><th>Selector / context</th><th>Literal</th><th>Role</th></tr></thead><tbody>';
        cands.slice(0, 250).forEach(function (it) {
            html += '<tr><td>' + esc(it.class) + '</td><td>' + esc(it.ref) + (it.line ? ':' + it.line : '') + '</td><td><small>' + esc(it.selector ? it.selector + ' → ' : '') + esc(it.snippet) + '</small></td><td>' + sw(it.hex) + (it.literal.toLowerCase() !== it.hex ? ' <small>(' + esc(it.literal) + ')</small>' : '') + '</td><td>' + esc(it.role || '—') + '</td></tr>';
        });
        html += '</tbody></table></div>' + (cands.length > 250 ? '<p class="cv-meta">First 250 of ' + cands.length + ' shown; all are in the report data.</p>' : '') + '</details>';

        // Coverage
        html += '<details><summary>Coverage (' + results.length + ' renders)</summary><table class="widefat striped cv-table"><thead><tr><th>View</th><th>Group</th><th>Resolver</th><th>Viewport</th><th>Status</th><th>Followed</th><th>Stayed</th><th>Notes</th></tr></thead><tbody>';
        results.forEach(function (r) {
            html += '<tr><td><a href="' + esc(r.target.url) + '" target="_blank" rel="noopener">' + esc(r.target.label) + '</a></td><td>' + esc(r.target.group || 'Demo') + '</td><td>' + esc(r.target.resolver) + '</td><td>' + esc(r.viewport) + '</td><td><span class="cv-st cv-st-' + esc(r.status) + '">' + esc(r.status) + '</span></td><td>' + r.follows + '</td><td>' + r.bypassed + '</td><td>' + esc(r.notes.concat(r.unverifiable.slice(0, 3)).join(' · ')) + (r.unverifiable.length > 3 ? ' (+' + (r.unverifiable.length - 3) + ' more unverifiable)' : '') + '</td></tr>';
        });
        html += '</tbody></table><p><strong>Not verified:</strong></p><ul class="cv-list">' + plan.gaps.map(function (g) { return '<li><strong>' + esc(g.view) + '</strong> — ' + esc(g.reason) + '</li>'; }).join('') + '</ul></details>';

        // Raw findings
        html += '<details><summary>Raw findings (' + findings.length + ' observations)</summary><p class="cv-meta">Expected/observed as ' + esc(B.label) + ' | ' + esc(C.label) + '. Fix column links each observation to its fix.</p>';
        html += '<div class="cv-scroll"><table class="widefat striped cv-table cv-findings"><thead><tr><th>Fix</th><th>View</th><th>Element</th><th>Property</th><th>Expected</th><th>Observed</th><th>Winning declaration</th><th>Token chain</th><th>Cause</th></tr></thead><tbody>';
        findings.slice(0, 500).forEach(function (f) {
            html += '<tr><td>' + esc(f.fixId || '') + '</td><td>' + esc(f.label) + '<br><small>' + esc(f.viewport) + ' · ' + esc(f.resolver) + '</small></td><td><code>' + esc(f.element) + '</code><br><small>' + esc(f.component) + ' · ' + esc(f.region) + (f.text ? ' · “' + esc(f.text) + '”' : '') + '</small></td><td>' + esc((f.state ? f.state + ':' : '') + f.property) + (f.stop ? ' [stop ' + (f.stop + 1) + ']' : '') + '</td><td>' + sw(f.expectedBase) + '<br>' + sw(f.expectedComp) + '</td><td>' + sw(f.observedBase) + '<br>' + sw(f.observedComp) + '</td><td>' + (f.decl ? '<code>' + esc(f.decl.selector) + '</code><br><small>' + esc(f.decl.value) + ' — ' + esc(f.decl.source.label) + '</small>' : '—') + (f.inheritedFrom ? '<br><small>' + esc(f.inheritedFrom) + '</small>' : '') + '</td><td><small>' + esc(chainText(f.chain)) + '</small></td><td><small>' + esc(CAUSE_LABEL[f.cause] || f.cause) + '</small></td></tr>';
        });
        html += '</tbody></table></div>' + (findings.length > 500 ? '<p class="cv-meta">First 500 shown; all ' + findings.length + ' are in the downloaded report data.</p>' : '') + '</details>';
        html += '</div>';

        var plain = function (fx) {
            var o = {};
            Object.keys(fx).forEach(function (k) { if (k !== 'obs' && k !== 'locate') { o[k] = fx[k]; } });
            o.observations = fx.obs.map(function (x) { return x.n; });
            return o;
        };
        var data = {
            plan: { generated: plan.generated, site: plan.site, theme: plan.theme, baseline: B, comparison: C, active: plan.active, roles: plan.roles, gaps: plan.gaps, resolvers: plan.resolvers },
            summary: sum,
            batches: model.batches.map(function (b) { return { n: b.n, place: b.place, category: b.category, owner: b.owner, fixes: b.fixes.map(function (f) { return f.id; }), pages: b.pageCount, components: b.compCount, observations: b.count, impact: b.impact }; }),
            fixes: model.site.map(plain),
            previewFixes: model.preview.map(plain),
            intentional: model.intentional.map(plain),
            candidates: model.candidates,
            coverage: results.map(function (r) { return { override: r.override || null, id: r.target.id, label: r.target.label, url: r.target.url, group: r.target.group || 'Demo', resolver: r.target.resolver, viewport: r.viewport, status: r.status, follows: r.follows, bypassed: r.bypassed, adminSkipped: r.adminSkipped || 0, notes: r.notes, unverifiable: r.unverifiable, loaded: r.loaded || null, navigation: r.navigation || null, baselineTokens: r.baselineTokens || null, comparisonTokens: r.comparisonTokens || null }; }),
            findings: findings.map(function (f) { var o = Object.assign({}, f); delete o.key; return o; }),
            working: model.working,
            source: plan.source
        };
        return { html: html, data: data, baseline: B, comparison: C };
    }

    var REPORT_CSS = '.cv-report{font:13px/1.45 -apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;color:#1f2937}.cv-report h2{margin:.2em 0}.cv-report h3{margin:1.4em 0 .4em}.cv-meta{color:#6b7280}.cv-verdict p{margin:.35em 0}.cv-table{border-collapse:collapse;width:100%}.cv-table th,.cv-table td{text-align:left;vertical-align:top;padding:4px 6px;border-bottom:1px solid #e5e7eb}.cv-table code,.cv-fixes code{font-size:11px;word-break:break-word}.cv-sw{display:inline-block;width:11px;height:11px;border-radius:2px;margin-right:4px;vertical-align:-1px;box-shadow:0 0 0 1px rgba(0,0,0,.2)}.cv-st{padding:1px 6px;border-radius:9px;background:#f3f4f6}.cv-st-verified{background:#ecfdf5;color:#047857}.cv-st-scheme-overridden{background:#fff7ed;color:#9a3412}.cv-st-failed,.cv-st-scheme-not-loaded{background:#fef2f2;color:#b91c1c}.cv-st-partial{background:#fffbeb;color:#92400e}.cv-fixes li{margin-bottom:10px}.cv-k{color:#6b7280}.cv-scroll{max-height:520px;overflow:auto;border:1px solid #e5e7eb}.cv-list li{margin:.25em 0}.cv-kpis{display:flex;gap:10px;margin:8px 0}.cv-kpis div{border:1px solid #e5e7eb;border-radius:6px;padding:6px 14px;color:#6b7280}.cv-kpis b{display:block;font-size:22px;line-height:1.2;color:#111827}.cv-cat{display:inline-block;padding:0 6px;border-radius:9px;font-size:11px;font-weight:600;white-space:nowrap}.cv-cat-bypass{background:#fef2f2;color:#b91c1c}.cv-cat-custom{background:#eff6ff;color:#1e40af}.cv-cat-unverified{background:#fffbeb;color:#92400e}.cv-cat-investigate{background:#f3f4f6;color:#374151}.cv-cat-intentional{background:#ecfdf5;color:#047857}.cv-warn{color:#b45309}.cv-batches>li{margin-bottom:8px}.cv-batches .cv-list{margin:2px 0 0 14px}.cv-fixtable td{font-size:12px}.cv-drillrow td{background:#fafafa;padding-top:0}.cv-drill summary{cursor:pointer;color:#6b7280}.cv-mini th,.cv-mini td{font-size:11px;padding:2px 5px}.cv-report details>summary{cursor:pointer}';

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
