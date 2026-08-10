<?php
/**
 * Title: Home — Music Artist
 * Slug: ajnanda/page-home-music-artist
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, music, artist, dj, band
 * Block Types: core/post-content
 * Post Types: page
 * Description: An energetic homepage for a musician/DJ/band — bold hero, sound/genre grid, career stats, fan or press quotes, and a booking CTA. Pairs well with the "Neon Night" Site Kit.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-gradient',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-testimonials-cards',
    'ajnanda/section-cta-split',
));
