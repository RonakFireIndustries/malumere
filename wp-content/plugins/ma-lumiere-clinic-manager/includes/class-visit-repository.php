<?php
/**
 * Visit repository: consultation records with per-patient numbering,
 * cap-aware validation, an update history trail and an explicit lock so
 * finalized consultations cannot be silently rewritten.
 *
 * Numbers are per-patient (ML-V-0001…) and never reused. Updates record a
 * history entry (who, when, note) instead of discarding past context.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Visit_Repository {

	/**
	 * Per-patient visit number prefix.
	 *
	 * @var string
	 */
	const NUMBER_PREFIX = 'ML-V-';

	/**
	 * Visit statuses.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'open', 'completed' );
	}

	/**
	 * Persistable visit columns.
	 *
	 * @return array<string>
	 */
	public static function fields() {
		return array(
			'patient_id',
			'doctor_user_id',
			'visit_date',
			'chief_complaint',
			'diagnosis',
			'clinical_notes',
			'treatment_plan',
			'skin_type',
			'fitzpatrick_type',
			'skin_sensitivity',
			'oiliness_level',
			'pigmentation_notes',
			'acne_severity',
			'scarring_notes',
			'hair_loss_pattern',
			'hair_density',
			'scalp_condition',
			'hair_fall_duration',
			'status',
		);
	}

	/**
	 * Sanitize a raw input array into a full visit field set.
	 *
	 * @param mixed $input    Raw values.
	 * @param array $defaults Existing row for updates.
	 *
	 * @return array
	 */
	public static function sanitize( $input, $defaults = array() ) {
		$i = is_array( $input ) ? $input : array();

		$out = array();
		foreach ( self::fields() as $key ) {
			$raw  = array_key_exists( $key, $i ) ? $i[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
			$out[ $key ] = self::clean_field( $key, $raw );
		}
		return $out;
	}

	/**
	 * Validate a sanitized field set.
	 *
	 * @param array $clean Sanitized values.
	 *
	 * @return array<string,string>
	 */
	public static function validate( array $clean ) {
		$errors = array();

		if ( ! ML_Patient_Repository::get( absint( $clean['patient_id'] ) ) ) {
			$errors['patient_id'] = __( 'Please choose a valid patient.', 'ma-lumiere-clinic' );
		}
		if ( ! self::is_valid_date( $clean['visit_date'] ) ) {
			$errors['visit_date'] = __( 'Visit date must be a valid date.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['visit_date'] && strtotime( (string) $clean['visit_date'] ) > strtotime( 'tomorrow' ) ) {
			$errors['visit_date'] = __( 'Visit date cannot be in the future.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['doctor_user_id'] && ! get_userdata( absint( $clean['doctor_user_id'] ) ) ) {
			$errors['doctor_user_id'] = __( 'The doctor account does not exist.', 'ma-lumiere-clinic' );
		}
		if ( ! in_array( $clean['status'], self::statuses(), true ) ) {
			$errors['status'] = __( 'Please choose a valid status.', 'ma-lumiere-clinic' );
		}
		if ( '' === trim( (string) $clean['chief_complaint'] ) && '' === trim( (string) $clean['diagnosis'] ) ) {
			$errors['chief_complaint'] = __( 'A chief complaint or diagnosis is required for a consultation.', 'ma-lumiere-clinic' );
		}

		return $errors;
	}

	/**
	 * Create a visit with an allocated per-patient number + audit.
	 *
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error Visit ID on success.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$clean  = self::sanitize( $input );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_visit_invalid', __( 'Visit data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$now   = current_time( 'mysql' );
		$by    = get_current_user_id() ? get_current_user_id() : 0;
		$table = ML_Database::table( 'visits' );
		$id    = 0;

		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$row = array(
				'patient_id'        => absint( $clean['patient_id'] ),
				'visit_number'      => self::next_number( (int) $clean['patient_id'] ),
				'doctor_user_id'    => '' !== (string) $clean['doctor_user_id'] ? absint( $clean['doctor_user_id'] ) : null,
				'visit_date'        => $clean['visit_date'],
				'chief_complaint'   => self::null_or( $clean['chief_complaint'] ),
				'diagnosis'         => self::null_or( $clean['diagnosis'] ),
				'clinical_notes'    => self::null_or( $clean['clinical_notes'] ),
				'treatment_plan'    => self::null_or( $clean['treatment_plan'] ),
				'skin_type'         => self::null_or( $clean['skin_type'] ),
				'fitzpatrick_type'  => self::null_or( $clean['fitzpatrick_type'] ),
				'skin_sensitivity'  => self::null_or( $clean['skin_sensitivity'] ),
				'oiliness_level'    => self::null_or( $clean['oiliness_level'] ),
				'pigmentation_notes'=> self::null_or( $clean['pigmentation_notes'] ),
				'acne_severity'     => self::null_or( $clean['acne_severity'] ),
				'scarring_notes'    => self::null_or( $clean['scarring_notes'] ),
				'hair_loss_pattern' => self::null_or( $clean['hair_loss_pattern'] ),
				'hair_density'      => self::null_or( $clean['hair_density'] ),
				'scalp_condition'   => self::null_or( $clean['scalp_condition'] ),
				'hair_fall_duration'=> self::null_or( $clean['hair_fall_duration'] ),
				'status'            => $clean['status'],
				'is_locked'         => 0,
				'created_by'        => $by ? $by : null,
				'created_at'        => $now,
				'updated_at'        => $now,
			);

			$inserted = $wpdb->insert( $table, $row, self::formats( $row ) );
			if ( $inserted ) {
				$id = (int) $wpdb->insert_id;
				break;
			}
		}

		if ( ! $id ) {
			return new WP_Error( 'ml_visit_number', __( 'Could not allocate a visit number. Please try again.', 'ma-lumiere-clinic' ) );
		}

		$name = self::patient_name( (int) $clean['patient_id'] );
		ml_audit( 'visit_created', 'visit', $id, sprintf( __( 'Visit created: %s — %s', 'ma-lumiere-clinic' ), $name, $row['visit_number'] ) );

		return $id;
	}

	/**
	 * Update a visit. Locked visits are rejected. A short note recorded in
	 * the visit's update history is optional.
	 *
	 * @param int   $id     Visit ID.
	 * @param array $input  Raw values.
	 * @param string $note  Optional human note appended to update history.
	 *
	 * @return int|WP_Error Visit ID on success.
	 */
	public static function update( $id, array $input, $note = '' ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_visit_not_found', __( 'Visit not found.', 'ma-lumiere-clinic' ) );
		}
		if ( (int) $existing['is_locked'] ) {
			return new WP_Error( 'ml_visit_locked', __( 'This consultation is locked and can no longer be edited.', 'ma-lumiere-clinic' ) );
		}

		$clean  = self::sanitize( $input, $existing );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_visit_invalid', __( 'Visit data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$now    = current_time( 'mysql' );
		$by     = get_current_user_id() ? get_current_user_id() : 0;
		$table  = ML_Database::table( 'visits' );
		$history = self::history( $existing, (array) $clean, $note );

		$row = array(
			'patient_id'        => absint( $clean['patient_id'] ),
			'doctor_user_id'    => '' !== (string) $clean['doctor_user_id'] ? absint( $clean['doctor_user_id'] ) : null,
			'visit_date'        => $clean['visit_date'],
			'chief_complaint'   => self::null_or( $clean['chief_complaint'] ),
			'diagnosis'         => self::null_or( $clean['diagnosis'] ),
			'clinical_notes'    => self::null_or( $clean['clinical_notes'] ),
			'treatment_plan'    => self::null_or( $clean['treatment_plan'] ),
			'skin_type'         => self::null_or( $clean['skin_type'] ),
			'fitzpatrick_type'  => self::null_or( $clean['fitzpatrick_type'] ),
			'skin_sensitivity'  => self::null_or( $clean['skin_sensitivity'] ),
			'oiliness_level'    => self::null_or( $clean['oiliness_level'] ),
			'pigmentation_notes'=> self::null_or( $clean['pigmentation_notes'] ),
			'acne_severity'     => self::null_or( $clean['acne_severity'] ),
			'scarring_notes'    => self::null_or( $clean['scarring_notes'] ),
			'hair_loss_pattern' => self::null_or( $clean['hair_loss_pattern'] ),
			'hair_density'      => self::null_or( $clean['hair_density'] ),
			'scalp_condition'   => self::null_or( $clean['scalp_condition'] ),
			'hair_fall_duration'=> self::null_or( $clean['hair_fall_duration'] ),
			'status'            => $clean['status'],
			'updated_by'        => $by ? $by : null,
			'update_history'    => $history ? wp_json_encode( $history ) : $existing['update_history'],
			'updated_at'        => $now,
		);

		$wpdb->update( $table, $row, array( 'id' => $id ), self::formats( $row ), array( '%d' ) );

		$fresh = self::get( $id );
		if ( ! $fresh ) {
			return new WP_Error( 'ml_visit_update_failed', __( 'Visit update failed.', 'ma-lumiere-clinic' ) );
		}

		ml_audit( 'visit_updated', 'visit', $id, sprintf( __( 'Visit %s updated by %s', 'ma-lumiere-clinic' ), $fresh['visit_number'], wp_get_current_user()->display_name ) );

		return $id;
	}

	/**
	 * Fetch a single visit.
	 *
	 * @param int $id Visit ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$table = ML_Database::table( 'visits' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		if ( is_array( $row ) ) {
			self::decorate( $row );
		}
		return $row;
	}

	/**
	 * Visits for a patient, newest first.
	 *
	 * @param int $patient_id Patient ID.
	 *
	 * @return array
	 */
	public static function for_patient( $patient_id ) {
		global $wpdb;
		$table = ML_Database::table( 'visits' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE patient_id = %d ORDER BY visit_date DESC, id DESC", absint( $patient_id ) ),
			ARRAY_A
		);
		foreach ( (array) $rows as &$row ) {
			self::decorate( $row );
		}
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Filterable, paginated visit listing.
	 *
	 * @param array $args Filters: patient_id, doctor, from, to, status, search, page, per_page.
	 *
	 * @return array
	 */
	public static function list( array $args = array() ) {
		global $wpdb;
		$table = ML_Database::table( 'visits' );

		$page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page = min( 100, max( 1, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );
		$patient  = absint( isset( $args['patient_id'] ) ? $args['patient_id'] : 0 );
		$doctor   = absint( isset( $args['doctor'] ) ? $args['doctor'] : 0 );
		$from     = isset( $args['from'] ) ? sanitize_text_field( (string) $args['from'] ) : '';
		$to       = isset( $args['to'] ) ? sanitize_text_field( (string) $args['to'] ) : '';
		$status   = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$search   = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';

		if ( ! in_array( $status, self::statuses(), true ) ) {
			$status = '';
		}

		$where  = array( '1=1' );
		$params = array();
		if ( $patient ) {
			$where[]  = 'v.patient_id = %d';
			$params[] = $patient;
		}
		if ( $doctor ) {
			$where[]  = 'v.doctor_user_id = %d';
			$params[] = $doctor;
		}
		if ( self::is_valid_date( $from ) ) {
			$where[]  = 'v.visit_date >= %s';
			$params[] = $from;
		}
		if ( self::is_valid_date( $to ) ) {
			$where[]  = 'v.visit_date <= %s';
			$params[] = $to;
		}
		if ( '' !== $status ) {
			$where[]  = 'v.status = %s';
			$params[] = $status;
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = "(p.first_name LIKE %s OR p.last_name LIKE %s OR CONCAT(p.first_name, ' ', p.last_name) LIKE %s OR p.patient_uid LIKE %s OR v.visit_number LIKE %s)";
			foreach ( array( 1, 1, 1, 1, 1 ) as $i ) {
				$params[] = $like;
			}
		}

		$p        = ML_Database::table( 'patients' );
		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} v LEFT JOIN {$p} p ON p.id = v.patient_id WHERE {$where_sql}";
		$total   = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$pages   = (int) max( 1, ceil( $total / $per_page ) );
		$page    = min( $page, $pages );
		$offset  = ( $page - 1 ) * $per_page;

		$params[] = $per_page;
		$params[] = $offset;

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.*, p.first_name AS p_first_name, p.last_name AS p_last_name, p.patient_uid AS p_uid
				 FROM {$table} v LEFT JOIN {$p} p ON p.id = v.patient_id
				 WHERE {$where_sql} ORDER BY v.visit_date DESC, v.id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$params
			),
			ARRAY_A
		);

		foreach ( (array) $items as &$item ) {
			self::decorate( $item );
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
	 * Lock or unlock a visit (only when permission is checked at the call
	 * site). Unlocking records an audit entry.
	 *
	 * @param int  $id   Visit ID.
	 * @param bool $lock True to lock, false to unlock.
	 *
	 * @return bool|WP_Error
	 */
	public static function set_locked( $id, $lock ) {
		global $wpdb;
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_visit_not_found', __( 'Visit not found.', 'ma-lumiere-clinic' ) );
		}

		$updated = $wpdb->update(
			ML_Database::table( 'visits' ),
			array( 'is_locked' => $lock ? 1 : 0, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'ml_visit_lock_failed', __( 'Could not update the visit lock.', 'ma-lumiere-clinic' ) );
		}

		ml_audit( $lock ? 'visit_locked' : 'visit_unlocked', 'visit', $id, sprintf( __( 'Visit %s %s', 'ma-lumiere-clinic' ), $existing['visit_number'], $lock ? 'locked' : 'unlocked' ) );

		return (bool) $lock;
	}

	/**
	 * Next per-patient visit number (ML-V-0001…) using MAX() so numbers are
	 * never reused.
	 *
	 * @param int $patient_id Patient ID.
	 *
	 * @return string
	 */
	public static function next_number( $patient_id ) {
		global $wpdb;
		$patient_id = absint( $patient_id );
		$table      = ML_Database::table( 'visits' );
		$rows       = $wpdb->get_col(
			$wpdb->prepare( 'SELECT visit_number FROM ' . $table . ' WHERE patient_id = %d', $patient_id )
		);
		$max = 0;
		foreach ( (array) $rows as $num ) {
			$value = (int) str_replace( self::NUMBER_PREFIX, '', (string) $num );
			if ( $value > $max ) {
				$max = $value;
			}
		}
		return self::NUMBER_PREFIX . str_pad( (string) ( $max + 1 ), 4, '0', STR_PAD_LEFT );
	}

	/**
	 * Decoded update history for a visit (list of entries, oldest first).
	 *
	 * @param array $visit Visit row.
	 *
	 * @return array
	 */
	public static function history( array $visit, array $next = null, $note = '' ) {
		$history = array();
		if ( ! empty( $visit['update_history'] ) ) {
			$decoded = json_decode( (string) $visit['update_history'], true );
			$history = is_array( $decoded ) ? $decoded : array();
		}

		if ( is_array( $next ) ) {
			$changed = array();
			$keys    = array_diff( self::fields(), array( 'patient_id', 'doctor_user_id', 'status' ) );
			foreach ( $keys as $key ) {
				$before = isset( $visit[ $key ] ) ? (string) $visit[ $key ] : '';
				$after  = isset( $next[ $key ] ) ? (string) $next[ $key ] : '';
				if ( $before !== $after ) {
					$changed[] = $key;
				}
			}
			$by = wp_get_current_user();
			$history[] = array(
				'at'    => time(),
				'by'    => get_current_user_id(),
				'name'  => $by && $by->exists() ? $by->display_name : '',
				'note'  => mb_substr( sanitize_textarea_field( (string) $note ), 0, 300 ),
				'fields'=> $changed,
			);
		}

		return $history;
	}

	/**
	 * Decorate a row with patient name + lock/status labels for display.
	 *
	 * @param array $row Row by reference.
	 *
	 * @return void
	 */
	private static function decorate( array &$row ) {
		if ( empty( $row['p_first_name'] ) ) {
			$patient = ML_Patient_Repository::get( (int) $row['patient_id'] );
			$row['patient_name'] = $patient ? trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ) : (string) $row['patient_id'];
		} else {
			$row['patient_name'] = trim( (string) $row['p_first_name'] . ' ' . (string) $row['p_last_name'] );
		}
		if ( ! empty( $row['update_history'] ) ) {
			$history = json_decode( (string) $row['update_history'], true );
			$row['history_log'] = is_array( $history ) ? $history : array();
		} else {
			$row['history_log'] = array();
		}
	}

	/**
	 * Patient display name.
	 *
	 * @param int $patient_id Patient ID.
	 *
	 * @return string
	 */
	private static function patient_name( $patient_id ) {
		$patient = ML_Patient_Repository::get( $patient_id );
		return $patient ? trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ) : (string) $patient_id;
	}

	/**
	 * NULL when empty.
	 *
	 * @param mixed $value Value.
	 *
	 * @return string|null
	 */
	private static function null_or( $value ) {
		return '' === (string) $value ? null : (string) $value;
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
		$ints    = array( 'id', 'patient_id', 'doctor_user_id', 'is_locked', 'created_by', 'updated_by' );
		foreach ( array_keys( $row ) as $key ) {
			$formats[ $key ] = in_array( $key, $ints, true ) ? '%d' : '%s';
		}
		return $formats;
	}

	/**
	 * Clean one field.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Raw value.
	 *
	 * @return string
	 */
	private static function clean_field( $key, $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		switch ( $key ) {
			case 'patient_id':
			case 'doctor_user_id':
				return '' === trim( $value ) ? '' : (string) absint( $value );
			case 'visit_date':
				$value = sanitize_text_field( $value );
				if ( self::is_valid_date( $value ) ) {
					return $value;
				}
				return '';
			case 'chief_complaint':
			case 'diagnosis':
			case 'clinical_notes':
			case 'treatment_plan':
			case 'pigmentation_notes':
			case 'scarring_notes':
				return mb_substr( sanitize_textarea_field( $value ), 0, 5000 );
			case 'skin_type':
			case 'fitzpatrick_type':
			case 'skin_sensitivity':
			case 'oiliness_level':
			case 'acne_severity':
			case 'hair_density':
			case 'hair_fall_duration':
				return mb_substr( sanitize_text_field( $value ), 0, 100 );
			case 'hair_loss_pattern':
			case 'scalp_condition':
				return mb_substr( sanitize_text_field( $value ), 0, 150 );
			case 'status':
				return in_array( $value, self::statuses(), true ) ? $value : 'open';
			default:
				return sanitize_text_field( $value );
		}
	}
}