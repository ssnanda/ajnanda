<?php
/**
 * Title: Conditions & Treatments
 * Slug: ajnanda/page-conditions-treatments
 * Categories: ajnanda-page-designs
 * Keywords: conditions, treatments, procedures, orthopedic, joint replacement, arthroscopy
 * Block Types: core/post-content
 * Post Types: page
 * Description: A conditions-and-treatments overview — intro, a grid of condition categories, alternating detail rows for key procedures, common questions, and a call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-services-card-grid',
    'ajnanda/section-services-alternating-rows',
    'ajnanda/section-faq-accordion',
    'ajnanda/section-cta-gradient',
));
