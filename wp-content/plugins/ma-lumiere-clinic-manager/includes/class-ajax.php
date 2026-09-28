<?php
/**
 * Secure AJAX foundation for admin UI.
 *
 * Every action is gated: nonce -> login -> capability -> validate -> run -> JSON.
 * Shared gate is in ML_Security::gate().
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Ajax {

	/**
	 * Register all AJAX actions.
	 *
	 * @return void
	 */
	public static function register() {
		$actions = array(
			'ml_get_dashboard_stats' => 'handle_dashboard_stats',
			'ml_search_patients'     => 'handle_patient_search',
			'ml_get_recent_audit'    => 'handle_recent_audit',
			'ml_prescription_pdf'    => 'handle_prescription_pdf',
			'ml_invoice_pdf'         => 'handle_invoice_pdf',
		);

		foreach ( $actions as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, $method ) );
		}
	}

	/**
	 * Verify endpoint used by admin.js to confirm nonce wiring.
	 *
	 * @return void
	 */
	public static function handle_verify() {
		if ( ! ML_Security::gate( 'ml_manage_clinic', null, false ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'ma-lumiere-clinic' ) ), 403 );
		}
		wp_send_json_success( array( 'ok' => true ) );
	}

	/**
	 * Dashboard stat cards (real counts, zero when empty).
	 *
	 * @return void
	 */
	public static function handle_dashboard_stats() {
		if ( ! ML_Security::gate( 'ml_view_patients' ) ) {
			return;
		}
		global $wpdb;

		$stats = array(
			'total_patients'      => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ML_Database::table( 'patients' ) ),
			'today_appointments'  => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ML_Database::table( 'appointments' ) . ' WHERE appointment_date = CURDATE()' ),
			'upcoming_appointments' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ML_Database::table( 'appointments' ) . ' WHERE appointment_date >= CURDATE()' ),
			'pending_followups'   => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ML_Database::table( 'followups' ) . " WHERE status = 'upcoming'" ),
			'today_revenue'       => (float) $wpdb->get_var( 'SELECT COALESCE(SUM(amount),0) FROM ' . ML_Database::table( 'payments' ) . ' WHERE DATE(payment_date) = CURDATE()' ),
			'pending_payments'    => (float) $wpdb->get_var( 'SELECT COALESCE(SUM(balance_amount),0) FROM ' . ML_Database::table( 'invoices' ) . " WHERE payment_status IN ('unpaid','partial')" ),
			'open_visits'         => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ML_Database::table( 'visits' ) . " WHERE status = 'open'" ),
			'draft_prescriptions' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ML_Database::table( 'prescriptions' ) . " WHERE status = 'draft'" ),
			'final_prescriptions' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ML_Database::table( 'prescriptions' ) . " WHERE status = 'final'" ),
		);

		ML_Audit_Log::record( 'patient_viewed', 'dashboard', 0, __( 'Dashboard stats viewed', 'ma-lumiere-clinic' ) );
		wp_send_json_success( $stats );
	}

	/**
	 * Patient search stub (foundation only).
	 *
	 * @return void
	 */
	public static function handle_patient_search() {
		if ( ! ML_Security::gate( 'ml_view_patients' ) ) {
			return;
		}
		$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

		global $wpdb;
		$table   = ML_Database::table( 'patients' );
		$like    = '%' . $wpdb->esc_like( $term ) . '%';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, patient_uid, first_name, last_name, phone, email
				 FROM {$table}
				 WHERE first_name LIKE %s OR last_name LIKE %s OR patient_uid LIKE %s OR phone LIKE %s
				 ORDER BY last_name ASC LIMIT 10",
				$like,
				$like,
				$like,
				$like
			),
			ARRAY_A
		);

		wp_send_json_success( is_array( $results ) ? $results : array() );
	}

	/**
	 * Recent audit entries (respects audit capability).
	 *
	 * @return void
	 */
	public static function handle_recent_audit() {
		if ( ! ML_Security::gate( 'ml_view_audit_logs' ) ) {
			return;
		}
		$limit = isset( $_POST['limit'] ) ? min( 20, absint( $_POST['limit'] ) ) : 10;
		wp_send_json_success( ML_Audit_Log::query( array( 'limit' => $limit ) ) );
	}

	/**
	 * Prescription PDF download. Works over GET (link) or POST. Gated like
	 * every other surface: nonce -> login -> capability -> IDOR. Only
	 * finalized prescriptions can be exported. Sends the raw PDF directly.
	 *
	 * @return void
	 */
	public static function handle_prescription_pdf() {
		if ( ! ML_Security::gate( 'ml_manage_prescriptions', null, false ) ) {
			wp_die( esc_html__( 'Forbidden.', 'ma-lumiere-clinic' ), 403 );
		}
		$id  = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
		$row = $id ? ML_Prescription_Repository::get( $id ) : null;
		if ( ! $row ) {
			wp_die( esc_html__( 'Prescription not found.', 'ma-lumiere-clinic' ), 404 );
		}
		if ( ! ML_Security::can_access_patient( (int) $row['patient_id'] ) ) {
			wp_die( esc_html__( 'Forbidden.', 'ma-lumiere-clinic' ), 403 );
		}
		if ( 'final' !== (string) $row['status'] ) {
			wp_die( esc_html__( 'Only finalized prescriptions can be exported.', 'ma-lumiere-clinic' ), 400 );
		}

		$pdf = ML_Prescription_Service::pdf( $id );
		if ( is_wp_error( $pdf ) ) {
			wp_die( esc_html( $pdf->get_error_message() ), 400 );
		}

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $pdf['filename'] ) . '"' );
		header( 'Content-Length: ' . strlen( $pdf['data'] ) );

		ML_Audit_Log::record( 'prescription_pdf_downloaded', 'prescription', $id, sprintf( __( 'Prescription %s exported as PDF.', 'ma-lumiere-clinic' ), (string) $row['prescription_number'] ) );

		echo $pdf['data']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Invoice PDF download (GET link or POST). Gated like every other
	 * surface: nonce -> login -> capability -> IDOR. Sends the PDF directly.
	 *
	 * @return void
	 */
	public static function handle_invoice_pdf() {
		if ( ! ML_Security::gate( 'ml_view_billing', null, false ) ) {
			wp_die( esc_html__( 'Forbidden.', 'ma-lumiere-clinic' ), 403 );
		}
		$id      = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
		$invoice = $id ? ML_Invoice_Repository::get( $id ) : null;
		if ( ! $invoice ) {
			wp_die( esc_html__( 'Invoice not found.', 'ma-lumiere-clinic' ), 404 );
		}
		if ( ! ML_Security::can_access_patient( (int) $invoice['patient_id'] ) ) {
			wp_die( esc_html__( 'Forbidden.', 'ma-lumiere-clinic' ), 403 );
		}

		$pdf = ML_Invoice_Pdf::generate( $id );
		if ( is_wp_error( $pdf ) ) {
			wp_die( esc_html( $pdf->get_error_message() ), 400 );
		}

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $pdf['filename'] ) . '"' );
		header( 'Content-Length: ' . strlen( $pdf['data'] ) );

		ML_Audit_Log::record(
			'invoice_pdf_downloaded',
			'invoice',
			$id,
			sprintf( __( 'Invoice %s exported as PDF.', 'ma-lumiere-clinic' ), (string) $invoice['invoice_number'] )
		);

		echo $pdf['data']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}