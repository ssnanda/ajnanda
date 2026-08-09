<?php
/**
 * Title: Home — Technology / SaaS
 * Slug: ajnanda/page-home-technology
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, technology, saas, software
 * Block Types: core/post-content
 * Post Types: page
 * Description: A product-led homepage for a technology or SaaS company — split hero, trust row, feature grid, stats, and a dark closing CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-split',
    'ajnanda/section-trust-row',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-dark',
));
