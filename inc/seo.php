<?php
/**
 * Theme-native SEO, GEO/AEO, and Site Kit insights.
 *
 * Site-wide settings and the Site Kit insights panel live in the Customizer, matching the rest of
 * this theme's settings (see ajnanda_customize_register() in functions.php). Per-post overrides
 * can't live there — the Customizer only edits site-wide theme_mods, not individual post data — so
 * those get a small meta box on the post/page editor instead, the only place WordPress allows it.
 *
 * @package NCLLC_Pro
 */

if (! defined('ABSPATH')) {
    exit;
}

// ── Customizer: SEO Settings + SEO Insights ────────────────────────────────

add_action('customize_register', 'ajnanda_seo_customize_register');
function ajnanda_seo_customize_register($wp_customize) {
    if (class_exists('WP_Customize_Control') && ! class_exists('AJNanda_SEO_Insights_Control')) {
        class AJNanda_SEO_Insights_Control extends WP_Customize_Control {
            public $type = 'ajnanda_seo_insights';

            public function render_content() {
                ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                <div class="ajnanda-seo-insights">
                    <?php echo ajnanda_seo_render_site_kit_insights(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <?php
            }
        }
    }

    $wp_customize->add_section('ajnanda_seo', array(
        'title'    => __('SEO Settings', 'ajnanda'),
        'priority' => 28,
    ));

    $wp_customize->add_setting('seo_meta_description_default', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('seo_meta_description_default', array(
        'label'       => __('Default Meta Description', 'ajnanda'),
        'description' => __('Used on pages/posts that don\'t set their own (see the SEO box on the post editor).', 'ajnanda'),
        'section'     => 'ajnanda_seo',
        'type'        => 'textarea',
    ));

    $wp_customize->add_setting('seo_default_social_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));
    if (class_exists('WP_Customize_Image_Control')) {
        $wp_customize->add_control(new WP_Customize_Image_Control(
            $wp_customize,
            'seo_default_social_image',
            array(
                'label'       => __('Default Social Share Image', 'ajnanda'),
                'description' => __('Used for Open Graph/Twitter previews when a post has no featured image.', 'ajnanda'),
                'section'     => 'ajnanda_seo',
            )
        ));
    }

    $wp_customize->add_setting('seo_twitter_handle', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('seo_twitter_handle', array(
        'label'   => __('Twitter/X Handle', 'ajnanda'),
        'section' => 'ajnanda_seo',
        'type'    => 'text',
        'input_attrs' => array('placeholder' => '@yourbusiness'),
    ));

    $wp_customize->add_setting('seo_business_phone', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('seo_business_phone', array(
        'label'       => __('Business Phone', 'ajnanda'),
        'description' => __('Optional. Filling this in along with Business Address upgrades the schema markup from generic Organization to LocalBusiness.', 'ajnanda'),
        'section'     => 'ajnanda_seo',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('seo_business_address', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('seo_business_address', array(
        'label'   => __('Business Address', 'ajnanda'),
        'section' => 'ajnanda_seo',
        'type'    => 'text',
    ));

    $seo_toggles = array(
        'seo_schema_enabled'    => array(
            'label'       => __('Enable Schema Markup', 'ajnanda'),
            'description' => __('Adds structured data (Organization/LocalBusiness, Article, FAQ) that search engines and AI answer engines use to understand and cite your content.', 'ajnanda'),
        ),
        'seo_allow_ai_crawlers' => array(
            'label'       => __('Allow AI Crawlers (GEO/AEO)', 'ajnanda'),
            'description' => __('Explicitly allows GPTBot, ClaudeBot, PerplexityBot, Google-Extended, and CCBot in robots.txt, so ChatGPT/Claude/Perplexity/Google AI Overviews can crawl and cite this site.', 'ajnanda'),
        ),
        'seo_llms_txt_enabled'  => array(
            'label'       => __('Publish /llms.txt', 'ajnanda'),
            'description' => __('A plain-text summary of your site for AI tools that support the emerging llms.txt convention.', 'ajnanda'),
        ),
    );

    foreach ($seo_toggles as $setting_id => $control) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => true,
            'sanitize_callback' => 'ajnanda_sanitize_checkbox',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($setting_id, array(
            'label'       => $control['label'],
            'description' => $control['description'],
            'section'     => 'ajnanda_seo',
            'type'        => 'checkbox',
        ));
    }

    // Read-only info control confirming WordPress core's native sitemap (no custom generator needed).
    $wp_customize->add_setting('seo_sitemap_info', array(
        'default'           => '',
        'sanitize_callback' => '__return_false',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('seo_sitemap_info', array(
        'label'       => __('Sitemap', 'ajnanda'),
        'description' => sprintf(
            /* translators: %s: sitemap URL */
            __('WordPress already publishes a sitemap automatically: %s', 'ajnanda'),
            '<a href="' . esc_url(home_url('/wp-sitemap.xml')) . '" target="_blank" rel="noopener">' . esc_html(home_url('/wp-sitemap.xml')) . '</a>'
        ),
        'section' => 'ajnanda_seo',
        'type'    => 'hidden',
    ));

    // ── SEO Insights (Site Kit) ─────────────────────────────────────────────
    $wp_customize->add_section('ajnanda_seo_insights', array(
        'title'    => __('SEO Insights', 'ajnanda'),
        'priority' => 29,
    ));

    $wp_customize->add_setting('seo_insights_display', array(
        'default'           => '',
        'sanitize_callback' => '__return_false',
        'transport'         => 'refresh',
    ));

    if (class_exists('AJNanda_SEO_Insights_Control')) {
        $wp_customize->add_control(new AJNanda_SEO_Insights_Control(
            $wp_customize,
            'seo_insights_display',
            array(
                'label'   => __('Suggestions from Google Site Kit', 'ajnanda'),
                'section' => 'ajnanda_seo_insights',
            )
        ));
    }
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
    if ($noindex) {
        echo '<meta name="robots" content="noindex,follow">' . "\n";
    }

    $description = $post_id ? get_post_meta($post_id, '_ajnanda_seo_description', true) : '';
    if (! $description && $is_singular) {
        $description = wp_strip_all_tags(get_the_excerpt($post_id));
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
    $og_title = $is_singular ? get_the_title($post_id) : (get_bloginfo('name') ?: wp_parse_url(home_url(), PHP_URL_HOST));
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
 * Organization/LocalBusiness on the front page, Article + auto-detected FAQPage on single posts.
 * FAQPage detection looks for "heading ending in a question mark, followed by a paragraph" in the
 * post content — no manual Q&A entry required. This is the main GEO/AEO win: structured Q&A that
 * AI answer engines (and Google's AI Overviews) can lift directly, without any content-authoring
 * changes beyond writing headings as questions, which many posts already do naturally.
 */
function ajnanda_seo_output_schema($is_singular, $post_id) {
    $graphs = array();

    if (is_front_page() || is_home()) {
        $phone   = get_theme_mod('seo_business_phone', '');
        $address = get_theme_mod('seo_business_address', '');
        $logo    = get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '';

        $business = array(
            '@context' => 'https://schema.org',
            '@type'    => ($phone && $address) ? 'LocalBusiness' : 'Organization',
            'name'     => get_bloginfo('name') ?: wp_parse_url(home_url(), PHP_URL_HOST),
            'url'      => home_url('/'),
        );
        if (get_bloginfo('description')) {
            $business['description'] = get_bloginfo('description');
        }
        if ($logo) {
            $business['logo'] = $logo;
        }
        if ($phone) {
            $business['telephone'] = $phone;
        }
        if ($address) {
            $business['address'] = $address;
        }
        $graphs[] = $business;
    }

    if ($is_singular && 'post' === get_post_type($post_id)) {
        $image = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'large') : get_theme_mod('seo_default_social_image', '');
        $article = array(
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => get_the_title($post_id),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified'  => get_the_modified_date('c', $post_id),
            'author'        => array(
                '@type' => 'Person',
                'name'  => get_the_author_meta('display_name', get_post_field('post_author', $post_id)),
            ),
        );
        if ($image) {
            $article['image'] = $image;
        }
        $graphs[] = $article;

        $faq = ajnanda_seo_extract_faq_schema($post_id);
        if ($faq) {
            $graphs[] = $faq;
        }
    }

    foreach ($graphs as $graph) {
        echo '<script type="application/ld+json">' . wp_json_encode($graph, JSON_UNESCAPED_SLASHES) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

/**
 * Scans rendered post content for "<h2/h3>...question?</h2> <p>answer</p>" pairs and turns them
 * into FAQPage JSON-LD. Deliberately simple (no block-editor-specific parsing) so it works
 * regardless of which block/editor produced the HTML.
 */
function ajnanda_seo_extract_faq_schema($post_id) {
    $content = apply_filters('the_content', get_post_field('post_content', $post_id));
    if (! preg_match_all('/<h[23][^>]*>(.*?\?)\s*<\/h[23]>\s*<p[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER)) {
        return null;
    }

    $questions = array();
    foreach ($matches as $match) {
        $question = trim(wp_strip_all_tags($match[1]));
        $answer   = trim(wp_strip_all_tags($match[2]));
        if ('' === $question || '' === $answer) {
            continue;
        }
        $questions[] = array(
            '@type'          => 'Question',
            'name'           => $question,
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $answer,
            ),
        );
    }

    if (empty($questions)) {
        return null;
    }

    return array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $questions,
    );
}

// ── robots.txt: allow AI crawlers (GEO/AEO) ─────────────────────────────────

add_filter('robots_txt', 'ajnanda_seo_robots_txt', 10, 2);
function ajnanda_seo_robots_txt($output, $public) {
    if (! $public || ! get_theme_mod('seo_allow_ai_crawlers', true)) {
        return $output;
    }

    $ai_bots = array('GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'CCBot');
    $output .= "\n# AI answer engine crawlers — explicitly allowed (AJNanda SEO Settings)\n";
    foreach ($ai_bots as $bot) {
        $output .= "User-agent: {$bot}\nAllow: /\n\n";
    }

    return $output;
}

// ── /llms.txt ────────────────────────────────────────────────────────────────

add_action('template_redirect', 'ajnanda_seo_maybe_serve_llms_txt');
function ajnanda_seo_maybe_serve_llms_txt() {
    if (! get_theme_mod('seo_llms_txt_enabled', true)) {
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
