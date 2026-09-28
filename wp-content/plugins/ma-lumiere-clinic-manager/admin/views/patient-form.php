<?php
/**
 * Create/edit patient form (post-back to the patients page, nonce-protected).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_base     = admin_url( 'admin.php' );
$ml_page_url = add_query_arg( 'page', 'ml-clinic-patients', $ml_base );

$ml_form_vals = ML_Patient_Controller::$form_data;
if ( empty( $ml_form_vals ) ) {
	$ml_form_vals = ( $is_edit && ! empty( $patient ) ) ? $patient : array(
		'first_name'      => '',
		'last_name'       => '',
		'date_of_birth'   => '',
		'gender'          => '',
		'phone'           => '',
		'email'           => '',
		'address'         => '',
		'city'            => '',
		'state'           => '',
		'pincode'         => '',
		'emergency_contact_name'  => '',
		'emergency_contact_phone' => '',
		'registration_date' => current_time( 'Y-m-d' ),
		'status'          => 'active',
		'wp_user_id'      => '',
	);
}
$ml_fval = static function ( $key, $default = '' ) use ( $ml_form_vals ) {
	return isset( $ml_form_vals[ $key ] ) ? (string) $ml_form_vals[ $key ] : $default;
};
$ml_errors = ML_Patient_Controller::$form_errors;
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php echo esc_html( $is_edit ? __( 'Edit Patient', 'ma-lumiere-clinic' ) : __( 'Register Patient', 'ma-lumiere-clinic' ) ); ?></h1>
		<a class="button" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Back to list', 'ma-lumiere-clinic' ); ?></a>
	</div>

	<?php include ML_CLINIC_PATH . 'admin/views/_notice.php'; ?>

	<?php if ( ! empty( $ml_errors['form'] ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $ml_errors['form'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" class="ml-form" novalidate>
		<input type="hidden" name="ml_action" value="save_patient" />
		<input type="hidden" name="id" value="<?php echo esc_attr( $form_id ); ?>" />
		<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>

		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Personal Details', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-first-name"><?php esc_html_e( 'First name', 'ma-lumiere-clinic' ); ?> *</label>
					<input id="ml-first-name" name="patient[first_name]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'first_name' ) ); ?>" maxlength="100" required />
					<?php if ( isset( $ml_errors['first_name'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['first_name'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-last-name"><?php esc_html_e( 'Last name', 'ma-lumiere-clinic' ); ?> *</label>
					<input id="ml-last-name" name="patient[last_name]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'last_name' ) ); ?>" maxlength="100" required />
					<?php if ( isset( $ml_errors['last_name'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['last_name'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-dob"><?php esc_html_e( 'Date of birth', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-dob" name="patient[date_of_birth]" type="date" value="<?php echo esc_attr( $ml_fval( 'date_of_birth' ) ); ?>" />
					<?php if ( isset( $ml_errors['date_of_birth'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['date_of_birth'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-gender"><?php esc_html_e( 'Gender', 'ma-lumiere-clinic' ); ?></label>
					<select id="ml-gender" name="patient[gender]">
						<option value=""><?php esc_html_e( '— Not specified —', 'ma-lumiere-clinic' ); ?></option>
						<?php foreach ( ML_Patient_Repository::genders() as $ml_gender ) : ?>
							<option value="<?php echo esc_attr( $ml_gender ); ?>" <?php selected( $ml_fval( 'gender' ), $ml_gender ); ?>><?php echo esc_html( ucfirst( $ml_gender ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( isset( $ml_errors['gender'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['gender'] ); ?></span><?php endif; ?>
				</p>
			</div>
		</section>

		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Contact Information', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-phone"><?php esc_html_e( 'Phone', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-phone" name="patient[phone]" type="tel" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'phone' ) ); ?>" maxlength="20" />
				</p>
				<p class="ml-field">
					<label for="ml-email"><?php esc_html_e( 'Email', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-email" name="patient[email]" type="email" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'email' ) ); ?>" />
					<?php if ( isset( $ml_errors['email'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['email'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field ml-field--wide">
					<label for="ml-address"><?php esc_html_e( 'Address', 'ma-lumiere-clinic' ); ?></label>
					<textarea id="ml-address" name="patient[address]" class="large-text" rows="2"><?php echo esc_textarea( $ml_fval( 'address' ) ); ?></textarea>
				</p>
				<p class="ml-field">
					<label for="ml-city"><?php esc_html_e( 'City', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-city" name="patient[city]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'city' ) ); ?>" maxlength="100" />
				</p>
				<p class="ml-field">
					<label for="ml-state"><?php esc_html_e( 'State', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-state" name="patient[state]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'state' ) ); ?>" maxlength="100" />
				</p>
				<p class="ml-field">
					<label for="ml-pincode"><?php esc_html_e( 'Pincode', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-pincode" name="patient[pincode]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'pincode' ) ); ?>" maxlength="20" />
				</p>
			</div>
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Emergency Contact', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-ec-name"><?php esc_html_e( 'Contact name', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-ec-name" name="patient[emergency_contact_name]" type="text" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'emergency_contact_name' ) ); ?>" maxlength="100" />
				</p>
				<p class="ml-field">
					<label for="ml-ec-phone"><?php esc_html_e( 'Contact phone', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-ec-phone" name="patient[emergency_contact_phone]" type="tel" class="regular-text" value="<?php echo esc_attr( $ml_fval( 'emergency_contact_phone' ) ); ?>" maxlength="20" />
				</p>
			</div>
		</section>

		<section class="ml-form-section">
			<h2 class="ml-form-section__title"><?php esc_html_e( 'Registration & Status', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-form-grid">
				<p class="ml-field">
					<label for="ml-reg-date"><?php esc_html_e( 'Registration date', 'ma-lumiere-clinic' ); ?></label>
					<input id="ml-reg-date" name="patient[registration_date]" type="date" value="<?php echo esc_attr( $ml_fval( 'registration_date' ) ); ?>" />
					<?php if ( isset( $ml_errors['registration_date'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['registration_date'] ); ?></span><?php endif; ?>
				</p>
				<p class="ml-field">
					<label for="ml-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
					<select id="ml-status" name="patient[status]">
						<?php foreach ( ML_Patient_Repository::statuses() as $ml_status ) : ?>
							<option value="<?php echo esc_attr( $ml_status ); ?>" <?php selected( $ml_fval( 'status' ), $ml_status ); ?>><?php echo esc_html( ucfirst( $ml_status ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( isset( $ml_errors['status'] ) ) : ?><span class="ml-field__error"><?php echo esc_html( $ml_errors['status'] ); ?></span><?php endif; ?>
				</p>
			</div>
		</section>

		<p class="ml-form-actions">
			<button type="submit" class="button button-primary button-large">
				<?php echo esc_html( $is_edit ? __( 'Save Changes', 'ma-lumiere-clinic' ) : __( 'Register Patient', 'ma-lumiere-clinic' ) ); ?>
			</button>
			<a class="button button-large" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?></a>
		</p>
	</form>
</div>