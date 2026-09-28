<?php
/**
 * Appointment list (server-rendered with REST-backed quick actions).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_s      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ml_status = isset( $_GET['ml_status'] ) ? sanitize_key( wp_unslash( $_GET['ml_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ml_from   = isset( $_GET['ml_from'] ) ? sanitize_text_field( wp_unslash( $_GET['ml_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ml_to     = isset( $_GET['ml_to'] ) ? sanitize_text_field( wp_unslash( $_GET['ml_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ml_doctor = isset( $_GET['ml_doctor'] ) ? absint( $_GET['ml_doctor'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ml_paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$ml_result = ML_Appointment_Repository::list(
	array(
		'page'     => $ml_paged,
		'per_page' => 20,
		'from'     => $ml_from,
		'to'       => $ml_to,
		'status'   => $ml_status,
		'doctor'   => $ml_doctor,
		'search'   => $ml_s,
	)
);

$ml_filtered = ( '' !== $ml_s || '' !== $ml_status || '' !== $ml_from || '' !== $ml_to || $ml_doctor > 0 );
?>
<form class="ml-form-filter" method="get">
	<input type="hidden" name="page" value="ml-clinic-appointments" />
	<input type="hidden" name="ml_appt_view" value="list" />
	<p class="ml-field">
		<label class="ml-field__label" for="ml-ap-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
		<input id="ml-ap-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_s ); ?>"
			placeholder="<?php esc_attr_e( 'Name, phone or booking reference…', 'ma-lumiere-clinic' ); ?>" />
	</p>
	<p class="ml-field">
		<label class="ml-field__label" for="ml-ap-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
		<select id="ml-ap-status" name="ml_status" class="ml-control">
			<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
			<?php foreach ( $ml_statuses as $ml_option ) : ?>
				<option value="<?php echo esc_attr( $ml_option ); ?>" <?php selected( $ml_status, $ml_option ); ?>><?php echo esc_html( $ml_status_label[ $ml_option ] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="ml-field">
		<label class="ml-field__label" for="ml-ap-from"><?php esc_html_e( 'From date', 'ma-lumiere-clinic' ); ?></label>
		<input id="ml-ap-from" type="date" name="ml_from" class="ml-control" value="<?php echo esc_attr( $ml_from ); ?>" />
	</p>
	<p class="ml-field">
		<label class="ml-field__label" for="ml-ap-to"><?php esc_html_e( 'To date', 'ma-lumiere-clinic' ); ?></label>
		<input id="ml-ap-to" type="date" name="ml_to" class="ml-control" value="<?php echo esc_attr( $ml_to ); ?>" />
	</p>
	<p class="ml-field">
		<label class="ml-field__label" for="ml-ap-doctor"><?php esc_html_e( 'Doctor', 'ma-lumiere-clinic' ); ?></label>
		<select id="ml-ap-doctor" name="ml_doctor" class="ml-control">
			<option value="0"><?php esc_html_e( 'All doctors', 'ma-lumiere-clinic' ); ?></option>
			<?php foreach ( $ml_doctor_opts as $ml_doc ) : ?>
				<option value="<?php echo esc_attr( $ml_doc['id'] ); ?>" <?php selected( $ml_doctor, $ml_doc['id'] ); ?>><?php echo esc_html( $ml_doc['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="ml-field">
		<button type="submit" class="button"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></button>
		<?php if ( $ml_filtered ) : ?>
			<a class="button button-link" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Reset', 'ma-lumiere-clinic' ); ?></a>
		<?php endif; ?>
	</p>
</form>

<?php if ( empty( $ml_result['items'] ) ) : ?>
	<div class="ml-clinic-empty">
		<p><?php echo $ml_filtered ? esc_html__( 'No appointments match your filters.', 'ma-lumiere-clinic' ) : esc_html__( 'No appointments yet.', 'ma-lumiere-clinic' ); ?></p>
		<?php if ( $ml_can_manage ) : ?>
			<button type="button" class="button button-primary ml-u-mt-sm" data-ml-open="new"><?php esc_html_e( 'Book your first appointment', 'ma-lumiere-clinic' ); ?></button>
		<?php endif; ?>
	</div>
<?php else : ?>
	<table class="widefat striped ml-clinic-list-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Reference', 'ma-lumiere-clinic' ); ?></th>
				<th><?php esc_html_e( 'Date / Time', 'ma-lumiere-clinic' ); ?></th>
				<th><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></th>
				<th><?php esc_html_e( 'Treatment', 'ma-lumiere-clinic' ); ?></th>
				<th><?php esc_html_e( 'Type', 'ma-lumiere-clinic' ); ?></th>
				<th><?php esc_html_e( 'Doctor', 'ma-lumiere-clinic' ); ?></th>
				<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
				<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $ml_result['items'] as $ml_a ) : ?>
				<tr>
					<td>
						<strong><?php echo esc_html( $ml_a['booking_reference'] ); ?></strong>
						<br /><span class="ml-muted"><?php echo esc_html( '#' . $ml_a['id'] ); ?></span>
					</td>
					<td>
						<?php echo esc_html( ml_date( $ml_a['appointment_date'], 'd M Y' ) ); ?>
						<br /><span class="ml-muted"><?php echo esc_html( mb_substr( (string) $ml_a['start_time'], 0, 5 ) . ' – ' . mb_substr( (string) $ml_a['end_time'], 0, 5 ) ); ?></span>
					</td>
					<td>
						<strong><?php echo esc_html( $ml_a['patient_name'] ? $ml_a['patient_name'] : '—' ); ?></strong>
						<?php if ( ! empty( $ml_a['patient_email'] ) ) : ?>
							<br /><span class="ml-muted"><?php echo esc_html( $ml_a['patient_email'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $ml_a['patient_uid'] ) ) : ?>
							<br /><span class="ml-muted"><?php echo esc_html( $ml_a['patient_uid'] ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $ml_a['treatment_name'] ? $ml_a['treatment_name'] : '—' ); ?></td>
					<td><?php echo esc_html( isset( $ml_type_label[ $ml_a['appointment_type'] ] ) ? $ml_type_label[ $ml_a['appointment_type'] ] : $ml_a['appointment_type'] ); ?></td>
					<td><?php echo esc_html( $ml_a['doctor_name'] ? $ml_a['doctor_name'] : '—' ); ?></td>
					<td>
						<span class="ml-badge ml-badge--<?php echo esc_attr( 'cancelled' === $ml_a['status'] || 'no_show' === $ml_a['status'] ? 'danger' : ( 'completed' === $ml_a['status'] || 'rescheduled' === $ml_a['status'] ? 'muted' : ( 'checked_in' === $ml_a['status'] ? 'info' : ( 'confirmed' === $ml_a['status'] ? 'success' : 'warning' ) ) ) ); ?>">
							<?php echo esc_html( $ml_status_label[ $ml_a['status'] ] ); ?>
						</span>
					</td>
					<td class="ml-col-actions">
						<button type="button" class="button button-small" data-ml-open="detail" data-ml-id="<?php echo esc_attr( $ml_a['id'] ); ?>"><?php esc_html_e( 'View', 'ma-lumiere-clinic' ); ?></button>
						<?php if ( $ml_can_manage && ! in_array( $ml_a['status'], array( 'completed', 'cancelled', 'rescheduled', 'no_show' ), true ) ) : ?>
							<?php if ( 'checked_in' !== $ml_a['status'] ) : ?>
								<button type="button" class="button button-small" data-ml-action="checkin" data-ml-id="<?php echo esc_attr( $ml_a['id'] ); ?>"><?php esc_html_e( 'Check in', 'ma-lumiere-clinic' ); ?></button>
							<?php endif; ?>
							<button type="button" class="button button-small" data-ml-action="complete" data-ml-id="<?php echo esc_attr( $ml_a['id'] ); ?>"><?php esc_html_e( 'Complete', 'ma-lumiere-clinic' ); ?></button>
							<button type="button" class="button button-small" data-ml-action="no_show" data-ml-id="<?php echo esc_attr( $ml_a['id'] ); ?>"><?php esc_html_e( 'No show', 'ma-lumiere-clinic' ); ?></button>
							<button type="button" class="button button-small" data-ml-open="reschedule" data-ml-id="<?php echo esc_attr( $ml_a['id'] ); ?>"><?php esc_html_e( 'Reschedule', 'ma-lumiere-clinic' ); ?></button>
							<button type="button" class="button button-small button-link-delete" data-ml-open="cancel" data-ml-id="<?php echo esc_attr( $ml_a['id'] ); ?>"><?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?></button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $ml_result['pages'] > 1 ) : ?>
		<div class="ml-clinic-pagination">
			<?php if ( $ml_result['page'] > 1 ) : ?>
				<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'page' => 'ml-clinic-appointments', 'paged' => $ml_result['page'] - 1, 's' => $ml_s, 'ml_status' => $ml_status, 'ml_from' => $ml_from, 'ml_to' => $ml_to, 'ml_doctor' => $ml_doctor, 'ml_appt_view' => 'list' ) ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
			<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $ml_result['page'], $ml_result['pages'] ) ); ?></span>
			<?php if ( $ml_result['page'] < $ml_result['pages'] ) : ?>
				<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'page' => 'ml-clinic-appointments', 'paged' => $ml_result['page'] + 1, 's' => $ml_s, 'ml_status' => $ml_status, 'ml_from' => $ml_from, 'ml_to' => $ml_to, 'ml_doctor' => $ml_doctor, 'ml_appt_view' => 'list' ) ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
<?php endif; ?>