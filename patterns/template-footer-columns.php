<?php
/**
 * Title: Footer — Columns
 * Slug: ajnanda/template-footer-columns
 * Categories: ajnanda-templates
 * Keywords: slot-footer, footer, columns
 * Description: Brand column plus three link columns and a legal bar. Pick it under Customizer > Template Slots > Footer. Colors follow the Footer color settings.
 *
 * @package AJNanda
 */
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"2rem","left":"3rem"}}}} -->
<div class="wp-block-columns alignwide">
<!-- wp:column {"width":"34%"} -->
<div class="wp-block-column" style="flex-basis:34%">
<!-- wp:site-logo {"width":140} /-->
<!-- wp:paragraph -->
<p>A short description of the business goes here.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"small"} -->
<h3 class="wp-block-heading has-small-font-size">Company</h3>
<!-- /wp:heading -->
<!-- wp:list {"className":"is-style-ajnanda-footer-links"} -->
<ul class="wp-block-list is-style-ajnanda-footer-links"><!-- wp:list-item --><li><a href="/about/">About</a></li><!-- /wp:list-item --><!-- wp:list-item --><li><a href="/team/">Team</a></li><!-- /wp:list-item --><!-- wp:list-item --><li><a href="/contact/">Contact</a></li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"small"} -->
<h3 class="wp-block-heading has-small-font-size">Services</h3>
<!-- /wp:heading -->
<!-- wp:list {"className":"is-style-ajnanda-footer-links"} -->
<ul class="wp-block-list is-style-ajnanda-footer-links"><!-- wp:list-item --><li><a href="/services/">Overview</a></li><!-- /wp:list-item --><!-- wp:list-item --><li><a href="/pricing/">Pricing</a></li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"small"} -->
<h3 class="wp-block-heading has-small-font-size">Contact</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>123 Main Street<br>hello@example.com</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:separator {"align":"wide","className":"is-style-wide"} -->
<hr class="wp-block-separator has-alpha-channel-opacity alignwide is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide">
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">© Your Company. All rights reserved.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><a href="/privacy/">Privacy</a> · <a href="/terms/">Terms</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
