<?php
/**
 * Follow-ups list + complete / cancel actions.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_can_manage = current_user_can( 'ml_manage_clinic' ) || current_user_can( 'ml_manage_followups' );
$ml_base       = admin_url( 'admin.php' );
$ml_page_url   = add_query_arg( 'page', 'ml-clinic-followups', $ml_base );
$ml_error      = '';

// Complete / cancel a follow-up.
if ( $ml_can_manage && isset( $_POST['ml_action'], $_POST['followup_id'], $_POST['_wpnonce'] )
	&& in_array( sanitize_key( $_POST['ml_action'] ), array( 'complete', 'cancel' ), true ) ) {
	if ( ML_Security::verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) ) ) {
		$followup_id = absint( $_POST['followup_id'] );
		$row         = ML_Followup_Repository::get( $followup_id );
		if ( $row && ML_Security::can_access_patient( (int) $row['patient_id'] ) ) {
			$target = 'complete' === sanitize_key( $_POST['ml_action'] ) ? 'completed' : 'cancelled';
			$result = ML_Followup_Repository::set_status( $followup_id, $target );
			if ( is_wp_error( $result ) ) {
				$ml_error = $result->get_error_message();
			} else {
				wp_safe_redirect( $ml_page_url );
				exit;
			}
		} else {
			$ml_error = __( 'You are not allowed to access this follow-up.', 'ma-lumiere-clinic' );
		}
	}
}

$ml_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$ml_status = isset( $_GET['ml_status'] ) ? sanitize_key( wp_unslash( $_GET['ml_status'] ) ) : '';

$result = ML_Followup_Repository::list(
	array(
		'page'     => 1,
		'per_page' => 100,
		'status'   => $ml_status,
		'search'   => $ml_search,
	)
);
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Follow-ups', 'ma-lumiere-clinic' ); ?></h1>
		<?php if ( $ml_can_manage ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'page', 'ml-clinic-patients', $ml_base ) ); ?>">
				<?php esc_html_e( 'Open a patient record', 'ma-lumiere-clinic' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $ml_error ) ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $ml_error ); ?></p></div>
	<?php endif; ?>

	<form class="ml-form-filter" method="get">
		<input type="hidden" name="page" value="ml-clinic-followups" />
		<p class="ml-field">
			<label class="ml-field__label" for="ml-fu-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
			<input id="ml-fu-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_search ); ?>"
				placeholder="<?php esc_attr_e( 'Patient name or UID…', 'ma-lumiere-clinic' ); ?>" />
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-fu-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
			<select id="ml-fu-status" name="ml_status" class="ml-control">
				<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
				<?php foreach ( ML_Followup_Repository::statuses() as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $ml_status, $option ); ?>>
						<?php echo esc_html( ucfirst( $option ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="ml-field">
			<button type="submit" class="button"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></button>
			<?php if ( '' !== $ml_search || '' !== $ml_status ) : ?>
				<a class="button button-link" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Reset', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
		</p>
	</form>

	<?php if ( empty( $result['items'] ) ) : ?>
		<div class="ml-clinic-empty">
			<p><?php echo ( '' !== $ml_search || '' !== $ml_status ) ? esc_html__( 'No follow-ups match your filters.', 'ma-lumiere-clinic' ) : esc_html__( 'No follow-ups scheduled yet.', 'ma-lumiere-clinic' ); ?></p>
		</div>
	<?php else : ?>
		<table class="widefat striped ml-clinic-list-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Reason', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
					<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $result['items'] as $followup ) :
					$fu_patient = trim( (string) $followup['p_first_name'] . ' ' . (string) $followup['p_last_name'] );
					$fu_done    = in_array( (string) $followup['status'], array( 'completed', 'cancelled' ), true );
					?>
					<tr>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-patients', 'view' => (int) $followup['patient_id'] ), $ml_base ) ); ?>">
								<?php echo esc_html( '' !== $fu_patient ? $fu_patient : '#' . (int) $followup['patient_id'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( ml_date( $followup['followup_date'], 'd M Y' ) ); ?></td>
						<td><?php echo esc_html( $followup['reason'] ? $followup['reason'] : '—' ); ?></td>
						<td>
							<span class="ml-badge ml-badge--<?php echo esc_attr( 'upcoming' === $followup['status'] ? 'warning' : ( 'completed' === $followup['status'] ? 'success' : 'muted' ) ); ?>">
								<?php echo esc_html( ucfirst( $followup['status'] ) ); ?>
							</span>
						</td>
						<td class="ml-col-actions">
							<?php if ( $ml_can_manage && ! $fu_done && 'upcoming' === (string) $followup['status'] ) : ?>
								<div class="ml-inline-actions">
									<form method="post" class="ml-form-inline">
										<input type="hidden" name="ml_action" value="complete" />
										<input type="hidden" name="followup_id" value="<?php echo esc_attr( (int) $followup['id'] ); ?>" />
										<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
										<button class="button button-small"><?php esc_html_e( 'Complete', 'ma-lumiere-clinic' ); ?></button>
									</form>
									<form method="post" class="ml-form-inline">
										<input type="hidden" name="ml_action" value="cancel" />
										<input type="hidden" name="followup_id" value="<?php echo esc_attr( (int) $followup['id'] ); ?>" />
										<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
										<button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Cancel this follow-up?', 'ma-lumiere-clinic' ) ); ?>');"><?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?></button>
									</form>
								</div>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>