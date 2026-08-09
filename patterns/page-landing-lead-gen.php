<?php
/**
 * Title: Landing Page — Lead Generation
 * Slug: ajnanda/page-landing-lead-gen
 * Categories: ajnanda-page-designs
 * Keywords: landing page, lead generation, campaign
 * Block Types: core/post-content
 * Post Types: page
 * Description: A focused, no-navigation-distraction lead-gen landing page — bold hero, trust signals, benefits, testimonials, and a form. Pair with the Blank/Landing page template for a distraction-free layout.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-super-bold',
    'ajnanda/section-trust-row',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-contact-form-information',
));
