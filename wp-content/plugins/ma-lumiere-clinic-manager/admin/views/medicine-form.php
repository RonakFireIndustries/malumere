<?php
/**
 * Create/edit medicine form (post-back to the inventory page, nonce-protected).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_base     = admin_url( 'admin.php' );
$ml_page_url = add_query_arg( 'page', ML_Inventory_Controller::PAGE, $ml_base );

$ml_form_vals = ML_Inventory_Controller::$form_data;
if ( empty( $ml_form_vals ) ) {
	$ml_form_vals = ( $is_edit && ! empty( $medicine ) ) ? $medicine : array(
		'sku'                   => '',
		'name'                  => '',
		'generic_name'          => '',
		'brand_name'            => '',
		'strength'              => '',
		'form'                  => '',
		'category'              => '',
		'reorder_level'         => 0,
		'requires_prescription' => 1,
		'status'                => 'active',
		'notes'                 => '',
	);
}

$ml_fval = static function ( $key, $default = '' ) use ( $ml_form_vals ) {
	return isset( $ml_form_vals[ $key ] ) ? (string) $ml_form_vals[ $key ] : $default;
};
$ml_errors = ML_Inventory_Controller::$form_errors;
$ml_fid    = isset( $ml_form_vals['id'] ) ? absint( $ml_form_vals['id'] ) : 0;
?>

<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php echo esc_html( $is_edit ? __( 'Edit Medicine', 'ma-lumiere-clinic' ) : __( 'Add Medicine', 'ma-lumiere-clinic' ) ); ?></h1>
		<a class="button" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Back to inventory', 'ma-lumiere-clinic' ); ?></a>
	</div>

	<?php include ML_CLINIC_PATH . 'admin/views/_inventory-notice.php'; ?>

	<?php if ( ! empty( $ml_errors['form'] ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $ml_errors['form'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" class="ml-form" novalidate>
		<input type="hidden" name="ml_action" value="save_medicine" />
		<input type="hidden" name="id" value="<?php echo esc_attr( $ml_fid ); ?>" />
		<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>

		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Identification', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-med-name"><?php esc_html_e( 'Medicine name', 'ma-lumiere-clinic' ); ?> *</label>
					<input id="ml-med-name" name="medicine[name]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'name' ) ); ?>" maxlength="200" required />
					<?php if ( isset( $ml_errors['name'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['name'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-med-generic"><?php esc_html_e( 'Generic name', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-med-generic" name="medicine[generic_name]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'generic_name' ) ); ?>" maxlength="200" />
				</p>
				<p class="ml-field">
					<label for="ml-med-brand"><?php esc_html_e( 'Brand name', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-med-brand" name="medicine[brand_name]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'brand_name' ) ); ?>" maxlength="200" />
				</p>
				<p class="ml-field">
					<label for="ml-med-strength"><?php esc_html_e( 'Strength', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-med-strength" name="medicine[strength]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'strength' ) ); ?>" maxlength="100"
						placeholder="<?php esc_attr_e( 'e.g. 5% or 250 mg', 'ma-lumiere-clinic' ); ?>" />
				</p>
				<p class="ml-field">
					<label for="ml-med-sku"><?php esc_html_e( 'Catalogue code', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-med-sku" name="medicine[sku]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'sku' ) ); ?>" maxlength="50" />
					<span class="ml-field__hint"><?php esc_html_e( 'Leave blank to allocate the next code automatically.', 'ma-lumiere-clinic' ); ?></span>
					<?php if ( isset( $ml_errors['sku'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['sku'] ); ?></span><?php endif; ?>
				</p>
			</div>
		</section>

		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Classification', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-med-form"><?php esc_html_e( 'Dosage form', 'ma-lumiere-clinic' ); ?></label>
					<select id="ml-med-form" name="medicine[form]">
						<option value=""><?php esc_html_e( '— Not specified —', 'ma-lumiere-clinic' ); ?></option>
						<?php foreach ( ML_Medicine_Repository::forms() as $ml_f ) : ?>
							<option value="<?php echo esc_attr( $ml_f ); ?>" <?php selected( $ml_fval( 'form' ), $ml_f ); ?>><?php echo esc_html( ucfirst( $ml_f ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( isset( $ml_errors['form'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['form'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-med-cat"><?php esc_html_e( 'Category', 'ma-lumiere-clinic' ); ?></label>
					<select id="ml-med-cat" name="medicine[category]">
						<option value=""><?php esc_html_e( '— Not specified —', 'ma-lumiere-clinic' ); ?></option>
						<?php foreach ( ML_Medicine_Repository::categories() as $ml_c ) : ?>
							<option value="<?php echo esc_attr( $ml_c ); ?>" <?php selected( $ml_fval( 'category' ), $ml_c ); ?>><?php echo esc_html( ucfirst( str_replace( '-', ' ', $ml_c ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( isset( $ml_errors['category'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['category'] ); ?></span><?php endif; ?>
				</p>
			</div>
		</section>

		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Stock Policy', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-med-reorder"><?php esc_html_e( 'Reorder level', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-med-reorder" name="medicine[reorder_level]" type="number" min="0" step="1" value="<?php echo esc_attr( $ml_fval( 'reorder_level', '0' ) ); ?>" />
					<span class="ml-field__hint"><?php esc_html_e( 'Flag the medicine as low once total stock reaches this number. Use 0 to disable.', 'ma-lumiere-clinic' ); ?></span>
					<?php if ( isset( $ml_errors['reorder_level'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['reorder_level'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-med-rx"><?php esc_html_e( 'Prescription only', 'ma-lumiere-clinic' ); ?></label>
					<select id="ml-med-rx" name="medicine[requires_prescription]">
						<option value="1" <?php selected( $ml_fval( 'requires_prescription', '1' ), '1' ); ?>><?php esc_html_e( 'Yes — prescription required', 'ma-lumiere-clinic' ); ?></option>
						<option value="0" <?php selected( $ml_fval( 'requires_prescription', '1' ), '0' ); ?>><?php esc_html_e( 'No — may be supplied directly', 'ma-lumiere-clinic' ); ?></option>
					</select>
				</p>
				<p class="ml-field">
					<label for="ml-med-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
					<select id="ml-med-status" name="medicine[status]">
						<?php foreach ( ML_Medicine_Repository::statuses() as $ml_s ) : ?>
							<option value="<?php echo esc_attr( $ml_s ); ?>" <?php selected( $ml_fval( 'status' ), $ml_s ); ?>><?php echo esc_html( ucfirst( $ml_s ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="ml-field__hint"><?php esc_html_e( 'Inactive medicines stay in the catalogue but stop appearing in pickers and alerts.', 'ma-lumiere-clinic' ); ?></span>
					<?php if ( isset( $ml_errors['status'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['status'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field ml-field--wide">
					<label for="ml-med-notes"><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?></label>
					<textarea id="ml-med-notes" name="medicine[notes]" class="large-text" rows="2"><?php echo esc_textarea( $ml_fval( 'notes' ) ); ?></textarea>
				</p>
			</div>
		</section>

		<p class="ml-form-actions">
			<button type="submit" class="button button-primary button-large">
				<?php echo esc_html( $is_edit ? __( 'Save Changes', 'ma-lumiere-clinic' ) : __( 'Add Medicine', 'ma-lumiere-clinic' ) ); ?>
			</button>
			<a class="button button-large" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?></a>
		</p>
	</form>

	<?php if ( $is_edit && empty( $ml_errors ) && ! empty( $medicine ) ) : ?>
		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Remove from catalogue', 'ma-lumiere-clinic' ); ?></h2>
			<p class="ml-field__hint">
				<?php esc_html_e( 'A medicine can only be deleted while it has no stock movements. Once stock has been received or dispensed, mark it inactive instead so the history is preserved.', 'ma-lumiere-clinic' ); ?>
			</p>
			<form method="post" class="ml-form-inline" onsubmit="return confirm('<?php echo esc_js( __( 'Permanently delete this medicine from the catalogue?', 'ma-lumiere-clinic' ) ); ?>');">
				<input type="hidden" name="ml_action" value="delete_medicine" />
				<input type="hidden" name="id" value="<?php echo esc_attr( (int) $medicine['id'] ); ?>" />
				<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
				<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Delete medicine', 'ma-lumiere-clinic' ); ?></button>
			</form>
		</section>
	<?php endif; ?>
</div>
