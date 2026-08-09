<?php
/**
 * Title: Locations
 * Slug: ajnanda/page-locations
 * Categories: ajnanda-page-designs
 * Keywords: locations, offices, branches
 * Block Types: core/post-content
 * Post Types: page
 * Description: A locations page — intro, a grid of office/branch locations, and a short FAQ.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-contact-locations',
    'ajnanda/section-faq-standard',
));
