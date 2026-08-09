<?php
/**
 * Title: Home — Product / Solution
 * Slug: ajnanda/page-home-product-solution
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, product, solution, reseller
 * Block Types: core/post-content
 * Post Types: page
 * Description: A homepage built around a flagship product or solution — split hero, product imagery, a featured offering, and partner logos.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-split',
    'ajnanda/section-content-image-right',
    'ajnanda/section-services-featured',
    'ajnanda/section-logo-row',
    'ajnanda/section-cta-split',
));
