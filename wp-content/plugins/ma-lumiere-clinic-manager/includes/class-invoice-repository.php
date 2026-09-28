<?php
/**
 * Invoice repository + lifecycle.
 *
 * Money is stored as DECIMAL(10,2); totals are recomputed server-side from
 * line items so a submitted amount is never trusted. Invoices are final
 * documents: editing is not supported; a new invoice should be issued and
 * the old one voided by recording a refund/credit note (payments repository).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Invoice_Repository {

	/**
	 * Payment statuses for the invoice.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'unpaid', 'partial', 'paid', 'void' );
	}

	/**
	 * Paginated invoice list (joins patient name/UID).
	 *
	 * @param array $args { page, per_page, patient_id, status, from, to, search }.
	 *
	 * @return array{items:array,total:int,pages:int,page:int,per_page:int}
	 */
	public static function list( array $args = array() ) {
		global $wpdb;

		$page    = max( 1, isset( $args['page'] ) ? absint( $args['page'] ) : 1 );
		$per_page = isset( $args['per_page'] ) ? min( 100, max( 1, absint( $args['per_page'] ) ) ) : 20;
		$patient = isset( $args['patient_id'] ) ? absint( $args['patient_id'] ) : 0;
		$status  = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : '';
		$from    = isset( $args['from'] ) ? (string) $args['from'] : '';
		$to      = isset( $args['to'] ) ? (string) $args['to'] : '';
		$search  = isset( $args['search'] ) ? trim( (string) $args['search'] ) : '';

		$inv = ML_Database::table( 'invoices' );
		$pats = ML_Database::table( 'patients' );

		$where  = array( '1=1' );
		$params = array();

		if ( $patient ) {
			$where[]  = 'i.patient_id = %d';
			$params[] = $patient;
		}
		if ( $status && in_array( $status, self::statuses(), true ) ) {
			$where[]  = 'i.payment_status = %s';
			$params[] = $status;
		}
		if ( $from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
			$where[]  = 'i.invoice_date >= %s';
			$params[] = $from;
		}
		if ( $to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			$where[]  = 'i.invoice_date <= %s';
			$params[] = $to;
		}
		if ( '' !== $search ) {
			$where[]  = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_uid LIKE %s OR i.invoice_number LIKE %s)";
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$base      = " FROM {$inv} i JOIN {$pats} p ON p.id = i.patient_id WHERE {$where_sql}";

		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( 'SELECT COUNT(*)' . $base, $params ) : 'SELECT COUNT(*)' . $base );
		$pages = max( 1, (int) ceil( $total / $per_page ) );

		$sql = 'SELECT i.*, p.first_name AS p_first_name, p.last_name AS p_last_name, p.patient_uid AS p_uid'
			. $base . ' ORDER BY i.invoice_date DESC, i.id DESC LIMIT %d OFFSET %d';
		$params[] = $per_page;
		$params[] = ( $page - 1 ) * $per_page;

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
	 * Single invoice row.
	 *
	 * @param int $id Invoice ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . ML_Database::table( 'invoices' ) . ' WHERE id = %d', absint( $id ) ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Line items for an invoice.
	 *
	 * @param int $id Invoice ID.
	 *
	 * @return array
	 */
	public static function items( $id ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . ML_Database::table( 'invoice_items' ) . ' WHERE invoice_id = %d ORDER BY id ASC',
				absint( $id )
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Create an invoice with its line items. Totals are computed here from
	 * the item rows; the submitted total is never stored directly.
	 *
	 * @param array $input { patient_id, invoice_date, due_date, items[], appointment_id, visit_id }.
	 *
	 * @return int|WP_Error New invoice id.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$patient_id = isset( $input['patient_id'] ) ? absint( $input['patient_id'] ) : 0;
		$patient    = $patient_id ? ML_Patient_Repository::get( $patient_id ) : null;
		if ( ! $patient ) {
			return new WP_Error( 'ml_invoice_patient', __( 'A valid patient is required.', 'ma-lumiere-clinic' ) );
		}
		if ( ! ML_Security::can_access_patient( $patient_id ) ) {
			return new WP_Error( 'ml_invoice_forbidden', __( 'You are not allowed to bill this patient.', 'ma-lumiere-clinic' ) );
		}

		$invoice_date = isset( $input['invoice_date'] ) ? sanitize_text_field( (string) $input['invoice_date'] ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $invoice_date ) ) {
			$invoice_date = current_time( 'Y-m-d' );
		}
		$due_date = isset( $input['due_date'] ) ? sanitize_text_field( (string) $input['due_date'] ) : '';
		if ( $due_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $due_date ) ) {
			$due_date = '';
		}

		$appointment_id = isset( $input['appointment_id'] ) ? absint( $input['appointment_id'] ) : 0;
		$visit_id       = isset( $input['visit_id'] ) ? absint( $input['visit_id'] ) : 0;

		// Normalize line items: only rows with a description + qty > 0 count.
		$raw_items = isset( $input['items'] ) && is_array( $input['items'] ) ? $input['items'] : array();
		$items     = array();
		foreach ( $raw_items as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$desc = isset( $row['description'] ) ? sanitize_text_field( (string) $row['description'] ) : '';
			if ( '' === trim( $desc ) ) {
				continue;
			}
			$qty = isset( $row['quantity'] ) ? max( 0, floatval( $row['quantity'] ) ) : 1;
			if ( $qty <= 0 ) {
				continue;
			}
			$items[] = array(
				'description' => $desc,
				'quantity'    => $qty,
				'unit_price'  => isset( $row['unit_price'] ) ? max( 0, floatval( $row['unit_price'] ) ) : 0,
				'discount'    => isset( $row['discount'] ) ? max( 0, floatval( $row['discount'] ) ) : 0,
				'item_type'   => isset( $row['item_type'] ) ? sanitize_key( (string) $row['item_type'] ) : 'consultation',
				'treatment_id' => isset( $row['treatment_id'] ) ? absint( $row['treatment_id'] ) : 0,
			);
		}
		if ( ! $items ) {
			return new WP_Error( 'ml_invoice_items', __( 'At least one line item with a description is required.', 'ma-lumiere-clinic' ) );
		}

		// Recompute totals server-side (never trust submitted totals).
		$tax_rate     = (bool) ML_Settings::get( 'billing.tax_enabled', 0 ) ? (float) ML_Settings::get( 'billing.tax_percent', 0 ) : 0;
		$subtotal     = 0.0;
		$total_disc   = 0.0;
		$total_tax    = 0.0;
		$line_rows    = array();
		foreach ( $items as $item ) {
			$line_full  = round( $item['unit_price'] * $item['quantity'], 2 );
			$line_disc  = round( min( $line_full, $item['discount'] ), 2 );
			$line_base  = round( $line_full - $line_disc, 2 );
			$line_tax   = $tax_rate > 0 ? round( $line_base * $tax_rate / 100, 2 ) : 0.0;
			$line_total = round( $line_base + $line_tax, 2 );

			$subtotal   += $line_full;
			$total_disc += $line_disc;
			$total_tax  += $line_tax;

			$line_rows[] = array(
				'item_type'    => $item['item_type'],
				'treatment_id' => $item['treatment_id'],
				'description'  => $item['description'],
				'quantity'     => $item['quantity'],
				'unit_price'   => $item['unit_price'],
				'discount'     => $line_disc,
				'tax'          => $line_tax,
				'line_total'   => $line_total,
			);
		}
		$total = round( $subtotal - $total_disc + $total_tax, 2 );

		$number = self::allocate_number();
		if ( is_wp_error( $number ) ) {
			return $number;
		}

		$invoice = array(
			'invoice_number'  => $number,
			'patient_id'      => $patient_id,
			'appointment_id'  => $appointment_id ? $appointment_id : null,
			'visit_id'        => $visit_id ? $visit_id : null,
			'subtotal'        => round( $subtotal, 2 ),
			'discount'        => round( $total_disc, 2 ),
			'tax'             => round( $total_tax, 2 ),
			'total'           => $total,
			'paid_amount'     => 0,
			'balance_amount'  => $total,
			'payment_status'  => 0 < $total ? 'unpaid' : 'paid',
			'invoice_date'    => $invoice_date,
			'due_date'        => $due_date ? $due_date : null,
			'created_by'      => get_current_user_id() ? get_current_user_id() : null,
			'created_at'      => current_time( 'mysql' ),
		);
		if ( 0 >= $total ) {
			$invoice['paid_amount'] = 0;
		}

		$invoice_table = ML_Database::table( 'invoices' );
		$item_table    = ML_Database::table( 'invoice_items' );

		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$inserted = $wpdb->insert( $invoice_table, $invoice, self::invoice_formats() );
		if ( ! $inserted ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return new WP_Error( 'ml_invoice_save', __( 'The invoice could not be saved.', 'ma-lumiere-clinic' ) );
		}
		$invoice_id = (int) $wpdb->insert_id;

		foreach ( $line_rows as $item ) {
			$item['invoice_id']   = $invoice_id;
			$item['item_type']    = isset( $item['item_type'] ) ? $item['item_type'] : 'consultation';
			$item['treatment_id'] = $item['treatment_id'] ? $item['treatment_id'] : null;
			$ok = $wpdb->insert(
				$item_table,
				$item,
				array( '%d', '%s', '%d', '%s', '%f', '%f', '%f', '%f', '%f' )
			);
			if ( ! $ok ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				return new WP_Error( 'ml_invoice_items_save', __( 'Invoice line items could not be saved.', 'ma-lumiere-clinic' ) );
			}
		}
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		ML_Audit_Log::record(
			'invoice_created',
			'invoice',
			$invoice_id,
			sprintf( __( 'Invoice %s created for patient %s.', 'ma-lumiere-clinic' ), $number, (string) $patient['patient_uid'] )
		);

		return $invoice_id;
	}

	/**
	 * Recompute invoice paid/balance/status from linked payments.
	 *
	 * @param int $id Invoice ID.
	 *
	 * @return array|WP_Error Updated invoice row.
	 */
	public static function recalc( $id ) {
		global $wpdb;
		$invoice = self::get( $id );
		if ( ! $invoice ) {
			return new WP_Error( 'ml_invoice_not_found', __( 'Invoice not found.', 'ma-lumiere-clinic' ) );
		}

		$paid = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount),0) FROM " . ML_Database::table( 'payments' ) . " WHERE invoice_id = %d AND payment_status = 'completed'",
				absint( $id )
			)
		);
		$total   = (float) $invoice['total'];
		$balance = (float) max( 0, $total - $paid );

		if ( $balance <= 0.005 ) {
			$status = 'paid';
			$balance = 0;
			$paid    = $total;
		} elseif ( $paid > 0 ) {
			$status = 'partial';
		} else {
			$status = 'unpaid';
		}
		if ( 'void' === (string) $invoice['payment_status'] ) {
			$status = 'void';
		}

		$wpdb->update(
			ML_Database::table( 'invoices' ),
			array(
				'paid_amount'    => round( $paid, 2 ),
				'balance_amount' => round( $balance, 2 ),
				'payment_status' => $status,
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => absint( $id ) ),
			array( '%f', '%f', '%s', '%s' ),
			array( '%d' )
		);

		return self::get( $id );
	}

	/**
	 * Issue the next invoice number under the configured prefix.
	 * UNIQUE constraint + retry loop guards against races.
	 *
	 * @return string|WP_Error
	 */
	private static function allocate_number() {
		global $wpdb;
		$prefix = (string) ML_Settings::get( 'billing.invoice_prefix', 'ML-INV-' );
		$prefix = sanitize_text_field( $prefix );
		if ( '' === $prefix ) {
			$prefix = 'ML-INV-';
		}
		$table = ML_Database::table( 'invoices' );

		for ( $attempt = 0; $attempt < 8; $attempt++ ) {
			$max = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(invoice_number) FROM {$table} WHERE invoice_number LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) );
			$seq = $max ? (int) substr( (string) $max, strlen( $prefix ) ) + 1 : 1;
			$candidate = $prefix . str_pad( (string) $seq, 6, '0', STR_PAD_LEFT );

			$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE invoice_number = %s", $candidate ) );
			if ( $exists ) {
				continue;
			}
			return $candidate;
		}
		return new WP_Error( 'ml_invoice_number', __( 'Could not allocate an invoice number. Please try again.', 'ma-lumiere-clinic' ) );
	}

	/**
	 * WP-DB insert format map for the invoice row.
	 *
	 * @return array<string>
	 */
	private static function invoice_formats() {
		return array(
			'invoice_number'  => '%s',
			'patient_id'      => '%d',
			'appointment_id'  => '%d',
			'visit_id'        => '%d',
			'subtotal'        => '%f',
			'discount'        => '%f',
			'tax'             => '%f',
			'total'           => '%f',
			'paid_amount'     => '%f',
			'balance_amount'  => '%f',
			'payment_status'  => '%s',
			'invoice_date'    => '%s',
			'due_date'        => '%s',
			'created_by'      => '%d',
			'created_at'      => '%s',
		);
	}
}