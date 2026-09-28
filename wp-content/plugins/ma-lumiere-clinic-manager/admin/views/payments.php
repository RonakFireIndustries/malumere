<?php
/**
 * Payments — read-only register plus refund/failure follow-ups. Payments are
 * recorded from the invoice detail screen; listing here keeps a single audit
 * trail. An erroneously completed payment can be marked refunded, which
 * recomputes the invoice balance.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_base       = admin_url( 'admin.php' );
$ml_self       = add_query_arg( 'page', 'ml-clinic-payments', $ml_base );
$ml_error      = '';
$ml_notice     = isset( $_GET['ml_notice'] ) ? sanitize_key( wp_unslash( $_GET['ml_notice'] ) ) : '';

if ( isset( $_POST['ml_action'], $_POST['_wpnonce'] )
	&& 'payment_status' === sanitize_key( wp_unslash( $_POST['ml_action'] ) ) ) {
	if ( ML_Security::verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) ) ) {
		$ml_result = ML_Payment_Repository::set_status(
			isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0,
			isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : ''
		);
		if ( is_wp_error( $ml_result ) ) {
			$ml_error = $ml_result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-payments', 'ml_notice' => 'status_updated' ), $ml_base ) );
			exit;
		}
	} else {
		$ml_error = __( 'Security check failed. Please try again.', 'ma-lumiere-clinic' );
	}
}

$ml_notice_msg = '';
if ( 'status_updated' === $ml_notice ) {
	$ml_notice_msg = __( 'Payment status updated.', 'ma-lumiere-clinic' );
}

$ml_method = isset( $_GET['method'] ) ? sanitize_key( wp_unslash( $_GET['method'] ) ) : '';
$ml_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$ml_from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
$ml_to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
$ml_page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

$ml_pays = ML_Payment_Repository::list(
	array(
		'page'     => $ml_page,
		'per_page' => 20,
		'method'   => $ml_method,
		'status'   => $ml_status,
		'from'     => $ml_from,
		'to'       => $ml_to,
	)
);

$ml_status_labels = array(
	'pending'   => __( 'Pending', 'ma-lumiere-clinic' ),
	'completed' => __( 'Completed', 'ma-lumiere-clinic' ),
	'failed'    => __( 'Failed', 'ma-lumiere-clinic' ),
	'refunded'  => __( 'Refunded', 'ma-lumiere-clinic' ),
);
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Payments', 'ma-lumiere-clinic' ); ?></h1>
		<p class="ml-clinic-subtitle"><?php esc_html_e( 'Every payment against an invoice, from any method. Record payments from an invoice; failed or refunded payments here are followed up to keep the trail honest.', 'ma-lumiere-clinic' ); ?></p>
	</div>

	<?php if ( ! empty( $ml_error ) ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $ml_error ); ?></p></div>
	<?php endif; ?>
	<?php if ( '' !== $ml_notice_msg ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $ml_notice_msg ); ?></p></div>
	<?php endif; ?>

	<form class="ml-form-filter" method="get">
		<input type="hidden" name="page" value="ml-clinic-payments" />
		<p class="ml-field">
			<label class="ml-field__label" for="ml-pay-filter-method"><?php esc_html_e( 'Method', 'ma-lumiere-clinic' ); ?></label>
			<select id="ml-pay-filter-method" name="method" class="ml-control">
				<option value=""><?php esc_html_e( 'All methods', 'ma-lumiere-clinic' ); ?></option>
				<?php foreach ( ML_Payment_Repository::methods() as $ml_opt ) : ?>
					<option value="<?php echo esc_attr( $ml_opt ); ?>" <?php selected( $ml_method, $ml_opt ); ?>><?php echo esc_html( ucfirst( str_replace( '_', ' ', $ml_opt ) ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-pay-filter-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
			<select id="ml-pay-filter-status" name="status" class="ml-control">
				<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
				<?php foreach ( array_keys( $ml_status_labels ) as $ml_opt ) : ?>
					<option value="<?php echo esc_attr( $ml_opt ); ?>" <?php selected( $ml_status, $ml_opt ); ?>><?php echo esc_html( $ml_status_labels[ $ml_opt ] ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-pay-filter-from"><?php esc_html_e( 'From', 'ma-lumiere-clinic' ); ?></label>
			<input id="ml-pay-filter-from" type="date" name="from" class="ml-control" value="<?php echo esc_attr( $ml_from ); ?>" />
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-pay-filter-to"><?php esc_html_e( 'To', 'ma-lumiere-clinic' ); ?></label>
			<input id="ml-pay-filter-to" type="date" name="to" class="ml-control" value="<?php echo esc_attr( $ml_to ); ?>" />
		</p>
		<p class="ml-field">
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'ma-lumiere-clinic' ); ?></button>
		</p>
	</form>

	<?php if ( empty( $ml_pays['items'] ) ) : ?>
		<div class="ml-clinic-empty"><p><?php esc_html_e( 'No payments match.', 'ma-lumiere-clinic' ); ?></p></div>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Invoice', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Method', 'ma-lumiere-clinic' ); ?></th>
					<th class="ml-col-num"><?php esc_html_e( 'Amount', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
					<th class="ml-col-actions"><?php esc_html_e( 'Follow-up', 'ma-lumiere-clinic' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $ml_pays['items'] as $ml_pay ) :
					$ml_can_bill = current_user_can( 'ml_manage_clinic' ) || current_user_can( 'ml_view_billing' );
					$ml_st_label = isset( $ml_status_labels[ $ml_pay['payment_status'] ] ) ? $ml_status_labels[ $ml_pay['payment_status'] ] : ucfirst( $ml_pay['payment_status'] );
					$ml_st_cls   = 'completed' === $ml_pay['payment_status'] ? 'success'
						: ( 'refunded' === $ml_pay['payment_status'] ? 'warning'
						: ( 'failed' === $ml_pay['payment_status'] ? 'muted' : 'warning' ) );
					?>
					<tr>
						<td><?php echo esc_html( ml_date( $ml_pay['payment_date'], 'd M Y H:i' ) ); ?></td>
						<td>
							<?php if ( $ml_can_bill ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-billing', 'view' => (int) $ml_pay['invoice_id'] ), $ml_base ) ); ?>">
									<?php echo esc_html( $ml_pay['i_number'] ?: (string) $ml_pay['invoice_id'] ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $ml_pay['i_number'] ?: (string) $ml_pay['invoice_id'] ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( trim( (string) $ml_pay['p_first_name'] . ' ' . (string) $ml_pay['p_last_name'] ) ); ?>
							<?php if ( ! empty( $ml_pay['p_uid'] ) ) : ?><span class="ml-muted">(<?php echo esc_html( $ml_pay['p_uid'] ); ?>)</span><?php endif; ?></td>
						<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $ml_pay['payment_method'] ) ) ); ?>
							<?php if ( ! empty( $ml_pay['gateway'] ) ) : ?><span class="ml-muted">· <?php echo esc_html( $ml_pay['gateway'] ); ?></span><?php endif; ?></td>
						<td class="ml-col-num"><?php echo esc_html( ml_money( $ml_pay['amount'] ) ); ?></td>
						<td><span class="ml-badge ml-badge--<?php echo esc_attr( $ml_st_cls ); ?>"><?php echo esc_html( $ml_st_label ); ?></span></td>
						<td class="ml-col-actions">
							<?php if ( 'completed' === $ml_pay['payment_status'] && ( current_user_can( 'ml_manage_clinic' ) || current_user_can( 'ml_record_payments' ) ) ) : ?>
							<form method="post" class="ml-form-inline">
								<input type="hidden" name="ml_action" value="payment_status" />
								<input type="hidden" name="payment_id" value="<?php echo esc_attr( (int) $ml_pay['id'] ); ?>" />
								<input type="hidden" name="status" value="refunded" />
								<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
								<button type="submit" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Mark this payment as refunded? The invoice balance will be recalculated.', 'ma-lumiere-clinic' ) ); ?>');"><?php esc_html_e( 'Refund', 'ma-lumiere-clinic' ); ?></button>
							</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $ml_pays['pages'] > 1 ) : ?>
			<div class="ml-clinic-pagination">
				<?php if ( $ml_pays['page'] > 1 ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-payments', 'method' => $ml_method, 'status' => $ml_status, 'from' => $ml_from, 'to' => $ml_to, 'paged' => $ml_pays['page'] - 1 ), $ml_base ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
				<?php endif; ?>
				<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $ml_pays['page'], $ml_pays['pages'] ) ); ?></span>
				<?php if ( $ml_pays['page'] < $ml_pays['pages'] ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-payments', 'method' => $ml_method, 'status' => $ml_status, 'from' => $ml_from, 'to' => $ml_to, 'paged' => $ml_pays['page'] + 1 ), $ml_base ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>