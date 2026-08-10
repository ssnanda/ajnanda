<?php
/**
 * Title: Home — Developer Portfolio
 * Slug: ajnanda/page-home-developer
 * Categories: ajnanda-page-designs
 * Keywords: home, homepage, developer, coder, programmer, portfolio, tech
 * Block Types: core/post-content
 * Post Types: page
 * Description: A clean, spacious homepage for a developer/coder portfolio — hero, a skills/tech-stack grid, featured project cards, and a contact CTA. Pairs well with the "Developer Portfolio" Site Kit.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-minimal',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-services-card-grid',
    'ajnanda/section-cta-minimal',
));
