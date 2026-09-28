<?php
/**
 * Patient photo repository (before/after management + private serving).
 *
 * Files live OUTSIDE the web-accessible uploads tree (see
 * ML_Private_File_Manager). file_path stores a path relative to the
 * private root so the storage option can be relocated. Serving only
 * happens through an authorized handler with a signed token or a
 * capability + IDOR check.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Photo_Repository {

	/**
	 * Allowed photo types (before/after management).
	 *
	 * @return array<string>
	 */
	public static function types() {
		return array( 'before', 'after', 'other' );
	}

	/**
	 * Paginated list of photos, optionally scoped to one patient.
	 *
	 * @param array $args { page, per_page, patient_id, photo_type, search }.
	 *
	 * @return array{items:array,total:int,pages:int,page:int,per_page:int}
	 */
	public static function list( array $args = array() ) {
		global $wpdb;

		$page     = max( 1, isset( $args['page'] ) ? absint( $args['page'] ) : 1 );
		$per_page = isset( $args['per_page'] ) ? min( 100, max( 1, absint( $args['per_page'] ) ) ) : 20;
		$patient  = isset( $args['patient_id'] ) ? absint( $args['patient_id'] ) : 0;
		$type     = isset( $args['photo_type'] ) ? sanitize_key( $args['photo_type'] ) : '';
		$search   = isset( $args['search'] ) ? trim( (string) $args['search'] ) : '';

		$photos = ML_Database::table( 'patient_photos' );
		$pats   = ML_Database::table( 'patients' );

		$where  = array( '1=1' );
		$params = array();

		if ( $patient ) {
			$where[]  = 'ph.patient_id = %d';
			$params[] = $patient;
		}
		if ( $type && in_array( $type, self::types(), true ) ) {
			$where[]  = 'ph.photo_type = %s';
			$params[] = $type;
		}
		if ( '' !== $search ) {
			$where[]  = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_uid LIKE %s)";
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$base      = " FROM {$photos} ph JOIN {$pats} p ON p.id = ph.patient_id WHERE {$where_sql}";

		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( 'SELECT COUNT(*)' . $base, $params ) : 'SELECT COUNT(*)' . $base );
		$pages = max( 1, (int) ceil( $total / $per_page ) );
		$limit = $per_page;
		$offset = ( $page - 1 ) * $per_page;

		$sql = 'SELECT ph.*, p.first_name AS p_first_name, p.last_name AS p_last_name, p.patient_uid AS p_uid'
			. $base . ' ORDER BY ph.created_at DESC, ph.id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		$items = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return array(
			'items'    => is_array( $items ) ? $items : array(),
			'total'    => $total,
			'pages'    => $pages,
			'page'     => min( $page, $pages ),
			'per_page' => $per_page,
		);
	}

	/**
	 * Single photo row (with joined patient name).
	 *
	 * @param int $id Photo ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$photos = ML_Database::table( 'patient_photos' );
		$pats   = ML_Database::table( 'patients' );
		$row    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ph.*, p.first_name AS p_first_name, p.last_name AS p_last_name, p.patient_uid AS p_uid
				 FROM {$photos} ph JOIN {$pats} p ON p.id = ph.patient_id WHERE ph.id = %d",
				absint( $id )
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Upload + store one photo for a patient.
	 *
	 * Callers must gate nonce + capability + can_access_patient; this
	 * repository re-checks patient ownership as defense in depth.
	 *
	 * @param array $input { patient_id, file, photo_type, body_area, photo_date, notes, visit_id }.
	 *
	 * @return int|WP_Error New photo ID.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$patient_id = isset( $input['patient_id'] ) ? absint( $input['patient_id'] ) : 0;
		if ( ! $patient_id || ! ML_Patient_Repository::get( $patient_id ) ) {
			return new WP_Error( 'ml_photo_patient', __( 'A valid patient is required.', 'ma-lumiere-clinic' ) );
		}
		if ( ! ML_Security::can_access_patient( $patient_id ) ) {
			return new WP_Error( 'ml_photo_forbidden', __( 'You are not allowed to upload files for this patient.', 'ma-lumiere-clinic' ) );
		}

		$type = isset( $input['photo_type'] ) ? sanitize_key( $input['photo_type'] ) : 'before';
		if ( ! in_array( $type, self::types(), true ) ) {
			$type = 'before';
		}

		$visit_id = isset( $input['visit_id'] ) ? absint( $input['visit_id'] ) : 0;
		if ( $visit_id ) {
			$visit = ML_Visit_Repository::get( $visit_id );
			if ( ! $visit || (int) $visit['patient_id'] !== $patient_id ) {
				return new WP_Error( 'ml_photo_visit', __( 'That visit does not belong to this patient.', 'ma-lumiere-clinic' ) );
			}
		}

		$file = isset( $input['file'] ) && is_array( $input['file'] ) ? $input['file'] : array();
		$valid = ML_Private_File_Manager::validate_upload( $file );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$stored = self::store_file( $patient_id, $valid );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}

		$photo_date = isset( $input['photo_date'] ) ? sanitize_text_field( (string) $input['photo_date'] ) : '';
		if ( $photo_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $photo_date ) ) {
			$photo_date = '';
		}

		$inserted = $wpdb->insert(
			ML_Database::table( 'patient_photos' ),
			array(
				'patient_id' => $patient_id,
				'visit_id'   => $visit_id ? $visit_id : null,
				'photo_type' => $type,
				'body_area'  => isset( $input['body_area'] ) ? sanitize_text_field( (string) $input['body_area'] ) : '',
				'file_path'  => $stored,
				'photo_date' => $photo_date ? $photo_date : current_time( 'Y-m-d' ),
				'notes'      => isset( $input['notes'] ) ? sanitize_textarea_field( (string) $input['notes'] ) : '',
				'created_by' => get_current_user_id() ? get_current_user_id() : null,
				'created_at' => current_time( 'mysql' ),
			)
		);

		if ( ! $inserted ) {
			self::remove_file( $stored );
			return new WP_Error( 'ml_photo_save', __( 'The photo could not be saved.', 'ma-lumiere-clinic' ) );
		}

		$photo_id = (int) $wpdb->insert_id;

		ML_Audit_Log::record(
			'patient_photo_uploaded',
			'patient_photo',
			$photo_id,
			sprintf(
				__( 'Photo #%1$d (%2$s) uploaded for patient %3$s.', 'ma-lumiere-clinic' ),
				$photo_id,
				$type,
				(string) ML_Patient_Repository::get( $patient_id )['patient_uid']
			)
		);

		return $photo_id;
	}

	/**
	 * Delete a photo row + its private file.
	 *
	 * @param int $id Photo ID.
	 *
	 * @return true|WP_Error
	 */
	public static function delete( $id ) {
		$row = self::get( $id );
		if ( ! $row ) {
			return new WP_Error( 'ml_photo_not_found', __( 'Photo not found.', 'ma-lumiere-clinic' ) );
		}
		if ( ! ML_Security::can_access_patient( (int) $row['patient_id'] ) ) {
			return new WP_Error( 'ml_photo_forbidden', __( 'You are not allowed to manage this photo.', 'ma-lumiere-clinic' ) );
		}

		self::remove_file( (string) $row['file_path'] );

		global $wpdb;
		$deleted = $wpdb->delete( ML_Database::table( 'patient_photos' ), array( 'id' => absint( $id ) ) );
		if ( ! $deleted ) {
			return new WP_Error( 'ml_photo_delete', __( 'The photo could not be deleted.', 'ma-lumiere-clinic' ) );
		}

		ML_Audit_Log::record( 'patient_photo_deleted', 'patient_photo', absint( $id ), __( 'Photo deleted.', 'ma-lumiere-clinic' ) );
		return true;
	}

	/**
	 * Absolute filesystem path for a stored relative file_path, or false
	 * when the path escapes the private directory or is missing.
	 *
	 * @param string $relative Relative path under the private root.
	 *
	 * @return string|false
	 */
	public static function absolute_path( $relative ) {
		$relative = trim( (string) $relative );
		if ( '' === $relative || false !== strpos( $relative, '..' ) || 0 === strpos( $relative, '/' ) ) {
			return false;
		}
		$full = trailingslashit( ML_Private_File_Manager::private_dir() ) . $relative;
		$full = wp_normalize_path( $full );
		if ( ! ML_Private_File_Manager::is_private_path( $full ) ) {
			return false;
		}
		return $full;
	}

	/**
	 * Stream a photo file to the browser (authorized callers only).
	 *
	 * @param array $row Photo row from get().
	 *
	 * @return true|WP_Error
	 */
	public static function serve( array $row ) {
		$path = self::absolute_path( isset( $row['file_path'] ) ? (string) $row['file_path'] : '' );
		if ( ! $path || ! is_readable( $path ) ) {
			return new WP_Error( 'ml_photo_missing', __( 'The photo file is no longer available.', 'ma-lumiere-clinic' ) );
		}

		$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$mime = 'image/jpeg';
		foreach ( ML_Private_File_Manager::allowed_mime_types() as $type => $extension ) {
			if ( $extension === $ext || ( 'jpg' === $extension && 'jpeg' === $ext ) ) {
				$mime = $type;
				break;
			}
		}

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: inline; filename="patient-photo-' . absint( $row['id'] ) . '.' . ( 'jpg' === $ext ? 'jpg' : $ext ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: private, max-age=300' );

		ML_Audit_Log::record( 'patient_photo_viewed', 'patient_photo', absint( $row['id'] ), __( 'Photo served.', 'ma-lumiere-clinic' ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );
		return true;
	}

	/**
	 * Signed, expiring URL for <img> embedding (token-based serving).
	 *
	 * @param int  $photo_id Photo ID.
	 * @param int $ttl       Seconds until the token expires.
	 *
	 * @return string
	 */
	public static function serve_url( $photo_id, $ttl = 600 ) {
		$token = ML_Security::file_token( 'photo', absint( $photo_id ), $ttl );
		return esc_url_raw(
			add_query_arg(
				array(
					'rest_route' => '/' . ML_Rest_Api::NS . '/photos/' . absint( $photo_id ) . '/file',
					'token'      => $token,
				),
				rest_url()
			)
		);
	}

	/**
	 * Photo counts per patient (before/after/total), keyed by patient id.
	 *
	 * @param array $patient_ids Patient IDs to summarise.
	 *
	 * @return array<int,array{total:int,before:int,after:int}>
	 */
	public static function counts_for( array $patient_ids ) {
		global $wpdb;
		$counts = array();
		$ids    = array_values( array_unique( array_filter( array_map( 'absint', $patient_ids ) ) ) );
		if ( ! $ids ) {
			return $counts;
		}
		$table = ML_Database::table( 'patient_photos' );
		$in    = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT patient_id, photo_type, COUNT(*) AS c FROM {$table} WHERE patient_id IN ({$in}) GROUP BY patient_id, photo_type",
				$ids
			),
			ARRAY_A
		);
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$pid = (int) $row['patient_id'];
				if ( ! isset( $counts[ $pid ] ) ) {
					$counts[ $pid ] = array(
						'total'  => 0,
						'before' => 0,
						'after'  => 0,
					);
				}
				$type = (string) $row['photo_type'];
				$counts[ $pid ]['total'] += (int) $row['c'];
				if ( 'before' === $type || 'after' === $type ) {
					$counts[ $pid ][ $type ] += (int) $row['c'];
				}
			}
		}
		return $counts;
	}

	/**
	 * Move a validated upload into the patient's private folder.
	 *
	 * Returns the stored path RELATIVE to the private root.
	 *
	 * @param int   $patient_id Patient ID.
	 * @param array $valid      Validated file info from validate_upload().
	 *
	 * @return string|WP_Error
	 */
	private static function store_file( $patient_id, array $valid ) {
		$relative = 'photos/patient-' . absint( $patient_id ) . '/' . time() . '-' . wp_generate_password( 12, false, false ) . '.' . ( 'jpeg' === $valid['ext'] ? 'jpg' : $valid['ext'] );
		$dest     = trailingslashit( ML_Private_File_Manager::private_dir() ) . $relative;

		$dir = dirname( $dest );
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'ml_photo_dir', __( 'The photo folder could not be created.', 'ma-lumiere-clinic' ) );
		}
		ML_Private_File_Manager::write_blocker_files( $dir );

		$tmp = (string) $valid['tmp_path'];
		if ( is_uploaded_file( $tmp ) ) {
			$moved = move_uploaded_file( $tmp, $dest );
		} elseif ( 'cli' === PHP_SAPI ) {
			// CLI smoke tests / importers are not HTTP uploads; copy so the
			// caller's temp file is preserved.
			$moved = copy( $tmp, $dest );
		} else {
			return new WP_Error( 'ml_photo_not_uploaded', __( 'The upload could not be verified.', 'ma-lumiere-clinic' ) );
		}

		if ( ! $moved ) {
			return new WP_Error( 'ml_photo_move', __( 'The photo file could not be stored.', 'ma-lumiere-clinic' ) );
		}
		@chmod( $dest, 0644 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		return $relative;
	}

	/**
	 * Remove a stored private file (best effort).
	 *
	 * @param string $relative Relative path under the private root.
	 *
	 * @return void
	 */
	private static function remove_file( $relative ) {
		$path = self::absolute_path( $relative );
		if ( $path && is_readable( $path ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}
}
