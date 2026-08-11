<?php
/**
 * Template Name: Contact Page
 * Description: Contact page shell that lets the WordPress editor control the page content.
 *
 * WordPress' template hierarchy auto-selects this file for ANY page whose
 * slug is exactly "contact" (page-{slug}.php), independent of whether
 * "Contact Page" was ever picked from the template dropdown — and
 * "contact" is the slug AJNanda's own page-contact Page Design and every
 * starter site with a Contact page use by convention. This file used to
 * be a pure duplicate of page.php's boxed/white-panel branch, predating
 * the builder-canvas full-width detection page.php later gained — so any
 * modern, Section-Pattern-built Contact page (full-width builder-section
 * markup, dark surfaces, edge-to-edge hero) silently rendered boxed and
 * light instead, on every AJNanda site, purely because of its slug. Same
 * $has_builder_sections sniff + branch as page.php now, so a page named
 * "contact" renders identically to any other AJNanda page; a
 * classic/non-builder content page still gets the same boxed-panel
 * fallback it always did.
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

        $page_content_section_classes = array('page-content-section');
        if (ajnanda_has_leading_builder_hero_content($content)) {
            $page_content_section_classes[] = 'has-leading-hero-content';
        }
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if ($has_builder_sections) : ?>
                <div class="entry-content builder-canvas-content page-builder-content">
                    <?php
                    echo apply_filters('the_content', $content);

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ajnanda'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            <?php else : ?>
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
            <?php endif; ?>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>
