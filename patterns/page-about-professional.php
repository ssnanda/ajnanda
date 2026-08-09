<?php
/**
 * Title: About — Professional
 * Slug: ajnanda/page-about-professional
 * Categories: ajnanda-page-designs
 * Keywords: about, professional, credentials, leadership
 * Block Types: core/post-content
 * Post Types: page
 * Description: A credentials-forward About page for a professional-services or advisory firm — comparison of engagement options, certifications, and leadership.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda-pro/provider-comparison',
    'ajnanda/section-trust-row',
    'ajnanda/section-team-leadership',
    'ajnanda/section-cta-split',
));
