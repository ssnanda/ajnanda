<?php
/**
 * AJNanda Color Scheme Validator.
 *
 * Read-only diagnostics for the Color Schemes screen. Two complementary checks:
 *
 *  1. Source scan (PHP, this file): literal colors in editable theme styles,
 *     templates, patterns, theme.json, saved theme_mods, Additional CSS, Global
 *     Styles, saved block content and widgets. Literals inside the palette
 *     definitions themselves are classified as valid.
 *  2. Rendered comparison (inc/admin/assets/color-validator.js): loads each target
 *     in a same-origin iframe under the baseline and comparison scheme, reads
 *     computed styles, finds the winning declaration and its token chain.
 *
 * Two render resolvers are compared on purpose, because the site has two:
 *  - "preview": the existing non-destructive preview (inc/preview.php), which only
 *    adds body.ajnanda-scheme-{slug}. Used for the Corporate Home demo.
 *  - "apply": real pages rendered with the 4 brand theme_mods filtered for this
 *    request only — the same values the real apply path (Customizer swatches,
 *    Site Kits, `wp ajnanda color set`) writes, read through the same
 *    ajnanda_customizer_css() / theme.json palette sync. Nothing is saved.
 *    A "class" mode replicates the preview mechanism on a real page.
 *
 * The render mode only activates for a logged-in administrator with a valid
 * validator nonce, sends no-cache/noindex headers, hides the admin bar so the
 * page matches public rendering, and never writes options, theme_mods or posts.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJNanda_Color_Validator {

    const NONCE_ACTION = 'ajnanda_color_validator';
    const PARAM_SCHEME = 'ajnanda_cv';
    const PARAM_MODE   = 'ajnanda_cv_mode';
    const PARAM_NONCE  = 'ajnanda_cv_nonce';
    const CAPABILITY   = 'manage_options'; // Same as the Color Schemes screen.
    const BASELINE     = 'blue';
    const COMPARISON   = 'teal';

    public static function init() {
        if (isset($_GET[self::PARAM_SCHEME])) {
            add_action('init', array(__CLASS__, 'maybe_enable_render_mode'), 0);
        }
        add_action('wp_ajax_ajnanda_color_validator_plan', array(__CLASS__, 'ajax_plan'));
        add_action('wp_ajax_ajnanda_color_validator_locate', array(__CLASS__, 'ajax_locate'));
    }

    /* ------------------------------------------------------------------
     * Render mode (unsaved, request-scoped)
     * ---------------------------------------------------------------- */

    public static function maybe_enable_render_mode() {
        if (!is_user_logged_in() || !current_user_can(self::CAPABILITY)) {
            return;
        }
        $nonce = isset($_GET[self::PARAM_NONCE]) ? sanitize_text_field(wp_unslash($_GET[self::PARAM_NONCE])) : '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }
        $schemes = ajnanda_get_color_schemes();
        $slug    = sanitize_key(wp_unslash($_GET[self::PARAM_SCHEME]));
        if (!isset($schemes[$slug])) {
            return;
        }
        $mode   = (isset($_GET[self::PARAM_MODE]) && 'class' === $_GET[self::PARAM_MODE]) ? 'class' : 'apply';
        $scheme = $schemes[$slug];

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
        if (!headers_sent()) {
            header('X-Robots-Tag: noindex, nofollow');
        }
        add_filter('show_admin_bar', '__return_false');

        if ('apply' === $mode) {
            $map = array(
                'theme_primary_color'      => $scheme['primary'],
                'theme_primary_dark_color' => $scheme['primary_dark'],
                'theme_secondary_color'    => $scheme['secondary'],
                'theme_accent_color'       => $scheme['accent'],
            );
            foreach ($map as $mod => $value) {
                add_filter('theme_mod_' . $mod, static function () use ($value) {
                    return $value;
                }, 9999);
            }
        } else {
            add_filter('body_class', static function ($classes) use ($slug) {
                $classes[] = 'ajnanda-scheme-' . $slug;
                return $classes;
            });
        }

        add_filter('body_class', static function ($classes) use ($slug, $mode) {
            $classes[] = 'ajnanda-cv-render';
            $classes[] = 'ajnanda-cv-' . $mode . '-' . $slug;
            return $classes;
        });
    }

    public static function render_url($url, $scheme_slug, $mode = 'apply') {
        return add_query_arg(array(
            self::PARAM_SCHEME => $scheme_slug,
            self::PARAM_MODE   => $mode,
            self::PARAM_NONCE  => wp_create_nonce(self::NONCE_ACTION),
        ), $url);
    }

    /* ------------------------------------------------------------------
     * Plan: roles, targets, source scan
     * ---------------------------------------------------------------- */

    public static function ajax_plan() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(array('message' => __('You do not have permission to run the validator.', 'ajnanda')), 403);
        }
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $schemes    = ajnanda_get_color_schemes();
        $baseline   = isset($_POST['baseline']) ? sanitize_key(wp_unslash($_POST['baseline'])) : self::BASELINE;
        $comparison = isset($_POST['comparison']) ? sanitize_key(wp_unslash($_POST['comparison'])) : self::COMPARISON;
        if (!isset($schemes[$baseline])) { $baseline = self::BASELINE; }
        if (!isset($schemes[$comparison])) { $comparison = self::COMPARISON; }
        $article_limit = max(1, min(50, isset($_POST['articles']) ? absint($_POST['articles']) : 5));

        wp_send_json_success(array(
            'generated'   => gmdate('c'),
            'site'        => home_url('/'),
            'theme'       => wp_get_theme()->get('Version'),
            'baseline'    => array('slug' => $baseline) + $schemes[$baseline],
            'comparison'  => array('slug' => $comparison) + $schemes[$comparison],
            'active'      => array(
                'slug'   => ajnanda_get_active_color_scheme_slug(),
                'colors' => ajnanda_get_active_brand_colors(),
            ),
            'roles'       => self::roles($schemes[$baseline], $schemes[$comparison]),
            'targets'     => self::targets($baseline, $comparison, $article_limit),
            'gaps'        => self::gaps(),
            'source'      => self::source_scan($schemes[$baseline], $schemes[$comparison]),
            'resolvers'   => self::resolver_notes(),
            'mods'        => self::saved_color_mods(),
        ));
    }

    /** Saved color-like theme_mods (mod => raw value), so a fix can name the setting that holds a value. */
    private static function saved_color_mods() {
        $out = array();
        foreach ((array) get_theme_mods() as $mod => $value) {
            if (is_string($value) && preg_match(self::COLOR_RE, $value)) {
                $out[$mod] = $value;
            }
        }
        return $out;
    }

    /* ------------------------------------------------------------------
     * Locate: map a rendered declaration back to file:line or saved content
     * ---------------------------------------------------------------- */

    /**
     * Read-only lookup for the report's fix list. Each item carries needles
     * (whitespace-insensitive declaration text such as "--primary:#2563eb")
     * and a scope:
     *  - theme|mu|plugin + rel: one file under that root;
     *  - code: the active theme, mu-plugins and active plugins;
     *  - post + post_id: that post's saved content, then reusable blocks /
     *    template parts, then code (a literal not in saved content is markup).
     * Needles without ":" are loose and only count next to the selector hint.
     */
    public static function ajax_locate() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(array('message' => __('You do not have permission to run the validator.', 'ajnanda')), 403);
        }
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $items = json_decode(isset($_POST['items']) ? wp_unslash($_POST['items']) : '[]', true);
        $items = is_array($items) ? array_slice($items, 0, 400) : array();
        $roots = self::locate_roots();
        $out   = array();
        $code  = array(); // Items searched across all code in one pass.
        $file_items = array(); // path => items

        foreach ($items as $item) {
            $id = isset($item['id']) ? sanitize_key($item['id']) : '';
            if (!$id) { continue; }
            $q = array(
                'id'       => $id,
                'needles'  => array_slice(array_values(array_unique(array_filter(array_map(array(__CLASS__, 'squash'), (array) ($item['needles'] ?? array()))))), 0, 12),
                'selector' => self::squash($item['selector'] ?? ''),
            );
            if (!$q['needles']) { continue; }
            $scope = (string) ($item['scope'] ?? '');
            if ('post' === $scope) {
                $hit = self::find_in_posts(absint($item['post_id'] ?? 0), $q['needles']);
                if ($hit) { $out[$id] = $hit; continue; }
                $scope = 'code';
            }
            if ('code' === $scope) { $code[] = $q; continue; }
            $path = self::safe_path($scope, (string) ($item['rel'] ?? ''), $roots);
            if ($path) { $file_items[$path][] = $q; }
        }

        $budget = 80 * MB_IN_BYTES;
        foreach ($file_items as $path => $qs) {
            foreach (self::search_file($path, $qs, $budget) as $id => $hit) { $out[$id] = $hit; }
        }
        if ($code) {
            $pending = $code;
            $weak = array();
            foreach (self::code_files($roots) as $path) {
                if (!$pending || $budget <= 0) { break; }
                foreach (self::search_file($path, $pending, $budget) as $id => $hit) {
                    if ($hit['strong']) {
                        $out[$id] = $hit;
                        $pending = array_values(array_filter($pending, static function ($q) use ($id) { return $q['id'] !== $id; }));
                    } elseif (!isset($weak[$id])) {
                        $weak[$id] = $hit;
                    }
                }
            }
            foreach ($weak as $id => $hit) { if (!isset($out[$id])) { $out[$id] = $hit; } }
        }
        wp_send_json_success($out);
    }

    private static function squash($s) {
        return strtolower(preg_replace('/\s+/', '', (string) $s));
    }

    private static function locate_roots() {
        $plugins = array();
        foreach ((array) get_option('active_plugins', array()) as $plugin) {
            $dir = dirname($plugin);
            if ('.' !== $dir && is_dir(WP_PLUGIN_DIR . '/' . $dir)) { $plugins[] = WP_PLUGIN_DIR . '/' . $dir; }
        }
        return array(
            'theme'   => get_template_directory(),
            'mu'      => defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins',
            'plugin'  => WP_PLUGIN_DIR,
            'plugins' => $plugins,
        );
    }

    private static function safe_path($scope, $rel, $roots) {
        if (!in_array($scope, array('theme', 'mu', 'plugin'), true) || '' === $rel) { return ''; }
        $base = realpath($roots[$scope]);
        $path = realpath($roots[$scope] . '/' . ltrim($rel, '/'));
        if (!$base || !$path || 0 !== strpos($path, $base . '/') || !is_file($path)) { return ''; }
        return preg_match('/\.(css|php|js|json|html)$/', $path) ? $path : '';
    }

    private static function code_files($roots) {
        $dirs = array_merge(array($roots['theme'], $roots['mu']), $roots['plugins']);
        $out = array();
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) { continue; }
            $it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                static function ($f) {
                    return $f->isDir() ? !preg_match('/^(\.git|node_modules|vendor|tests?|languages|docs|releases|bin)$/', $f->getFilename()) : (bool) preg_match('/\.(css|php|js|html)$/', $f->getFilename()) && !preg_match('/\.min\.js$/', $f->getFilename());
                }
            ));
            foreach ($it as $f) {
                if ($f->getSize() <= 3 * MB_IN_BYTES) { $out[] = $f->getPathname(); }
            }
        }
        // CSS first: most declarations live there.
        usort($out, static function ($a, $b) { return (int) !preg_match('/\.css$/', $a) <=> (int) !preg_match('/\.css$/', $b); });
        return $out;
    }

    private static function display_path($path) {
        $content = realpath(WP_CONTENT_DIR);
        $real = realpath($path);
        return ($content && $real && 0 === strpos($real, $content . '/')) ? substr($real, strlen($content) + 1) : basename($path);
    }

    /**
     * @return array<string,array{file:string,line:int,strong:bool}>
     */
    private static function search_file($path, $queries, &$budget) {
        $text = @file_get_contents($path);
        if (false === $text) { return array(); }
        $budget -= strlen($text);
        $lines = preg_split('/\R/', $text);
        $sq = array_map(array(__CLASS__, 'squash'), $lines);
        $label = self::display_path($path);
        $hits = array();
        $open = array();
        foreach ($queries as $q) { $open[$q['id']] = $q; }
        foreach ($sq as $i => $line) {
            if ('' === $line || !$open) { continue; }
            foreach ($open as $id => $q) {
                foreach ($q['needles'] as $needle) {
                    $pos = strpos($line, $needle);
                    if (false === $pos) { continue; }
                    if ('' !== $q['selector'] && self::selector_matches($sq, $i, $pos, $q['selector'])) {
                        $hits[$id] = array('file' => $label, 'line' => $i + 1, 'strong' => true);
                        unset($open[$id]);
                        continue 2;
                    }
                    if (!isset($hits[$id]) && false !== strpos($needle, ':')) {
                        // No selector hint: the declaration text alone is the answer.
                        $hits[$id] = array('file' => $label, 'line' => $i + 1, 'strong' => '' === $q['selector']);
                        if ('' === $q['selector']) { unset($open[$id]); continue 2; }
                    }
                }
            }
        }
        return $hits;
    }

    /** Does the rule enclosing squashed line $i at $pos list $selector (squashed) — or, for markup, mention it? */
    private static function selector_matches($sq, $i, $pos, $selector) {
        $buf = substr($sq[$i], 0, $pos);
        if (false !== strpos($sq[$i], $selector)) {
            // Same line mentions it (minified rule or markup class="…").
            $head = $buf;
        } else {
            $head = $buf;
            for ($j = $i - 1; $j >= 0 && $i - $j <= 60 && false === strrpos($head, '{'); $j--) { $head = $sq[$j] . $head; }
        }
        $open = strrpos($head, '{');
        if (false === $open) { return false !== strpos($sq[$i], $selector); }
        $head = substr($head, 0, $open);
        $cut = -1;
        foreach (array('}', ';', '*/', '{') as $stop) {
            $p = strrpos($head, $stop);
            if (false !== $p) { $cut = max($cut, $p + strlen($stop) - 1); }
        }
        $list = explode(',', substr($head, $cut + 1));
        return in_array($selector, $list, true) || false !== strpos($sq[$i], $selector);
    }

    private static function find_in_posts($post_id, $needles) {
        $posts = array();
        if ($post_id && ($p = get_post($post_id))) { $posts[] = $p; }
        $posts = array_merge($posts, get_posts(array('post_type' => array('wp_block', 'wp_template', 'wp_template_part', 'wp_navigation'), 'post_status' => 'publish', 'numberposts' => 200)));
        foreach ($posts as $p) {
            $content = self::squash($p->post_content);
            foreach ($needles as $needle) {
                if (false !== strpos($content, $needle)) {
                    return array('post' => (int) $p->ID, 'type' => $p->post_type, 'title' => wp_strip_all_tags(get_the_title($p)), 'edit' => (string) get_edit_post_link($p->ID, 'raw'), 'needle' => $needle);
                }
            }
        }
        return null;
    }

    public static function roles($base, $comp) {
        $roles = array();
        foreach (array('primary' => '--primary', 'primary_dark' => '--primary-dark', 'secondary' => '--secondary', 'accent' => '--accent') as $role => $token) {
            $roles[] = array(
                'role'       => $role,
                'token'      => $token,
                'baseline'   => strtolower($base[$role]),
                'comparison' => strtolower($comp[$role]),
                'differs'    => strtolower($base[$role]) !== strtolower($comp[$role]),
            );
        }
        return $roles;
    }

    /**
     * Every public view the validator renders. Real pages use the "apply"
     * resolver; the demo uses the existing preview URL unchanged.
     */
    public static function targets($baseline, $comparison, $article_limit) {
        $targets = array();
        $mobile_ids = array();

        if (function_exists('ajnanda_get_preview_url') && class_exists('AJNanda_Admin')) {
            $slug = AJNanda_Admin::PREVIEW_DEFAULT_PAGE_DESIGN;
            $targets[] = array(
                'id' => 'demo-corporate-home', 'kind' => 'demo', 'resolver' => 'preview',
                'label' => __('Corporate Home demo (Color Schemes preview)', 'ajnanda'),
                'url' => ajnanda_get_preview_url($slug, $comparison),
                'source' => self::pattern_source($slug),
                'baseline_url' => ajnanda_get_preview_url($slug, $baseline),
                'comparison_url' => ajnanda_get_preview_url($slug, $comparison),
                'mobile' => true,
            );
        }

        $add = static function ($id, $label, $url, $group, $mobile = false, $object = '') use (&$targets, $baseline, $comparison) {
            if (!$url) { return; }
            $targets[] = array(
                'id' => $id, 'kind' => 'real', 'resolver' => 'apply', 'label' => $label, 'group' => $group,
                'url' => $url, 'object' => $object,
                'baseline_url' => self::render_url($url, $baseline, 'apply'),
                'comparison_url' => self::render_url($url, $comparison, 'apply'),
                'mobile' => $mobile,
            );
        };

        $front_id = (int) get_option('page_on_front');
        $add('home', __('Homepage', 'ajnanda'), home_url('/'), 'Home', true, $front_id ? 'page:' . $front_id : '');

        // The existing preview mechanism (body class) on the real homepage, to separate the two resolvers.
        $targets[] = array(
            'id' => 'home-preview-class', 'kind' => 'real', 'resolver' => 'class', 'group' => 'Home',
            'label' => __('Homepage — preview mechanism (body class only)', 'ajnanda'),
            'url' => home_url('/'), 'object' => $front_id ? 'page:' . $front_id : '',
            'baseline_url' => self::render_url(home_url('/'), $baseline, 'class'),
            'comparison_url' => self::render_url(home_url('/'), $comparison, 'class'),
            'mobile' => false,
        );

        $pages = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 100, 'orderby' => 'menu_order title', 'order' => 'ASC'));
        foreach ($pages as $page) {
            if ((int) $page->ID === $front_id) { continue; }
            $name  = $page->post_name;
            $group = 'Pages';
            if (in_array($name, array('service', 'services'), true)) { $group = 'Services'; }
            elseif ($page->post_parent && in_array(get_post_field('post_name', $page->post_parent), array('service', 'services'), true)) { $group = 'Service detail'; }
            elseif ('knowledge-base' === $name) { $group = 'Knowledge Base'; }
            $mobile = in_array($name, array('service', 'services', 'about', 'email-us', 'contact', 'knowledge-base'), true);
            $add('page-' . $page->ID, get_the_title($page) . ' (/' . $name . '/)', get_permalink($page), $group, $mobile, 'page:' . $page->ID);
        }

        foreach (get_post_types(array('public' => true, '_builtin' => false), 'objects') as $type) {
            $posts = get_posts(array('post_type' => $type->name, 'post_status' => 'publish', 'numberposts' => 50));
            foreach ($posts as $i => $p) {
                $add($type->name . '-' . $p->ID, $type->labels->singular_name . ': ' . get_the_title($p), get_permalink($p), 'Service detail' === $type->labels->singular_name ? 'Service detail' : $type->labels->name, 0 === $i, $type->name . ':' . $p->ID);
            }
            $archive = get_post_type_archive_link($type->name);
            if ($archive) { $add('archive-' . $type->name, $type->labels->name . ' archive', $archive, $type->labels->name); }
        }

        $page_for_posts = (int) get_option('page_for_posts');
        if ($page_for_posts) {
            $add('posts-index', __('Posts index', 'ajnanda'), get_permalink($page_for_posts), 'Knowledge Base');
        }
        $articles = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'numberposts' => $article_limit));
        foreach ($articles as $i => $p) {
            $add('post-' . $p->ID, __('Article', 'ajnanda') . ': ' . get_the_title($p), get_permalink($p), 'Knowledge Base', 0 === $i, 'post:' . $p->ID);
        }
        $cats = get_terms(array('taxonomy' => 'category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 3));
        if (!is_wp_error($cats)) {
            foreach ($cats as $cat) {
                $add('cat-' . $cat->term_id, __('Category archive', 'ajnanda') . ': ' . $cat->name, get_term_link($cat), 'Knowledge Base');
            }
        }
        $add('search', __('Search results', 'ajnanda'), add_query_arg('s', 'llc', home_url('/')), 'Templates');
        $add('404', __('404 template', 'ajnanda'), home_url('/ajnanda-color-validator-404-check/'), 'Templates', true);

        return $targets;
    }

    /** Public views the validator knows it does not render. */
    public static function gaps() {
        $gaps = array();
        $total = (int) wp_count_posts('post')->publish;
        $gaps[] = array('view' => __('Remaining Knowledge Base articles', 'ajnanda'), 'reason' => sprintf(__('%d published articles share single.php; only the sampled ones are rendered.', 'ajnanda'), $total));
        $gaps[] = array('view' => __('Hover / focus states', 'ajnanda'), 'reason' => __('Not rendered: resolved from matching :hover/:focus rules against each element\'s own custom properties (rule-resolved).', 'ajnanda'));
        $gaps[] = array('view' => __('Logged-out rendering', 'ajnanda'), 'reason' => __('Pages render as the logged-in administrator with the admin bar hidden; plugins that change output for logged-in users may differ.', 'ajnanda'));
        $gaps[] = array('view' => __('Cross-origin / iframe / shadow-DOM widgets', 'ajnanda'), 'reason' => __('Styles inside third-party iframes, shadow roots or cross-origin stylesheets cannot be read and are marked unverifiable.', 'ajnanda'));
        $gaps[] = array('view' => __('Customer portal, checkout and form flows', 'ajnanda'), 'reason' => __('Plugin-rendered states behind login or form submission are not exercised.', 'ajnanda'));
        return $gaps;
    }

    public static function resolver_notes() {
        return array(
            'preview' => 'inc/preview.php ajnanda_handle_preview_request(): adds body.ajnanda-scheme-{slug}; style.css .ajnanda-scheme-{slug} overrides --primary/--primary-dark/--secondary/--accent on <body> only. theme_mods, ajnanda_customizer_css() output and theme.json presets are untouched.',
            'apply'   => 'Customizer "Quick presets" swatches (inc/color-schemes.php), ajnanda_apply_site_kit() (inc/site-kits.php) and `wp ajnanda color set` (inc/cli/class-ajnanda-cli.php) all write theme_primary_color / theme_primary_dark_color / theme_secondary_color / theme_accent_color. Read by ajnanda_customizer_css() (functions.php, :root literals) and ajnanda_sync_brand_palette() (inc/template-slots.php, theme.json presets).',
            'class'   => 'Same body-class mechanism as the preview, applied to a real page by the validator.',
        );
    }

    private static function pattern_source($slug) {
        $name = preg_replace('#^ajnanda/#', '', $slug);
        foreach (array('patterns/' . $name . '.php', 'inc/page-designs.php') as $rel) {
            if (file_exists(get_theme_file_path($rel))) {
                return $rel;
            }
        }
        return 'inc/page-designs.php';
    }

    /* ------------------------------------------------------------------
     * Source scan
     * ---------------------------------------------------------------- */

    const COLOR_RE = '/#(?:[0-9a-fA-F]{8}|[0-9a-fA-F]{6}|[0-9a-fA-F]{3,4})\b|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*[\d.]+\s*)?\)/';

    public static function source_scan($base, $comp) {
        $ctx = self::classifier_context($base, $comp);
        $items = array();
        $counts = array();

        $theme_dir = get_template_directory();
        $files = self::theme_files($theme_dir);
        foreach ($files as $rel) {
            self::scan_text(file_get_contents($theme_dir . '/' . $rel), 'theme_file', $rel, $ctx, $items, $counts);
        }

        // Saved WordPress styles and settings.
        $custom_css = function_exists('wp_get_custom_css') ? wp_get_custom_css() : '';
        if ($custom_css) {
            self::scan_text($custom_css, 'additional_css', 'Appearance → Customize → Additional CSS', $ctx, $items, $counts);
        }
        foreach ((array) get_theme_mods() as $mod => $value) {
            if (!is_string($value) || !preg_match(self::COLOR_RE, $value)) { continue; }
            self::scan_text($mod . ': ' . $value, 'theme_mod', 'theme_mod ' . $mod, $ctx, $items, $counts, $mod);
        }
        $global_styles = get_posts(array('post_type' => 'wp_global_styles', 'post_status' => 'any', 'numberposts' => 5));
        foreach ($global_styles as $gs) {
            self::scan_text($gs->post_content, 'global_styles', 'Global Styles (wp_global_styles #' . $gs->ID . ')', $ctx, $items, $counts);
        }
        $content_types = array_merge(array('page', 'post', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation'), array_keys(get_post_types(array('public' => true, '_builtin' => false))));
        $content = get_posts(array('post_type' => $content_types, 'post_status' => 'publish', 'numberposts' => 500));
        foreach ($content as $post) {
            $ref = sprintf('%s #%d "%s" (%s)', $post->post_type, $post->ID, wp_strip_all_tags(get_the_title($post)), wp_make_link_relative((string) get_permalink($post)));
            self::scan_text($post->post_content, 'content', $ref, $ctx, $items, $counts, '', $post->ID);
            if (preg_match_all('/has-(primary-blue|deep-blue|purple|gold)-(?:background-)?color|"(?:backgroundColor|textColor)":"(primary-blue|deep-blue|purple|gold)"/', $post->post_content, $m)) {
                $counts['preset_slug'] = ($counts['preset_slug'] ?? 0) + count($m[0]);
            }
        }
        foreach (array('widget_block', 'widget_text', 'widget_custom_html') as $opt) {
            $val = get_option($opt);
            if ($val) {
                self::scan_text(wp_json_encode($val), 'widget', 'option ' . $opt, $ctx, $items, $counts);
            }
        }

        // Plugin-owned styles are reported, not scanned in depth.
        $plugins = array();
        foreach ((array) get_option('active_plugins', array()) as $plugin) {
            $plugins[] = dirname($plugin);
        }

        usort($items, static function ($a, $b) {
            $rank = array('brand_literal' => 0, 'literal_default' => 1, 'saved_override' => 2, 'brand_tint' => 3, 'fallback' => 4);
            return ($rank[$a['class']] ?? 9) <=> ($rank[$b['class']] ?? 9);
        });

        return array(
            'counts'  => $counts,
            'items'   => array_slice($items, 0, 600),
            'total'   => count($items),
            'files'   => count($files),
            'plugins' => $plugins,
        );
    }

    private static function theme_files($dir) {
        $out = array();
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $rel = ltrim(str_replace($dir, '', $file->getPathname()), '/');
            if (!preg_match('/\.(css|php|json|js|html|svg)$/', $rel)) { continue; }
            // Admin-only screens, tooling and docs never reach public pages.
            if (preg_match('#^(bin|docs|tests|releases|node_modules|\.git|inc/admin|inc/cli|inc/search-ai/admin)/#', $rel)) { continue; }
            if (preg_match('#(^|/)(color-validator|admin-ui|admin-posts-tiles)\.php$|\.min\.(css|js)$#', $rel)) { continue; }
            $out[] = $rel;
        }
        sort($out);
        return $out;
    }

    private static function classifier_context($base, $comp) {
        $roles = array();
        foreach (array('primary', 'primary_dark', 'secondary', 'accent') as $role) {
            if (strtolower($base[$role]) !== strtolower($comp[$role])) {
                $roles[strtolower($base[$role])] = $role;
            }
        }
        $hsl = self::hex_to_hsl($base['primary']);
        return array('roles' => $roles, 'hue' => $hsl[0]);
    }

    /**
     * @param string $text   Raw source.
     * @param string $type   theme_file|additional_css|theme_mod|global_styles|content|widget.
     * @param string $ref    Human-readable location.
     */
    private static function scan_text($text, $type, $ref, $ctx, &$items, &$counts, $mod = '', $post_id = 0) {
        $lines = preg_split('/\R/', (string) $text);
        $selector = '';
        $palette_block = false;
        $depth = 0;
        $is_css = 'theme_file' === $type ? (bool) preg_match('/\.css$/', $ref) : in_array($type, array('additional_css'), true);
        foreach ($lines as $n => $line) {
            if ($is_css) {
                if (preg_match('/^\s*([^{}@][^{}]*)\{/', $line, $m)) {
                    $selector = trim($m[1]);
                    $palette_block = 0 === $depth && (bool) preg_match('/^(:root|\.ajnanda-scheme-[a-z0-9-]+(\s*,\s*\.ajnanda-scheme-[a-z0-9-]+)*)$/', $selector);
                }
                $depth += substr_count($line, '{') - substr_count($line, '}');
                if ($depth <= 0) { $depth = 0; }
            }
            if (!preg_match_all(self::COLOR_RE, $line, $matches)) { continue; }
            foreach ($matches[0] as $literal) {
                $hex = self::normalize($literal);
                if (!$hex) { continue; }
                $class = self::classify($hex, $literal, $line, $type, $ref, $ctx, $palette_block, $mod);
                $counts[$class] = ($counts[$class] ?? 0) + 1;
                if (in_array($class, array('neutral', 'other', 'palette_definition'), true)) { continue; }
                $property = preg_match('/([a-z-]+)\s*:\s*[^;]*' . preg_quote($literal, '/') . '/i', $line, $pm) ? $pm[1] : '';
                $items[] = array(
                    'class'    => $class,
                    'type'     => $type,
                    'ref'      => $ref,
                    'line'     => 'theme_file' === $type ? $n + 1 : 0,
                    'selector' => $is_css ? $selector : '',
                    'property' => $property,
                    'literal'  => $literal,
                    'hex'      => $hex,
                    'role'     => $ctx['roles'][$hex] ?? '',
                    'snippet'  => mb_substr(trim($line), 0, 220),
                    'post_id'  => $post_id,
                );
            }
        }
    }

    private static function classify($hex, $literal, $line, $type, $ref, $ctx, $palette_block, $mod) {
        if ('theme_file' === $type) {
            if ($palette_block) { return 'palette_definition'; }
            if ('inc/color-schemes.php' === $ref || 'theme.json' === $ref && false !== strpos($line, '"color"') ) { return 'palette_definition'; }
            if (preg_match('/var\(\s*--[a-z0-9-]+\s*,\s*' . preg_quote($literal, '/') . '/i', $line)) { return 'fallback'; }
            if (isset($ctx['roles'][$hex]) && preg_match("/get_theme_mod\(\s*'([a-z0-9_]+)'\s*,\s*'" . preg_quote($literal, '/') . "'/i", $line, $m) && !in_array($m[1], array('theme_primary_color', 'theme_primary_dark_color', 'theme_secondary_color', 'theme_accent_color'), true)) {
                return 'literal_default';
            }
            if (isset($ctx['roles'][$hex]) && preg_match("/'(primary-blue|deep-blue|purple|gold)'\s*=>\s*'#/", $line)) { return 'palette_definition'; }
            if (isset($ctx['roles'][$hex]) && preg_match("/'(default|color)'\s*=>\s*'#/", $line) && preg_match('/theme_(primary|primary_dark|secondary|accent)_color|Primary|Secondary|Accent/', $line)) { return 'palette_definition'; }
        }
        if ('theme_mod' === $type) {
            if (in_array($mod, array('theme_primary_color', 'theme_primary_dark_color', 'theme_secondary_color', 'theme_accent_color'), true)) { return 'palette_definition'; }
            if (isset($ctx['roles'][$hex]) || self::is_brand_tint($hex, $ctx)) { return 'saved_override'; }
        }
        if (isset($ctx['roles'][$hex])) { return 'brand_literal'; }
        if (self::is_brand_tint($hex, $ctx)) { return 'brand_tint'; }
        $hsl = self::hex_to_hsl($hex);
        return $hsl[1] < 0.12 ? 'neutral' : 'other';
    }

    private static function is_brand_tint($hex, $ctx) {
        list($h, $s, $l) = self::hex_to_hsl($hex);
        $dh = abs($h - $ctx['hue']);
        $dh = min($dh, 360 - $dh);
        return $s >= 0.3 && $dh <= 22 && $l >= 0.2 && $l <= 0.97;
    }

    public static function normalize($literal) {
        $literal = strtolower(trim($literal));
        if ('#' === $literal[0]) {
            $h = substr($literal, 1);
            if (3 === strlen($h) || 4 === strlen($h)) {
                $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
            }
            return '#' . substr($h, 0, 6);
        }
        if (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $literal, $m)) {
            return sprintf('#%02x%02x%02x', min(255, (int) $m[1]), min(255, (int) $m[2]), min(255, (int) $m[3]));
        }
        return '';
    }

    public static function hex_to_hsl($hex) {
        $hex = ltrim(self::normalize($hex) ?: '#000000', '#');
        $r = hexdec(substr($hex, 0, 2)) / 255; $g = hexdec(substr($hex, 2, 2)) / 255; $b = hexdec(substr($hex, 4, 2)) / 255;
        $max = max($r, $g, $b); $min = min($r, $g, $b); $l = ($max + $min) / 2;
        if ($max === $min) { return array(0, 0, $l); }
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        if ($max === $r) { $h = ($g - $b) / $d + ($g < $b ? 6 : 0); }
        elseif ($max === $g) { $h = ($b - $r) / $d + 2; }
        else { $h = ($r - $g) / $d + 4; }
        return array($h * 60, $s, $l);
    }
}

AJNanda_Color_Validator::init();
