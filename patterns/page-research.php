<?php
/**
 * Title: Research
 * Slug: ajnanda/page-research
 * Categories: ajnanda-page-designs
 * Keywords: research, projects, methods, lab, science, astrophysics
 * Block Types: core/post-content
 * Post Types: page
 * Description: A research overview page — intro, alternating rows for each research thread, a feature grid for methods and instruments, a metrics strip, and a call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-services-alternating-rows',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-results-metrics',
    'ajnanda/section-cta-split',
));
