<?php
/**
 * Title: Single Post — Hero + Reading Column
 * Slug: ajnanda/template-single-post
 * Categories: ajnanda-templates
 * Keywords: slot-single, single, post, article
 * Description: Wide hero, readable column, prev/next, related posts and a CTA slot. Pick it under Customizer > Template Slots > Single post.
 *
 * @package AJNanda
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"3rem","bottom":"2rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group alignfull" style="padding-top:3rem;padding-right:1.5rem;padding-bottom:2rem;padding-left:1.5rem">
<!-- wp:post-title {"level":1,"align":"wide"} /-->
<!-- wp:post-date /-->
<!-- wp:post-featured-image {"aspectRatio":"21/9","align":"wide"} /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group alignfull" style="padding-right:1.5rem;padding-left:1.5rem">
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
<!-- wp:post-terms {"term":"post_tag"} /-->
<!-- wp:ajnanda/slot {"slot":"post-cta"} /-->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"style":{"spacing":{"margin":{"top":"3rem"}}}} -->
<div class="wp-block-group" style="margin-top:3rem">
<!-- wp:post-navigation-link {"type":"previous","label":"Previous","showTitle":true} /-->
<!-- wp:post-navigation-link {"type":"next","label":"Next","showTitle":true} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"3rem","bottom":"4rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group alignfull" style="padding-top:3rem;padding-right:1.5rem;padding-bottom:4rem;padding-left:1.5rem">
<!-- wp:heading {"align":"wide","level":2} -->
<h2 class="wp-block-heading alignwide">Related posts</h2>
<!-- /wp:heading -->
<!-- wp:query {"queryId":0,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false},"align":"wide","className":"ajn-related-posts"} -->
<div class="wp-block-query alignwide ajn-related-posts">
<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2"} /-->
<!-- wp:post-title {"isLink":true,"level":3,"fontSize":"medium"} /-->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->
