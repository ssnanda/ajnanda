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
        'title' => __('AJNanda Blocks', 'ncllc-pro'),
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
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render'),
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
        'buttonText' => __('Read More', 'ncllc-pro'),
        'order' => 'desc',
        'orderBy' => 'date',
        'columns' => 3,
    ));

    $query = new WP_Query(ajnanda_blocks_post_query_args($attrs));

    if (!$query->have_posts()) {
        return '<div class="aj-block aj-posts"><p>' . esc_html__('No posts found.', 'ncllc-pro') . '</p></div>';
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
        'buttonText' => __('Read More', 'ncllc-pro'),
        'order' => 'desc',
        'orderBy' => 'date',
        'columns' => 3,
        'dateFormat' => get_option('date_format'),
    ));

    $query = new WP_Query(ajnanda_blocks_post_query_args($attrs));

    if (!$query->have_posts()) {
        return '<div class="aj-block aj-posts aj-posts--' . esc_attr($variant) . '"><p>' . esc_html__('No posts found.', 'ncllc-pro') . '</p></div>';
    }

    ob_start();
    ?>
    <div class="aj-block aj-posts aj-posts--<?php echo esc_attr($variant); ?>" style="<?php echo esc_attr(ajnanda_blocks_posts_style($attrs)); ?>">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
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
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();

    return ob_get_clean();
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
        return '<nav class="aj-block aj-taxonomy-list"><p>' . esc_html__('No terms found.', 'ncllc-pro') . '</p></nav>';
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
        'placeholder' => __('Search...', 'ncllc-pro'),
        'buttonText' => __('Search', 'ncllc-pro'),
        'layout' => 'inline',
        'buttonPosition' => 'right',
    ));
    $layout = in_array($attrs['layout'], array('inline', 'stacked'), true) ? $attrs['layout'] : 'inline';
    $button_position = ('left' === $attrs['buttonPosition']) ? 'left' : 'right';

    ob_start();
    ?>
    <form class="aj-block aj-search aj-search--<?php echo esc_attr($layout); ?> aj-search--button-<?php echo esc_attr($button_position); ?>" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <label class="screen-reader-text" for="aj-search-field"><?php esc_html_e('Search for:', 'ncllc-pro'); ?></label>
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
        return '<nav class="aj-block aj-nav-menu"><p>' . esc_html__('Assign a menu to this location first.', 'ncllc-pro') . '</p></nav>';
    }

    return $output;
}

function ajnanda_blocks_render_toc($attrs, $content, $block) {
    $attrs = ajnanda_blocks_attrs($attrs, array(
        'title' => __('On this page', 'ncllc-pro'),
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
        return '<nav class="aj-block aj-toc"><p>' . esc_html__('Add headings to generate a table of contents.', 'ncllc-pro') . '</p></nav>';
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
        'loggedOutText' => __('Login area placeholder.', 'ncllc-pro'),
        'loginText' => __('Log In', 'ncllc-pro'),
        'logoutText' => __('Log Out', 'ncllc-pro'),
    ));

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();

        return '<div class="aj-block aj-login-placeholder"><p>' . esc_html(sprintf(__('Signed in as %s.', 'ncllc-pro'), $current_user->display_name)) . '</p><a class="aj-button" href="' . esc_url(wp_logout_url()) . '">' . esc_html($attrs['logoutText']) . '</a></div>';
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

function ajnanda_blocks_register_dynamic_blocks() {
    $post_attributes = array(
        'count' => array('type' => 'number', 'default' => 6),
        'showExcerpt' => array('type' => 'boolean', 'default' => true),
        'showImage' => array('type' => 'boolean', 'default' => true),
        'buttonText' => array('type' => 'string', 'default' => __('Read More', 'ncllc-pro')),
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
                'placeholder' => array('type' => 'string', 'default' => __('Search...', 'ncllc-pro')),
                'buttonText' => array('type' => 'string', 'default' => __('Search', 'ncllc-pro')),
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
                'title' => array('type' => 'string', 'default' => __('On this page', 'ncllc-pro')),
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
                'loggedOutText' => array('type' => 'string', 'default' => __('Login area placeholder.', 'ncllc-pro')),
                'loginText' => array('type' => 'string', 'default' => __('Log In', 'ncllc-pro')),
                'logoutText' => array('type' => 'string', 'default' => __('Log Out', 'ncllc-pro')),
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
