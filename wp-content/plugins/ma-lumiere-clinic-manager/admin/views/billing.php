<?php
/**
 * Billing — invoices, PDF downloads and the Razorpay checkout path.
 *
 * POST actions handled here, gated by the shared nonce + capability:
 *   create_invoice   – new invoice with line items (server-recomputed totals).
 *   record_payment   – manual payment for an invoice (cash/card/upi/…).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_base        = admin_url( 'admin.php' );
$ml_self        = add_query_arg( 'page', 'ml-clinic-billing', $ml_base );
$ml_can_manage  = current_user_can( 'ml_manage_clinic' ) || current_user_can( 'ml_manage_billing' );
$ml_can_record  = current_user_can( 'ml_manage_clinic' ) || current_user_can( 'ml_record_payments' );
$ml_error       = '';
$ml_notice      = isset( $_GET['ml_notice'] ) ? sanitize_key( wp_unslash( $_GET['ml_notice'] ) ) : '';
$ml_view        = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;

if ( ! ML_Security::verify_nonce( isset( $_POST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) : '' ) ) {
	unset( $_POST['ml_action'], $_POST['mk_razorpay_order'] );
}

// --- New invoice ---------------------------------------------------------
if ( $ml_can_manage && isset( $_POST['ml_action'] ) && 'create_invoice' === sanitize_key( wp_unslash( $_POST['ml_action'] ) ) ) {

	$ml_search_term = isset( $_POST['billing_patient_search'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_patient_search'] ) ) : '';
	$ml_pid         = isset( $_POST['billing_pid'] ) ? absint( $_POST['billing_pid'] ) : 0;

	if ( ! $ml_pid && '' !== $ml_search_term ) {
		// First step: find the patient, present radio choices. Item rows are
		// preserved so the user only picks a patient and submits again.
		$ml_found = ML_Patient_Repository::list(
			array(
				'per_page' => 10,
				'search'   => $ml_search_term,
				'orderby'  => 'last_name',
				'order'    => 'ASC',
			)
		);
	} else {
		$ml_items = array();
		if ( isset( $_POST['item_desc'], $_POST['item_qty'], $_POST['item_price'], $_POST['item_disc'] ) ) {
			$ml_descs  = (array) $_POST['item_desc'];
			$ml_qtys   = (array) $_POST['item_qty'];
			$ml_prices = (array) $_POST['item_price'];
			$ml_discs  = (array) $_POST['item_disc'];
			foreach ( $ml_descs as $i => $ml_desc ) {
				if ( '' === trim( sanitize_text_field( wp_unslash( $ml_desc ) ) ) ) {
					continue;
				}
				$ml_items[] = array(
					'description' => sanitize_text_field( wp_unslash( $ml_desc ) ),
					'quantity'    => isset( $ml_qtys[ $i ] ) ? floatval( $ml_qtys[ $i ] ) : 1,
					'unit_price'  => isset( $ml_prices[ $i ] ) ? floatval( $ml_prices[ $i ] ) : 0,
					'discount'    => isset( $ml_discs[ $i ] ) ? floatval( $ml_discs[ $i ] ) : 0,
				);
			}
		}
		$ml_result = ML_Invoice_Repository::create(
			array(
				'patient_id'   => $ml_pid,
				'invoice_date' => isset( $_POST['invoice_date'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_date'] ) ) : '',
				'due_date'     => isset( $_POST['due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['due_date'] ) ) : '',
				'items'        => $ml_items,
			)
		);
		if ( is_wp_error( $ml_result ) ) {
			$ml_error = $ml_result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-billing', 'view' => (int) $ml_result, 'ml_notice' => 'invoice_created' ), $ml_base ) );
			exit;
		}
	}
}

// --- Record a manual payment ----------------------------------------------
if ( $ml_can_record && $ml_view && isset( $_POST['ml_action'] ) && 'record_payment' === sanitize_key( wp_unslash( $_POST['ml_action'] ) ) ) {
	$ml_inv = ML_Invoice_Repository::get( $ml_view );
	if ( $ml_inv && ML_Security::can_access_patient( (int) $ml_inv['patient_id'] ) ) {
		$ml_result = ML_Payment_Repository::create(
			array(
				'invoice_id'     => $ml_view,
				'amount'         => isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0,
				'method'         => isset( $_POST['method'] ) ? sanitize_key( wp_unslash( $_POST['method'] ) ) : 'cash',
				'payment_date'   => isset( $_POST['payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) : '',
				'transaction_id' => isset( $_POST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) : '',
				'notes'          => isset( $_POST['payment_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['payment_notes'] ) ) : '',
			)
		);
		if ( is_wp_error( $ml_result ) ) {
			$ml_error = $ml_result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-billing', 'view' => $ml_view, 'ml_notice' => 'payment_recorded' ), $ml_base ) );
			exit;
		}
	}
}

$ml_notice_msg = '';
if ( 'invoice_created' === $ml_notice ) {
	$ml_notice_msg = __( 'Invoice created.', 'ma-lumiere-clinic' );
} elseif ( 'payment_recorded' === $ml_notice ) {
	$ml_notice_msg = __( 'Payment recorded.', 'ma-lumiere-clinic' );
}
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Billing', 'ma-lumiere-clinic' ); ?></h1>
		<p class="ml-clinic-subtitle"><?php esc_html_e( 'Invoices, PDFs, payments and online (Razorpay) checkout. Totals are always recomputed from line items.', 'ma-lumiere-clinic' ); ?></p>
	</div>

	<?php if ( ! empty( $ml_error ) ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $ml_error ); ?></p></div>
	<?php endif; ?>
	<?php if ( '' !== $ml_notice_msg ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $ml_notice_msg ); ?></p></div>
	<?php endif; ?>

	<?php if ( $ml_view ) : ?>

		<?php
		$ml_invoice = ML_Invoice_Repository::get( $ml_view );
		if ( ! $ml_invoice ) :
			?>
			<div class="ml-clinic-empty"><p><?php esc_html_e( 'Invoice not found.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php else : ?>
			<?php
			$ml_patient = ML_Patient_Repository::get( (int) $ml_invoice['patient_id'] );
			$ml_items   = ML_Invoice_Repository::items( $ml_view );
			$ml_balance = (float) $ml_invoice['balance_amount'];
			$ml_due     = $ml_balance > 0.005;
			$ml_pdf     = add_query_arg( array( 'action' => 'ml_invoice_pdf', 'id' => $ml_view ), admin_url( 'admin-ajax.php' ) );
			$ml_state   = array(
				'unpaid'  => array( 'label' => __( 'Unpaid', 'ma-lumiere-clinic' ), 'cls' => 'warning' ),
				'partial' => array( 'label' => __( 'Partially paid', 'ma-lumiere-clinic' ), 'cls' => 'warning' ),
				'paid'    => array( 'label' => __( 'Paid', 'ma-lumiere-clinic' ), 'cls' => 'success' ),
				'void'    => array( 'label' => __( 'Void', 'ma-lumiere-clinic' ), 'cls' => 'muted' ),
			);
			$ml_state_row = isset( $ml_state[ $ml_invoice['payment_status'] ] ) ? $ml_state[ $ml_invoice['payment_status'] ] : $ml_state['unpaid'];
			?>

			<p><a class="button button-link" href="<?php echo esc_url( $ml_self ); ?>">← <?php esc_html_e( 'All invoices', 'ma-lumiere-clinic' ); ?></a></p>

			<h2 class="ml-clinic-section-title">
				<?php echo esc_html( $ml_invoice['invoice_number'] ); ?>
				<span class="ml-badge ml-badge--<?php echo esc_attr( $ml_state_row['cls'] ); ?>"><?php echo esc_html( $ml_state_row['label'] ); ?></span>
			</h2>

			<table class="widefat striped ml-detail-table">
				<tbody>
					<tr><th><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></th>
						<td><?php echo esc_html( $ml_patient ? trim( (string) $ml_patient['first_name'] . ' ' . (string) $ml_patient['last_name'] ) : '—' ); ?>
							<?php if ( $ml_patient ) : ?><span class="ml-muted">(<?php echo esc_html( $ml_patient['patient_uid'] ); ?>)</span>
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-patients', 'view' => (int) $ml_patient['id'] ), $ml_base ) ); ?>"><?php esc_html_e( 'Open patient', 'ma-lumiere-clinic' ); ?></a>
							<?php endif; ?>
						</td></tr>
					<tr><th><?php esc_html_e( 'Invoice date', 'ma-lumiere-clinic' ); ?></th><td><?php echo esc_html( ml_date( $ml_invoice['invoice_date'], 'd M Y' ) ); ?>
						<?php if ( ! empty( $ml_invoice['due_date'] ) ) : ?><span class="ml-muted">— <?php echo esc_html( sprintf( __( 'due %s', 'ma-lumiere-clinic' ), ml_date( $ml_invoice['due_date'], 'd M Y' ) ) ); ?></span><?php endif; ?>
					</td></tr>
					<tr><th><?php esc_html_e( 'Subtotal / discount / tax', 'ma-lumiere-clinic' ); ?></th>
						<td><?php echo esc_html( ml_money( $ml_invoice['subtotal'] ) ); ?> / <?php echo esc_html( ml_money( $ml_invoice['discount'] ) ); ?> / <?php echo esc_html( ml_money( $ml_invoice['tax'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Total', 'ma-lumiere-clinic' ); ?></th><td><strong><?php echo esc_html( ml_money( $ml_invoice['total'] ) ); ?></strong></td></tr>
					<tr><th><?php esc_html_e( 'Paid / Balance', 'ma-lumiere-clinic' ); ?></th>
						<td><?php echo esc_html( ml_money( $ml_invoice['paid_amount'] ) ); ?> / <strong><?php echo esc_html( ml_money( $ml_balance ) ); ?></strong></td></tr>
					<tr><th><?php esc_html_e( 'Created', 'ma-lumiere-clinic' ); ?></th><td><span class="ml-muted"><?php echo esc_html( ml_date( $ml_invoice['created_at'], 'd M Y H:i' ) ); ?></span></td></tr>
				</tbody>
			</table>

			<h2 class="ml-clinic-section-title"><?php esc_html_e( 'Line items', 'ma-lumiere-clinic' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr><th><?php esc_html_e( 'Description', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Qty', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Rate', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Disc', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Amount', 'ma-lumiere-clinic' ); ?></th></tr>
				</thead>
				<tbody>
					<?php if ( $ml_items ) : foreach ( $ml_items as $ml_item ) : ?>
						<tr>
							<td><?php echo esc_html( $ml_item['description'] ); ?></td>
							<td class="ml-col-num"><?php echo esc_html( rtrim( rtrim( number_format( (float) $ml_item['quantity'], 2 ), '0' ), '.' ) ); ?></td>
							<td class="ml-col-num"><?php echo esc_html( ml_money( $ml_item['unit_price'] ) ); ?></td>
							<td class="ml-col-num"><?php echo 0 < (float) $ml_item['discount'] ? esc_html( '−' . ml_money( $ml_item['discount'] ) ) : '—'; ?></td>
							<td class="ml-col-num"><?php echo esc_html( ml_money( $ml_item['line_total'] ) ); ?></td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="5" class="ml-muted"><?php esc_html_e( 'No line items.', 'ma-lumiere-clinic' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>

			<p class="ml-form-actions">
				<a class="button" href="<?php echo esc_url( wp_nonce_url( $ml_pdf, ML_Security::NONCE_ACTION ) ); ?>"><?php esc_html_e( 'Download PDF', 'ma-lumiere-clinic' ); ?></a>
			</p>

			<?php if ( $ml_due && 'void' !== $ml_invoice['payment_status'] ) : ?>

				<?php
				$ml_rz = is_callable( array( 'ML_Payment_Repository', 'gateway_config' ) ) ? ML_Payment_Repository::gateway_config() : new WP_Error( 'x', 'x' );
				?>

				<?php if ( ! is_wp_error( $ml_rz ) && $ml_can_manage ) : ?>
					<p>
						<button type="button" class="button button-primary" id="ml-razorpay-pay"
							data-invoice="<?php echo esc_attr( $ml_view ); ?>"><?php esc_html_e( 'Pay online (Razorpay)', 'ma-lumiere-clinic' ); ?></button>
					</p>
				<?php endif; ?>

				<?php if ( $ml_can_record ) : ?>
					<details class="ml-clinic-add" open>
						<summary><?php esc_html_e( 'Record a manual payment', 'ma-lumiere-clinic' ); ?></summary>
					<form method="post" class="ml-form" action="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-billing', 'view' => $ml_view ), $ml_base ) ); ?>">
						<input type="hidden" name="ml_action" value="record_payment" />
						<div class="ml-form-grid">
							<p class="ml-field">
								<label class="ml-field__label" for="ml-pay-amount"><?php esc_html_e( 'Amount', 'ma-lumiere-clinic' ); ?> <span class="ml-field__req">*</span></label>
								<input id="ml-pay-amount" class="ml-control" type="number" name="amount" step="0.01" min="0.01" max="<?php echo esc_attr( $ml_balance ); ?>" value="<?php echo esc_attr( $ml_balance ); ?>" required />
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-pay-method"><?php esc_html_e( 'Method', 'ma-lumiere-clinic' ); ?></label>
								<select id="ml-pay-method" class="ml-control" name="method">
									<?php foreach ( ML_Payment_Repository::methods() as $ml_method ) :
										if ( 'razorpay' === $ml_method ) { continue; } ?>
										<option value="<?php echo esc_attr( $ml_method ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $ml_method ) ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-pay-date"><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-pay-date" class="ml-control" type="date" name="payment_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-pay-txn"><?php esc_html_e( 'Transaction ref (optional)', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-pay-txn" class="ml-control" type="text" name="transaction_id" />
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-pay-notes"><?php esc_html_e( 'Notes (optional)', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-pay-notes" class="ml-control" name="payment_notes" rows="2"></textarea>
							</p>
						</div>
						<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
						<p class="ml-form-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Record payment', 'ma-lumiere-clinic' ); ?></button>
						</p>
					</form>
					</details>
				<?php endif; ?>

			<?php elseif ( 'void' === $ml_invoice['payment_status'] ) : ?>
				<div class="ml-clinic-empty"><p><?php esc_html_e( 'This invoice is void.', 'ma-lumiere-clinic' ); ?></p></div>
			<?php endif; ?>

		<?php endif; ?>

	<?php else : ?>

		<?php if ( $ml_can_manage ) : ?>
			<details class="ml-clinic-add">
				<summary><?php esc_html_e( 'New invoice', 'ma-lumiere-clinic' ); ?></summary>
				<form method="post" class="ml-form" action="<?php echo esc_url( $ml_self ); ?>">
					<input type="hidden" name="ml_action" value="create_invoice" />

					<div class="ml-form-filter">
						<p class="ml-field">
							<label class="ml-field__label" for="ml-bil-search"><?php esc_html_e( 'Find patient', 'ma-lumiere-clinic' ); ?></label>
							<input id="ml-bil-search" class="ml-control" type="search" name="billing_patient_search" value="<?php echo isset( $_POST['billing_patient_search'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['billing_patient_search'] ) ) ) : ''; ?>"
								placeholder="<?php esc_attr_e( 'Name or UID…', 'ma-lumiere-clinic' ); ?>" />
						</p>
						<p class="ml-field">
							<button type="submit" class="button"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></button>
						</p>
						<?php if ( isset( $_POST['billing_pid'] ) ) :
							$ml_sel = $_POST['billing_pid'] ? ML_Patient_Repository::get( absint( $_POST['billing_pid'] ) ) : null;
							if ( $ml_sel ) : ?>
								<p class="ml-field">
									<span class="ml-field__hint"><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?>:
										<strong><?php echo esc_html( trim( (string) $ml_sel['first_name'] . ' ' . (string) $ml_sel['last_name'] ) ); ?></strong>
										<span class="ml-muted">(<?php echo esc_html( $ml_sel['patient_uid'] ); ?>)</span></span>
								</p>
							<?php endif; ?>
						<?php endif; ?>
					</div>

					<?php if ( isset( $ml_found ) && empty( $ml_pid ) ) : ?>
						<?php if ( empty( $ml_found['items'] ) ) : ?>
							<p class="ml-field__hint"><?php esc_html_e( 'No patients matched. Adjust the search term.', 'ma-lumiere-clinic' ); ?></p>
						<?php else : ?>
							<div class="ml-u-stack ml-field ml-field--wide">
								<span class="ml-field__label"><?php esc_html_e( 'Choose the patient:', 'ma-lumiere-clinic' ); ?></span>
								<ul class="ml-search-results">
									<?php foreach ( $ml_found['items'] as $ml_f ) : ?>
										<li>
											<label class="ml-field--inline">
												<input type="radio" name="billing_pid" value="<?php echo esc_attr( (int) $ml_f['id'] ); ?>" />
												<strong><?php echo esc_html( trim( (string) $ml_f['first_name'] . ' ' . (string) $ml_f['last_name'] ) ); ?></strong>
												<span class="ml-muted">(<?php echo esc_html( $ml_f['patient_uid'] ); ?>)</span>
											</label>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( empty( $ml_found ) ) : ?>
						<input type="hidden" name="billing_pid" value="<?php echo isset( $_POST['billing_pid'] ) ? esc_attr( absint( $_POST['billing_pid'] ) ) : 0; ?>" />
					<?php endif; ?>

					<div class="ml-form-grid">
						<p class="ml-field">
							<label class="ml-field__label" for="ml-inv-date"><?php esc_html_e( 'Invoice date', 'ma-lumiere-clinic' ); ?></label>
							<input id="ml-inv-date" class="ml-control" type="date" name="invoice_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
						</p>
						<p class="ml-field">
							<label class="ml-field__label" for="ml-inv-due"><?php esc_html_e( 'Due date (optional)', 'ma-lumiere-clinic' ); ?></label>
							<input id="ml-inv-due" class="ml-control" type="date" name="due_date" />
						</p>
					</div>

					<p class="ml-field__label"><?php esc_html_e( 'Invoice items', 'ma-lumiere-clinic' ); ?></p>
					<div class="ml-form-repeat__head" aria-hidden="true">
						<span><?php esc_html_e( 'Item / service', 'ma-lumiere-clinic' ); ?></span>
						<span><?php esc_html_e( 'Qty', 'ma-lumiere-clinic' ); ?></span>
						<span><?php esc_html_e( 'Unit price', 'ma-lumiere-clinic' ); ?></span>
						<span><?php esc_html_e( 'Disc', 'ma-lumiere-clinic' ); ?></span>
					</div>
					<div class="ml-invoice-items">
						<?php
						$ml_saved_items = array();
						if ( isset( $_POST['item_desc'] ) ) {
							$ml_saved_q  = isset( $_POST['item_qty'] ) ? (array) $_POST['item_qty'] : array();
							$ml_saved_p  = isset( $_POST['item_price'] ) ? (array) $_POST['item_price'] : array();
							$ml_saved_d  = isset( $_POST['item_disc'] ) ? (array) $_POST['item_disc'] : array();
							foreach ( (array) $_POST['item_desc'] as $ml_i => $ml_d ) {
								$ml_saved_items[] = array(
									'desc'  => sanitize_text_field( wp_unslash( $ml_d ) ),
									'qty'   => isset( $ml_saved_q[ $ml_i ] ) ? floatval( $ml_saved_q[ $ml_i ] ) : 1,
									'price' => isset( $ml_saved_p[ $ml_i ] ) ? floatval( $ml_saved_p[ $ml_i ] ) : 0,
									'disc'  => isset( $ml_saved_d[ $ml_i ] ) ? floatval( $ml_saved_d[ $ml_i ] ) : 0,
								);
							}
						}
						for ( $ml_row = 0; $ml_row < 5; $ml_row++ ) :
							$ml_v = isset( $ml_saved_items[ $ml_row ] ) ? $ml_saved_items[ $ml_row ] : array( 'desc' => '', 'qty' => 1, 'price' => '', 'disc' => '' );
							?>
						<div class="ml-invoice-item ml-form-repeat">
							<input type="text" name="item_desc[]" class="ml-control ml-invoice-desc" value="<?php echo esc_attr( $ml_v['desc'] ); ?>" placeholder="<?php esc_attr_e( 'Item / service', 'ma-lumiere-clinic' ); ?>" />
							<input type="number" name="item_qty[]"  class="ml-control ml-invoice-qty" value="<?php echo esc_attr( $ml_v['qty'] ); ?>" min="0.01" step="any" placeholder="<?php esc_attr_e( 'Qty', 'ma-lumiere-clinic' ); ?>" />
							<input type="number" name="item_price[]" class="ml-control ml-invoice-price" value="<?php echo esc_attr( $ml_v['price'] ); ?>" min="0" step="0.01" placeholder="<?php esc_attr_e( 'Unit price', 'ma-lumiere-clinic' ); ?>" />
							<input type="number" name="item_disc[]"  class="ml-control ml-invoice-disc" value="<?php echo esc_attr( $ml_v['disc'] ); ?>" min="0" step="0.01" placeholder="<?php esc_attr_e( 'Disc', 'ma-lumiere-clinic' ); ?>" />
						</div>
					<?php endfor; ?>
					</div>
					<p class="ml-field__hint">
						<?php echo esc_html(
							sprintf(
								__( 'Tax applies automatically when enabled in Settings (%1$s, %2$s%%). Leave blank rows empty.', 'ma-lumiere-clinic' ),
								(bool) ML_Settings::get( 'billing.tax_enabled', 0 ) ? __( 'enabled', 'ma-lumiere-clinic' ) : __( 'disabled', 'ma-lumiere-clinic' ),
								ML_Settings::get( 'billing.tax_percent', 0 )
							)
						); ?>
					</p>
					<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
					<p class="ml-form-actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Create invoice', 'ma-lumiere-clinic' ); ?></button>
					</p>
				</form>
			</details>
		<?php endif; ?>

		<?php
		$ml_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$ml_from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$ml_to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$ml_s      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$ml_page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$ml_invs   = ML_Invoice_Repository::list(
			array(
				'page'     => $ml_page,
				'per_page' => 20,
				'status'   => $ml_status,
				'from'     => $ml_from,
				'to'       => $ml_to,
				'search'   => $ml_s,
			)
		);
		?>

		<form class="ml-form-filter" method="get">
			<input type="hidden" name="page" value="ml-clinic-billing" />
			<p class="ml-field">
				<label class="ml-field__label" for="ml-inv-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-inv-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_s ); ?>" placeholder="<?php esc_attr_e( 'Search invoice, patient or UID…', 'ma-lumiere-clinic' ); ?>" />
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-inv-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
				<select id="ml-inv-status" name="status" class="ml-control">
					<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( ML_Invoice_Repository::statuses() as $ml_opt ) : ?>
						<option value="<?php echo esc_attr( $ml_opt ); ?>" <?php selected( $ml_status, $ml_opt ); ?>><?php echo esc_html( ucfirst( $ml_opt ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-inv-from"><?php esc_html_e( 'From', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-inv-from" type="date" name="from" class="ml-control" value="<?php echo esc_attr( $ml_from ); ?>" />
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-inv-to"><?php esc_html_e( 'To', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-inv-to" type="date" name="to" class="ml-control" value="<?php echo esc_attr( $ml_to ); ?>" />
			</p>
			<p class="ml-field">
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'ma-lumiere-clinic' ); ?></button>
			</p>
		</form>

		<?php if ( empty( $ml_invs['items'] ) ) : ?>
			<div class="ml-clinic-empty"><p><?php esc_html_e( 'No invoices match.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Number', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Total', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Balance', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ml_invs['items'] as $ml_inv ) :
						$ml_st = isset( $ml_state[ $ml_inv['payment_status'] ] ) ? $ml_state[ $ml_inv['payment_status'] ] : $ml_state['unpaid'];
						?>
						<tr>
							<td><strong><?php echo esc_html( $ml_inv['invoice_number'] ); ?></strong></td>
							<td><?php echo esc_html( ml_date( $ml_inv['invoice_date'], 'd M Y' ) ); ?></td>
							<td><?php echo esc_html( trim( (string) $ml_inv['p_first_name'] . ' ' . (string) $ml_inv['p_last_name'] ) ); ?> <span class="ml-muted">(<?php echo esc_html( $ml_inv['p_uid'] ); ?>)</span></td>
							<td class="ml-col-num"><?php echo esc_html( ml_money( $ml_inv['total'] ) ); ?></td>
							<td class="ml-col-num"><?php echo esc_html( ml_money( $ml_inv['balance_amount'] ) ); ?></td>
							<td><span class="ml-badge ml-badge--<?php echo esc_attr( $ml_st['cls'] ); ?>"><?php echo esc_html( $ml_st['label'] ); ?></span></td>
							<td class="ml-col-actions">
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-billing', 'view' => (int) $ml_inv['id'] ), $ml_base ) ); ?>"><?php esc_html_e( 'View', 'ma-lumiere-clinic' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $ml_invs['pages'] > 1 ) : ?>
				<div class="ml-clinic-pagination">
					<?php if ( $ml_invs['page'] > 1 ) : ?>
						<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-billing', 's' => $ml_s, 'status' => $ml_status, 'from' => $ml_from, 'to' => $ml_to, 'paged' => $ml_invs['page'] - 1 ), $ml_base ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
					<?php endif; ?>
					<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $ml_invs['page'], $ml_invs['pages'] ) ); ?></span>
					<?php if ( $ml_invs['page'] < $ml_invs['pages'] ) : ?>
						<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-billing', 's' => $ml_s, 'status' => $ml_status, 'from' => $ml_from, 'to' => $ml_to, 'paged' => $ml_invs['page'] + 1 ), $ml_base ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	<?php endif; ?>
</div>

<?php if ( $ml_view ) : ?>
	<script>
	(function () {
		if (!(window.MLClinicAdmin && window.MLClinicAdmin.restUrl && window.MLClinicAdmin.restNonce)) { return; }

		/**
		 * Lazily load the Razorpay checkout library and open the pay modal
		 * for an invoice.
		 *
		 * @param {string} orderId Razorpay order id.
		 * @param {Object} cfg     { key_id, amount, currency, receipt }.
		 * @param {Function} onDone Called once the payment modal flow completes.
		 * @return {void}
		 */
		function openRazorpayCashier(orderId, cfg, onDone) {
			if (window.Razorpay) {
				var rz = new window.Razorpay({
					key: cfg.key_id,
					amount: Math.round(cfg.amount * 100),
					currency: cfg.currency,
					name: cfg.receipt ? ('Invoice ' + cfg.receipt) : document.title,
					order_id: orderId,
					handler: onDone,
					modal: { ondismiss: function () { } }
				});
				rz.open();
				return;
			}
			var s = document.createElement('script');
			s.src = 'https://checkout.razorpay.com/v1/checkout.js';
			s.onload = function () { openRazorpayCashier(orderId, cfg, onDone); };
			document.head.appendChild(s);
		}

		function loadCashier(orderId, cfg) {
			return function () {
				openRazorpayCashier(orderId, cfg, function (pay) {
					var vUrl = window.MLClinicAdmin.restUrl + 'invoices/' + cfg.invoiceId + '/razorpay-verify';
					fetch(vUrl, {
						method: 'POST',
						headers: { 'X-WP-Nonce': window.MLClinicAdmin.restNonce, 'Content-Type': 'application/json' },
						body: JSON.stringify({
							razorpay_order_id: pay.razorpay_order_id,
							razorpay_payment_id: pay.razorpay_payment_id,
							razorpay_signature: pay.razorpay_signature
						})
					}).then(function (r) { return r.json(); })
						.then(function () { window.location.reload(); })
						.catch(function () { window.location.reload(); });
				});
			};
		}

		var btn = document.getElementById('ml-razorpay-pay');
		if (!btn) { return; }
		btn.addEventListener('click', function (ev) {
			ev.preventDefault();
			if (btn.getAttribute('data-busy')) { return; }
			btn.setAttribute('data-busy', '1');
			var orderUrl = window.MLClinicAdmin.restUrl + 'invoices/' + btn.getAttribute('data-invoice') + '/razorpay-order';
			fetch(orderUrl, {
				method: 'POST',
				headers: { 'X-WP-Nonce': window.MLClinicAdmin.restNonce }
			}).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
				.then(function (res) {
					btn.removeAttribute('data-busy');
					if (!res.ok) {
						var msg = (res.d && res.d.data && res.d.data.message) ? res.d.data.message : 'Payment could not be started.';
						window.alert(msg);
						return;
					}
					var o = res.d.data;
					loadCashier(o.order_id, {
						key_id: o.key_id,
						amount: o.amount,
						currency: o.currency,
						receipt: o.receipt,
						invoiceId: btn.getAttribute('data-invoice')
					})();
				})
				.catch(function () {
					btn.removeAttribute('data-busy');
					window.alert('Payment could not be started. Use the manual form below.');
				});
		});
	})();
	</script>
<?php endif; ?>