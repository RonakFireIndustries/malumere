<?php
/**
 * Follow-up repository: scheduled re-check visits with cap-aware
 * sanitization. Follow-ups are derived from doctor decisions (never
 * invented); completions and cancellations are explicit user actions so
 * patient records stay truthful.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Followup_Repository {

	/**
	 * Follow-up statuses.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'upcoming', 'completed', 'cancelled' );
	}

	/**
	 * Status display labels.
	 *
	 * @return array<string,string>
	 */
	public static function status_labels() {
		return array(
			'upcoming'  => __( 'Upcoming', 'ma-lumiere-clinic' ),
			'completed' => __( 'Completed', 'ma-lumiere-clinic' ),
			'cancelled' => __( 'Cancelled', 'ma-lumiere-clinic' ),
		);
	}

	/**
	 * Create a follow-up scheduled for a patient.
	 *
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error Follow-up ID.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$patient_id = absint( isset( $input['patient_id'] ) ? $input['patient_id'] : 0 );
		$visit_id   = absint( isset( $input['visit_id'] ) ? $input['visit_id'] : 0 );
		$doctor_id  = absint( isset( $input['doctor_user_id'] ) ? $input['doctor_user_id'] : 0 );

		if ( ! ML_Patient_Repository::get( $patient_id ) ) {
			return new WP_Error( 'ml_followup_invalid', __( 'Please choose a valid patient.', 'ma-lumiere-clinic' ), array( 'errors' => array( 'patient_id' => __( 'Please choose a valid patient.', 'ma-lumiere-clinic' ) ) ) );
		}
		if ( $visit_id && ( ! $visit = ML_Visit_Repository::get( $visit_id ) ) ) {
			return new WP_Error( 'ml_followup_invalid', __( 'The linked visit does not exist.', 'ma-lumiere-clinic' ), array( 'errors' => array( 'visit_id' => __( 'The linked visit does not exist.', 'ma-lumiere-clinic' ) ) ) );
		}
		if ( $doctor_id && ! get_userdata( $doctor_id ) ) {
			return new WP_Error( 'ml_followup_invalid', __( 'The doctor account does not exist.', 'ma-lumiere-clinic' ), array( 'errors' => array( 'doctor_user_id' => __( 'The doctor account does not exist.', 'ma-lumiere-clinic' ) ) ) );
		}

		$date_raw = isset( $input['followup_date'] ) ? (string) $input['followup_date'] : '';
		if ( ! self::is_valid_date( $date_raw ) ) {
			return new WP_Error( 'ml_followup_invalid', __( 'Follow-up date must be a valid date.', 'ma-lumiere-clinic' ), array( 'errors' => array( 'followup_date' => __( 'Follow-up date must be a valid date.', 'ma-lumiere-clinic' ) ) ) );
		}

		$now   = current_time( 'mysql' );
		$by    = get_current_user_id() ? get_current_user_id() : 0;
		$doctor_id = $doctor_id ? $doctor_id : ( $by ? $by : 0 );

		$row = array(
			'patient_id'   => $patient_id,
			'visit_id'     => $visit_id ? $visit_id : null,
			'doctor_user_id' => $doctor_id ? $doctor_id : null,
			'followup_date' => $date_raw,
			'reason'       => isset( $input['reason'] ) ? mb_substr( sanitize_text_field( (string) $input['reason'] ), 0, 200 ) : null,
			'status'       => 'upcoming',
			'notes'        => isset( $input['notes'] ) ? mb_substr( sanitize_textarea_field( (string) $input['notes'] ), 0, 2000 ) : null,
			'created_at'   => $now,
			'updated_at'   => $now,
		);

		$inserted = $wpdb->insert( ML_Database::table( 'followups' ), $row, self::formats( $row ) );
		if ( ! $inserted ) {
			return new WP_Error( 'ml_followup_create_failed', __( 'Could not create the follow-up.', 'ma-lumiere-clinic' ) );
		}
		$id = (int) $wpdb->insert_id;

		ml_audit( 'followup_created', 'followup', $id, sprintf( __( 'Follow-up scheduled for %s', 'ma-lumiere-clinic' ), ml_date( $date_raw ) ) );

		return $id;
	}

	/**
	 * Transition a follow-up (completed / cancelled / reopened).
	 *
	 * @param int    $id     Follow-up ID.
	 * @param string $status Target status.
	 *
	 * @return array|WP_Error Follow-up row.
	 */
	public static function set_status( $id, $status ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! self::get( $id ) ) {
			return new WP_Error( 'ml_followup_not_found', __( 'Follow-up not found.', 'ma-lumiere-clinic' ) );
		}
		$status = sanitize_key( (string) $status );
		if ( ! in_array( $status, self::statuses(), true ) ) {
			return new WP_Error( 'ml_followup_status', __( 'Invalid follow-up status.', 'ma-lumiere-clinic' ) );
		}

		$wpdb->update(
			ML_Database::table( 'followups' ),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$fresh = self::get( $id );

		$action = 'followup_updated';
		if ( 'completed' === $status ) {
			$action = 'followup_completed';
		} elseif ( 'cancelled' === $status ) {
			$action = 'followup_cancelled';
		}
		ml_audit( $action, 'followup', $id, sprintf( __( 'Follow-up marked %s', 'ma-lumiere-clinic' ), $status ) );

		return $fresh;
	}

	/**
	 * Fetch a single follow-up.
	 *
	 * @param int $id Follow-up ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$table = ML_Database::table( 'followups' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Upcoming follow-ups for a patient on a given date (inclusive).
	 *
	 * @param int    $patient_id Patient ID.
	 * @param string $date       Date to check.
	 *
	 * @return array
	 */
	public static function upcoming( $patient_id, $date ) {
		global $wpdb;
		$table = ML_Database::table( 'followups' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE patient_id = %d AND followup_date = %s AND status = 'upcoming' ORDER BY id ASC",
				absint( $patient_id ),
				$date
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Follow-ups for a patient, closest first.
	 *
	 * @param int    $patient_id Patient ID.
	 * @param string $status     Optional status filter.
	 *
	 * @return array
	 */
	public static function for_patient( $patient_id, $status = '' ) {
		global $wpdb;
		$table = ML_Database::table( 'followups' );
		$sql   = 'SELECT * FROM ' . $table . ' WHERE patient_id = %d';
		$args  = array( absint( $patient_id ) );
		if ( $status && in_array( $status, self::statuses(), true ) ) {
			$sql   .= ' AND status = %s';
			$args[] = $status;
		}
		$sql  .= ' ORDER BY followup_date ASC, id ASC';
		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Filterable, paginated listing.
	 *
	 * @param array $args Filters: patient_id, doctor, status, from, to, search, page, per_page.
	 *
	 * @return array
	 */
	public static function list( array $args = array() ) {
		global $wpdb;
		$table = ML_Database::table( 'followups' );
		$p     = ML_Database::table( 'patients' );

		$page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page = min( 100, max( 1, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );
		$patient  = absint( isset( $args['patient_id'] ) ? $args['patient_id'] : 0 );
		$doctor   = absint( isset( $args['doctor'] ) ? $args['doctor'] : 0 );
		$status   = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$from     = isset( $args['from'] ) ? sanitize_text_field( (string) $args['from'] ) : '';
		$to       = isset( $args['to'] ) ? sanitize_text_field( (string) $args['to'] ) : '';
		$search   = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$overdue  = ! empty( $args['overdue'] ) && 'upcoming' === $status;

		if ( ! in_array( $status, self::statuses(), true ) ) {
			$status = '';
		}

		$where  = array( '1=1' );
		$params = array();
		if ( $patient ) {
			$where[]  = 'f.patient_id = %d';
			$params[] = $patient;
		}
		if ( $doctor ) {
			$where[]  = 'f.doctor_user_id = %d';
			$params[] = $doctor;
		}
		if ( '' !== $status ) {
			$where[]  = 'f.status = %s';
			$params[] = $status;
		}
		if ( $overdue ) {
			$where[]  = 'f.followup_date < %s';
			$params[] = current_time( 'Y-m-d' );
		}
		if ( self::is_valid_date( $from ) ) {
			$where[]  = 'f.followup_date >= %s';
			$params[] = $from;
		}
		if ( self::is_valid_date( $to ) ) {
			$where[]  = 'f.followup_date <= %s';
			$params[] = $to;
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = "(p.first_name LIKE %s OR p.last_name LIKE %s OR CONCAT(p.first_name, ' ', p.last_name) LIKE %s OR p.patient_uid LIKE %s)";
			foreach ( array( 1, 1, 1, 1 ) as $i ) {
				$params[] = $like;
			}
		}

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} f LEFT JOIN {$p} p ON p.id = f.patient_id WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$pages     = (int) max( 1, ceil( $total / $per_page ) );
		$page      = min( $page, $pages );
		$offset    = ( $page - 1 ) * $per_page;

		$params[] = $per_page;
		$params[] = $offset;

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.*, p.first_name AS p_first_name, p.last_name AS p_last_name, p.patient_uid AS p_uid
				 FROM {$table} f LEFT JOIN {$p} p ON p.id = f.patient_id
				 WHERE {$where_sql} ORDER BY f.followup_date ASC, f.id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$params
			),
			ARRAY_A
		);

		foreach ( (array) $items as &$item ) {
			if ( empty( $item['p_first_name'] ) ) {
				$patient = ML_Patient_Repository::get( (int) $item['patient_id'] );
				$item['patient_name'] = $patient ? trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ) : (string) $item['patient_id'];
			} else {
				$item['patient_name'] = trim( (string) $item['p_first_name'] . ' ' . (string) $item['p_last_name'] );
			}
		}

		return array(
			'items'    => is_array( $items ) ? $items : array(),
			'total'    => $total,
			'pages'    => $pages,
			'page'     => $page,
			'per_page' => $per_page,
		);
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
		$ints    = array( 'id', 'patient_id', 'visit_id', 'doctor_user_id', 'reminder_sent' );
		foreach ( array_keys( $row ) as $key ) {
			$formats[ $key ] = in_array( $key, $ints, true ) ? '%d' : '%s';
		}
		return $formats;
	}
}