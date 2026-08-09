<?php
/**
 * Title: Product — Detail
 * Slug: ajnanda/page-product-detail
 * Categories: ajnanda-page-designs
 * Keywords: product, detail, spec
 * Block Types: core/post-content
 * Post Types: page
 * Description: A single-product detail page — intro, product imagery, feature grid, related products, and a bold CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-image-right',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-related-services',
    'ajnanda/section-cta-super-bold',
));
