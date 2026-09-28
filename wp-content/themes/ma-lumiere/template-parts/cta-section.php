<?php
/**
 * Appointment CTA.
 *
 * The booking front-end (multi-step flow + payment) is implemented by the
 * Ma Lumière Clinic Manager plugin in a later phase; this links to the
 * appointment page which will host that flow.
 *
 * @package ma-lumiere
 */
?>
<section class="section">
	<div class="container">
		<div class="cta__inner" data-reveal="fade">
			<p class="eyebrow" style="justify-content:center"><?php esc_html_e( 'Begin your skin journey', 'ma-lumiere' ); ?></p>
			<h2 class="section__title"><?php esc_html_e( 'Begin Your Skin Journey', 'ma-lumiere' ); ?></h2>
			<p><?php esc_html_e( 'Book a private consultation and let us design a plan around your skin — its history, its concerns and the results you hope for.', 'ma-lumiere' ); ?></p>
			<div class="cta__actions">
				<a class="btn btn--light" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-appointment.php' ) ); ?>">
					<?php esc_html_e( 'Book a Consultation', 'ma-lumiere' ); ?>
				</a>
				<a class="btn btn--outline" style="border-color:var(--color-ivory);color:var(--color-ivory)" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-contact.php' ) ); ?>">
					<?php esc_html_e( 'Contact the clinic', 'ma-lumiere' ); ?>
				</a>
			</div>
		</div>
	</div>
</section>