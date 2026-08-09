<?php
/**
 * Title: Services — Overview
 * Slug: ajnanda/page-services-overview
 * Categories: ajnanda-page-designs
 * Keywords: services, overview
 * Block Types: core/post-content
 * Post Types: page
 * Description: A full services landing page — intro, card grid of every service, process, testimonials, and a closing CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-services-card-grid',
    'ajnanda-pro/three-step-process',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-cta-split',
));
