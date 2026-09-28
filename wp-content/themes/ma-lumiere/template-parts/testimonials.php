<?php
/**
 * Testimonials — real, consented feedback only.
 *
 * @package ma-lumiere
 */

$testimonials = new WP_Query(
	array(
		'post_type'      => 'ml_testimonial',
		'posts_per_page' => 6,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
?>

<section class="section section--tint testimonials">
	<div class="container">

		<div class="section__head section__head--center">
			<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'Kind words', 'ma-lumiere' ); ?></p>
			<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'From our patients', 'ma-lumiere' ); ?></h2>
		</div>

		<?php if ( $testimonials->have_posts() ) : ?>
			<div class="testimonials__grid">
				<?php
				while ( $testimonials->have_posts() ) :
					$testimonials->the_post();
					get_template_part( 'template-parts/testimonial' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<div class="ph">[REAL PATIENT TESTIMONIAL REQUIRED — publish only with patient consent]</div>
		<?php endif; ?>

	</div>
</section>