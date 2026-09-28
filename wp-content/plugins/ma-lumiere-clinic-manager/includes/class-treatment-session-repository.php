<?php
/**
 * Treatment session repository: per-patient, per-treatment session schedule
 * with a recommended plan, confirmations and completions.
 *
 * A doctor recommends a plan (N sessions with an optional interval/schedule);
 * the plan materializes as session rows the clinic confirms as patients
 * attend. Values are real clinical records — never invented by defaults.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Treatment_Session_Repository {

	/**
	 * Session statuses.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'rec', 'confirmed', 'completed', 'cancelled' );
	}

	/**
	 * Status display labels.
	 *
	 * @return array<string,string>
	 */
	public static function status_labels() {
		return array(
			'rec'       => __( 'Recommended', 'ma-lumiere-clinic' ),
			'confirmed' => __( 'Confirmed', 'ma-lumiere-clinic' ),
			'completed' => __( 'Completed', 'ma-lumiere-clinic' ),
			'cancelled' => __( 'Cancelled', 'ma-lumiere-clinic' ),
		);
	}

	/**
	 * Create a recommended session. Locked rows cannot be changed.
	 *
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error Session ID.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$patient_id = absint( isset( $input['patient_id'] ) ? $input['patient_id'] : 0 );
		$treatment_id = absint( isset( $input['treatment_id'] ) ? $input['treatment_id'] : 0 );

		if ( ! ML_Patient_Repository::get( $patient_id ) ) {
			return new WP_Error( 'ml_session_invalid', __( 'Please choose a valid patient.', 'ma-lumiere-clinic' ), array( 'errors' => array( 'patient_id' => __( 'Please choose a valid patient.', 'ma-lumiere-clinic' ) ) ) );
		}
		if ( $treatment_id && ! ML_Treatment_Repository::get( $treatment_id ) ) {
			return new WP_Error( 'ml_session_invalid', __( 'Please choose a valid treatment.', 'ma-lumiere-clinic' ), array( 'errors' => array( 'treatment_id' => __( 'Please choose a valid treatment.', 'ma-lumiere-clinic' ) ) ) );
		}

		$session_number = absint( isset( $input['session_number'] ) ? $input['session_number'] : ( self::max_session( $patient_id, $treatment_id ) + 1 ) );
		$status  = isset( $input['status'] ) ? sanitize_key( (string) $input['status'] ) : 'rec';
		if ( ! in_array( $status, self::statuses(), true ) ) {
			$status = 'rec';
		}
		$date = isset( $input['scheduled_date'] ) ? sanitize_text_field( (string) $input['scheduled_date'] ) : '';
		if ( '' !== $date && ! self::is_valid_date( $date ) ) {
			$date = '';
		}

		$now = current_time( 'mysql' );
		$by  = get_current_user_id() ? get_current_user_id() : 0;

		$row = array(
			'patient_id'     => $patient_id,
			'treatment_id'   => $treatment_id ? $treatment_id : null,
			'doctor_user_id' => ! empty( $input['doctor_user_id'] ) ? absint( $input['doctor_user_id'] ) : ( $by ? $by : null ),
			'session_number' => max( 1, $session_number ),
			'session_type'   => isset( $input['session_type'] ) ? mb_substr( sanitize_text_field( (string) $input['session_type'] ), 0, 50 ) : '',
			'scheduled_date' => '' !== $date ? $date : null,
			'status'         => $status,
			'notes'          => isset( $input['notes'] ) ? mb_substr( sanitize_textarea_field( (string) $input['notes'] ), 0, 2000 ) : null,
			'created_by'     => $by ? $by : null,
			'created_at'     => $now,
			'updated_at'     => $now,
		);

		$inserted = $wpdb->insert( ML_Database::table( 'treatment_sessions' ), $row, self::formats( $row ) );
		if ( ! $inserted ) {
			return new WP_Error( 'ml_session_create_failed', __( 'Could not create the treatment session.', 'ma-lumiere-clinic' ) );
		}
		$id = (int) $wpdb->insert_id;

		ml_audit( 'treatment_session_created', 'treatment_session', $id, sprintf( __( 'Treatment session #%d recommended.', 'ma-lumiere-clinic' ), (int) $row['session_number'] ) );

		return $id;
	}

	/**
	 * Recommend a plan of several sessions in one call. Creates one row per
	 * session; existing unlocked rows for the same patient/treatment are
	 * kept (no duplicates are created for the same numbers).
	 *
	 * @param array $input Raw values with `plan` list of session numbers/dates.
	 *
	 * @return array|WP_Error List of created session IDs.
	 */
	public static function create_plan( array $input ) {
		$patient_id  = absint( isset( $input['patient_id'] ) ? $input['patient_id'] : 0 );
		$treatment_id = absint( isset( $input['treatment_id'] ) ? $input['treatment_id'] : 0 );

		$plan = isset( $input['plan'] ) && is_array( $input['plan'] ) ? $input['plan'] : array();
		$plan = array_slice( $plan, 0, 30 );
		if ( ! $plan ) {
			return new WP_Error( 'ml_session_plan', __( 'At least one session is required.', 'ma-lumiere-clinic' ) );
		}

		$created = array();
		foreach ( array_values( $plan ) as $entry ) {
			$data = is_array( $entry ) ? $entry : array( 'session_number' => $entry );
			$data['patient_id']   = $patient_id;
			$data['treatment_id'] = $treatment_id;
			$result = self::create( $data );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$created[] = (int) $result;
		}
		return $created;
	}

	/**
	 * Update a session (status, scheduled date, notes). Locked sessions are
	 * frozen.
	 *
	 * @param int   $id    Session ID.
	 * @param array $input Raw values.
	 *
	 * @return array|WP_Error Session row.
	 */
	public static function update( $id, array $input ) {
		global $wpdb;

		$id   = absint( $id );
		$row  = self::get( $id );
		if ( ! $row ) {
			return new WP_Error( 'ml_session_not_found', __( 'Treatment session not found.', 'ma-lumiere-clinic' ) );
		}
		if ( (int) $row['is_locked'] ) {
			return new WP_Error( 'ml_session_locked', __( 'This session is locked.', 'ma-lumiere-clinic' ) );
		}

		$by    = get_current_user_id() ? get_current_user_id() : 0;
		$set   = array( 'updated_at' => current_time( 'mysql' ), 'updated_by' => $by ? $by : null );
		$form  = array( '%s', '%d' );

		if ( array_key_exists( 'status', $input ) ) {
			$status = sanitize_key( (string) $input['status'] );
			if ( in_array( $status, self::statuses(), true ) ) {
				$set['status'] = $status;
				$form[] = '%s';
				if ( 'completed' === $status ) {
					$set['completed_at'] = current_time( 'mysql' );
					$form[] = '%s';
				}
			}
		}
		if ( array_key_exists( 'scheduled_date', $input ) ) {
			$date = sanitize_text_field( (string) $input['scheduled_date'] );
			$set['scheduled_date'] = self::is_valid_date( $date ) ? $date : ( '' === $date ? null : $row['scheduled_date'] );
			$form[] = ( self::is_valid_date( $date ) || '' === $date ) ? ( '' === $date ? '%s' : '%s' ) : '%s';
		}
		if ( array_key_exists( 'session_type', $input ) ) {
			$set['session_type'] = mb_substr( sanitize_text_field( (string) $input['session_type'] ), 0, 50 );
			$form[] = '%s';
		}
		if ( array_key_exists( 'notes', $input ) ) {
			$set['notes'] = mb_substr( sanitize_textarea_field( (string) $input['notes'] ), 0, 2000 );
			$form[] = '%s';
		}
		if ( array_key_exists( 'is_locked', $input ) && isset( $input['is_locked'] ) && $input['is_locked'] ) {
			$set['is_locked'] = 1;
			$form[] = '%d';
		}

		$wpdb->update( ML_Database::table( 'treatment_sessions' ), $set, array( 'id' => $id ), $form, array( '%d' ) );

		$fresh = self::get( $id );
		ml_audit( 'treatment_session_updated', 'treatment_session', $id, sprintf( __( 'Treatment session #%d → %s', 'ma-lumiere-clinic' ), (int) $fresh['session_number'], $fresh['status'] ) );

		return $fresh;
	}

	/**
	 * Sessions for a patient, optionally scoped to one treatment.
	 *
	 * @param int $patient_id   Patient ID.
	 * @param int $treatment_id Optional treatment ID.
	 *
	 * @return array
	 */
	public static function for_patient( $patient_id, $treatment_id = 0 ) {
		global $wpdb;
		$table = ML_Database::table( 'treatment_sessions' );
		$sql   = "SELECT * FROM {$table} WHERE patient_id = %d";
		$args  = array( absint( $patient_id ) );
		if ( $treatment_id ) {
			$sql   .= ' AND treatment_id = %d';
			$args[] = absint( $treatment_id );
		}
		$sql  .= ' ORDER BY session_number ASC, id ASC';
		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$items = array();
		foreach ( (array) $rows as $row ) {
			$treatment = ! empty( $row['treatment_id'] ) ? ML_Treatment_Repository::get( (int) $row['treatment_id'] ) : null;
			$row['treatment_name'] = $treatment ? (string) $treatment['name'] : '';
			$items[] = $row;
		}
		return $items;
	}

	/**
	 * Fetch a single session.
	 *
	 * @param int $id Session ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$table = ML_Database::table( 'treatment_sessions' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		$treatment = ! empty( $row['treatment_id'] ) ? ML_Treatment_Repository::get( (int) $row['treatment_id'] ) : null;
		$row['treatment_name'] = $treatment ? (string) $treatment['name'] : '';
		return $row;
	}

	/**
	 * Highest session number recorded for a patient/treatment pair.
	 *
	 * @param int $patient_id   Patient ID.
	 * @param int $treatment_id Treatment ID.
	 *
	 * @return int
	 */
	private static function max_session( $patient_id, $treatment_id ) {
		global $wpdb;
		$table = ML_Database::table( 'treatment_sessions' );
		$sql   = 'SELECT MAX(session_number) FROM ' . $table . ' WHERE patient_id = %d AND treatment_id = %d';
		$val = $wpdb->get_var( $wpdb->prepare( $sql, absint( $patient_id ), $treatment_id ) );
		return (int) $val;
	}

	/**
	 * Strict Y-m-d date check.
	 *
	 * @param string $date Candidate date.
	 *
	 * @return bool
	 */
	private static function is_valid_date( $date ) {
		if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}

	/**
	 * $wpdb formats.
	 *
	 * @param array $row Row.
	 *
	 * @return array
	 */
	private static function formats( array $row ) {
		$formats = array();
		$ints    = array( 'id', 'patient_id', 'treatment_id', 'doctor_user_id', 'session_number', 'is_locked', 'created_by', 'updated_by' );
		foreach ( array_keys( $row ) as $key ) {
			$formats[ $key ] = in_array( $key, $ints, true ) ? '%d' : '%s';
		}
		return $formats;
	}
}