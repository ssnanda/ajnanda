<?php
/**
 * Title: Sports Medicine
 * Slug: ajnanda/page-sports-medicine
 * Categories: ajnanda-page-designs
 * Keywords: sports medicine, athletes, injury, performance, return to play, physical therapy
 * Block Types: core/post-content
 * Post Types: page
 * Description: A sports-medicine focus page — split hero, a program feature with an image placeholder, three columns of services, return-to-play numbers, an athlete endorsement, and a call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-split',
    'ajnanda/section-content-image-right',
    'ajnanda/section-services-three-columns',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-gradient',
));
