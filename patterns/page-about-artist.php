<?php
/**
 * Title: About — Artist
 * Slug: ajnanda/page-about-artist
 * Categories: ajnanda-page-designs
 * Keywords: about, bio, artist, story, musician
 * Block Types: core/post-content
 * Post Types: page
 * Description: A narrative artist bio page — intro, a bold mission/sound statement, an origin story, a quote, and a booking CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-large-statement',
    'ajnanda/section-content-image-right',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-minimal',
));
