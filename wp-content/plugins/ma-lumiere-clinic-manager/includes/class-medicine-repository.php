<?php
/**
 * Medicine catalog repository: the reference list of every formulation the
 * clinic stocks or prescribes.
 *
 * A medicine row is deliberately *not* a stock counter. Quantities live on
 * batch rows (see ML_Medicine_Batch_Repository) so that expiry and cost stay
 * attached to the physical consignment, and the available total is always a
 * derived sum rather than a number that can silently drift.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Medicine_Repository {

	/**
	 * SKU prefix for auto-allocated catalogue codes.
	 *
	 * @var string
	 */
	const SKU_PREFIX = 'MED-';

	/**
	 * Medicine lifecycle statuses.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'active', 'inactive' );
	}

	/**
	 * Dosage forms. Drives packaging-aware default units and keeps the
	 * catalogue consistent without a free-text "form" column drifting.
	 *
	 * @return array<string>
	 */
	public static function forms() {
		return array(
			'tablet',
			'capsule',
			'syrup',
			'suspension',
			'cream',
			'gel',
			'ointment',
			'solution',
			'drops',
			'injection',
			'powder',
			'sachet',
			'device',
		);
	}

	/**
	 * Therapeutic categories used to group the catalogue.
	 *
	 * @return array<string>
	 */
	public static function categories() {
		return array(
			'antibiotic',
			'anti-inflammatory',
			'analgesic',
			'antihistamine',
			'corticosteroid',
			'retinoid',
			'antifungal',
			'antiseptic',
			'moisturiser',
			'sunscreen',
			'vitamin',
			'anaesthetic',
			'other',
		);
	}

	/**
	 * Persistable medicine columns.
	 *
	 * @return array<string>
	 */
	public static function fields() {
		return array(
			'sku',
			'name',
			'generic_name',
			'brand_name',
			'strength',
			'form',
			'category',
			'reorder_level',
			'requires_prescription',
			'status',
			'notes',
		);
	}

	/**
	 * Sanitize raw input into a medicine field set.
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

		if ( '' === trim( (string) $clean['name'] ) ) {
			$errors['name'] = __( 'Medicine name is required.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['status'] && ! in_array( $clean['status'], self::statuses(), true ) ) {
			$errors['status'] = __( 'Invalid medicine status.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['form'] && ! in_array( $clean['form'], self::forms(), true ) ) {
			$errors['form'] = __( 'Invalid dosage form.', 'ma-lumiere-clinic' );
		}
		if ( '' !== (string) $clean['category'] && ! in_array( $clean['category'], self::categories(), true ) ) {
			$errors['category'] = __( 'Invalid category.', 'ma-lumiere-clinic' );
		}
		if ( (int) $clean['reorder_level'] < 0 ) {
			$errors['reorder_level'] = __( 'Reorder level cannot be negative.', 'ma-lumiere-clinic' );
		}

		return $errors;
	}

	/**
	 * Create a medicine.
	 *
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error Medicine ID.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$clean  = self::sanitize( $input );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_medicine_invalid', __( 'Medicine data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$sku = (string) $clean['sku'];
		if ( '' === $sku ) {
			$sku = self::allocate_sku();
		}
		if ( self::sku_taken( $sku ) ) {
			return new WP_Error( 'ml_medicine_duplicate_sku', __( 'That medicine code is already in use.', 'ma-lumiere-clinic' ), array( 'errors' => array( 'sku' => __( 'That medicine code is already in use.', 'ma-lumiere-clinic' ) ) ) );
		}

		$now = current_time( 'mysql' );
		$row = array(
			'sku'                    => $sku,
			'name'                   => (string) $clean['name'],
			'generic_name'           => self::null_or( $clean['generic_name'] ),
			'brand_name'             => self::null_or( $clean['brand_name'] ),
			'strength'               => self::null_or( $clean['strength'] ),
			'form'                   => self::null_or( $clean['form'] ),
			'category'               => self::null_or( $clean['category'] ),
			'reorder_level'          => absint( $clean['reorder_level'] ),
			'requires_prescription'  => (int) $clean['requires_prescription'] ? 1 : 0,
			'status'                 => '' !== (string) $clean['status'] ? (string) $clean['status'] : 'active',
			'notes'                  => self::null_or( $clean['notes'] ),
			'created_at'             => $now,
			'updated_at'             => $now,
		);

		$inserted = $wpdb->insert( ML_Database::table( 'medicines' ), $row, self::formats( $row ) );
		if ( ! $inserted ) {
			return new WP_Error( 'ml_medicine_create_failed', __( 'Could not save the medicine.', 'ma-lumiere-clinic' ) );
		}
		$id = (int) $wpdb->insert_id;

		ml_audit( 'medicine_created', 'medicine', $id, sprintf( __( 'Medicine added: %s', 'ma-lumiere-clinic' ), $row['name'] ) );

		return $id;
	}

	/**
	 * Update a medicine. Catalogue edits never touch stock.
	 *
	 * @param int   $id    Medicine ID.
	 * @param array $input Raw values.
	 *
	 * @return int|WP_Error
	 */
	public static function update( $id, array $input ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_medicine_not_found', __( 'Medicine not found.', 'ma-lumiere-clinic' ) );
		}

		$clean  = self::sanitize( $input, $existing );
		$errors = self::validate( $clean );
		if ( $errors ) {
			return new WP_Error( 'ml_medicine_invalid', __( 'Medicine data is invalid.', 'ma-lumiere-clinic' ), array( 'errors' => $errors ) );
		}

		$sku = (string) $clean['sku'];
		if ( '' !== $sku && self::sku_taken( $sku, $id ) ) {
			return new WP_Error( 'ml_medicine_duplicate_sku', __( 'That medicine code is already in use.', 'ma-lumiere-clinic' ), array( 'errors' => array( 'sku' => __( 'That medicine code is already in use.', 'ma-lumiere-clinic' ) ) ) );
		}

		$row = array(
			'sku'                   => '' === $sku ? (string) $existing['sku'] : $sku,
			'name'                  => (string) $clean['name'],
			'generic_name'          => self::null_or( $clean['generic_name'] ),
			'brand_name'            => self::null_or( $clean['brand_name'] ),
			'strength'              => self::null_or( $clean['strength'] ),
			'form'                  => self::null_or( $clean['form'] ),
			'category'              => self::null_or( $clean['category'] ),
			'reorder_level'         => absint( $clean['reorder_level'] ),
			'requires_prescription' => (int) $clean['requires_prescription'] ? 1 : 0,
			'status'                => '' !== (string) $clean['status'] ? (string) $clean['status'] : 'active',
			'notes'                 => self::null_or( $clean['notes'] ),
			'updated_at'            => current_time( 'mysql' ),
		);

		$wpdb->update( ML_Database::table( 'medicines' ), $row, array( 'id' => $id ), self::formats( $row ), array( '%d' ) );

		ml_audit( 'medicine_updated', 'medicine', $id, sprintf( __( 'Medicine updated: %s', 'ma-lumiere-clinic' ), $row['name'] ) );

		return $id;
	}

	/**
	 * Remove a medicine. Refused while any stock movement references it, so
	 * the ledger can never point at a missing medicine.
	 *
	 * @param int $id Medicine ID.
	 *
	 * @return true|WP_Error
	 */
	public static function delete( $id ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_medicine_not_found', __( 'Medicine not found.', 'ma-lumiere-clinic' ) );
		}

		$movements = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . ML_Database::table( 'stock_movements' ) . ' WHERE medicine_id = %d', $id )
		);
		if ( $movements > 0 ) {
			return new WP_Error( 'ml_medicine_has_history', __( 'This medicine has stock history and cannot be deleted. Mark it inactive instead.', 'ma-lumiere-clinic' ) );
		}

		$deleted = $wpdb->delete( ML_Database::table( 'medicine_batches' ), array( 'medicine_id' => $id ), array( '%d' ) );
		unset( $deleted );

		$wpdb->delete( ML_Database::table( 'medicines' ), array( 'id' => $id ), array( '%d' ) );

		ml_audit( 'medicine_deleted', 'medicine', $id, sprintf( __( 'Medicine removed from catalogue: %s', 'ma-lumiere-clinic' ), $existing['name'] ) );

		return true;
	}

	/**
	 * Fetch a medicine decorated with its derived stock totals.
	 *
	 * @param int $id Medicine ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$table = ML_Database::table( 'medicines' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		self::decorate( $row );
		return $row;
	}

	/**
	 * Filterable, paginated catalogue listing joined to live stock totals.
	 *
	 * @param array $args Filters: search, status, form, category, stock (low|out), page, per_page, orderby, order.
	 *
	 * @return array
	 */
	public static function list( array $args = array() ) {
		global $wpdb;

		$table    = ML_Database::table( 'medicines' );
		$page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page = min( 100, max( 1, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );
		$search   = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$status   = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$form     = isset( $args['form'] ) ? sanitize_key( (string) $args['form'] ) : '';
		$category = isset( $args['category'] ) ? sanitize_key( (string) $args['category'] ) : '';
		$stock    = isset( $args['stock'] ) ? sanitize_key( (string) $args['stock'] ) : '';

		$orderby = isset( $args['orderby'] ) ? sanitize_key( (string) $args['orderby'] ) : 'name';
		$order   = ( isset( $args['order'] ) && 'DESC' === strtoupper( (string) $args['order'] ) ) ? 'DESC' : 'ASC';

		if ( ! in_array( $status, self::statuses(), true ) ) {
			$status = '';
		}
		if ( ! in_array( $form, self::forms(), true ) ) {
			$form = '';
		}
		if ( ! in_array( $category, self::categories(), true ) ) {
			$category = '';
		}

		// Available stock is the sum of live batches, so it is aggregated in a
		// sub-select rather than stored on the medicine row.
		$stock_sql = self::stock_expression( 'm' );

		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $status ) {
			$where[]  = 'm.status = %s';
			$params[] = $status;
		}
		if ( '' !== $form ) {
			$where[]  = 'm.form = %s';
			$params[] = $form;
		}
		if ( '' !== $category ) {
			$where[]  = 'm.category = %s';
			$params[] = $category;
		}
		if ( 'out' === $stock ) {
			$where[] = "( {$stock_sql} <= 0 )";
		} elseif ( 'low' === $stock ) {
			$where[] = "( {$stock_sql} > 0 AND {$stock_sql} <= m.reorder_level )";
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(m.name LIKE %s OR m.generic_name LIKE %s OR m.brand_name LIKE %s OR m.sku LIKE %s OR m.strength LIKE %s)';
			foreach ( array( 1, 1, 1, 1, 1 ) as $i ) {
				$params[] = $like;
				unset( $i );
			}
		}

		$where_sql  = implode( ' AND ', $where );
		$select_sql = "SELECT m.*, {$stock_sql} AS stock_available, m.reorder_level AS reorder_threshold FROM {$table} m WHERE {$where_sql}";

		$allowed_orderby = array( 'name', 'generic_name', 'category', 'status', 'created_at', 'stock_available' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'name';
		}

		$count_sql = "SELECT COUNT(*) FROM {$table} m WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$pages     = (int) max( 1, ceil( $total / $per_page ) );
		$page      = min( $page, $pages );
		$offset    = ( $page - 1 ) * $per_page;

		$list_params   = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;

		$sql  = "{$select_sql} ORDER BY m.{$orderby} {$order}, m.id ASC LIMIT %d OFFSET %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $list_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$items = is_array( $rows ) ? $rows : array();
		foreach ( $items as &$item ) {
			$item['stock_available'] = (int) $item['stock_available'];
			$item['stock_state']     = self::stock_state( $item['stock_available'], (int) $item['reorder_level'] );
			$item['display_name']    = self::display_name( $item );
			$item['next_expiry']     = ML_Medicine_Batch_Repository::next_expiry( (int) $item['id'] );
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
	 * Medicines that need attention, most urgent first.
	 *
	 * Covers everything with no dispensable stock, plus anything sitting at or
	 * below a reorder level that has been set.
	 *
	 * @param int $limit Maximum rows.
	 *
	 * @return array
	 */
	public static function low_stock( $limit = 50 ) {
		global $wpdb;

		$limit    = min( 500, max( 1, absint( $limit ) ) );
		$table    = ML_Database::table( 'medicines' );
		$stock_sql = self::stock_expression( 'm' );

		/*
		 * A medicine is reported when it has run out, or when it sits at or
		 * below a reorder level that has actually been set. Zero-order
		 * medicines are therefore still flagged as out of stock, while a
		 * threshold of 0 keeps an otherwise healthy medicine quiet.
		 */
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.*, {$stock_sql} AS stock_available FROM {$table} m
				 WHERE m.status = 'active'
				   AND ( ( {$stock_sql} <= 0 ) OR ( m.reorder_level > 0 AND {$stock_sql} <= m.reorder_level ) )
				 ORDER BY ({$stock_sql} - m.reorder_level) ASC, m.name ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			),
			ARRAY_A
		);

		$items = is_array( $rows ) ? $rows : array();
		foreach ( $items as &$item ) {
			$item['stock_available'] = (int) $item['stock_available'];
			$item['stock_state']     = self::stock_state( $item['stock_available'], (int) $item['reorder_level'] );
			$item['display_name']    = self::display_name( $item );
		}
		unset( $item );

		return $items;
	}

	/**
	 * All medicines ordered for select controls.
	 *
	 * @param bool $active_only Restrict to active medicines.
	 *
	 * @return array
	 */
	public static function options( $active_only = true ) {
		global $wpdb;

		$table  = ML_Database::table( 'medicines' );
		$where  = $active_only ? "WHERE status = 'active'" : '';
		$rows   = $wpdb->get_results( "SELECT id, sku, name, generic_name, strength, form FROM {$table} {$where} ORDER BY name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items  = is_array( $rows ) ? $rows : array();
		$return = array();
		foreach ( $items as $row ) {
			$return[] = array(
				'id'    => (int) $row['id'],
				'label' => self::display_name( $row ),
			);
		}
		return $return;
	}

	/**
	 * Human label for a medicine: name plus distinguishing strength.
	 *
	 * @param array $medicine Medicine row.
	 *
	 * @return string
	 */
	public static function display_name( $medicine ) {
		$name = trim( (string) $medicine['name'] );
		if ( '' !== (string) $medicine['strength'] ) {
			$name .= ' ' . (string) $medicine['strength'];
		}
		return $name;
	}

	/**
	 * SQL that sums the units of a medicine which can actually be handed out.
	 *
	 * Only active lots that have not passed their expiry date count, so the
	 * derived total matches what ML_Medicine_Batch_Repository::dispensable()
	 * would offer. Expired stock is reported separately by the alerts rather
	 * than being folded into a misleading on-hand figure.
	 *
	 * @param string $alias Table alias for the medicines table.
	 *
	 * @return string
	 */
	private static function stock_expression( $alias = 'm' ) {
		$batches = ML_Database::table( 'medicine_batches' );

		return "COALESCE((SELECT SUM(b.quantity_available) FROM {$batches} b
			WHERE b.medicine_id = {$alias}.id
			  AND " . self::dispensable_where( 'b' ) . "), 0)";
	}

	/**
	 * Shared predicate for the units that can actually be handed out: an active
	 * lot that is not past its expiry date. Listing, filtering, per-medicine
	 * totals and the inventory summary all derive from this so a shelf can
	 * never report two different on-hand figures. Expired stock is surfaced
	 * through the expiry alerts instead of inflating these totals.
	 *
	 * @param string $alias Table alias for the batches table, or '' when the
	 *                       query selects from the table without an alias.
	 *
	 * @return string
	 */
	private static function dispensable_where( $alias = 'b' ) {
		$prefix = ( '' !== $alias ) ? $alias . '.' : '';

		return "{$prefix}status = 'active' AND ({$prefix}expiry_date IS NULL OR {$prefix}expiry_date >= CURDATE())";
	}

	/**
	 * Classify a stock level against its reorder threshold.
	 *
	 * @param int $available    Units on hand.
	 * @param int $reorder_level Reorder threshold.
	 *
	 * @return string 'out'|'low'|'ok'
	 */
	public static function stock_state( $available, $reorder_level ) {
		$available     = (int) $available;
		$reorder_level = (int) $reorder_level;
		if ( $available <= 0 ) {
			return 'out';
		}
		if ( $reorder_level > 0 && $available <= $reorder_level ) {
			return 'low';
		}
		return 'ok';
	}

	/**
	 * Next free catalogue code. Retired codes are never reused, matching the
	 * allocator behaviour used for patient UIDs and prescription numbers.
	 *
	 * @return string
	 */
	public static function allocate_sku() {
		global $wpdb;

		$table = ML_Database::table( 'medicines' );
		$start = strlen( self::SKU_PREFIX ) + 1;
		$max   = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT MAX(CAST(SUBSTRING(sku, %d) AS UNSIGNED)) FROM ' . $table . ' WHERE sku LIKE %s',
				$start,
				$wpdb->esc_like( self::SKU_PREFIX ) . '%'
			)
		);
		return self::SKU_PREFIX . str_pad( (string) ( $max + 1 ), 5, '0', STR_PAD_LEFT );
	}

	/**
	 * Is a catalogue code already used?
	 *
	 * @param string $sku        Candidate code.
	 * @param int    $except_id  Medicine to ignore (edit case).
	 *
	 * @return bool
	 */
	public static function sku_taken( $sku, $except_id = 0 ) {
		global $wpdb;

		$table = ML_Database::table( 'medicines' );
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE sku = %s AND id <> %d",
				(string) $sku,
				absint( $except_id )
			)
		);
		return $count > 0;
	}

	/**
	 * Total units on hand across live batches.
	 *
	 * @param int $medicine_id Medicine ID.
	 *
	 * @return int
	 */
	public static function total_available( $medicine_id ) {
		global $wpdb;

		$table = ML_Database::table( 'medicine_batches' );
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(quantity_available), 0) FROM {$table} WHERE medicine_id = %d AND " . self::dispensable_where( '' ),
				absint( $medicine_id )
			)
		);
		return $total;
	}

	/**
	 * Attach derived stock figures to a single row.
	 *
	 * @param array $row Row by reference.
	 *
	 * @return void
	 */
	private static function decorate( array &$row ) {
		$row['stock_available'] = self::total_available( (int) $row['id'] );
		$row['stock_state']     = self::stock_state( $row['stock_available'], (int) $row['reorder_level'] );
		$row['display_name']    = self::display_name( $row );
		$row['next_expiry']     = ML_Medicine_Batch_Repository::next_expiry( (int) $row['id'] );
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
			case 'sku':
				return mb_substr( sanitize_text_field( strtoupper( $value ) ), 0, 50 );
			case 'name':
			case 'generic_name':
			case 'brand_name':
				return mb_substr( sanitize_text_field( $value ), 0, 200 );
			case 'strength':
				return mb_substr( sanitize_text_field( $value ), 0, 100 );
			case 'form':
			case 'category':
			case 'status':
				return in_array( $value, self::statuses(), true ) || in_array( $value, self::forms(), true ) || in_array( $value, self::categories(), true ) ? $value : '';
			case 'reorder_level':
				return (string) absint( $value );
			case 'requires_prescription':
				return ( $value && '0' !== (string) $value && 'false' !== strtolower( (string) $value ) ) ? '1' : '0';
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
		$ints    = array( 'id', 'reorder_level', 'requires_prescription' );
		foreach ( array_keys( $row ) as $key ) {
			$formats[ $key ] = in_array( $key, $ints, true ) ? '%d' : '%s';
		}
		return $formats;
	}
}
