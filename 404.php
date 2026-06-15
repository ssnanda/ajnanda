<?php
/**
 * 404 Error Page Template
 * 
 * @package NCLLC_Pro
 */

get_header(); ?>

<main id="main-content" class="site-main">
    <section class="error-404 not-found">
        <div class="container">
            <div class="error-404-inner">
                <h1 class="error-404-code">404</h1>
                <h2 class="error-404-title"><?php esc_html_e('Oops! Page Not Found', 'ajnanda'); ?></h2>
                <p class="error-404-message"><?php esc_html_e("The page you're looking for doesn't exist or has been moved. Let's get you back on track!", 'ajnanda'); ?></p>
                <div class="error-404-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary"><?php esc_html_e('Go Home', 'ajnanda'); ?></a>
                    <button type="button" class="btn btn-outline" onclick="history.back()"><?php esc_html_e('Go Back', 'ajnanda'); ?></button>
                </div>
                <div class="error-404-search">
                    <h3 class="error-404-search-label"><?php esc_html_e('Try searching:', 'ajnanda'); ?></h3>
                    <?php get_search_form(); ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
