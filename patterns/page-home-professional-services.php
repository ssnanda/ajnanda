<?php
/**
 * Title: Home — Professional Services
 * Slug: ajnanda/page-home-professional-services
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, professional services, consulting
 * Block Types: core/post-content
 * Post Types: page
 * Description: A trust-forward homepage for a professional-services firm — centered hero, proof points, services, process, and testimonials.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-centered',
    'ajnanda-pro/proof-points',
    'ajnanda/section-services-three-columns',
    'ajnanda-pro/three-step-process',
    'ajnanda/section-testimonials-cards',
    'ajnanda-pro/centered-final-cta',
));
