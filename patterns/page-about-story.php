<?php
/**
 * Title: About — Story
 * Slug: ajnanda/page-about-story
 * Categories: ajnanda-page-designs
 * Keywords: about, story, mission, editorial
 * Block Types: core/post-content
 * Post Types: page
 * Description: A narrative-led About page — bold mission statement, origin story, and a client endorsement.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-large-statement',
    'ajnanda/section-content-image-right',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-minimal',
));
