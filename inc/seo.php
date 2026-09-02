<?php
/**
 * Theme-native SEO, GEO/AEO, and Site Kit insights.
 *
 * Site-wide settings and the Site Kit insights panel are plain admin pages under the AJNanda menu
 * (see ajnanda_seo_register_admin_pages() below) — moved out of the Customizer, since none of this
 * needs live-preview and a regular admin page is faster to load and easier to find. Still stored as
 * theme_mods (set_theme_mod()/get_theme_mod()), same keys as before the move, so every read
 * elsewhere in this file (ajnanda_seo_head_tags(), ajnanda_seo_output_schema(),
 * ajnanda_seo_robots_txt(), etc.) needed zero changes. Per-post overrides still can't live here —
 * theme_mods are site-wide, not per-post — so those stay on the meta box further down, the only
 * place WordPress allows that.
 *
 * @package NCLLC_Pro
 */

if (! defined('ABSPATH')) {
    exit;
}

// ── AJNanda admin menu: SEO Settings + SEO Insights ─────────────────────────

// Priority 20: must run after AJNanda_Admin::register_menu() (inc/admin/class-ajnanda-admin.php,
// default priority 10) has already called add_menu_page('ajnanda', ...). WordPress resolves each
// submenu page's internal callback hook name from its parent's registration state at the moment
// add_submenu_page() runs — calling this before the 'ajnanda' top-level menu exists makes it
// compute the wrong hook name, so the render callbacks silently never fire when the pages are
// actually opened.
add_action('admin_menu', 'ajnanda_seo_register_admin_pages', 20);
function ajnanda_seo_register_admin_pages() {
    $settings_hook = add_submenu_page(
        'ajnanda',
        __('SEO Settings', 'ajnanda'),
        __('SEO Settings', 'ajnanda'),
        'manage_options',
        'ajnanda-seo-settings',
        'ajnanda_seo_render_settings_page'
    );
    // Only the SEO Settings screen needs the media picker (Default Social Share Image) — same
    // wp_enqueue_media() call the post-editor meta box below already uses, just scoped to this one
    // admin page via load-{hook} instead of the meta box's post.php/post-new.php check.
    if ($settings_hook) {
        add_action('load-' . $settings_hook, 'wp_enqueue_media');
    }

    add_submenu_page(
        'ajnanda',
        __('SEO Insights', 'ajnanda'),
        __('SEO Insights', 'ajnanda'),
        'manage_options',
        'ajnanda-seo-insights',
        'ajnanda_seo_render_insights_page'
    );
}

function ajnanda_seo_render_settings_page() {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Insufficient permissions.', 'ajnanda'));
    }

    if (class_exists('AJNanda_Search_AI_Admin')) {
        wp_safe_redirect(add_query_arg(
            array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'seo'),
            admin_url('admin.php')
        ));
        exit;
    }

    $values = array(
        'seo_meta_description_default' => get_theme_mod('seo_meta_description_default', ''),
        'seo_default_social_image'     => get_theme_mod('seo_default_social_image', ''),
        'seo_twitter_handle'           => get_theme_mod('seo_twitter_handle', ''),
        'seo_business_phone'           => get_theme_mod('seo_business_phone', ''),
        'seo_business_address'         => get_theme_mod('seo_business_address', ''),
        'seo_schema_enabled'           => get_theme_mod('seo_schema_enabled', true),
        'seo_allow_ai_crawlers'        => get_theme_mod('seo_allow_ai_crawlers', true),
        'seo_llms_txt_enabled'         => get_theme_mod('seo_llms_txt_enabled', true),
    );
    $saved = isset($_GET['ajnanda_seo_saved']);

    include get_template_directory() . '/inc/admin/views/seo-settings.php';
}

add_action('admin_post_ajnanda_save_seo_settings', 'ajnanda_seo_save_settings');
function ajnanda_seo_save_settings() {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Insufficient permissions.', 'ajnanda'));
    }
    check_admin_referer('ajnanda_seo_save_settings', 'ajnanda_seo_settings_nonce');

    set_theme_mod('seo_meta_description_default', sanitize_text_field(wp_unslash($_POST['seo_meta_description_default'] ?? '')));
    set_theme_mod('seo_default_social_image', esc_url_raw(wp_unslash($_POST['seo_default_social_image'] ?? '')));
    set_theme_mod('seo_twitter_handle', sanitize_text_field(wp_unslash($_POST['seo_twitter_handle'] ?? '')));
    if (isset($_POST['seo_business_phone']) || ! class_exists('AJNanda_Search_AI_Admin')) {
        set_theme_mod('seo_business_phone', sanitize_text_field(wp_unslash($_POST['seo_business_phone'] ?? '')));
    }
    if (isset($_POST['seo_business_address']) || ! class_exists('AJNanda_Search_AI_Admin')) {
        set_theme_mod('seo_business_address', sanitize_text_field(wp_unslash($_POST['seo_business_address'] ?? '')));
    }
    set_theme_mod('seo_schema_enabled', ajnanda_sanitize_checkbox($_POST['seo_schema_enabled'] ?? ''));
    // The combined control is retained only for the legacy standalone form.
    // Search & AI's AI Discovery tab owns the separated crawler policies.
    if (isset($_POST['seo_allow_ai_crawlers']) || ! class_exists('AJNanda_Search_AI_Admin')) {
        set_theme_mod('seo_allow_ai_crawlers', ajnanda_sanitize_checkbox($_POST['seo_allow_ai_crawlers'] ?? ''));
    }
    set_theme_mod('seo_llms_txt_enabled', ajnanda_sanitize_checkbox($_POST['seo_llms_txt_enabled'] ?? ''));

    wp_safe_redirect(add_query_arg(
        array('page' => 'ajnanda-search-ai', 'tab' => 'seo', 'ajnanda_seo_saved' => '1'),
        admin_url('admin.php')
    ));
    exit;
}

function ajnanda_seo_render_insights_page() {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Insufficient permissions.', 'ajnanda'));
    }

    if (class_exists('AJNanda_Search_AI_Admin')) {
        wp_safe_redirect(add_query_arg(
            array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'insights'),
            admin_url('admin.php')
        ));
        exit;
    }

    include get_template_directory() . '/inc/admin/views/seo-insights.php';
}

// ── Site Kit insights ────────────────────────────────────────────────────────

/**
 * Pulls Search Console + PageSpeed Insights data from the already-connected Google Site Kit plugin
 * and turns it into a few plain, actionable suggestions. Uses Site Kit's own REST routes via
 * rest_do_request() (in-process, no HTTP round trip) rather than trying to reach its internal
 * Module::get_data() directly — Site Kit doesn't expose a public accessor for that, and reflection
 * into a third-party plugin's internals would be fragile across Site Kit versions. This is the same
 * mechanism Site Kit's own code uses internally for server-to-server calls.
 */
function ajnanda_seo_render_site_kit_insights() {
    if (! class_exists('\Google\Site_Kit\Plugin')) {
        return '<p>' . esc_html__('Install and connect Google Site Kit to see SEO suggestions here.', 'ajnanda') . '</p>';
    }

    $search_console = ajnanda_seo_site_kit_request(
        'search-console',
        'searchanalytics',
        array(
            'startDate'  => gmdate('Y-m-d', strtotime('-28 days')),
            'endDate'    => gmdate('Y-m-d', strtotime('-1 day')),
            'dimensions' => array('query'),
            'limit'      => 25,
        )
    );

    $pagespeed = ajnanda_seo_site_kit_request(
        'pagespeed-insights',
        'pagespeed',
        array('strategy' => 'mobile')
    );

    if (null === $search_console && null === $pagespeed) {
        return '<p>' . esc_html__('Connect Google Site Kit (Search Console and/or PageSpeed Insights) to see suggestions here.', 'ajnanda') . '</p>';
    }

    $suggestions = array();

    if (is_array($search_console)) {
        foreach ($search_console as $row) {
            $clicks      = (float) ($row['clicks'] ?? 0);
            $impressions = (float) ($row['impressions'] ?? 0);
            $position    = (float) ($row['position'] ?? 0);
            $query       = is_array($row['keys'] ?? null) ? ($row['keys'][0] ?? '') : '';
            if ('' === $query) {
                continue;
            }
            $ctr = $impressions > 0 ? ($clicks / $impressions) : 0;

            if ($impressions >= 20 && $ctr < 0.02) {
                $suggestions[] = sprintf(
                    /* translators: %s: search query */
                    __('"%s" gets impressions but a low click-through rate — consider rewriting the title/meta description to match what searchers want.', 'ajnanda'),
                    esc_html($query)
                );
            } elseif ($position >= 6 && $position <= 20) {
                $suggestions[] = sprintf(
                    /* translators: 1: search query, 2: average position */
                    __('"%1$s" ranks around position %2$s — a content refresh (more depth, better structure, an FAQ section) could push it toward page 1.', 'ajnanda'),
                    esc_html($query),
                    number_format_i18n($position, 1)
                );
            }
            if (count($suggestions) >= 5) {
                break;
            }
        }
    }

    if (is_array($pagespeed)) {
        $score = null;
        if (isset($pagespeed['lighthouseResult']['categories']['performance']['score'])) {
            $score = round(((float) $pagespeed['lighthouseResult']['categories']['performance']['score']) * 100);
        }
        if (null !== $score && $score < 50) {
            $suggestions[] = sprintf(
                /* translators: %d: PageSpeed score out of 100 */
                __('Mobile PageSpeed score is %d/100 — slow mobile performance can hurt both search ranking and how often visitors stick around.', 'ajnanda'),
                (int) $score
            );
        }
    }

    if (empty($suggestions)) {
        return '<p>' . esc_html__('No specific suggestions right now — nothing stood out in the last 28 days of data.', 'ajnanda') . '</p>';
    }

    $html = '<ul style="margin:0;padding-left:18px;">';
    foreach ($suggestions as $suggestion) {
        $html .= '<li style="margin-bottom:8px;font-size:12px;line-height:1.4;">' . wp_kses_post($suggestion) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}

/**
 * One in-process call to a Site Kit REST datapoint. Returns the decoded `data` payload, or null if
 * Site Kit isn't connected / the request fails for any reason (never surfaces an error — this is a
 * best-effort insights panel, not a critical path).
 */
function ajnanda_seo_site_kit_request($module_slug, $datapoint, $params = array()) {
    if (! function_exists('rest_do_request')) {
        return null;
    }
    try {
        $request = new WP_REST_Request('GET', '/google-site-kit/v1/modules/' . $module_slug . '/data/' . $datapoint);
        foreach ($params as $key => $value) {
            $request->set_param($key, $value);
        }
        $response = rest_do_request($request);
        if (is_wp_error($response) || $response->is_error()) {
            return null;
        }
        $data = $response->get_data();
        return $data['data'] ?? $data;
    } catch (\Throwable $e) {
        return null;
    }
}

// ── <head> output ────────────────────────────────────────────────────────────

add_filter('pre_get_document_title', 'ajnanda_seo_document_title');
function ajnanda_seo_document_title($title) {
    if (is_singular()) {
        $custom = get_post_meta(get_queried_object_id(), '_ajnanda_seo_title', true);
        if ($custom) {
            return $custom;
        }
    }
    return $title;
}

add_action('wp_head', 'ajnanda_seo_head_tags', 1);
function ajnanda_seo_head_tags() {
    $is_singular = is_singular();
    $post_id     = $is_singular ? get_queried_object_id() : 0;

    $noindex = $post_id && get_post_meta($post_id, '_ajnanda_seo_noindex', true);
    if ($post_id && class_exists('AJNanda_Search_AI_Content_Policy')) {
        $noindex = ! AJNanda_Search_AI_Content_Policy::evaluate($post_id)['search_indexable'];
    }
    if ($noindex) {
        echo '<meta name="robots" content="noindex,follow">' . "\n";
    }

    $description = $post_id ? get_post_meta($post_id, '_ajnanda_seo_description', true) : '';
    if (! $description && $is_singular) {
        $description = ajnanda_seo_excerpt_fallback($post_id);
    }
    if (! $description) {
        $description = get_theme_mod('seo_meta_description_default', '');
    }
    if ($description) {
        echo '<meta name="description" content="' . esc_attr(wp_trim_words($description, 40)) . '">' . "\n";
    }

    // Only emit our own canonical for the home/front page — WordPress core's rel_canonical()
    // already outputs one for every singular post/page via its own wp_head hook, so adding a
    // second one there would duplicate the tag.
    $canonical = $is_singular ? get_permalink($post_id) : (is_home() || is_front_page() ? home_url('/') : '');
    if ($canonical && ! $is_singular) {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

    // Open Graph / Twitter Card
    $custom_title = $post_id ? get_post_meta($post_id, '_ajnanda_seo_title', true) : '';
    $og_title     = $custom_title ? $custom_title : ($is_singular ? get_the_title($post_id) : (get_bloginfo('name') ?: wp_parse_url(home_url(), PHP_URL_HOST)));
    $og_image = $post_id ? get_post_meta($post_id, '_ajnanda_seo_social_image', true) : '';
    if (! $og_image && $post_id && has_post_thumbnail($post_id)) {
        $og_image = get_the_post_thumbnail_url($post_id, 'large');
    }
    if (! $og_image) {
        $og_image = get_theme_mod('seo_default_social_image', '');
    }

    echo '<meta property="og:type" content="' . esc_attr($is_singular ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
    if ($description) {
        echo '<meta property="og:description" content="' . esc_attr(wp_trim_words($description, 40)) . '">' . "\n";
    }
    if ($canonical) {
        echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    }
    if ($og_image) {
        echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
    }

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $twitter_handle = get_theme_mod('seo_twitter_handle', '');
    if ($twitter_handle) {
        echo '<meta name="twitter:site" content="' . esc_attr($twitter_handle) . '">' . "\n";
    }

    if (get_theme_mod('seo_schema_enabled', true) && ! $noindex) {
        ajnanda_seo_output_schema($is_singular, $post_id);
    }
}

/**
 * Meta-description fallback for singular posts/pages that have no manual `_ajnanda_seo_description`.
 * get_the_excerpt() on block-built content (headings, stat labels, short paragraphs stacked with no
 * punctuation between them) collapses into a run-on string once tags are stripped — e.g. "★★★★★5.0
 * RatingFast SetupSame Day AvailableCharlotte, NC1914 J N PEASE PL." Inserting a space at every
 * block-level closing tag before stripping keeps the words apart so the description reads as a
 * sentence instead of a scraped fragment.
 */
function ajnanda_seo_excerpt_fallback($post_id) {
    $excerpt = get_the_excerpt($post_id);
    $spaced  = preg_replace('/<\/(h[1-6]|p|li|div|section|figcaption)>/i', ' ', $excerpt);
    $spaced  = wp_strip_all_tags($spaced);
    return trim(preg_replace('/\s+/', ' ', $spaced));
}

/**
 * Organization/LocalBusiness on the front page, Article + auto-detected FAQPage on single posts.
 * FAQPage detection looks for "heading ending in a question mark, followed by a paragraph" in the
 * post content — no manual Q&A entry required. This is the main GEO/AEO win: structured Q&A that
 * AI answer engines (and Google's AI Overviews) can lift directly, without any content-authoring
 * changes beyond writing headings as questions, which many posts already do naturally.
 */
function ajnanda_seo_output_schema($is_singular, $post_id) {
    if (class_exists('AJNanda_Search_AI_Schema_Graph')) {
        AJNanda_Search_AI_Schema_Graph::render($is_singular, $post_id);
    }
}

/**
 * Scans rendered post content for two FAQ authoring patterns and turns whichever is found into
 * FAQPage JSON-LD:
 *  1. "<h2/h3>question?</h2><p>answer</p>" — the plain heading+paragraph style blog posts tend to use.
 *  2. "<details><summary>question?</summary>...<p>answer</p>...</details>" — the accordion (Details
 *     block) style the homepage FAQ section uses. Everything between <summary> and </details> is
 *     collapsed into one answer so multi-paragraph accordion answers still produce a single,
 *     complete acceptedAnswer instead of only the first <p>.
 * Deliberately simple regex matching (no block-editor-specific parsing) so it works regardless of
 * which block/editor produced the HTML.
 */
function ajnanda_seo_extract_faq_schema($post_id, $include_heading_pairs = true) {
    $content = apply_filters('the_content', get_post_field('post_content', $post_id));
    $questions = array();

    // Each capture is bounded to "(?:(?!</tag>).)*" — everything up to that specific tag's own
    // closing delimiter, never beyond it — instead of a bare ".*?". A bare lazy dot-star only stops
    // at the first spot where the rest of the pattern happens to match, so a heading that doesn't
    // end in "?" lets it swallow everything up to the next unrelated "?" anywhere later in the page
    // into one bogus giant "question". Bounding first, then checking for "?" on the isolated result,
    // makes a non-question heading fail to match at all rather than leak into whatever comes after it.
    if ($include_heading_pairs && preg_match_all('/<h[23][^>]*>((?:(?!<\/h[23]>).)*)<\/h[23]>\s*<p[^>]*>((?:(?!<\/p>).)*)<\/p>/is', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $questions[] = array($match[1], $match[2]);
        }
    }

    if (preg_match_all('/<details[^>]*>\s*<summary[^>]*>((?:(?!<\/summary>).)*)<\/summary>((?:(?!<\/details>).)*)<\/details>/is', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $questions[] = array($match[1], $match[2]);
        }
    }

    $faq_entries = array();
    foreach ($questions as $pair) {
        $question = trim(wp_strip_all_tags($pair[0]));
        $answer   = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($pair[1])));
        if ('' === $question || '?' !== substr($question, -1) || '' === $answer) {
            continue;
        }
        $faq_entries[] = array(
            '@type'          => 'Question',
            'name'           => $question,
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $answer,
            ),
        );
    }

    if (empty($faq_entries)) {
        return null;
    }

    return array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $faq_entries,
    );
}

// ── robots.txt: allow AI crawlers (GEO/AEO) ─────────────────────────────────

add_filter('robots_txt', 'ajnanda_seo_robots_txt', 10, 2);
function ajnanda_seo_robots_txt($output, $public) {
    if (! $public) {
        return $output;
    }

    if (class_exists('AJNanda_Search_AI_Crawler_Registry')) {
        $policy = AJNanda_Search_AI_Content_Policy::settings();
        $blocked_paths = array();
        if (! empty($policy['effects']['automated_crawlers'])) {
            $blocked_paths = $policy['excluded_paths'];
            foreach ($policy['excluded_post_ids'] as $post_id) {
                $path = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);
                if ($path) { $blocked_paths[] = $path; }
            }
            $blocked_paths = array_values(array_unique($blocked_paths));
            if ($blocked_paths) {
                $output .= "\n# Explicit content exclusions for automated crawlers\nUser-agent: *\n";
                foreach ($blocked_paths as $path) { $output .= 'Disallow: ' . $path . "\n"; }
                $output .= "\n";
            }
        }
        $output .= "\n# AI crawler policy (AJNanda Search & AI)\n";
        foreach (AJNanda_Search_AI_Crawler_Registry::all() as $crawler) {
            if (empty($crawler['robots_control']) || empty($crawler['token'])) {
                continue;
            }
            if (AJNanda_Search_AI_Crawler_Registry::category_allowed($crawler['category'])) { continue; }
            $output .= 'User-agent: ' . $crawler['token'] . "\nDisallow: /\n\n";
        }
        return $output;
    }

    if (get_theme_mod('seo_allow_ai_crawlers', true)) {
        $output .= "\n# AI answer engine crawlers — explicitly allowed (AJNanda SEO Settings)\n";
        foreach (array('GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'CCBot') as $bot) {
            $output .= "User-agent: {$bot}\nAllow: /\n\n";
        }
    }

    return $output;
}

// ── /llms.txt ────────────────────────────────────────────────────────────────

add_action('template_redirect', 'ajnanda_seo_maybe_serve_llms_txt');
function ajnanda_seo_maybe_serve_llms_txt() {
    $enabled = class_exists('AJNanda_Search_AI_Discovery_Files')
        ? AJNanda_Search_AI_Discovery_Files::llms_enabled()
        : get_theme_mod('seo_llms_txt_enabled', true);
    if (! $enabled) {
        return;
    }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
    if ('/llms.txt' !== $path) {
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo ajnanda_seo_render_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}

function ajnanda_seo_render_llms_txt() {
    if (class_exists('AJNanda_Search_AI_Discovery_Files')) {
        return AJNanda_Search_AI_Discovery_Files::render_llms_txt();
    }
    $site_name = get_bloginfo('name') ?: wp_parse_url(home_url(), PHP_URL_HOST);
    $lines   = array();
    $lines[] = '# ' . $site_name;
    $lines[] = '';
    if (get_bloginfo('description')) {
        $lines[] = '> ' . get_bloginfo('description');
        $lines[] = '';
    }

    $lines[] = '## Pages';
    $pages = get_pages(array('sort_column' => 'menu_order', 'number' => 30));
    foreach ($pages as $page) {
        $lines[] = '- [' . $page->post_title . '](' . get_permalink($page->ID) . ')';
    }
    $lines[] = '';

    $lines[] = '## Recent Posts';
    $recent = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 20));
    foreach ($recent as $post) {
        $lines[] = '- [' . $post->post_title . '](' . get_permalink($post->ID) . ')';
    }

    return implode("\n", $lines) . "\n";
}

// ── Per-post SEO meta box ────────────────────────────────────────────────────

add_action('add_meta_boxes', 'ajnanda_seo_add_meta_box');
function ajnanda_seo_add_meta_box() {
    add_meta_box(
        'ajnanda_seo',
        __('SEO', 'ajnanda'),
        'ajnanda_seo_render_meta_box',
        array('post', 'page'),
        'normal',
        'default'
    );
}

function ajnanda_seo_render_meta_box($post) {
    wp_nonce_field('ajnanda_seo_save', 'ajnanda_seo_nonce');

    $title       = get_post_meta($post->ID, '_ajnanda_seo_title', true);
    $description = get_post_meta($post->ID, '_ajnanda_seo_description', true);
    $image       = get_post_meta($post->ID, '_ajnanda_seo_social_image', true);
    $noindex     = get_post_meta($post->ID, '_ajnanda_seo_noindex', true);
    ?>
    <p>
        <label for="ajnanda_seo_title"><strong><?php esc_html_e('SEO Title', 'ajnanda'); ?></strong></label><br>
        <input type="text" id="ajnanda_seo_title" name="ajnanda_seo_title" value="<?php echo esc_attr($title); ?>" style="width:100%;" placeholder="<?php echo esc_attr(get_the_title($post)); ?>">
    </p>
    <p>
        <label for="ajnanda_seo_description"><strong><?php esc_html_e('Meta Description', 'ajnanda'); ?></strong></label><br>
        <textarea id="ajnanda_seo_description" name="ajnanda_seo_description" rows="3" style="width:100%;"><?php echo esc_textarea($description); ?></textarea>
    </p>
    <p>
        <label for="ajnanda_seo_social_image"><strong><?php esc_html_e('Social Share Image', 'ajnanda'); ?></strong></label><br>
        <input type="text" id="ajnanda_seo_social_image" name="ajnanda_seo_social_image" value="<?php echo esc_url($image); ?>" style="width:70%;">
        <button type="button" class="button" id="ajnanda_seo_social_image_button"><?php esc_html_e('Choose Image', 'ajnanda'); ?></button>
    </p>
    <p>
        <label><input type="checkbox" name="ajnanda_seo_noindex" value="1" <?php checked($noindex, '1'); ?>> <?php esc_html_e('Hide from search engines (noindex)', 'ajnanda'); ?></label>
    </p>
    <script>
    (function () {
        var btn = document.getElementById('ajnanda_seo_social_image_button');
        var input = document.getElementById('ajnanda_seo_social_image');
        if (!btn || !input || typeof wp === 'undefined' || !wp.media) { return; }
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var frame = wp.media({ title: <?php echo wp_json_encode(__('Choose Social Share Image', 'ajnanda')); ?>, multiple: false });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                input.value = attachment.url;
            });
            frame.open();
        });
    })();
    </script>
    <?php
}

add_action('save_post', 'ajnanda_seo_save_meta_box');
function ajnanda_seo_save_meta_box($post_id) {
    if (! isset($_POST['ajnanda_seo_nonce']) || ! wp_verify_nonce($_POST['ajnanda_seo_nonce'], 'ajnanda_seo_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    update_post_meta($post_id, '_ajnanda_seo_title', sanitize_text_field(wp_unslash($_POST['ajnanda_seo_title'] ?? '')));
    update_post_meta($post_id, '_ajnanda_seo_description', sanitize_textarea_field(wp_unslash($_POST['ajnanda_seo_description'] ?? '')));
    update_post_meta($post_id, '_ajnanda_seo_social_image', esc_url_raw(wp_unslash($_POST['ajnanda_seo_social_image'] ?? '')));
    update_post_meta($post_id, '_ajnanda_seo_noindex', isset($_POST['ajnanda_seo_noindex']) ? '1' : '');
}

add_action('admin_enqueue_scripts', 'ajnanda_seo_admin_enqueue_media');
function ajnanda_seo_admin_enqueue_media($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_media();
    }
}
