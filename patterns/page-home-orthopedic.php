<?php
/**
 * Title: Home — Orthopedic Practice
 * Slug: ajnanda/page-home-orthopedic
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, orthopedic, orthopaedic, surgeon, joint, sports medicine, clinic
 * Block Types: core/post-content
 * Post Types: page
 * Description: A homepage for an orthopedic practice — split hero with a photo placeholder, credentials row, a grid of conditions treated, a "path to recovery" flow, outcome numbers, patient stories, and a request-a-consult call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-split',
    'ajnanda/section-trust-row',
    'ajnanda/section-services-card-grid',
    'ajnanda-pro/three-step-process',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-cta-gradient',
));
