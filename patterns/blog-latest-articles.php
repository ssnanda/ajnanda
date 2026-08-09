<?php
/**
 * Title: Blog / Latest Articles
 * Slug: ajnanda/section-blog-latest-articles
 * Categories: ajnanda-footer
 * Keywords: blog, articles, posts, news
 * Description: A three-column grid of the latest posts using WordPress core's native Query Loop — automatically stays current, no custom post block required.
 *
 * @package AJNanda
 */
?>
<!-- wp:group {"align":"full","className":"builder-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull builder-section"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">From the Blog</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":1,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:post-featured-image {"isLink":true} /-->

<!-- wp:post-title {"level":4,"isLink":true} /-->

<!-- wp:post-excerpt {"excerptLength":20} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/blog/">View All Articles</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
