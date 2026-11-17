<?php
/**
 * Doctor introduction section.
 *
 * @package ma-lumiere
 */

$image_id       = ml_doctor_image_id();
$image_fallback = ml_asset_href( 'assets/images/doctor-portrait.jpg' );
$name           = ml_doctor_name();
$qual           = ml_doctor_qualifications();
$bio            = ml_doctor_bio();
$expertise      = ml_doctor_expertise();
$show_cta       = ! isset( $args['show_cta'] ) || (bool) $args['show_cta'];
?>

<section class="section doctor" id="doctor">
	<div class="container doctor__grid">

		<div class="doctor__media" data-reveal="left">
			<?php if ( ml_attachment_is_deployed( $image_id ) ) : ?>
				<?php echo wp_get_attachment_image( $image_id, 'ml-portrait', false, array( 'class' => 'doctor__img' ) ); ?>
			<?php elseif ( $image_fallback ) : ?>
				<img class="doctor__img" src="<?php echo esc_url( $image_fallback ); ?>" width="900" height="1100" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" decoding="async" />
			<?php else : ?>
				<div class="ph" style="border-radius:var(--radius-arch);min-height:480px">[DOCTOR IMAGE]</div>
			<?php endif; ?>
		</div>

		<div class="doctor__content" data-reveal="right">
			<p class="eyebrow"><?php esc_html_e( 'MEET YOUR PHYSICIAN', 'ma-lumiere' ); ?></p>
			<h2 class="doctor__name"><?php echo esc_html( $name ); ?></h2>
			<p class="doctor__qual"><?php echo esc_html( $qual ); ?></p>
			<div class="doctor__bio entry-content">
				<?php echo wp_kses_post( $bio ); ?>
			</div>
			<?php if ( $expertise ) : ?>
				<ul class="expertise-list" aria-label="<?php esc_attr_e( 'Areas of expertise', 'ma-lumiere' ); ?>">
					<?php foreach ( $expertise as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $show_cta ) : ?>
			<a class="btn btn--primary" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-about.php' ) ); ?>">
				<?php esc_html_e( 'Meet the Doctor', 'ma-lumiere' ); ?>
			</a>
			<?php endif; ?>
		</div>

	</div>
</section>