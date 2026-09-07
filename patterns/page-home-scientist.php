<?php
/**
 * Title: Home — Researcher / Scientist
 * Slug: ajnanda/page-home-scientist
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, researcher, scientist, academic, astrophysicist, professor, lab
 * Block Types: core/post-content
 * Post Types: page
 * Description: A homepage for a researcher or science communicator — split hero with a portrait placeholder, affiliations row, a bold research statement, research areas, publication and impact numbers, a peer endorsement, and a collaborate / invite-to-speak call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-split',
    'ajnanda/section-trust-row',
    'ajnanda/section-content-large-statement',
    'ajnanda/section-services-three-columns',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-gradient',
));
