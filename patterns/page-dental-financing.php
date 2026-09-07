<?php
/**
 * Title: Insurance & Financing — Dental Practice
 * Slug: ajnanda/page-dental-financing
 * Categories: ajnanda-page-designs
 * Keywords: insurance, financing, payment, dentist, dental, plans, membership
 * Block Types: core/post-content
 * Post Types: page
 * Description: An insurance-and-financing page for a dental practice — intro, a feature grid for accepted plans and payment options, common billing questions, and a call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-faq-standard',
    'ajnanda/section-cta-minimal',
));
