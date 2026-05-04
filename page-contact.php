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
        $content = trim(get_the_content());
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if ($content) : ?>
                <div class="entry-content builder-canvas-content contact-builder-content">
                    <?php
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ncllc-pro'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            <?php else : ?>
            <section class="contact-editor-section">
                <div class="container">
                    <div class="contact-form-panel contact-editor-content">
                        <?php
                        echo do_shortcode('[wp_formy id="1"]');

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
