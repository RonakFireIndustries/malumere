<?php
/**
 * Audit logging service.
 *
 * Records sensitive actions to the {prefix}ml_audit_logs table.
 * Never stores passwords, payment secrets, tokens or clinical content —
 * only lightweight metadata and short safe descriptions.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Audit_Log {

	/**
	 * Record an audit entry.
	 *
	 * @param string $action       Action slug, e.g. 'patient_created'.
	 * @param string $entity_type  Entity type, e.g. 'patient'.
	 * @param int    $entity_id    Related record ID.
	 * @param string $description  Short human-safe note.
	 *
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function record( $action, $entity_type = '', $entity_id = 0, $description = '' ) {
		global $wpdb;

		$allowed = self::sanitize_action( $action );
		if ( ! $allowed ) {
			return false;
		}

		$data = array(
			'user_id'     => get_current_user_id() ? get_current_user_id() : 0,
			'action'      => $allowed,
			'entity_type' => mb_substr( (string) $entity_type, 0, 50 ),
			'entity_id'   => absint( $entity_id ),
			'description' => mb_substr( (string) $description, 0, 400 ),
			'ip_address'  => ML_Security::current_ip(),
			'user_agent'  => ML_Security::current_user_agent(),
			'created_at'  => current_time( 'mysql' ),
		);

		$table = ML_Database::table( 'audit_logs' );
		$result = $wpdb->insert( $table, $data, array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ) );

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Fetch audit rows with a simple filter.
	 *
	 * @param array $filters Optional: 'action', 'entity_type', 'entity_id', 'limit'.
	 *
	 * @return array
	 */
	public static function query( array $filters = array() ) {
		global $wpdb;
		$table = ML_Database::table( 'audit_logs' );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $filters['action'] ) ) {
			$where[]  = 'action = %s';
			$params[] = self::sanitize_action( $filters['action'] );
		}
		if ( ! empty( $filters['entity_type'] ) ) {
			$where[]  = 'entity_type = %s';
			$params[] = mb_substr( (string) $filters['entity_type'], 0, 50 );
		}
		if ( isset( $filters['entity_id'] ) ) {
			$where[]  = 'entity_id = %d';
			$params[] = absint( $filters['entity_id'] );
		}

		$limit = isset( $filters['limit'] ) ? min( 200, max( 1, absint( $filters['limit'] ) ) ) : 50;

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d";
		$params[]  = $limit;

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Total audit entries (used by dashboard).
	 *
	 * @return int
	 */
	public static function count() {
		global $wpdb;
		$table = ML_Database::table( 'audit_logs' );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Whitelist of allowed actions. Unknown actions are rejected, which
	 * prevents garbage from ever reaching the log.
	 *
	 * @param string $action Action slug.
	 *
	 * @return string|false
	 */
	private static function sanitize_action( $action ) {
		$allowed = array(
			'patient_created',
			'patient_updated',
			'patient_deleted',
			'patient_viewed',
			'visit_created',
			'visit_updated',
			'visit_locked',
			'visit_unlocked',
			'prescription_created',
			'prescription_updated',
			'prescription_finalized',
			'prescription_corrected',
			'prescription_pdf_downloaded',
			'treatment_session_created',
			'treatment_session_updated',
			'followup_created',
			'followup_updated',
			'followup_completed',
			'followup_cancelled',
			'appointment_created',
			'appointment_updated',
			'appointment_rescheduled',
			'appointment_cancelled',
			'appointment_checked_in',
			'appointment_completed',
			'appointment_no_show',
			'booking_failed',
			'photo_uploaded',
			'invoice_created',
			'payment_recorded',
			'payment_completed_razorpay',
			'invoice_pdf_downloaded',
			'portal_login_sent',
			'portal_login_consumed',
			'portal_logout',
			'portal_pdf_downloaded',
			'report_exported',
			'login',
			'permission_denied',
			'role_changed',
			'settings_updated',
		);
		return in_array( $action, $allowed, true ) ? $action : false;
	}
}