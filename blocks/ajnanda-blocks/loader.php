<?php
/**
 * AJNanda native Gutenberg block library.
 *
 * @package NCLLC_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

function ajnanda_blocks_asset_version($relative_path) {
    $path = get_template_directory() . '/blocks/ajnanda-blocks/' . ltrim($relative_path, '/');

    return file_exists($path) ? (string) filemtime($path) : wp_get_theme()->get('Version');
}

function ajnanda_blocks_register_category($categories) {
    foreach ($categories as $category) {
        if (!empty($category['slug']) && 'ajnanda-blocks' === $category['slug']) {
            return $categories;
        }
    }

    array_unshift($categories, array(
        'slug'  => 'ajnanda-blocks',
        'title' => __('AJNanda Blocks', 'ajnanda'),
        'icon'  => null,
    ));

    return $categories;
}
add_filter('block_categories_all', 'ajnanda_blocks_register_category');

function ajnanda_blocks_register_assets() {
    $base_uri = get_template_directory_uri() . '/blocks/ajnanda-blocks';

    wp_register_style(
        'ajnanda-blocks-style',
        $base_uri . '/style.css',
        array(),
        ajnanda_blocks_asset_version('style.css')
    );

    wp_register_style(
        'ajnanda-blocks-editor-style',
        $base_uri . '/editor.css',
        array('ajnanda-blocks-style'),
        ajnanda_blocks_asset_version('editor.css')
    );

    wp_register_script(
        'ajnanda-blocks-editor',
        $base_uri . '/index.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n', 'wp-server-side-render'),
        ajnanda_blocks_asset_version('index.js'),
        true
    );

    wp_register_script(
        'ajnanda-blocks-frontend',
        $base_uri . '/frontend.js',
        array(),
        ajnanda_blocks_asset_version('frontend.js'),
        true
    );
}
add_action('init', 'ajnanda_blocks_register_assets');

function ajnanda_blocks_enqueue_frontend_assets() {
    wp_enqueue_style('ajnanda-blocks-style');
    wp_enqueue_script('ajnanda-blocks-frontend');
}
add_action('wp_enqueue_scripts', 'ajnanda_blocks_enqueue_frontend_assets');

function ajnanda_blocks_enqueue_editor_assets() {
    wp_enqueue_script('ajnanda-blocks-editor');
    wp_enqueue_style('ajnanda-blocks-editor-style');
}
add_action('enqueue_block_editor_assets', 'ajnanda_blocks_enqueue_editor_assets');

function ajnanda_blocks_attrs($attrs, $defaults = array()) {
    return wp_parse_args(is_array($attrs) ? $attrs : array(), $defaults);
}

function ajnanda_blocks_post_query_args($attrs) {
    $allowed_orderby = array('date', 'title', 'menu_order');
    $order_by = in_array($attrs['orderBy'], $allowed_orderby, true) ? $attrs['orderBy'] : 'date';
    $order = ('asc' === strtolower((string) $attrs['order'])) ? 'ASC' : 'DESC';

    return array(
        'post_type'           => 'post',
        'posts_per_page'      => max(1, min(12, absint($attrs['count']))),
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'orderby'             => $order_by,
        'order'               => $order,
    );
}

function ajnanda_blocks_posts_style($attrs) {
    $columns = max(1, min(6, absint($attrs['columns'])));

    return '--aj-columns:' . $columns;
}

function ajnanda_blocks_render_posts($attrs) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'count' => 3,
        'showExcerpt' => true,
        'showImage' => true,
        'buttonText' => __('Read More', 'ajnanda'),
        'order' => 'desc',
        'orderBy' => 'date',
        'columns' => 3,
    ));

    $query = new WP_Query(ajnanda_blocks_post_query_args($attrs));

    if (!$query->have_posts()) {
        return '<div class="aj-block aj-posts"><p>' . esc_html__('No posts found.', 'ajnanda') . '</p></div>';
    }

    ob_start();
    ?>
    <div class="aj-block aj-posts" style="<?php echo esc_attr(ajnanda_blocks_posts_style($attrs)); ?>">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <article class="aj-post-card">
                <?php if (!empty($attrs['showImage']) && has_post_thumbnail()) : ?>
                    <a class="aj-post-card__image" href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium_large'); ?>
                    </a>
                <?php endif; ?>
                <div class="aj-post-card__body">
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php if (!empty($attrs['showExcerpt'])) : ?>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
                    <?php endif; ?>
                    <a class="aj-button" href="<?php the_permalink(); ?>"><?php echo esc_html($attrs['buttonText']); ?></a>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();

    return ob_get_clean();
}

function ajnanda_blocks_render_posts_variant($attrs, $variant = 'grid') {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'count' => 6,
        'showExcerpt' => true,
        'showImage' => true,
        'buttonText' => __('Read More', 'ajnanda'),
        'order' => 'desc',
        'orderBy' => 'date',
        'columns' => 3,
        'dateFormat' => get_option('date_format'),
    ));

    $query = new WP_Query(ajnanda_blocks_post_query_args($attrs));

    if (!$query->have_posts()) {
        return '<div class="aj-block aj-posts aj-posts--' . esc_attr($variant) . '"><p>' . esc_html__('No posts found.', 'ajnanda') . '</p></div>';
    }

    $cards = '';
    while ($query->have_posts()) {
        $query->the_post();
        ob_start();
        ?>
        <article class="aj-post-card">
            <?php if ('timeline' === $variant) : ?>
                <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date($attrs['dateFormat'])); ?></time>
            <?php endif; ?>
            <?php if (!empty($attrs['showImage']) && has_post_thumbnail()) : ?>
                <a class="aj-post-card__image" href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('medium_large'); ?>
                </a>
            <?php endif; ?>
            <div class="aj-post-card__body">
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <?php if (!empty($attrs['showExcerpt'])) : ?>
                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
                <?php endif; ?>
                <a class="aj-button" href="<?php the_permalink(); ?>"><?php echo esc_html($attrs['buttonText']); ?></a>
            </div>
        </article>
        <?php
        $cards .= ob_get_clean();
    }
    wp_reset_postdata();

    // Carousel variant: hand the cards to the shared accessible carousel
    // (carousel.php/js) — the same one ajnanda/slider and the review blocks use —
    // instead of a plain scroll strip.
    if ('carousel' === $variant && function_exists('ajnanda_carousel_markup')) {
        return '<div class="aj-block aj-posts aj-posts--carousel" style="' . esc_attr(ajnanda_blocks_posts_style($attrs)) . '">'
            . ajnanda_carousel_markup($cards, array(
                'label'    => __('Posts', 'ajnanda'),
                'autoplay' => !empty($attrs['autoplay']),
                'interval' => max(3000, absint($attrs['delay'] ?? 4) * 1000),
                'dots'     => true,
            ))
            . '</div>';
    }

    return '<div class="aj-block aj-posts aj-posts--' . esc_attr($variant) . '" style="' . esc_attr(ajnanda_blocks_posts_style($attrs)) . '">' . $cards . '</div>';
}

function ajnanda_blocks_render_post_grid($attrs) {
    return ajnanda_blocks_render_posts_variant($attrs, 'grid');
}

function ajnanda_blocks_render_post_carousel($attrs) {
    return ajnanda_blocks_render_posts_variant($attrs, 'carousel');
}

function ajnanda_blocks_render_post_timeline($attrs) {
    return ajnanda_blocks_render_posts_variant($attrs, 'timeline');
}

function ajnanda_blocks_render_taxonomy_list($attrs) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'taxonomy' => 'category',
        'layout' => 'pills',
        'hideEmpty' => false,
        'showCount' => false,
    ));

    $taxonomy = taxonomy_exists($attrs['taxonomy']) ? $attrs['taxonomy'] : 'category';
    $layout = in_array($attrs['layout'], array('pills', 'list', 'inline'), true) ? $attrs['layout'] : 'pills';
    $terms = get_terms(array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => !empty($attrs['hideEmpty']),
        'number'     => 24,
    ));

    if (is_wp_error($terms) || empty($terms)) {
        return '<nav class="aj-block aj-taxonomy-list"><p>' . esc_html__('No terms found.', 'ajnanda') . '</p></nav>';
    }

    $items = array();

    foreach ($terms as $term) {
        $label = $term->name;

        if (!empty($attrs['showCount'])) {
            $label .= ' (' . absint($term->count) . ')';
        }

        $items[] = sprintf(
            '<li><a href="%1$s">%2$s</a></li>',
            esc_url(get_term_link($term)),
            esc_html($label)
        );
    }

    return '<nav class="aj-block aj-taxonomy-list aj-taxonomy-list--' . esc_attr($layout) . '"><ul>' . implode('', $items) . '</ul></nav>';
}

function ajnanda_blocks_render_search($attrs) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'placeholder' => __('Search...', 'ajnanda'),
        'buttonText' => __('Search', 'ajnanda'),
        'layout' => 'inline',
        'buttonPosition' => 'right',
    ));
    $layout = in_array($attrs['layout'], array('inline', 'stacked'), true) ? $attrs['layout'] : 'inline';
    $button_position = ('left' === $attrs['buttonPosition']) ? 'left' : 'right';

    ob_start();
    ?>
    <form class="aj-block aj-search aj-search--<?php echo esc_attr($layout); ?> aj-search--button-<?php echo esc_attr($button_position); ?>" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <label class="screen-reader-text" for="aj-search-field"><?php esc_html_e('Search for:', 'ajnanda'); ?></label>
        <input id="aj-search-field" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php echo esc_attr($attrs['placeholder']); ?>">
        <button type="submit"><?php echo esc_html($attrs['buttonText']); ?></button>
    </form>
    <?php

    return ob_get_clean();
}

function ajnanda_blocks_render_menu($attrs) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'menuLocation' => 'primary',
        'layout' => 'horizontal',
        'depth' => 2,
        'dropdownOnHover' => true,
    ));

    $location = sanitize_key($attrs['menuLocation']);
    $layout = in_array($attrs['layout'], array('horizontal', 'vertical'), true) ? $attrs['layout'] : 'horizontal';
    $depth = max(1, min(4, absint($attrs['depth'])));
    $hover_class = !empty($attrs['dropdownOnHover']) ? ' aj-nav-menu--hover' : '';

    ob_start();
    wp_nav_menu(array(
        'theme_location' => $location,
        'container'      => 'nav',
        'container_class'=> 'aj-block aj-nav-menu aj-nav-menu--' . $layout . $hover_class,
        'fallback_cb'    => false,
        'depth'          => $depth,
    ));
    $output = ob_get_clean();

    if (!$output) {
        return '<nav class="aj-block aj-nav-menu"><p>' . esc_html__('Assign a menu to this location first.', 'ajnanda') . '</p></nav>';
    }

    return $output;
}

function ajnanda_blocks_render_toc($attrs, $content, $block) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'title' => __('On this page', 'ajnanda'),
        'minLevel' => 2,
        'maxLevel' => 3,
        'ordered' => true,
        'collapsible' => false,
    ));
    $post = get_post();

    if (!$post) {
        return '';
    }

    $min_level = max(1, min(6, absint($attrs['minLevel'])));
    $max_level = max($min_level, min(6, absint($attrs['maxLevel'])));
    preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', $post->post_content, $matches, PREG_SET_ORDER);

    if (!$matches) {
        return '<nav class="aj-block aj-toc"><p>' . esc_html__('Add headings to generate a table of contents.', 'ajnanda') . '</p></nav>';
    }

    $items = array();

    foreach ($matches as $match) {
        $level = absint($match[1]);

        if ($level < $min_level || $level > $max_level) {
            continue;
        }

        $text = wp_strip_all_tags($match[2]);
        $slug = sanitize_title($text);

        if ($text) {
            $items[] = sprintf(
                '<li class="aj-toc__level-%1$d"><a href="#%2$s">%3$s</a></li>',
                $level,
                esc_attr($slug),
                esc_html($text)
            );
        }
    }

    if (!$items) {
        return '';
    }

    $list_tag = !empty($attrs['ordered']) ? 'ol' : 'ul';
    $class = 'aj-block aj-toc' . (!empty($attrs['collapsible']) ? ' aj-toc--collapsible' : '');

    return '<nav class="' . esc_attr($class) . '"><strong>' . esc_html($attrs['title']) . '</strong><' . $list_tag . '>' . implode('', $items) . '</' . $list_tag . '></nav>';
}

function ajnanda_blocks_add_heading_anchor($block_content, $block) {
    if (empty($block['blockName']) || 'core/heading' !== $block['blockName']) {
        return $block_content;
    }

    $post = get_post();

    if (!$post || false === strpos($post->post_content, '<!-- wp:ajnanda/table-of-contents')) {
        return $block_content;
    }

    if (false !== stripos($block_content, ' id=')) {
        return $block_content;
    }

    if (!preg_match('/<h([1-6])([^>]*)>(.*?)<\/h[1-6]>/i', $block_content, $match)) {
        return $block_content;
    }

    $text = wp_strip_all_tags($match[3]);

    if (!$text) {
        return $block_content;
    }

    $id = sanitize_title($text);

    return preg_replace('/<h([1-6])([^>]*)>/i', '<h$1$2 id="' . esc_attr($id) . '">', $block_content, 1);
}
add_filter('render_block', 'ajnanda_blocks_add_heading_anchor', 10, 2);

function ajnanda_blocks_render_login_placeholder($attrs) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'loggedOutText' => __('Login area placeholder.', 'ajnanda'),
        'loginText' => __('Log In', 'ajnanda'),
        'logoutText' => __('Log Out', 'ajnanda'),
    ));

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();

        return '<div class="aj-block aj-login-placeholder"><p>' . esc_html(sprintf(__('Signed in as %s.', 'ajnanda'), $current_user->display_name)) . '</p><a class="aj-button" href="' . esc_url(wp_logout_url()) . '">' . esc_html($attrs['logoutText']) . '</a></div>';
    }

    return '<div class="aj-block aj-login-placeholder"><p>' . esc_html($attrs['loggedOutText']) . '</p><a class="aj-button" href="' . esc_url(wp_login_url()) . '">' . esc_html($attrs['loginText']) . '</a></div>';
}

function ajnanda_blocks_render_svg($attrs) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'svg' => '<svg viewBox="0 0 80 80" role="img" aria-label="Circle"><circle cx="40" cy="40" r="32"/></svg>',
    ));

    $allowed_svg = array(
        'svg' => array(
            'aria-hidden' => true,
            'aria-label' => true,
            'class' => true,
            'fill' => true,
            'focusable' => true,
            'height' => true,
            'role' => true,
            'stroke' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'stroke-width' => true,
            'viewbox' => true,
            'viewBox' => true,
            'width' => true,
            'xmlns' => true,
        ),
        'circle' => array('cx' => true, 'cy' => true, 'fill' => true, 'r' => true, 'stroke' => true, 'stroke-width' => true),
        'ellipse' => array('cx' => true, 'cy' => true, 'fill' => true, 'rx' => true, 'ry' => true, 'stroke' => true, 'stroke-width' => true),
        'g' => array('class' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'transform' => true),
        'line' => array('x1' => true, 'x2' => true, 'y1' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true),
        'path' => array('class' => true, 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-width' => true),
        'polygon' => array('fill' => true, 'points' => true, 'stroke' => true, 'stroke-width' => true),
        'polyline' => array('fill' => true, 'points' => true, 'stroke' => true, 'stroke-width' => true),
        'rect' => array('fill' => true, 'height' => true, 'rx' => true, 'ry' => true, 'stroke' => true, 'stroke-width' => true, 'width' => true, 'x' => true, 'y' => true),
        'title' => array(),
    );

    return '<div class="aj-block aj-svg">' . wp_kses($attrs['svg'], $allowed_svg) . '</div>';
}

function ajnanda_blocks_render_slide($attrs, $content) {
    return '<div class="swiper-slide aj-slide">' . $content . '</div>';
}

function ajnanda_blocks_render_slider($attrs, $content) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'loop'       => true,
        'autoplay'   => false,
        'delay'      => 4000,
        'speed'      => 400,
        'effect'     => 'slide',
        'showArrows' => true,
        'showDots'   => true,
    ));

    return '<div class="aj-block aj-slider aj-slider--accessible">' . ajnanda_carousel_markup($content, array(
        'label' => __('Content slider', 'ajnanda'),
        'autoplay' => !empty($attrs['autoplay']),
        'interval' => $attrs['delay'],
        'dots' => !empty($attrs['showDots']),
        'loop' => !empty($attrs['loop']),
        'effect' => $attrs['effect'],
        'speed' => $attrs['speed'],
    )) . '</div>';
}

function ajnanda_blocks_register_dynamic_blocks() {
    $post_attributes = array(
        'count' => array('type' => 'number', 'default' => 6),
        'showExcerpt' => array('type' => 'boolean', 'default' => true),
        'showImage' => array('type' => 'boolean', 'default' => true),
        'buttonText' => array('type' => 'string', 'default' => __('Read More', 'ajnanda')),
        'order' => array('type' => 'string', 'default' => 'desc'),
        'orderBy' => array('type' => 'string', 'default' => 'date'),
        'columns' => array('type' => 'number', 'default' => 3),
    );

    $dynamic_blocks = array(
        'ajnanda/svg' => array(
            'callback' => 'ajnanda_blocks_render_svg',
            'attributes' => array('svg' => array('type' => 'string', 'default' => '<svg viewBox="0 0 80 80" role="img" aria-label="Circle"><circle cx="40" cy="40" r="32"/></svg>')),
        ),
        'ajnanda/posts' => array(
            'callback' => 'ajnanda_blocks_render_posts',
            'attributes' => array_merge($post_attributes, array('count' => array('type' => 'number', 'default' => 3))),
        ),
        'ajnanda/post-grid' => array(
            'callback' => 'ajnanda_blocks_render_post_grid',
            'attributes' => $post_attributes,
        ),
        'ajnanda/post-carousel' => array(
            'callback' => 'ajnanda_blocks_render_post_carousel',
            'attributes' => array_merge($post_attributes, array(
                'autoplay' => array('type' => 'boolean', 'default' => false),
                'delay' => array('type' => 'number', 'default' => 4),
            )),
        ),
        'ajnanda/post-timeline' => array(
            'callback' => 'ajnanda_blocks_render_post_timeline',
            'attributes' => array_merge($post_attributes, array(
                'count' => array('type' => 'number', 'default' => 5),
                'dateFormat' => array('type' => 'string', 'default' => 'M j, Y'),
            )),
        ),
        'ajnanda/search' => array(
            'callback' => 'ajnanda_blocks_render_search',
            'attributes' => array(
                'placeholder' => array('type' => 'string', 'default' => __('Search...', 'ajnanda')),
                'buttonText' => array('type' => 'string', 'default' => __('Search', 'ajnanda')),
                'layout' => array('type' => 'string', 'default' => 'inline'),
                'buttonPosition' => array('type' => 'string', 'default' => 'right'),
            ),
        ),
        'ajnanda/nav-menu' => array(
            'callback' => 'ajnanda_blocks_render_menu',
            'attributes' => array(
                'menuLocation' => array('type' => 'string', 'default' => 'primary'),
                'layout' => array('type' => 'string', 'default' => 'horizontal'),
                'depth' => array('type' => 'number', 'default' => 2),
                'dropdownOnHover' => array('type' => 'boolean', 'default' => true),
            ),
        ),
        'ajnanda/table-of-contents' => array(
            'callback' => 'ajnanda_blocks_render_toc',
            'attributes' => array(
                'title' => array('type' => 'string', 'default' => __('On this page', 'ajnanda')),
                'minLevel' => array('type' => 'number', 'default' => 2),
                'maxLevel' => array('type' => 'number', 'default' => 3),
                'ordered' => array('type' => 'boolean', 'default' => true),
                'collapsible' => array('type' => 'boolean', 'default' => false),
            ),
        ),
        'ajnanda/taxonomy-list' => array(
            'callback' => 'ajnanda_blocks_render_taxonomy_list',
            'attributes' => array(
                'taxonomy' => array('type' => 'string', 'default' => 'category'),
                'layout' => array('type' => 'string', 'default' => 'pills'),
                'hideEmpty' => array('type' => 'boolean', 'default' => false),
                'showCount' => array('type' => 'boolean', 'default' => false),
            ),
        ),
        'ajnanda/login-placeholder' => array(
            'callback' => 'ajnanda_blocks_render_login_placeholder',
            'attributes' => array(
                'loggedOutText' => array('type' => 'string', 'default' => __('Login area placeholder.', 'ajnanda')),
                'loginText' => array('type' => 'string', 'default' => __('Log In', 'ajnanda')),
                'logoutText' => array('type' => 'string', 'default' => __('Log Out', 'ajnanda')),
            ),
        ),
        'ajnanda/slide' => array(
            'callback'   => 'ajnanda_blocks_render_slide',
            'attributes' => array(),
        ),
        'ajnanda/slider' => array(
            'callback'   => 'ajnanda_blocks_render_slider',
            'attributes' => array(
                'loop'       => array('type' => 'boolean', 'default' => true),
                'autoplay'   => array('type' => 'boolean', 'default' => false),
                'delay'      => array('type' => 'number',  'default' => 4000),
                'speed'      => array('type' => 'number',  'default' => 400),
                'effect'     => array('type' => 'string',  'default' => 'slide'),
                'showArrows' => array('type' => 'boolean', 'default' => true),
                'showDots'   => array('type' => 'boolean', 'default' => true),
            ),
        ),
    );

    foreach ($dynamic_blocks as $name => $block) {
        register_block_type($name, array(
            'editor_script'   => 'ajnanda-blocks-editor',
            'editor_style'    => 'ajnanda-blocks-editor-style',
            'style'           => 'ajnanda-blocks-style',
            'attributes'      => $block['attributes'],
            'render_callback' => $block['callback'],
        ));
    }
}
add_action('init', 'ajnanda_blocks_register_dynamic_blocks');

// ---------------------------------------------------------------------------
// Frontend: normalize AJ Buttons classes/styles on render.
//
// This protects the frontend when the editor preview is correct but the saved
// core/buttons wrapper still has stale WordPress layout classes such as
// is-vertical or older AJ layout classes. The block attributes are the source
// of truth; render_block rebuilds the wrapper classes every time the page is
// rendered.
// ---------------------------------------------------------------------------

add_filter('render_block', 'ajnanda_render_core_buttons_block', 10, 2);

function ajnanda_render_core_buttons_block($block_content, $block) {
    if (empty($block_content)) {
        return $block_content;
    }

    $block_name = $block['blockName'] ?? '';
    $attrs      = $block['attrs'] ?? [];

    if ('core/buttons' === $block_name) {
        $has_layout = ajnanda_buttons_has_layout_attrs($attrs);
        $has_shared = !empty($attrs['ajnBtnScheme']) || !empty($attrs['ajnBtnSharedBg']) || !empty($attrs['ajnBtnStyle']) || !empty($attrs['ajnBtnSizeStyle']);
        $has_per    = false;

        for ($i = 1; $i <= 6; $i++) {
            if (!empty($attrs['ajnBtnColor' . $i])) {
                $has_per = true;
                break;
            }
        }

        if (!$has_layout && !$has_shared && !$has_per) {
            return $block_content;
        }

        $classes = ajnanda_buttons_build_wrapper_classes($attrs);
        if ($classes) {
            $block_content = ajnanda_buttons_inject_wrapper_classes($block_content, $classes, 'wp-block-buttons');
        }

        $vars = ajnanda_buttons_build_css_vars($attrs);
        if ($vars) {
            $block_content = ajnanda_buttons_inject_style_vars($block_content, $vars, 'wp-block-buttons');
        }

        return $block_content;
    }

    if ('core/button' === $block_name) {
        $vars = ajnanda_single_button_build_css_vars($attrs);
        if (!$vars) {
            return $block_content;
        }

        return ajnanda_buttons_inject_style_vars($block_content, $vars, 'wp-block-button');
    }

    return $block_content;
}

function ajnanda_buttons_has_layout_attrs($attrs) {
    if (!is_array($attrs)) {
        return false;
    }

    $keys = array(
        'ajnButtonLayoutDesktop',
        'ajnButtonLayoutTablet',
        'ajnButtonLayoutMobile',
        'ajnButtonGapDesktop',
        'ajnButtonGapTablet',
        'ajnButtonGapMobile',
        'ajnButtonsWidthDesktop',
        'ajnButtonsWidthTablet',
        'ajnButtonsWidthMobile',
        'ajnButtonsCustomWidthDesktop',
        'ajnButtonsCustomWidthTablet',
        'ajnButtonsCustomWidthMobile',
        'ajnBtnJustify',
    );

    foreach ($keys as $key) {
        if (array_key_exists($key, $attrs) && '' !== $attrs[$key] && null !== $attrs[$key]) {
            return true;
        }
    }

    $class_name = isset($attrs['className']) ? (string) $attrs['className'] : '';
    return false !== strpos($class_name, 'aj-buttons-control');
}

function ajnanda_buttons_allowed_layout($value, $fallback = 'row') {
    $value = is_string($value) ? $value : '';
    return in_array($value, array('row', 'stack', 'grid', 'featured'), true) ? $value : $fallback;
}

function ajnanda_buttons_allowed_width($value, $fallback = 'auto') {
    $value = is_string($value) ? $value : '';
    return in_array($value, array('auto', 'narrow', 'standard', 'wide', 'full', 'custom'), true) ? $value : $fallback;
}

function ajnanda_buttons_allowed_justify($value) {
    $value = is_string($value) ? $value : 'center';
    return in_array($value, array('flex-start', 'center', 'flex-end', 'space-between', 'space-evenly', 'stretch'), true) ? $value : 'center';
}

function ajnanda_buttons_build_wrapper_classes($attrs) {
    $desktop_layout = ajnanda_buttons_allowed_layout($attrs['ajnButtonLayoutDesktop'] ?? 'row', 'row');
    $tablet_layout  = ajnanda_buttons_allowed_layout($attrs['ajnButtonLayoutTablet'] ?? $desktop_layout, $desktop_layout);
    $mobile_layout  = ajnanda_buttons_allowed_layout($attrs['ajnButtonLayoutMobile'] ?? $tablet_layout, $tablet_layout ?: 'stack');

    $desktop_width = ajnanda_buttons_allowed_width($attrs['ajnButtonsWidthDesktop'] ?? 'auto', 'auto');
    $tablet_width  = ajnanda_buttons_allowed_width($attrs['ajnButtonsWidthTablet'] ?? $desktop_width, $desktop_width);
    $mobile_width  = ajnanda_buttons_allowed_width($attrs['ajnButtonsWidthMobile'] ?? $tablet_width, $tablet_width);

    $classes = array(
        'aj-buttons-control',
        'aj-buttons-desktop-' . $desktop_layout,
        'aj-buttons-tablet-' . $tablet_layout,
        'aj-buttons-mobile-' . $mobile_layout,
        'aj-buttons-width-desktop-' . $desktop_width,
        'aj-buttons-width-tablet-' . $tablet_width,
        'aj-buttons-width-mobile-' . $mobile_width,
    );

    if ('stack' === $desktop_layout) {
        $classes[] = 'is-vertical';
    }

    if ('stretch' === ajnanda_buttons_allowed_justify($attrs['ajnBtnJustify'] ?? 'center')) {
        $classes[] = 'aj-buttons-stretch';
    }

    $has_shared = !empty($attrs['ajnBtnStyle']) || !empty($attrs['ajnBtnScheme']) || !empty($attrs['ajnBtnSizeStyle']) ||
        !empty($attrs['ajnBtnSharedBg']) || !empty($attrs['ajnBtnSharedColor']) || !empty($attrs['ajnBtnSharedBorderColor']) ||
        isset($attrs['ajnBtnSharedBorderWidth']) || isset($attrs['ajnBtnSharedBorderRadius']) ||
        isset($attrs['ajnBtnSharedPaddingX']) || isset($attrs['ajnBtnSharedPaddingY']);

    if ($has_shared) {
        $classes[] = 'aj-has-btn-shared-styles';
    }

    for ($i = 1; $i <= 6; $i++) {
        if (!empty($attrs['ajnBtnColor' . $i])) {
            $classes[] = 'aj-has-btn-per-colors';
            break;
        }
    }

    return implode(' ', array_unique(array_map('sanitize_html_class', $classes)));
}

function ajnanda_buttons_inject_wrapper_classes($block_content, $classes, $required_class) {
    return preg_replace_callback(
        '/(<div\b[^>]*\b' . preg_quote($required_class, '/') . '\b[^>]*>)/i',
        static function ($m) use ($classes) {
            $tag = $m[1];
            $remove_patterns = array(
                '/^aj-buttons-control$/',
                '/^aj-buttons-desktop-(row|stack|grid|featured)$/',
                '/^aj-buttons-tablet-(row|stack|grid|featured)$/',
                '/^aj-buttons-mobile-(row|stack|grid|featured)$/',
                '/^aj-buttons-width-desktop-(auto|narrow|standard|wide|full|custom)$/',
                '/^aj-buttons-width-tablet-(auto|narrow|standard|wide|full|custom)$/',
                '/^aj-buttons-width-mobile-(auto|narrow|standard|wide|full|custom)$/',
                '/^aj-buttons-stretch$/',
                '/^aj-has-btn-shared-styles$/',
                '/^aj-has-btn-per-colors$/',
                '/^is-vertical$/',
            );

            if (preg_match('/\bclass="([^"]*)"/i', $tag, $class_match)) {
                $existing = preg_split('/\s+/', trim($class_match[1]));
                $kept = array();

                foreach ($existing as $class_name) {
                    if ('' === $class_name) {
                        continue;
                    }

                    $remove = false;
                    foreach ($remove_patterns as $pattern) {
                        if (preg_match($pattern, $class_name)) {
                            $remove = true;
                            break;
                        }
                    }

                    if (!$remove) {
                        $kept[] = $class_name;
                    }
                }

                $next = trim(implode(' ', array_unique(array_merge($kept, preg_split('/\s+/', $classes)))));
                return str_replace($class_match[0], 'class="' . esc_attr($next) . '"', $tag);
            }

            return substr($tag, 0, -1) . ' class="' . esc_attr($classes) . '">';
        },
        $block_content,
        1
    );
}

function ajnanda_buttons_inject_style_vars($block_content, $vars, $required_class) {
    return preg_replace_callback(
        '/(<div\b[^>]*\b' . preg_quote($required_class, '/') . '\b[^>]*>)/i',
        static function ($m) use ($vars) {
            $tag = $m[1];
            if (preg_match('/\bstyle="([^"]*)"/i', $tag, $s)) {
                $existing = rtrim($s[1], '; ');
                $merged   = $existing ? $existing . ';' . $vars : $vars;
                return str_replace($s[0], 'style="' . esc_attr($merged) . '"', $tag);
            }

            return substr($tag, 0, -1) . ' style="' . esc_attr($vars) . '">';
        },
        $block_content,
        1
    );
}

function ajnanda_single_button_build_css_vars($attrs) {
    $parts = [];

    $bg       = ajnanda_safe_css_color($attrs['ajnSingleBtnBg'] ?? '');
    $text     = ajnanda_safe_css_color($attrs['ajnSingleBtnColor'] ?? '');
    $border_c = ajnanda_safe_css_color($attrs['ajnSingleBtnBorderColor'] ?? '');

    if ($bg)       $parts[] = '--aj-btn-item-bg:' . $bg;
    if ($text)     $parts[] = '--aj-btn-item-color:' . $text;
    if ($border_c) $parts[] = '--aj-btn-item-border-color:' . $border_c;

    return $parts ? implode(';', $parts) : '';
}

function ajnanda_buttons_build_css_vars($attrs) {
    $parts      = [];
    $has_scheme = !empty($attrs['ajnBtnScheme']) || !empty($attrs['ajnBtnStyle']);
    $has_size   = !empty($attrs['ajnBtnStyle'])  || !empty($attrs['ajnBtnSizeStyle']);

    $bg       = ajnanda_safe_css_color($attrs['ajnBtnSharedBg'] ?? '');
    $text     = ajnanda_safe_css_color($attrs['ajnBtnSharedColor'] ?? '');
    $border_c = ajnanda_safe_css_color($attrs['ajnBtnSharedBorderColor'] ?? '');

    if ($bg || $has_scheme)       $parts[] = '--aj-btn-shared-bg:'          . ($bg       ?: 'initial');
    if ($text || $has_scheme)     $parts[] = '--aj-btn-shared-color:'        . ($text     ?: 'inherit');
    if ($border_c || $has_scheme) $parts[] = '--aj-btn-shared-border-color:' . ($border_c ?: 'transparent');

    $bdr_w = isset($attrs['ajnBtnSharedBorderWidth'])  ? (int) $attrs['ajnBtnSharedBorderWidth']  : null;
    $bdr_r = isset($attrs['ajnBtnSharedBorderRadius']) ? (int) $attrs['ajnBtnSharedBorderRadius'] : null;
    $pad_x = isset($attrs['ajnBtnSharedPaddingX'])     ? (int) $attrs['ajnBtnSharedPaddingX']     : null;
    $pad_y = isset($attrs['ajnBtnSharedPaddingY'])     ? (int) $attrs['ajnBtnSharedPaddingY']     : null;

    if ($has_size || ($bdr_w !== null && $bdr_w > 0)) $parts[] = '--aj-btn-shared-border-width:'  . ($bdr_w  ?? 0) . 'px';
    if ($has_size || ($bdr_r !== null && $bdr_r > 0)) $parts[] = '--aj-btn-shared-border-radius:' . ($bdr_r  ?? 0) . 'px';
    if ($has_size || ($pad_x !== null && $pad_x > 0)) $parts[] = '--aj-btn-shared-padding-x:'     . ($pad_x  ?? 0) . 'px';
    if ($has_size || ($pad_y !== null && $pad_y > 0)) $parts[] = '--aj-btn-shared-padding-y:'     . ($pad_y  ?? 0) . 'px';

    for ($i = 1; $i <= 6; $i++) {
        $c = ajnanda_safe_css_color($attrs['ajnBtnColor' . $i] ?? '');
        if ($c) $parts[] = '--aj-btn-color-' . $i . ':' . $c;
    }

    $gap_desktop = isset($attrs['ajnButtonGapDesktop']) ? (int) $attrs['ajnButtonGapDesktop'] : 12;
    $gap_tablet  = isset($attrs['ajnButtonGapTablet'])  ? (int) $attrs['ajnButtonGapTablet']  : $gap_desktop;
    $gap_mobile  = isset($attrs['ajnButtonGapMobile'])  ? (int) $attrs['ajnButtonGapMobile']  : $gap_tablet;

    $parts[] = '--aj-buttons-gap-desktop:' . max(0, min(120, $gap_desktop)) . 'px';
    $parts[] = '--aj-buttons-gap-tablet:'  . max(0, min(120, $gap_tablet))  . 'px';
    $parts[] = '--aj-buttons-gap-mobile:'  . max(0, min(120, $gap_mobile))  . 'px';

    $custom_desktop = ajnanda_safe_css_size($attrs['ajnButtonsCustomWidthDesktop'] ?? '');
    $custom_tablet  = ajnanda_safe_css_size($attrs['ajnButtonsCustomWidthTablet'] ?? '');
    $custom_mobile  = ajnanda_safe_css_size($attrs['ajnButtonsCustomWidthMobile'] ?? '');

    if ($custom_desktop) $parts[] = '--aj-buttons-custom-width-desktop:' . $custom_desktop;
    if ($custom_tablet)  $parts[] = '--aj-buttons-custom-width-tablet:' . $custom_tablet;
    if ($custom_mobile)  $parts[] = '--aj-buttons-custom-width-mobile:' . $custom_mobile;

    $justify = ajnanda_buttons_allowed_justify($attrs['ajnBtnJustify'] ?? 'center');
    if ('stretch' !== $justify) {
        $parts[] = '--aj-btn-justify:' . $justify;
    }

    return $parts ? implode(';', $parts) : '';
}

function ajnanda_safe_css_color($value) {
    if (!$value || !is_string($value)) return '';
    $value = trim($value);
    if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value))  return $value;
    if (preg_match('/^rgba?\([^)]{0,60}\)$/', $value)) return $value;
    if (preg_match('/^hsla?\([^)]{0,60}\)$/', $value)) return $value;
    if (preg_match('/^[a-zA-Z]{1,30}$/', $value))      return $value;
    return '';
}

function ajnanda_safe_css_size($value) {
    if (!$value || !is_string($value)) return '';
    $value = trim($value);
    if (preg_match('/^[0-9]+(?:\.[0-9]+)?$/', $value)) return $value . 'px';
    if (preg_match('/^[0-9]+(?:\.[0-9]+)?(?:px|em|rem|vh|vw|vmin|vmax|%)$/', $value)) return $value;
    return '';
}

// ---------------------------------------------------------------------------
// ajnanda/accordion: the block's own "Collapse other items" / "Expand first
// item" toggles used to store attributes nothing read. Emit the same data
// attributes ajnanda/faq uses so frontend.js (initFaq, run on .aj-accordion
// too) wires up the behaviour. No saved-markup change → no re-validation.
// ---------------------------------------------------------------------------

add_filter('render_block', 'ajnanda_blocks_accordion_attrs', 10, 2);

function ajnanda_blocks_accordion_attrs($block_content, $block) {
    if ('ajnanda/accordion' !== ($block['blockName'] ?? '') || '' === trim((string) $block_content)) {
        return $block_content;
    }

    $attrs    = $block['attrs'] ?? array();
    $collapse = (!array_key_exists('collapseOtherItems', $attrs) || $attrs['collapseOtherItems']) ? 'true' : 'false';
    $expand   = (!array_key_exists('expandFirstItem', $attrs) || $attrs['expandFirstItem']) ? 'true' : 'false';

    return preg_replace(
        '/(<div\b[^>]*\bclass="[^"]*\baj-accordion\b[^"]*")/',
        '$1 data-collapse-other-items="' . $collapse . '" data-expand-first-item="' . $expand . '"',
        $block_content,
        1
    );
}

// ---------------------------------------------------------------------------
// Structured data: emit schema.org JSON-LD for the blocks whose "describe this
// as … content" toggle is on. Those toggles (ajnanda/faq enableSchema,
// ajnanda/how-to showSchema, ajnanda/review enableSchema) previously stored an
// attribute that produced no markup. Parsed from the rendered block HTML so it
// always matches what the visitor sees.
// ---------------------------------------------------------------------------

add_filter('render_block', 'ajnanda_blocks_structured_data', 20, 2);

function ajnanda_blocks_structured_data($block_content, $block) {
    if ('' === trim((string) $block_content) || is_admin() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return $block_content;
    }

    $name  = $block['blockName'] ?? '';
    $attrs = $block['attrs'] ?? array();

    if ('ajnanda/faq' === $name && !empty($attrs['enableSchema'])) {
        return $block_content . ajnanda_blocks_jsonld(ajnanda_blocks_faq_schema($block_content));
    }
    if ('ajnanda/how-to' === $name && !empty($attrs['showSchema'])) {
        return $block_content . ajnanda_blocks_jsonld(ajnanda_blocks_howto_schema($block_content));
    }
    if ('ajnanda/review' === $name && !empty($attrs['enableSchema'])) {
        return $block_content . ajnanda_blocks_jsonld(ajnanda_blocks_review_schema($block_content));
    }

    return $block_content;
}

function ajnanda_blocks_jsonld($data) {
    if (empty($data)) {
        return '';
    }
    return '<script type="application/ld+json">'
        . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>';
}

/** Parse an HTML fragment for querying; null if DOM support is unavailable. */
function ajnanda_blocks_schema_xpath($html) {
    if (!class_exists('DOMDocument')) {
        return null;
    }
    $dom  = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="ajnanda-schema-root">' . $html . '</div>', LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    return new DOMXPath($dom);
}

function ajnanda_blocks_schema_text($node) {
    return $node ? trim(preg_replace('/\s+/', ' ', $node->textContent)) : '';
}

function ajnanda_blocks_faq_schema($html) {
    $xp = ajnanda_blocks_schema_xpath($html);
    if (!$xp) {
        return null;
    }

    $entities = array();
    foreach ($xp->query('//details') as $details) {
        $summary = null;
        foreach ($details->childNodes as $child) {
            if ($child instanceof DOMElement && 'summary' === strtolower($child->nodeName)) {
                $summary = $child;
                break;
            }
        }
        $question = ajnanda_blocks_schema_text($summary);
        $answer   = trim(preg_replace('/^' . preg_quote($question, '/') . '/', '', ajnanda_blocks_schema_text($details)));
        if ('' === $question || '' === $answer) {
            continue;
        }
        $entities[] = array(
            '@type'          => 'Question',
            'name'           => $question,
            'acceptedAnswer' => array('@type' => 'Answer', 'text' => $answer),
        );
    }

    return $entities ? array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities) : null;
}

function ajnanda_blocks_howto_schema($html) {
    $xp = ajnanda_blocks_schema_xpath($html);
    if (!$xp) {
        return null;
    }

    $name  = ajnanda_blocks_schema_text($xp->query('//h1|//h2|//h3')->item(0));
    $steps = array();
    foreach ($xp->query('//li') as $li) {
        $text = ajnanda_blocks_schema_text($li);
        if ('' !== $text) {
            $steps[] = array('@type' => 'HowToStep', 'text' => $text);
        }
    }

    if (count($steps) < 2) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type'    => 'HowTo',
        'name'     => '' !== $name ? $name : __('How to', 'ajnanda'),
        'step'     => $steps,
    );
}

function ajnanda_blocks_review_schema($html) {
    $xp = ajnanda_blocks_schema_xpath($html);
    if (!$xp) {
        return null;
    }

    $body = ajnanda_blocks_schema_text($xp->query('//blockquote//p')->item(0));
    if ('' === $body) {
        $quote = $xp->query('//blockquote')->item(0);
        if ($quote) {
            foreach (iterator_to_array($xp->query('.//cite', $quote)) as $cite) {
                $cite->parentNode->removeChild($cite);
            }
            $body = ajnanda_blocks_schema_text($quote);
        }
    }
    if ('' === $body) {
        return null;
    }

    $review = array(
        '@context'     => 'https://schema.org',
        '@type'        => 'Review',
        'reviewBody'   => $body,
        'itemReviewed' => array('@type' => 'Organization', 'name' => get_bloginfo('name')),
    );

    $author = ajnanda_blocks_schema_text($xp->query('//cite')->item(0));
    if ('' !== $author) {
        $review['author'] = array('@type' => 'Person', 'name' => $author);
    }

    $stars = $xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " aj-stars ")]')->item(0);
    if ($stars instanceof DOMElement) {
        $label  = $stars->getAttribute('aria-label');
        $rating = is_numeric($label) ? (float) $label : (float) substr_count($stars->textContent, "\xe2\x98\x85");
        if ($rating > 0) {
            $review['reviewRating'] = array('@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => 5, 'worstRating' => 1);
        }
    }

    return $review;
}

// ---------------------------------------------------------------------------
// ajnanda/modal: render a real trigger + native <dialog> around the saved
// content (native dialog gives focus-trap, Esc and backdrop for free; no
// library). ajnanda/tabs: split the saved content on its headings into a real
// tablist + panels. Both transform at render time only — the saved block markup
// (a plain styled <section>/<div> + InnerBlocks) never changes, so there is no
// block re-validation and old content keeps working.
// ---------------------------------------------------------------------------

add_filter('render_block', 'ajnanda_blocks_interactive_containers', 15, 2);

function ajnanda_blocks_interactive_containers($block_content, $block) {
    $name = $block['blockName'] ?? '';
    if ('' === trim((string) $block_content)) {
        return $block_content;
    }
    if ('ajnanda/modal' === $name) {
        return ajnanda_blocks_render_modal($block_content, $block['attrs'] ?? array());
    }
    if ('ajnanda/tabs' === $name) {
        return ajnanda_blocks_render_tabs($block_content, $block['attrs'] ?? array());
    }
    if ('ajnanda/testimonials' === $name && 'carousel' === ($block['attrs']['layout'] ?? '')) {
        return ajnanda_blocks_carousel_wrap($block_content, 'aj-testimonials', __('Testimonials', 'ajnanda'));
    }
    return $block_content;
}

/** Wrap a block's direct child elements in the shared accessible carousel. */
function ajnanda_blocks_carousel_wrap($block_content, $class_needle, $label) {
    if (!class_exists('DOMDocument') || !function_exists('ajnanda_carousel_markup')
        || !preg_match('#<(section|div)\b[^>]*\bclass="([^"]*\b' . preg_quote($class_needle, '#') . '\b[^"]*)"[^>]*>(.*)</\1>\s*$#s', $block_content, $m)) {
        return $block_content;
    }

    $dom  = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="aj-cw-root">' . $m[3] . '</div>', LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = (new DOMXPath($dom))->query('//*[@id="aj-cw-root"]')->item(0);
    if (!$root) {
        return $block_content;
    }

    $slides = '';
    foreach (iterator_to_array($root->childNodes) as $node) {
        if ($node instanceof DOMElement) {
            $slides .= $dom->saveHTML($node);
        }
    }
    if ('' === trim($slides)) {
        return $block_content;
    }

    return '<' . $m[1] . ' class="' . esc_attr($m[2]) . '">'
        . ajnanda_carousel_markup($slides, array('label' => $label, 'dots' => true))
        . '</' . $m[1] . '>';
}

function ajnanda_blocks_render_modal($block_content, $attrs) {
    if (!preg_match('/<section\b[^>]*\baj-modal-placeholder\b[^>]*>(.*)<\/section>/s', $block_content, $m)) {
        return $block_content;
    }
    $inner   = $m[1];
    $trigger = trim((string) ($attrs['triggerText'] ?? ''));
    if ('' === $trigger) {
        $trigger = __('Open', 'ajnanda');
    }
    $width = max(320, min(1200, (int) ($attrs['modalWidth'] ?? 640)));
    $id    = wp_unique_id('aj-modal-');

    return '<div class="aj-block aj-modal" data-aj-modal>'
        . '<button type="button" class="aj-button aj-modal__trigger" aria-haspopup="dialog" aria-controls="' . esc_attr($id) . '" data-aj-modal-open>' . esc_html($trigger) . '</button>'
        . '<dialog id="' . esc_attr($id) . '" class="aj-modal__dialog" style="--aj-modal-width:' . $width . 'px">'
        . '<form method="dialog"><button class="aj-modal__close" aria-label="' . esc_attr__('Close', 'ajnanda') . '" data-aj-modal-close>&times;</button></form>'
        . '<div class="aj-modal__body">' . $inner . '</div>'
        . '</dialog></div>';
}

function ajnanda_blocks_render_tabs($block_content, $attrs) {
    if (!class_exists('DOMDocument')
        || !preg_match('/<div\b[^>]*\bclass="([^"]*\baj-tabs\b[^"]*)"[^>]*>(.*)<\/div>\s*$/s', $block_content, $m)) {
        return $block_content;
    }

    $wrap_class = $m[1];
    $dom  = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="aj-tabs-root">' . $m[2] . '</div>', LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = (new DOMXPath($dom))->query('//*[@id="aj-tabs-root"]')->item(0);
    if (!$root) {
        return $block_content;
    }

    $groups = array();
    foreach (iterator_to_array($root->childNodes) as $node) {
        if ($node instanceof DOMElement && preg_match('/^h[1-6]$/i', $node->nodeName)) {
            $groups[] = array('label' => trim(preg_replace('/\s+/', ' ', $node->textContent)), 'html' => '');
            continue;
        }
        if (empty($groups)) {
            continue; // content before the first heading is dropped from the tab UI
        }
        $groups[count($groups) - 1]['html'] .= $dom->saveHTML($node);
    }

    if (count($groups) < 2) {
        return $block_content;
    }

    $active = max(1, min(count($groups), (int) ($attrs['activeTab'] ?? 1)));
    $id     = wp_unique_id('aj-tabs-');
    $tabs   = '';
    $panels = '';
    foreach ($groups as $i => $group) {
        $n        = $i + 1;
        $selected = $n === $active;
        $tabs   .= '<button type="button" role="tab" id="' . esc_attr("$id-t$n") . '" aria-controls="' . esc_attr("$id-p$n") . '"'
            . ' aria-selected="' . ($selected ? 'true' : 'false') . '" tabindex="' . ($selected ? '0' : '-1') . '">'
            . esc_html($group['label']) . '</button>';
        $panels .= '<div class="aj-tabs__panel" role="tabpanel" id="' . esc_attr("$id-p$n") . '" aria-labelledby="' . esc_attr("$id-t$n") . '"'
            . ($selected ? '' : ' hidden') . '>' . $group['html'] . '</div>';
    }

    return '<div class="' . esc_attr($wrap_class) . '" data-aj-tabs>'
        . '<div class="aj-tabs__list" role="tablist">' . $tabs . '</div>'
        . '<div class="aj-tabs__panels">' . $panels . '</div></div>';
}
