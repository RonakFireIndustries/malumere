<?php
/**
 * Appointments: list + Day/Week/Month calendar shells driven by the REST
 * layer for actions. Server-rendered grids + JS quick actions.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_can_manage = current_user_can( 'ml_manage_appointments' ) || current_user_can( 'ml_manage_clinic' );
if ( ! current_user_can( 'ml_view_appointments' ) && ! current_user_can( 'ml_view_own_appointments' ) && ! $ml_can_manage ) {
	wp_die( esc_html__( 'You are not allowed to view appointments.', 'ma-lumiere-clinic' ), 403 );
}

$ml_view = isset( $_GET['ml_appt_view'] ) ? sanitize_key( wp_unslash( $_GET['ml_appt_view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! in_array( $ml_view, array( 'list', 'day', 'week', 'month' ), true ) ) {
	$ml_view = 'list';
}

$ml_cur = isset( $_GET['ml_date'] ) ? sanitize_text_field( wp_unslash( $_GET['ml_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! $ml_cur || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ml_cur ) ) {
	$ml_cur = (string) current_time( 'Y-m-d' );
}
$ml_cur_parts = array_map( 'intval', explode( '-', $ml_cur ) );
if ( ! checkdate( $ml_cur_parts[1], $ml_cur_parts[2], $ml_cur_parts[0] ) ) {
	$ml_cur = (string) current_time( 'Y-m-d' );
}

$ml_base        = admin_url( 'admin.php' );
$ml_page_url    = add_query_arg( 'page', 'ml-clinic-appointments', $ml_base );
$ml_url         = function ( $args ) use ( $ml_page_url ) {
	return add_query_arg( $args, $ml_page_url );
};
$ml_doctor_opts = ML_Appointment_Service::doctor_options();

// Navigation helper: the offset applied to today's grid position.
$ml_offset = isset( $_GET['ml_off'] ) ? absint( $_GET['ml_off'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$ml_patients      = ML_Patient_Repository::list( array( 'page' => 1, 'per_page' => 300 ) );
$ml_patient_light = array();
foreach ( (array) $ml_patients['items'] as $ml_p ) {
	$ml_patient_light[] = array(
		'id'    => (int) $ml_p['id'],
		'uid'   => (string) $ml_p['patient_uid'],
		'name'  => trim( (string) $ml_p['first_name'] . ' ' . (string) $ml_p['last_name'] ),
		'email' => (string) $ml_p['email'],
	);
}

$ml_treatments   = ML_Treatment_Repository::catalog();
$ml_types        = ML_Appointment_Repository::types();
$ml_statuses     = ML_Appointment_Repository::statuses();
$ml_status_label = array(
	'pending'    => __( 'Pending', 'ma-lumiere-clinic' ),
	'confirmed'  => __( 'Confirmed', 'ma-lumiere-clinic' ),
	'checked_in' => __( 'Checked in', 'ma-lumiere-clinic' ),
	'completed'  => __( 'Completed', 'ma-lumiere-clinic' ),
	'cancelled'  => __( 'Cancelled', 'ma-lumiere-clinic' ),
	'rescheduled' => __( 'Rescheduled', 'ma-lumiere-clinic' ),
	'no_show'    => __( 'No show', 'ma-lumiere-clinic' ),
);
$ml_type_label = array(
	'new_consultation' => __( 'New consultation', 'ma-lumiere-clinic' ),
	'follow_up'        => __( 'Follow-up', 'ma-lumiere-clinic' ),
	'treatment_session' => __( 'Treatment session', 'ma-lumiere-clinic' ),
	'procedure'        => __( 'Procedure', 'ma-lumiere-clinic' ),
	'other'            => __( 'Other', 'ma-lumiere-clinic' ),
);
?>
<div class="wrap ml-clinic-wrap" id="ml-appointments-page"
	data-ml-rest="<?php echo esc_url_raw( rest_url( 'ml-clinic/v1/' ) ); ?>"
	data-ml-nonce="<?php echo esc_attr( ML_Security::nonce() ); ?>"
	data-ml-manage="<?php echo $ml_can_manage ? '1' : '0'; ?>"
	data-ml-patients="<?php echo esc_attr( wp_json_encode( $ml_patient_light ) ); ?>"
	data-ml-treatments="<?php echo esc_attr( wp_json_encode( $ml_treatments ) ); ?>"
	data-ml-doctor-default="<?php echo esc_attr( (string) ML_Appointment_Service::default_doctor() ); ?>">

	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Appointments', 'ma-lumiere-clinic' ); ?></h1>
		<div class="ml-clinic-header__actions">
			<?php if ( $ml_can_manage ) : ?>
				<button type="button" class="button button-primary" data-ml-open="new"><?php esc_html_e( 'New Appointment', 'ma-lumiere-clinic' ); ?></button>
			<?php endif; ?>
		</div>
	</div>

	<?php include ML_CLINIC_PATH . 'admin/views/_notice.php'; ?>

	<div class="ml-clinic-toolbar ml-appt-tabs">
		<a class="ml-appt-tab <?php echo 'list' === $ml_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $ml_url( array( 'ml_appt_view' => 'list' ) ) ); ?>"><?php esc_html_e( 'List', 'ma-lumiere-clinic' ); ?></a>
		<a class="ml-appt-tab <?php echo 'day' === $ml_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $ml_url( array( 'ml_appt_view' => 'day', 'ml_date' => $ml_cur ) ) ); ?>"><?php esc_html_e( 'Day', 'ma-lumiere-clinic' ); ?></a>
		<a class="ml-appt-tab <?php echo 'week' === $ml_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $ml_url( array( 'ml_appt_view' => 'week', 'ml_date' => $ml_cur ) ) ); ?>"><?php esc_html_e( 'Week', 'ma-lumiere-clinic' ); ?></a>
		<a class="ml-appt-tab <?php echo 'month' === $ml_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $ml_url( array( 'ml_appt_view' => 'month', 'ml_date' => $ml_cur ) ) ); ?>"><?php esc_html_e( 'Month', 'ma-lumiere-clinic' ); ?></a>
	</div>

	<?php if ( 'day' === $ml_view || 'week' === $ml_view || 'month' === $ml_view ) : ?>
		<?php
		$ml_day_ts  = strtotime( $ml_cur . ' + ' . $ml_offset . ' days' );
		$ml_anchor  = (string) date_i18n( 'Y-m-d', $ml_day_ts );
		$ml_prev_ts = strtotime( $ml_anchor . ' -1 ' . ( 'month' === $ml_view ? 'month' : 'week' ) );
		$ml_next_ts = strtotime( $ml_anchor . ' +1 ' . ( 'month' === $ml_view ? 'month' : 'week' ) );
		$ml_prev    = max( 0, $ml_offset - 7 );
		$ml_next    = $ml_offset + 7;
		?>
		<div class="ml-appt-nav">
			<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'ml_appt_view' => $ml_view, 'ml_date' => $ml_cur, 'ml_off' => $ml_prev ) ) ); ?>"><?php echo esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
			<strong><?php echo esc_html( date_i18n( ( 'day' === $ml_view ) ? 'l, j M Y' : ( ( 'week' === $ml_view ) ? 'W' : 'F Y' ), $ml_day_ts ) ); ?></strong>
			<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'ml_appt_view' => $ml_view, 'ml_date' => $ml_cur, 'ml_off' => 0 ) ) ); ?>"><?php esc_html_e( 'Today', 'ma-lumiere-clinic' ); ?></a>
			<a class="button button-small" href="<?php echo esc_url( $ml_url( array( 'ml_appt_view' => $ml_view, 'ml_date' => $ml_cur, 'ml_off' => $ml_next ) ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
		</div>

		<?php include ML_CLINIC_PATH . 'admin/views/_appointment-calendar.php'; ?>
	<?php else : ?>
		<?php include ML_CLINIC_PATH . 'admin/views/_appointment-list.php'; ?>
	<?php endif; ?>

	<?php include ML_CLINIC_PATH . 'admin/views/_appointment-modals.php'; ?>
</div>