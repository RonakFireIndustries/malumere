<?php
/**
 * Patients list.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_can_create = current_user_can( 'ml_create_patients' );
$ml_can_edit   = current_user_can( 'ml_edit_patients' );
$ml_base       = admin_url( 'admin.php' );
$ml_page_url   = add_query_arg( 'page', 'ml-clinic-patients', $ml_base );
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Patients', 'ma-lumiere-clinic' ); ?></h1>
		<?php if ( $ml_can_create ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'action', 'new', $ml_page_url ) ); ?>">
				<?php esc_html_e( 'Add Patient', 'ma-lumiere-clinic' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php include ML_CLINIC_PATH . 'admin/views/_notice.php'; ?>

	<form class="ml-form-filter" method="get">
		<input type="hidden" name="page" value="ml-clinic-patients" />
		<p class="ml-field">
			<label class="ml-field__label" for="ml-pt-search"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></label>
			<input id="ml-pt-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_attr_e( 'Name, UID, phone or email…', 'ma-lumiere-clinic' ); ?>" />
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-pt-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
			<select id="ml-pt-status" name="ml_status" class="ml-control">
				<option value=""><?php esc_html_e( 'All statuses', 'ma-lumiere-clinic' ); ?></option>
				<?php foreach ( ML_Patient_Repository::statuses() as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $status, $option ); ?>>
						<?php echo esc_html( ucfirst( $option ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="ml-field">
			<button type="submit" class="button"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></button>
			<?php if ( '' !== $search || '' !== $status ) : ?>
				<a class="button button-link" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Reset', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
		</p>
	</form>

	<?php if ( empty( $result['items'] ) ) : ?>
		<div class="ml-clinic-empty">
			<p><?php echo ( '' !== $search || '' !== $status ) ? esc_html__( 'No patients match your filters.', 'ma-lumiere-clinic' ) : esc_html__( 'No patients registered yet.', 'ma-lumiere-clinic' ); ?></p>
			<?php if ( $ml_can_create ) : ?>
				<a class="button button-primary ml-u-mt-sm" href="<?php echo esc_url( add_query_arg( 'action', 'new', $ml_page_url ) ); ?>"><?php esc_html_e( 'Register your first patient', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<table class="widefat striped ml-clinic-list-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Patient ID', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Name', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Contact', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Gender / Age', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Registered', 'ma-lumiere-clinic' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
					<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $result['items'] as $patient ) : $age = ML_Patient_Repository::age( $patient ); ?>
					<tr>
						<td><strong><?php echo esc_html( $patient['patient_uid'] ); ?></strong></td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( 'view', $patient['id'], $ml_page_url ) ); ?>">
								<?php echo esc_html( trim( $patient['first_name'] . ' ' . $patient['last_name'] ) ); ?>
							</a>
							<?php if ( ! empty( $patient['email'] ) ) : ?>
								<br /><span class="ml-muted"><?php echo esc_html( $patient['email'] ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $patient['phone'] ) ) : ?>
								<?php echo esc_html( $patient['phone'] ); ?>
							<?php else : ?>
								<span class="ml-muted">&mdash;</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $patient['gender'] ) ) : ?>
								<?php echo esc_html( ucfirst( $patient['gender'] ) ); ?>
							<?php else : ?>
								<span class="ml-muted">—</span>
							<?php endif; ?>
							<?php if ( '' !== $age ) : ?><br /><span class="ml-muted"><?php echo esc_html( $age . ' ' . __( 'yrs', 'ma-lumiere-clinic' ) ); ?></span><?php endif; ?>
						</td>
						<td><?php echo esc_html( ml_date( $patient['registration_date'], 'd M Y' ) ); ?></td>
						<td>
							<span class="ml-badge ml-badge--<?php echo esc_attr( 'active' === $patient['status'] ? 'success' : 'muted' ); ?>">
								<?php echo esc_html( ucfirst( $patient['status'] ) ); ?>
							</span>
						</td>
						<td class="ml-col-actions">
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'view', $patient['id'], $ml_page_url ) ); ?>"><?php esc_html_e( 'View', 'ma-lumiere-clinic' ); ?></a>
							<?php if ( $ml_can_edit ) : ?>
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $patient['id'] ), $ml_page_url ) ); ?>"><?php esc_html_e( 'Edit', 'ma-lumiere-clinic' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $result['pages'] > 1 ) : ?>
			<div class="ml-clinic-pagination">
				<?php if ( $result['page'] > 1 ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'paged' => $result['page'] - 1, 's' => $search, 'ml_status' => $status ), $ml_page_url ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
				<?php endif; ?>
				<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $result['page'], $result['pages'] ) ); ?></span>
				<?php if ( $result['page'] < $result['pages'] ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'paged' => $result['page'] + 1, 's' => $search, 'ml_status' => $status ), $ml_page_url ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>