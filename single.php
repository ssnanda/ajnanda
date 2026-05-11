<?php
/**
 * Template for displaying single posts
 *
 * Supports an editable leading hero block when the first block has class "builder-hero-section".
 *
 * @package NCLLC_Pro
 */

get_header(); ?>

<main id="main-content" class="site-main">
    <?php
    while (have_posts()) :
        the_post();

        $content = get_the_content();
        $split_content = ncllc_pro_split_leading_builder_hero($content);
        $has_leading_hero = '' !== $split_content['hero'];
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if ($has_leading_hero) : ?>
                <div class="entry-content builder-canvas-content page-hero-content">
                    <?php echo $split_content['hero']; ?>
                </div>
            <?php else : ?>
                <section class="page-hero blog-hero">
                    <div class="container">
                        <div class="page-hero-badge"><?php echo esc_html(get_bloginfo('name')); ?></div>
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                        <?php if (has_excerpt()) : ?>
                            <p><?php echo esc_html(get_the_excerpt()); ?></p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="container">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-thumbnail" style="margin: 2rem 0;">
                        <?php the_post_thumbnail('large', array('style' => 'width: 100%; height: auto; border-radius: 1rem;')); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content" style="padding: 2rem 0 4rem; max-width: 800px; margin: 0 auto; line-height: 1.8;">
                    <?php
                    echo apply_filters('the_content', $has_leading_hero ? $split_content['rest'] : $content);

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ncllc-pro'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <footer class="entry-footer" style="padding: 2rem 0; border-top: 1px solid #e5e7eb; max-width: 800px; margin: 0 auto;">
                    <?php
                    $categories_list = get_the_category_list(', ');
                    if ($categories_list) {
                        printf('<span class="cat-links">Categories: %s</span>', $categories_list);
                    }

                    $tags_list = get_the_tag_list('', ', ');
                    if ($tags_list) {
                        printf('<span class="tags-links" style="margin-left: 1rem;">Tags: %s</span>', $tags_list);
                    }
                    ?>
                </footer>
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
