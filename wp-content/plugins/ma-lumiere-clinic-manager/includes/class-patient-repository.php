<?php
/**
 * Patient repository: safe CRUD, validation, search, pagination and the
 * ML-PT-<nnnnnn> patient UID allocator.
 *
 * All writes go through dbDelta-managed tables and are audited. Unknown
 * fields are dropped; every value is cleaned at the field level before it
 * can reach a prepared query.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Patient_Repository {

	/**
	 * Patient UID prefix, e.g. ML-PT-000001.
	 *
	 * @var string
	 */
	const UID_PREFIX = 'ML-PT-';

	/**
	 * Numeric suffix width for patient UIDs.
	 *
	 * @var int
	 */
	const UID_DIGITS = 6;

	/**
	 * Allowed patient statuses.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'active', 'inactive' );
	}

	/**
	 * Allowed gender values.
	 *
	 * @return array<string>
	 */
	public static function genders() {
		return array( 'male', 'female', 'other' );
	}

	/**
	 * Persistable patient columns.
	 *
	 * @return array<string>
	 */
	public static function fields() {
		return array(
			'first_name',
			'last_name',
			'date_of_birth',
			'gender',
			'phone',
			'email',
			'address',
			'city',
			'state',
			'pincode',
			'emergency_contact_name',
			'emergency_contact_phone',
			'registration_date',
			'status',
			'wp_user_id',
		);
	}

	/**
	 * Clean a raw input array into a full, sanitized patient field set.
	 * Optionals that are blank are kept as '' so validation can decide;
	 * persistence later skips them (columns stay NULL in the DB).
	 *
	 * @param mixed $input    Raw values (POST body, REST params, ...).
	 * @param array $defaults Seeded values (existing row for updates).
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
	 * Validate a sanitized field set. Returns an associative map of
	 * field => message; an empty array means the set is valid.
	 *
	 * @param array $clean Sanitized values.
	 *
	 * @return array<string,string>
	 */
	public static function validate( array $clean ) {
		$errors = array();

		if ( '' === trim( (string) $clean['first_name'] ) ) {
			$errors['first_name'] = __( 'First name is required.', 'ma-lumiere-clinic' );
		}
		if ( '' === trim( (string) $clean['last_name'] ) ) {
			$errors['last_name'] = __( 'Last name is required.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['email'] && ! is_email( $clean['email'] ) ) {
			$errors['email'] = __( 'Please enter a valid email address.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['date_of_birth'] && ! self::is_valid_date( $clean['date_of_birth'] ) ) {
			$errors['date_of_birth'] = __( 'Date of birth must be a valid past date.', 'ma-lumiere-clinic' );
		} elseif ( '' !== (string) $clean['date_of_birth'] && strtotime( (string) $clean['date_of_birth'] ) > strtotime( 'tomorrow' ) ) {
			$errors['date_of_birth'] = __( 'Date of birth cannot be in the future.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['registration_date'] && ! self::is_valid_date( $clean['registration_date'] ) ) {
			$errors['registration_date'] = __( 'Registration date is not a valid date.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['gender'] && ! in_array( $clean['gender'], self::genders(), true ) ) {
			$errors['gender'] = __( 'Please choose a valid gender.', 'ma-lumiere-clinic' );
		}
		if ( ! in_array( $clean['status'], self::statuses(), true ) ) {
			$errors['status'] = __( 'Please choose a valid status.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['wp_user_id'] && ! get_userdata( absint( $clean['wp_user_id'] ) ) ) {
			$errors['wp_user_id'] = __( 'The linked WordPress user does not exist.', 'ma-lumiere-clinic' );
		}

		return $errors;
	}

	/**
	 * Create a patient record. Inserts with an allocated UID, sets
	 * timestamps and default registration date, then audits.
	 *
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error Patient ID on success.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$clean  = self::sanitize( $input );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_patient_invalid', __( 'Patient data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$now  = current_time( 'mysql' );
		$today = current_time( 'Y-m-d' );
		if ( '' === (string) $clean['registration_date'] ) {
			$clean['registration_date'] = $today;
		}

		$row = array(
			'patient_uid'     => '',
			'first_name'      => $clean['first_name'],
			'last_name'       => $clean['last_name'],
			'date_of_birth'   => '' !== (string) $clean['date_of_birth'] ? $clean['date_of_birth'] : null,
			'gender'          => '' !== (string) $clean['gender'] ? $clean['gender'] : null,
			'phone'           => '' !== (string) $clean['phone'] ? $clean['phone'] : null,
			'email'           => '' !== (string) $clean['email'] ? $clean['email'] : null,
			'address'         => '' !== (string) $clean['address'] ? $clean['address'] : null,
			'city'            => '' !== (string) $clean['city'] ? $clean['city'] : null,
			'state'           => '' !== (string) $clean['state'] ? $clean['state'] : null,
			'pincode'         => '' !== (string) $clean['pincode'] ? $clean['pincode'] : null,
			'emergency_contact_name'  => '' !== (string) $clean['emergency_contact_name'] ? $clean['emergency_contact_name'] : null,
			'emergency_contact_phone' => '' !== (string) $clean['emergency_contact_phone'] ? $clean['emergency_contact_phone'] : null,
			'registration_date' => $clean['registration_date'],
			'status'          => $clean['status'],
			'wp_user_id'      => '' !== (string) $clean['wp_user_id'] ? absint( $clean['wp_user_id'] ) : null,
			'created_at'      => $now,
			'updated_at'      => $now,
		);

		$table = ML_Database::table( 'patients' );
		$id    = 0;

		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$row['patient_uid'] = self::next_uid();
			$inserted = $wpdb->insert( $table, $row, self::formats( $row ) );
			if ( $inserted ) {
				$id = (int) $wpdb->insert_id;
				break;
			}
		}

		if ( ! $id ) {
			return new WP_Error( 'ml_patient_uid', __( 'Could not allocate a unique patient ID. Please try again.', 'ma-lumiere-clinic' ) );
		}

		$name = trim( $clean['first_name'] . ' ' . $clean['last_name'] );
		ml_audit( 'patient_created', 'patient', $id, sprintf( __( 'Patient created: %s (%s)', 'ma-lumiere-clinic' ), $name, $row['patient_uid'] ) );

		return $id;
	}

	/**
	 * Update a patient record. Blanking an optional field clears it to NULL.
	 *
	 * @param int   $id    Patient ID.
	 * @param array $input Raw values to change.
	 *
	 * @return int|WP_Error Patient ID on success.
	 */
	public static function update( $id, array $input ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_patient_not_found', __( 'Patient not found.', 'ma-lumiere-clinic' ) );
		}

		$clean  = self::sanitize( $input, $existing );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_patient_invalid', __( 'Patient data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$optional  = array( 'date_of_birth', 'gender', 'phone', 'email', 'address', 'city', 'state', 'pincode', 'emergency_contact_name', 'emergency_contact_phone', 'registration_date', 'wp_user_id' );
		$row       = array( 'updated_at' => current_time( 'mysql' ) );

		foreach ( self::fields() as $key ) {
			$is_required = ! in_array( $key, $optional, true );
			if ( array_key_exists( $key, $input ) ) {
				if ( '' === (string) $clean[ $key ] && in_array( $key, $optional, true ) ) {
					$row[ $key ] = null;
				} else {
					$row[ $key ] = 'wp_user_id' === $key ? absint( $clean[ $key ] ) : $clean[ $key ];
				}
			} elseif ( $is_required ) {
				$row[ $key ] = $clean[ $key ];
			}
		}

		$table = ML_Database::table( 'patients' );
		$wpdb->update( $table, $row, array( 'id' => $id ), self::formats( $row ), array( '%d' ) );

		// Verify the write actually reached the DB (no silent 0-row update on real change).
		$fresh = self::get( $id );
		if ( ! $fresh ) {
			return new WP_Error( 'ml_patient_update_failed', __( 'Patient update failed.', 'ma-lumiere-clinic' ) );
		}

		$changed = array();
		foreach ( self::fields() as $key ) {
			$before = is_null( $existing[ $key ] ) ? '' : (string) $existing[ $key ];
			$after  = is_null( $fresh[ $key ] ) ? '' : (string) $fresh[ $key ];
			if ( $before !== $after ) {
				$changed[] = $key;
			}
		}

		if ( $changed ) {
			$name = trim( (string) $fresh['first_name'] . ' ' . (string) $fresh['last_name'] );
			ml_audit( 'patient_updated', 'patient', $id, sprintf( __( 'Patient updated: %s (%s)', 'ma-lumiere-clinic' ), $name, $fresh['patient_uid'] ) );
		}

		return $id;
	}

	/**
	 * Permanently remove a patient. Restricted to ml_manage_clinic at the
	 * call site; related clinical records are deliberately left orphaned
	 * (no cascade) so history is never silently destroyed.
	 *
	 * @param int $id Patient ID.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete( $id ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_patient_not_found', __( 'Patient not found.', 'ma-lumiere-clinic' ) );
		}

		$deleted = $wpdb->delete( ML_Database::table( 'patients' ), array( 'id' => $id ), array( '%d' ) );

		if ( false === $deleted ) {
			return new WP_Error( 'ml_patient_delete_failed', __( 'Patient delete failed.', 'ma-lumiere-clinic' ) );
		}

		$name = trim( (string) $existing['first_name'] . ' ' . (string) $existing['last_name'] );
		ml_audit( 'patient_deleted', 'patient', $id, sprintf( __( 'Patient deleted: %s (%s)', 'ma-lumiere-clinic' ), $name, $existing['patient_uid'] ) );

		return true;
	}

	/**
	 * Fetch a single patient row.
	 *
	 * @param int $id Patient ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$table = ML_Database::table( 'patients' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Find a patient by UID.
	 *
	 * @param string $uid Patient UID.
	 *
	 * @return array|null
	 */
	public static function get_by_uid( $uid ) {
		global $wpdb;
		$table = ML_Database::table( 'patients' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE patient_uid = %s", sanitize_text_field( (string) $uid ) ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Find an existing patient by exact email or phone (booking flow reuses
	 * a recorded patient instead of duplicating one; returning patients must
	 * not leak any other details back to the caller).
	 *
	 * @param string $email Email.
	 * @param string $phone Phone.
	 *
	 * @return array|null Matching patient row (id, uid, name).
	 */
	public static function find_by_contact( $email, $phone ) {
		global $wpdb;
		$table = ML_Database::table( 'patients' );
		$email = '' !== (string) $email ? sanitize_email( (string) $email ) : '';
		$phone = '' !== (string) $phone ? preg_replace( '/[^0-9+]/', '', (string) $phone ) : '';

		$where  = array();
		$params = array();
		if ( '' !== $email ) {
			$where[]  = 'email = %s';
			$params[] = $email;
		}
		if ( '' !== $phone ) {
			$where[]  = 'phone = %s';
			$params[] = $phone;
		}
		if ( ! $where ) {
			return null;
		}
		$where_sql = implode( ' OR ', $where );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, patient_uid, first_name, last_name, email, phone, status
				 FROM {$table} WHERE {$where_sql} LIMIT 1",
				$params
			),
			ARRAY_A
		);
		if ( ! is_array( $row ) || 'active' !== (string) $row['status'] ) {
			return null;
		}
		return $row;
	}

	/**
	 * Lightweight term search for AJAX autocomplete-style lookups.
	 *
	 * @param string $term  Search term.
	 * @param int    $limit Max rows.
	 *
	 * @return array
	 */
	public static function search( $term, $limit = 10 ) {
		global $wpdb;
		$table = ML_Database::table( 'patients' );
		$like  = '%' . $wpdb->esc_like( (string) $term ) . '%';
		$limit = min( 50, max( 1, absint( $limit ) ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, patient_uid, first_name, last_name, gender, phone, email, status
				 FROM {$table}
				 WHERE first_name LIKE %s OR last_name LIKE %s OR patient_uid LIKE %s OR phone LIKE %s OR email LIKE %s
				 ORDER BY last_name ASC, first_name ASC
				 LIMIT %d",
				$like,
				$like,
				$like,
				$like,
				$like,
				$limit
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Paginated, filterable patient listing.
	 *
	 * @param array $args Search/filter/pagination controls.
	 *
	 * @return array{items:array,total:int,pages:int,page:int,per_page:int}
	 */
	public static function list( array $args = array() ) {
		global $wpdb;
		$table = ML_Database::table( 'patients' );

		$page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page = min( 100, max( 1, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );
		$search   = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$status   = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$orderby  = isset( $args['orderby'] ) ? sanitize_key( (string) $args['orderby'] ) : 'last_name';
		$order    = isset( $args['order'] ) ? strtoupper( sanitize_key( (string) $args['order'] ) ) : 'ASC';

		if ( ! in_array( $status, self::statuses(), true ) ) {
			$status = '';
		}
		$orderby = in_array( $orderby, array( 'id', 'patient_uid', 'last_name', 'first_name', 'registration_date', 'created_at' ), true ) ? $orderby : 'last_name';
		$order   = ( 'DESC' === $order ) ? 'DESC' : 'ASC';

		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(first_name LIKE %s OR last_name LIKE %s OR patient_uid LIKE %s OR phone LIKE %s OR email LIKE %s)';
			foreach ( array( 1, 1, 1, 1, 1 ) as $i ) {
				$params[] = $like;
			}
		}

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$pages = (int) max( 1, ceil( $total / $per_page ) );
		$page  = min( $page, $pages );
		$offset = ( $page - 1 ) * $per_page;

		$params[] = $per_page;
		$params[] = $offset;

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$params
			),
			ARRAY_A
		);

		return array(
			'items'    => is_array( $items ) ? $items : array(),
			'total'    => $total,
			'pages'    => $pages,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Aggregate counts for the patient record screen, keyed by domain so the
	 * caller can expose only what the current capability allows.
	 *
	 * @param int $id Patient ID.
	 *
	 * @return array
	 */
	public static function record_summary( $id ) {
		global $wpdb;
		$id = absint( $id );

		$count_for = static function ( $table, $extra = '1=1', array $params = array() ) use ( $id, $wpdb ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE patient_id = %d AND {$extra}", array_merge( array( $id ), $params ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		};

		$invoices = ML_Database::table( 'invoices' );
		$balance  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(balance_amount),0) FROM {$invoices} WHERE patient_id = %d", $id ) );
		$paid     = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM " . ML_Database::table( 'payments' ) . " WHERE patient_id = %d", $id ) );

		return array(
			'appointments' => $count_for( ML_Database::table( 'appointments' ) ),
			'visits'       => $count_for( ML_Database::table( 'visits' ) ),
			'prescriptions' => $count_for( ML_Database::table( 'prescriptions' ) ),
			'followups'    => $count_for( ML_Database::table( 'followups' ) ),
			'photos'       => $count_for( ML_Database::table( 'patient_photos' ) ),
			'invoices'     => $count_for( ML_Database::table( 'invoices' ) ),
			'open_invoices' => $count_for( ML_Database::table( 'invoices' ), "payment_status IN ('unpaid','partial')" ),
			'unpaid_balance' => $balance,
			'total_paid'   => $paid,
		);
	}

	/**
	 * Allocate the next patient UID (ML-PT-000001, ...). Uses MAX() so
	 * previously deleted numbers are not reused.
	 *
	 * @return string
	 */
	public static function next_uid() {
		global $wpdb;
		$table = ML_Database::table( 'patients' );
		$start = strlen( self::UID_PREFIX ) + 1;
		$max   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(CAST(SUBSTRING(patient_uid, %d) AS UNSIGNED)) FROM ' . $table, $start ) );
		return self::UID_PREFIX . str_pad( (string) ( $max + 1 ), self::UID_DIGITS, '0', STR_PAD_LEFT );
	}

	/**
	 * Clean one field to a safe scalar.
	 *
	 * @param string $key Column key.
	 * @param mixed  $value Raw value.
	 *
	 * @return string
	 */
	private static function clean_field( $key, $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		switch ( $key ) {
			case 'first_name':
			case 'last_name':
			case 'city':
			case 'state':
			case 'emergency_contact_name':
				return substr( sanitize_text_field( $value ), 0, 100 );
			case 'email':
				$email = sanitize_email( $value );
				if ( '' === $email && '' !== trim( $value ) ) {
					return substr( sanitize_text_field( trim( $value ) ), 0, 191 );
				}
				return $email;
			case 'phone':
			case 'emergency_contact_phone':
				$value = preg_replace( '/[^0-9+()\-.\s]/', '', $value );
				return substr( html_entity_decode( sanitize_text_field( $value ) ), 0, 20 );
			case 'pincode':
				$value = preg_replace( '/[^0-9A-Za-z\-]/', '', sanitize_text_field( $value ) );
				return substr( $value, 0, 20 );
			case 'address':
				return mb_substr( sanitize_textarea_field( $value ), 0, 5000 );
			case 'date_of_birth':
			case 'registration_date':
				$value = sanitize_text_field( $value );
				if ( self::is_valid_date( $value ) ) {
					return $value;
				}
				return ( '' === trim( $value ) ) ? '' : substr( $value, 0, 10 );
			case 'gender':
				if ( in_array( $value, self::genders(), true ) ) {
					return $value;
				}
				return ( '' === trim( $value ) ) ? '' : substr( sanitize_key( $value ), 0, 20 );
			case 'status':
				if ( in_array( $value, self::statuses(), true ) ) {
					return $value;
				}
				return ( '' === trim( $value ) ) ? 'active' : substr( sanitize_key( $value ), 0, 20 );
			case 'wp_user_id':
				return '' === trim( $value ) ? '' : (string) absint( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Strict Y-m-d date check.
	 *
	 * @param string $date Candidate date string.
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
	 * $wpdb format placeholders for a row (ids as %d, everything else %s).
	 *
	 * @param array $row Data row.
	 *
	 * @return array<string>
	 */
	private static function formats( array $row ) {
		$formats = array();
		foreach ( array_keys( $row ) as $key ) {
			$formats[ $key ] = in_array( $key, array( 'id', 'wp_user_id' ), true ) ? '%d' : '%s';
		}
		return $formats;
	}

	/**
	 * Derived age in years for display; empty when no birth date.
	 *
	 * @param array $patient Patient row.
	 *
	 * @return string
	 */
	public static function age( array $patient ) {
		if ( empty( $patient['date_of_birth'] ) ) {
			return '';
		}
		$dob = strtotime( (string) $patient['date_of_birth'] );
		if ( ! $dob ) {
			return '';
		}
		$diff = time() - $dob;
		return (string) max( 0, (int) floor( $diff / YEAR_IN_SECONDS ) );
	}
}