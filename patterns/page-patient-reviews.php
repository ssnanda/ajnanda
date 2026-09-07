<?php
/**
 * Title: Patient Reviews — Dental Practice
 * Slug: ajnanda/page-patient-reviews
 * Categories: ajnanda-page-designs
 * Keywords: reviews, testimonials, patients, dentist, dental, feedback
 * Block Types: core/post-content
 * Post Types: page
 * Description: A patient-reviews page — intro, a set of testimonial cards, one featured endorsement, and a prompt to leave a review. Swap the placeholder quotes for real ones, or connect AJ Core Reviews for live Google reviews.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-gradient',
));
