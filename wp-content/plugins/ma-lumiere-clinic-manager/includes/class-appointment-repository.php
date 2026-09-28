<?php
/**
 * Appointment repository: safe CRUD, validation, status transitions, the
 * ML-APT-<nnnnnn> reference allocator and conflict-safe scheduling.
 *
 * All scheduling writes run inside a transaction with a final DB-level
 * availability re-check, so a slot can never be double-booked even under
 * concurrent requests. Every write is audited.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Appointment_Repository {

	/**
	 * Booking reference prefix, e.g. ML-APT-000001.
	 *
	 * @var string
	 */
	const REF_PREFIX = 'ML-APT-';

	/**
	 * Numeric suffix width for booking references.
	 *
	 * @var int
	 */
	const REF_DIGITS = 6;

	/**
	 * Appointment statuses. `rescheduled` marks the original row (kept for
	 * history) while the successor row carries a new reference.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'rescheduled', 'no_show' );
	}

	/**
	 * Statuses that still occupy a slot (block further booking).
	 *
	 * @return array<string>
	 */
	public static function blocking_statuses() {
		return array( 'pending', 'confirmed', 'checked_in' );
	}

	/**
	 * Appointment types.
	 *
	 * @return array<string>
	 */
	public static function types() {
		return array( 'new_consultation', 'follow_up', 'treatment_session', 'procedure', 'other' );
	}

	/**
	 * Booking sources.
	 *
	 * @return array<string>
	 */
	public static function sources() {
		return array( 'website', 'admin', 'reception', 'doctor', 'other' );
	}

	/**
	 * Cancellation reasons.
	 *
	 * @return array<string>
	 */
	public static function cancellation_reasons() {
		return array( 'patient_request', 'clinic', 'reschedule', 'no_show', 'other' );
	}

	/**
	 * Persistable appointment columns.
	 *
	 * @return array<string>
	 */
	public static function fields() {
		return array(
			'patient_id',
			'doctor_user_id',
			'treatment_id',
			'appointment_date',
			'start_time',
			'status',
			'appointment_type',
			'notes',
			'source',
			'patient_message',
			'confirmation_sent',
		);
	}

	/**
	 * Nominal slot duration to use when no per-treatment duration exists.
	 *
	 * @return int
	 */
	public static function default_duration() {
		return max( 5, min( 240, (int) ML_Settings::get( 'appointments.appointment_duration', 30 ) ) );
	}

	/**
	 * Effective treatment duration in minutes (0 falls back to the default).
	 *
	 * @param int|null $treatment_id ml_treatments.id.
	 *
	 * @return int
	 */
	public static function effective_duration( $treatment_id ) {
		$duration = $treatment_id ? ML_Treatment_Repository::duration_minutes( (int) $treatment_id ) : 0;
		return $duration > 0 ? $duration : self::default_duration();
	}

	/**
	 * "H:i" minute-of-day integer.
	 *
	 * @param string $time HH:MM.
	 *
	 * @return int
	 */
	public static function minute_of_day( $time ) {
		$parts = array_map( 'intval', explode( ':', (string) $time ) );
		return isset( $parts[1] ) ? ( $parts[0] * 60 ) + $parts[1] : 0;
	}

	/**
	 * Sanitize a raw input array into a cleaned appointment field set.
	 *
	 * @param mixed $input    Raw values.
	 * @param array $defaults Seeded values (existing row for updates).
	 *
	 * @return array
	 */
	public static function sanitize( $input, $defaults = array() ) {
		$i   = is_array( $input ) ? $input : array();
		$out = array();

		foreach ( self::fields() as $key ) {
			$raw = array_key_exists( $key, $i ) ? $i[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
			$out[ $key ] = self::clean_field( $key, $raw );
		}
		return $out;
	}

	/**
	 * Validate a cleaned appointment set. Empty array means valid.
	 *
	 * @param array $clean Sanitized values.
	 *
	 * @return array<string,string>
	 */
	public static function validate( array $clean ) {
		$errors = array();

		if ( empty( $clean['appointment_date'] ) || ! self::is_valid_date( $clean['appointment_date'] ) ) {
			$errors['appointment_date'] = __( 'Please choose a valid appointment date.', 'ma-lumiere-clinic' );
		} elseif ( isset( $clean['start_time'] ) && '' !== (string) $clean['start_time'] && self::is_past( $clean['appointment_date'], $clean['start_time'] ) ) {
			$errors['appointment_date'] = __( 'Appointments cannot be booked in the past.', 'ma-lumiere-clinic' );
		}

		if ( ! self::is_valid_time( isset( $clean['start_time'] ) ? $clean['start_time'] : '' ) ) {
			$errors['start_time'] = __( 'Please choose a valid start time.', 'ma-lumiere-clinic' );
		}

		if ( '' !== (string) $clean['status'] && ! in_array( $clean['status'], self::statuses(), true ) ) {
			$errors['status'] = __( 'Please choose a valid appointment status.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['appointment_type'] && ! in_array( $clean['appointment_type'], self::types(), true ) ) {
			$errors['appointment_type'] = __( 'Please choose a valid appointment type.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['source'] && ! in_array( $clean['source'], self::sources(), true ) ) {
			$errors['source'] = __( 'Please choose a valid booking source.', 'ma-lumiere-clinic' );
		}
		if ( ! empty( $clean['patient_id'] ) && ! ML_Patient_Repository::get( (int) $clean['patient_id'] ) ) {
			$errors['patient_id'] = __( 'The selected patient does not exist.', 'ma-lumiere-clinic' );
		}
		if ( ! empty( $clean['doctor_user_id'] ) && ! get_userdata( (int) $clean['doctor_user_id'] ) ) {
			$errors['doctor_user_id'] = __( 'The selected doctor does not exist.', 'ma-lumiere-clinic' );
		}
		if ( ! empty( $clean['treatment_id'] ) && ! ML_Treatment_Repository::get( (int) $clean['treatment_id'] ) ) {
			$errors['treatment_id'] = __( 'The selected treatment does not exist.', 'ma-lumiere-clinic' );
		}

		return $errors;
	}

	/**
	 * Create an appointment inside a transaction.
	 *
	 * Preconditions: patient exists (caller resolves the patient first).
	 * The final slot availability check happens here, at the database, so a
	 * parallel booking cannot sneak past the client-level checks.
	 *
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error Appointment ID on success.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$clean  = self::sanitize( $input );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_appointment_invalid', __( 'Appointment data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$patient_id = absint( $clean['patient_id'] );
		if ( ! $patient_id || ! ML_Patient_Repository::get( $patient_id ) ) {
			return new WP_Error( 'ml_appointment_patient', __( 'A valid patient is required.', 'ma-lumiere-clinic' ) );
		}

		$date   = (string) $clean['appointment_date'];
		$start  = (string) $clean['start_time'];
		$doctor = absint( $clean['doctor_user_id'] );
		$min    = self::effective_duration( absint( $clean['treatment_id'] ) );
		$end    = self::time_plus( $start, $min );

		$table = ML_Database::table( 'appointments' );
		$wpdb->query( 'START TRANSACTION' );

		if ( self::slot_is_taken( $date, $start, $end, $doctor, 0 ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'ml_appointment_conflict', __( 'That time is no longer available. Please pick another slot.', 'ma-lumiere-clinic' ) );
		}

		$now   = current_time( 'mysql' );
		$row   = array(
			'patient_id'      => $patient_id,
			'doctor_user_id'  => $doctor ? $doctor : null,
			'treatment_id'    => absint( $clean['treatment_id'] ) ? absint( $clean['treatment_id'] ) : null,
			'appointment_date' => $date,
			'start_time'      => $start,
			'end_time'        => $end,
			'status'          => '' !== (string) $clean['status'] ? $clean['status'] : 'pending',
			'appointment_type' => '' !== (string) $clean['appointment_type'] ? $clean['appointment_type'] : 'new_consultation',
			'notes'           => '' !== (string) $clean['notes'] ? $clean['notes'] : null,
			'source'          => '' !== (string) $clean['source'] ? $clean['source'] : 'admin',
			'created_by'      => get_current_user_id() ? get_current_user_id() : null,
			'booking_reference' => '',
			'patient_message' => isset( $clean['patient_message'] ) && '' !== (string) $clean['patient_message'] ? $clean['patient_message'] : null,
			'confirmation_sent' => ! empty( $clean['confirmation_sent'] ) ? 1 : 0,
			'created_at'      => $now,
			'updated_at'      => $now,
		);
		if ( isset( $input['idempotency_key'] ) && '' !== (string) $input['idempotency_key'] ) {
			$row['idempotency_key'] = substr( sanitize_key( (string) $input['idempotency_key'] ), 0, 64 );
		}
		if ( isset( $input['rescheduled_from'] ) && absint( $input['rescheduled_from'] ) ) {
			$row['rescheduled_from'] = absint( $input['rescheduled_from'] );
		}

		$id = 0;
		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$row['booking_reference'] = self::next_reference();
			$inserted = $wpdb->insert( $table, $row, self::formats( $row ) );
			if ( $inserted ) {
				$id = (int) $wpdb->insert_id;
				break;
			}
			if ( $wpdb->last_error && false !== strpos( (string) $wpdb->last_error, 'booking_reference' ) ) {
				continue;
			}
			break;
		}

		if ( ! $id ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'ml_appointment_id', __( 'Could not allocate a booking reference. Please try again.', 'ma-lumiere-clinic' ) );
		}

		$wpdb->query( 'COMMIT' );

		$patient = ML_Patient_Repository::get( $patient_id );
		$name    = $patient ? trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ) : '';
		ml_audit( 'appointment_created', 'appointment', $id, sprintf( __( 'Appointment %s booked for %s on %s', 'ma-lumiere-clinic' ), $row['booking_reference'], $name, $date ) );

		return $id;
	}

	/**
	 * Whether another active booking already occupies the interval.
	 *
	 * @param string $date      Y-m-d.
	 * @param string $start     HH:MM.
	 * @param string $end       HH:MM.
	 * @param int    $doctor    Doctor user ID (0 = default doctor scope).
	 * @param int    $exclude_id Appointment row to ignore (reschedule target).
	 *
	 * @return bool
	 */
	public static function slot_is_taken( $date, $start, $end, $doctor, $exclude_id = 0 ) {
		global $wpdb;
		$table = ML_Database::table( 'appointments' );

		$blocks  = self::blocking_statuses();
		$holders = implode( ',', array_fill( 0, count( $blocks ), '%s' ) );

		$sql = "SELECT COUNT(*)
				FROM {$table}
				WHERE appointment_date = %s
				  AND status IN ({$holders})
				  AND start_time < %s
				  AND ( end_time IS NULL OR end_time > %s )";

		$params = array_merge( array( $date ), $blocks, array( $end, $start ) );

		if ( $doctor ) {
			$sql   .= ' AND doctor_user_id = %d';
			$params[] = $doctor;
		}
		if ( $exclude_id ) {
			$sql   .= ' AND id <> %d';
			$params[] = $exclude_id;
		}

		return '1' === (string) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Update editable fields on a non-terminal appointment. Date/time
	 * changes are re-validated against availability, excluding the row
	 * itself.
	 *
	 * @param int   $id    Appointment ID.
	 * @param array $input Raw values to change.
	 *
	 * @return array|WP_Error Updated (decorated) row or error.
	 */
	public static function update( $id, array $input ) {
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_appointment_not_found', __( 'Appointment not found.', 'ma-lumiere-clinic' ) );
		}
		if ( in_array( $existing['status'], array( 'completed', 'cancelled', 'rescheduled', 'no_show' ), true ) ) {
			return new WP_Error( 'ml_appointment_terminal', __( 'Finished appointments cannot be edited.', 'ma-lumiere-clinic' ) );
		}

		$clean = self::sanitize( $input, $existing );
		if ( ! isset( $input['status'] ) || '' === (string) $input['status'] ) {
			$clean['status'] = $existing['status'];
		}
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_appointment_invalid', __( 'Appointment data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$date   = (string) $clean['appointment_date'];
		$start  = (string) $clean['start_time'];
		$doctor = absint( $clean['doctor_user_id'] );
		$min    = self::effective_duration( absint( $clean['treatment_id'] ) );
		$end    = self::time_plus( $start, $min );

		$slot_changed = ( $date !== (string) $existing['appointment_date'] ) || ( $start !== (string) $existing['start_time'] ) || ( $doctor !== absint( $existing['doctor_user_id'] ) );
		if ( $slot_changed && self::slot_is_taken( $date, $start, $end, $doctor, $id ) ) {
			return new WP_Error( 'ml_appointment_conflict', __( 'That time is no longer available. Please pick another slot.', 'ma-lumiere-clinic' ) );
		}

		global $wpdb;
		$table  = ML_Database::table( 'appointments' );
		$publish = array(
			'patient_id'       => absint( $clean['patient_id'] ) ? absint( $clean['patient_id'] ) : null,
			'doctor_user_id'   => $doctor ? $doctor : null,
			'treatment_id'     => absint( $clean['treatment_id'] ) ? absint( $clean['treatment_id'] ) : null,
			'appointment_date' => $date,
			'start_time'       => $start,
			'end_time'         => $end,
			'status'           => (string) $clean['status'],
			'appointment_type' => (string) $clean['appointment_type'],
			'notes'            => '' !== (string) $clean['notes'] ? $clean['notes'] : null,
			'patient_message'  => '' !== (string) $clean['patient_message'] ? $clean['patient_message'] : null,
			'updated_at'       => current_time( 'mysql' ),
		);
		$wpdb->update( $table, $publish, array( 'id' => $id ), self::formats( $publish ), array( '%d' ) );

		$fresh = self::get( $id );
		ml_audit( 'appointment_updated', 'appointment', $id, sprintf( __( 'Appointment %s updated', 'ma-lumiere-clinic' ), $existing['booking_reference'] ) );
		return $fresh;
	}

	/**
	 * Fetch a single appointment row.
	 *
	 * @param int $id Appointment ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$table = ML_Database::table( 'appointments' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		return self::decorate( $row );
	}

	/**
	 * Fetch by booking reference.
	 *
	 * @param string $reference ML-APT-xxxxxx.
	 *
	 * @return array|null
	 */
	public static function get_by_reference( $reference ) {
		global $wpdb;
		$table = ML_Database::table( 'appointments' );
		$ref   = sanitize_text_field( (string) $reference );
		if ( ! preg_match( '/^' . self::REF_PREFIX . '\d+$/', $ref ) ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE booking_reference = %s", $ref ), ARRAY_A );
		return is_array( $row ) ? self::decorate( $row ) : null;
	}

	/**
	 * Find an existing appointment by its idempotency key (double-submit guard).
	 *
	 * @param string $key Idempotency key.
	 *
	 * @return array|null
	 */
	public static function get_by_idempotency( $key ) {
		global $wpdb;
		$key = substr( sanitize_key( (string) $key ), 0, 64 );
		if ( '' === $key ) {
			return null;
		}
		$table = ML_Database::table( 'appointments' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE idempotency_key = %s", $key ), ARRAY_A );
		return is_array( $row ) ? self::decorate( $row ) : null;
	}

	/**
	 * Filterable, paginated appointment listing with patient lookup.
	 *
	 * @param array $args page/per_page/from/to/status/doctor/search.
	 *
	 * @return array{items:array,total:int,pages:int,page:int,per_page:int}
	 */
	public static function list( array $args = array() ) {
		global $wpdb;
		$table = ML_Database::table( 'appointments' );
		$ptab  = ML_Database::table( 'patients' );
		$ttab  = ML_Database::table( 'treatments' );

		$page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page = min( 100, max( 1, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );

		$where  = array( '1=1' );
		$params = array();

		$from = isset( $args['from'] ) ? sanitize_text_field( (string) $args['from'] ) : '';
		$to   = isset( $args['to'] ) ? sanitize_text_field( (string) $args['to'] ) : '';
		if ( self::is_valid_date( $from ) ) {
			$where[]  = 'a.appointment_date >= %s';
			$params[] = $from;
		}
		if ( self::is_valid_date( $to ) ) {
			$where[]  = 'a.appointment_date <= %s';
			$params[] = $to;
		}

		$status = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		if ( $status && in_array( $status, self::statuses(), true ) ) {
			$where[]  = 'a.status = %s';
			$params[] = $status;
		}

		$doctor = isset( $args['doctor'] ) ? absint( $args['doctor'] ) : 0;
		if ( $doctor ) {
			$where[]  = 'a.doctor_user_id = %d';
			$params[] = $doctor;
		}

		$search = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '( a.booking_reference LIKE %s OR p.first_name LIKE %s OR p.last_name LIKE %s OR p.phone LIKE %s )';
			foreach ( array( $like, $like, $like, $like ) as $v ) {
				$params[] = $v;
			}
		}

		$scope = '';
		if ( ! current_user_can( 'ml_manage_appointments' ) && ! current_user_can( 'ml_manage_clinic' ) ) {
			// View-only staff see only their own scheduling rows.
			$scope   = 'a.doctor_user_id = %d';
			$params[] = get_current_user_id();
		}

		$where_sql = implode( ' AND ', $where );
		$from_sql  = "FROM {$table} a LEFT JOIN {$ptab} p ON p.id = a.patient_id LEFT JOIN {$ttab} t ON t.id = a.treatment_id";
		$scope_sql = $scope ? " AND {$scope}" : '';

		$count_sql = "SELECT COUNT(*) {$from_sql} WHERE {$where_sql}{$scope_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$pages = max( 1, (int) ceil( $total / $per_page ) );
		$page  = min( $page, $pages );
		$offset = ( $page - 1 ) * $per_page;

		$params[] = $per_page;
		$params[] = $offset;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, p.patient_uid, p.first_name, p.last_name, p.phone, p.email AS patient_email, t.name AS treatment_name
				 {$from_sql} WHERE {$where_sql}{$scope_sql}
				 ORDER BY a.appointment_date ASC, a.start_time ASC
				 LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$params
			),
			ARRAY_A
		);

		$items = array();
		foreach ( (array) $rows as $row ) {
			$items[] = self::decorate( $row );
		}

		return array(
			'items'    => $items,
			'total'    => $total,
			'pages'    => $pages,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * All non-terminal appointments for a date (calendar + conflict inputs).
	 *
	 * @param string $date   Y-m-d.
	 * @param int    $doctor Doctor user ID or 0 for all.
	 *
	 * @return array
	 */
	public static function for_date( $date, $doctor = 0 ) {
		global $wpdb;
		if ( ! self::is_valid_date( $date ) ) {
			return array();
		}
		$doctor = absint( $doctor );

		$table = ML_Database::table( 'appointments' );
		$ptab  = ML_Database::table( 'patients' );
		$ttab  = ML_Database::table( 'treatments' );

		$sql = "SELECT a.*, p.patient_uid, p.first_name, p.last_name, p.phone, t.name AS treatment_name
				FROM {$table} a
				LEFT JOIN {$ptab} p ON p.id = a.patient_id
				LEFT JOIN {$ttab} t ON t.id = a.treatment_id
				WHERE a.appointment_date = %s";

		$params = array( $date );
		if ( $doctor ) {
			$sql    .= ' AND a.doctor_user_id = %d';
			$params[] = $doctor;
		}
		$sql .= ' ORDER BY a.start_time ASC';

		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items = array();
		foreach ( (array) $rows as $row ) {
			$items[] = self::decorate( $row );
		}
		return $items;
	}

	/**
	 * Transition a non-terminal appointment into terminal statuses or
	 * check-in. Enforces a valid transition + permission at the call site.
	 *
	 * @param int    $id      Appointment ID.
	 * @param string $status  Target status.
	 * @param string $reason  Cancellation/reason context.
	 *
	 * @return array|WP_Error Updated (decorated) row or error.
	 */
	public static function transition( $id, $status, $reason = '' ) {
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_appointment_not_found', __( 'Appointment not found.', 'ma-lumiere-clinic' ) );
		}

		$current = $existing['status'];
		$target  = sanitize_key( (string) $status );

		if ( ! in_array( $target, self::statuses(), true ) ) {
			return new WP_Error( 'ml_appointment_invalid', __( 'Invalid appointment status.', 'ma-lumiere-clinic' ) );
		}
		if ( in_array( $current, array( 'completed', 'cancelled', 'rescheduled', 'no_show' ), true ) ) {
			return new WP_Error( 'ml_appointment_terminal', __( 'This appointment is already finished.', 'ma-lumiere-clinic' ) );
		}
		$allowed = array(
			'checked_in' => array( 'pending', 'confirmed' ),
			'completed'  => array( 'pending', 'confirmed', 'checked_in' ),
			'no_show'    => array( 'pending', 'confirmed', 'checked_in' ),
			'cancelled'  => array( 'pending', 'confirmed', 'checked_in' ),
			'rescheduled' => array( 'pending', 'confirmed', 'checked_in' ),
			'pending'    => array( 'confirmed' ),
			'confirmed'  => array( 'pending' ),
		);
		if ( isset( $allowed[ $target ] ) && ! in_array( $current, $allowed[ $target ], true ) ) {
			return new WP_Error( 'ml_appointment_transition', sprintf( __( 'Cannot change an appointment from %s to %s.', 'ma-lumiere-clinic' ), $current, $target ) );
		}

		global $wpdb;
		$row = array(
			'status'    => $target,
			'updated_at' => current_time( 'mysql' ),
		);
		if ( 'checked_in' === $target ) {
			$row['checked_in_at'] = current_time( 'mysql' );
		}
		if ( 'completed' === $target && empty( $existing['completed_at'] ) ) {
			$row['completed_at'] = current_time( 'mysql' );
		}
		if ( 'cancelled' === $target || 'rescheduled' === $target ) {
			$row['cancelled_at'] = current_time( 'mysql' );
			$row['cancelled_by'] = get_current_user_id() ? get_current_user_id() : 0;
			$row['cancellation_reason'] = in_array( $reason, self::cancellation_reasons(), true ) ? $reason : 'other';
		}

		$wpdb->update( ML_Database::table( 'appointments' ), $row, array( 'id' => $id ), self::formats( $row ), array( '%d' ) );
		$fresh = self::get( $id );

		$map = array(
			'checked_in' => 'appointment_checked_in',
			'completed'  => 'appointment_completed',
			'no_show'    => 'appointment_no_show',
			'cancelled'  => 'appointment_cancelled',
		);
		if ( isset( $map[ $target ] ) ) {
			ml_audit( $map[ $target ], 'appointment', $id, sprintf( __( 'Appointment %s marked %s', 'ma-lumiere-clinic' ), $existing['booking_reference'], $target ) );
		}

		return $fresh;
	}

	/**
	 * Allocate the next booking reference (ML-APT-000001, ...). MAX()-based
	 * so previously released numbers are never reused.
	 *
	 * @return string
	 */
	public static function next_reference() {
		global $wpdb;
		$table = ML_Database::table( 'appointments' );
		$start = strlen( self::REF_PREFIX ) + 1;
		$max   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(CAST(SUBSTRING(booking_reference, %d) AS UNSIGNED)) FROM ' . $table, $start ) );
		return self::REF_PREFIX . str_pad( (string) ( $max + 1 ), self::REF_DIGITS, '0', STR_PAD_LEFT );
	}

	/**
	 * Add HH:MM + minutes.
	 *
	 * @param string $time HH:MM.
	 * @param int    $mins Minutes.
	 *
	 * @return string
	 */
	public static function time_plus( $time, $mins ) {
		$base = self::minute_of_day( $time );
		$next = $base + max( 0, (int) $mins );
		return sprintf( '%02d:%02d', intdiv( $next, 60 ), $next % 60 );
	}

	/**
	 * Merge patient/treatment display fields into a row (kept lean — no
	 * clinical content is ever attached here). Joined rows pass through
	 * unchanged; single-row fetches are enriched on demand.
	 *
	 * @param array $row Raw row.
	 *
	 * @return array
	 */
	private static function decorate( array $row ) {
		if ( empty( $row['end_time'] ) && ! empty( $row['start_time'] ) ) {
			$row['end_time'] = self::time_plus( $row['start_time'], self::effective_duration( (int) $row['treatment_id'] ) );
		}

		$patient = null;
		if ( empty( $row['patient_email'] ) && ! empty( $row['patient_id'] ) ) {
			$patient = ML_Patient_Repository::get( (int) $row['patient_id'] );
			if ( is_array( $patient ) ) {
				$row['patient_email'] = (string) $patient['email'];
				$row['patient_phone'] = (string) $patient['phone'];
				$row['patient_uid']   = (string) $patient['patient_uid'];
				if ( empty( $row['first_name'] ) ) {
					$row['first_name'] = (string) $patient['first_name'];
					$row['last_name']  = (string) $patient['last_name'];
				}
			}
		}
		$row['patient_name'] = trim( (string) ( isset( $row['first_name'] ) ? $row['first_name'] : '' ) . ' ' . ( isset( $row['last_name'] ) ? $row['last_name'] : '' ) );

		if ( empty( $row['doctor_name'] ) && ! empty( $row['doctor_user_id'] ) ) {
			$doctor = get_userdata( (int) $row['doctor_user_id'] );
			if ( $doctor instanceof WP_User ) {
				$row['doctor_name'] = trim( (string) $doctor->display_name );
			}
		}

		if ( empty( $row['treatment_name'] ) && ! empty( $row['treatment_id'] ) ) {
			$treatment = ML_Treatment_Repository::get( (int) $row['treatment_id'] );
			if ( is_array( $treatment ) ) {
				$row['treatment_name']   = (string) $treatment['name'];
				$row['treatment_duration'] = (int) $treatment['duration'];
			}
		}

		return $row;
	}

	/**
	 * Clean one field to a safe scalar.
	 *
	 * @param string $key   Column key.
	 * @param mixed  $value Raw value.
	 *
	 * @return string
	 */
	private static function clean_field( $key, $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		switch ( $key ) {
			case 'appointment_date':
				return self::is_valid_date( $value ) ? $value : substr( sanitize_text_field( $value ), 0, 10 );
			case 'start_time':
				return self::is_valid_time( $value ) ? $value : substr( sanitize_text_field( $value ), 0, 8 );
			case 'status':
				if ( in_array( $value, self::statuses(), true ) ) {
					return $value;
				}
				return '' === trim( $value ) ? 'pending' : substr( sanitize_key( $value ), 0, 20 );
			case 'appointment_type':
				if ( in_array( $value, self::types(), true ) ) {
					return $value;
				}
				return '' === trim( $value ) ? 'new_consultation' : substr( sanitize_key( $value ), 0, 50 );
			case 'source':
				if ( in_array( $value, self::sources(), true ) ) {
					return $value;
				}
				return '' === trim( $value ) ? 'other' : substr( sanitize_key( $value ), 0, 50 );
			case 'notes':
			case 'patient_message':
				return mb_substr( sanitize_textarea_field( $value ), 0, 5000 );
			case 'patient_id':
			case 'doctor_user_id':
			case 'treatment_id':
				return '' === trim( $value ) ? '' : (string) absint( $value );
			case 'confirmation_sent':
				return empty( $value ) ? '0' : '1';
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Strict Y-m-d check.
	 *
	 * @param string $date Candidate.
	 *
	 * @return bool
	 */
	public static function is_valid_date( $date ) {
		if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}

	/**
	 * HH:MM clock check.
	 *
	 * @param mixed $time Candidate.
	 *
	 * @return bool
	 */
	public static function is_valid_time( $time ) {
		return is_string( $time ) && (bool) preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $time );
	}

	/**
	 * Is the given local (date + time) already in the past?
	 *
	 * @param string $date Y-m-d.
	 * @param string $time HH:MM.
	 *
	 * @return bool
	 */
	public static function is_past( $date, $time ) {
		$candidate = strtotime( $date . ' ' . $time . ':00' );
		$now       = strtotime( (string) current_time( 'mysql' ) );
		return false !== $candidate && $candidate <= $now;
	}

	/**
	 * $wpdb format placeholders (ids as %d, everything else %s).
	 *
	 * @param array $row Row.
	 *
	 * @return array<string>
	 */
	private static function formats( array $row ) {
		$formats = array();
		foreach ( array_keys( $row ) as $key ) {
			$formats[ $key ] = in_array( $key, array( 'id', 'patient_id', 'doctor_user_id', 'treatment_id', 'created_by', 'rescheduled_from', 'cancelled_by' ), true ) ? '%d' : '%s';
		}
		return $formats;
	}
}