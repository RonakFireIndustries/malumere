<?php
/**
 * Audit log listing. Read-only, capability-gated, paginated.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'ml_view_audit_logs' ) ) {
	wp_die( esc_html__( 'You are not allowed to view this page.', 'ma-lumiere-clinic' ), 403 );
}

$ml_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ml_total = ML_Audit_Log::count();
$ml_logs  = ML_Audit_Log::query( array( 'limit' => 50 ) );
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Audit Logs', 'ma-lumiere-clinic' ); ?></h1>
		<p class="ml-clinic-subtitle">
			<?php
			printf(
				/* translators: %d: total entries */
				esc_html__( '%d recorded events', 'ma-lumiere-clinic' ),
				esc_html( number_format_i18n( $ml_total ) )
			);
			?>
		</p>
	</div>

	<?php if ( empty( $ml_logs ) ) : ?>
		<div class="ml-clinic-empty">
			<p><?php esc_html_e( 'No audit entries yet.', 'ma-lumiere-clinic' ); ?></p>
		</div>
	<?php else : ?>
		<table class="widefat striped ml-clinic-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Action', 'ma-lumiere-clinic' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Entity', 'ma-lumiere-clinic' ); ?></th>
					<th scope="col"><?php esc_html_e( 'ID', 'ma-lumiere-clinic' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Description', 'ma-lumiere-clinic' ); ?></th>
					<th scope="col"><?php esc_html_e( 'User', 'ma-lumiere-clinic' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $ml_logs as $ml_log ) : ?>
					<tr>
						<td><?php echo esc_html( ml_date( $ml_log['created_at'], 'd M Y H:i' ) ); ?></td>
						<td><code><?php echo esc_html( $ml_log['action'] ); ?></code></td>
						<td><?php echo esc_html( $ml_log['entity_type'] ); ?></td>
						<td><?php echo esc_html( $ml_log['entity_id'] ); ?></td>
						<td><?php echo esc_html( $ml_log['description'] ); ?></td>
						<td><?php echo esc_html( $ml_log['user_id'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>