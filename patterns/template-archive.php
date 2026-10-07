<?php
/**
 * Title: Archive — Heading + Post Grid
 * Slug: ajnanda/template-archive
 * Categories: ajnanda-templates
 * Keywords: slot-archive, archive, blog, category
 * Description: Archive title, description and a paginated post grid that follows the main query. Pick it under Customizer > Template Slots > Archive / blog index.
 *
 * @package AJNanda
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"4rem","bottom":"5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group alignfull" style="padding-top:4rem;padding-right:1.5rem;padding-bottom:5rem;padding-left:1.5rem">
<!-- wp:query-title {"type":"archive","level":1,"align":"wide"} /-->
<!-- wp:term-description {"align":"wide"} /-->
<!-- wp:query {"queryId":1,"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"align":"wide"} -->
<div class="wp-block-query alignwide">
<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2"} /-->
<!-- wp:post-title {"isLink":true,"level":2,"fontSize":"large"} /-->
<!-- wp:post-date /-->
<!-- wp:post-excerpt {"excerptLength":24} /-->
<!-- /wp:post-template -->
<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->
<!-- wp:query-no-results -->
<!-- wp:paragraph --><p>No posts found.</p><!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->
