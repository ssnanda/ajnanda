<?php
/**
 * Title: About — Company
 * Slug: ajnanda/page-about-company
 * Categories: ajnanda-page-designs
 * Keywords: about, company, overview
 * Block Types: core/post-content
 * Post Types: page
 * Description: A standard About page — intro, company overview, stats, and team preview.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-image-left',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-team-grid',
    'ajnanda/section-cta-minimal',
));
