<?php
/**
 * Contact section + form.
 *
 * @package ma-lumiere
 */
?>
<section class="section contact" id="contact">
	<div class="container">
		<div class="section__head section__head--center">
			<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'Get in touch', 'ma-lumiere' ); ?></p>
			<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'We would love to hear from you', 'ma-lumiere' ); ?></h2>
		</div>

		<div class="contact__grid">

			<div class="contact__info" data-reveal="left">
				<?php if ( ml_clinic_address() ) : ?>
					<div class="contact-info__item">
						<span class="contact-info__icon"><?php ml_icon( 'marker', 20 ); ?></span>
						<div>
							<p class="contact-info__label"><?php esc_html_e( 'Find us', 'ma-lumiere' ); ?></p>
							<p class="contact-info__value"><?php echo esc_html( ml_clinic_address() ); ?></p>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ml_clinic_phone() ) : ?>
					<div class="contact-info__item">
						<span class="contact-info__icon"><?php ml_icon( 'phone', 20 ); ?></span>
						<div>
							<p class="contact-info__label"><?php esc_html_e( 'Call us', 'ma-lumiere' ); ?></p>
							<p class="contact-info__value">
								<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', ml_clinic_phone() ) ); ?>"><?php echo esc_html( ml_clinic_phone() ); ?></a>
							</p>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ml_clinic_email() ) : ?>
					<div class="contact-info__item">
						<span class="contact-info__icon"><?php ml_icon( 'mail', 20 ); ?></span>
						<div>
							<p class="contact-info__label"><?php esc_html_e( 'Write to us', 'ma-lumiere' ); ?></p>
							<p class="contact-info__value">
								<a href="mailto:<?php echo esc_attr( ml_clinic_email() ); ?>"><?php echo esc_html( ml_clinic_email() ); ?></a>
							</p>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ml_clinic_hours() ) : ?>
					<div class="contact-info__item">
						<span class="contact-info__icon"><?php ml_icon( 'clock', 20 ); ?></span>
						<div>
							<p class="contact-info__label"><?php esc_html_e( 'Opening hours', 'ma-lumiere' ); ?></p>
							<p class="contact-info__value contact-info__hours"><?php echo esc_html( ml_clinic_hours() ); ?></p>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="contact__form" data-reveal="right">
				<?php get_template_part( 'template-parts/contact-form' ); ?>
			</div>

		</div>
	</div>
</section>