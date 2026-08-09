<?php
/**
 * Title: Home — Small Business
 * Slug: ajnanda/page-home-small-business
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, small business, local
 * Block Types: core/post-content
 * Post Types: page
 * Description: A friendly, no-frills homepage for a small or local business — minimal hero, features, services, and direct contact information.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-minimal',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-services-three-columns',
    'ajnanda/section-contact-information',
    'ajnanda/section-cta-minimal',
));
