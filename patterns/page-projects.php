<?php
/**
 * Title: Projects
 * Slug: ajnanda/page-projects
 * Categories: ajnanda-page-designs
 * Keywords: projects, portfolio, work, case studies, developer
 * Block Types: core/post-content
 * Post Types: page
 * Description: A project showcase page — intro, a grid of project cards (replace placeholders with real project names/links/screenshots), and a contact CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-services-card-grid',
    'ajnanda/section-cta-minimal',
));
