<?php
/**
 * Prescriptions list + finalize / correct / PDF download actions.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_can_manage = current_user_can( 'ml_manage_clinic' ) || current_user_can( 'ml_manage_prescriptions' );
$ml_base       = admin_url( 'admin.php' );
$ml_page_url   = add_query_arg( 'page', 'ml-clinic-prescriptions', $ml_base );
$ml_error      = '';

// Finalize a draft or correct a finalized prescription.
if ( $ml_can_manage && isset( $_POST['ml_action'], $_POST['rx_id'], $_POST['_wpnonce'] )
	&& in_array( sanitize_key( $_POST['ml_action'] ), array( 'finalize', 'correct' ), true ) ) {
	if ( ML_Security::verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) ) ) {
		$rx_id  = absint( $_POST['rx_id'] );
		$action = sanitize_key( $_POST['ml_action'] );
		$row    = ML_Prescription_Repository::get( $rx_id );
		if ( $row && ML_Security::can_access_patient( (int) $row['patient_id'] ) ) {
			$result = 'finalize' === $action
				? ML_Prescription_Service::finalize( $rx_id )
				: ML_Prescription_Service::correct( $rx_id );
			if ( is_wp_error( $result ) ) {
				$ml_error = $result->get_error_message();
			} else {
				wp_safe_redirect( add_query_arg( array( 'paged' => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ), $ml_page_url ) );
				exit;
			}
		} else {
			$ml_error = __( 'You are not allowed to access this prescription.', 'ma-lumiere-clinic' );
		}
	}
}

$ml_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$ml_status = isset( $_GET['ml_status'] ) ? sanitize_key( wp_unslash( $_GET['ml_status'] ) ) : '';
$ml_page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

$result = ML_Prescription_Repository::list(
	array(
		'page'     => $ml_page,
		'per_page' => 20,
		'status'   => $ml_status,
		'search'   => $ml_search,
	)
);

$ml_pdf_nonce = wp_create_nonce( ML_Security::NONCE_ACTION );
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Prescriptions', 'ma-lumiere-clinic' ); ?></h1>
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
		<input type="hidden" name="page" value="ml-clinic-prescriptions" />
		<p class="ml-field">
			<label class="ml-field__label" for="ml-rx-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
			<input id="ml-rx-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_search ); ?>"
				placeholder="<?php esc_attr_e( 'Patient name, UID or prescription number…', 'ma-lumiere-clinic' ); ?>" />
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-rx-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
			<select id="ml-rx-status" name="ml_status" class="ml-control">
				<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
				<?php foreach ( ML_Prescription_Repository::statuses() as $option ) : ?>
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
			<p><?php echo ( '' !== $ml_search || '' !== $ml_status ) ? esc_html__( 'No prescriptions match your filters.', 'ma-lumiere-clinic' ) : esc_html__( 'No prescriptions recorded yet.', 'ma-lumiere-clinic' ); ?></p>
		</div>
	<?php else : ?>
		<table class="widefat striped ml-clinic-list-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Number', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Items', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Version', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
					<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $result['items'] as $rx ) :
					$rx_patient = trim( (string) $rx['p_first_name'] . ' ' . (string) $rx['p_last_name'] );
					$rx_items   = ML_Prescription_Repository::items( (int) $rx['id'] );
					$rx_final   = 'final' === (string) $rx['status'];
					$rx_num     = $rx_final ? $rx['prescription_number'] : __( '— (draft)', 'ma-lumiere-clinic' );
					?>
					<tr>
						<td><strong><?php echo esc_html( $rx_num ); ?></strong></td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-patients', 'view' => (int) $rx['patient_id'] ), $ml_base ) ); ?>">
								<?php echo esc_html( '' !== $rx_patient ? $rx_patient : '#' . (int) $rx['patient_id'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( ml_date( $rx['prescription_date'], 'd M Y' ) ); ?></td>
						<td>
							<details><summary><?php echo esc_html( sprintf( _n( '%d item', '%d items', count( $rx_items ), 'ma-lumiere-clinic' ), count( $rx_items ) ) ); ?></summary>
								<ul class="ml-clinic-expand__list">
									<?php foreach ( $rx_items as $item ) : ?>
										<li>
											<strong><?php echo esc_html( $item['medicine_name'] ); ?></strong>
											<?php if ( $item['dosage'] ) : ?> &mdash; <?php echo esc_html( $item['dosage'] ); ?><?php endif; ?>
											<?php if ( $item['frequency'] ) : ?> &middot; <?php echo esc_html( $item['frequency'] ); ?><?php endif; ?>
											<?php if ( $item['duration'] ) : ?> &middot; <?php echo esc_html( $item['duration'] ); ?><?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</details>
						</td>
						<td><?php echo esc_html( 'v' . (int) $rx['version'] ); ?></td>
						<td>
							<span class="ml-badge ml-badge--<?php echo esc_attr( $rx_final ? 'success' : 'warning' ); ?>">
								<?php echo esc_html( ucfirst( $rx['status'] ) ); ?>
							</span>
						</td>
						<td class="ml-col-actions">
							<div class="ml-inline-actions">
								<?php if ( $ml_can_manage ) : ?>
									<?php if ( ! $rx_final ) : ?>
										<form method="post" class="ml-form-inline">
											<input type="hidden" name="ml_action" value="finalize" />
											<input type="hidden" name="rx_id" value="<?php echo esc_attr( (int) $rx['id'] ); ?>" />
											<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
											<button class="button button-small button-primary" onclick="return confirm('<?php echo esc_js( __( 'Finalize this prescription? It will be locked and issued.', 'ma-lumiere-clinic' ) ); ?>');">
												<?php esc_html_e( 'Finalize', 'ma-lumiere-clinic' ); ?>
											</button>
										</form>
									<?php else : ?>
										<a class="button button-small" target="_blank" rel="noopener"
											href="<?php echo esc_url( add_query_arg( array( 'action' => 'ml_prescription_pdf', 'id' => (int) $rx['id'], '_wpnonce' => $ml_pdf_nonce ), admin_url( 'admin-ajax.php' ) ) ); ?>">
											<?php esc_html_e( 'PDF', 'ma-lumiere-clinic' ); ?>
										</a>
										<form method="post" class="ml-form-inline">
											<input type="hidden" name="ml_action" value="correct" />
											<input type="hidden" name="rx_id" value="<?php echo esc_attr( (int) $rx['id'] ); ?>" />
											<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
											<button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Open a correction draft? The original is kept untouched.', 'ma-lumiere-clinic' ) ); ?>');">
												<?php esc_html_e( 'Correct', 'ma-lumiere-clinic' ); ?>
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