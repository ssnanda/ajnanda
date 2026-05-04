<?php
/**
 * Front page template.
 *
 * The Home page is intentionally editor-controlled. Build and edit the
 * homepage from Pages > Home; this template only renders that content.
 *
 * @package NCLLC_Pro
 */

get_header();
?>

<main id="main-content" class="site-main editor-front-page">
    <?php
    while (have_posts()) :
        the_post();
        the_content();
    endwhile;
    ?>
</main>

<?php get_footer(); ?>
