<?php
/**
 * Title: Services — Dental Practice
 * Slug: ajnanda/page-services-dental
 * Categories: ajnanda-page-designs
 * Keywords: services, treatments, dentist, dental, cleanings, implants, whitening, orthodontics
 * Block Types: core/post-content
 * Post Types: page
 * Description: A treatments overview for a dental practice — intro, a grid of treatment categories, alternating detail rows, common questions, and a book-an-appointment call to action.
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
