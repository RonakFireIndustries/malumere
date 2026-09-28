<?php
/**
 * Single testimonial card.
 *
 * @package ma-lumiere
 */

$author    = get_post_meta( get_the_ID(), '_ml_testi_author', true );
$treatment = get_post_meta( get_the_ID(), '_ml_testi_treatment', true );
?>
<figure class="testimonial-card" data-reveal="up">
	<span class="testimonial-card__mark" aria-hidden="true">“</span>
	<blockquote class="testimonial-card__quote"><?php echo esc_html( wp_strip_all_tags( get_the_content() ) ); ?></blockquote>
	<figcaption class="testimonial-card__author">
		<strong><?php echo esc_html( $author ? $author : __( 'Verified patient', 'ma-lumiere' ) ); ?></strong>
		<span><?php echo esc_html( $treatment ); ?></span>
	</figcaption>
</figure>