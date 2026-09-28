<?php
/**
 * Philosophy — editorial dark section.
 *
 * @package ma-lumiere
 */

$quote = ml_mod( 'ml_philosophy_quote', __( 'Your skin deserves more than a routine. It deserves a plan.', 'ma-lumiere' ) );
$body  = ml_mod(
	'ml_philosophy_body',
	__( 'Beautiful skin begins with understanding — exactly how your skin behaves, what it has been through, and what it needs going forward. At Ma Lumière, every journey starts with a conversation and a careful assessment, then develops into a plan built around you.', 'ma-lumiere' )
);
?>
<section class="section philosophy">
	<div class="container container--narrow">
		<p class="eyebrow" data-reveal="fade" style="justify-content:center"><?php esc_html_e( 'Our philosophy', 'ma-lumiere' ); ?></p>
		<blockquote class="philosophy__quote" data-reveal="fade"><?php echo esc_html( $quote ); ?></blockquote>
		<p class="philosophy__body" data-reveal="fade"><?php echo esc_html( $body ); ?></p>
	</div>
</section>