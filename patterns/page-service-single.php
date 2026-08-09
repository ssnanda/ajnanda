<?php
/**
 * Title: Service — Single Service
 * Slug: ajnanda/page-service-single
 * Categories: ajnanda-page-designs
 * Keywords: service, single, detail
 * Block Types: core/post-content
 * Post Types: page
 * Description: A detail page for one individual service — intro, description, featured highlight, FAQ, related services, and a bold CTA.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-content-image-left',
    'ajnanda/section-services-featured',
    'ajnanda/section-faq-standard',
    'ajnanda/section-related-services',
    'ajnanda/section-cta-super-bold',
));
