<?php
/**
 * Title: Home — Dental Practice
 * Slug: ajnanda/page-home-dental
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, dentist, dental, practice, clinic
 * Block Types: core/post-content
 * Post Types: page
 * Description: A welcoming homepage for a dental practice — hero with a smile photo placeholder, trust badges, core treatments, "your first visit" steps, patient testimonials, and a book-an-appointment call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-split',
    'ajnanda/section-trust-row',
    'ajnanda/section-services-three-columns',
    'ajnanda-pro/three-step-process',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-cta-gradient',
));
