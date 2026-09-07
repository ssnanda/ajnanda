<?php
/**
 * Title: Outreach & Education
 * Slug: ajnanda/page-outreach
 * Categories: ajnanda-page-designs
 * Keywords: outreach, education, public engagement, schools, workshops, STEM
 * Block Types: core/post-content
 * Post Types: page
 * Description: A public-engagement page — intro, a three-step "how to book a visit or workshop" flow, reach numbers, short quotes from teachers and organisers, and a call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda-pro/three-step-process',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-cta-split',
));
