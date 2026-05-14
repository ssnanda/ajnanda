<?php
/**
 * Template for displaying pages
 * 
 * @package NCLLC_Pro
 */

get_header(); ?>

<main id="main-content" class="site-main">
    <?php
    while (have_posts()) :
        the_post();
        $content = get_the_content();
        $has_builder_sections = false !== strpos($content, 'builder-section');
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if ($has_builder_sections) : ?>
                <div class="entry-content builder-canvas-content page-builder-content">
                    <?php
                    echo apply_filters('the_content', $content);

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ncllc-pro'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            <?php else : ?>
            <section class="page-content-section">
                <div class="container">
                    <div class="entry-content page-content-panel">
                    <?php
                    echo apply_filters('the_content', $content);

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ncllc-pro'),
                        'after'  => '</div>',
                    ));
                    ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>
