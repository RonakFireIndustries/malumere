<?php
/**
 * 404 page.
 *
 * @package ma-lumiere
 */

get_header(); ?>

<section class="section">
	<div class="container">
		<div class="error-404__inner">
			<p class="error-404__code" aria-hidden="true">404</p>
			<h1><?php esc_html_e( 'This page has gone for a consultation.', 'ma-lumiere' ); ?></h1>
			<p><?php esc_html_e( 'We could not find the page you were looking for. It may have been moved, renamed or retired gracefully.', 'ma-lumiere' ); ?></p>
			<p>
				<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return home', 'ma-lumiere' ); ?></a>
				<a class="btn btn--outline" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-appointment.php' ) ); ?>"><?php esc_html_e( 'Book a Consultation', 'ma-lumiere' ); ?></a>
			</p>
			<?php get_search_form(); ?>
		</div>
	</div>
</section>

<?php
get_footer();