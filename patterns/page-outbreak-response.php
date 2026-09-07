<?php
/**
 * Title: Outbreak Response
 * Slug: ajnanda/page-outbreak-response
 * Categories: ajnanda-page-designs
 * Keywords: outbreak, surveillance, response, sequencing, diagnostics, public health, biosafety
 * Block Types: core/post-content
 * Post Types: page
 * Description: A rapid-response page for a lab or research group — intro, a three-step response flow, turnaround and throughput metrics, a grid of capabilities (sequencing, serology, containment), and a partner / media-enquiry call to action.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-page-intro',
    'ajnanda-pro/three-step-process',
    'ajnanda/section-results-metrics',
    'ajnanda/section-content-feature-grid',
    'ajnanda/section-cta-split',
));
