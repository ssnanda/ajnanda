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
        $builder_canvas_markers = array(
            'builder-section',
            'home-hero-section',
            'home-registered-agent-section',
            'home-features-section',
            'home-reviews-section',
            'home-faq-section',
            'home-knowledge-section',
            'home-cta-section',
        );
        $has_builder_sections = false;

        foreach ($builder_canvas_markers as $builder_canvas_marker) {
            if (false !== strpos($content, $builder_canvas_marker)) {
                $has_builder_sections = true;
                break;
            }
        }
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
