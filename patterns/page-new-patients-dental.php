<?php
/**
 * Title: New Patients — Dental Practice
 * Slug: ajnanda/page-new-patients-dental
 * Categories: ajnanda-page-designs
 * Keywords: new patients, first visit, dentist, dental, forms, what to expect
 * Block Types: core/post-content
 * Post Types: page
 * Description: A new-patient welcome page — intro, what to expect on the first visit, a place for patient forms with an image placeholder, common questions, and a call to action.
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
