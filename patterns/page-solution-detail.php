<?php
/**
 * Title: Solution — Detail
 * Slug: ajnanda/page-solution-detail
 * Categories: ajnanda-page-designs
 * Keywords: solution, detail, results
 * Block Types: core/post-content
 * Post Types: page
 * Description: A solution detail page framed around outcomes — intro, image/copy split, before/after results, a client quote, and a dark CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-image-left',
    'ajnanda/section-results-metrics',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-dark',
));
