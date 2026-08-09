<?php
/**
 * Title: Services — Technology
 * Slug: ajnanda/page-services-technology
 * Categories: ajnanda-page-designs
 * Keywords: services, technology, saas
 * Block Types: core/post-content
 * Post Types: page
 * Description: A services page for a technology company — alternating feature rows, stats, integration/partner logos, and a dark CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-services-alternating-rows',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-logo-row',
    'ajnanda/section-cta-dark',
));
