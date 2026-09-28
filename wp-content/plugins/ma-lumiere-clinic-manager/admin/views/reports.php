<?php
/**
 * Reports page: date-range activity + financial summary with CSV/PDF export.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ML_Reports' ) ) {
	require_once ML_CLINIC_PATH . 'includes/class-reports.php';
}

$ml_r_from    = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
$ml_r_to      = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
list( $ml_r_from, $ml_r_to ) = ML_Reports::parse_range( $ml_r_from, $ml_r_to );
$ml_r          = ML_Reports::summary( $ml_r_from, $ml_r_to );
$ml_r_nonce    = wp_create_nonce( 'ml_export_report' );
$ml_r_page_url = esc_url( add_query_arg( 'page', 'ml-clinic-reports', admin_url( 'admin.php' ) ) );
$ml_r_doc      = isset( $_GET['doctor'] ) ? absint( $_GET['doctor'] ) : 0;
$ml_r_clinicians = ML_Reports::clinicians( $ml_r_from, $ml_r_to );
if ( $ml_r_doc ) {
	$ml_r_doc_rows = array_values( array_filter(
		$ml_r_clinicians,
		static function ( $c ) use ( $ml_r_doc ) {
			return (int) $c['doctor_user_id'] === (int) $ml_r_doc;
		}
	) );
} else {
	$ml_r_doc_rows = $ml_r_clinicians;
}
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Reports', 'ma-lumiere-clinic' ); ?></h1>
		<p class="ml-clinic-subtitle"><?php esc_html_e( 'Activity and financial summary for the selected period.', 'ma-lumiere-clinic' ); ?></p>
	</div>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ml-form-filter ml-report-filter">
		<input type="hidden" name="page" value="ml-clinic-reports">
		<p class="ml-field">
			<label class="ml-field__label" for="ml-r-from"><?php esc_html_e( 'From', 'ma-lumiere-clinic' ); ?></label>
			<input class="ml-control" type="date" id="ml-r-from" name="from" value="<?php echo esc_attr( $ml_r_from ); ?>">
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-r-to"><?php esc_html_e( 'To', 'ma-lumiere-clinic' ); ?></label>
			<input class="ml-control" type="date" id="ml-r-to" name="to" value="<?php echo esc_attr( $ml_r_to ); ?>">
		</p>
		<p class="ml-field">
			<label class="ml-field__label" for="ml-r-doctor"><?php esc_html_e( 'Clinician', 'ma-lumiere-clinic' ); ?></label>
			<select class="ml-control" id="ml-r-doctor" name="doctor">
				<option value="0"><?php esc_html_e( 'All clinicians', 'ma-lumiere-clinic' ); ?></option>
				<?php foreach ( $ml_r_clinicians as $ml_c ) : ?>
					<option value="<?php echo esc_attr( (int) $ml_c['doctor_user_id'] ); ?>" <?php selected( (int) $ml_r_doc, (int) $ml_c['doctor_user_id'] ); ?>><?php echo esc_html( $ml_c['doctor_name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="ml-field">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'View period', 'ma-lumiere-clinic' ); ?></button>
			<a class="button" href="<?php echo esc_url( $ml_r_page_url ); ?>"><?php esc_html_e( 'Reset', 'ma-lumiere-clinic' ); ?></a>
		</p>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ml-form-actions ml-report-export">
		<input type="hidden" name="action" value="ml_export_report">
		<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $ml_r_nonce ); ?>">
		<input type="hidden" name="from" value="<?php echo esc_attr( $ml_r_from ); ?>">
		<input type="hidden" name="to" value="<?php echo esc_attr( $ml_r_to ); ?>">
		<input type="hidden" name="doctor" value="<?php echo esc_attr( (int) $ml_r_doc ); ?>">
		<button type="submit" name="export" value="csv" class="button"><?php esc_html_e( 'Export CSV', 'ma-lumiere-clinic' ); ?></button>
		<button type="submit" name="export" value="pdf" class="button"><?php esc_html_e( 'Export PDF', 'ma-lumiere-clinic' ); ?></button>
	</form>

	<p class="ml-clinic-note">
		<?php
		printf(
			/* translators: 1: from date, 2: to date */
			esc_html__( 'Period: %1$s to %2$s. Revenue is collected payments; refunds are listed separately; outstanding is the total open balance across all invoices.', 'ma-lumiere-clinic' ),
			'<strong>' . esc_html( $ml_r_from ) . '</strong>',
			'<strong>' . esc_html( $ml_r_to ) . '</strong>'
		);
		?>
	</p>

	<div class="ml-clinic-cards">
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Patients Registered', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $ml_r['patients_created'] ) ); ?></strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Appointments', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $ml_r['appointments'] ) ); ?></strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Visits', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $ml_r['visits'] ) ); ?></strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Prescriptions Finalized', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $ml_r['prescriptions_finalized'] ) ); ?></strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Treatment Sessions', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $ml_r['treatment_sessions'] ) ); ?></strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Follow-ups', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( number_format_i18n( (int) $ml_r['followups'] ) ); ?></strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Revenue', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( ml_money( $ml_r['revenue'] ) ); ?></strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Refunds', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( ml_money( $ml_r['refunds'] ) ); ?></strong>
		</div>
		<div class="ml-clinic-card">
			<span class="ml-clinic-card__label"><?php esc_html_e( 'Outstanding', 'ma-lumiere-clinic' ); ?></span>
			<strong class="ml-clinic-card__value"><?php echo esc_html( ml_money( $ml_r['outstanding'] ) ); ?></strong>
		</div>
	</div>

	<h2 class="ml-clinic-section-title"><?php esc_html_e( 'Daily breakdown', 'ma-lumiere-clinic' ); ?></h2>

	<div class="ml-report-scroll">
		<table class="widefat striped ml-report-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
					<th class="num"><?php esc_html_e( 'Invoices issued', 'ma-lumiere-clinic' ); ?></th>
					<th class="num"><?php esc_html_e( 'Collected', 'ma-lumiere-clinic' ); ?></th>
					<th class="num"><?php esc_html_e( 'Refunded', 'ma-lumiere-clinic' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( (array) $ml_r['daily'] as $ml_day ) : ?>
					<tr>
						<td><?php echo esc_html( $ml_day['date'] ); ?></td>
						<td class="num"><?php echo esc_html( number_format_i18n( (int) $ml_day['invoices'] ) ); ?></td>
						<td class="num"><?php echo esc_html( ml_money( $ml_day['collected'] ) ); ?></td>
						<td class="num"><?php echo esc_html( ml_money( $ml_day['refunds'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<h2 class="ml-clinic-section-title"><?php esc_html_e( 'Activity by clinician', 'ma-lumiere-clinic' ); ?></h2>

	<?php if ( ! $ml_r_doc_rows ) : ?>
		<p class="ml-clinic-empty"><?php esc_html_e( 'No clinician activity in this period.', 'ma-lumiere-clinic' ); ?></p>
	<?php else : ?>
		<div class="ml-report-scroll">
			<table class="widefat striped ml-report-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Clinician', 'ma-lumiere-clinic' ); ?></th>
						<th class="num"><?php esc_html_e( 'Appointments', 'ma-lumiere-clinic' ); ?></th>
						<th class="num"><?php esc_html_e( 'Visits', 'ma-lumiere-clinic' ); ?></th>
						<th class="num"><?php esc_html_e( 'Prescriptions finalized', 'ma-lumiere-clinic' ); ?></th>
						<th class="num"><?php esc_html_e( 'Treatment sessions', 'ma-lumiere-clinic' ); ?></th>
						<th class="num"><?php esc_html_e( 'Follow-ups', 'ma-lumiere-clinic' ); ?></th>
						<th class="num"><?php esc_html_e( 'Revenue', 'ma-lumiere-clinic' ); ?></th>
						<th class="num"><?php esc_html_e( 'Refunds', 'ma-lumiere-clinic' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ml_r_doc_rows as $ml_c ) : ?>
						<tr>
							<td><?php echo esc_html( $ml_c['doctor_name'] ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( (int) $ml_c['appointments'] ) ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( (int) $ml_c['visits'] ) ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( (int) $ml_c['prescriptions_finalized'] ) ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( (int) $ml_c['treatment_sessions'] ) ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( (int) $ml_c['followups'] ) ); ?></td>
							<td class="num"><?php echo esc_html( ml_money( $ml_c['revenue'] ) ); ?></td>
							<td class="num"><?php echo esc_html( ml_money( $ml_c['refunds'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>