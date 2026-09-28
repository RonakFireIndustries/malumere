<?php
/**
 * Prescription workflow service.
 *
 * Deliberately thin — data integrity lives in ML_Prescription_Repository.
 * This class orchestrates the side effects of the lifecycle:
 *
 *  - `finalize` issues the prescription (number allocation), derives a
 *    follow-up visit when the doctor chose one, and optionally notifies the
 *    patient by email.
 *  - `email` is never sent automatically: it is gated by the
 *    `ml_clinic_send_email` filter and recorded on the prescription.
 *  - `pdf` renders the approved document only for finalized prescriptions.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Prescription_Service {

	/**
	 * Create a draft prescription.
	 *
	 * @param array $input Raw values (items nested under 'items').
	 *
	 * @return int|WP_Error
	 */
	public static function create( array $input ) {
		return ML_Prescription_Repository::create( $input );
	}

	/**
	 * Update a draft prescription.
	 *
	 * @param int   $id    Prescription ID.
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error
	 */
	public static function update( $id, array $input ) {
		return ML_Prescription_Repository::update( $id, $input );
	}

	/**
	 * Turn a finalized prescription (or its correction) into the issued
	 * document history. Idempotent for already-finalized rows.
	 *
	 * Side effects (only when the row actually transitions draft → final):
	 *  - derive a follow-up appointment from follow_up_date, if set;
	 *  - trigger a (gated) patient notification email.
	 *
	 * @param int $id Prescription ID.
	 *
	 * @return array|WP_Error Prescription row.
	 */
	public static function finalize( $id ) {
		$id = absint( $id );
		$before = ML_Prescription_Repository::get( $id );
		if ( ! $before ) {
			return new WP_Error( 'ml_prescription_not_found', __( 'Prescription not found.', 'ma-lumiere-clinic' ) );
		}

		$result = ML_Prescription_Repository::finalize( $id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( 'final' === (string) $before['status'] ) {
			return $result; // Already issued — nothing else to do.
		}

		if ( ! empty( $result['follow_up_date'] ) ) {
			self::derive_followup( $result );
		}

		if ( apply_filters( 'ml_clinic_send_email', false, 'prescription_notification', $result ) ) {
			self::send_email( $id );
		}

		return $result;
	}

	/**
	 * Create a new draft that revises an issued prescription.
	 *
	 * @param int $id Issued prescription ID.
	 *
	 * @return int|WP_Error New correction ID.
	 */
	public static function correct( $id ) {
		return ML_Prescription_Repository::correct( $id );
	}

	/**
	 * Email the prescription PDF/notice to the patient. Records the attempt
	 * on the prescription row regardless of transport result.
	 *
	 * @param int $id Prescription ID.
	 *
	 * @return true|WP_Error
	 */
	public static function send_email( $id ) {
		global $wpdb;

		$prescription = ML_Prescription_Repository::get( $id );
		if ( ! $prescription ) {
			return new WP_Error( 'ml_prescription_not_found', __( 'Prescription not found.', 'ma-lumiere-clinic' ) );
		}
		$patient = ML_Patient_Repository::get( (int) $prescription['patient_id'] );
		$email   = $patient ? (string) trim( (string) $patient['email'] ) : '';

		if ( ! is_email( $email ) ) {
			$reason = __( 'The patient has no valid email address.', 'ma-lumiere-clinic' );
			self::record_email_log( $prescription, 'skipped', $reason );
			return new WP_Error( 'ml_no_patient_email', $reason );
		}
		if ( ( 'final' !== (string) $prescription['status'] ) || empty( $prescription['prescription_number'] ) ) {
			$reason = __( 'Only issued prescriptions can be emailed.', 'ma-lumiere-clinic' );
			self::record_email_log( $prescription, 'skipped', $reason );
			return new WP_Error( 'ml_prescription_not_final', $reason );
		}

		$subject = sprintf(
			/* translators: %s: prescription number. */
			__( 'Your prescription %s from %s', 'ma-lumiere-clinic' ),
			$prescription['prescription_number'],
			ML_Settings::get( 'clinic.clinic_name', __( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ) )
		);

		$display  = trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] );
		$message  = '<div style="font-family:Arial,sans-serif;max-width:600px">'
			. '<h2>' . esc_html__( 'Your prescription is ready', 'ma-lumiere-clinic' ) . '</h2>'
			. '<p>' . esc_html( sprintf( __( 'Dear %s,', 'ma-lumiere-clinic' ), $display ) ) . '</p>'
			. '<p>' . esc_html( __( 'Your prescription has been issued by Ma Lumière Clinic. Please review the medicines and instructions carefully before use and follow up as advised.', 'ma-lumiere-clinic' ) ) . '</p>'
			. '<p><strong>' . esc_html( $prescription['prescription_number'] ) . '</strong> — ' . esc_html( ml_date( $prescription['prescription_date'] ) ) . '</p>'
			. '<p>' . esc_html__( 'With best wishes,', 'ma-lumiere-clinic' ) . '</p>'
			. '<p><strong>' . esc_html( (string) ML_Settings::get( 'doctor.doctor_name', '' ) ) . '</strong></p>'
			. '<p style="color:#667">' . esc_html( (string) ML_Settings::get( 'clinic.clinic_name', '' ) ) . '</p>'
			. '</div>';

		$pdf_log = '';
		$sent = false;
		if ( apply_filters( 'ml_clinic_send_email', false, 'prescription_notification', $prescription ) ) {
			$sent = ML_Email_Service::send(
				$email,
				$subject,
				$message,
				'prescription_notification',
				array( 'prescription' => $prescription, 'patient' => $patient ),
				true
			);
		} else {
			$pdf_log = ' email_disabled';
		}

		$log = sprintf(
			'%s | attempt=%s | %s',
			(is_wp_error( $sent ) ? $sent->get_error_code() : ( true === $sent ? 'sent' : 'failed' )),
			current_time( 'mysql' ),
			trim( $pdf_log )
		);

		$wpdb->update(
			ML_Database::table( 'prescriptions' ),
			array( 'email_sent' => ( true === $sent ) ? 1 : (int) $prescription['email_sent'], 'email_log' => $log ),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return true === $sent ? true : new WP_Error( 'ml_mail_not_sent', __( 'Email was not sent (transport disabled or failed).', 'ma-lumiere-clinic' ), array( 'log' => $log ) );
	}

	/**
	 * Render the issued prescription as PDF bytes.
	 *
	 * @param int $id Prescription ID.
	 *
	 * @return array|WP_Error ['filename', 'data'].
	 */
	public static function pdf( $id ) {
		return ML_Prescription_Pdf::generate( $id );
	}

	/**
	 * Derived a follow-up record on finalize. Skipped when an upcoming
	 * follow-up is already scheduled for that patient on that date, or when
	 * the date has already passed (keeps the schedule truthful).
	 *
	 * @param array $prescription Issued prescription row.
	 *
	 * @return void
	 */
	private static function derive_followup( array $prescription ) {
		$date = (string) $prescription['follow_up_date'];
		if ( $date < current_time( 'Y-m-d' ) ) {
			return;
		}
		if ( ML_Followup_Repository::upcoming( (int) $prescription['patient_id'], $date ) ) {
			return;
		}

		ML_Followup_Repository::create(
			array(
				'patient_id'     => (int) $prescription['patient_id'],
				'visit_id'       => ! empty( $prescription['visit_id'] ) ? (int) $prescription['visit_id'] : 0,
				'doctor_user_id' => ! empty( $prescription['doctor_user_id'] ) ? (int) $prescription['doctor_user_id'] : 0,
				'followup_date'  => $date,
				'reason'         => __( 'Post-consultation follow-up', 'ma-lumiere-clinic' ),
			)
		);
	}

	/**
	 * Append a transport log entry to a prescription without sending mail.
	 *
	 * @param array  $prescription Prescription row.
	 * @param string $code         Short code.
	 * @param string $reason       Human reason.
	 *
	 * @return void
	 */
	private static function record_email_log( array $prescription, $code, $reason ) {
		global $wpdb;
		$log = sprintf( '%s | %s | %s', $code, current_time( 'mysql' ), mb_substr( $reason, 0, 200 ) );
		$wpdb->update(
			ML_Database::table( 'prescriptions' ),
			array( 'email_log' => $log ),
			array( 'id' => (int) $prescription['id'] ),
			array( '%s' ),
			array( '%d' )
		);
	}
}