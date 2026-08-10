<?php
/**
 * Title: Home — Personal
 * Slug: ajnanda/page-home-personal
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, personal, hobby, fan site, kids
 * Block Types: core/post-content
 * Post Types: page
 * Description: A friendly homepage for a personal, hobby, or fan site — soft hero, a grid for favorites/interests, a follow/subscribe signup, and a simple call to action. Pairs well with the "Bubblegum Pop" Site Kit.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-minimal',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-newsletter-cta',
    'ajnanda/section-cta-minimal',
));
