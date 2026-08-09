<?php
/**
 * Title: Pricing
 * Slug: ajnanda/page-pricing
 * Categories: ajnanda-page-designs
 * Keywords: pricing, plans, packages
 * Block Types: core/post-content
 * Post Types: page
 * Description: A pricing/plans page — intro, side-by-side plan comparison, a feature-by-feature comparison, FAQ, and a CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda-pro/two-service-cards',
    'ajnanda-pro/provider-comparison',
    'ajnanda/section-faq-standard',
    'ajnanda/section-cta-split',
));
