<?php
/**
 * Title: For Patients
 * Slug: ajnanda/page-for-patients
 * Categories: ajnanda-page-designs
 * Keywords: patients, first visit, forms, pre-op, what to expect, recovery
 * Block Types: core/post-content
 * Post Types: page
 * Description: A patient-information page — intro, a three-step "what to expect" flow, a place for intake and pre-op forms with an image placeholder, common questions, and a call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda-pro/three-step-process',
    'ajnanda/section-content-image-left',
    'ajnanda/section-faq-accordion',
    'ajnanda/section-cta-split',
));
