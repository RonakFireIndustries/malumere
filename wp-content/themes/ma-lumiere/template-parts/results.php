<?php
/**
 * Before & After results section.
 *
 * Only consent-approved, identity-safe pairings are published. Real images
 * come from the `ml_before_after` content type; a clear placeholder is
 * shown until actual clinic results are provided.
 *
 * @package ma-lumiere
 */

$results = new WP_Query(
	array(
		'post_type'      => 'ml_before_after',
		'posts_per_page' => 4,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
?>

<section class="section results" id="results">
	<div class="container">

		<div class="section__head section__head--center">
			<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'Results', 'ma-lumiere' ); ?></p>
			<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'The difference, honestly shown', 'ma-lumiere' ); ?></h2>
			<p class="section__lead" data-reveal="fade">
				<?php esc_html_e( 'Real outcomes from the clinic, shared with consent and care for privacy. Individual results vary.', 'ma-lumiere' ); ?>
			</p>
		</div>

		<?php if ( $results->have_posts() ) : ?>
			<div class="results__grid">
				<?php
				while ( $results->have_posts() ) :
					$results->the_post();
					get_template_part( 'template-parts/before-after-card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<div class="ph">[REAL BEFORE & AFTER RESULTS REQUIRED — add consent-approved case studies]</div>
		<?php endif; ?>

	</div>
</section>