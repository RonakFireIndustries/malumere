<?php
/**
 * Prescription repository: ML-RX-###### numbering, draft → final lifecycle
 * with revision chains (corrections become new versions of the same number),
 * line-item management and cap-aware sanitization.
 *
 * A prescription has a single stable number; revised versions share it and
 * increment `version`. Only finalized rows carry a number and become
 * exportable / printable; drafts are editable.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Prescription_Repository {

	/**
	 * Prescription number prefix.
	 *
	 * @var string
	 */
	const RX_PREFIX = 'ML-RX-';

	/**
	 * Numeric suffix width for prescription numbers.
	 *
	 * @var int
	 */
	const RX_DIGITS = 6;

	/**
	 * Prescription statuses.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'draft', 'final' );
	}

	/**
	 * Persistable prescription columns (excluding items).
	 *
	 * @return array<string>
	 */
	public static function fields() {
		return array(
			'patient_id',
			'visit_id',
			'doctor_user_id',
			'prescription_date',
			'diagnosis',
			'doctor_notes',
			'advice',
			'follow_up_date',
			'status',
		);
	}

	/**
	 * Sanitize a raw input array into a prescription field set.
	 *
	 * @param mixed $input    Raw values.
	 * @param array $defaults Existing row.
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
		if ( ! self::is_valid_date( $clean['prescription_date'] ) ) {
			$errors['prescription_date'] = __( 'Prescription date must be a valid date.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['follow_up_date'] && ! self::is_valid_date( $clean['follow_up_date'] ) ) {
			$errors['follow_up_date'] = __( 'Follow-up date must be a valid date.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['visit_id'] && is_null( ML_Visit_Repository::get( absint( $clean['visit_id'] ) ) ) ) {
			$errors['visit_id'] = __( 'The linked visit does not exist.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['doctor_user_id'] && ! get_userdata( absint( $clean['doctor_user_id'] ) ) ) {
			$errors['doctor_user_id'] = __( 'The doctor account does not exist.', 'ma-lumiere-clinic' );
		}

		return $errors;
	}

	/**
	 * Create a draft prescription. Number is allocated on finalize only.
	 *
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error Prescription ID.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$clean  = self::sanitize( $input );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_prescription_invalid', __( 'Prescription data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$now   = current_time( 'mysql' );
		$by    = get_current_user_id() ? get_current_user_id() : 0;
		$table = ML_Database::table( 'prescriptions' );

		$row = array(
			'patient_id'        => absint( $clean['patient_id'] ),
			'visit_id'          => '' !== (string) $clean['visit_id'] ? absint( $clean['visit_id'] ) : null,
			'doctor_user_id'    => '' !== (string) $clean['doctor_user_id'] ? absint( $clean['doctor_user_id'] ) : null,
			'prescription_date' => $clean['prescription_date'],
			'status'            => $clean['status'],
			'version'           => 1,
			'diagnosis'         => self::null_or( $clean['diagnosis'] ),
			'doctor_notes'      => self::null_or( $clean['doctor_notes'] ),
			'advice'            => self::null_or( $clean['advice'] ),
			'follow_up_date'    => '' !== (string) $clean['follow_up_date'] ? $clean['follow_up_date'] : null,
			'created_by'        => $by ? $by : null,
			'created_at'        => $now,
			'updated_at'        => $now,
		);

		$inserted = $wpdb->insert( $table, $row, self::formats( $row ) );
		if ( ! $inserted ) {
			return new WP_Error( 'ml_prescription_create_failed', __( 'Could not create the prescription.', 'ma-lumiere-clinic' ) );
		}
		$id = (int) $wpdb->insert_id;

		self::replace_items( $id, isset( $input['items'] ) ? $input['items'] : array() );

		ml_audit( 'prescription_created', 'prescription', $id, __( 'Prescription draft created.', 'ma-lumiere-clinic' ) );

		return $id;
	}

	/**
	 * Update a draft prescription and replace its item lines.
	 * Finalized prescriptions are immutable (use correct()).
	 *
	 * @param int   $id    Prescription ID.
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error
	 */
	public static function update( $id, array $input ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_prescription_not_found', __( 'Prescription not found.', 'ma-lumiere-clinic' ) );
		}
		if ( 'final' === (string) $existing['status'] ) {
			return new WP_Error( 'ml_prescription_final', __( 'This prescription is finalized. Create a correction to revise it.', 'ma-lumiere-clinic' ) );
		}

		$clean  = self::sanitize( $input, $existing );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_prescription_invalid', __( 'Prescription data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$by = get_current_user_id() ? get_current_user_id() : 0;
		$row = array(
			'patient_id'        => absint( $clean['patient_id'] ),
			'visit_id'          => '' !== (string) $clean['visit_id'] ? absint( $clean['visit_id'] ) : null,
			'doctor_user_id'    => '' !== (string) $clean['doctor_user_id'] ? absint( $clean['doctor_user_id'] ) : null,
			'prescription_date' => $clean['prescription_date'],
			'status'            => $clean['status'],
			'diagnosis'         => self::null_or( $clean['diagnosis'] ),
			'doctor_notes'      => self::null_or( $clean['doctor_notes'] ),
			'advice'            => self::null_or( $clean['advice'] ),
			'follow_up_date'    => '' !== (string) $clean['follow_up_date'] ? $clean['follow_up_date'] : null,
			'updated_by'        => $by ? $by : null,
			'updated_at'        => current_time( 'mysql' ),
		);

		$wpdb->update( ML_Database::table( 'prescriptions' ), $row, array( 'id' => $id ), self::formats( $row ), array( '%d' ) );

		if ( array_key_exists( 'items', $input ) ) {
			self::replace_items( $id, $input['items'] );
		}

		ml_audit( 'prescription_updated', 'prescription', $id, __( 'Prescription draft updated.', 'ma-lumiere-clinic' ) );

		return $id;
	}

	/**
	 * Mark a prescription final and allocate its number. Idempotent for an
	 * already-final prescription; refreshes never destroy approved content.
	 *
	 * @param int $id Prescription ID.
	 *
	 * @return array|WP_Error Prescription row on success.
	 */
	public static function finalize( $id ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_prescription_not_found', __( 'Prescription not found.', 'ma-lumiere-clinic' ) );
		}
		if ( 'final' === (string) $existing['status'] ) {
			return $existing;
		}

		$by = get_current_user_id() ? get_current_user_id() : 0;
		$now = current_time( 'mysql' );

		// A correction chain keeps the original number; every row allocated
		// here is the root of its own chain, retired numbers are never reused.
		$number = '';
		if ( (int) $existing['revision_of'] > 0 ) {
			$root  = self::get( (int) $existing['revision_of'] );
			while ( $root && (int) $root['revision_of'] > 0 ) {
				$root = self::get( (int) $root['revision_of'] );
			}
			$number = $root ? (string) $root['prescription_number'] : '';
		}
		if ( '' === $number || '0' === $number ) {
			$table = ML_Database::table( 'prescriptions' );
			for ( $attempt = 0; $attempt < 8; $attempt++ ) {
				$candidate = self::next_number();
				$taken = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE prescription_number = %s", $candidate ) );
				if ( $taken > 0 ) {
					continue;
				}
				$updated   = $wpdb->update(
					$table,
					array(
						'prescription_number' => $candidate,
						'status'              => 'final',
						'finalized_at'        => $now,
						'finalized_by'        => $by ? $by : null,
						'updated_at'          => $now,
					),
					array( 'id' => $id ),
					array( '%s', '%s', '%s', '%d', '%s' ),
					array( '%d' )
				);
				if ( false !== $updated ) {
					$number = $candidate;
					break;
				}
			}
			if ( '' === $number ) {
				return new WP_Error( 'ml_prescription_number', __( 'Could not allocate a prescription number. Please try again.', 'ma-lumiere-clinic' ) );
			}
		} else {
			$wpdb->update(
				ML_Database::table( 'prescriptions' ),
				array(
					'prescription_number' => $number,
					'status'              => 'final',
					'finalized_at'        => $now,
					'finalized_by'        => $by ? $by : null,
					'updated_at'          => $now,
				),
				array( 'id' => $id ),
				array( '%s', '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);
		}

		$fresh = self::get( $id );

		ml_audit( 'prescription_finalized', 'prescription', $id, sprintf( __( 'Prescription %s finalized (v%d).', 'ma-lumiere-clinic' ), $number, (int) $fresh['version'] ) );

		return $fresh;
	}

	/**
	 * Create a new draft version that revises an existing finalized row.
	 * Content is pre-filled from the parent; items start empty so the doctor
	 * explicitly records what changed.
	 *
	 * @param int $id Finalized prescription ID.
	 *
	 * @return int|WP_Error New prescription ID.
	 */
	public static function correct( $id ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_prescription_not_found', __( 'Prescription not found.', 'ma-lumiere-clinic' ) );
		}
		if ( 'final' !== (string) $existing['status'] ) {
			return new WP_Error( 'ml_prescription_not_final', __( 'Only a finalized prescription can be corrected.', 'ma-lumiere-clinic' ) );
		}

		$now  = current_time( 'mysql' );
		$by   = get_current_user_id() ? get_current_user_id() : 0;
		$next_version = (int) $existing['version'] + 1;

		$row = array(
			'patient_id'        => (int) $existing['patient_id'],
			'visit_id'          => ! empty( $existing['visit_id'] ) ? (int) $existing['visit_id'] : null,
			'doctor_user_id'    => ! empty( $existing['doctor_user_id'] ) ? (int) $existing['doctor_user_id'] : null,
			'prescription_date' => $existing['prescription_date'],
			'status'            => 'draft',
			'version'           => $next_version,
			'revision_of'       => (int) $existing['revision_of'] > 0 ? (int) $existing['revision_of'] : $id,
			'diagnosis'         => ! empty( $existing['diagnosis'] ) ? $existing['diagnosis'] : null,
			'doctor_notes'      => ! empty( $existing['doctor_notes'] ) ? $existing['doctor_notes'] : null,
			'advice'            => ! empty( $existing['advice'] ) ? $existing['advice'] : null,
			'follow_up_date'    => ! empty( $existing['follow_up_date'] ) ? $existing['follow_up_date'] : null,
			'created_by'        => $by ? $by : null,
			'created_at'        => $now,
			'updated_at'        => $now,
		);

		$inserted = $wpdb->insert( ML_Database::table( 'prescriptions' ), $row, self::formats( $row ) );
		if ( ! $inserted ) {
			return new WP_Error( 'ml_prescription_correct_failed', __( 'Could not create the correction.', 'ma-lumiere-clinic' ) );
		}
		$new_id = (int) $wpdb->insert_id;

		foreach ( self::items( $id ) as $item ) {
			$wpdb->insert(
				ML_Database::table( 'prescription_items' ),
				array(
					'prescription_id'    => $new_id,
					'medicine_id'        => ! empty( $item['medicine_id'] ) ? (int) $item['medicine_id'] : null,
					'medicine_name'      => $item['medicine_name'],
					'quantity'           => (int) $item['quantity'],
					'dosage'             => $item['dosage'],
					'frequency'          => $item['frequency'],
					'timing'             => $item['timing'],
					'duration'           => $item['duration'],
					'instructions'       => $item['instructions'],
					'sort_order'         => (int) $item['sort_order'],
					// Stock was dispensed against the parent, never the
					// correction, so the copy starts fully outstanding.
					'dispensed_quantity' => 0,
				),
				array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
			);
		}

		ml_audit( 'prescription_corrected', 'prescription', $new_id, sprintf( __( 'Correction created for %s (v%d).', 'ma-lumiere-clinic' ), $existing['prescription_number'], $next_version ) );

		return $new_id;
	}

	/**
	 * Fetch a prescription with its line items + revision chain summary.
	 *
	 * @param int $id Prescription ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$table = ML_Database::table( 'prescriptions' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		self::decorate( $row );
		return $row;
	}

	/**
	 * Prescription items ordered by sort_order.
	 *
	 * @param int $id Prescription ID.
	 *
	 * @return array
	 */
	public static function items( $id ) {
		global $wpdb;
		$table = ML_Database::table( 'prescription_items' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE prescription_id = %d ORDER BY sort_order ASC, id ASC", absint( $id ) ),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Replace all item lines for a prescription with the submitted set.
	 *
	 * Only ever runs against a draft (update() refuses once a prescription is
	 * final), so no dispensed quantity can be lost here. A line may be linked
	 * to a catalogue medicine; unlinked lines and zero-quantity lines are kept
	 * as free-text instructions and never affect stock.
	 *
	 * @param int   $id    Prescription ID.
	 * @param mixed $items Raw items list.
	 *
	 * @return void
	 */
	private static function replace_items( $id, $items ) {
		global $wpdb;
		$id    = absint( $id );
		$table = ML_Database::table( 'prescription_items' );
		$wpdb->delete( $table, array( 'prescription_id' => $id ), array( '%d' ) );

		if ( ! is_array( $items ) ) {
			return;
		}
		$order = 0;
		foreach ( array_values( $items ) as $item_raw ) {
			if ( ! is_array( $item_raw ) ) {
				continue;
			}
			$medicine = trim( sanitize_text_field( isset( $item_raw['medicine_name'] ) ? (string) $item_raw['medicine_name'] : '' ) );

			$medicine_id = isset( $item_raw['medicine_id'] ) ? absint( $item_raw['medicine_id'] ) : 0;
			$medicine_id = $medicine_id ? $medicine_id : null;
			$quantity    = isset( $item_raw['quantity'] ) ? (int) $item_raw['quantity'] : 0;
			$quantity    = $quantity > 0 ? $quantity : 0;

			if ( '' === $medicine && ! $medicine_id ) {
				continue; // Ignore blank item rows; never invent content.
			}

			/*
			 * A catalogue medicine supplies the printed name, so a line that
			 * only carries an id still reads correctly on the prescription.
			 */
			if ( '' === $medicine && $medicine_id ) {
				$linked = ML_Medicine_Repository::get( $medicine_id );
				if ( $linked ) {
					$medicine = (string) $linked['display_name'];
				} else {
					$medicine_id = null;
					$quantity    = 0;
				}
			}

			$wpdb->insert(
				$table,
				array(
					'prescription_id' => $id,
					'medicine_id'     => $medicine_id,
					'medicine_name'   => mb_substr( $medicine, 0, 200 ),
					'quantity'        => $quantity,
					'dosage'          => self::short_text( isset( $item_raw['dosage'] ) ? $item_raw['dosage'] : '' ),
					'frequency'       => self::short_text( isset( $item_raw['frequency'] ) ? $item_raw['frequency'] : '' ),
					'timing'          => self::short_text( isset( $item_raw['timing'] ) ? $item_raw['timing'] : '' ),
					'duration'        => self::short_text( isset( $item_raw['duration'] ) ? $item_raw['duration'] : '' ),
					'instructions'    => mb_substr( sanitize_textarea_field( isset( $item_raw['instructions'] ) ? (string) $item_raw['instructions'] : '' ), 0, 2000 ),
					'sort_order'      => $order,
				),
				array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
			);
			$order++;
		}
	}

	/**
	 * Filterable, paginated listing with patient info.
	 *
	 * @param array $args Filters: patient_id, visit_id, doctor, status, from, to, search, page, per_page.
	 *
	 * @return array
	 */
	public static function list( array $args = array() ) {
		global $wpdb;
		$table = ML_Database::table( 'prescriptions' );
		$p     = ML_Database::table( 'patients' );

		$page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page = min( 100, max( 1, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );
		$patient  = absint( isset( $args['patient_id'] ) ? $args['patient_id'] : 0 );
		$visit    = absint( isset( $args['visit_id'] ) ? $args['visit_id'] : 0 );
		$doctor   = absint( isset( $args['doctor'] ) ? $args['doctor'] : 0 );
		$status   = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$from     = isset( $args['from'] ) ? sanitize_text_field( (string) $args['from'] ) : '';
		$to       = isset( $args['to'] ) ? sanitize_text_field( (string) $args['to'] ) : '';
		$search   = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';

		if ( ! in_array( $status, self::statuses(), true ) ) {
			$status = '';
		}

		$where  = array( '1=1' );
		$params = array();
		if ( $patient ) {
			$where[]  = 'r.patient_id = %d';
			$params[] = $patient;
		}
		if ( $visit ) {
			$where[]  = 'r.visit_id = %d';
			$params[] = $visit;
		}
		if ( $doctor ) {
			$where[]  = 'r.doctor_user_id = %d';
			$params[] = $doctor;
		}
		if ( '' !== $status ) {
			$where[]  = 'r.status = %s';
			$params[] = $status;
		}
		if ( self::is_valid_date( $from ) ) {
			$where[]  = 'r.prescription_date >= %s';
			$params[] = $from;
		}
		if ( self::is_valid_date( $to ) ) {
			$where[]  = 'r.prescription_date <= %s';
			$params[] = $to;
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = "(p.first_name LIKE %s OR p.last_name LIKE %s OR CONCAT(p.first_name, ' ', p.last_name) LIKE %s OR p.patient_uid LIKE %s OR r.prescription_number LIKE %s)";
			foreach ( array( 1, 1, 1, 1, 1 ) as $i ) {
				$params[] = $like;
			}
		}

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} r LEFT JOIN {$p} p ON p.id = r.patient_id WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$pages     = (int) max( 1, ceil( $total / $per_page ) );
		$page      = min( $page, $pages );
		$offset    = ( $page - 1 ) * $per_page;

		$params[] = $per_page;
		$params[] = $offset;

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, p.first_name AS p_first_name, p.last_name AS p_last_name, p.patient_uid AS p_uid
				 FROM {$table} r LEFT JOIN {$p} p ON p.id = r.patient_id
				 WHERE {$where_sql} ORDER BY r.id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
	 * Revision chain for a prescription (root first, oldest to newest).
	 *
	 * @param int $id Prescription ID.
	 *
	 * @return array
	 */
	public static function revisions( $id ) {
		global $wpdb;
		$id = absint( $id );
		$root_id = 0;
		$test    = self::get( $id );
		if ( ! $test ) {
			return array();
		}
		if ( (int) $test['revision_of'] > 0 ) {
			$cursor = $test;
			while ( $cursor && (int) $cursor['revision_of'] > 0 ) {
				$cursor = self::get( (int) $cursor['revision_of'] );
			}
			$root_id = $cursor ? (int) $cursor['id'] : $id;
		} else {
			$root_id = $id;
		}

		$table = ML_Database::table( 'prescriptions' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d OR revision_of = %d ORDER BY version ASC, id ASC",
				$root_id,
				$root_id
			),
			ARRAY_A
		);
		foreach ( (array) $rows as &$row ) {
			self::decorate( $row );
		}
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Next global prescription number (ML-RX-######), never reused.
	 *
	 * @return string
	 */
	public static function next_number() {
		global $wpdb;
		$table = ML_Database::table( 'prescriptions' );
		$start = strlen( self::RX_PREFIX ) + 1;
		$max   = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT MAX(CAST(SUBSTRING(prescription_number, %d) AS UNSIGNED)) FROM ' . $table . ' WHERE prescription_number IS NOT NULL', $start )
		);
		return self::RX_PREFIX . str_pad( (string) ( $max + 1 ), self::RX_DIGITS, '0', STR_PAD_LEFT );
	}

	/**
	 * Decorate a row with patient name, item count, revisions and labels.
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
		$row['item_count'] = count( self::items( (int) $row['id'] ) );
		$row['is_final']   = ( 'final' === (string) $row['status'] );
	}

	/**
	 * Short text sanitizer for item columns.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	private static function short_text( $value ) {
		return mb_substr( sanitize_text_field( (string) $value ), 0, 100 );
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
		$ints    = array( 'id', 'patient_id', 'visit_id', 'doctor_user_id', 'version', 'revision_of', 'created_by', 'updated_by', 'finalized_by', 'email_sent' );
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
			case 'visit_id':
			case 'doctor_user_id':
				return '' === trim( $value ) ? '' : (string) absint( $value );
			case 'prescription_date':
			case 'follow_up_date':
				$value = sanitize_text_field( $value );
				return self::is_valid_date( $value ) ? $value : '';
			case 'diagnosis':
			case 'doctor_notes':
			case 'advice':
				return mb_substr( sanitize_textarea_field( $value ), 0, 5000 );
			case 'status':
				return in_array( $value, self::statuses(), true ) ? $value : 'draft';
			default:
				return sanitize_text_field( $value );
		}
	}
}