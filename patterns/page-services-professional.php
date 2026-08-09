<?php
/**
 * Title: Services — Professional
 * Slug: ajnanda/page-services-professional
 * Categories: ajnanda-page-designs
 * Keywords: services, professional, consulting
 * Block Types: core/post-content
 * Post Types: page
 * Description: A services page for a professional-services firm — overview grid, proof points, plan comparison, and a closing CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda-pro/service-overview-grid',
    'ajnanda-pro/proof-points',
    'ajnanda-pro/two-service-cards',
    'ajnanda-pro/centered-final-cta',
));
