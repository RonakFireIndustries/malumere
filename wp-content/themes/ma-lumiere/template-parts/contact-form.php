<?php
/**
 * Contact form markup (AJAX-delivered, nonce-protected, honeypot-guarded).
 *
 * @package ma-lumiere
 */
?>
<form class="ml-contact-form" method="post" data-contact-form novalidate>
	<div class="form-message" role="status" data-form-message></div>

	<div class="form-row">
		<div class="form-field">
			<label for="cf-name"><?php esc_html_e( 'Name', 'ma-lumiere' ); ?> <span aria-hidden="true">*</span></label>
			<input type="text" id="cf-name" name="name" autocomplete="name" required />
		</div>
		<div class="form-field">
			<label for="cf-phone"><?php esc_html_e( 'Phone', 'ma-lumiere' ); ?> <span aria-hidden="true">*</span></label>
			<input type="tel" id="cf-phone" name="phone" autocomplete="tel" required/>
		</div>
	</div>

	<div class="form-row">
		<div class="form-field">
			<label for="cf-email"><?php esc_html_e( 'Email', 'ma-lumiere' ); ?> <span aria-hidden="true">*</span></label>
			<input type="email" id="cf-email" name="email" autocomplete="email" required />
		</div>
		<div class="form-field">
			<label for="cf-subject"><?php esc_html_e( 'How can we help?', 'ma-lumiere' ); ?> <span aria-hidden="true">*</span></label>
			<select name="subject" id="cf-subject" required>
				<option value="" disabled selected><?php esc_html_e( 'Select an option', 'ma-lumiere' ); ?></option>
				<option value="general"><?php esc_html_e( 'Consultation', 'ma-lumiere' ); ?></option>
				<option value="general"><?php esc_html_e( 'Acne / Acne Scars', 'ma-lumiere' ); ?></option>
				<option value="general"><?php esc_html_e( 'Pigmentation', 'ma-lumiere' ); ?></option>
				<option value="general"><?php esc_html_e( 'Hair Loss', 'ma-lumiere' ); ?></option>
				<option value="quote"><?php esc_html_e( 'Laser Hair Reduction', 'ma-lumiere' ); ?></option>
				<option value="support"><?php esc_html_e( 'Injectables', 'ma-lumiere' ); ?></option>
				<option value="support"><?php esc_html_e( 'Others', 'ma-lumiere' ); ?></option>
			</select>
		</div>
	</div>

	<div data-reveal="fade">
		<div class="form-field">
			<label for="cf-message"><?php esc_html_e( 'Message', 'ma-lumiere' ); ?> <span aria-hidden="true">*</span></label>
			<textarea id="cf-message" name="message" rows="6" required></textarea>
		</div>
	</div>

	<!-- Honeypot: hidden from humans, bots fill it. -->
	<p class="hp-field" aria-hidden="true">
		<label for="cf-website"><?php esc_html_e( 'Leave this field empty', 'ma-lumiere' ); ?></label>
		<input type="text" id="cf-website" name="website" tabindex="-1" autocomplete="off" />
	</p>

	<button type="submit" class="btn btn--primary" data-submit> <?php esc_html_e( 'Send message', 'ma-lumiere' ); ?></button>
	<p class="form-note"><?php esc_html_e( 'Your details are used only to respond to your enquiry.', 'ma-lumiere' ); ?></p>
</form>