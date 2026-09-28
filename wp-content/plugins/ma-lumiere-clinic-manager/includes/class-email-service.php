<?php
/**
 * Email service foundation.
 *
 * Provides the architecture for transactional clinic emails. Phase 3 ships
 * the transport + template registry; templates are added by later phases.
 * No emails are silently sent during development — callers must opt in via
 * the ml_clinic_send_email filter (disabled unless a transport succeeds).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Email_Service {

	/**
	 * Registered email types (event => template name).
	 *
	 * @return array<string,string>
	 */
	public static function templates() {
		return array(
			'appointment_confirmation' => 'appointment-confirmation',
			'appointment_reminder'     => 'appointment-reminder',
			'appointment_cancellation' => 'appointment-cancellation',
			'followup_reminder'        => 'followup-reminder',
			'invoice_notification'     => 'invoice-notification',
			'prescription_notification' => 'prescription-notification',
			'portal_magic_link'        => 'portal-magic-link',
		);
	}

	/**
	 * Send an email using the configured sender identity.
	 *
	 * @param string $to      Recipient email.
	 * @param string $subject Subject.
	 * @param string $message Body (plain text or HTML).
	 * @param string $type    Template event key (see templates()).
	 * @param array  $data    Context data for future templating.
	 * @param bool   $html    Send as HTML.
	 *
	 * @return bool|WP_Error Success flag or request error.
	 */
	public static function send( $to, $subject, $message, $type = '', array $data = array(), $html = false ) {
		$to = sanitize_email( $to );
		if ( ! is_email( $to ) ) {
			return new WP_Error( 'ml_invalid_recipient', __( 'Invalid recipient email address.', 'ma-lumiere-clinic' ) );
		}

		$subject = sanitize_text_field( $subject );
		$message = $html ? wp_kses_post( $message ) : wp_strip_all_tags( $message );

		$headers = self::headers();

		/**
		 * Cut-out to permit sending during development/where wp_mail is
		 * unavailable (e.g. local XAMPP). Default false — nothing is
		 * sent until a phase ships a deliberate trigger.
		 *
		 * @param bool $allowed Whether sending is permitted.
		 */
		if ( ! apply_filters( 'ml_clinic_send_email', false, $type, $data ) ) {
			return new WP_Error( 'ml_mail_disabled', __( 'Email sending is disabled for this environment.', 'ma-lumiere-clinic' ) );
		}

		$sent = wp_mail( $to, $subject, $message, $headers );
		return $sent ? true : new WP_Error( 'ml_mail_failed', __( 'wp_mail() returned false.', 'ma-lumiere-clinic' ) );
	}

	/**
	 * Build mail headers from settings.
	 *
	 * @return array
	 */
	private static function headers() {
		$from_name  = ML_Settings::get( 'email.sender_name', '' );
		$from_email = ML_Settings::get( 'email.sender_email', '' );
		$headers    = array( 'Content-Type: text/html; charset=UTF-8' );

		if ( $from_email ) {
			$headers[] = 'From: ' . ( $from_name ? "{$from_name} <{$from_email}>" : $from_email );
		}
		return $headers;
	}
}