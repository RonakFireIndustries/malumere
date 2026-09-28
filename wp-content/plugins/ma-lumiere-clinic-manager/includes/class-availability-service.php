<?php
/**
 * Availability engine: working hours per day, slot generation, clinic
 * breaks, blocked dates, booking horizon and conflict-free scheduling.
 *
 * Absolute truth for "is this slot free" always comes from a DB-level
 * re-check at write time (ML_Appointment_Repository::slot_is_taken()); this
 * service only builds the offered set for the UI. Nothing sensitive —
 * patient counts, identities or reasons — is ever included in its output.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Availability_Service {

	/**
	 * Booking horizon in days.
	 *
	 * @return int
	 */
	public static function max_advance_days() {
		return max( 1, min( 365, (int) ML_Settings::get( 'appointments.max_advance_days', 30 ) ) );
	}

	/**
	 * Booking buffer (minutes) to keep free before/after every booking.
	 *
	 * @return int
	 */
	public static function buffer() {
		return max( 0, min( 120, (int) ML_Settings::get( 'appointments.booking_buffer', 15 ) ) );
	}

	/**
	 * Resolve the doctor to schedule against: explicit valid user, else the
	 * configured default doctor, else 0 (no doctor assignment).
	 *
	 * @param int $doctor_id Requested doctor user ID.
	 *
	 * @return int
	 */
	public static function resolve_doctor( $doctor_id = 0 ) {
		$doctor_id = absint( $doctor_id );
		if ( $doctor_id && get_userdata( $doctor_id ) ) {
			return $doctor_id;
		}
		$fallback = absint( ML_Settings::get( 'appointments.default_doctor', 0 ) );
		return ( $fallback && get_userdata( $fallback ) ) ? $fallback : 0;
	}

	/**
	 * Working hours for a single date (enabled + open/close) or null when
	 * the clinic is closed that day.
	 *
	 * @param string $date Y-m-d.
	 *
	 * @return array|null
	 */
	public static function hours_for( $date ) {
		if ( ! ML_Appointment_Repository::is_valid_date( $date ) ) {
			return null;
		}
		$dow = strtolower( gmdate( 'D', strtotime( $date ) ) );
		$map = array( 'mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun' );
		$dow = isset( $map[ $dow ] ) ? $map[ $dow ] : $dow;

		$hours = ML_Settings::hours();
		if ( empty( $hours[ $dow ] ) || empty( $hours[ $dow ]['enabled'] ) ) {
			return null;
		}
		$open  = (string) $hours[ $dow ]['open'];
		$close = (string) $hours[ $dow ]['close'];
		if ( ! ML_Appointment_Repository::is_valid_time( $open ) || ! ML_Appointment_Repository::is_valid_time( $close ) || $open >= $close ) {
			return null;
		}
		return array(
			'day'   => $dow,
			'open'  => $open,
			'close' => $close,
		);
	}

	/**
	 * Dates the clinic is open for booking within the horizon. Only safe
	 * fields (date + hours); no counts, names or reasons.
	 *
	 * @param int $treatment_id Treatment id.
	 * @param int $doctor_id    Requested doctor.
	 *
	 * @return array
	 */
	public static function open_days( $treatment_id = 0, $doctor_id = 0 ) {
		$days    = array();
		$today   = current_time( 'Y-m-d' );
		$horizon = self::max_advance_days();

		for ( $offset = 0; $offset < $horizon; $offset++ ) {
			$date = gmdate( 'Y-m-d', strtotime( $today ) + ( $offset * DAY_IN_SECONDS ) );
			if ( self::is_all_day_blocked( $date ) ) {
				continue;
			}
			$hours = self::hours_for( $date );
			if ( ! $hours || $hours['open'] >= $hours['close'] ) {
				continue;
			}
			$slots = self::slots_for( $date, $treatment_id, $doctor_id );
			if ( $slots ) {
				$days[] = array(
					'date'  => $date,
					'open'  => $hours['open'],
					'close' => $hours['close'],
				);
			}
		}
		return $days;
	}

	/**
	 * Free booking slots for a date. Returns only time/duration pairs.
	 *
	 * @param string $date         Y-m-d.
	 * @param int    $treatment_id Treatment id.
	 * @param int    $doctor_id    Requested doctor.
	 *
	 * @return array
	 */
	public static function slots_for( $date, $treatment_id = 0, $doctor_id = 0 ) {
		global $wpdb;

		if ( ! ML_Appointment_Repository::is_valid_date( $date ) ) {
			return array();
		}
		if ( self::is_all_day_blocked( $date ) ) {
			return array();
		}
		$hours = self::hours_for( $date );
		if ( ! $hours ) {
			return array();
		}

		$open    = ML_Appointment_Repository::minute_of_day( $hours['open'] );
		$close   = ML_Appointment_Repository::minute_of_day( $hours['close'] );
		$step    = ML_Appointment_Repository::default_duration();
		$duration = ML_Appointment_Repository::effective_duration( (int) $treatment_id );
		$buffer  = self::buffer();
		$doctor  = self::resolve_doctor( $doctor_id );

		$busy = self::busy_minutes( $date, $doctor );
		$busy[] = array( -INF, $open ); // nothing before opening.

		// Blocked partial windows.
		foreach ( self::blocked_windows( $date ) as $block ) {
			if ( $block['all_day'] ) {
				continue;
			}
			$busy[] = array( ML_Appointment_Repository::minute_of_day( $block['start_time'] ), ML_Appointment_Repository::minute_of_day( $block['end_time'] ) );
		}

		// Daily clinic break.
		$break_start = (string) ML_Settings::get( 'appointments.break_start', '' );
		$break_end   = (string) ML_Settings::get( 'appointments.break_end', '' );
		if ( ML_Appointment_Repository::is_valid_time( $break_start ) && ML_Appointment_Repository::is_valid_time( $break_end ) && $break_start < $break_end ) {
			$busy[] = array( ML_Appointment_Repository::minute_of_day( $break_start ), ML_Appointment_Repository::minute_of_day( $break_end ) );
		}

		// Today: nothing in the past.
		if ( $date === current_time( 'Y-m-d' ) ) {
			$now = (int) strtotime( (string) current_time( 'mysql' ) ) - ( (int) strtotime( $date ) );
			$now_min = (int) floor( ( $now % DAY_IN_SECONDS ) / 60 );
			$busy[] = array( -INF, max( -INF, $now_min ) );
		}

		$busy = self::merge_ranges( $busy );
		$path = self::during( $open, $close, $busy );

		$slots = array();
		for ( $start = $open; ( $start + $duration ) <= $close; $start += $step ) {
			$end = $start + $duration;
			if ( self::overlaps( $start, $end, $path ) ) {
				continue;
			}
			$slots[] = array(
				'time'     => self::to_hm( $start ),
				'end'      => self::to_hm( $end ),
				'duration' => $duration,
			);
		}
		return $slots;
	}

	/**
	 * Busy minute intervals from existing appointments (buffered).
	 *
	 * @param string $date   Y-m-d.
	 * @param int    $doctor Confirmed doctor id (0 = all).
	 *
	 * @return array<int,array>
	 */
	private static function busy_minutes( $date, $doctor ) {
		global $wpdb;
		$table = ML_Database::table( 'appointments' );

		$blocks  = ML_Appointment_Repository::blocking_statuses();
		$holders = implode( ',', array_fill( 0, count( $blocks ), '%s' ) );
		$sql     = "SELECT start_time, end_time FROM {$table} WHERE appointment_date = %s AND status IN ({$holders})";
		$params  = array_merge( array( $date ), $blocks );

		if ( $doctor ) {
			$sql    .= ' AND doctor_user_id = %d';
			$params[] = $doctor;
		}

		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$buffer = self::buffer();
		$ranges = array();

		foreach ( (array) $rows as $row ) {
			$start = ML_Appointment_Repository::minute_of_day( $row['start_time'] );
			$end   = ! empty( $row['end_time'] ) ? ML_Appointment_Repository::minute_of_day( $row['end_time'] ) : $start;
			$ranges[] = array( max( 0, $start - $buffer ), $end + $buffer );
		}
		return $ranges;
	}

	/**
	 * Blocked-date rows touching a date.
	 *
	 * @param string $date Y-m-d.
	 *
	 * @return array
	 */
	private static function blocked_windows( $date ) {
		global $wpdb;
		$table = ML_Database::table( 'clinic_blocked_dates' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT blocked_date, all_day, start_time, end_time FROM {$table} WHERE blocked_date = %s", $date ),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * True when a date is fully blocked.
	 *
	 * @param string $date Y-m-d.
	 *
	 * @return bool
	 */
	public static function is_all_day_blocked( $date ) {
		global $wpdb;
		$table = ML_Database::table( 'clinic_blocked_dates' );
		$count = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE blocked_date = %s AND all_day = 1", $date )
		);
		return $count > 0;
	}

	/**
	 * Merge overlapping minute ranges.
	 *
	 * @param array $ranges List of [start, end].
	 *
	 * @return array
	 */
	private static function merge_ranges( array $ranges ) {
		if ( ! $ranges ) {
			return array();
		}
		usort( $ranges, static function ( $a, $b ) {
			return $a[0] <=> $b[0];
		} );
		$merged = array();
		foreach ( $ranges as $range ) {
			if ( ! $merged || $range[0] > end( $merged )[1] ) {
				$merged[] = $range;
			} else {
				$key = array_key_last( $merged );
				$merged[ $key ][1] = max( $merged[ $key ][1], $range[1] );
			}
		}
		return $merged;
	}

	/**
	 * Complement of the busy ranges inside [open, close].
	 *
	 * @param int   $open  Minute of day.
	 * @param int   $close Minute of day.
	 * @param array $busy  Merged busy ranges.
	 *
	 * @return array
	 */
	private static function during( $open, $close, array $busy ) {
		$free  = array();
		$cursor = $open;
		foreach ( $busy as $range ) {
			if ( $range[1] <= $cursor ) {
				continue;
			}
			if ( $range[0] >= $close ) {
				break;
			}
			if ( $range[0] > $cursor ) {
				$free[] = array( $cursor, $range[0] );
			}
			$cursor = max( $cursor, $range[1] );
		}
		if ( $cursor < $close ) {
			$free[] = array( $cursor, $close );
		}
		return $free;
	}

	/**
	 * Is [start, end) fully inside one of the free windows in $path?
	 * A slot that starts inside a window but runs past it is busy.
	 *
	 * @param int   $start Minute.
	 * @param int   $end   Minute.
	 * @param array $path  Free ranges from during().
	 *
	 * @return bool True when the slot overlaps busy time.
	 */
	private static function overlaps( $start, $end, array $path ) {
		foreach ( $path as $range ) {
			if ( $range[1] <= $start ) {
				continue;
			}
			if ( $range[0] > $start ) {
				return true; // Slot starts in a busy gap.
			}
			return $end > $range[1]; // Inside the window; free only if it fits.
		}
		return true; // After every free window => busy.
	}

	/**
	 * Minutes to HH:MM.
	 *
	 * @param int $mins Minute of day.
	 *
	 * @return string
	 */
	private static function to_hm( $mins ) {
		$mins = max( 0, (int) $mins );
		return sprintf( '%02d:%02d', intdiv( $mins, 60 ), $mins % 60 );
	}
}