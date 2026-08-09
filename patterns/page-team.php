<?php
/**
 * Title: Team
 * Slug: ajnanda/page-team
 * Categories: ajnanda-page-designs
 * Keywords: team, staff, people
 * Block Types: core/post-content
 * Post Types: page
 * Description: A full team page — intro, team grid, and a leadership spotlight.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-team-grid',
    'ajnanda/section-team-leadership',
    'ajnanda/section-cta-minimal',
));
