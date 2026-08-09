<?php
/**
 * Title: Resource Center
 * Slug: ajnanda/page-resource-center
 * Categories: ajnanda-page-designs
 * Keywords: resources, knowledge base, articles
 * Block Types: core/post-content
 * Post Types: page
 * Description: A resource hub page — intro, a linked tile grid of resource categories, latest articles, and a newsletter signup.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda-pro/linked-tile-grid',
    'ajnanda/section-blog-latest-articles',
    'ajnanda/section-newsletter-cta',
));
