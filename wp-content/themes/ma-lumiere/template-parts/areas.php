<?php
/**
 * Service area section.
 *
 * Reinforces the clinic's real location and the neighbourhoods it serves.
 * Every area name comes from the SEO & Local Search Customizer section, so the
 * visible copy and the structured data never drift apart.
 *
 * @package ma-lumiere
 */

$areas       = ml_areas_served();
$locality    = ml_clinic_locality();
$local_url   = ml_page_url_by_template( 'page-templates/page-mira-bhayander.php', '' );
$booking_url = ml_page_url_by_template( 'page-templates/page-appointment.php' );
$hide_link   = ! empty( $args['hide_link'] );
?>

<section class="section section--tint areas" id="areas">
	<div class="container">

		<div class="section__head section__head--center">
			<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'Serving Mira Bhayander', 'ma-lumiere' ); ?></p>
			<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'A skin clinic for Mira Road & Bhayander', 'ma-lumiere' ); ?></h2>
			<p class="section__lead" data-reveal="fade">
				<?php
				printf(
					/* translators: %s: clinic locality. */
					esc_html__( 'Ma Lumière is located in %s, a short drive from Bhayander, Kashimira, Dahisar and Borivali. Patients come to us for face treatments, acne and pigmentation care, laser hair reduction and hair loss assessment.', 'ma-lumiere' ),
					esc_html( $locality )
				);
				?>
			</p>
		</div>

		<?php if ( $areas ) : ?>
			<ul class="chips" data-reveal="fade">
				<?php foreach ( $areas as $area ) : ?>
					<li><span class="chip"><?php echo esc_html( $area ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<p class="section__head section__head--center" style="margin:var(--sp-lg) 0 0" data-reveal="fade">
			<?php if ( $local_url && ! $hide_link ) : ?>
				<a class="link-arrow" href="<?php echo esc_url( $local_url ); ?>">
					<?php esc_html_e( 'Skin clinic in Mira Bhayander', 'ma-lumiere' ); ?>
					<?php ml_icon( 'arrow-right', 18 ); ?>
				</a>
			<?php else : ?>
				<a class="link-arrow" href="<?php echo esc_url( $booking_url ); ?>">
					<?php esc_html_e( 'Book a consultation', 'ma-lumiere' ); ?>
					<?php ml_icon( 'arrow-right', 18 ); ?>
				</a>
			<?php endif; ?>
		</p>

	</div>
</section>
