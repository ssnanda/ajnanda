<?php
/**
 * Title: Shows / Tour Dates
 * Slug: ajnanda/page-tour-dates
 * Categories: ajnanda-page-designs
 * Keywords: shows, tour, dates, events, gigs, booking
 * Block Types: core/post-content
 * Post Types: page
 * Description: An upcoming-shows page — intro, a grid of dates/venues (built from the Locations section, repurposed as a tour-stop list), a booking FAQ, and a contact CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-contact-locations',
    'ajnanda/section-faq-standard',
    'ajnanda/section-cta-split',
));
