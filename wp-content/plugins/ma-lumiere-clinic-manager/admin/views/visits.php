<?php
/**
 * Visits list + lock/unlock actions.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_can_manage = current_user_can( 'ml_manage_clinic' ) || current_user_can( 'ml_manage_visits' );
$ml_base       = admin_url( 'admin.php' );
$ml_page_url   = add_query_arg( 'page', 'ml-clinic-visits', $ml_base );
$ml_error      = '';

// Lock / unlock a visit (nonce + capability).
if ( $ml_can_manage && isset( $_POST['ml_action'], $_POST['visit_id'], $_POST['_wpnonce'] )
	&& in_array( sanitize_key( $_POST['ml_action'] ), array( 'lock', 'unlock' ), true ) ) {
	if ( ML_Security::verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) ) ) {
		$visit_id = absint( $_POST['visit_id'] );
		$result   = ML_Visit_Repository::set_locked( $visit_id, 'lock' === sanitize_key( $_POST['ml_action'] ) );
		if ( is_wp_error( $result ) ) {
			$ml_error = $result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( array( 'paged' => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ), $ml_page_url ) );
			exit;
		}
	}
}

$ml_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$ml_status = isset( $_GET['ml_status'] ) ? sanitize_key( wp_unslash( $_GET['ml_status'] ) ) : '';
$ml_page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

$result = ML_Visit_Repository::list(
	array(
		'page'     => $ml_page,
		'per_page' => 20,
		'status'   => $ml_status,
		'search'   => $ml_search,
	)
);
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Visits', 'ma-lumiere-clinic' ); ?></h1>
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
		<input type="hidden" name="page" value="ml-clinic-visits" />
		<p class="ml-field">
			<label class="ml-field__label" for="ml-vs-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
			<input id="ml-vs-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_search ); ?>"
				placeholder="<?php esc_attr_e( 'Patient name, UID or visit number…', 'ma-lumiere-clinic' ); ?>" />
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-vs-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
			<select id="ml-vs-status" name="ml_status" class="ml-control">
				<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
				<?php foreach ( ML_Visit_Repository::statuses() as $option ) : ?>
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
			<p><?php echo ( '' !== $ml_search || '' !== $ml_status ) ? esc_html__( 'No visits match your filters.', 'ma-lumiere-clinic' ) : esc_html__( 'No visits recorded yet.', 'ma-lumiere-clinic' ); ?></p>
		</div>
	<?php else : ?>
		<table class="widefat striped ml-clinic-list-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Visit', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Chief complaint', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
					<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $result['items'] as $visit ) :
					$visit_patient = trim( (string) $visit['p_first_name'] . ' ' . (string) $visit['p_last_name'] );
					?>
					<tr>
						<td>
							<details><summary><strong><?php echo esc_html( $visit['visit_number'] ); ?></strong></summary>
								<div class="ml-clinic-expand">
									<p><strong><?php esc_html_e( 'Diagnosis', 'ma-lumiere-clinic' ); ?>:</strong> <?php echo esc_html( $visit['diagnosis'] ? $visit['diagnosis'] : '—' ); ?></p>
									<p><strong><?php esc_html_e( 'Clinical notes', 'ma-lumiere-clinic' ); ?>:</strong> <?php echo esc_html( $visit['clinical_notes'] ? $visit['clinical_notes'] : '—' ); ?></p>
									<p><strong><?php esc_html_e( 'Treatment plan', 'ma-lumiere-clinic' ); ?>:</strong> <?php echo esc_html( $visit['treatment_plan'] ? $visit['treatment_plan'] : '—' ); ?></p>
								</div>
							</details>
						</td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-patients', 'view' => (int) $visit['patient_id'] ), $ml_base ) ); ?>">
								<?php echo esc_html( '' !== $visit_patient ? $visit_patient : '#' . (int) $visit['patient_id'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( ml_date( $visit['visit_date'], 'd M Y' ) ); ?></td>
						<td><?php echo esc_html( $visit['chief_complaint'] ? $visit['chief_complaint'] : '—' ); ?></td>
						<td>
							<span class="ml-badge ml-badge--<?php echo esc_attr( 'open' === $visit['status'] ? 'success' : 'muted' ); ?>">
								<?php echo esc_html( ucfirst( $visit['status'] ) ); ?>
							</span>
							<?php if ( ! empty( $visit['is_locked'] ) ) : ?>
								<span class="ml-badge ml-badge--danger"><?php esc_html_e( 'Locked', 'ma-lumiere-clinic' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="ml-col-actions">
							<div class="ml-inline-actions">
								<?php if ( $ml_can_manage ) : ?>
									<form method="post" class="ml-form-inline">
										<input type="hidden" name="ml_action" value="<?php echo empty( $visit['is_locked'] ) ? 'lock' : 'unlock'; ?>" />
										<input type="hidden" name="visit_id" value="<?php echo esc_attr( (int) $visit['id'] ); ?>" />
										<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
										<button class="button button-small"><?php echo empty( $visit['is_locked'] ) ? esc_html__( 'Lock', 'ma-lumiere-clinic' ) : esc_html__( 'Unlock', 'ma-lumiere-clinic' ); ?></button>
									</form>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $result['pages'] > 1 ) : ?>
			<div class="ml-clinic-pagination">
				<?php if ( $result['page'] > 1 ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'paged' => $result['page'] - 1, 's' => $ml_search, 'ml_status' => $ml_status ), $ml_page_url ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
				<?php endif; ?>
				<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $result['page'], $result['pages'] ) ); ?></span>
				<?php if ( $result['page'] < $result['pages'] ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'paged' => $result['page'] + 1, 's' => $ml_search, 'ml_status' => $ml_status ), $ml_page_url ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>