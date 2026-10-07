<?php
/**
 * Title: Sticky Media Stage
 * Slug: ajnanda/media-sticky-stage
 * Categories: ajnanda-media
 * Keywords: sticky, scrollytelling, steps, media
 * Description: Sticky media column beside scrolling steps. The "has-stage-swap" class swaps the image to match the active step (image N = step N); remove it for a plain sticky column. Stacks and unsticks on mobile.
 *
 * @package AJNanda
 */
?>
<!-- wp:columns {"align":"wide","className":"is-style-ajnanda-sticky-stage has-stage-swap"} -->
<div class="wp-block-columns alignwide is-style-ajnanda-sticky-stage has-stage-swap">
<!-- wp:column {"className":"ajn-stage-media"} -->
<div class="wp-block-column ajn-stage-media">
<!-- wp:group {"className":"ajn-stage-sticky"} -->
<div class="wp-block-group ajn-stage-sticky">
<!-- wp:image {"aspectRatio":"4/3","scale":"cover"} -->
<figure class="wp-block-image"><img alt="" style="aspect-ratio:4/3;object-fit:cover"/></figure>
<!-- /wp:image -->
<!-- wp:image {"aspectRatio":"4/3","scale":"cover"} -->
<figure class="wp-block-image"><img alt="" style="aspect-ratio:4/3;object-fit:cover"/></figure>
<!-- /wp:image -->
<!-- wp:image {"aspectRatio":"4/3","scale":"cover"} -->
<figure class="wp-block-image"><img alt="" style="aspect-ratio:4/3;object-fit:cover"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->

<!-- wp:column {"className":"ajn-stage-steps"} -->
<div class="wp-block-column ajn-stage-steps">
<!-- wp:group {"className":"ajn-stage-step"} -->
<div class="wp-block-group ajn-stage-step">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Step one</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Describe the first step.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"ajn-stage-step"} -->
<div class="wp-block-group ajn-stage-step">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Step two</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Describe the second step.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"ajn-stage-step"} -->
<div class="wp-block-group ajn-stage-step">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Step three</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Describe the third step.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
