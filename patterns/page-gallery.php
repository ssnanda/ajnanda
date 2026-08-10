<?php
/**
 * Title: Gallery
 * Slug: ajnanda/page-gallery
 * Categories: ajnanda-page-designs
 * Keywords: gallery, photos, pictures, showcase
 * Block Types: core/post-content
 * Post Types: page
 * Description: A picture/showcase page — intro plus a grid of placeholder image tiles (replace each with a real Image block) and a simple call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-cta-minimal',
));
