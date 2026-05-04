<?php
/**
 * Template Name: Builder Canvas
 * Template Post Type: page
 *
 * Full-width, editor-controlled page template for block-based landing pages.
 *
 * @package NCLLC_Pro
 */

get_header();
?>

<main id="main-content" class="site-main builder-canvas">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('builder-canvas-page'); ?>>
            <div class="entry-content builder-canvas-content">
                <?php
                the_content();

                wp_link_pages(array(
                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'ncllc-pro'),
                    'after'  => '</div>',
                ));
                ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>
