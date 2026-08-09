<?php
/**
 * Title: Partners
 * Slug: ajnanda/page-partners
 * Categories: ajnanda-page-designs
 * Keywords: partners, alliances, network
 * Block Types: core/post-content
 * Post Types: page
 * Description: A partners/alliances page — intro, partner logo wall, a statement about the partner program, and contact options.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-logo-row',
    'ajnanda/section-content-large-statement',
    'ajnanda-pro/contact-cards',
    'ajnanda/section-cta-split',
));
