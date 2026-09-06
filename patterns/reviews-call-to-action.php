<?php
/**
 * Title: Reviews Call to Action
 * Slug: ajnanda/reviews-call-to-action
 * Categories: ajnanda-social-proof
 * Keywords: reviews, testimonials, google
 * Description: Editable AJ Core-backed review collection.
 */
$attributes = array(
    'heading' => __( 'Share your experience', 'ajnanda' ),
    'supportingText' => __( 'Read featured feedback and leave your own review on Google.', 'ajnanda' ),
    'layout' => 'featured',
    'limit' => 1,
    'showViewButton' => true,
    'showWriteButton' => true,
);
?>
<!-- wp:ajnanda/google-reviews <?php echo wp_json_encode( $attributes ); ?> /-->
