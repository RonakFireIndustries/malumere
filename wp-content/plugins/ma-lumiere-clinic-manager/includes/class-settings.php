<?php
/**
 * Plugin settings registry backed by a single serialized option.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Settings {

	/**
	 * Option name that stores the settings array.
	 *
	 * @var string
	 */
	const OPTION = 'ml_clinic_settings';

	/**
	 * Capability required to alter settings.
	 *
	 * @var string
	 */
	const CAP = 'ml_manage_settings';

	/**
	 * Cached settings array.
	 *
	 * @var array|null
	 */
	private static $cache;

	/**
	 * Initialize settings hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Invalidate the cached settings array. Callers that write the option
	 * directly (e.g. signature upload handling) must call flush() before
	 * reading settings later in the same request.
	 *
	 * @return void
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Default settings (no invented credentials/data).
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'clinic'      => array(
				'clinic_name' => __( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ),
				'address'     => '',
				'phone'       => '',
				'email'       => '',
				'website'     => '',
				'logo_id'     => 0,
			),
			'doctor'      => array(
				'doctor_name'      => '',
				'designation'      => '',
				'registration_no'  => '',
				'signature_id'     => 0,
			),
			'appointments' => array(
				'appointment_duration' => 30,
				'opening_time'         => '09:00',
				'closing_time'         => '17:00',
				'working_days'         => array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ),
				'booking_buffer'       => 15,
				'days'                 => array(
					'mon' => array( 'enabled' => 1, 'open' => '09:00', 'close' => '17:00' ),
					'tue' => array( 'enabled' => 1, 'open' => '09:00', 'close' => '17:00' ),
					'wed' => array( 'enabled' => 1, 'open' => '09:00', 'close' => '17:00' ),
					'thu' => array( 'enabled' => 1, 'open' => '09:00', 'close' => '17:00' ),
					'fri' => array( 'enabled' => 1, 'open' => '09:00', 'close' => '17:00' ),
					'sat' => array( 'enabled' => 1, 'open' => '09:00', 'close' => '17:00' ),
					'sun' => array( 'enabled' => 0, 'open' => '09:00', 'close' => '14:00' ),
				),
				'max_advance_days'     => 30,
				'default_doctor'       => 0,
				'break_start'          => '',
				'break_end'            => '',
			),
			'email'       => array(
				'sender_name'        => '',
				'sender_email'       => '',
				'appointment_reminders' => 1,
				'followup_reminders'    => 1,
				'invoice_emails'        => 1,
			),
			'billing'     => array(
				'currency'      => 'INR',
				'tax_enabled'   => 0,
				'tax_percent'   => 0,
				'invoice_prefix' => 'ML-INV-',
				'razorpay'      => array(
					'mode'       => 'off',
					'key_id'     => '',
					'key_secret' => '',
				),
			),
			'privacy'     => array(
				'delete_on_uninstall' => 0,
			),
			'portal'      => array(
				'page_id' => 0,
			),
		);
	}

	/**
	 * All settings merged over defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$saved = get_option( self::OPTION, array() );
			self::$cache = array_replace_recursive( self::defaults(), is_array( $saved ) ? $saved : array() );
		}
		return self::$cache;
	}

	/**
	 * Get a dotted-path setting, e.g. 'clinic.clinic_name'.
	 *
	 * @param string $path  Dot-separated key path.
	 * @param mixed  $default Fallback value.
	 *
	 * @return mixed
	 */
	public static function get( $path, $default = null ) {
		$value = self::all();
		foreach ( explode( '.', $path ) as $key ) {
			if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
				return $default;
			}
			$value = $value[ $key ];
		}
		return $value;
	}

	/**
	 * Register settings with WordPress Settings API.
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			'ml_clinic_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Effective per-day working hours, keyed mon...sun. Falls back to the
	 * legacy flat opening_time/closing_time/working_days keys for stored
	 * settings saved before the per-day structure existed.
	 *
	 * @return array<string,array>
	 */
	public static function hours() {
		$appointments = self::get( 'appointments', array() );
		if ( is_array( $appointments ) && isset( $appointments['days'] ) && is_array( $appointments['days'] ) ) {
			return $appointments['days'];
		}

		$open   = (string) self::get( 'appointments.opening_time', '09:00' );
		$close  = (string) self::get( 'appointments.closing_time', '17:00' );
		$days   = (array) self::get( 'appointments.working_days', array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ) );
		$hours  = array();
		foreach ( array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ) as $dow ) {
			$hours[ $dow ] = array(
				'enabled' => in_array( $dow, $days, true ) ? 1 : 0,
				'open'    => in_array( $dow, $days, true ) ? $open : '09:00',
				'close'   => in_array( $dow, $days, true ) ? $close : '14:00',
			);
		}
		return $hours;
	}

	/**
	 * Sanitize submitted settings before storage.
	 *
	 * @param array $input Raw submitted value.
	 *
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		$out = array();

		$out['clinic']        = array(
			'clinic_name' => isset( $input['clinic']['clinic_name'] ) ? sanitize_text_field( $input['clinic']['clinic_name'] ) : $defaults['clinic']['clinic_name'],
			'address'     => isset( $input['clinic']['address'] ) ? sanitize_textarea_field( $input['clinic']['address'] ) : '',
			'phone'       => isset( $input['clinic']['phone'] ) ? sanitize_text_field( $input['clinic']['phone'] ) : '',
			'email'       => isset( $input['clinic']['email'] ) ? sanitize_email( $input['clinic']['email'] ) : '',
			'website'     => isset( $input['clinic']['website'] ) ? esc_url_raw( $input['clinic']['website'] ) : '',
			'logo_id'     => isset( $input['clinic']['logo_id'] ) ? absint( $input['clinic']['logo_id'] ) : 0,
		);

		$out['doctor']        = array(
			'doctor_name'     => isset( $input['doctor']['doctor_name'] ) ? sanitize_text_field( $input['doctor']['doctor_name'] ) : '',
			'designation'     => isset( $input['doctor']['designation'] ) ? sanitize_text_field( $input['doctor']['designation'] ) : '',
			'registration_no' => isset( $input['doctor']['registration_no'] ) ? sanitize_text_field( $input['doctor']['registration_no'] ) : '',
			'signature_id'    => isset( $input['doctor']['signature_id'] ) ? absint( $input['doctor']['signature_id'] ) : 0,
		);

		$out['appointments']  = self::sanitize_appointments( isset( $input['appointments'] ) ? $input['appointments'] : $defaults['appointments'] );

		$out['email']         = array(
			'sender_name'           => isset( $input['email']['sender_name'] ) ? sanitize_text_field( $input['email']['sender_name'] ) : '',
			'sender_email'          => isset( $input['email']['sender_email'] ) ? sanitize_email( $input['email']['sender_email'] ) : '',
			'appointment_reminders' => isset( $input['email']['appointment_reminders'] ) ? absint( $input['email']['appointment_reminders'] ) : 0,
			'followup_reminders'    => isset( $input['email']['followup_reminders'] ) ? absint( $input['email']['followup_reminders'] ) : 0,
			'invoice_emails'        => isset( $input['email']['invoice_emails'] ) ? absint( $input['email']['invoice_emails'] ) : 0,
		);

		$rz = isset( $input['billing']['razorpay'] ) && is_array( $input['billing']['razorpay'] ) ? $input['billing']['razorpay'] : array();
		$rz_mode = isset( $rz['mode'] ) ? sanitize_key( $rz['mode'] ) : 'off';
		if ( ! in_array( $rz_mode, array( 'off', 'manual', 'test', 'live' ), true ) ) {
			$rz_mode = 'off';
		}

		$out['billing']       = array(
			'currency'       => isset( $input['billing']['currency'] ) ? sanitize_text_field( $input['billing']['currency'] ) : 'INR',
			'tax_enabled'    => isset( $input['billing']['tax_enabled'] ) ? absint( $input['billing']['tax_enabled'] ) : 0,
			'tax_percent'    => isset( $input['billing']['tax_percent'] ) ? max( 0, min( 100, floatval( $input['billing']['tax_percent'] ) ) ) : 0,
			'invoice_prefix' => isset( $input['billing']['invoice_prefix'] ) ? sanitize_text_field( $input['billing']['invoice_prefix'] ) : 'ML-INV-',
			'razorpay'       => array(
				'mode'       => $rz_mode,
				'key_id'     => isset( $rz['key_id'] ) ? sanitize_text_field( $rz['key_id'] ) : '',
				'key_secret' => isset( $rz['key_secret'] ) ? sanitize_text_field( $rz['key_secret'] ) : '',
			),
		);

		$out['privacy']       = array(
			'delete_on_uninstall' => isset( $input['privacy']['delete_on_uninstall'] ) ? absint( $input['privacy']['delete_on_uninstall'] ) : 0,
		);

		$out['portal']        = array(
			'page_id' => isset( $input['portal']['page_id'] ) ? absint( $input['portal']['page_id'] ) : 0,
		);

		return $out;
	}

	/**
	 * Sanitize the appointments settings block, normalizing per-day hours,
	 * the booking horizon, the default doctor and the daily clinic break.
	 *
	 * @param mixed $raw Raw appointments block.
	 *
	 * @return array
	 */
	public static function sanitize_appointments( $raw ) {
		$raw   = is_array( $raw ) ? $raw : array();
		$hlp   = isset( $raw['opening_time'] ) ? self::clean_time( $raw['opening_time'], '09:00' ) : '09:00';
		$hlc   = isset( $raw['closing_time'] ) ? self::clean_time( $raw['closing_time'], '17:00' ) : '17:00';
		$days  = array();
		$wdays = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );

		$spec = ( isset( $raw['days'] ) && is_array( $raw['days'] ) ) ? $raw['days'] : array();
		foreach ( $wdays as $dow ) {
			$legacy_enabled = isset( $raw['working_days'] ) && is_array( $raw['working_days'] ) && in_array( $dow, $raw['working_days'], true );
			$legacy_open    = ( $dow !== 'sun' ) ? $hlp : '09:00';
			$legacy_close   = ( $dow !== 'sun' ) ? $hlc : '14:00';

			if ( isset( $spec[ $dow ] ) && is_array( $spec[ $dow ] ) ) {
				$enabled = ( isset( $spec[ $dow ]['enabled'] ) && $spec[ $dow ]['enabled'] ) ? 1 : 0;
				$open    = self::clean_time( isset( $spec[ $dow ]['open'] ) ? $spec[ $dow ]['open'] : $legacy_open, $legacy_open );
				$close   = self::clean_time( isset( $spec[ $dow ]['close'] ) ? $spec[ $dow ]['close'] : $legacy_close, $legacy_close );
			} else {
				$enabled = $legacy_enabled ? 1 : 0;
				$open    = $legacy_open;
				$close   = ( $legacy_enabled && $dow !== 'sun' ) ? $legacy_close : $legacy_close;
			}
			if ( $enabled && $open >= $close ) {
				$open  = '09:00';
				$close = '17:00';
				if ( $open >= $close ) {
					$close = '17:30';
				}
			}
			$days[ $dow ] = array(
				'enabled' => $enabled,
				'open'    => $open,
				'close'   => $close,
			);
		}

		$default_doctor = isset( $raw['default_doctor'] ) ? absint( $raw['default_doctor'] ) : 0;
		if ( $default_doctor && ! get_userdata( $default_doctor ) ) {
			$default_doctor = 0;
		}

		return array(
			'appointment_duration' => isset( $raw['appointment_duration'] ) ? max( 5, min( 240, absint( $raw['appointment_duration'] ) ) ) : 30,
			'opening_time'         => $hlp,
			'closing_time'         => $hlc,
			'working_days'         => array_values( array_filter( array_map( 'sanitize_key', ( isset( $raw['working_days'] ) && is_array( $raw['working_days'] ) ) ? $raw['working_days'] : array() ) ) ),
			'booking_buffer'       => isset( $raw['booking_buffer'] ) ? max( 0, min( 120, absint( $raw['booking_buffer'] ) ) ) : 15,
			'days'                 => $days,
			'max_advance_days'     => isset( $raw['max_advance_days'] ) ? max( 1, min( 365, absint( $raw['max_advance_days'] ) ) ) : 30,
			'default_doctor'       => $default_doctor,
			'break_start'          => self::clean_time( isset( $raw['break_start'] ) ? $raw['break_start'] : '', '' ),
			'break_end'            => self::clean_time( isset( $raw['break_end'] ) ? $raw['break_end'] : '', '' ),
		);
	}

	/**
	 * Validate a HH:MM clock string ('' or a real time on a 24h clock).
	 *
	 * @param mixed  $value   Candidate value.
	 * @param string $default Fallback ('' allowed).
	 *
	 * @return string
	 */
	private static function clean_time( $value, $default ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( '' === $value ) {
			return '' === $default ? '' : $default;
		}
		if ( preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $value, $m ) ) {
			return sprintf( '%02d:%02d', (int) $m[1], (int) $m[2] );
		}
		return '' === $default ? '' : $default;
	}

	/**
	 * Whether clinic data should be deleted on uninstall.
	 *
	 * @return bool
	 */
	public static function delete_on_uninstall() {
		return (bool) self::get( 'privacy.delete_on_uninstall', 0 );
	}
}