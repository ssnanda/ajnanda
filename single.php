<?php
/**
 * Template for displaying single posts
 *
 * @package NCLLC_Pro
 */

get_header(); ?>

<main id="main-content" class="site-main">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="container">
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    <div class="entry-meta">
                        <?php if (get_theme_mod('post_meta_show_date', true)) : ?>
                        <time class="entry-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                            <?php echo esc_html(get_the_date()); ?>
                        </time>
                        <?php endif; ?>
                        <?php if (get_theme_mod('post_meta_show_author', true)) : ?>
                        <span class="entry-author">
                            <?php
                            printf(
                                /* translators: %s: author link */
                                esc_html__('By %s', 'ajnanda'),
                                '<a href="' . esc_url(get_author_posts_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a>'
                            );
                            ?>
                        </span>
                        <?php endif; ?>
                        <?php if (get_theme_mod('post_meta_show_read_time', true)) :
                        $word_count = str_word_count(wp_strip_all_tags(get_the_content()));
                        $read_time  = max(1, (int) ceil($word_count / 200));
                        printf(
                            '<span class="entry-read-time">' . esc_html(
                                /* translators: %d: minutes */
                                _n('%d min read', '%d min read', $read_time, 'ajnanda')
                            ) . '</span>',
                            $read_time
                        );
                        endif; ?>
                    </div>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-thumbnail">
                        <?php the_post_thumbnail('large', array('alt' => the_title_attribute(array('echo' => false)), 'class' => 'post-thumbnail-img')); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content single-entry-content">
                    <?php
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ajnanda'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <footer class="entry-footer single-entry-footer">
                    <?php
                    $categories_list = get_the_category_list(', ');
                    if ($categories_list) {
                        printf(
                            '<span class="cat-links"><span class="entry-footer-label">' . esc_html__('Categories:', 'ajnanda') . '</span> %s</span>',
                            $categories_list
                        );
                    }

                    $tags_list = get_the_tag_list('', ', ');
                    if ($tags_list) {
                        printf(
                            '<span class="tags-links"><span class="entry-footer-label">' . esc_html__('Tags:', 'ajnanda') . '</span> %s</span>',
                            $tags_list
                        );
                    }
                    ?>
                </footer>

                <?php
                // Author bio
                $author_id          = get_the_author_meta('ID');
                $author_description = get_the_author_meta('description');
                if ($author_description && get_theme_mod('post_meta_show_author', true)) :
                ?>
                <div class="author-bio">
                    <div class="author-bio-avatar">
                        <?php echo get_avatar($author_id, 80, '', get_the_author(), array('class' => '')); ?>
                    </div>
                    <div class="author-bio-content">
                        <p class="author-bio-name"><?php echo esc_html(get_the_author()); ?></p>
                        <p class="author-bio-description"><?php echo esc_html($author_description); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                // Related posts (same category, exclude current)
                $current_id   = get_the_ID();
                $cats         = wp_get_post_categories($current_id);
                $related_args = array(
                    'category__in'        => $cats ?: array(),
                    'post__not_in'        => array($current_id),
                    'posts_per_page'      => 3,
                    'orderby'             => 'rand',
                    'ignore_sticky_posts' => 1,
                );
                $related = new WP_Query($related_args);
                if ($related->have_posts()) :
                ?>
                <div class="related-posts">
                    <h3 class="related-posts-title"><?php esc_html_e('Related Articles', 'ajnanda'); ?></h3>
                    <div class="related-posts-grid">
                        <?php while ($related->have_posts()) : $related->the_post(); ?>
                        <a class="related-post-card" href="<?php the_permalink(); ?>">
                            <div class="related-post-thumb">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('ajnanda-thumbnail', array('alt' => the_title_attribute(array('echo' => false)))); ?>
                                <?php else : ?>
                                    <div class="related-post-thumb-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <div class="related-post-info">
                                <?php if (get_theme_mod('post_meta_show_date', true)) : ?>
                                <div class="related-post-date"><?php echo esc_html(get_the_date()); ?></div>
                                <?php endif; ?>
                                <p class="related-post-title"><?php the_title(); ?></p>
                            </div>
                        </a>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </article>

        <?php
        the_post_navigation(array(
            'prev_text' => '<span class="nav-subtitle">Previous:</span> <span class="nav-title">%title</span>',
            'next_text' => '<span class="nav-subtitle">Next:</span> <span class="nav-title">%title</span>',
        ));

        if (comments_open() || get_comments_number()) :
            comments_template();
        endif;

    endwhile;
    ?>
</main>

<?php get_footer(); ?>
