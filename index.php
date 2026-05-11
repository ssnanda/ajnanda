<?php
/**
 * Main template file.
 *
 * Used for the posts page / Knowledge Base index.
 *
 * @package NCLLC_Pro
 */

get_header();

// Check if this is the static posts page with custom content
$posts_page_id = (int) get_option('page_for_posts');
$page_content = '';

if (is_home() && $posts_page_id) {
    // Static posts page - use its title/excerpt/content
    $page_title = get_the_title($posts_page_id);
    $page_intro = get_post_field('post_excerpt', $posts_page_id);
    $page_content = get_post_field('post_content', $posts_page_id);
} else {
    // All archives (category, tag, date, author, search) - use WordPress APIs
    $page_title = get_the_archive_title();
    $page_intro = get_the_archive_description();
}

// Clean up HTML tags from descriptions
$page_title = strip_tags($page_title);
$page_intro = strip_tags($page_intro);
?>

<main id="main-content" class="site-main">
    <?php if ($posts_page_id && trim($page_content)) : ?>
        <section class="posts-page-content-section">
            <div class="entry-content posts-page-content">
                <?php echo apply_filters('the_content', $page_content); ?>
            </div>
        </section>
    <?php else : ?>
        <section class="page-hero blog-hero">
            <div class="container">
                <?php if (is_home() && $posts_page_id) : ?>
                    <div class="page-hero-badge"><?php echo esc_html(get_bloginfo('name')); ?></div>
                <?php else : ?>
                    <div class="page-hero-badge"><?php echo esc_html(get_post_type_object('post')->labels->name); ?></div>
                <?php endif; ?>
                <h1 class="entry-title"><?php echo esc_html($page_title); ?></h1>
                <?php if ($page_intro) : ?>
                    <p><?php echo esc_html($page_intro); ?></p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="blog-index-section">
        <div class="container">
            <?php if (have_posts()) : ?>
                <div class="blog-grid">
                    <?php
                    while (have_posts()) :
                        the_post();
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class('blog-card animate-on-scroll'); ?>>
                            <?php if (has_post_thumbnail()) : ?>
                                <a class="blog-card-image" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                                    <?php the_post_thumbnail('ncllc-thumbnail'); ?>
                                </a>
                            <?php endif; ?>

                            <div class="blog-card-content">
                                <div class="blog-card-date"><?php echo esc_html(get_the_date()); ?></div>
                                <?php the_title('<h2 class="blog-card-title"><a href="' . esc_url(get_permalink()) . '">', '</a></h2>'); ?>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
                                <a class="blog-card-link" href="<?php the_permalink(); ?>">
                                    <?php esc_html_e('Read More', 'ncllc-pro'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <nav class="blog-pagination" aria-label="<?php esc_attr_e('Posts navigation', 'ncllc-pro'); ?>">
                    <?php
                    the_posts_pagination(array(
                        'mid_size'  => 2,
                        'prev_text' => __('Previous', 'ncllc-pro'),
                        'next_text' => __('Next', 'ncllc-pro'),
                    ));
                    ?>
                </nav>
            <?php else : ?>
                <div class="blog-empty">
                    <h2><?php esc_html_e('No articles yet', 'ncllc-pro'); ?></h2>
                    <p><?php esc_html_e('Check back soon for expert insights on North Carolina business compliance.', 'ncllc-pro'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
