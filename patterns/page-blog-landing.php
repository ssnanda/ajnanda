<?php
/**
 * Title: Blog Landing
 * Slug: ajnanda/page-blog-landing
 * Categories: ajnanda-page-designs
 * Keywords: blog, landing, news
 * Block Types: core/post-content
 * Post Types: page
 * Description: A blog landing page intro plus a latest-articles query loop and a newsletter signup — a good starting point for a page assigned as the site's Posts page.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-blog-latest-articles',
    'ajnanda/section-newsletter-cta',
));
