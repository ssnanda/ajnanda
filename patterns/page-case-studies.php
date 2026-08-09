<?php
/**
 * Title: Case Studies Overview
 * Slug: ajnanda/page-case-studies
 * Categories: ajnanda-page-designs
 * Keywords: case studies, results, portfolio
 * Block Types: core/post-content
 * Post Types: page
 * Description: A case-studies landing page — intro, results/metrics summary, testimonials, and linked case study tiles. Duplicate the intro + results sections per case study for a Case Study Detail page.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-results-metrics',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-related-services',
    'ajnanda/section-cta-minimal',
));
