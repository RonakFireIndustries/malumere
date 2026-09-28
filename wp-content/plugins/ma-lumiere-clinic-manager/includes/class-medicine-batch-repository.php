<?php
/**
 * Medicine batch repository: one row per physical consignment (lot) received
 * into the clinic.
 *
 * Stock is never a single counter. Each batch owns its own
 * `quantity_available`, and a medicine's on-hand total is always the sum of
 * its live batches. This keeps expiry dates and unit costs attached to the
 * stock they belong to, and makes write-offs traceable to a specific lot.
 *
 * Quantities are only ever mutated by ML_Inventory_Service, which writes the
 * matching stock movement in the same request.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Medicine_Batch_Repository {

	/**
	 * Batch lifecycle statuses.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'active', 'quarantined', 'depleted', 'expired', 'disposed' );
	}

	/**
	 * Statuses whose units may still be dispensed.
	 *
	 * @return array<string>
	 */
	public static function dispensable_statuses() {
		return array( 'active' );
	}

	/**
	 * Status display labels.
	 *
	 * @return array<string,string>
	 */
	public static function status_labels() {
		return array(
			'active'      => __( 'In stock', 'ma-lumiere-clinic' ),
			'quarantined' => __( 'Quarantined', 'ma-lumiere-clinic' ),
			'depleted'    => __( 'Depleted', 'ma-lumiere-clinic' ),
			'expired'     => __( 'Expired', 'ma-lumiere-clinic' ),
			'disposed'    => __( 'Disposed', 'ma-lumiere-clinic' ),
		);
	}

	/**
	 * Persistable batch columns.
	 *
	 * @return array<string>
	 */
	public static function fields() {
		return array(
			'medicine_id',
			'batch_number',
			'expiry_date',
			'supplier',
			'storage_location',
			'quantity_received',
			'unit_cost',
			'received_date',
			'notes',
		);
	}

	/**
	 * Sanitize raw input into a batch field set.
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
			$raw          = array_key_exists( $key, $i ) ? $i[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
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

		$medicine = ML_Medicine_Repository::get( absint( $clean['medicine_id'] ) );
		if ( ! $medicine ) {
			$errors['medicine_id'] = __( 'Please choose a valid medicine.', 'ma-lumiere-clinic' );
		}
		if ( (int) $clean['quantity_received'] <= 0 ) {
			$errors['quantity_received'] = __( 'Received quantity must be greater than zero.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['expiry_date'] && ! ml_is_date( (string) $clean['expiry_date'] ) ) {
			$errors['expiry_date'] = __( 'Expiry date must be a valid date.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['received_date'] && ! ml_is_date( (string) $clean['received_date'] ) ) {
			$errors['received_date'] = __( 'Received date must be a valid date.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['expiry_date'] && '' !== (string) $clean['received_date'] && (string) $clean['expiry_date'] < (string) $clean['received_date'] ) {
			$errors['expiry_date'] = __( 'Expiry date cannot be before the received date.', 'ma-lumiere-clinic' );
		}
		if ( (float) $clean['unit_cost'] < 0 ) {
			$errors['unit_cost'] = __( 'Unit cost cannot be negative.', 'ma-lumiere-clinic' );
		}

		return $errors;
	}

	/**
	 * Create a batch. Quantity is seeded by the caller through
	 * ML_Inventory_Service::receive() so that an opening stock movement is
	 * always written; direct use is reserved for seeding.
	 *
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error Batch ID.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$clean  = self::sanitize( $input );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_batch_invalid', __( 'Batch data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$qty = absint( $clean['quantity_received'] );

		$now  = current_time( 'mysql' );
		$by   = get_current_user_id() ? get_current_user_id() : 0;
		$row  = array(
			'medicine_id'        => absint( $clean['medicine_id'] ),
			'batch_number'       => self::null_or( $clean['batch_number'] ),
			'expiry_date'        => '' !== (string) $clean['expiry_date'] ? (string) $clean['expiry_date'] : null,
			'supplier'           => self::null_or( $clean['supplier'] ),
			'storage_location'   => self::null_or( $clean['storage_location'] ),
			'quantity_received'  => $qty,
			'quantity_available' => $qty,
			'unit_cost'          => (float) $clean['unit_cost'],
			'received_date'      => '' !== (string) $clean['received_date'] ? (string) $clean['received_date'] : current_time( 'Y-m-d' ),
			'status'             => 'active',
			'notes'              => self::null_or( $clean['notes'] ),
			'created_by'         => $by ? $by : null,
			'created_at'         => $now,
			'updated_at'         => $now,
		);

		$inserted = $wpdb->insert( ML_Database::table( 'medicine_batches' ), $row, self::formats( $row ) );
		if ( ! $inserted ) {
			return new WP_Error( 'ml_batch_create_failed', __( 'Could not save the batch.', 'ma-lumiere-clinic' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update batch metadata. Quantity is intentionally not editable here —
	 * stock changes only through a movement so the ledger stays complete.
	 *
	 * @param int   $id    Batch ID.
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error
	 */
	public static function update( $id, array $input ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_batch_not_found', __( 'Batch not found.', 'ma-lumiere-clinic' ) );
		}

		$clean  = self::sanitize( $input, $existing );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_batch_invalid', __( 'Batch data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$row = array(
			'batch_number'     => self::null_or( $clean['batch_number'] ),
			'expiry_date'      => '' !== (string) $clean['expiry_date'] ? (string) $clean['expiry_date'] : null,
			'supplier'         => self::null_or( $clean['supplier'] ),
			'storage_location' => self::null_or( $clean['storage_location'] ),
			'unit_cost'        => (float) $clean['unit_cost'],
			'received_date'    => '' !== (string) $clean['received_date'] ? (string) $clean['received_date'] : (string) $existing['received_date'],
			'notes'            => self::null_or( $clean['notes'] ),
			'updated_at'       => current_time( 'mysql' ),
		);

		$wpdb->update( ML_Database::table( 'medicine_batches' ), $row, array( 'id' => $id ), self::formats( $row ), array( '%d' ) );

		ml_audit( 'medicine_batch_updated', 'medicine_batch', $id, __( 'Batch details updated.', 'ma-lumiere-clinic' ) );

		return $id;
	}

	/**
	 * Fetch a batch decorated with its medicine label and expiry state.
	 *
	 * @param int $id Batch ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$table = ML_Database::table( 'medicine_batches' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		self::decorate( $row );
		return $row;
	}

	/**
	 * List batches, joined to the medicine for display and filtering.
	 *
	 * @param array $args Filters: medicine_id, status, expiring_before, only_available, search, page, per_page.
	 *
	 * @return array
	 */
	public static function list( array $args = array() ) {
		global $wpdb;

		$table       = ML_Database::table( 'medicine_batches' );
		$medicines   = ML_Database::table( 'medicines' );
		$page        = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page    = min( 200, max( 1, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );
		$medicine_id = absint( isset( $args['medicine_id'] ) ? $args['medicine_id'] : 0 );
		$status      = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$expiring    = isset( $args['expiring_before'] ) ? sanitize_text_field( (string) $args['expiring_before'] ) : '';
		$available   = ! empty( $args['only_available'] );
		$search      = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';

		if ( ! in_array( $status, self::statuses(), true ) ) {
			$status = '';
		}

		$where  = array( '1=1' );
		$params = array();

		if ( $medicine_id ) {
			$where[]  = 'b.medicine_id = %d';
			$params[] = $medicine_id;
		}
		if ( '' !== $status ) {
			$where[]  = 'b.status = %s';
			$params[] = $status;
		}
		if ( $available ) {
			$where[] = 'b.quantity_available > 0';
		}
		if ( ml_is_date( $expiring ) ) {
			$where[]  = '( b.expiry_date IS NOT NULL AND b.expiry_date <= %s )';
			$params[] = $expiring;
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(m.name LIKE %s OR m.generic_name LIKE %s OR m.sku LIKE %s OR b.batch_number LIKE %s OR b.supplier LIKE %s)';
			foreach ( array( 1, 1, 1, 1, 1 ) as $i ) {
				$params[] = $like;
				unset( $i );
			}
		}

		$where_sql = implode( ' AND ', $where );
		$join_sql  = "FROM {$table} b LEFT JOIN {$medicines} m ON m.id = b.medicine_id";

		$count_sql = "SELECT COUNT(*) {$join_sql} WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$pages     = (int) max( 1, ceil( $total / $per_page ) );
		$page      = min( $page, $pages );
		$offset    = ( $page - 1 ) * $per_page;

		$list_params   = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;

		// FEFO: soonest expiry first, undated lots last.
		$sql  = "SELECT b.*, m.name AS m_name, m.generic_name AS m_generic_name, m.strength AS m_strength, m.form AS m_form, m.sku AS m_sku
				 {$join_sql} WHERE {$where_sql}
				 ORDER BY ( b.expiry_date IS NULL ) ASC, b.expiry_date ASC, b.id ASC
				 LIMIT %d OFFSET %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $list_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$items = is_array( $rows ) ? $rows : array();
		foreach ( $items as &$item ) {
			self::decorate( $item );
		}
		unset( $item );

		return array(
			'items'    => $items,
			'total'    => $total,
			'pages'    => $pages,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Live batches of a medicine in FEFO order, ready to be drawn from.
	 *
	 * Expired units and anything past its expiry date are excluded so a
	 * dispense can never hand out a lot that should have been quarantined.
	 *
	 * @param int $medicine_id Medicine ID.
	 *
	 * @return array
	 */
	public static function dispensable( $medicine_id ) {
		global $wpdb;

		$table = ML_Database::table( 'medicine_batches' );
		$today = current_time( 'Y-m-d' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE medicine_id = %d
				   AND status = 'active'
				   AND quantity_available > 0
				   AND ( expiry_date IS NULL OR expiry_date >= %s )
				 ORDER BY ( expiry_date IS NULL ) ASC, expiry_date ASC, id ASC",
				absint( $medicine_id ),
				$today
			),
			ARRAY_A
		);

		$items = is_array( $rows ) ? $rows : array();
		foreach ( $items as &$item ) {
			self::decorate( $item );
		}
		unset( $item );
		return $items;
	}

	/**
	 * Plan a FEFO draw of N units without changing anything. Returns the
	 * batches that would be consumed and any shortfall.
	 *
	 * @param int $medicine_id Medicine ID.
	 * @param int $quantity     Units requested.
	 *
	 * @return array ['allocations' => [ ['batch_id','quantity'], ... ], 'shortfall' => int, 'available' => int]
	 */
	public static function plan_draw( $medicine_id, $quantity ) {
		$medicine_id = absint( $medicine_id );
		$quantity    = absint( $quantity );

		$allocations = array();
		$remaining   = $quantity;
		$available   = 0;

		foreach ( self::dispensable( $medicine_id ) as $batch ) {
			$available += (int) $batch['quantity_available'];
			if ( $remaining <= 0 ) {
				continue;
			}
			$take = min( $remaining, (int) $batch['quantity_available'] );
			if ( $take > 0 ) {
				$allocations[] = array(
					'batch_id' => (int) $batch['id'],
					'quantity' => $take,
					'unit_cost' => (float) $batch['unit_cost'],
				);
				$remaining    -= $take;
			}
		}

		return array(
			'allocations' => $allocations,
			'shortfall'   => max( 0, $remaining ),
			'available'   => $available,
		);
	}

	/**
	 * Batches expiring on or before a date, soonest first.
	 *
	 * @param string $before Cut-off date (Y-m-d).
	 * @param int    $limit  Maximum rows.
	 *
	 * @return array
	 */
	public static function expiring( $before, $limit = 50 ) {
		$cutoff = ml_is_date( $before ) ? $before : gmdate( 'Y-m-d', time() + ( 90 * DAY_IN_SECONDS ) );
		$result = self::list(
			array(
				'expiring_before' => $cutoff,
				'only_available'  => true,
				'per_page'        => min( 500, max( 1, absint( $limit ) ) ),
			)
		);
		return $result['items'];
	}

	/**
	 * Soonest expiry date among a medicine's live batches.
	 *
	 * @param int $medicine_id Medicine ID.
	 *
	 * @return string Empty string when no dated batch remains.
	 */
	public static function next_expiry( $medicine_id ) {
		global $wpdb;

		$table = ML_Database::table( 'medicine_batches' );
		$date  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(expiry_date) FROM {$table} WHERE medicine_id = %d AND status = 'active' AND quantity_available > 0 AND expiry_date IS NOT NULL",
				absint( $medicine_id )
			)
		);
		return $date ? (string) $date : '';
	}

	/**
	 * Batches for a medicine, FEFO ordered (all statuses).
	 *
	 * @param int $medicine_id Medicine ID.
	 *
	 * @return array
	 */
	public static function for_medicine( $medicine_id ) {
		$result = self::list(
			array(
				'medicine_id' => absint( $medicine_id ),
				'per_page'    => 100,
			)
		);
		return $result['items'];
	}

	/**
	 * Apply a signed quantity change to a batch under an optimistic guard.
	 *
	 * The UPDATE only lands when the stored quantity still matches what the
	 * caller read, so two concurrent dispenses of the last unit cannot both
	 * succeed. Returns the new balance, or WP_Error on conflict/overdraw.
	 *
	 * @param int   $batch_id Batch ID.
	 * @param int   $delta    Signed change (negative to remove stock).
	 * @param int   $expected Quantity the caller believed was on hand.
	 *
	 * @return int|WP_Error New quantity available.
	 */
	public static function apply_delta( $batch_id, $delta, $expected ) {
		global $wpdb;

		$batch_id = absint( $batch_id );
		$delta    = (int) $delta;
		$expected = (int) $expected;

		$table = ML_Database::table( 'medicine_batches' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $batch_id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return new WP_Error( 'ml_batch_not_found', __( 'Batch not found.', 'ma-lumiere-clinic' ) );
		}

		$current = (int) $row['quantity_available'];
		if ( $current !== $expected ) {
			return new WP_Error( 'ml_batch_conflict', __( 'This batch changed while you were working. Please reload and try again.', 'ma-lumiere-clinic' ) );
		}

		$new = $current + $delta;
		if ( $new < 0 ) {
			return new WP_Error( 'ml_batch_insufficient', __( 'Not enough stock in this batch.', 'ma-lumiere-clinic' ), array( 'available' => $current ) );
		}

		$update = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET quantity_available = %d, updated_at = %s WHERE id = %d AND quantity_available = %d",
				$new,
				current_time( 'mysql' ),
				$batch_id,
				$expected
			)
		);

		if ( false === $update ) {
			return new WP_Error( 'ml_batch_update_failed', __( 'Could not update the batch.', 'ma-lumiere-clinic' ) );
		}
		if ( 0 === (int) $update ) {
			return new WP_Error( 'ml_batch_conflict', __( 'This batch changed while you were working. Please reload and try again.', 'ma-lumiere-clinic' ) );
		}

		// A lot that has run out is retired so it stops appearing as a draw
		// candidate, but its history is preserved.
		if ( 0 === $new && $current > 0 ) {
			$wpdb->update(
				$table,
				array( 'status' => 'depleted', 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $batch_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		} elseif ( $new > 0 && 0 === $current && 'depleted' === (string) $row['status'] ) {
			/*
			 * Stock coming back into an empty lot (a return, or an adjustment
			 * that undoes a correction) puts it back in circulation. Quarantine,
			 * expiry and disposal are deliberately left alone because they are
			 * deliberate, human decisions.
			 */
			$wpdb->update(
				$table,
				array( 'status' => 'active', 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $batch_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		return $new;
	}

	/**
	 * Delete a batch row, but only when nothing has ever moved through it.
	 *
	 * Used to unwind a receive whose opening ledger entry could not be
	 * written, so a stock-less lot is never left behind.
	 *
	 * @param int $batch_id Batch ID.
	 *
	 * @return bool True when the row was removed.
	 */
	public static function discard_if_unledgered( $batch_id ) {
		global $wpdb;

		$batch_id = absint( $batch_id );
		if ( ! $batch_id ) {
			return false;
		}

		$movements = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . ML_Database::table( 'stock_movements' ) . ' WHERE batch_id = %d',
				$batch_id
			)
		);
		if ( $movements > 0 ) {
			return false;
		}

		$deleted = $wpdb->delete( ML_Database::table( 'medicine_batches' ), array( 'id' => $batch_id ), array( '%d' ) );

		return (bool) $deleted;
	}

	/**
	 * Set a batch status, refusing to reactivate a lot that is past expiry.
	 *
	 * @param int    $id     Batch ID.
	 * @param string $status Target status.
	 *
	 * @return array|WP_Error Updated batch row.
	 */
	public static function set_status( $id, $status ) {
		global $wpdb;

		$id     = absint( $id );
		$status = sanitize_key( (string) $status );

		$batch = self::get( $id );
		if ( ! $batch ) {
			return new WP_Error( 'ml_batch_not_found', __( 'Batch not found.', 'ma-lumiere-clinic' ) );
		}
		if ( ! in_array( $status, self::statuses(), true ) ) {
			return new WP_Error( 'ml_batch_status', __( 'Invalid batch status.', 'ma-lumiere-clinic' ) );
		}
		if ( 'active' === $status && ! empty( $batch['expiry_date'] ) && (string) $batch['expiry_date'] < current_time( 'Y-m-d' ) ) {
			return new WP_Error( 'ml_batch_expired', __( 'An expired batch cannot be returned to stock.', 'ma-lumiere-clinic' ) );
		}

		$wpdb->update(
			ML_Database::table( 'medicine_batches' ),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$fresh = self::get( $id );
		ml_audit( 'medicine_batch_status', 'medicine_batch', $id, sprintf( __( 'Batch marked %s.', 'ma-lumiere-clinic' ), $status ) );

		return $fresh;
	}

	/**
	 * Attach derived display fields to a batch row.
	 *
	 * @param array $row Row by reference.
	 *
	 * @return void
	 */
	private static function decorate( array &$row ) {
		$row['quantity_received']  = (int) $row['quantity_received'];
		$row['quantity_available'] = (int) $row['quantity_available'];
		$row['unit_cost']          = (float) $row['unit_cost'];
		$row['stock_value']        = $row['quantity_available'] * $row['unit_cost'];

		if ( isset( $row['m_name'] ) ) {
			$row['medicine_label'] = ML_Medicine_Repository::display_name(
				array(
					'name'     => (string) $row['m_name'],
					'strength' => (string) $row['m_strength'],
				)
			);
		} else {
			$row['medicine_label'] = '';
		}

		$row['expiry_state']  = self::expiry_state( $row );
		$row['days_to_expiry'] = ( ! empty( $row['expiry_date'] ) )
			? (int) floor( ( strtotime( (string) $row['expiry_date'] ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS )
			: null;
	}

	/**
	 * Classify a batch's expiry for badge display.
	 *
	 * @param array $row Batch row.
	 *
	 * @return string 'none'|'expired'|'critical'|'warning'
	 */
	public static function expiry_state( array $row ) {
		if ( empty( $row['expiry_date'] ) ) {
			return 'none';
		}
		$expiry = (string) $row['expiry_date'];
		$today  = current_time( 'Y-m-d' );

		if ( $expiry < $today ) {
			return 'expired';
		}
		$days = (int) floor( ( strtotime( $expiry ) - strtotime( $today ) ) / DAY_IN_SECONDS );
		if ( $days <= 30 ) {
			return 'critical';
		}
		if ( $days <= 90 ) {
			return 'warning';
		}
		return 'none';
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
			case 'medicine_id':
				return (string) absint( $value );
			case 'batch_number':
			case 'storage_location':
				return mb_substr( sanitize_text_field( $value ), 0, 100 );
			case 'supplier':
				return mb_substr( sanitize_text_field( $value ), 0, 200 );
			case 'expiry_date':
			case 'received_date':
				$value = sanitize_text_field( $value );
				return ml_is_date( $value ) ? $value : '';
			case 'quantity_received':
				return (string) absint( $value );
			case 'unit_cost':
				$number = (float) $value;
				return $number < 0 ? '0' : number_format( $number, 2, '.', '' );
			case 'notes':
				return mb_substr( sanitize_textarea_field( $value ), 0, 2000 );
			default:
				return sanitize_text_field( $value );
		}
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
	 * $wpdb formats.
	 *
	 * @param array $row Row.
	 *
	 * @return array
	 */
	private static function formats( array $row ) {
		$formats = array();
		$ints    = array( 'id', 'medicine_id', 'quantity_received', 'quantity_available', 'created_by' );
		foreach ( array_keys( $row ) as $key ) {
			$formats[ $key ] = in_array( $key, $ints, true ) ? '%d' : '%s';
		}
		return $formats;
	}
}
