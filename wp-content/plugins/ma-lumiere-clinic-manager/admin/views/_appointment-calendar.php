<?php
/**
 * Appointment calendar grids: Day, Week, Month (server-rendered).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'ml_appt_status_chip' ) ) {
	function ml_appt_status_chip( $status ) {
		$map = array(
			'pending'    => 'warning',
			'confirmed'  => 'success',
			'checked_in' => 'info',
			'completed'  => 'muted',
			'cancelled'  => 'danger',
			'rescheduled' => 'muted',
			'no_show'    => 'danger',
		);
		$tone = isset( $map[ $status ] ) ? $map[ $status ] : 'muted';
		$label = ucfirst( (string) $status );
		return '<span class="ml-badge ml-badge--' . esc_attr( $tone ) . '">' . esc_html( $label ) . '</span>';
	}
}

$ml_hours = ML_Settings::hours();
$ml_open  = 540;
$ml_close = 1020;
foreach ( array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ) as $ml_day_key ) { // phpcs:ignore
	if ( isset( $ml_hours[ $ml_day_key ]['open'], $ml_hours[ $ml_day_key ]['close'] ) ) {
		$ml_open  = min( $ml_open, ML_Appointment_Repository::minute_of_day( $ml_hours[ $ml_day_key ]['open'] ) );
		$ml_close = max( $ml_close, ML_Appointment_Repository::minute_of_day( $ml_hours[ $ml_day_key ]['close'] ) );
	}
}

if ( 'day' === $ml_view ) :

	$ml_day_items = ML_Appointment_Repository::for_date( $ml_anchor );
	$ml_dowkey = array( 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' )[ (int) date_i18n( 'w', strtotime( $ml_anchor ) ) ];
	$ml_day_hours = isset( $ml_hours[ $ml_dowkey ] ) ? $ml_hours[ $ml_dowkey ] : null;
	?>
	<div class="ml-appt-cal ml-appt-cal--day">
		<div class="ml-appt-cal__head">
			<strong><?php echo esc_html( date_i18n( 'l, d F Y', strtotime( $ml_anchor ) ) ); ?></strong>
			<?php if ( ! $ml_day_hours ) : ?>
				<span class="ml-badge ml-badge--muted"><?php esc_html_e( 'Closed', 'ma-lumiere-clinic' ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( ! $ml_day_hours ) : ?>
			<div class="ml-clinic-empty"><p><?php esc_html_e( 'The clinic is closed on this day.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php else : ?>
			<div class="ml-appt-cal__timeline">
				<?php
				$ml_from = ML_Appointment_Repository::minute_of_day( $ml_day_hours['open'] );
				$ml_to   = ML_Appointment_Repository::minute_of_day( $ml_day_hours['close'] );
				for ( $ml_t = $ml_from; $ml_t < $ml_to; $ml_t += 30 ) {
					$ml_time = sprintf( '%02d:%02d', intdiv( $ml_t, 60 ), $ml_t % 60 );
					echo '<div class="ml-appt-cal__row"><span class="ml-appt-cal__time">' . esc_html( $ml_time ) . '</span><div class="ml-appt-cal__slot">';
					foreach ( $ml_day_items as $ml_item ) {
						$ml_item_start = ML_Appointment_Repository::minute_of_day( $ml_item['start_time'] );
						if ( $ml_item_start === $ml_t && ! in_array( $ml_item['status'], array( 'cancelled', 'rescheduled', 'no_show' ), true ) ) {
							$ml_item_user  = get_userdata( (int) $ml_item['doctor_user_id'] );
							$ml_item_doc   = $ml_item_user instanceof WP_User ? trim( (string) $ml_item_user->display_name ) : '';
							echo '<div class="ml-appt-cal__card ml-appt-cal__card--' . esc_attr( $ml_item['status'] ) . '">'
								. '<strong>' . esc_html( $ml_item['patient_name'] ? $ml_item['patient_name'] : '—' ) . '</strong>'
								. ' <span class="ml-muted">(' . esc_html( mb_substr( (string) $ml_item['start_time'], 0, 5 ) . '–' . mb_substr( (string) $ml_item['end_time'], 0, 5 ) ) . ')</span>'
								. ( $ml_item_doc ? '<br /><span class="ml-muted">' . esc_html( $ml_item_doc ) . '</span>' : '' )
								. '<br /><span class="ml-muted">' . esc_html( $ml_item['treatment_name'] ? $ml_item['treatment_name'] : '' ) . '</span>'
								. ' ' . ml_appt_status_chip( $ml_item['status'] )
								. ( $ml_can_manage ? '<br /><button type="button" class="button button-small" data-ml-open="detail" data-ml-id="' . esc_attr( $ml_item['id'] ) . '">' . esc_html__( 'View', 'ma-lumiere-clinic' ) . '</button>' : '' )
								. '</div>';
							break;
						}
					}
					echo '</div></div>';
				}
				foreach ( $ml_day_items as $ml_item ) {
					if ( in_array( $ml_item['status'], array( 'cancelled', 'rescheduled', 'no_show' ), true ) ) {
						echo '<div class="ml-appt-cal__closed">' . esc_html( mb_substr( (string) $ml_item['start_time'], 0, 5 ) )
							. ' — ' . esc_html( $ml_item['patient_name'] ? $ml_item['patient_name'] : '—' ) . ' ' . ml_appt_status_chip( $ml_item['status'] ) . '</div>';
					}
				}
				?>
			</div>
		<?php endif; ?>
	</div>

<?php elseif ( 'week' === $ml_view ) : ?>

	<?php
	$ml_monday = strtotime( 'monday this week', strtotime( $ml_anchor ) );
	$ml_dates  = array();
	for ( $ml_i = 0; $ml_i < 7; $ml_i++ ) {
		$ml_dates[] = (string) date_i18n( 'Y-m-d', strtotime( '+' . $ml_i . ' days', $ml_monday ) );
	}
	$ml_week_items = array();
	foreach ( $ml_dates as $ml_d ) {
		$ml_week_items[ $ml_d ] = ML_Appointment_Repository::for_date( $ml_d );
	}
	?>
	<div class="ml-appt-cal ml-appt-cal--week">
		<div class="ml-appt-cal__cols">
			<?php foreach ( $ml_dates as $ml_d ) : $ml_dow = array( 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' )[ (int) date_i18n( 'w', strtotime( $ml_d ) ) ]; ?>
				<div class="ml-appt-cal__col <?php echo $ml_d === $ml_anchor ? 'is-today' : ''; ?>">
					<div class="ml-appt-cal__colhead">
						<?php echo esc_html( date_i18n( 'D', strtotime( $ml_d ) ) ); ?>
						<strong><?php echo esc_html( (int) date_i18n( 'j', strtotime( $ml_d ) ) ); ?></strong>
						<?php if ( isset( $ml_hours[ $ml_dow ] ) ) : ?>
							<span class="ml-muted"><?php echo esc_html( mb_substr( (string) $ml_hours[ $ml_dow ]['open'], 0, 5 ) . '–' . mb_substr( (string) $ml_hours[ $ml_dow ]['close'], 0, 5 ) ); ?></span>
						<?php else : ?>
							<span class="ml-badge ml-badge--muted"><?php esc_html_e( 'Closed', 'ma-lumiere-clinic' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="ml-appt-cal__colbody">
						<?php if ( empty( $ml_week_items[ $ml_d ] ) ) : ?>
							<p class="ml-muted ml-appt-cal__none"><?php esc_html_e( 'No appointments', 'ma-lumiere-clinic' ); ?></p>
						<?php else : foreach ( $ml_week_items[ $ml_d ] as $ml_item ) :
							$ml_item_user = get_userdata( (int) $ml_item['doctor_user_id'] );
							$ml_item_doc  = $ml_item_user instanceof WP_User ? trim( (string) $ml_item_user->display_name ) : '';
							?>
							<div class="ml-appt-cal__card ml-appt-cal__card--<?php echo esc_attr( $ml_item['status'] ); ?>">
								<strong><?php echo esc_html( mb_substr( (string) $ml_item['start_time'], 0, 5 ) ); ?></strong>
								<span><?php echo esc_html( $ml_item['patient_name'] ? $ml_item['patient_name'] : '—' ); ?></span>
								<?php if ( $ml_item_doc ) : ?><span class="ml-muted"><?php echo esc_html( $ml_item_doc ); ?></span><?php endif; ?>
								<?php echo ml_appt_status_chip( $ml_item['status'] ); ?>
								<?php if ( $ml_can_manage && ! in_array( $ml_item['status'], array( 'completed', 'cancelled', 'rescheduled', 'no_show' ), true ) ) : ?>
									<span class="ml-appt-cal__mini">
										<button type="button" class="button button-small" data-ml-action="checkin" data-ml-id="<?php echo esc_attr( $ml_item['id'] ); ?>"><?php esc_html_e( 'In', 'ma-lumiere-clinic' ); ?></button>
										<button type="button" class="button button-small" data-ml-action="complete" data-ml-id="<?php echo esc_attr( $ml_item['id'] ); ?>"><?php esc_html_e( 'Done', 'ma-lumiere-clinic' ); ?></button>
										<button type="button" class="button button-small" data-ml-open="reschedule" data-ml-id="<?php echo esc_attr( $ml_item['id'] ); ?>"><?php esc_html_e( 'Move', 'ma-lumiere-clinic' ); ?></button>
									</span>
								<?php endif; ?>
							</div>
						<?php endforeach; endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

<?php elseif ( 'month' === $ml_view ) : ?>

	<?php
	$ml_ym      = (int) date_i18n( 'Ym', strtotime( $ml_anchor ) );
	$ml_first   = strtotime( $ml_anchor ) !== false ? strtotime( date_i18n( 'Y-m-01', strtotime( $ml_anchor ) ) ) : current_time( 'timestamp' );
	$ml_grid    = (int) date_i18n( 'N', $ml_first );
	$ml_cursor  = strtotime( '-' . ( $ml_grid - 1 ) . ' days', $ml_first );
	$ml_cells   = array();
	for ( $ml_c = 0; $ml_c < 42; $ml_c++ ) {
		$ml_cells[] = (string) date_i18n( 'Y-m-d', strtotime( '+' . $ml_c . ' days', $ml_cursor ) );
	}
	$ml_cur_ts  = strtotime( $ml_anchor );
	$ml_month_items = array();
	foreach ( $ml_cells as $ml_d ) {
		$ml_month_items[ $ml_d ] = ML_Appointment_Repository::for_date( $ml_d );
	}
	?>
	<div class="ml-appt-cal ml-appt-cal--month">
		<div class="ml-appt-cal__grid">
			<?php foreach ( array( __( 'Mon', 'ma-lumiere-clinic' ), __( 'Tue', 'ma-lumiere-clinic' ), __( 'Wed', 'ma-lumiere-clinic' ), __( 'Thu', 'ma-lumiere-clinic' ), __( 'Fri', 'ma-lumiere-clinic' ), __( 'Sat', 'ma-lumiere-clinic' ), __( 'Sun', 'ma-lumiere-clinic' ) ) as $ml_cell_head ) : ?>
				<div class="ml-appt-cal__cellhead"><?php echo esc_html( $ml_cell_head ); ?></div>
			<?php endforeach; ?>
			<?php
			foreach ( $ml_cells as $ml_d ) :
				$ml_d_ts = strtotime( $ml_d );
				$ml_in_month = $ml_ym === (int) date_i18n( 'Ym', $ml_d_ts );
				$ml_dow_key = array( 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' )[ (int) date_i18n( 'w', $ml_d_ts ) ];
				$ml_d_closed = ! isset( $ml_hours[ $ml_dow_key ] );
				?>
				<div class="ml-appt-cal__cell <?php echo $ml_in_month ? '' : 'is-dim'; ?> <?php echo $ml_d === $ml_anchor ? 'is-today' : ''; ?>">
					<div class="ml-appt-cal__date">
						<?php echo esc_html( (int) date_i18n( 'j', $ml_d_ts ) ); ?>
						<?php if ( $ml_d_closed ) : ?>
							<span class="ml-muted">·</span>
						<?php endif; ?>
						<?php if ( $ml_can_manage ) : ?>
							<button type="button" class="ml-appt-cal__add" data-ml-open="new" data-ml-date="<?php echo esc_attr( $ml_d ); ?>" title="<?php esc_attr_e( 'New appointment', 'ma-lumiere-clinic' ); ?>">+</button>
						<?php endif; ?>
					</div>
					<?php
					foreach ( (array) $ml_month_items[ $ml_d ] as $ml_item ) {
						$ml_item_user = get_userdata( (int) $ml_item['doctor_user_id'] );
						$ml_item_doc  = $ml_item_user instanceof WP_User ? trim( (string) $ml_item_user->display_name ) : '';
						echo '<a class="ml-appt-cal__chip ml-appt-cal__chip--' . esc_attr( $ml_item['status'] ) . '" href="#" data-ml-open="detail" data-ml-id="' . esc_attr( $ml_item['id'] ) . '">'
							. '<span>' . esc_html( mb_substr( (string) $ml_item['start_time'], 0, 5 ) . ' ' . ( $ml_item['patient_name'] ? $ml_item['patient_name'] : '—' ) ) . '</span>'
							. ( $ml_item_doc ? '<em class="ml-muted">' . esc_html( $ml_item_doc ) . '</em>' : '' )
							. '</a>';
					}
					?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

<?php endif; ?>