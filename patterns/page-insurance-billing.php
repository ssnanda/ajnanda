<?php
/**
 * Title: Insurance & Billing
 * Slug: ajnanda/page-insurance-billing
 * Categories: ajnanda-page-designs
 * Keywords: insurance, billing, payment, coverage, financing, estimates
 * Block Types: core/post-content
 * Post Types: page
 * Description: An insurance-and-billing page for a medical practice — intro, a feature grid for accepted plans and payment options, common billing questions, and a light call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-faq-standard',
    'ajnanda/section-cta-minimal',
));
