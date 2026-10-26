<?php
/**
 * Minimal .env loader for server-side configuration.
 *
 * WordPress has no native .env support, so this reads the project-root .env
 * once per request. Real environment variables always win, which keeps
 * hosting-panel/container configuration working without editing files.
 *
 * Secrets loaded here are only ever read by server-side code. Nothing in this
 * class is exposed to the REST API or printed in a template.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Env {

	/**
	 * Whether the files have been parsed for this request.
	 *
	 * @var bool
	 */
	private static $loaded = false;

	/**
	 * Values parsed from .env files.
	 *
	 * @var array<string,string>
	 */
	private static $values = array();

	/**
	 * Parse the supported .env locations once.
	 *
	 * @return void
	 */
	public static function load() {
		if ( self::$loaded ) {
			return;
		}
		self::$loaded = true;

		$paths = array(
			ABSPATH . '.env',
			ML_CLINIC_PATH . '.env',
		);

		foreach ( array_unique( $paths ) as $path ) {
			if ( is_readable( $path ) ) {
				self::parse_file( $path );
			}
		}
	}

	/**
	 * Read one key. Precedence: real environment, then .env.
	 *
	 * @param string $key     Variable name.
	 * @param string $default Fallback value.
	 *
	 * @return string
	 */
	public static function get( $key, $default = '' ) {
		self::load();

		$value = getenv( $key );
		if ( false !== $value ) {
			return (string) $value;
		}
		if ( isset( $_ENV[ $key ] ) && is_scalar( $_ENV[ $key ] ) ) {
			return (string) $_ENV[ $key ];
		}
		if ( isset( $_SERVER[ $key ] ) && is_scalar( $_SERVER[ $key ] ) ) {
			return (string) $_SERVER[ $key ];
		}
		if ( array_key_exists( $key, self::$values ) ) {
			return (string) self::$values[ $key ];
		}

		return (string) $default;
	}

	/**
	 * Resolved Razorpay configuration from the environment.
	 *
	 * Mode is explicit when RAZORPAY_MODE is test/live, otherwise inferred
	 * from the public key prefix. `configured` requires both credentials.
	 *
	 * @return array{key_id:string,key_secret:string,mode:string,configured:bool}
	 */
	public static function razorpay() {
		$key_id     = trim( self::get( 'RAZORPAY_KEY_ID' ) );
		$key_secret = trim( self::get( 'RAZORPAY_KEY_SECRET' ) );
		$mode       = strtolower( trim( self::get( 'RAZORPAY_MODE' ) ) );

		if ( ! in_array( $mode, array( 'test', 'live', 'off' ), true ) ) {
			if ( 0 === strpos( $key_id, 'rzp_live_' ) ) {
				$mode = 'live';
			} elseif ( 0 === strpos( $key_id, 'rzp_test_' ) ) {
				$mode = 'test';
			} else {
				$mode = '';
			}
		}

		return array(
			'key_id'     => $key_id,
			'key_secret' => $key_secret,
			'mode'       => $mode,
			'configured' => '' !== $key_id && '' !== $key_secret,
		);
	}

	/**
	 * Parse a single .env file without executing it.
	 *
	 * Supports KEY=VALUE, export KEY=VALUE, # comments, and single/double
	 * quoted values. Malformed lines are ignored.
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return void
	 */
	private static function parse_file( $path ) {
		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( ! is_array( $lines ) ) {
			return;
		}

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}
			if ( 0 === stripos( $line, 'export ' ) ) {
				$line = trim( substr( $line, 7 ) );
			}

			$separator = strpos( $line, '=' );
			if ( false === $separator ) {
				continue;
			}

			$key   = trim( substr( $line, 0, $separator ) );
			$value = trim( substr( $line, $separator + 1 ) );

			if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $key ) ) {
				continue;
			}

			if ( strlen( $value ) >= 2 ) {
				$first = $value[0];
				$last  = substr( $value, -1 );
				if ( ( '"' === $first && '"' === $last ) || ( "'" === $first && "'" === $last ) ) {
					$value = substr( $value, 1, -1 );
				} else {
					$comment = strpos( $value, ' #' );
					if ( false !== $comment ) {
						$value = rtrim( substr( $value, 0, $comment ) );
					}
				}
			}

			if ( ! array_key_exists( $key, self::$values ) ) {
				self::$values[ $key ] = $value;
			}
		}
	}
}
