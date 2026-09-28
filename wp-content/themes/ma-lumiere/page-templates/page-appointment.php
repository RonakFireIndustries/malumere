<?php
/**
 * Template Name: Appointment
 *
 * Public booking gate. The full multi-step booking flow (treatment → date →
 * slots → details → review → payment → verification → confirmation) is
 * provided by the Ma Lumière Clinic Manager plugin. When the plugin is
 * active it renders its [ml_booking] shortcode here; until then the page
 * presents clinic contact details so visitors can still book directly.
 *
 * @package ma-lumiere
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="page-hero">
		<div class="container">
			<?php ml_breadcrumbs(); ?>
			<h1 class="page-hero__title"><?php the_title(); ?></h1>
			<p class="page-hero__lead"><?php esc_html_e( 'Choose the treatment, pick a slot that suits you, and confirm. Private, simple and secure.', 'ma-lumiere' ); ?></p>
		</div>
	</section>
	<?php
endwhile;
?>

<section class="section" style="padding-top:var(--sp-xl)">
	<div class="container container--narrow">

		<div data-reveal="fade">
			<?php
			// Booking widget slot — populated by the clinic plugin when active.
			echo do_shortcode( '[ml_booking]' ); // phpcs:ignore WordPress.Security.EscapeOutput -- rendered output escaped internally.
			?>
			<noscript>
				<div class="ph"><?php esc_html_e( 'Please enable JavaScript to use the online booking form, or contact the clinic directly.', 'ma-lumiere' ); ?></div>
			</noscript>
		</div>

		<?php if ( ! shortcode_exists( 'ml_booking' ) ) : ?>
			<div class="ph" style="margin-top:var(--sp-lg)">
				<?php esc_html_e( 'Online booking opens soon — please call or WhatsApp to reserve your consultation.', 'ma-lumiere' ); ?>
			</div>
			<?php if ( ml_clinic_phone() || ml_clinic_whatsapp() ) : ?>
				<div class="td-cta" style="text-align:center;margin-top:var(--sp-lg)">
					<?php if ( ml_clinic_phone() ) : ?>
						<a class="btn btn--primary" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', ml_clinic_phone() ) ); ?>"><?php echo esc_html( ml_clinic_phone() ); ?></a>
					<?php endif; ?>
					<?php if ( ml_clinic_whatsapp() ) : ?>
						<a class="btn btn--outline" href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', ml_clinic_whatsapp() ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp the clinic', 'ma-lumiere' ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	</div>
</section>

<?php
get_template_part( 'template-parts/journey' );
get_footer();