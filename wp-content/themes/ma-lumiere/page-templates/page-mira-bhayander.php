<?php
/**
 * Template Name: Local Clinic (Mira Bhayander)
 *
 * A dedicated local landing page for searches such as “skin clinic in Mira
 * Bhayander” and “face treatment Mira Road”. It repeats the clinic's real
 * location, treatment menu and FAQs in one crawlable, internally linked page.
 *
 * The page body is editable in WordPress; a short default introduction is
 * shown until the page has content so the template is never blank.
 *
 * @package ma-lumiere
 */

get_header();

$booking_url = ml_page_url_by_template( 'page-templates/page-appointment.php' );
$locality    = ml_clinic_locality();
$area        = ml_seo_local_area();
$phone       = ml_clinic_phone();

$treatments = get_posts(
	array(
		'post_type'      => 'ml_treatment',
		'posts_per_page' => 9,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	)
);

while ( have_posts() ) :
	the_post();
	?>

	<section class="page-hero">
		<div class="container">
			<?php ml_breadcrumbs(); ?>

			<h1 class="page-hero__title"><?php the_title(); ?></h1>

			<?php if ( has_excerpt() ) : ?>
				<p class="page-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php else : ?>
				<p class="page-hero__lead">
					<?php
					printf(
						/* translators: 1: clinic locality, 2: service area. */
						esc_html__( 'Ma Lumière is a skin, hair and laser clinic in %1$s, serving patients across %2$s. Every treatment starts with a private consultation.', 'ma-lumiere' ),
						esc_html( $locality ),
						esc_html( $area )
					);
					?>
				</p>
			<?php endif; ?>

			<div class="td-hero__actions" style="justify-content:center">
				<a class="btn btn--primary" href="<?php echo esc_url( $booking_url ); ?>">
					<?php esc_html_e( 'Book a Consultation', 'ma-lumiere' ); ?>
				</a>
				<?php if ( $phone ) : ?>
					<a class="btn btn--outline" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
						<?php echo esc_html( sprintf( /* translators: %s: phone number. */ __( 'Call %s', 'ma-lumiere' ), $phone ) ); ?>
					</a>
				<?php endif; ?>
			</div>

			<dl class="td-hero__facts">
				<div class="td-hero__fact">
					<dt><?php esc_html_e( 'Where', 'ma-lumiere' ); ?></dt>
					<dd><?php echo esc_html( $locality ); ?></dd>
				</div>
				<div class="td-hero__fact">
					<dt><?php esc_html_e( 'Serves', 'ma-lumiere' ); ?></dt>
					<dd><?php echo esc_html( $area ); ?></dd>
				</div>
				<div class="td-hero__fact">
					<dt><?php esc_html_e( 'First step', 'ma-lumiere' ); ?></dt>
					<dd><?php esc_html_e( 'Consultation', 'ma-lumiere' ); ?></dd>
				</div>
			</dl>
		</div>
	</section>

	<section class="section">
		<div class="container container--narrow entry-content">
			<?php if ( trim( (string) get_the_content() ) ) : ?>
				<?php the_content(); ?>
			<?php else : ?>
				<h2><?php esc_html_e( 'A skin clinic for Mira Road and Bhayander', 'ma-lumiere' ); ?></h2>
				<p><?php esc_html_e( 'If you are searching for a skin clinic in Mira Bhayander, Ma Lumière is in Mira Road East — close enough for patients from Bhayander East, Bhayander West, Kashimira, Dahisar and Borivali to reach for a consultation without travelling into the city.', 'ma-lumiere' ); ?></p>
				<p><?php esc_html_e( 'The clinic focuses on diagnosis first and procedures second. Before any treatment is planned, we assess your skin, ask about your history and explain what can realistically change, how long it is likely to take and what it will involve.', 'ma-lumiere' ); ?></p>
				<h2><?php esc_html_e( 'Face treatments in Mira Bhayander', 'ma-lumiere' ); ?></h2>
				<p><?php esc_html_e( 'Patients commonly come to us for acne and acne scars, pigmentation and uneven skin tone, dull or tired-looking skin, enlarged pores, uneven texture, and facial rejuvenation. Laser hair reduction, hair loss assessment and mole or skin tag removal are also available.', 'ma-lumiere' ); ?></p>
				<h2><?php esc_html_e( 'Your first visit', 'ma-lumiere' ); ?></h2>
				<p><?php esc_html_e( 'A first consultation is a conversation, not a sales pitch. We review your skin, discuss what has and has not worked, and agree on a plan that fits your goals and your schedule. If a treatment is not right for you, we will say so.', 'ma-lumiere' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $treatments ) : ?>
		<section class="section section--tint">
			<div class="container">
				<div class="section__head section__head--center">
					<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'The treatment menu', 'ma-lumiere' ); ?></p>
					<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'Treatments available in Mira Bhayander', 'ma-lumiere' ); ?></h2>
					<p class="section__lead" data-reveal="fade"><?php esc_html_e( 'Every plan is built around your skin. Explore a treatment, then book a consultation to find out whether it is right for you.', 'ma-lumiere' ); ?></p>
				</div>

				<div class="treatments__grid">
					<?php
					foreach ( $treatments as $treatment ) :
						global $post;
						$post = $treatment; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						setup_postdata( $post );
						get_template_part( 'template-parts/treatment-card' );
					endforeach;
					wp_reset_postdata();
					?>
				</div>

				<p class="section__head section__head--center" style="margin:var(--sp-lg) 0 0" data-reveal="fade">
					<a class="link-arrow" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-treatments.php', get_post_type_archive_link( 'ml_treatment' ) ) ); ?>">
						<?php esc_html_e( 'View all treatments', 'ma-lumiere' ); ?>
						<?php ml_icon( 'arrow-right', 18 ); ?>
					</a>
				</p>
			</div>
		</section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/areas', null, array( 'hide_link' => true ) ); ?>

	<?php get_template_part( 'template-parts/doctor-section', null, array( 'show_cta' => true ) ); ?>

	<?php get_template_part( 'template-parts/faq-section', null, array( 'limit' => -1 ) ); ?>

	<?php get_template_part( 'template-parts/contact-section' ); ?>

	<?php get_template_part( 'template-parts/cta-section' ); ?>

	<?php
endwhile;

get_footer();
