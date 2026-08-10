<?php
/**
 * Title: Newsletter CTA
 * Slug: ajnanda/section-newsletter-cta
 * Categories: ajnanda-footer
 * Keywords: newsletter, subscribe, email, ajforms
 * Description: A centered newsletter signup band. AJNanda has no built-in email system — embed an AJ Core form (or your email provider's embed code) via the Shortcode/Custom HTML block in place of the placeholder shortcode.
 *
 * @package AJNanda
 */
?>
<!-- wp:group {"align":"full","className":"builder-section builder-section-soft has-content-align-center animate-on-scroll","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull builder-section builder-section-soft has-content-align-center animate-on-scroll"><!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">Stay in the loop</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Get occasional updates — no spam, unsubscribe anytime.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[ajforms id="REPLACE_WITH_YOUR_NEWSLETTER_FORM_ID"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->
