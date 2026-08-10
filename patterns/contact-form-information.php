<?php
/**
 * Title: Contact — Form + Information
 * Slug: ajnanda/section-contact-form-information
 * Categories: ajnanda-contact
 * Keywords: contact, form, ajforms, ajcore
 * Description: A two-column contact layout pairing an AJ Core form with contact details. AJNanda does not ship its own form system — this pattern embeds the AJCore [ajforms] shortcode via the core Shortcode block. Requires the AJCore plugin.
 *
 * @package AJNanda
 */
?>
<!-- wp:group {"align":"full","className":"builder-section animate-on-scroll","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull builder-section animate-on-scroll"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Contact Us</h2>
<!-- /wp:heading -->

<!-- wp:columns {"verticalAlignment":"top","className":"builder-split"} -->
<div class="wp-block-columns are-vertically-aligned-top builder-split"><!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"className":"is-style-ajnanda-card-soft"} -->
<div class="wp-block-group is-style-ajnanda-card-soft"><!-- wp:paragraph -->
<p><strong>Add your AJ Core form here.</strong> Create a form under <em>AJ Core → Forms</em>, then replace the ID below with the real form ID.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[ajforms id="REPLACE_WITH_YOUR_FORM_ID"]
<!-- /wp:shortcode --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">Prefer to reach out directly?</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>Phone:</strong> <a href="tel:+10000000000">(000) 000-0000</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Email:</strong> <a href="mailto:hello@example.com">hello@example.com</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Hours:</strong> Monday–Friday, 9am–5pm</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
