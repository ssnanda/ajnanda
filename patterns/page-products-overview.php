<?php
/**
 * Title: Products — Overview
 * Slug: ajnanda/page-products-overview
 * Categories: ajnanda-page-designs
 * Keywords: products, overview, catalog
 * Block Types: core/post-content
 * Post Types: page
 * Description: A product catalog landing page — intro, card grid of products, partner/brand logos, and a CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-services-card-grid',
    'ajnanda/section-logo-row',
    'ajnanda/section-cta-split',
));
