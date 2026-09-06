<?php
/**
 * Title: Featured Review with Google Rating Summary
 * Slug: ajnanda/reviews-featured-summary
 * Categories: ajnanda-social-proof
 * Keywords: reviews, testimonials, google
 * Description: Editable AJ Core-backed review collection.
 */
$attributes = array(
    'heading' => __( 'A featured review', 'ajnanda' ),
    'layout' => 'featured',
    'limit' => 1,
    'showOverallRating' => true,
    'showTotal' => true,
);
?>
<!-- wp:ajnanda/google-reviews <?php echo wp_json_encode( $attributes ); ?> /-->
