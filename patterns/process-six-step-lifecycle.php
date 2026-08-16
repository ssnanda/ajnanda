<?php
/**
 * Title: Process — Six-Step Lifecycle
 * Slug: ajnanda/section-process-six-step
 * Categories: ajnanda-content
 * Keywords: process, steps, how it works, lifecycle, workflow
 * Description: Six numbered, equal-height steps in two rows of three — for operational lifecycles too long for the three-step pattern (onboarding flows, service cycles, case lifecycles).
 *
 * @package AJNanda
 */

if ( ! function_exists( 'ajnanda_render_lifecycle_step' ) ) :
function ajnanda_render_lifecycle_step( $number, $title, $description ) {
	ob_start();
	?>
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-ajnanda-card ajnanda-step-card has-content-align-center","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-ajnanda-card ajnanda-step-card has-content-align-center"><!-- wp:paragraph {"align":"center","className":"is-style-ajnanda-icon-tile-round"} -->
<p class="has-text-align-center is-style-ajnanda-icon-tile-round"><?php echo esc_html( $number ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center"><?php echo esc_html( $title ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php echo esc_html( $description ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->
	<?php
	return trim( ob_get_clean() );
}
endif;

$steps = array(
	array( '1', 'Listen', 'Explain what happens first and what the visitor needs to do.' ),
	array( '2', 'Organize', 'Explain the second stage of the process.' ),
	array( '3', 'Execute', 'Explain the third stage of the process.' ),
	array( '4', 'Document', 'Explain the fourth stage of the process.' ),
	array( '5', 'Report', 'Explain the fifth stage of the process.' ),
	array( '6', 'Improve', 'Explain the final stage and what the visitor gets.' ),
);

$row_one = array_slice( $steps, 0, 3 );
$row_two = array_slice( $steps, 3, 3 );
?>
<!-- wp:group {"align":"full","className":"builder-section builder-section-soft has-content-align-center animate-on-scroll","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull builder-section builder-section-soft has-content-align-center animate-on-scroll"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">How It Works</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"builder-section-intro"} -->
<p class="has-text-align-center builder-section-intro">Walk visitors through a longer operational cycle in two rows of three steps.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"className":"is-style-ajnanda-equal-height"} -->
<div class="wp-block-columns is-style-ajnanda-equal-height">
<?php foreach ( $row_one as $step ) { echo ajnanda_render_lifecycle_step( $step[0], $step[1], $step[2] ); } ?>
</div>
<!-- /wp:columns -->

<!-- wp:columns {"className":"is-style-ajnanda-equal-height"} -->
<div class="wp-block-columns is-style-ajnanda-equal-height">
<?php foreach ( $row_two as $step ) { echo ajnanda_render_lifecycle_step( $step[0], $step[1], $step[2] ); } ?>
</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
