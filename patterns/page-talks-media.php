<?php
/**
 * Title: Talks & Media
 * Slug: ajnanda/page-talks-media
 * Categories: ajnanda-page-designs
 * Keywords: talks, keynotes, lectures, media, press, podcast, interviews
 * Block Types: core/post-content
 * Post Types: page
 * Description: A speaking-and-press page — intro, a keynote feature with an image placeholder, a grid of talk topics, and a call to action for booking or press enquiries.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-image-right',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-cta-gradient',
));
