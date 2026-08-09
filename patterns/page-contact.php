<?php
/**
 * Title: Contact
 * Slug: ajnanda/page-contact
 * Categories: ajnanda-page-designs
 * Keywords: contact, form, get in touch
 * Block Types: core/post-content
 * Post Types: page
 * Description: A standard contact page — intro, AJ Core contact form paired with direct details, and a general contact information row.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda/section-contact-form-information',
    'ajnanda/section-contact-information',
));
