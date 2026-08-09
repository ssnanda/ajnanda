<?php
/**
 * Title: Careers
 * Slug: ajnanda/page-careers
 * Categories: ajnanda-page-designs
 * Keywords: careers, jobs, hiring
 * Block Types: core/post-content
 * Post Types: page
 * Description: A careers page — intro, culture/benefits grid, an employee quote, hiring FAQ, and a bold apply CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-faq-standard',
    'ajnanda/section-cta-super-bold',
));
