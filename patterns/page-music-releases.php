<?php
/**
 * Title: Music / Releases
 * Slug: ajnanda/page-music-releases
 * Categories: ajnanda-page-designs
 * Keywords: music, releases, mixes, discography, tracks
 * Block Types: core/post-content
 * Post Types: page
 * Description: A releases/mixes page — intro, a grid for tracks or mixes (replace the placeholder cards with your own titles/links), a press or fan quote, and a simple call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-minimal',
));
