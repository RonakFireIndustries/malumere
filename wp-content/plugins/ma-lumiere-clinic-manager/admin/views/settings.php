<?php
/**
 * Settings page. Outputs the WordPress Settings API form for the
 * ml_clinic_settings option (capability-gated by ml_manage_settings).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/template.php';

if ( ! current_user_can( ML_Settings::CAP ) ) {
	wp_die( esc_html__( 'You are not allowed to view this page.', 'ma-lumiere-clinic' ), 403 );
}

$ml_settings = ML_Settings::all();
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Clinic Settings', 'ma-lumiere-clinic' ); ?></h1>
		<p class="ml-clinic-subtitle"><?php esc_html_e( 'Configuration for the Ma Lumière clinic system.', 'ma-lumiere-clinic' ); ?></p>
	</div>

	<form method="post" action="options.php" class="ml-form ml-settings-form">
		<?php settings_fields( 'ml_clinic_settings_group' ); ?>

		<div class="ml-settings-section">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Clinic Information', 'ma-lumiere-clinic' ); ?></h2>
			<table class="form-table ml-form-table" role="presentation">
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-clinic-name"><?php esc_html_e( 'Clinic Name', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="text" id="ml-clinic-name" name="ml_clinic_settings[clinic][clinic_name]" value="<?php echo esc_attr( $ml_settings['clinic']['clinic_name'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-clinic-address"><?php esc_html_e( 'Address', 'ma-lumiere-clinic' ); ?></label></th>
					<td><textarea class="large-text" id="ml-clinic-address" name="ml_clinic_settings[clinic][address]" rows="3"><?php echo esc_textarea( $ml_settings['clinic']['address'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-clinic-phone"><?php esc_html_e( 'Phone', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="text" id="ml-clinic-phone" name="ml_clinic_settings[clinic][phone]" value="<?php echo esc_attr( $ml_settings['clinic']['phone'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-clinic-email"><?php esc_html_e( 'Email', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="email" id="ml-clinic-email" name="ml_clinic_settings[clinic][email]" value="<?php echo esc_attr( $ml_settings['clinic']['email'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-clinic-website"><?php esc_html_e( 'Website', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="url" id="ml-clinic-website" name="ml_clinic_settings[clinic][website]" value="<?php echo esc_attr( $ml_settings['clinic']['website'] ); ?>" /></td>
				</tr>
			</table>
		</div>

		<div class="ml-settings-section">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Doctor Information', 'ma-lumiere-clinic' ); ?></h2>
			<table class="form-table ml-form-table" role="presentation">
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-doctor-name"><?php esc_html_e( 'Doctor Name', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="text" id="ml-doctor-name" name="ml_clinic_settings[doctor][doctor_name]" value="<?php echo esc_attr( $ml_settings['doctor']['doctor_name'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-doctor-designation"><?php esc_html_e( 'Designation', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="text" id="ml-doctor-designation" name="ml_clinic_settings[doctor][designation]" value="<?php echo esc_attr( $ml_settings['doctor']['designation'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-doctor-registration"><?php esc_html_e( 'Registration Number', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="text" id="ml-doctor-registration" name="ml_clinic_settings[doctor][registration_no]" value="<?php echo esc_attr( $ml_settings['doctor']['registration_no'] ); ?>" /></td>
				</tr>
			</table>
		</div>

		<div class="ml-settings-section">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Appointment Settings', 'ma-lumiere-clinic' ); ?></h2>
			<table class="form-table ml-form-table" role="presentation">
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-appt-duration"><?php esc_html_e( 'Appointment Duration (minutes)', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="small-text" type="number" min="5" max="240" step="5" id="ml-appt-duration" name="ml_clinic_settings[appointments][appointment_duration]" value="<?php echo esc_attr( $ml_settings['appointments']['appointment_duration'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-appt-buffer"><?php esc_html_e( 'Booking Buffer (minutes)', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="small-text" type="number" min="0" max="120" step="5" id="ml-appt-buffer" name="ml_clinic_settings[appointments][booking_buffer]" value="<?php echo esc_attr( $ml_settings['appointments']['booking_buffer'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-appt-max-advance"><?php esc_html_e( 'Booking Horizon (days)', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="small-text" type="number" min="1" max="365" id="ml-appt-max-advance" name="ml_clinic_settings[appointments][max_advance_days]" value="<?php echo esc_attr( $ml_settings['appointments']['max_advance_days'] ); ?>" />
						<p class="description"><?php esc_html_e( 'How far into the future patients may book.', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-appt-default-doctor"><?php esc_html_e( 'Default Doctor', 'ma-lumiere-clinic' ); ?></label></th>
					<td>
						<select class="ml-control" id="ml-appt-default-doctor" name="ml_clinic_settings[appointments][default_doctor]">
							<option value="0"><?php esc_html_e( 'No default (choose per booking)', 'ma-lumiere-clinic' ); ?></option>
							<?php foreach ( ML_Appointment_Service::doctor_options() as $ml_doc ) : ?>
								<option value="<?php echo esc_attr( $ml_doc['id'] ); ?>" <?php selected( $ml_settings['appointments']['default_doctor'], $ml_doc['id'] ); ?>><?php echo esc_html( $ml_doc['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Slots default to this doctor when none is selected.', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Break', 'ma-lumiere-clinic' ); ?></th>
					<td>
						<label class="ml-checkbox">
							<input class="small-text" type="time" name="ml_clinic_settings[appointments][break_start]" value="<?php echo esc_attr( $ml_settings['appointments']['break_start'] ); ?>" />
							<?php esc_html_e( 'to', 'ma-lumiere-clinic' ); ?>
							<input class="small-text" type="time" name="ml_clinic_settings[appointments][break_end]" value="<?php echo esc_attr( $ml_settings['appointments']['break_end'] ); ?>" />
						</label>
						<p class="description"><?php esc_html_e( 'Optional daily break when no appointments are offered (e.g. lunch).', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Opening Hours', 'ma-lumiere-clinic' ); ?></th>
					<td>
						<table class="widefat striped ml-form-table ml-form-table--compact">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Day', 'ma-lumiere-clinic' ); ?></th>
									<th><?php esc_html_e( 'Open', 'ma-lumiere-clinic' ); ?></th>
									<th><?php esc_html_e( 'Close', 'ma-lumiere-clinic' ); ?></th>
									<th><?php esc_html_e( 'Open?', 'ma-lumiere-clinic' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$ml_day_order = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
								$ml_day_names = array(
									'mon' => __( 'Monday', 'ma-lumiere-clinic' ),
									'tue' => __( 'Tuesday', 'ma-lumiere-clinic' ),
									'wed' => __( 'Wednesday', 'ma-lumiere-clinic' ),
									'thu' => __( 'Thursday', 'ma-lumiere-clinic' ),
									'fri' => __( 'Friday', 'ma-lumiere-clinic' ),
									'sat' => __( 'Saturday', 'ma-lumiere-clinic' ),
									'sun' => __( 'Sunday', 'ma-lumiere-clinic' ),
								);
								$ml_days_val   = is_array( $ml_settings['appointments']['days'] ) ? $ml_settings['appointments']['days'] : array();
								foreach ( $ml_day_order as $ml_dk ) :
									$ml_drow = isset( $ml_days_val[ $ml_dk ] ) ? $ml_days_val[ $ml_dk ] : array();
									?>
									<tr>
										<td><?php echo esc_html( $ml_day_names[ $ml_dk ] ); ?></td>
										<td><input class="small-text" type="time" name="ml_clinic_settings[appointments][days][<?php echo esc_attr( $ml_dk ); ?>][open]" value="<?php echo esc_attr( isset( $ml_drow['open'] ) ? $ml_drow['open'] : '09:00' ); ?>" /></td>
										<td><input class="small-text" type="time" name="ml_clinic_settings[appointments][days][<?php echo esc_attr( $ml_dk ); ?>][close]" value="<?php echo esc_attr( isset( $ml_drow['close'] ) ? $ml_drow['close'] : '17:00' ); ?>" /></td>
										<td><input type="checkbox" name="ml_clinic_settings[appointments][days][<?php echo esc_attr( $ml_dk ); ?>][enabled]" value="1" <?php checked( ! empty( $ml_drow['enabled'] ) ); ?> /></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<p class="description"><?php esc_html_e( 'Days without "Open?" checked are closed for bookings.', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="ml-settings-section">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Email Settings', 'ma-lumiere-clinic' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Emails build on the service layer added in this phase; templates arrive in a later phase.', 'ma-lumiere-clinic' ); ?></p>
			<table class="form-table ml-form-table" role="presentation">
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-email-name"><?php esc_html_e( 'Sender Name', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="text" id="ml-email-name" name="ml_clinic_settings[email][sender_name]" value="<?php echo esc_attr( $ml_settings['email']['sender_name'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-email-from"><?php esc_html_e( 'Sender Email', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="email" id="ml-email-from" name="ml_clinic_settings[email][sender_email]" value="<?php echo esc_attr( $ml_settings['email']['sender_email'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Notifications', 'ma-lumiere-clinic' ); ?></th>
					<td>
						<label class="ml-checkbox">
							<input type="checkbox" name="ml_clinic_settings[email][appointment_reminders]" value="1" <?php checked( $ml_settings['email']['appointment_reminders'] ); ?> />
							<?php esc_html_e( 'Appointment reminders', 'ma-lumiere-clinic' ); ?>
						</label><br />
						<label class="ml-checkbox">
							<input type="checkbox" name="ml_clinic_settings[email][followup_reminders]" value="1" <?php checked( $ml_settings['email']['followup_reminders'] ); ?> />
							<?php esc_html_e( 'Follow-up reminders', 'ma-lumiere-clinic' ); ?>
						</label><br />
						<label class="ml-checkbox">
							<input type="checkbox" name="ml_clinic_settings[email][invoice_emails]" value="1" <?php checked( $ml_settings['email']['invoice_emails'] ); ?> />
							<?php esc_html_e( 'Invoice emails', 'ma-lumiere-clinic' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div class="ml-settings-section">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Billing Settings', 'ma-lumiere-clinic' ); ?></h2>
			<table class="form-table ml-form-table" role="presentation">
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-cur"><?php esc_html_e( 'Currency', 'ma-lumiere-clinic' ); ?></label></th>
					<td>
						<select class="ml-control" id="ml-cur" name="ml_clinic_settings[billing][currency]">
							<?php foreach ( array( 'INR' => 'INR (₹)', 'USD' => 'USD ($)', 'EUR' => 'EUR (€)', 'GBP' => 'GBP (£)' ) as $ml_code => $ml_label ) : ?>
								<option value="<?php echo esc_attr( $ml_code ); ?>" <?php selected( $ml_settings['billing']['currency'], $ml_code ); ?>><?php echo esc_html( $ml_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-tax-enabled"><?php esc_html_e( 'Enable Tax', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="small-text" type="checkbox" id="ml-tax-enabled" name="ml_clinic_settings[billing][tax_enabled]" value="1" <?php checked( $ml_settings['billing']['tax_enabled'] ); ?> /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-tax-percent"><?php esc_html_e( 'Tax Percentage', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="small-text" type="number" min="0" max="100" step="0.01" id="ml-tax-percent" name="ml_clinic_settings[billing][tax_percent]" value="<?php echo esc_attr( $ml_settings['billing']['tax_percent'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-invoice-prefix"><?php esc_html_e( 'Invoice Prefix', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text" type="text" id="ml-invoice-prefix" name="ml_clinic_settings[billing][invoice_prefix]" value="<?php echo esc_attr( $ml_settings['billing']['invoice_prefix'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-razr-mode"><?php esc_html_e( 'Razorpay mode', 'ma-lumiere-clinic' ); ?></label></th>
					<td>
						<select class="ml-control" id="ml-razr-mode" name="ml_clinic_settings[billing][razorpay][mode]">
							<?php foreach ( array( 'off' => __( 'Off (manual payments only)', 'ma-lumiere-clinic' ), 'test' => __( 'Test', 'ma-lumiere-clinic' ), 'live' => __( 'Live', 'ma-lumiere-clinic' ) ) as $ml_rk => $ml_rlabel ) : ?>
								<option value="<?php echo esc_attr( $ml_rk ); ?>" <?php selected( isset( $ml_settings['billing']['razorpay']['mode'] ) ? $ml_settings['billing']['razorpay']['mode'] : 'off', $ml_rk ); ?>><?php echo esc_html( $ml_rlabel ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Enables the "Pay online (Razorpay)" button on invoice screens in the selected environment. Test and live credentials are kept separate.', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-razr-key-id"><?php esc_html_e( 'Razorpay Key ID', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text code" type="text" id="ml-razr-key-id" name="ml_clinic_settings[billing][razorpay][key_id]" value="<?php echo esc_attr( isset( $ml_settings['billing']['razorpay']['key_id'] ) ? $ml_settings['billing']['razorpay']['key_id'] : '' ); ?>" autocomplete="off" /></td>
				</tr>
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-razr-key-secret"><?php esc_html_e( 'Razorpay Key Secret', 'ma-lumiere-clinic' ); ?></label></th>
					<td><input class="regular-text code" type="password" id="ml-razr-key-secret" name="ml_clinic_settings[billing][razorpay][key_secret]" value="<?php echo esc_attr( isset( $ml_settings['billing']['razorpay']['key_secret'] ) ? $ml_settings['billing']['razorpay']['key_secret'] : '' ); ?>" autocomplete="off" /></td>
				</tr>
			</table>
		</div>

		<div class="ml-settings-section">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Patient portal', 'ma-lumiere-clinic' ); ?></h2>
			<table class="form-table ml-form-table" role="presentation">
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-portal-page"><?php esc_html_e( 'Portal page', 'ma-lumiere-clinic' ); ?></label></th>
					<td>
						<select class="ml-control" id="ml-portal-page" name="ml_clinic_settings[portal][page_id]">
							<option value="0"><?php esc_html_e( '— Site home —', 'ma-lumiere-clinic' ); ?></option>
							<?php $ml_portal_pages = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'post_title' ) ); ?>
							<?php foreach ( $ml_portal_pages as $ml_page ) : ?>
								<option value="<?php echo esc_attr( $ml_page->ID ); ?>" <?php selected( absint( $ml_settings['portal']['page_id'] ), absint( $ml_page->ID ) ); ?>><?php echo esc_html( $ml_page->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Page that hosts the sign-in/dashboard shortcode below. Leave empty to use the site home page.', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sign-in form', 'ma-lumiere-clinic' ); ?></th>
					<td>
						<code>[ml_portal_login]</code>
						<p class="description"><?php esc_html_e( 'Patients enter the phone or email they provided at the clinic and receive a one-time sign-in link by email.', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Patient dashboard', 'ma-lumiere-clinic' ); ?></th>
					<td>
						<code>[ml_portal]</code>
						<p class="description"><?php esc_html_e( 'Shows the patient their own details, appointments, visits, prescriptions (PDF), treatment sessions, follow-ups and invoices — including online payment where Razorpay is configured. Photos stay staff-only.', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="ml-settings-section">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Privacy', 'ma-lumiere-clinic' ); ?></h2>
			<table class="form-table ml-form-table" role="presentation">
				<tr>
					<th scope="row"><label class="ml-field__label" for="ml-delete-on-uninstall"><?php esc_html_e( 'Delete clinic data on uninstall', 'ma-lumiere-clinic' ); ?></label></th>
					<td>
						<input type="checkbox" id="ml-delete-on-uninstall" name="ml_clinic_settings[privacy][delete_on_uninstall]" value="1" <?php checked( $ml_settings['privacy']['delete_on_uninstall'] ); ?> />
						<p class="description"><?php esc_html_e( 'WARNING: By default uninstall keeps all clinic tables. Enabling this permanently deletes patient/health data only when the plugin is uninstalled.', 'ma-lumiere-clinic' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>

	<?php
	/*
	 * Sample data lives outside the Settings API form: it posts to
	 * admin-post.php through ML_Sample_Data, never to options.php, so a
	 * dataset can be created or removed without rewriting the stored
	 * clinic settings.
	 */
	$ml_sample_state  = ML_Sample_Data::is_installed();
	$ml_sample_counts = $ml_sample_state ? ML_Sample_Data::live_counts() : array();
	$ml_sample_labels = ML_Sample_Data::labels();
	$ml_sample_at     = ML_Sample_Data::installed_at();
	$ml_sample_flag   = isset( $_GET['ml_sample'] ) ? sanitize_key( wp_unslash( $_GET['ml_sample'] ) ) : '';
	$ml_sample_error  = isset( $_GET['ml_message'] ) ? sanitize_text_field( wp_unslash( $_GET['ml_message'] ) ) : '';
	$ml_sample_bad    = 'word' === ( isset( $_GET['ml_failed'] ) ? sanitize_key( wp_unslash( $_GET['ml_failed'] ) ) : '' );
	$ml_sample_rows   = 0;
	foreach ( $ml_sample_counts as $ml_sample_count ) {
		$ml_sample_rows += (int) $ml_sample_count;
	}
	?>

	<div class="ml-settings-section ml-settings-section--sample">
		<h2 class="ml-settings-section__title"><?php esc_html_e( 'Sample data', 'ma-lumiere-clinic' ); ?></h2>

		<?php if ( 'installed' === $ml_sample_flag ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sample data created. Browse the screens below to see it in place.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php elseif ( 'removed' === $ml_sample_flag ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sample data removed.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php elseif ( 'failed' === $ml_sample_flag ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php echo esc_html( '' !== $ml_sample_error ? $ml_sample_error : __( 'The sample data could not be changed.', 'ma-lumiere-clinic' ) ); ?></p></div>
		<?php elseif ( 'already' === $ml_sample_flag ) : ?>
			<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Sample data is already installed.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php elseif ( 'absent' === $ml_sample_flag ) : ?>
			<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'There is no sample data to remove.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php endif; ?>

		<table class="form-table ml-form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
				<td>
					<?php if ( $ml_sample_state ) : ?>
						<p class="ml-sample-state ml-sample-state--on">
							<strong><?php esc_html_e( 'Installed', 'ma-lumiere-clinic' ); ?></strong>
							<?php
							printf(
								/* translators: 1: number of rows, 2: date the dataset was created. */
								esc_html__( '%1$d sample records across the clinic screens, created %2$s.', 'ma-lumiere-clinic' ),
								(int) $ml_sample_rows,
								esc_html( $ml_sample_at ? ml_date( $ml_sample_at, 'd M Y, H:i' ) : __( 'recently', 'ma-lumiere-clinic' ) )
							);
							?>
						</p>
						<table class="widefat striped ml-form-table ml-form-table--compact ml-sample-breakdown">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Dataset', 'ma-lumiere-clinic' ); ?></th>
									<th><?php esc_html_e( 'Rows', 'ma-lumiere-clinic' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $ml_sample_labels as $ml_slug => $ml_label ) : ?>
									<?php $ml_n = isset( $ml_sample_counts[ $ml_slug ] ) ? (int) $ml_sample_counts[ $ml_slug ] : 0; ?>
									<tr>
										<td><?php echo esc_html( $ml_label ); ?></td>
										<td><?php echo esc_html( number_format_i18n( $ml_n ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="ml-sample-state ml-sample-state--off">
							<strong><?php esc_html_e( 'Not installed', 'ma-lumiere-clinic' ); ?></strong>
							<?php esc_html_e( 'No sample data is present. You can create a fictional dataset to explore the screens, reports and PDFs.', 'ma-lumiere-clinic' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php if ( ! $ml_sample_state ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ml-form-actions ml-sample-form">
				<input type="hidden" name="action" value="ml_sample_data_install" />
				<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Create sample data', 'ma-lumiere-clinic' ); ?>
				</button>
				<p class="description ml-u-mt-sm">
					<?php esc_html_e( 'Creates fictional patients, visits, appointments, prescriptions, medicines with stock, invoices and payments. Every record is tagged so it can be removed again without touching your own records.', 'ma-lumiere-clinic' ); ?>
				</p>
			</form>
		<?php elseif ( 'confirm' === $ml_sample_flag ) : ?>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'This permanently deletes the sample dataset.', 'ma-lumiere-clinic' ); ?></strong></p>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ml-form ml-sample-form ml-sample-form--danger">
				<input type="hidden" name="action" value="ml_sample_data_remove" />
				<input type="hidden" name="ml_sample_stage" value="2" />
				<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>

				<div class="ml-field">
					<label class="ml-field__label" for="ml-sample-confirm">
						<?php
						printf(
							/* translators: %s: the confirmation word. */
							esc_html__( 'Type %s to confirm', 'ma-lumiere-clinic' ),
							'<code>' . esc_html( ML_Sample_Data::CONFIRM_WORD ) . '</code>'
						);
						?>
					</label>
					<input
						class="ml-control"
						type="text"
						id="ml-sample-confirm"
						name="ml_sample_confirm"
						value=""
						autocomplete="off"
						spellcheck="false"
						aria-describedby="ml-sample-confirm-hint"
					/>
					<?php if ( $ml_sample_bad ) : ?>
						<p class="ml-field__error" id="ml-sample-confirm-hint"><?php esc_html_e( 'That did not match. Type the word exactly, in capitals.', 'ma-lumiere-clinic' ); ?></p>
					<?php else : ?>
						<p class="ml-field__hint" id="ml-sample-confirm-hint"><?php esc_html_e( 'Only rows carrying the sample markers are deleted. Anything you edited into a real record is left alone.', 'ma-lumiere-clinic' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="ml-form-actions">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Delete sample data', 'ma-lumiere-clinic' ); ?>
					</button>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ml-clinic-settings' ) ); ?>">
						<?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?>
					</a>
				</div>
			</form>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ml-form-actions ml-sample-form">
				<input type="hidden" name="action" value="ml_sample_data_remove" />
				<input type="hidden" name="ml_sample_stage" value="1" />
				<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
				<button type="submit" class="button button-secondary">
					<?php esc_html_e( 'Remove sample data', 'ma-lumiere-clinic' ); ?>
				</button>
				<p class="description ml-u-mt-sm"><?php esc_html_e( 'You will be asked to confirm before anything is deleted.', 'ma-lumiere-clinic' ); ?></p>
			</form>
		<?php endif; ?>
	</div>
</div>