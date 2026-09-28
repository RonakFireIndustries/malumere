<?php
/**
 * Clinic Dashboard shell.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_clinic_name = ML_Settings::get( 'clinic.clinic_name', __( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ) );
$ml_stats_loaded = false;
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php echo esc_html( $ml_clinic_name ); ?></h1>
		<p class="ml-clinic-subtitle"><?php esc_html_e( 'Clinic management foundation', 'ma-lumiere-clinic' ); ?></p>
	</div>

	<div class="ml-clinic-cards" id="ml-dashboard-stats" data-loaded="0">
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Total Patients', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="total_patients">0</strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( "Today's Appointments", 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="today_appointments">0</strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Upcoming Appointments', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="upcoming_appointments">0</strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Pending Follow-ups', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="pending_followups">0</strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Open Visits', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="open_visits">0</strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Draft Prescriptions', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="draft_prescriptions">0</strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Finalized Prescriptions', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="final_prescriptions">0</strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( "Today's Revenue", 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="today_revenue">--</strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Pending Payments', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value" data-stat="pending_payments">--</strong>
		</div>
	</div>

	<?php
	/*
	 * Stock alerts are rendered server-side rather than through the stats
	 * AJAX call: a shortage needs to be actionable even before the dashboard
	 * finishes loading, and the figures come from a different set of tables.
	 */
	$ml_inventory_alerts = ( current_user_can( 'ml_view_inventory' ) || current_user_can( 'ml_manage_clinic' ) )
		? ML_Inventory_Service::alerts()
		: null;
	?>

	<?php if ( is_array( $ml_inventory_alerts ) ) : ?>
		<div class="ml-clinic-quicklinks">
			<h2 class="ml-clinic-section-title"><?php esc_html_e( 'Stock Alerts', 'ma-lumiere-clinic' ); ?></h2>
			<?php
			$ml_alert_rows = array(
				__( 'Out of stock', 'ma-lumiere-clinic' )   => count( $ml_inventory_alerts['out_of_stock'] ),
				__( 'At reorder level', 'ma-lumiere-clinic' ) => count( $ml_inventory_alerts['low_stock'] ),
				__( 'Expiring in 90 days', 'ma-lumiere-clinic' ) => count( $ml_inventory_alerts['expiring_soon'] ),
				__( 'Expired, still held', 'ma-lumiere-clinic' )  => count( $ml_inventory_alerts['expired'] ),
			);
			?>
			<p>
				<?php foreach ( $ml_alert_rows as $ml_label => $ml_count ) : ?>
					<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-inventory', 'tab' => 'alerts' ), admin_url( 'admin.php' ) ) ); ?>">
						<?php
						echo esc_html(
							0 < $ml_count
								? sprintf( '%1$s: %2$d', $ml_label, $ml_count )
								: $ml_label
						);
						?>
					</a>
				<?php endforeach; ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="ml-clinic-quicklinks">
		<h2 class="ml-clinic-section-title"><?php esc_html_e( 'Shortcuts', 'ma-lumiere-clinic' ); ?></h2>
		<p>
			<?php if ( current_user_can( 'ml_manage_visits' ) || current_user_can( 'ml_manage_clinic' ) ) : ?>
				<a class="button" href="<?php echo esc_url( add_query_arg( 'page', 'ml-clinic-visits', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Visits', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
			<?php if ( current_user_can( 'ml_manage_prescriptions' ) || current_user_can( 'ml_manage_clinic' ) ) : ?>
				<a class="button" href="<?php echo esc_url( add_query_arg( 'page', 'ml-clinic-prescriptions', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Prescriptions', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
			<?php if ( current_user_can( 'ml_view_inventory' ) || current_user_can( 'ml_manage_clinic' ) ) : ?>
				<a class="button" href="<?php echo esc_url( add_query_arg( 'page', 'ml-clinic-inventory', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Inventory', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
			<?php if ( current_user_can( 'ml_manage_followups' ) || current_user_can( 'ml_manage_clinic' ) ) : ?>
				<a class="button" href="<?php echo esc_url( add_query_arg( 'page', 'ml-clinic-followups', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Follow-ups', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'page', 'ml-clinic-patients', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Patients', 'ma-lumiere-clinic' ); ?></a>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'page', 'ml-clinic-appointments', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Appointments', 'ma-lumiere-clinic' ); ?></a>
		</p>
	</div>

	<p class="ml-clinic-note">
		<?php esc_html_e( 'Real figures load automatically from the clinic database. Empty records display as 0 — no sample data is shown.', 'ma-lumiere-clinic' ); ?>
	</p>
</div>