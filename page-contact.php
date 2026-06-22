<?php
/**
 * Template Name: Contact Page
 * Description: Contact page shell that lets the WordPress editor control the page content.
 *
 * @package NCLLC_Pro
 */

get_header();
?>

<main id="main-content" class="site-main">
    <?php
    while (have_posts()) :
        the_post();
        $content = get_the_content();
        $page_content_section_classes = array('page-content-section');
        if (ajnanda_has_leading_builder_hero_content($content)) {
            $page_content_section_classes[] = 'has-leading-hero-content';
        }
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <section class="<?php echo esc_attr(implode(' ', $page_content_section_classes)); ?>">
                <div class="container">
                    <div class="entry-content page-content-panel">
                        <?php
                        echo apply_filters('the_content', $content);

                        wp_link_pages(array(
                            'before' => '<div class="page-links">' . esc_html__('Pages:', 'ajnanda'),
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
