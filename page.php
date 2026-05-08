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
        $split_content = ncllc_pro_split_leading_builder_hero($content);
        $has_leading_hero = '' !== $split_content['hero'];
        $has_builder_sections = false !== strpos($split_content['rest'], 'builder-section');
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if ($has_leading_hero) : ?>
                <div class="entry-content builder-canvas-content page-hero-content">
                    <?php echo $split_content['hero']; ?>
                </div>
            <?php endif; ?>

            <?php if ($has_builder_sections) : ?>
                <div class="entry-content builder-canvas-content page-builder-content<?php echo $has_leading_hero ? ' has-leading-hero-content' : ''; ?>">
                    <?php
                    echo apply_filters('the_content', $split_content['rest']);

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ncllc-pro'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            <?php else : ?>
            <section class="page-content-section<?php echo $has_leading_hero ? ' has-leading-hero-content' : ''; ?>">
                <div class="container">
                    <div class="entry-content page-content-panel">
                    <?php
                    echo apply_filters('the_content', $has_leading_hero ? $split_content['rest'] : $content);

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
