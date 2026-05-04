<?php
/**
 * 404 Error Page Template
 * 
 * @package NCLLC_Pro
 */

get_header(); ?>

<main id="main-content" class="site-main">
    <section class="error-404" style="min-height: 80vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 4rem 1.5rem;">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <h1 style="font-size: clamp(4rem, 10vw, 8rem); font-weight: 900; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem;">404</h1>
                
                <h2 style="font-size: clamp(1.5rem, 4vw, 2.5rem); margin-bottom: 1.5rem;">Oops! Page Not Found</h2>
                
                <p style="font-size: 1.125rem; color: var(--gray-600); margin-bottom: 2.5rem;">
                    The page you're looking for doesn't exist or has been moved. Let's get you back on track!
                </p>
                
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                        Go Home
                    </a>
                    <a href="javascript:history.back()" class="btn btn-secondary" style="background: var(--gray-100); color: var(--gray-800); border-color: var(--gray-300);">
                        Go Back
                    </a>
                </div>
                
                <div style="margin-top: 3rem;">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Try searching:</h3>
                    <?php get_search_form(); ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
