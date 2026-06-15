<?php
/**
 * Template Name: Contact Page
 * Description: Contact page shell that lets the WordPress editor control the page content.
 *
 * @package NCLLC_Pro
 */

get_header();
?>

<main id="main" class="site-main">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <section class="page-content-section">
                <div class="container">
                    <div class="entry-content page-content-panel">
                        <?php
                        the_content();

                        wp_link_pages(array(
                            'before' => '<div class="page-links">' . esc_html__('Pages:', 'ncllc-pro'),
                            'after'  => '</div>',
                        ));
                        ?>
                    </div>
                </div>
            </section>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>
