<?php
/**
 * Contact form: REST endpoint + validation + secure delivery.
 *
 * Uses a honeypot for bots and a per-IP rate limit. Never exposes the
 * recipient address publicly; the form ships via the clinic CMS mail.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'ml_register_contact_route' );

/**
 * Register POST /wp-json/ml/v1/contact.
 */
function ml_register_contact_route() {
	register_rest_route(
		'ml/v1',
		'/contact',
		array(
			'methods'             => 'POST',
			'callback'            => 'ml_handle_contact',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * Handle contact form submission.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ml_handle_contact( $request ) {
	// Nonce.
	$nonce = sanitize_text_field( (string) $request->get_param( 'nonce' ) );
	if ( ! wp_verify_nonce( $nonce, 'ml_contact' ) ) {
		return new WP_REST_Response(
			array( 'message' => __( 'Your session expired. Please refresh the page and try again.', 'ma-lumiere' ) ),
			403
		);
	}

	// Honeypot — bots fill it, humans never see it.
	if ( ! empty( $request->get_param( 'website' ) ) ) {
		return new WP_REST_Response( array( 'message' => 'ok' ), 200 );
	}

	// Rate limit: 3 submissions / 15 min / IP.
	$ip    = ml_client_ip();
	$key   = 'ml_contact_' . md5( $ip );
	$count = (int) get_transient( $key );
	if ( $count >= 3 ) {
		return new WP_REST_Response(
			array( 'message' => __( 'Too many messages. Please wait a few minutes before trying again.', 'ma-lumiere' ) ),
			429
		);
	}
	set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );

	$name    = sanitize_text_field( (string) $request->get_param( 'name' ) );
	$phone   = sanitize_text_field( (string) $request->get_param( 'phone' ) );
	$email   = sanitize_email( (string) $request->get_param( 'email' ) );
	$subject = sanitize_text_field( (string) $request->get_param( 'subject' ) );
	$message = sanitize_textarea_field( (string) $request->get_param( 'message' ) );

	if ( ! $name || ! $email || ! $message ) {
		return new WP_REST_Response(
			array( 'message' => __( 'Please complete the required fields: name, email and message.', 'ma-lumiere' ) ),
			422
		);
	}
	if ( ! is_email( $email ) ) {
		return new WP_REST_Response(
			array( 'message' => __( 'Please enter a valid email address.', 'ma-lumiere' ) ),
			422
		);
	}
	if ( mb_strlen( $message ) > 4000 ) {
		return new WP_REST_Response(
			array( 'message' => __( 'Your message is too long.', 'ma-lumiere' ) ),
			422
		);
	}

	$to = ml_clinic_email();
	if ( ! $to ) {
		$to = get_option( 'admin_email' );
	}
	/**
	 * Filters the contact-form recipient.
	 *
	 * @param string $to     Recipient email.
	 * @param array  $fields Sanitized form fields.
	 */
	$to = apply_filters( 'ml_contact_mail_to', $to, compact( 'name', 'phone', 'email', 'subject', 'message' ) );

	$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$mail_sub  = $subject
		? sprintf(
			/* translators: 1: Site name, 2: Subject from visitor. */
			__( '[%1$s] Contact form: %2$s', 'ma-lumiere' ),
			$site_name,
			$subject
		)
		: sprintf( __( '[%1$s] New contact form message', 'ma-lumiere' ), $site_name );

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$html_body = wp_kses_post(
		'<p><strong>' . esc_html( $name ) . '</strong><br/>'
		. esc_html( $email ) . '<br/>'
		. esc_html( $phone ? $phone : '—' ) . '</p>'
		. '<hr/><p>' . nl2br( esc_html( $message ) ) . '</p>'
	);

	/**
	 * Deliver a contact-form message.
	 *
	 * Return true to short-circuit delivery (e.g. a custom transport).
	 *
	 * @param bool  $sent   Whether the message was delivered.
	 * @param string $to    Recipient.
	 * @param string $subject Subject.
	 * @param string $html_body HTML body.
	 * @param array  $headers Headers.
	 * @param array  $fields Sanitized form fields.
	 */
	$sent = apply_filters( 'ml_contact_send', false, $to, $mail_sub, $html_body, $headers, compact( 'name', 'phone', 'email', 'subject', 'message' ) );

	if ( ! $sent ) {
		$sent = wp_mail( $to, $mail_sub, $html_body, $headers );
	}

	if ( ! $sent ) {
		error_log( '[ma-lumiere] Contact form mail failed for: ' . $email ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		return new WP_REST_Response(
			array( 'message' => __( 'Sorry, something went wrong sending your message. Please contact us by phone instead.', 'ma-lumiere' ) ),
			500
		);
	}

	return new WP_REST_Response(
		array( 'message' => __( 'Thank you. Your message has been sent — we will get back to you shortly.', 'ma-lumiere' ) ),
		200
	);
}

/**
 * Best-effort client IP (without leaking through proxies).
 *
 * @return string
 */
function ml_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
}