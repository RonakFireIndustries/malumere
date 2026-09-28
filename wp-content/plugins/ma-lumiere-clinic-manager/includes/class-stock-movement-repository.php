<?php
/**
 * Stock movement repository: the append-only ledger behind every quantity
 * change in the pharmacy.
 *
 * Design rules enforced here:
 *
 *  - Movements are written **only** by ML_Inventory_Service, which pairs each
 *    row with a guarded batch update in the same request. This class never
 *    changes a quantity itself; it is the record of what changed.
 *  - `quantity` is signed (positive = stock in, negative = stock out) and
 *    `balance_after` is the batch quantity immediately afterwards, so a single
 *    row always explains the resulting state without re-reading the batch.
 *  - There is no update or delete path. Corrections are made by posting an
 *    opposing movement, which keeps the history auditable.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Stock_Movement_Repository {

	/**
	 * Movement types and their effect on stock.
	 *
	 * direction: 1 = stock in, -1 = stock out, 0 = metadata only.
	 *
	 * @return array<string,array>
	 */
	public static function types() {
		return array(
			'purchase'    => array(
				'direction' => 1,
				'label'     => __( 'Stock received', 'ma-lumiere-clinic' ),
			),
			'dispense'    => array(
				'direction' => -1,
				'label'     => __( 'Dispensed', 'ma-lumiere-clinic' ),
			),
			'return'      => array(
				'direction' => 1,
				'label'     => __( 'Patient return', 'ma-lumiere-clinic' ),
			),
			'wastage'     => array(
				'direction' => -1,
				'label'     => __( 'Wastage / damage', 'ma-lumiere-clinic' ),
			),
			'expired'     => array(
				'direction' => -1,
				'label'     => __( 'Expired write-off', 'ma-lumiere-clinic' ),
			),
			'adjustment'  => array(
				'direction' => 0,
				'label'     => __( 'Stock adjustment', 'ma-lumiere-clinic' ),
			),
		);
	}

	/**
	 * Movement type keys.
	 *
	 * @return array<string>
	 */
	public static function type_keys() {
		return array_keys( self::types() );
	}

	/**
	 * Display label for a movement type.
	 *
	 * @param string $type Movement type.
	 *
	 * @return string
	 */
	public static function type_label( $type ) {
		$types = self::types();
		return isset( $types[ $type ] ) ? $types[ $type ]['label'] : (string) $type;
	}

	/**
	 * Badge tone for a movement type.
	 *
	 * @param string $type Movement type.
	 *
	 * @return string
	 */
	public static function type_tone( $type ) {
		switch ( $type ) {
			case 'purchase':
			case 'return':
				return 'success';
			case 'dispense':
			case 'wastage':
			case 'expired':
				return 'danger';
			default:
				return 'info';
		}
	}

	/**
	 * Append a movement to the ledger.
	 *
	 * @param array $args medicine_id, batch_id, movement_type, quantity, balance_after, unit_cost, reference_type, reference_id, notes.
	 *
	 * @return int|WP_Error Movement ID.
	 */
	public static function record( array $args ) {
		global $wpdb;

		$type = isset( $args['movement_type'] ) ? sanitize_key( (string) $args['movement_type'] ) : '';
		if ( ! in_array( $type, self::type_keys(), true ) ) {
			return new WP_Error( 'ml_movement_type', __( 'Invalid stock movement type.', 'ma-lumiere-clinic' ) );
		}
		if ( ! ML_Medicine_Repository::get( absint( $args['medicine_id'] ) ) ) {
			return new WP_Error( 'ml_movement_medicine', __( 'Unknown medicine for this stock movement.', 'ma-lumiere-clinic' ) );
		}

		$by  = get_current_user_id() ? get_current_user_id() : 0;
		$qty = (int) $args['quantity'];

		$row = array(
			'medicine_id'    => absint( $args['medicine_id'] ),
			'batch_id'       => ! empty( $args['batch_id'] ) ? absint( $args['batch_id'] ) : null,
			'movement_type'  => $type,
			'quantity'       => $qty,
			'balance_after'  => isset( $args['balance_after'] ) ? (int) $args['balance_after'] : 0,
			'unit_cost'      => isset( $args['unit_cost'] ) ? (float) $args['unit_cost'] : 0,
			'reference_type' => ! empty( $args['reference_type'] ) ? sanitize_key( (string) $args['reference_type'] ) : null,
			'reference_id'   => ! empty( $args['reference_id'] ) ? absint( $args['reference_id'] ) : null,
			'notes'          => isset( $args['notes'] ) && '' !== (string) $args['notes'] ? mb_substr( sanitize_textarea_field( (string) $args['notes'] ), 0, 2000 ) : null,
			'created_by'     => $by ? $by : null,
			'created_at'     => current_time( 'mysql' ),
		);

		$inserted = $wpdb->insert( ML_Database::table( 'stock_movements' ), $row, self::formats( $row ) );
		if ( ! $inserted ) {
			return new WP_Error( 'ml_movement_failed', __( 'Could not record the stock movement.', 'ma-lumiere-clinic' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Filterable, paginated ledger joined to medicines/batches.
	 *
	 * @param array $args Filters: medicine_id, batch_id, movement_type, reference_type, reference_id, from, to, search, page, per_page.
	 *
	 * @return array
	 */
	public static function list( array $args = array() ) {
		global $wpdb;

		$table     = ML_Database::table( 'stock_movements' );
		$medicines = ML_Database::table( 'medicines' );
		$batches   = ML_Database::table( 'medicine_batches' );
		$presc     = ML_Database::table( 'prescriptions' );

		$page        = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page    = min( 200, max( 1, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );
		$medicine_id = absint( isset( $args['medicine_id'] ) ? $args['medicine_id'] : 0 );
		$batch_id    = absint( isset( $args['batch_id'] ) ? $args['batch_id'] : 0 );
		$type        = isset( $args['movement_type'] ) ? sanitize_key( (string) $args['movement_type'] ) : '';
		$ref_type    = isset( $args['reference_type'] ) ? sanitize_key( (string) $args['reference_type'] ) : '';
		$ref_id      = absint( isset( $args['reference_id'] ) ? $args['reference_id'] : 0 );
		$from        = isset( $args['from'] ) ? sanitize_text_field( (string) $args['from'] ) : '';
		$to          = isset( $args['to'] ) ? sanitize_text_field( (string) $args['to'] ) : '';
		$search      = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';

		if ( ! in_array( $type, self::type_keys(), true ) ) {
			$type = '';
		}

		$where  = array( '1=1' );
		$params = array();

		if ( $medicine_id ) {
			$where[]  = 's.medicine_id = %d';
			$params[] = $medicine_id;
		}
		if ( $batch_id ) {
			$where[]  = 's.batch_id = %d';
			$params[] = $batch_id;
		}
		if ( '' !== $type ) {
			$where[]  = 's.movement_type = %s';
			$params[] = $type;
		}
		if ( '' !== $ref_type ) {
			$where[]  = 's.reference_type = %s';
			$params[] = $ref_type;
		}
		if ( $ref_id ) {
			$where[]  = 's.reference_id = %d';
			$params[] = $ref_id;
		}
		if ( ml_is_date( $from ) ) {
			$where[]  = 's.created_at >= %s';
			$params[] = $from . ' 00:00:00';
		}
		if ( ml_is_date( $to ) ) {
			$where[]  = 's.created_at <= %s';
			$params[] = $to . ' 23:59:59';
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(m.name LIKE %s OR m.generic_name LIKE %s OR m.sku LIKE %s OR b.batch_number LIKE %s OR s.notes LIKE %s)';
			foreach ( array( 1, 1, 1, 1, 1 ) as $i ) {
				$params[] = $like;
				unset( $i );
			}
		}

		$where_sql = implode( ' AND ', $where );
		$join_sql  = "FROM {$table} s
			LEFT JOIN {$medicines} m ON m.id = s.medicine_id
			LEFT JOIN {$batches} b ON b.id = s.batch_id
			LEFT JOIN {$presc} pr ON pr.id = CASE WHEN s.reference_type = 'prescription' THEN s.reference_id ELSE NULL END";

		$count_sql = "SELECT COUNT(*) {$join_sql} WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$pages     = (int) max( 1, ceil( $total / $per_page ) );
		$page      = min( $page, $pages );
		$offset    = ( $page - 1 ) * $per_page;

		$list_params   = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;

		$sql  = "SELECT s.*, m.name AS m_name, m.generic_name AS m_generic_name, m.strength AS m_strength, m.sku AS m_sku,
					b.batch_number AS b_batch_number, b.expiry_date AS b_expiry_date,
					pr.prescription_number AS r_prescription_number, pr.status AS r_prescription_status
				 {$join_sql} WHERE {$where_sql}
				 ORDER BY s.id DESC LIMIT %d OFFSET %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $list_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$items = is_array( $rows ) ? $rows : array();
		foreach ( $items as &$item ) {
			$item['quantity']      = (int) $item['quantity'];
			$item['balance_after'] = (int) $item['balance_after'];
			$item['is_in']         = $item['quantity'] > 0;
			$item['type_label']    = self::type_label( $item['movement_type'] );
			$item['type_tone']     = self::type_tone( $item['movement_type'] );
			$item['medicine_label'] = ML_Medicine_Repository::display_name(
				array(
					'name'     => (string) $item['m_name'],
					'strength' => (string) $item['m_strength'],
				)
			);
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
	 * Movements recorded for a given prescription.
	 *
	 * @param int $prescription_id Prescription ID.
	 *
	 * @return array
	 */
	public static function for_prescription( $prescription_id ) {
		$result = self::list(
			array(
				'reference_type' => 'prescription',
				'reference_id'   => absint( $prescription_id ),
				'per_page'       => 100,
			)
		);
		return $result['items'];
	}

	/**
	 * Units of a medicine already dispensed for a prescription.
	 *
	 * @param int $prescription_id Prescription ID.
	 * @param int $medicine_id     Medicine ID.
	 *
	 * @return int
	 */
	public static function dispensed_for( $prescription_id, $medicine_id ) {
		global $wpdb;

		$table = ML_Database::table( 'stock_movements' );
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(ABS(SUM(quantity)), 0) FROM {$table}
				 WHERE reference_type = 'prescription' AND reference_id = %d AND medicine_id = %d AND movement_type = 'dispense'",
				absint( $prescription_id ),
				absint( $medicine_id )
			)
		);
		return $total;
	}

	/**
	 * Aggregate stock value and movement counts for the dashboard.
	 *
	 * @param string $from Optional start date (Y-m-d).
	 * @param string $to   Optional end date (Y-m-d).
	 *
	 * @return array
	 */
	public static function summary( $from = '', $to = '' ) {
		global $wpdb;

		$movements = ML_Database::table( 'stock_movements' );
		$batches   = ML_Database::table( 'medicine_batches' );
		$medicines = ML_Database::table( 'medicines' );

		$out = array(
			'medicines'         => 0,
			'active_batches'    => 0,
			'units_on_hand'     => 0,
			'stock_value'       => 0.0,
			'dispensed'         => 0,
			'wastage'           => 0,
			'expired'           => 0,
			'low_stock'         => 0,
			'out_of_stock'      => 0,
			'expiring_soon'     => 0,
			'expired_on_hand'   => 0,
		);

		$today = current_time( 'Y-m-d' );

		/*
		 * On-hand figures cover the units that can genuinely be handed out, so
		 * expired lots are excluded here just as they are in the per-medicine
		 * totals. Expired stock is reported on its own line below instead of
		 * quietly inflating the value of the shelves. The active-batch count
		 * shares this predicate so it can never disagree with units_on_hand.
		 */
		$sellable = "status = 'active' AND (expiry_date IS NULL OR expiry_date >= '{$today}')";

		$out['medicines']      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$medicines} WHERE status = 'active'" );
		$out['active_batches'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$batches} WHERE {$sellable} AND quantity_available > 0" );

		$out['units_on_hand']  = (int) $wpdb->get_var( "SELECT COALESCE(SUM(quantity_available), 0) FROM {$batches} WHERE {$sellable}" );
		$out['stock_value']    = (float) $wpdb->get_var( "SELECT COALESCE(SUM(quantity_available * unit_cost), 0) FROM {$batches} WHERE {$sellable}" );

		$low = ML_Medicine_Repository::low_stock( 200 );
		foreach ( $low as $row ) {
			if ( 'out' === $row['stock_state'] ) {
				$out['out_of_stock']++;
			} else {
				$out['low_stock']++;
			}
		}

		// Expiring soon is a forward-looking window, so already-expired lots
		// are counted separately rather than being listed twice.
		$out['expiring_soon'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$batches}
				 WHERE status = 'active' AND quantity_available > 0
				   AND expiry_date IS NOT NULL AND expiry_date >= %s AND expiry_date <= %s",
				$today,
				gmdate( 'Y-m-d', strtotime( $today . ' +90 days' ) )
			)
		);
		$out['expired_on_hand'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(quantity_available), 0) FROM {$batches} WHERE quantity_available > 0 AND expiry_date IS NOT NULL AND expiry_date < %s",
				$today
			)
		);

		$where  = array( 'movement_type IN ( %s, %s, %s )' );
		$params = array( 'dispense', 'wastage', 'expired' );
		if ( ml_is_date( $from ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = $from . ' 00:00:00';
		}
		if ( ml_is_date( $to ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = $to . ' 23:59:59';
		}
		$sql = 'SELECT movement_type, COALESCE(ABS(SUM(quantity)), 0) AS total FROM ' . $movements . ' WHERE ' . implode( ' AND ', $where ) . ' GROUP BY movement_type';
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) as $row ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( 'dispense' === $row['movement_type'] ) {
				$out['dispensed'] = (int) $row['total'];
			} elseif ( 'wastage' === $row['movement_type'] ) {
				$out['wastage'] = (int) $row['total'];
			} elseif ( 'expired' === $row['movement_type'] ) {
				$out['expired'] = (int) $row['total'];
			}
		}

		return $out;
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
		$ints    = array( 'id', 'medicine_id', 'batch_id', 'quantity', 'balance_after', 'reference_id', 'created_by' );
		foreach ( array_keys( $row ) as $key ) {
			$formats[ $key ] = in_array( $key, $ints, true ) ? '%d' : '%s';
		}
		return $formats;
	}
}
