<?php
/**
 * Title: FAQ
 * Slug: ajnanda/page-faq
 * Categories: ajnanda-page-designs
 * Keywords: faq, questions, help
 * Block Types: core/post-content
 * Post Types: page
 * Description: A dedicated FAQ page — intro and a full collapsible accordion of questions.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-faq-accordion',
    'ajnanda/section-cta-minimal',
));
