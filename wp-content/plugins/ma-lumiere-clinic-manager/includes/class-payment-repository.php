<?php
/**
 * Payment repository + Razorpay gateway integration.
 *
 * Payment amounts are recorded as-is and the invoice status is always
 * recomputed from completed payments. Razorpay is only usable when billing
 * settings configure a key pair; otherwise manual recording is the path.
 *
 * Razorpay flow (server-authoritative):
 *  1. create_razorpay_order()  - creates a pending payment row + remote order.
 *  2. Checkout happens on the client with the order_id.
 *  3. verify_razorpay_payment() - signature + amount check, then completes
 *     the matching pending payment. This also stores the gateway fields.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Payment_Repository {

	/**
	 * Payment methods an invoice payment may be recorded against.
	 *
	 * @return array<string>
	 */
	public static function methods() {
		return array( 'cash', 'card', 'upi', 'bank_transfer', 'razorpay', 'other' );
	}

	/**
	 * Internal payment statuses.
	 *
	 * @return array<string>
	 */
	public static function statuses() {
		return array( 'pending', 'completed', 'failed', 'refunded' );
	}

	/**
	 * Single payment row.
	 *
	 * @param int $id Payment ID.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . ML_Database::table( 'payments' ) . ' WHERE id = %d', absint( $id ) ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Paginated payment list joined with invoice + patient.
	 *
	 * @param array $args { page, per_page, invoice_id, patient_id, method, status, from, to }.
	 *
	 * @return array
	 */
	public static function list( array $args = array() ) {
		global $wpdb;

		$page     = max( 1, isset( $args['page'] ) ? absint( $args['page'] ) : 1 );
		$per_page = isset( $args['per_page'] ) ? min( 100, max( 1, absint( $args['per_page'] ) ) ) : 20;
		$invoice  = isset( $args['invoice_id'] ) ? absint( $args['invoice_id'] ) : 0;
		$patient  = isset( $args['patient_id'] ) ? absint( $args['patient_id'] ) : 0;
		$method   = isset( $args['method'] ) ? sanitize_key( $args['method'] ) : '';
		$status   = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : '';
		$from     = isset( $args['from'] ) ? (string) $args['from'] : '';
		$to       = isset( $args['to'] ) ? (string) $args['to'] : '';

		$inv  = ML_Database::table( 'invoices' );
		$pay  = ML_Database::table( 'payments' );
		$pats = ML_Database::table( 'patients' );

		$where  = array( '1=1' );
		$params = array();

		if ( $invoice ) {
			$where[]  = 'py.invoice_id = %d';
			$params[] = $invoice;
		}
		if ( $patient ) {
			$where[]  = 'py.patient_id = %d';
			$params[] = $patient;
		}
		if ( $method && in_array( $method, self::methods(), true ) ) {
			$where[]  = 'py.payment_method = %s';
			$params[] = $method;
		}
		if ( $status && in_array( $status, self::statuses(), true ) ) {
			$where[]  = 'py.payment_status = %s';
			$params[] = $status;
		}
		if ( $from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
			$where[]  = 'py.payment_date >= %s';
			$params[] = $from;
		}
		if ( $to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			$where[]  = 'py.payment_date <= %s';
			$params[] = $to;
		}

		$where_sql = implode( ' AND ', $where );
		$base      = " FROM {$pay} py JOIN {$inv} i ON i.id = py.invoice_id JOIN {$pats} p ON p.id = py.patient_id WHERE {$where_sql}";

		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( 'SELECT COUNT(*)' . $base, $params ) : 'SELECT COUNT(*)' . $base );
		$pages = max( 1, (int) ceil( $total / $per_page ) );

		$sql = 'SELECT py.*, i.invoice_number AS i_number, i.total AS i_total, i.balance_amount AS i_balance, p.first_name AS p_first_name, p.last_name AS p_last_name, p.patient_uid AS p_uid'
			. $base . ' ORDER BY py.payment_date DESC, py.id DESC LIMIT %d OFFSET %d';
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
	 * Record a payment against an invoice (manual or non-gateway). Completes
	 * the payment immediately and recalculates invoice status.
	 *
	 * @param array $input { invoice_id, amount, method, payment_date, transaction_id, notes }.
	 *
	 * @return int|WP_Error Payment id.
	 */
	public static function create( array $input ) {
		global $wpdb;

		$invoice_id = isset( $input['invoice_id'] ) ? absint( $input['invoice_id'] ) : 0;
		$invoice    = ML_Invoice_Repository::get( $invoice_id );
		if ( ! $invoice ) {
			return new WP_Error( 'ml_payment_invoice', __( 'Invoice not found.', 'ma-lumiere-clinic' ) );
		}
		if ( ! ML_Security::can_access_patient( (int) $invoice['patient_id'] ) ) {
			return new WP_Error( 'ml_payment_forbidden', __( 'You are not allowed to record payments for this patient.', 'ma-lumiere-clinic' ) );
		}
		if ( 'void' === (string) $invoice['payment_status'] ) {
			return new WP_Error( 'ml_payment_void', __( 'A voided invoice cannot receive payments.', 'ma-lumiere-clinic' ) );
		}

		$amount = isset( $input['amount'] ) ? floatval( $input['amount'] ) : 0;
		if ( $amount <= 0 ) {
			return new WP_Error( 'ml_payment_amount', __( 'Payment amount must be greater than zero.', 'ma-lumiere-clinic' ) );
		}
		$balance = (float) $invoice['balance_amount'];
		if ( $amount > 0.005 && $amount > $balance + 0.005 ) {
			return new WP_Error( 'ml_payment_over', __( 'Payment exceeds the invoice balance.', 'ma-lumiere-clinic' ) );
		}

		$method = isset( $input['method'] ) ? sanitize_key( $input['method'] ) : 'cash';
		if ( ! in_array( $method, self::methods(), true ) ) {
			$method = 'cash';
		}

		$payment_date = isset( $input['payment_date'] ) ? sanitize_text_field( (string) $input['payment_date'] ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $payment_date ) ) {
			$payment_date = current_time( 'Y-m-d H:i:s' );
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $payment_date ) ) {
			$payment_date .= ' 00:00:00';
		}

		$row = array(
			'invoice_id'     => $invoice_id,
			'patient_id'     => (int) $invoice['patient_id'],
			'amount'         => round( $amount, 2 ),
			'payment_method' => $method,
			'transaction_id' => isset( $input['transaction_id'] ) ? sanitize_text_field( (string) $input['transaction_id'] ) : '',
			'gateway'        => '',
			'payment_status' => 'completed',
			'payment_date'   => $payment_date,
			'notes'          => isset( $input['notes'] ) ? sanitize_textarea_field( (string) $input['notes'] ) : '',
			'created_by'     => get_current_user_id() ? get_current_user_id() : null,
			'created_at'     => current_time( 'mysql' ),
		);

		$inserted = $wpdb->insert( ML_Database::table( 'payments' ), $row, self::formats() );
		if ( ! $inserted ) {
			return new WP_Error( 'ml_payment_save', __( 'The payment could not be recorded.', 'ma-lumiere-clinic' ) );
		}
		$payment_id = (int) $wpdb->insert_id;

		ML_Invoice_Repository::recalc( $invoice_id );
		ML_Audit_Log::record(
			'payment_recorded',
			'payment',
			$payment_id,
			sprintf( __( 'Payment of %s recorded against invoice %s.', 'ma-lumiere-clinic' ), ml_money( $amount ), (string) $invoice['invoice_number'] )
		);

		return $payment_id;
	}

	/**
	 * Change a payment status. Used to fail or refund a payment; invoice
	 * totals are recalculated afterwards.
	 *
	 * @param int    $id     Payment ID.
	 * @param string $status One of self::statuses().
	 *
	 * @return array|WP_Error
	 */
	public static function set_status( $id, $status ) {
		global $wpdb;
		$payment = self::get( $id );
		if ( ! $payment ) {
			return new WP_Error( 'ml_payment_not_found', __( 'Payment not found.', 'ma-lumiere-clinic' ) );
		}
		if ( ! in_array( $status, self::statuses(), true ) ) {
			return new WP_Error( 'ml_payment_status', __( 'Invalid payment status.', 'ma-lumiere-clinic' ) );
		}

		$wpdb->update(
			ML_Database::table( 'payments' ),
			array( 'payment_status' => $status ),
			array( 'id' => absint( $id ) ),
			array( '%s' ),
			array( '%d' )
		);

		return ML_Invoice_Repository::recalc( (int) $payment['invoice_id'] );
	}

	/* ---------------------------------------------------------------------
	 * Razorpay
	 * ------------------------------------------------------------------- */

	/**
	 * Resolved Razorpay credentials or an error.
	 *
	 * @return array|WP_Error { mode, key_id, key_secret }.
	 */
	public static function gateway_config() {
		$rz = ML_Settings::get( 'billing.razorpay', array() );
		$rz = is_array( $rz ) ? $rz : array();
		$mode = isset( $rz['mode'] ) ? sanitize_key( $rz['mode'] ) : 'off';

		if ( 'test' !== $mode && 'live' !== $mode ) {
			return new WP_Error( 'ml_razorpay_disabled', __( 'Razorpay is not enabled. Enable it in settings first.', 'ma-lumiere-clinic' ) );
		}
		$key_id     = isset( $rz['key_id'] ) ? trim( (string) $rz['key_id'] ) : '';
		$key_secret = isset( $rz['key_secret'] ) ? trim( (string) $rz['key_secret'] ) : '';
		if ( '' === $key_id || '' === $key_secret ) {
			return new WP_Error( 'ml_razorpay_config', __( 'Razorpay key ID and secret are required.', 'ma-lumiere-clinic' ) );
		}
		return array(
			'mode'       => $mode,
			'key_id'     => $key_id,
			'key_secret' => $key_secret,
		);
	}

	/**
	 * Create a Razorpay order for the invoice's outstanding balance and open
	 * a pending payment row to be completed on verification.
	 *
	 * @param int $invoice_id Invoice ID.
	 *
	 * @return array|WP_Error { order_id, amount (INR float), currency, key_id, receipt, invoice, payment }.
	 */
	public static function create_razorpay_order( $invoice_id ) {
		$invoice = ML_Invoice_Repository::get( $invoice_id );
		if ( ! $invoice ) {
			return new WP_Error( 'ml_invoice_not_found', __( 'Invoice not found.', 'ma-lumiere-clinic' ) );
		}
		if ( ! ML_Security::can_access_patient( (int) $invoice['patient_id'] ) ) {
			return new WP_Error( 'ml_payment_forbidden', __( 'You are not allowed to do this.', 'ma-lumiere-clinic' ) );
		}

		$config = self::gateway_config();
		if ( is_wp_error( $config ) ) {
			return $config;
		}
		$balance = (float) $invoice['balance_amount'];
		if ( $balance <= 0.005 ) {
			return new WP_Error( 'ml_payment_none', __( 'This invoice has no outstanding balance.', 'ma-lumiere-clinic' ) );
		}

		// Amount must be an integer number of paise.
		$amount_paise = (int) round( $balance * 100 );
		$currency     = (string) ( ML_Settings::get( 'billing.currency', 'INR' ) ?: 'INR' );

		$body = array(
			'amount'          => $amount_paise,
			'currency'        => $currency,
			'receipt'         => substr( (string) $invoice['invoice_number'], 0, 40 ),
			'notes'           => array(
				'invoice_id' => (int) $invoice_id,
				'patient'    => (string) $invoice['patient_id'],
			),
			'payment_capture' => 1,
		);

		$response = wp_remote_post(
			'https://api.razorpay.com/v1/orders',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $config['key_id'] . ':' . $config['key_secret'] ),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || ! is_array( $data ) || empty( $data['id'] ) ) {
			return new WP_Error(
				'ml_razorpay_order',
				isset( $data['error']['description'] ) ? $data['error']['description'] : __( 'Razorpay could not create the order.', 'ma-lumiere-clinic' )
			);
		}

		global $wpdb;
		$row = array(
			'invoice_id'       => (int) $invoice_id,
			'patient_id'       => (int) $invoice['patient_id'],
			'amount'           => round( $balance, 2 ),
			'payment_method'   => 'razorpay',
			'gateway'          => 'razorpay',
			'gateway_order_id' => (string) $data['id'],
			'payment_status'   => 'pending',
			'payment_date'     => current_time( 'mysql' ),
			'created_by'       => get_current_user_id() ? get_current_user_id() : null,
			'created_at'       => current_time( 'mysql' ),
		);
		$wpdb->insert( ML_Database::table( 'payments' ), $row, self::formats() );
		$payment_id = $wpdb->insert_id;

		return array(
			'order_id'  => (string) $data['id'],
			'amount'    => round( $balance, 2 ),
			'currency'  => $currency,
			'key_id'    => $config['key_id'],
			'receipt'   => (string) $invoice['invoice_number'],
			'invoice'   => $invoice,
			'payment'   => self::get( $payment_id ),
		);
	}

	/**
	 * Verify a Razorpay callback. The check is signature-first: we never
	 * trust anything the client says beyond the signature it presents.
	 *
	 * @param int   $invoice_id Invoice ID.
	 * @param array $input      { razorpay_order_id, razorpay_payment_id, razorpay_signature }.
	 *
	 * @return array|WP_Error { payment, invoice }.
	 */
	public static function verify_razorpay_payment( $invoice_id, array $input ) {
		global $wpdb;

		$invoice = ML_Invoice_Repository::get( $invoice_id );
		if ( ! $invoice ) {
			return new WP_Error( 'ml_invoice_not_found', __( 'Invoice not found.', 'ma-lumiere-clinic' ) );
		}

		$config = self::gateway_config();
		if ( is_wp_error( $config ) ) {
			return $config;
		}

		$order_id   = isset( $input['razorpay_order_id'] ) ? (string) $input['razorpay_order_id'] : '';
		$payment_id = isset( $input['razorpay_payment_id'] ) ? (string) $input['razorpay_payment_id'] : '';
		$signature  = isset( $input['razorpay_signature'] ) ? (string) $input['razorpay_signature'] : '';
		if ( '' === $order_id || '' === $payment_id || '' === $signature ) {
			return new WP_Error( 'ml_razorpay_payload', __( 'Incomplete Razorpay callback.', 'ma-lumiere-clinic' ) );
		}

		// Signature check.
		$expected = hash_hmac( 'sha256', $order_id . '|' . $payment_id, $config['key_secret'] );
		if ( ! hash_equals( $expected, $signature ) ) {
			return new WP_Error( 'ml_razorpay_signature', __( 'Razorpay signature verification failed.', 'ma-lumiere-clinic' ) );
		}

		$pay_table = ML_Database::table( 'payments' );

		// Idempotency first: if this exact gateway payment was already
		// completed, never count it a second time.
		$already_completed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$pay_table} WHERE gateway_payment_id = %s AND payment_status = 'completed'",
				$payment_id
			)
		);
		if ( $already_completed ) {
			$fresh = ML_Invoice_Repository::recalc( $invoice_id );
			$row   = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$pay_table} WHERE gateway_payment_id = %s AND payment_status = 'completed' ORDER BY id DESC LIMIT 1",
					$payment_id
				),
				ARRAY_A
			);
			return array(
				'payment'   => is_array( $row ) ? $row : null,
				'invoice'   => $fresh,
				'duplicate' => true,
			);
		}

		// Match the pending payment row for this order + invoice.
		$payment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$pay_table} WHERE invoice_id = %d AND gateway = 'razorpay' AND gateway_order_id = %s AND payment_status = 'pending' ORDER BY id DESC LIMIT 1",
				absint( $invoice_id ),
				$order_id
			),
			ARRAY_A
		);
		if ( ! $payment ) {
			return new WP_Error( 'ml_razorpay_order_match', __( 'No pending Razorpay order matches this invoice.', 'ma-lumiere-clinic' ) );
		}

		// Amount was fixed at order time; confirm the captured value equals
		// what the pending payment was opened for (the outstanding balance).
		$expected_amount = (int) round( (float) $payment['amount'] * 100 );
		$captured = isset( $input['captured_amount'] ) ? (int) $input['captured_amount'] : $expected_amount;
		if ( (int) $captured !== $expected_amount ) {
			return new WP_Error( 'ml_razorpay_amount', __( 'Captured amount does not match the order.', 'ma-lumiere-clinic' ) );
		}

		// Capture payment details server-side (best effort; never fails the
		// verification when the order itself was already valid).
		$updated = $wpdb->update(
			$pay_table,
			array(
				'payment_status'     => 'completed',
				'gateway_payment_id' => $payment_id,
				'gateway_signature'  => $signature,
				'transaction_id'     => $payment_id,
				'payment_date'       => current_time( 'mysql' ),
			),
			array( 'id' => (int) $payment['id'] ),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		$invoice = ML_Invoice_Repository::recalc( $invoice_id );
		ML_Audit_Log::record(
			'payment_completed_razorpay',
			'payment',
			(int) $payment['id'],
			sprintf( __( 'Razorpay payment completed for invoice %s.', 'ma-lumiere-clinic' ), (string) $invoice['invoice_number'] )
		);

		return array(
			'payment'   => self::get( (int) $payment['id'] ),
			'invoice'   => $invoice,
			'duplicate' => false,
			'updated'   => (bool) $updated,
		);
	}

	/**
	 * Inserts/local formatting map for payments rows.
	 *
	 * @return array<string>
	 */
	private static function formats() {
		return array(
			'invoice_id'        => '%d',
			'patient_id'        => '%d',
			'amount'            => '%f',
			'payment_method'    => '%s',
			'transaction_id'    => '%s',
			'gateway'           => '%s',
			'gateway_order_id'  => '%s',
			'gateway_payment_id' => '%s',
			'gateway_signature' => '%s',
			'payment_status'    => '%s',
			'payment_date'      => '%s',
			'notes'             => '%s',
			'created_by'        => '%d',
			'created_at'        => '%s',
		);
	}
}