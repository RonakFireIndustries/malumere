<?php
/**
 * Consultation and prescription fees.
 *
 * The clinic already stores money as invoices + payments. This service turns
 * the two configurable clinical fees into ordinary invoices so the existing
 * billing screens, patient portal and Razorpay checkout keep working without
 * a second payment path:
 *
 *  - `consultation_fee` is invoiced when a public booking is created. The
 *    booking is still confirmed immediately; payment is optional at that
 *    point and can also be made later from the patient portal.
 *  - `prescription_fee` is invoiced once, when a prescription is finalized.
 *    Corrections (revision drafts) never raise a second fee.
 *
 * Amounts always come from Settings, never from the request body.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Fee_Service {

	/**
	 * Nonce action binding a public booking to its fee invoice.
	 *
	 * @param int $invoice_id Invoice ID.
	 *
	 * @return string
	 */
	public static function pay_nonce_action( $invoice_id ) {
		return 'ml_booking_pay_' . absint( $invoice_id );
	}

	/**
	 * Configured consultation fee.
	 *
	 * @return float
	 */
	public static function consultation_fee() {
		return max( 0, round( (float) ML_Settings::get( 'billing.consultation_fee', 0 ), 2 ) );
	}

	/**
	 * Configured prescription fee.
	 *
	 * @return float
	 */
	public static function prescription_fee() {
		return max( 0, round( (float) ML_Settings::get( 'billing.prescription_fee', 0 ), 2 ) );
	}

	/**
	 * Billing currency.
	 *
	 * @return string
	 */
	public static function currency() {
		$currency = (string) ML_Settings::get( 'billing.currency', 'INR' );
		return '' !== $currency ? $currency : 'INR';
	}

	/**
	 * Whether online checkout is usable right now.
	 *
	 * @return bool
	 */
	public static function gateway_enabled() {
		return ! is_wp_error( ML_Payment_Repository::gateway_config() );
	}

	/**
	 * Ensure the booking created exactly one consultation-fee invoice.
	 *
	 * @param array $appointment Appointment row.
	 *
	 * @return int|null Invoice ID, or null when the fee is zero/failed.
	 */
	public static function ensure_consultation_invoice( array $appointment ) {
		$fee          = self::consultation_fee();
		$appointment_id = isset( $appointment['id'] ) ? absint( $appointment['id'] ) : 0;
		$patient_id   = isset( $appointment['patient_id'] ) ? absint( $appointment['patient_id'] ) : 0;

		if ( $fee <= 0 || ! $appointment_id || ! $patient_id ) {
			return null;
		}

		$existing = ML_Invoice_Repository::find_for_appointment( $appointment_id, 'consultation' );
		if ( $existing ) {
			return (int) $existing['id'];
		}

		$treatment_id = isset( $appointment['treatment_id'] ) ? absint( $appointment['treatment_id'] ) : 0;
		$description  = __( 'Consultation fee', 'ma-lumiere-clinic' );
		if ( $treatment_id ) {
			$treatment = ML_Treatment_Repository::get( $treatment_id );
			if ( $treatment && ! empty( $treatment['name'] ) ) {
				$description .= ' — ' . $treatment['name'];
			}
		}

		$result = ML_Invoice_Repository::create_internal(
			array(
				'patient_id'     => $patient_id,
				'appointment_id' => $appointment_id,
				'invoice_date'   => current_time( 'Y-m-d' ),
				'items'          => array(
					array(
						'description'  => $description,
						'quantity'     => 1,
						'unit_price'   => $fee,
						'item_type'    => 'consultation',
						'treatment_id' => $treatment_id,
					),
				),
			)
		);

		if ( is_wp_error( $result ) ) {
			ml_audit(
				'booking_failed',
				'appointment',
				$appointment_id,
				sprintf( __( 'Consultation fee invoice could not be created: %s', 'ma-lumiere-clinic' ), $result->get_error_message() )
			);
			return null;
		}

		return (int) $result;
	}

	/**
	 * Payment details returned to the booking widget after a successful
	 * booking. Never throws: a missing gateway simply leaves `payable` false.
	 *
	 * @param array $appointment Appointment row.
	 *
	 * @return array
	 */
	public static function booking_payment_payload( array $appointment ) {
		$payload = array(
			'fee'             => 0,
			'currency'        => self::currency(),
			'invoice_id'      => 0,
			'pay_nonce'       => '',
			'gateway_enabled' => self::gateway_enabled(),
			'payable'         => false,
		);

		$fee = self::consultation_fee();
		if ( $fee <= 0 ) {
			return $payload;
		}
		$payload['fee'] = $fee;

		$invoice_id = self::ensure_consultation_invoice( $appointment );
		if ( ! $invoice_id ) {
			return $payload;
		}

		$invoice = ML_Invoice_Repository::get( $invoice_id );
		if ( ! $invoice ) {
			return $payload;
		}

		$payload['invoice_id'] = $invoice_id;
		$payload['fee']        = round( (float) $invoice['balance_amount'], 2 );
		$payload['pay_nonce']  = wp_create_nonce( self::pay_nonce_action( $invoice_id ) );
		$payload['payable']    = $payload['gateway_enabled'] && (float) $invoice['balance_amount'] > 0;

		return $payload;
	}

	/**
	 * Raise the one-off prescription fee invoice when a prescription is
	 * issued. Corrections (revision drafts) and zero-fee setups are skipped.
	 *
	 * @param array $prescription Finalized prescription row.
	 *
	 * @return int|WP_Error|null Invoice ID, null when skipped.
	 */
	public static function create_prescription_invoice( array $prescription ) {
		$fee = self::prescription_fee();
		if ( $fee <= 0 ) {
			return null;
		}

		$prescription_id = isset( $prescription['id'] ) ? absint( $prescription['id'] ) : 0;
		$patient_id      = isset( $prescription['patient_id'] ) ? absint( $prescription['patient_id'] ) : 0;
		if ( ! $prescription_id || ! $patient_id ) {
			return null;
		}

		// A correction keeps the original number; it must not re-bill.
		if ( ! empty( $prescription['revision_of'] ) ) {
			return null;
		}

		$existing = ML_Invoice_Repository::find_for_prescription( $prescription_id );
		if ( $existing ) {
			return (int) $existing['id'];
		}

		$number = ! empty( $prescription['prescription_number'] ) ? (string) $prescription['prescription_number'] : '';
		$label  = '' !== $number
			/* translators: %s: prescription number. */
			? sprintf( __( 'Prescription fee — %s', 'ma-lumiere-clinic' ), $number )
			: __( 'Prescription fee', 'ma-lumiere-clinic' );

		return ML_Invoice_Repository::create(
			array(
				'patient_id'      => $patient_id,
				'visit_id'        => isset( $prescription['visit_id'] ) ? absint( $prescription['visit_id'] ) : 0,
				'prescription_id' => $prescription_id,
				'invoice_date'    => current_time( 'Y-m-d' ),
				'items'           => array(
					array(
						'description' => $label,
						'quantity'    => 1,
						'unit_price'  => $fee,
						'item_type'   => 'prescription',
					),
				),
			)
		);
	}
}
