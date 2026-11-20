<?php
/**
 * Hero section.
 *
 * @package ma-lumiere
 */

$eyebrow  = ml_mod( 'ml_hero_eyebrow', __( 'Mira Road East · Mira Bhayander', 'ma-lumiere' ) );
$title    = ml_mod( 'ml_hero_title', __( 'Skin, Hair & Laser Care in Mira Bhayander.', 'ma-lumiere' ) );
$title    = explode( ' ', $title ); // Simple graceful italics on the closing word.
$last     = array_pop( $title );
$text     = ml_mod( 'ml_hero_text', __( 'Medical dermatology and aesthetic care for Mira Road, Bhayander and nearby areas — planned around your skin, your concerns and your long-term goals.', 'ma-lumiere' ) );

$cta_primary = ml_mod( 'ml_hero_cta_primary', __( 'Book a Consultation', 'ma-lumiere' ) );
$cta_primary_url = ml_mod( 'ml_hero_cta_primary_url', '' );
if ( ! $cta_primary_url ) {
	$cta_primary_url = ml_page_url_by_template( 'page-templates/page-appointment.php' );
}

$cta_secondary = ml_mod( 'ml_hero_cta_secondary', __( 'Explore Treatments', 'ma-lumiere' ) );
$cta_secondary_url = ml_mod( 'ml_hero_cta_secondary_url', '' );
if ( ! $cta_secondary_url ) {
	$cta_secondary_url = ml_page_url_by_template( 'page-templates/page-treatments.php', get_post_type_archive_link( 'ml_treatment' ) );
}

$hero_image_url = ml_hero_image_url( 'ml-portrait' );
$hero_image_alt = sprintf(
	/* translators: 1: clinic name, 2: service area. */
	__( '%1$s skin clinic in %2$s', 'ma-lumiere' ),
	ml_seo_brand_name(),
	ml_seo_local_area()
);
$phone          = ml_clinic_phone();
$locality       = ml_clinic_locality();
?>

<section class="hero">
	<div class="container hero__inner">

		<div class="hero__content">
			<p class="eyebrow" data-reveal="f e"><?php echo esc_html( $eyebrow ); ?></p>
			<h1 class="hero__title" data-reveal="left">
				<?php echo esc_html( implode( ' ', $title ) ); ?> <em><?php echo esc_html( $last ); ?></em>
			</h1>
			<p class="hero__text" data-reveal="left"><?php echo esc_html( $text ); ?></p>
			<div class="hero__actions" data-reveal="left">
				<a class="btn btn--primary" href="<?php echo esc_url( $cta_primary_url ); ?>"><?php echo esc_html( $cta_primary ); ?></a>
				<a class="btn btn--outline" href="<?php echo esc_url( $cta_secondary_url ); ?>"><?php echo esc_html( $cta_secondary ); ?></a>
			</div>
			<?php if ( $phone ) : ?>
				<div class="hero__meta" data-reveal="fade">
					<span><?php ml_icon( 'marker', 16 ); ?> <?php echo esc_html( $locality ); ?></span>
					<span><?php ml_icon( 'phone', 16 ); ?> <?php echo esc_html( $phone ); ?></span>
					<span><?php ml_icon( 'clock', 16 ); ?> <?php echo esc_html__( 'Consultation by appointment', 'ma-lumiere' ); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<div class="hero__media" data-reveal="right">
			<?php if ( $hero_image_url ) : ?>
				<img class="hero__media-img" src="<?php echo esc_url( $hero_image_url ); ?>" width="900" height="1100" alt="<?php echo esc_attr( $hero_image_alt ); ?>" fetchpriority="high" decoding="async" />
			<?php else : ?>
				<div class="hero__media-art" aria-hidden="true"></div>
			<?php endif; ?>
		</div>

	</div>
</section>