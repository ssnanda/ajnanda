<?php
/**
 * Title: About — Dental Practice
 * Slug: ajnanda/page-about-dental
 * Categories: ajnanda-page-designs
 * Keywords: about, dentist, dental, practice, team, mission
 * Block Types: core/post-content
 * Post Types: page
 * Description: An About page for a dental practice — intro, the practice story with an image placeholder, numbers that build trust, the care team, credentials, and a call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-image-right',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-team-grid',
    'ajnanda/section-trust-row',
    'ajnanda/section-cta-split',
));
