<?php
/**
 * Title: Home — Corporate
 * Slug: ajnanda/page-home-corporate
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, corporate, business
 * Block Types: core/post-content
 * Post Types: page
 * Description: A measured, credibility-first homepage — centered hero, client logos, services, results, and testimonials.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-centered',
    'ajnanda/section-logo-row',
    'ajnanda/section-services-three-columns',
    'ajnanda/section-results-metrics',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-cta-split',
));
