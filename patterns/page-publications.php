<?php
/**
 * Title: Publications
 * Slug: ajnanda/page-publications
 * Categories: ajnanda-page-designs
 * Keywords: publications, papers, bibliography, research output, citations
 * Block Types: core/post-content
 * Post Types: page
 * Description: A publications page — intro, a highlighted result statement, a feature grid for selected papers, notes on access and citation, and a light call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-large-statement',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-faq-standard',
    'ajnanda/section-cta-minimal',
));
