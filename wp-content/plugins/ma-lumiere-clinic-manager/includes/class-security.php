<?php
/**
 * Security helpers: nonces, authorization, sanitization, escaping and
 * request context. Covers the IDOR protection chain:
 *
 *     current user  ->  capability  ->  ownership  ->  record
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Security {

	/**
	 * Nonce action used across the clinic admin + AJAX surface.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'ml_clinic_nonce';

	/**
	 * Build a nonce for the clinic surface.
	 *
	 * @return string
	 */
	public static function nonce() {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	/**
	 * Verify a provided nonce. Returns bool; never dies.
	 *
	 * @param mixed $value Nonce value to check.
	 *
	 * @return bool
	 */
	public static function verify_nonce( $value = null ) {
		if ( null === $value ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$value = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		}
		return (bool) wp_verify_nonce( (string) $value, self::NONCE_ACTION );
	}

	/**
	 * Require a logged-in user.
	 *
	 * @return bool
	 */
	public static function require_login() {
		return is_user_logged_in();
	}

	/**
	 * Clear capability check. Never treats capability absence as success.
	 *
	 * @param string $cap Required capability.
	 *
	 * @return bool
	 */
	public static function can( $cap ) {
		return current_user_can( (string) $cap );
	}

	/**
	 * Full gate: logged-in + nonce + capability.
	 *
	 * @param string      $cap   Required capability.
	 * @param string|null $nonce Nonce value; null reads from _wpnonce.
	 * @param bool        $die   Whether to exit with a JSON error on failure.
	 *
	 * @return bool
	 */
	public static function gate( $cap, $nonce = null, $die = true ) {
		$ok = self::require_login() && self::verify_nonce( $nonce ) && self::can( $cap );

		if ( ! $ok && $die && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'ma-lumiere-clinic' ) ), 403 );
		}
		if ( ! $ok && $die && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'ma-lumiere-clinic' ), 403 );
		}
		return $ok;
	}

	/**
	 * Is the current user permitted to touch a given patient record?
	 * Foundation for IDOR protection.
	 *
	 * Order of checks:
	 *   1. capable of global patient/medical access
	 *   2. if not, and the user maps to a patient, allow only their own row.
	 *
	 * @param int $patient_id Patient ID being requested.
	 *
	 * @return bool
	 */
	public static function can_access_patient( $patient_id ) {
		$patient_id = absint( $patient_id );

		if ( self::can( 'ml_manage_clinic' ) || self::can( 'ml_view_medical_records' ) || self::can( 'ml_view_patients' ) ) {
			return true;
		}

		// Patient portal: only the user's OWN record (wp_user_id match).
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user_id = get_current_user_id();
		global $wpdb;
		$owned = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . ML_Database::table( 'patients' ) . ' WHERE id = %d AND wp_user_id = %d',
				$patient_id,
				$user_id
			)
		);
		return '1' === (string) $owned;
	}

	/**
	 * IP address of the current request.
	 *
	 * @return string
	 */
	public static function current_ip() {
		$addresses = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '0.0.0.0'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return (string) inet_pton( (string) $addresses ) ? $addresses : '0.0.0.0';
	}

	/**
	 * Short user agent string.
	 *
	 * @return string
	 */
	public static function current_user_agent() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}
		return substr( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 0, 255 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Signed, expiring token for private file serving (token-based media).
	 *
	 * Binds entity + id + expiry into an HMAC keyed with the site auth
	 * salt, so a token cannot be forged, reused for another file, or
	 * extended past its expiry.
	 *
	 * @param string $entity Entity namespace, e.g. 'photo'.
	 * @param int    $id     Record id being served.
	 * @param int    $ttl    Seconds until expiry (min 60).
	 *
	 * @return string "<expiry>.<hmac-sha256-hex>"
	 */
	public static function file_token( $entity, $id, $ttl = 600 ) {
		$exp = time() + max( 60, absint( $ttl ) );
		$sig = hash_hmac( 'sha256', $entity . '|' . absint( $id ) . '|' . $exp, wp_salt( 'auth' ) );
		return $exp . '.' . $sig;
	}

	/**
	 * Verify a token produced by file_token(). Constant-time compare,
	 * rejects expired or malformed values.
	 *
	 * @param string $entity Entity namespace, e.g. 'photo'.
	 * @param int    $id     Record id being requested.
	 * @param mixed  $token  Candidate token.
	 *
	 * @return bool
	 */
	public static function verify_file_token( $entity, $id, $token ) {
		$token = is_scalar( $token ) ? (string) $token : '';
		if ( ! preg_match( '/^(\d+)\.([a-f0-9]{64})$/', $token, $m ) ) {
			return false;
		}
		$exp = (int) $m[1];
		if ( $exp < time() ) {
			return false;
		}
		$expect = hash_hmac( 'sha256', $entity . '|' . absint( $id ) . '|' . $exp, wp_salt( 'auth' ) );
		return hash_equals( $expect, $m[2] );
	}
}