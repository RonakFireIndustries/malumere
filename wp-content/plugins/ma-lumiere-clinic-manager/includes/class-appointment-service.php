<?php
/**
 * Appointment service: orchestrates the public booking flow, admin
 * actions, rate limiting, abuse protection and transactional emails.
 *
 * The repository owns persistence and conflict prevention; this class
 * owns the "what may happen" rules: honeypot, idempotency, rate limits,
 * patient resolution, status workflows and notifications.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Appointment_Service {

	/**
	 * Honeypot field name (a real user never fills it).
	 *
	 * @var string
	 */
	const HONEYPOT = 'ml_website';

	/**
	 * Rate-limit transient prefix.
	 *
	 * @var string
	 */
	const RL_PREFIX = 'ml_rl_';

	/**
	 * Run the six-step public booking submission.
	 *
	 * @param array $params Posted payload.
	 *
	 * @return array|WP_Error Resolved appointment (decorated) or error.
	 */
	public static function book_public( array $params ) {
		if ( self::honeypot( $params ) ) {
			ml_audit( 'booking_failed', 'appointment', 0, __( 'Booking rejected: honeypot triggered', 'ma-lumiere-clinic' ) );
			return new WP_Error( 'ml_booking_spam', __( 'Your booking could not be submitted. Please contact the clinic directly.', 'ma-lumiere-clinic' ) );
		}

		// Rate limits (IP + email).
		$ip   = ML_Security::current_ip();
		$key  = ML_Appointment_Service::RL_PREFIX . 'book_ip_' . md5( (string) $ip );
		if ( self::limit_exceeded( $key, 5, 15 * MINUTE_IN_SECONDS, true ) ) {
			ml_audit( 'booking_failed', 'appointment', 0, __( 'Booking rejected: rate limit', 'ma-lumiere-clinic' ) );
			return new WP_Error( 'ml_booking_limited', __( 'Too many booking attempts. Please wait a few minutes.', 'ma-lumiere-clinic' ) );
		}

		$contact = sanitize_email( isset( $params['patient_email'] ) ? (string) $params['patient_email'] : '' );
		if ( is_email( $contact ) ) {
			$ekey = ML_Appointment_Service::RL_PREFIX . 'book_email_' . md5( strtolower( $contact ) );
			if ( self::limit_exceeded( $ekey, 3, HOUR_IN_SECONDS, true ) ) {
				ml_audit( 'booking_failed', 'appointment', 0, __( 'Booking rejected: email rate limit', 'ma-lumiere-clinic' ) );
				return new WP_Error( 'ml_booking_limited', __( 'Too many bookings for this email address. Please contact the clinic.', 'ma-lumiere-clinic' ) );
			}
		}

		// Idempotency — a repeated submit returns the original booking.
		$idem = isset( $params['idempotency_key'] ) ? sanitize_key( (string) $params['idempotency_key'] ) : '';
		if ( '' !== $idem ) {
			$existing = ML_Appointment_Repository::get_by_idempotency( $idem );
			if ( $existing ) {
				return $existing;
			}
		}

		// Field checks handled by the repository; resolve the patient first.
		$patient = self::resolve_patient( $params );
		if ( is_wp_error( $patient ) ) {
			return $patient;
		}

		$doctor = ML_Availability_Service::resolve_doctor( isset( $params['doctor_id'] ) ? absint( $params['doctor_id'] ) : 0 );

		$input = array(
			'patient_id'       => $patient['id'],
			'doctor_user_id'   => $doctor ? $doctor : '',
			'treatment_id'     => isset( $params['treatment_id'] ) ? absint( $params['treatment_id'] ) : 0,
			'appointment_date' => isset( $params['date'] ) ? sanitize_text_field( (string) $params['date'] ) : '',
			'start_time'       => isset( $params['time'] ) ? sanitize_text_field( (string) $params['time'] ) : '',
			'status'           => 'pending',
			'appointment_type' => isset( $params['appointment_type'] ) && in_array( $params['appointment_type'], ML_Appointment_Repository::types(), true ) ? $params['appointment_type'] : 'new_consultation',
			'source'           => 'website',
			'notes'            => isset( $params['notes'] ) ? sanitize_textarea_field( (string) $params['notes'] ) : '',
			'patient_message'  => isset( $params['patient_message'] ) ? sanitize_textarea_field( (string) $params['patient_message'] ) : '',
		);
		if ( '' !== $idem ) {
			$input['idempotency_key'] = $idem;
		}

		$id = ML_Appointment_Repository::create( $input );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$appointment = ML_Appointment_Repository::get( $id );
		self::notify( (int) $id, 'confirmation' );

		return $appointment;
	}

	/**
	 * Admin/back-office booking.
	 *
	 * @param array $params Raw values.
	 *
	 * @return int|WP_Error Appointment ID.
	 */
	public static function create_admin( array $params ) {
		$input              = is_array( $params ) ? $params : array();
		$input['source']    = in_array( $input['source'] ?? '', ML_Appointment_Repository::sources(), true ) ? $input['source'] : 'admin';
		$input['status']    = in_array( $input['status'] ?? '', ML_Appointment_Repository::statuses(), true ) ? $input['status'] : 'pending';
		$input['created_by'] = get_current_user_id();
		return ML_Appointment_Repository::create( $input );
	}

	/**
	 * Reschedule an existing appointment: the old row is closed as
	 * `rescheduled` (reason reschedule) and a successor row is created,
	 * linked via rescheduled_from.
	 *
	 * @param int    $id          Appointment ID.
	 * @param string $date        New Y-m-d.
	 * @param string $time        New HH:MM.
	 * @param string $reason      Optional context.
	 *
	 * @return array|WP_Error New appointment or error.
	 */
	public static function reschedule( $id, $date, $time, $reason = '' ) {
		$existing = ML_Appointment_Repository::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'ml_appointment_not_found', __( 'Appointment not found.', 'ma-lumiere-clinic' ) );
		}
		if ( in_array( $existing['status'], array( 'completed', 'cancelled', 'rescheduled', 'no_show' ), true ) ) {
			return new WP_Error( 'ml_appointment_terminal', __( 'This appointment cannot be rescheduled.', 'ma-lumiere-clinic' ) );
		}
		if ( ! ML_Appointment_Repository::is_valid_date( $date ) || ! ML_Appointment_Repository::is_valid_time( $time ) ) {
			return new WP_Error( 'ml_appointment_invalid', __( 'Please choose a valid new date and time.', 'ma-lumiere-clinic' ) );
		}
		if ( ML_Appointment_Repository::is_past( $date, $time ) ) {
			return new WP_Error( 'ml_appointment_past', __( 'The new time is in the past.', 'ma-lumiere-clinic' ) );
		}

		$doctor = absint( $existing['doctor_user_id'] );
		$end    = ML_Appointment_Repository::time_plus( $time, ML_Appointment_Repository::effective_duration( absint( $existing['treatment_id'] ) ) );

		if ( ML_Appointment_Repository::slot_is_taken( $date, $time, $end, $doctor, $id ) ) {
			return new WP_Error( 'ml_appointment_conflict', __( 'That time is no longer available. Please pick another slot.', 'ma-lumiere-clinic' ) );
		}

		$closed = ML_Appointment_Repository::transition( $id, 'rescheduled', 'reschedule' );
		if ( is_wp_error( $closed ) ) {
			return $closed;
		}

		$input = array(
			'patient_id'       => (int) $existing['patient_id'],
			'doctor_user_id'   => $existing['doctor_user_id'],
			'treatment_id'     => $existing['treatment_id'],
			'appointment_date' => $date,
			'start_time'       => $time,
			'status'           => ( 'confirmed' === $existing['status'] ) ? 'confirmed' : 'pending',
			'appointment_type' => $existing['appointment_type'],
			'source'           => 'admin' !== (string) $existing['source'] ? $existing['source'] : 'admin',
			'notes'            => (string) $existing['notes'],
			'patient_message'  => (string) $existing['patient_message'],
			'rescheduled_from' => (int) $existing['id'],
		);
		$new_id = ML_Appointment_Repository::create( $input );
		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		$new = ML_Appointment_Repository::get( $new_id );
		ml_audit( 'appointment_rescheduled', 'appointment', (int) $new_id, sprintf( __( 'Appointment %s rescheduled from %s to %s', 'ma-lumiere-clinic' ), $new['booking_reference'], $existing['booking_reference'], $date ) );

		self::notify( (int) $new_id, 'reschedule', (string) $existing['booking_reference'] );

		return $new;
	}

	/**
	 * Cancel an appointment (kept as a closed record, never deleted).
	 *
	 * @param int    $id     Appointment ID.
	 * @param string $reason Cancellation reason key.
	 *
	 * @return array|WP_Error
	 */
	public static function cancel( $id, $reason = 'patient_request' ) {
		$updated = ML_Appointment_Repository::transition( $id, 'cancelled', $reason );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		self::notify( (int) $id, 'cancellation' );
		return $updated;
	}

	/**
	 * Staff quick actions delegating to repository transitions.
	 *
	 * @param int    $id     Appointment ID.
	 * @param string $action check_in|complete|no_show.
	 *
	 * @return array|WP_Error
	 */
	public static function quick_action( $id, $action ) {
		$map = array(
			'check_in' => 'checked_in',
			'complete' => 'completed',
			'no_show'  => 'no_show',
		);
		if ( ! isset( $map[ $action ] ) ) {
			return new WP_Error( 'ml_appointment_invalid', __( 'Unknown appointment action.', 'ma-lumiere-clinic' ) );
		}
		return ML_Appointment_Repository::transition( $id, $map[ $action ] );
	}

	/**
	 * Enrich an appointment row with patient/treatment/clinic context for
	 * emails and confirmation screens (public-safe, no clinical fields).
	 *
	 * @param array $appointment Decorated row.
	 *
	 * @return array
	 */
	public static function summary( array $appointment ) {
		$patient_id = absint( $appointment['patient_id'] );
		$patient    = $patient_id ? ML_Patient_Repository::get( $patient_id ) : null;

		$treatment = null;
		$treatment_id = absint( $appointment['treatment_id'] );
		if ( $treatment_id ) {
			$treatment = ML_Treatment_Repository::get( $treatment_id );
		}

		$doctor = null;
		if ( ! empty( $appointment['doctor_user_id'] ) ) {
			$doc = get_userdata( (int) $appointment['doctor_user_id'] );
			if ( $doc instanceof WP_User ) {
				$doctor = array(
					'id'    => $doc->ID,
					'name'  => trim( $doc->display_name ),
					'email' => $doc->user_email,
				);
			}
		}

		return array(
			'summary' => array(
				'clinic_name' => (string) ML_Settings::get( 'clinic.clinic_name', __( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ) ),
				'clinic_phone' => (string) ML_Settings::get( 'clinic.phone', '' ),
				'reference'   => (string) $appointment['booking_reference'],
				'date'        => (string) $appointment['appointment_date'],
				'time'        => (string) $appointment['start_time'],
				'end'         => (string) ( isset( $appointment['end_time'] ) ? $appointment['end_time'] : '' ),
				'duration'    => ML_Appointment_Repository::minute_of_day( $appointment['end_time'] ) - ML_Appointment_Repository::minute_of_day( $appointment['start_time'] ),
				'status'      => (string) $appointment['status'],
				'treatment'   => $treatment ? $treatment['name'] : '',
				'patient'     => $patient ? trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ) : '',
				'patient_uid' => $patient ? (string) $patient['patient_uid'] : '',
				'patient_email' => $patient ? (string) $patient['email'] : '',
				'doctor'      => $doctor ? $doctor['name'] : '',
			),
		);
	}

	/**
	 * Send the relevant transactional email (gated by ml_clinic_send_email).
	 * A failure never fails the booking — it is logged and flagged.
	 *
	 * @param int    $id          Appointment ID.
	 * @param string $event       confirmation|reschedule|cancellation.
	 * @param string $from_reference Prior reference (reschedule).
	 *
	 * @return void
	 */
	private static function notify( $id, $event, $from_reference = '' ) {
		$appointment = ML_Appointment_Repository::get( $id );
		if ( ! $appointment ) {
			return;
		}
		$data  = self::summary( $appointment );
		$email = (string) $data['summary']['patient_email'];
		if ( ! is_email( $email ) ) {
			return;
		}

		$subject = '';
		$template = 'appointment_confirmation';
		switch ( $event ) {
			case 'cancellation':
				$template = 'appointment_cancellation';
				$subject  = sprintf( __( '%s — Appointment cancelled', 'ma-lumiere-clinic' ), $data['summary']['clinic_name'] );
				break;
			case 'reschedule':
				$subject = sprintf( __( '%s — Appointment rescheduled', 'ma-lumiere-clinic' ), $data['summary']['clinic_name'] );
				break;
			case 'confirmation':
			default:
				$subject = sprintf( __( '%s — Booking confirmation %s', 'ma-lumiere-clinic' ), $data['summary']['clinic_name'], $data['summary']['reference'] );
				break;
		}

		$message = self::email_body( $event, $data['summary'], $from_reference );
		$result  = ML_Email_Service::send( $email, $subject, $message, $template, $data );

		if ( true === $result ) {
			if ( 'cancellation' === $event ) {
				return;
			}
			$wpdb       = $GLOBALS['wpdb'];
			$wpdb->update( ML_Database::table( 'appointments' ), array( 'confirmation_sent' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
		} elseif ( is_wp_error( $result ) ) {
			// Logged only; the record itself stays valid.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log
			error_log( 'ML Clinic: ' . $event . ' email not sent for appointment ' . absint( $id ) . ': ' . $result->get_error_message() );
		}
	}

	/**
	 * Build the (simple, safe) HTML email body.
	 *
	 * @param string $event      Event key.
	 * @param array  $s          Summary payload.
	 * @param string $from_reference Previous reference when rescheduling.
	 *
	 * @return string
	 */
	private static function email_body( $event, array $s, $from_reference = '' ) {
		$date = idate( 'd', strtotime( $s['date'] ) ) . ' ' . gmdate( 'M Y', strtotime( $s['date'] ) );

		$lines   = array();
		$lines[] = '<p>' . esc_html__( 'Dear patient,', 'ma-lumiere-clinic' ) . '</p>';
		switch ( $event ) {
			case 'cancellation':
				$lines[] = esc_html__( 'Your appointment has been cancelled.', 'ma-lumiere-clinic' );
				break;
			case 'reschedule':
				$lines[] = esc_html__( 'Your appointment has been rescheduled. Your new details:', 'ma-lumiere-clinic' );
				if ( $from_reference ) {
					$lines[] = sprintf( esc_html__( 'Previous reference: %s', 'ma-lumiere-clinic' ), esc_html( $from_reference ) );
				}
				break;
			default:
				$lines[] = esc_html__( 'Thank you for booking your appointment with', 'ma-lumiere-clinic' ) . ' <strong>' . esc_html( $s['clinic_name'] ) . '</strong>.';
				break;
		}

		$lines[] = '<table cellpadding="6" style="border-collapse:collapse;margin-top:10px">';
		$lines[] = '<tr><td><strong>' . esc_html__( 'Reference', 'ma-lumiere-clinic' ) . '</strong></td><td>' . esc_html( $s['reference'] ) . '</td></tr>';
		if ( $s['treatment'] ) {
			$lines[] = '<tr><td><strong>' . esc_html__( 'Treatment', 'ma-lumiere-clinic' ) . '</strong></td><td>' . esc_html( $s['treatment'] ) . '</td></tr>';
		}
		$lines[] = '<tr><td><strong>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . '</strong></td><td>' . esc_html( $date ) . '</td></tr>';
		$lines[] = '<tr><td><strong>' . esc_html__( 'Time', 'ma-lumiere-clinic' ) . '</strong></td><td>' . esc_html( $s['time'] . ' – ' . $s['end'] ) . '</td></tr>';
		if ( $s['doctor'] ) {
			$lines[] = '<tr><td><strong>' . esc_html__( 'With', 'ma-lumiere-clinic' ) . '</strong></td><td>' . esc_html( $s['doctor'] ) . '</td></tr>';
		}
		$lines[] = '</table>';
		if ( $s['clinic_phone'] ) {
			$lines[] = '<p>' . sprintf( esc_html__( 'Questions? Call %s', 'ma-lumiere-clinic' ), esc_html( $s['clinic_phone'] ) ) . '</p>';
		}
		return implode( "\n", $lines );
	}

	/**
	 * Honeypot: the hidden field must be empty.
	 *
	 * @param array $params Payload.
	 *
	 * @return bool
	 */
	private static function honeypot( array $params ) {
		return ! empty( $params[ self::HONEYPOT ] );
	}

	/**
	 * Increment a rate-limit counter; returns true when the limit was hit
	 * (unless $consume is false, which is a pure check).
	 *
	 * @param string $key    Composed key.
	 * @param int    $max    Allowed actions in the window.
	 * @param int    $window Window in seconds.
	 * @param bool   $consume Whether to count this request.
	 *
	 * @return bool
	 */
	private static function limit_exceeded( $key, $max, $window, $consume ) {
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return true;
		}
		if ( $consume ) {
			set_transient( $key, $count + 1, $window );
		}
		return false;
	}

	/**
	 * Public-safe flight check for the availability endpoint.
	 *
	 * @return bool Whether the caller exceeded the slot-polling budget.
	 */
	private static function availability_limited() {
		$key = ML_Appointment_Service::RL_PREFIX . 'slots_ip_' . md5( (string) ML_Security::current_ip() );
		return self::limit_exceeded( $key, 60, 15 * MINUTE_IN_SECONDS, true );
	}

	/**
	 * Poll guard used by the REST availability handler.
	 *
	 * @return bool
	 */
	public static function availability_rate_limited() {
		return self::availability_limited();
	}

	/**
	 * Configured default doctor (WP user ID), 0 = unset.
	 *
	 * @return int
	 */
	public static function default_doctor() {
		return absint( ML_Settings::get( 'appointments.default_doctor', 0 ) );
	}

	/**
	 * Doctor options for dropdowns (users in the ml_doctor role plus
	 * administrators, deduplicated). Keyed by user ID.
	 *
	 * @return array<int,array{id:int,name:string,email:string}>
	 */
	public static function doctor_options() {
		$users  = array();
		$append = static function ( $user ) use ( &$users ) {
			if ( ! isset( $users[ $user->ID ] ) ) {
				$users[ $user->ID ] = array(
					'id'    => (int) $user->ID,
					'name'  => trim( (string) $user->display_name ),
					'email' => (string) $user->user_email,
				);
			}
		};

		foreach ( get_users( array( 'role' => 'ml_doctor', 'number' => 200, 'fields' => array( 'ID', 'display_name', 'user_email' ) ) ) as $user ) {
			$append( $user );
		}
		foreach ( get_users( array( 'role' => 'administrator', 'number' => 50, 'fields' => array( 'ID', 'display_name', 'user_email' ) ) ) as $user ) {
			$append( $user );
		}

		ksort( $users );
		return array_values( $users );
	}

	/**
	 * Find an existing patient by contact or create a minimal clinic record.
	 * Only the minimum fields are accepted; nothing is printed back.
	 *
	 * @param array $params Booking payload.
	 *
	 * @return array|WP_Error Patient row (id, uid, first/last name) or error.
	 */
	private static function resolve_patient( array $params ) {
		$first = isset( $params['first_name'] ) ? sanitize_text_field( (string) $params['first_name'] ) : '';
		$last  = isset( $params['last_name'] ) ? sanitize_text_field( (string) $params['last_name'] ) : '';
		$email = isset( $params['patient_email'] ) ? sanitize_email( (string) $params['patient_email'] ) : '';
		$phone = isset( $params['phone'] ) ? preg_replace( '/[^0-9+()\-.\s]/', '', sanitize_text_field( (string) $params['phone'] ) ) : '';

		$matched = ML_Patient_Repository::find_by_contact( $email, $phone );
		if ( $matched ) {
			return $matched;
		}

		$birth = isset( $params['date_of_birth'] ) ? sanitize_text_field( (string) $params['date_of_birth'] ) : '';
		$gender = isset( $params['gender'] ) ? sanitize_key( (string) $params['gender'] ) : '';

		$result = ML_Patient_Repository::create(
			array(
				'first_name'      => $first,
				'last_name'       => $last,
				'email'           => $email,
				'phone'           => $phone,
				'date_of_birth'   => $birth,
				'gender'          => $gender,
				'status'          => 'active',
				'registration_date' => current_time( 'Y-m-d' ),
			)
		);
		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$errors     = ( is_array( $error_data ) && isset( $error_data['errors'] ) ) ? $error_data['errors'] : array();
			return new WP_Error(
				'ml_booking_patient',
				__( 'Please check the patient details.', 'ma-lumiere-clinic' ),
				array( 'errors' => $errors )
			);
		}

		return ML_Patient_Repository::get( (int) $result );
	}
}