<?php
/**
 * Inventory screen: medicine catalogue, batch list, stock ledger, alerts and
 * the per-medicine stock card. Routed by ML_Inventory_Controller.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_base     = admin_url( 'admin.php' );
$ml_page_url = add_query_arg( 'page', ML_Inventory_Controller::PAGE, $ml_base );
$ml_can_manage = current_user_can( 'ml_manage_inventory' );
$ml_tabs     = isset( $tabs ) ? $tabs : ML_Inventory_Controller::tabs();
$ml_view     = isset( $view ) ? $view : 'medicines';
$ml_nonce    = wp_create_nonce( ML_Security::NONCE_ACTION );

/**
 * Build an inventory URL with extra query args.
 *
 * @param array $args Extra args.
 *
 * @return string
 */
$ml_url = static function ( array $args = array() ) use ( $ml_page_url ) {
	return add_query_arg( $args, $ml_page_url );
};
?>

<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Inventory', 'ma-lumiere-clinic' ); ?></h1>
		<div class="ml-clinic-header__actions">
			<?php if ( $ml_can_manage ) : ?>
				<a class="button" href="<?php echo esc_url( $ml_url( array( 'action' => 'receive', 'id' => 0 ) ) ); ?>">
					<?php esc_html_e( 'Receive Stock', 'ma-lumiere-clinic' ); ?>
				</a>
				<a class="button button-primary" href="<?php echo esc_url( $ml_url( array( 'action' => 'new_medicine' ) ) ); ?>">
					<?php esc_html_e( 'Add Medicine', 'ma-lumiere-clinic' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php include ML_CLINIC_PATH . 'admin/views/_inventory-notice.php'; ?>

	<?php if ( isset( $summary ) && 'stockcard' !== $ml_view ) : ?>
		<div class="ml-clinic-cards">
			<div class="ml-clinic-card">
				<span class="ml-clinic-card__label"><?php esc_html_e( 'Active medicines', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $summary['medicines'] ) ); ?></span>
			</div>
			<div class="ml-clinic-card">
				<span class="ml-clinic-card__label"><?php esc_html_e( 'Units on hand', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $summary['units_on_hand'] ) ); ?></span>
			</div>
			<div class="ml-clinic-card">
				<span class="ml-clinic-card__label"><?php esc_html_e( 'Stock value', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-card__value"><?php echo esc_html( ml_money( $summary['stock_value'] ) ); ?></span>
			</div>
			<div class="ml-clinic-card">
				<span class="ml-clinic-card__label"><?php esc_html_e( 'Dispensed to date', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $summary['dispensed'] ) ); ?></span>
			</div>
			<div class="ml-clinic-card">
				<span class="ml-clinic-card__label"><?php esc_html_e( 'Low stock', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $summary['low_stock'] ) ); ?></span>
			</div>
			<div class="ml-clinic-card">
				<span class="ml-clinic-card__label"><?php esc_html_e( 'Out of stock', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $summary['out_of_stock'] ) ); ?></span>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( 'stockcard' !== $ml_view ) : ?>
		<nav class="ml-appt-tabs ml-clinic-toolbar">
			<?php foreach ( $ml_tabs as $ml_key => $ml_label ) : ?>
				<?php $ml_badge = 0; ?>
				<?php
				if ( 'alerts' === $ml_key && isset( $alerts ) ) {
					$ml_badge = count( $alerts['out_of_stock'] ) + count( $alerts['low_stock'] ) + count( $alerts['expiring_soon'] ) + count( $alerts['expired'] );
				}
				?>
				<a class="ml-appt-tab <?php echo esc_attr( $ml_view === $ml_key ? 'is-active' : '' ); ?>"
					href="<?php echo esc_url( $ml_url( array( 'tab' => $ml_key ) ) ); ?>">
					<?php echo esc_html( $ml_label ); ?>
					<?php if ( $ml_badge > 0 ) : ?>
						<span class="ml-badge ml-badge--warning"><?php echo esc_html( number_format_i18n( $ml_badge ) ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php
	if ( 'stockcard' === $ml_view ) {
		$ml_batches = $card['batches'];
		$ml_history = $card['history'];
		?>

		<div class="ml-clinic-header">
			<h2 class="ml-clinic-subtitle">
				<?php
				echo esc_html(
					$medicine['display_name'] . ( $medicine['sku'] ? ' (' . $medicine['sku'] . ')' : '' )
				);
				?>
			</h2>
			<a class="button" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Back to inventory', 'ma-lumiere-clinic' ); ?></a>
		</div>

		<div class="ml-clinic-summary">
			<div class="ml-clinic-summary__item">
				<span class="ml-clinic-summary__label"><?php esc_html_e( 'On hand', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-summary__value"><?php echo esc_html( number_format_i18n( (int) $medicine['stock_available'] ) ); ?></span>
			</div>
			<div class="ml-clinic-summary__item">
				<span class="ml-clinic-summary__label"><?php esc_html_e( 'Reorder level', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-summary__value"><?php echo esc_html( number_format_i18n( (int) $medicine['reorder_level'] ) ); ?></span>
			</div>
			<div class="ml-clinic-summary__item">
				<span class="ml-clinic-summary__label"><?php esc_html_e( 'Batch value', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-summary__value"><?php echo esc_html( ml_money( $card['value'] ) ); ?></span>
			</div>
			<div class="ml-clinic-summary__item">
				<span class="ml-clinic-summary__label"><?php esc_html_e( 'Next expiry', 'ma-lumiere-clinic' ); ?></span>
				<span class="ml-clinic-summary__value">
					<?php echo esc_html( $medicine['next_expiry'] ? ml_date( $medicine['next_expiry'] ) : __( 'Not dated', 'ma-lumiere-clinic' ) ); ?>
				</span>
			</div>
		</div>

		<?php if ( $ml_can_manage ) : ?>
			<div class="ml-form-actions">
				<a class="button button-primary" href="<?php echo esc_url( $ml_url( array( 'action' => 'receive', 'id' => (int) $medicine['id'] ) ) ); ?>">
					<?php esc_html_e( 'Receive stock', 'ma-lumiere-clinic' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( $ml_url( array( 'action' => 'edit_medicine', 'id' => (int) $medicine['id'] ) ) ); ?>">
					<?php esc_html_e( 'Edit details', 'ma-lumiere-clinic' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<h3 class="ml-clinic-section-title"><?php esc_html_e( 'Batches', 'ma-lumiere-clinic' ); ?></h3>
		<?php if ( empty( $ml_batches ) ) : ?>
			<div class="ml-clinic-empty">
				<p><?php esc_html_e( 'No stock has been received for this medicine yet.', 'ma-lumiere-clinic' ); ?></p>
			</div>
		<?php else : ?>
			<table class="widefat striped ml-clinic-list-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Batch', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Expiry', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Received', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Available', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Unit cost', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $ml_batches as $ml_b ) :
						$ml_b_state = ML_Medicine_Batch_Repository::expiry_state( $ml_b );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $ml_b['batch_number'] ? $ml_b['batch_number'] : __( 'No batch no.', 'ma-lumiere-clinic' ) ); ?></strong>
								<?php if ( $ml_b['supplier'] ) : ?><br /><span class="ml-muted"><?php echo esc_html( $ml_b['supplier'] ); ?></span><?php endif; ?>
							</td>
							<td>
								<?php if ( $ml_b['expiry_date'] ) : ?>
									<?php echo esc_html( ml_date( $ml_b['expiry_date'] ) ); ?>
									<?php if ( 'expired' === $ml_b_state ) : ?>
										<span class="ml-badge ml-badge--danger"><?php esc_html_e( 'Expired', 'ma-lumiere-clinic' ); ?></span>
									<?php elseif ( 'critical' === $ml_b_state ) : ?>
										<span class="ml-badge ml-badge--warning"><?php echo esc_html( sprintf( _n( '%d day', '%d days', (int) $ml_b['days_to_expiry'], 'ma-lumiere-clinic' ), (int) $ml_b['days_to_expiry'] ) ); ?></span>
									<?php endif; ?>
								<?php else : ?>
									<span class="ml-muted"><?php esc_html_e( 'Not dated', 'ma-lumiere-clinic' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( (int) $ml_b['quantity_received'] ); ?></td>
							<td><strong><?php echo esc_html( (int) $ml_b['quantity_available'] ); ?></strong></td>
							<td><?php echo esc_html( ml_money( $ml_b['unit_cost'] ) ); ?></td>
							<td>
								<span class="ml-badge ml-badge--<?php echo esc_attr( 'disposed' === $ml_b['status'] || 'quarantined' === $ml_b['status'] ? 'info' : ( 'depleted' === $ml_b['status'] || 'expired' === $ml_b['status'] ? 'muted' : 'success' ) ); ?>">
									<?php echo esc_html( ML_Medicine_Batch_Repository::status_labels()[ $ml_b['status'] ] ?? $ml_b['status'] ); ?>
								</span>
							</td>
							<td class="ml-col-actions">
								<div class="ml-inline-actions">
									<?php if ( $ml_can_manage ) : ?>
										<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'action' => 'edit_batch', 'batch' => (int) $ml_b['id'] ) ) ); ?>">
											<?php esc_html_e( 'Edit', 'ma-lumiere-clinic' ); ?>
										</a>
										<?php if ( (int) $ml_b['quantity_available'] > 0 ) : ?>
											<form method="post" class="ml-form-inline">
												<input type="hidden" name="ml_action" value="adjust_stock" />
												<input type="hidden" name="batch_id" value="<?php echo esc_attr( (int) $ml_b['id'] ); ?>" />
												<input type="hidden" name="movement_type" value="wastage" />
												<input type="hidden" name="quantity" value="-1" />
												<input type="hidden" name="notes" value="<?php esc_attr_e( 'Wastage / damaged unit', 'ma-lumiere-clinic' ); ?>" />
												<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
												<button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Write off 1 unit of this batch as wastage?', 'ma-lumiere-clinic' ) ); ?>');">
													<?php esc_html_e( 'Write off 1', 'ma-lumiere-clinic' ); ?>
												</button>
											</form>
										<?php endif; ?>
										<?php if ( 'active' !== $ml_b['status'] && (int) $ml_b['quantity_available'] > 0 ) : ?>
											<form method="post" class="ml-form-inline">
												<input type="hidden" name="ml_action" value="set_batch_status" />
												<input type="hidden" name="batch_id" value="<?php echo esc_attr( (int) $ml_b['id'] ); ?>" />
												<input type="hidden" name="status" value="active" />
												<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
												<button class="button button-small"><?php esc_html_e( 'Return to stock', 'ma-lumiere-clinic' ); ?></button>
											</form>
										<?php elseif ( 'active' === $ml_b['status'] && (int) $ml_b['quantity_available'] > 0 ) : ?>
											<form method="post" class="ml-form-inline">
												<input type="hidden" name="ml_action" value="set_batch_status" />
												<input type="hidden" name="batch_id" value="<?php echo esc_attr( (int) $ml_b['id'] ); ?>" />
												<input type="hidden" name="status" value="quarantined" />
												<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
												<button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Quarantine this batch? It will no longer be dispensed.', 'ma-lumiere-clinic' ) ); ?>');">
													<?php esc_html_e( 'Quarantine', 'ma-lumiere-clinic' ); ?>
												</button>
											</form>
										<?php endif; ?>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h3 class="ml-clinic-section-title"><?php esc_html_e( 'Recent stock movements', 'ma-lumiere-clinic' ); ?></h3>
		<?php if ( empty( $ml_history ) ) : ?>
			<div class="ml-clinic-empty">
				<p><?php esc_html_e( 'No stock movements recorded for this medicine yet.', 'ma-lumiere-clinic' ); ?></p>
			</div>
		<?php else : ?>
			<table class="widefat striped ml-clinic-list-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Type', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Batch', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Change', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Balance', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ml_history as $ml_mv ) : ?>
						<tr>
							<td><?php echo esc_html( ml_date( $ml_mv['created_at'] ) . ' ' . gmdate( 'H:i', strtotime( (string) $ml_mv['created_at'] ) ) ); ?></td>
							<td>
								<span class="ml-badge ml-badge--<?php echo esc_attr( $ml_mv['type_tone'] ); ?>">
									<?php echo esc_html( $ml_mv['type_label'] ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $ml_mv['b_batch_number'] ? $ml_mv['b_batch_number'] : '—' ); ?></td>
							<td class="ml-col-num">
								<strong><?php echo esc_html( ( $ml_mv['quantity'] > 0 ? '+' : '' ) . (int) $ml_mv['quantity'] ); ?></strong>
							</td>
							<td class="ml-col-num"><?php echo esc_html( (int) $ml_mv['balance_after'] ); ?></td>
							<td><span class="ml-muted"><?php echo esc_html( $ml_mv['notes'] ? $ml_mv['notes'] : '—' ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	<?php } elseif ( 'batches' === $ml_view ) {

		$ml_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$ml_status = isset( $_GET['ml_status'] ) ? sanitize_key( wp_unslash( $_GET['ml_status'] ) ) : '';
		$ml_med    = isset( $_GET['medicine_id'] ) ? absint( $_GET['medicine_id'] ) : 0;
		$ml_page_n = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		$ml_result = ML_Medicine_Batch_Repository::list(
			array(
				'page'       => $ml_page_n,
				'per_page'   => 20,
				'search'     => $ml_search,
				'status'     => $ml_status,
				'medicine_id' => $ml_med,
			)
		);
		?>
		<form class="ml-form-filter" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( ML_Inventory_Controller::PAGE ); ?>" />
			<input type="hidden" name="tab" value="batches" />
			<p class="ml-field">
				<label class="ml-field__label" for="ml-b-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-b-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_search ); ?>"
					placeholder="<?php esc_attr_e( 'Medicine, batch number or supplier…', 'ma-lumiere-clinic' ); ?>" />
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-b-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
				<select id="ml-b-status" name="ml_status" class="ml-control">
					<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( ML_Medicine_Batch_Repository::status_labels() as $ml_key => $ml_label ) : ?>
						<option value="<?php echo esc_attr( $ml_key ); ?>" <?php selected( $ml_status, $ml_key ); ?>><?php echo esc_html( $ml_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ml-field">
				<button type="submit" class="button"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></button>
			</p>
			<?php if ( '' !== $ml_search || '' !== $ml_status || $ml_med ) : ?>
				<p class="ml-field"><a class="button button-link" href="<?php echo esc_url( $ml_url( array( 'tab' => 'batches' ) ) ); ?>"><?php esc_html_e( 'Reset', 'ma-lumiere-clinic' ); ?></a></p>
			<?php endif; ?>
		</form>

		<?php if ( empty( $ml_result['items'] ) ) : ?>
			<div class="ml-clinic-empty">
				<p><?php esc_html_e( 'No batches recorded yet. Receive stock to open the first one.', 'ma-lumiere-clinic' ); ?></p>
			</div>
		<?php else : ?>
			<table class="widefat striped ml-clinic-list-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Medicine', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Batch', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Expiry', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Available', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ml_result['items'] as $ml_b ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( $ml_url( array( 'action' => 'stockcard', 'id' => (int) $ml_b['medicine_id'] ) ) ); ?>">
									<?php echo esc_html( $ml_b['medicine_label'] ); ?>
								</a>
							</td>
							<td>
								<?php echo esc_html( $ml_b['batch_number'] ? $ml_b['batch_number'] : '—' ); ?>
								<?php if ( $ml_b['m_sku'] ) : ?><br /><span class="ml-muted"><?php echo esc_html( $ml_b['m_sku'] ); ?></span><?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( $ml_b['expiry_date'] ? ml_date( $ml_b['expiry_date'] ) : '—' ); ?>
								<?php if ( 'expired' === $ml_b['expiry_state'] ) : ?>
									<span class="ml-badge ml-badge--danger"><?php esc_html_e( 'Expired', 'ma-lumiere-clinic' ); ?></span>
								<?php elseif ( 'critical' === $ml_b['expiry_state'] ) : ?>
									<span class="ml-badge ml-badge--warning"><?php esc_html_e( 'Soon', 'ma-lumiere-clinic' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<strong><?php echo esc_html( (int) $ml_b['quantity_available'] ); ?></strong>
								<span class="ml-muted">/ <?php echo esc_html( (int) $ml_b['quantity_received'] ); ?></span>
							</td>
							<td>
								<span class="ml-badge ml-badge--<?php echo esc_attr( 'active' === $ml_b['status'] ? 'success' : 'muted' ); ?>">
									<?php echo esc_html( ML_Medicine_Batch_Repository::status_labels()[ $ml_b['status'] ] ?? $ml_b['status'] ); ?>
								</span>
							</td>
							<td class="ml-col-actions">
								<div class="ml-inline-actions">
									<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'action' => 'stockcard', 'id' => (int) $ml_b['medicine_id'] ) ) ); ?>">
										<?php esc_html_e( 'View', 'ma-lumiere-clinic' ); ?>
									</a>
									<?php if ( $ml_can_manage ) : ?>
										<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'action' => 'edit_batch', 'batch' => (int) $ml_b['id'] ) ) ); ?>">
											<?php esc_html_e( 'Edit', 'ma-lumiere-clinic' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $ml_result['pages'] > 1 ) : ?>
				<div class="ml-clinic-pagination">
					<?php if ( $ml_result['page'] > 1 ) : ?>
						<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'tab' => 'batches', 'paged' => $ml_result['page'] - 1, 's' => $ml_search, 'ml_status' => $ml_status ) ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
					<?php endif; ?>
					<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $ml_result['page'], $ml_result['pages'] ) ); ?></span>
					<?php if ( $ml_result['page'] < $ml_result['pages'] ) : ?>
						<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'tab' => 'batches', 'paged' => $ml_result['page'] + 1, 's' => $ml_search, 'ml_status' => $ml_status ) ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	<?php } elseif ( 'movements' === $ml_view ) {

		$ml_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$ml_type   = isset( $_GET['ml_type'] ) ? sanitize_key( wp_unslash( $_GET['ml_type'] ) ) : '';
		$ml_med    = isset( $_GET['medicine_id'] ) ? absint( $_GET['medicine_id'] ) : 0;
		$ml_from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$ml_to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$ml_page_n = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		$ml_result = ML_Stock_Movement_Repository::list(
			array(
				'page'          => $ml_page_n,
				'per_page'      => 30,
				'search'        => $ml_search,
				'movement_type' => $ml_type,
				'medicine_id'   => $ml_med,
				'from'          => $ml_from,
				'to'            => $ml_to,
			)
		);
		?>
		<form class="ml-form-filter" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( ML_Inventory_Controller::PAGE ); ?>" />
			<input type="hidden" name="tab" value="movements" />
			<p class="ml-field">
				<label class="ml-field__label" for="ml-m-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-m-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_search ); ?>"
					placeholder="<?php esc_attr_e( 'Medicine, batch or note…', 'ma-lumiere-clinic' ); ?>" />
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-m-type"><?php esc_html_e( 'Movement type', 'ma-lumiere-clinic' ); ?></label>
				<select id="ml-m-type" name="ml_type" class="ml-control">
					<option value=""><?php esc_html_e( 'All movement types', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( ML_Stock_Movement_Repository::types() as $ml_key => $ml_def ) : ?>
						<option value="<?php echo esc_attr( $ml_key ); ?>" <?php selected( $ml_type, $ml_key ); ?>><?php echo esc_html( $ml_def['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-m-from"><?php esc_html_e( 'From', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-m-from" type="date" name="from" class="ml-control" value="<?php echo esc_attr( $ml_from ); ?>" />
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-m-to"><?php esc_html_e( 'To', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-m-to" type="date" name="to" class="ml-control" value="<?php echo esc_attr( $ml_to ); ?>" />
			</p>
			<p class="ml-field">
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'ma-lumiere-clinic' ); ?></button>
			</p>
			<?php if ( '' !== $ml_search || '' !== $ml_type || $ml_med || '' !== $ml_from || '' !== $ml_to ) : ?>
				<p class="ml-field"><a class="button button-link" href="<?php echo esc_url( $ml_url( array( 'tab' => 'movements' ) ) ); ?>"><?php esc_html_e( 'Reset', 'ma-lumiere-clinic' ); ?></a></p>
			<?php endif; ?>
		</form>

		<?php if ( empty( $ml_result['items'] ) ) : ?>
			<div class="ml-clinic-empty">
				<p><?php esc_html_e( 'No stock movements match your filters.', 'ma-lumiere-clinic' ); ?></p>
			</div>
		<?php else : ?>
			<table class="widefat striped ml-clinic-list-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Medicine', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Type', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Batch', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Change', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Balance', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Reference', 'ma-lumiere-clinic' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ml_result['items'] as $ml_mv ) : ?>
						<tr>
							<td><?php echo esc_html( ml_date( $ml_mv['created_at'] ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( $ml_url( array( 'action' => 'stockcard', 'id' => (int) $ml_mv['medicine_id'] ) ) ); ?>">
									<?php echo esc_html( $ml_mv['medicine_label'] ); ?>
								</a>
							</td>
							<td>
								<span class="ml-badge ml-badge--<?php echo esc_attr( $ml_mv['type_tone'] ); ?>">
									<?php echo esc_html( $ml_mv['type_label'] ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $ml_mv['b_batch_number'] ? $ml_mv['b_batch_number'] : '—' ); ?></td>
							<td class="ml-col-num">
								<strong><?php echo esc_html( ( $ml_mv['quantity'] > 0 ? '+' : '' ) . (int) $ml_mv['quantity'] ); ?></strong>
							</td>
							<td class="ml-col-num"><?php echo esc_html( (int) $ml_mv['balance_after'] ); ?></td>
							<td>
								<?php if ( 'prescription' === $ml_mv['reference_type'] && $ml_mv['r_prescription_number'] ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-prescriptions', 's' => $ml_mv['r_prescription_number'] ), admin_url( 'admin.php' ) ) ); ?>">
										<?php echo esc_html( $ml_mv['r_prescription_number'] ); ?>
									</a>
								<?php elseif ( $ml_mv['notes'] ) : ?>
									<span class="ml-muted"><?php echo esc_html( $ml_mv['notes'] ); ?></span>
								<?php else : ?>
									<span class="ml-muted">—</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $ml_result['pages'] > 1 ) : ?>
				<div class="ml-clinic-pagination">
					<?php if ( $ml_result['page'] > 1 ) : ?>
						<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'tab' => 'movements', 'paged' => $ml_result['page'] - 1, 's' => $ml_search, 'ml_type' => $ml_type, 'from' => $ml_from, 'to' => $ml_to ) ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
					<?php endif; ?>
					<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $ml_result['page'], $ml_result['pages'] ) ); ?></span>
					<?php if ( $ml_result['page'] < $ml_result['pages'] ) : ?>
						<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'tab' => 'movements', 'paged' => $ml_result['page'] + 1, 's' => $ml_search, 'ml_type' => $ml_type, 'from' => $ml_from, 'to' => $ml_to ) ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	<?php } elseif ( 'alerts' === $ml_view ) {

		$ml_expired_units = isset( $summary['expired_on_hand'] ) ? (int) $summary['expired_on_hand'] : 0;
		?>
		<?php if ( $ml_can_manage && $ml_expired_units > 0 ) : ?>
			<form method="post" class="ml-form-actions">
				<input type="hidden" name="ml_action" value="write_off_expired" />
				<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
				<button type="submit" class="button button-primary" onclick="return confirm('<?php echo esc_js( __( 'Write off all expired units currently held in active batches? This cannot be undone.', 'ma-lumiere-clinic' ) ); ?>');">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: units. */
							__( 'Write off %d expired units', 'ma-lumiere-clinic' ),
							$ml_expired_units
						)
					);
					?>
				</button>
			</form>
		<?php endif; ?>

		<?php
		$ml_sections = array(
			'out_of_stock'  => array(
				'label'  => __( 'Out of stock', 'ma-lumiere-clinic' ),
				'items'  => $alerts['out_of_stock'],
				'tone'   => 'danger',
				'empty'  => __( 'Every active medicine has stock on hand.', 'ma-lumiere-clinic' ),
			),
			'low_stock'     => array(
				'label'  => __( 'At or below reorder level', 'ma-lumiere-clinic' ),
				'items'  => $alerts['low_stock'],
				'tone'   => 'warning',
				'empty'  => __( 'No medicine has fallen to its reorder level.', 'ma-lumiere-clinic' ),
			),
			'expiring_soon' => array(
				'label'  => __( 'Expiring soon', 'ma-lumiere-clinic' ),
				'items'  => $alerts['expiring_soon'],
				'tone'   => 'warning',
				'empty'  => __( 'No batch expires in the next 90 days.', 'ma-lumiere-clinic' ),
			),
			'expired'       => array(
				'label'  => __( 'Expired but still in stock', 'ma-lumiere-clinic' ),
				'items'  => $alerts['expired'],
				'tone'   => 'danger',
				'empty'  => __( 'No expired stock is currently held.', 'ma-lumiere-clinic' ),
			),
		);
		?>

		<?php foreach ( $ml_sections as $ml_key => $ml_section ) : ?>
			<h3 class="ml-clinic-section-title">
				<?php echo esc_html( $ml_section['label'] ); ?>
				<span class="ml-badge ml-badge--<?php echo esc_attr( $ml_section['tone'] ); ?>">
					<?php echo esc_html( number_format_i18n( count( $ml_section['items'] ) ) ); ?>
				</span>
			</h3>

			<?php if ( empty( $ml_section['items'] ) ) : ?>
				<div class="ml-clinic-empty"><p><?php echo esc_html( $ml_section['empty'] ); ?></p></div>
			<?php else : ?>
				<table class="widefat striped ml-clinic-list-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Medicine', 'ma-lumiere-clinic' ); ?></th>
							<?php if ( in_array( $ml_key, array( 'expiring_soon', 'expired' ), true ) ) : ?>
								<th><?php esc_html_e( 'Batch', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Expiry', 'ma-lumiere-clinic' ); ?></th>
							<?php endif; ?>
							<th class="ml-col-num"><?php esc_html_e( 'On hand', 'ma-lumiere-clinic' ); ?></th>
							<?php if ( in_array( $ml_key, array( 'out_of_stock', 'low_stock' ), true ) ) : ?>
								<th class="ml-col-num"><?php esc_html_e( 'Reorder at', 'ma-lumiere-clinic' ); ?></th>
							<?php endif; ?>
							<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $ml_section['items'] as $ml_row ) : ?>
							<?php
							/*
							 * Expiry sections carry batch rows (medicine_id +
							 * medicine_label), while stock sections carry medicine
							 * rows (id + display_name). Normalise both shapes so
							 * the shared markup below works for either.
							 */
							$ml_row_id    = isset( $ml_row['medicine_id'] ) ? (int) $ml_row['medicine_id'] : (int) $ml_row['id'];
							$ml_row_label = isset( $ml_row['medicine_label'] ) ? $ml_row['medicine_label'] : $ml_row['display_name'];
							?>
							<tr>
								<td>
									<a href="<?php echo esc_url( $ml_url( array( 'action' => 'stockcard', 'id' => $ml_row_id ) ) ); ?>">
										<?php echo esc_html( $ml_row_label ); ?>
									</a>
								</td>
								<?php if ( in_array( $ml_key, array( 'expiring_soon', 'expired' ), true ) ) : ?>
									<td><?php echo esc_html( $ml_row['batch_number'] ? $ml_row['batch_number'] : '—' ); ?></td>
									<td>
										<?php echo esc_html( $ml_row['expiry_date'] ? ml_date( $ml_row['expiry_date'] ) : '—' ); ?>
										<?php if ( 'expired' === $ml_row['expiry_state'] ) : ?>
											<span class="ml-badge ml-badge--danger"><?php esc_html_e( 'Expired', 'ma-lumiere-clinic' ); ?></span>
										<?php elseif ( 'critical' === $ml_row['expiry_state'] ) : ?>
											<span class="ml-badge ml-badge--warning"><?php esc_html_e( 'Soon', 'ma-lumiere-clinic' ); ?></span>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td class="ml-col-num">
									<strong><?php echo esc_html( (int) $ml_row['quantity_available'] ); ?></strong>
								</td>
								<?php if ( in_array( $ml_key, array( 'out_of_stock', 'low_stock' ), true ) ) : ?>
									<td class="ml-col-num"><?php echo esc_html( (int) $ml_row['reorder_level'] ); ?></td>
								<?php endif; ?>
								<td class="ml-col-actions">
									<div class="ml-inline-actions">
										<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'action' => 'stockcard', 'id' => $ml_row_id ) ) ); ?>">
											<?php esc_html_e( 'View', 'ma-lumiere-clinic' ); ?>
										</a>
										<?php if ( $ml_can_manage ) : ?>
											<a class="button button-small button-primary" href="<?php echo esc_url( $ml_url( array( 'action' => 'receive', 'id' => $ml_row_id ) ) ); ?>">
												<?php esc_html_e( 'Receive', 'ma-lumiere-clinic' ); ?>
											</a>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php endforeach; ?>

	<?php } else {

		$ml_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$ml_status = isset( $_GET['ml_status'] ) ? sanitize_key( wp_unslash( $_GET['ml_status'] ) ) : '';
		$ml_form   = isset( $_GET['form'] ) ? sanitize_key( wp_unslash( $_GET['form'] ) ) : '';
		$ml_cat    = isset( $_GET['category'] ) ? sanitize_key( wp_unslash( $_GET['category'] ) ) : '';
		$ml_stock  = isset( $_GET['stock'] ) ? sanitize_key( wp_unslash( $_GET['stock'] ) ) : '';
		$ml_page_n = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		$ml_result = ML_Medicine_Repository::list(
			array(
				'page'     => $ml_page_n,
				'per_page' => 20,
				'search'   => $ml_search,
				'status'   => $ml_status,
				'form'     => $ml_form,
				'category' => $ml_cat,
				'stock'    => $ml_stock,
			)
		);
		?>
		<form class="ml-form-filter" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( ML_Inventory_Controller::PAGE ); ?>" />
			<input type="hidden" name="tab" value="medicines" />
			<p class="ml-field">
				<label class="ml-field__label" for="ml-med-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-med-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_search ); ?>"
					placeholder="<?php esc_attr_e( 'Name, generic name or code…', 'ma-lumiere-clinic' ); ?>" />
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-med-category"><?php esc_html_e( 'Category', 'ma-lumiere-clinic' ); ?></label>
				<select id="ml-med-category" name="category" class="ml-control">
					<option value=""><?php esc_html_e( 'All categories', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( ML_Medicine_Repository::categories() as $ml_c ) : ?>
						<option value="<?php echo esc_attr( $ml_c ); ?>" <?php selected( $ml_cat, $ml_c ); ?>><?php echo esc_html( ucfirst( str_replace( '-', ' ', $ml_c ) ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-med-form"><?php esc_html_e( 'Form', 'ma-lumiere-clinic' ); ?></label>
				<select id="ml-med-form" name="form" class="ml-control">
					<option value=""><?php esc_html_e( 'All forms', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( ML_Medicine_Repository::forms() as $ml_f ) : ?>
						<option value="<?php echo esc_attr( $ml_f ); ?>" <?php selected( $ml_form, $ml_f ); ?>><?php echo esc_html( ucfirst( $ml_f ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-med-stock"><?php esc_html_e( 'Stock', 'ma-lumiere-clinic' ); ?></label>
				<select id="ml-med-stock" name="stock" class="ml-control">
					<option value=""><?php esc_html_e( 'Any stock', 'ma-lumiere-clinic' ); ?></option>
					<option value="low" <?php selected( $ml_stock, 'low' ); ?>><?php esc_html_e( 'Low stock', 'ma-lumiere-clinic' ); ?></option>
					<option value="out" <?php selected( $ml_stock, 'out' ); ?>><?php esc_html_e( 'Out of stock', 'ma-lumiere-clinic' ); ?></option>
				</select>
			</p>
			<p class="ml-field">
				<label class="ml-field__label" for="ml-med-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
				<select id="ml-med-status" name="ml_status" class="ml-control">
					<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( ML_Medicine_Repository::statuses() as $ml_s ) : ?>
						<option value="<?php echo esc_attr( $ml_s ); ?>" <?php selected( $ml_status, $ml_s ); ?>><?php echo esc_html( ucfirst( $ml_s ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ml-field">
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'ma-lumiere-clinic' ); ?></button>
			</p>
			<?php if ( '' !== $ml_search || '' !== $ml_status || '' !== $ml_form || '' !== $ml_cat || '' !== $ml_stock ) : ?>
				<p class="ml-field"><a class="button button-link" href="<?php echo esc_url( $ml_url( array( 'tab' => 'medicines' ) ) ); ?>"><?php esc_html_e( 'Reset', 'ma-lumiere-clinic' ); ?></a></p>
			<?php endif; ?>
		</form>

		<?php if ( empty( $ml_result['items'] ) ) : ?>
			<div class="ml-clinic-empty">
				<p><?php esc_html_e( 'No medicines in the catalogue yet.', 'ma-lumiere-clinic' ); ?></p>
				<?php if ( $ml_can_manage ) : ?>
					<p><a class="button button-primary" href="<?php echo esc_url( $ml_url( array( 'action' => 'new_medicine' ) ) ); ?>"><?php esc_html_e( 'Add the first medicine', 'ma-lumiere-clinic' ); ?></a></p>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<table class="widefat striped ml-clinic-list-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Medicine', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Form', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Category', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'On hand', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-num"><?php esc_html_e( 'Reorder at', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Next expiry', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $ml_result['items'] as $ml_m ) :
						$ml_state = $ml_m['stock_state'];
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $ml_m['display_name'] ); ?></strong>
								<?php if ( $ml_m['generic_name'] ) : ?><br /><span class="ml-muted"><?php echo esc_html( $ml_m['generic_name'] ); ?></span><?php endif; ?>
								<?php if ( $ml_m['sku'] ) : ?><br /><span class="ml-muted"><?php echo esc_html( $ml_m['sku'] ); ?></span><?php endif; ?>
							</td>
							<td><?php echo esc_html( $ml_m['form'] ? ucfirst( $ml_m['form'] ) : '—' ); ?></td>
							<td><?php echo esc_html( $ml_m['category'] ? ucfirst( str_replace( '-', ' ', $ml_m['category'] ) ) : '—' ); ?></td>
							<td class="ml-col-num">
								<strong><?php echo esc_html( number_format_i18n( (int) $ml_m['stock_available'] ) ); ?></strong>
								<?php if ( 'out' === $ml_state ) : ?>
									<span class="ml-badge ml-badge--danger"><?php esc_html_e( 'Out', 'ma-lumiere-clinic' ); ?></span>
								<?php elseif ( 'low' === $ml_state ) : ?>
									<span class="ml-badge ml-badge--warning"><?php esc_html_e( 'Low', 'ma-lumiere-clinic' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="ml-col-num"><?php echo esc_html( (int) $ml_m['reorder_level'] > 0 ? number_format_i18n( (int) $ml_m['reorder_level'] ) : '—' ); ?></td>
							<td>
								<?php echo esc_html( $ml_m['next_expiry'] ? ml_date( $ml_m['next_expiry'] ) : '—' ); ?>
							</td>
							<td class="ml-col-actions">
								<div class="ml-inline-actions">
									<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'action' => 'stockcard', 'id' => (int) $ml_m['id'] ) ) ); ?>">
										<?php esc_html_e( 'Stock', 'ma-lumiere-clinic' ); ?>
									</a>
									<?php if ( $ml_can_manage ) : ?>
										<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'action' => 'receive', 'id' => (int) $ml_m['id'] ) ) ); ?>">
											<?php esc_html_e( 'Receive', 'ma-lumiere-clinic' ); ?>
										</a>
										<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'action' => 'edit_medicine', 'id' => (int) $ml_m['id'] ) ) ); ?>">
											<?php esc_html_e( 'Edit', 'ma-lumiere-clinic' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $ml_result['pages'] > 1 ) : ?>
				<div class="ml-clinic-pagination">
					<?php if ( $ml_result['page'] > 1 ) : ?>
						<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'tab' => 'medicines', 'paged' => $ml_result['page'] - 1, 's' => $ml_search, 'ml_status' => $ml_status, 'category' => $ml_cat, 'form' => $ml_form, 'stock' => $ml_stock ) ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
					<?php endif; ?>
					<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $ml_result['page'], $ml_result['pages'] ) ); ?></span>
					<?php if ( $ml_result['page'] < $ml_result['pages'] ) : ?>
						<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'tab' => 'medicines', 'paged' => $ml_result['page'] + 1, 's' => $ml_search, 'ml_status' => $ml_status, 'category' => $ml_cat, 'form' => $ml_form, 'stock' => $ml_stock ) ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	<?php } ?>
</div>
