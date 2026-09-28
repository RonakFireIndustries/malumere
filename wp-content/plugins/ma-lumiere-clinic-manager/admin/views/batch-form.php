<?php
/**
 * Receive stock into a new batch, or edit an existing batch record.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_base     = admin_url( 'admin.php' );
$ml_page_url = add_query_arg( 'page', ML_Inventory_Controller::PAGE, $ml_base );

// The back link targets the stock card of the medicine whenever we know it.
$ml_back_medicine = $is_edit && ! empty( $batch ) ? (int) $batch['medicine_id'] : (int) $medicine_id;
$ml_back_url      = $ml_back_medicine
	? add_query_arg(
		array(
			'page'   => ML_Inventory_Controller::PAGE,
			'action' => 'stockcard',
			'id'     => $ml_back_medicine,
		),
		$ml_base
	)
	: add_query_arg(
		array(
			'page' => ML_Inventory_Controller::PAGE,
			'tab'  => 'batches',
		),
		$ml_base
	);

$ml_errors = ML_Inventory_Controller::$form_errors;

/*
 * Posted values win so validation errors do not discard user input. On a
 * first render of the edit form there is nothing posted yet, so fall back to
 * the stored row.
 */
$ml_source = ML_Inventory_Controller::$form_data;
if ( empty( $ml_source ) ) {
	$ml_source = $is_edit && ! empty( $batch ) ? $batch : array();
}

$ml_bval = static function ( $key, $default = '' ) use ( $ml_source ) {
	return isset( $ml_source[ $key ] ) ? (string) $ml_source[ $key ] : (string) $default;
};

/*
 * Active catalogue medicines, as a flat id => label list. The medicine already
 * attached to the batch is kept in the list even when it has since been made
 * inactive, so a received or existing lot is never silently unselectable.
 */
$ml_known_medicines = array();
foreach ( ML_Medicine_Repository::options( true ) as $ml_option ) {
	$ml_known_medicines[ (int) $ml_option['id'] ] = (string) $ml_option['label'];
}
if ( $medicine_id > 0 && ! isset( $ml_known_medicines[ (int) $medicine_id ] ) ) {
	$ml_fallback = ML_Medicine_Repository::get( (int) $medicine_id );
	if ( $ml_fallback ) {
		$ml_known_medicines[ (int) $medicine_id ] = $ml_fallback['display_name'];
	}
}

$ml_selected_medicine = (int) $ml_bval( 'medicine_id', $medicine_id ? $medicine_id : 0 );
if ( ! $ml_selected_medicine && $is_edit && ! empty( $batch ) ) {
	$ml_selected_medicine = (int) $batch['medicine_id'];
}

$ml_med_label = $medicine
	? $medicine['display_name']
	: ( $ml_selected_medicine && isset( $ml_known_medicines[ $ml_selected_medicine ] )
		? $ml_known_medicines[ $ml_selected_medicine ]
		: __( 'selected medicine', 'ma-lumiere-clinic' ) );
?>

<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title">
			<?php
			echo esc_html(
				$is_edit
					? __( 'Edit Batch', 'ma-lumiere-clinic' )
					: __( 'Receive Stock', 'ma-lumiere-clinic' )
			);
			?>
		</h1>
		<a class="button" href="<?php echo esc_url( $ml_back_url ); ?>"><?php esc_html_e( 'Back', 'ma-lumiere-clinic' ); ?></a>
	</div>

	<?php include ML_CLINIC_PATH . 'admin/views/_inventory-notice.php'; ?>

	<?php if ( ! empty( $ml_errors['form'] ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $ml_errors['form'] ); ?></p></div>
	<?php endif; ?>

	<p class="ml-field__hint">
		<?php
		printf(
			/* translators: %s: medicine name. */
			esc_html__( 'Medicine: %s', 'ma-lumiere-clinic' ),
			esc_html( $ml_med_label )
		);
		?>
	</p>

	<form method="post" class="ml-form" novalidate>
		<input type="hidden" name="ml_action" value="<?php echo esc_attr( $is_edit ? 'save_batch' : 'receive_stock' ); ?>" />
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="batch_id" value="<?php echo esc_attr( (int) $batch['id'] ); ?>" />
			<input type="hidden" name="batch[medicine_id]" value="<?php echo esc_attr( (int) $batch['medicine_id'] ); ?>" />
		<?php else : ?>
			<p class="ml-field ml-field--wide">
				<label for="ml-batch-medicine"><?php esc_html_e( 'Medicine', 'ma-lumiere-clinic' ); ?> *</label>
				<?php if ( $medicine_id > 0 ) : ?>
					<strong><?php echo esc_html( $ml_med_label ); ?></strong>
					<input type="hidden" name="batch[medicine_id]" value="<?php echo esc_attr( (int) $medicine_id ); ?>" />
				<?php else : ?>
					<select id="ml-batch-medicine" name="batch[medicine_id]" required>
						<option value=""><?php esc_html_e( '— Select a medicine —', 'ma-lumiere-clinic' ); ?></option>
						<?php foreach ( $ml_known_medicines as $ml_mid => $ml_mname ) : ?>
							<option value="<?php echo esc_attr( $ml_mid ); ?>" <?php selected( $ml_selected_medicine, $ml_mid ); ?>><?php echo esc_html( $ml_mname ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
				<?php if ( isset( $ml_errors['medicine_id'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['medicine_id'] ); ?></span><?php endif; ?>
			</p>
		<?php endif; ?>

		<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>

		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Batch Details', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-batch-number"><?php esc_html_e( 'Batch / lot number', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-batch-number" name="batch[batch_number]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_bval( 'batch_number' ) ); ?>" maxlength="100" />
					<?php if ( isset( $ml_errors['batch_number'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['batch_number'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-batch-received"><?php esc_html_e( 'Received date', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-batch-received" name="batch[received_date]" type="date" value="<?php echo esc_attr( $ml_bval( 'received_date', current_time( 'Y-m-d' ) ) ); ?>" />
					<?php if ( isset( $ml_errors['received_date'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['received_date'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-batch-expiry"><?php esc_html_e( 'Expiry date', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-batch-expiry" name="batch[expiry_date]" type="date" value="<?php echo esc_attr( $ml_bval( 'expiry_date' ) ); ?>" />
					<span class="ml-field__hint"><?php esc_html_e( 'Leave blank if the medicine does not expire.', 'ma-lumiere-clinic' ); ?></span>
					<?php if ( isset( $ml_errors['expiry_date'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['expiry_date'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-batch-location"><?php esc_html_e( 'Storage location', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-batch-location" name="batch[storage_location]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_bval( 'storage_location' ) ); ?>" maxlength="150"
						placeholder="<?php esc_attr_e( 'e.g. Shelf A2', 'ma-lumiere-clinic' ); ?>" />
				</p>
			</div>
		</section>

		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Quantity & Cost', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-batch-qty"><?php esc_html_e( 'Quantity received', 'ma-lumiere-clinic' ); ?> *</label>
					<?php if ( $is_edit ) : ?>
						<input id="ml-batch-qty" name="batch[quantity_received]" type="number" value="<?php echo esc_attr( (int) $batch['quantity_received'] ); ?>" readonly="readonly" />
						<span class="ml-field__hint">
							<?php
							printf(
								/* translators: 1: available units, 2: received units. */
								esc_html__( 'Received units are fixed. %1$d of %2$d unit(s) still available — use an adjustment to change stock.', 'ma-lumiere-clinic' ),
								(int) $batch['quantity_available'],
								(int) $batch['quantity_received']
							);
							?>
						</span>
					<?php else : ?>
						<input id="ml-batch-qty" name="batch[quantity_received]" type="number" min="1" step="1" value="<?php echo esc_attr( $ml_bval( 'quantity_received', '1' ) ); ?>" required />
					<?php endif; ?>
					<?php if ( isset( $ml_errors['quantity_received'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['quantity_received'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-batch-cost"><?php esc_html_e( 'Unit cost', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-batch-cost" name="batch[unit_cost]" type="number" min="0" step="0.01" value="<?php echo esc_attr( $ml_bval( 'unit_cost', '0' ) ); ?>" />
					<?php if ( isset( $ml_errors['unit_cost'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['unit_cost'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-batch-supplier"><?php esc_html_e( 'Supplier', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-batch-supplier" name="batch[supplier]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_bval( 'supplier' ) ); ?>" maxlength="200" />
				</p>
				<p class="ml-field ml-field--wide">
					<label for="ml-batch-notes"><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?></label>
					<textarea id="ml-batch-notes" name="batch[notes]" class="large-text" rows="2"><?php echo esc_textarea( $ml_bval( 'notes' ) ); ?></textarea>
				</p>
			</div>
		</section>

		<p class="ml-form-actions">
			<button type="submit" class="button button-primary button-large">
				<?php
				echo esc_html(
					$is_edit
						? __( 'Save Changes', 'ma-lumiere-clinic' )
						: __( 'Receive Stock', 'ma-lumiere-clinic' )
				);
				?>
			</button>
			<a class="button button-large" href="<?php echo esc_url( $ml_back_url ); ?>"><?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?></a>
		</p>
	</form>

	<?php if ( $is_edit ) : ?>
		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Batch Status', 'ma-lumiere-clinic' ); ?></h2>
			<p class="ml-field__hint">
				<?php esc_html_e( 'Quarantine a batch to keep it out of dispensing without writing it off. Depleted and disposed are set automatically.', 'ma-lumiere-clinic' ); ?>
			</p>
			<?php if ( 'depleted' === (string) $batch['status'] ) : ?>
				<p class="ml-field__hint">
					<em><?php esc_html_e( 'This batch is depleted, so its status cannot be changed. Receive replacement stock instead.', 'ma-lumiere-clinic' ); ?></em>
				</p>
			<?php else : ?>
				<form method="post" class="ml-form-inline">
					<input type="hidden" name="ml_action" value="set_batch_status" />
					<input type="hidden" name="batch_id" value="<?php echo esc_attr( (int) $batch['id'] ); ?>" />
					<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
					<select name="status">
						<?php
						$ml_labels     = ML_Medicine_Batch_Repository::status_labels();
						$ml_selectable = array( 'active', 'quarantined', 'expired', 'disposed' );
						foreach ( $ml_selectable as $ml_s ) :
							?>
							<option value="<?php echo esc_attr( $ml_s ); ?>" <?php selected( (string) $batch['status'], $ml_s ); ?>>
								<?php echo esc_html( isset( $ml_labels[ $ml_s ] ) ? $ml_labels[ $ml_s ] : ucfirst( $ml_s ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Update status', 'ma-lumiere-clinic' ); ?></button>
				</form>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</div>
