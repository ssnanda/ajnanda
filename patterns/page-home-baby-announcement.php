<?php
/**
 * Title: Home — Baby Announcement
 * Slug: ajnanda/page-home-baby-announcement
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, baby, nursery, announcement, family
 * Block Types: core/post-content
 * Post Types: page
 * Description: A soft, gentle homepage for a birth announcement or nursery/baby site — welcoming hero, a milestones/fun-facts grid, an updates signup, and a simple call to action. Pairs well with the "Little One" Site Kit.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-minimal',
    'ajnanda/section-content-large-statement',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-newsletter-cta',
    'ajnanda/section-cta-minimal',
));
