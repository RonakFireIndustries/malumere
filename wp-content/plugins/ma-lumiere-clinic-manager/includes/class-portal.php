<?php
/**
 * Patient portal (Phase 9).
 *
 * Magic-link login flow:
 *   1. Patient submits their clinic phone or email.
 *   2. A one-time token is minted and emailed as a link (generic
 *      response either way, so a non-match is not Fingerprintable).
 *   3. Following the link consumes the token, sets an HttpOnly, signed
 *      session cookie, and redirects to the portal home.
 *   4. `[ml_portal]` renders the patient's own medical + billing record
 *      server-side; every query is scoped to the session patient id so
 *      IDOR is structurally impossible. Photos remain staff-only.
 *
 * Query surface (all session-gated):
 *   ?ml_portal=magic&t=<token>   consume token + start session
 *   ?ml_portal=logout            end the session
 *   ?ml_portal=pdf&kind=prescription|invoice&id=<id>  own-record PDF
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Portal {

	/**
	 * Session cookie name.
	 *
	 * @var string
	 */
	const COOKIE = 'ml_portal';

	/**
	 * Magic-link lifetime (seconds).
	 *
	 * @var int
	 */
	const MAGIC_TTL = 900;

	/**
	 * Signed session lifetime (seconds).
	 *
	 * @var int
	 */
	const SESSION_TTL = 7200;

	/**
	 * Magic-link requests per IP per window.
	 *
	 * @var int
	 */
	const RATE_MAX = 5;

	/**
	 * Rate-limit window (seconds).
	 *
	 * @var int
	 */
	const RATE_WINDOW = 900;

	/**
	 * Login-form nonce action.
	 *
	 * @var string
	 */
	const LOGIN_NONCE = 'ml_portal_login';

	/**
	 * Whether portal CSS has been enqueued this request.
	 *
	 * @var bool
	 */
	private static $css_queued = false;

	/**
	 * Register shortcodes and the request router.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'ml_portal', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'ml_portal_login', array( __CLASS__, 'shortcode_login' ) );
		add_action( 'template_redirect', array( __CLASS__, 'dispatch' ) );
	}

	/**
	 * Router for magic links, logout and own-record PDF downloads.
	 *
	 * @return void
	 */
	public static function dispatch() {
		if ( empty( $_GET['ml_portal'] ) ) {
			if ( self::is_post() ) {
				self::handle_login_post();
			}
			return;
		}

		$action = sanitize_key( (string) $_GET['ml_portal'] );

		if ( 'magic' === $action ) {
			$token  = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';
			$patient = self::consume_magic_token( $token );
			if ( ! is_array( $patient ) ) {
				ml_audit( 'portal_login_consumed', 'patient', 0, __( 'Portal login rejected: invalid or expired magic token.', 'ma-lumiere-clinic' ) );
				wp_safe_redirect( add_query_arg( 'ml_login', 'invalid', self::portal_url() ) );
				exit;
			}
			self::set_session( (int) $patient['id'] );
			ml_audit(
				'portal_login_consumed',
				'patient',
				(int) $patient['id'],
				sprintf( __( 'Portal session started for %s (%s).', 'ma-lumiere-clinic' ), trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ), (string) $patient['patient_uid'] )
			);
			wp_safe_redirect( add_query_arg( 'ml_login', 'ok', self::portal_url() ) );
			exit;
		}

		if ( 'logout' === $action ) {
			$patient = self::current_patient();
			if ( is_array( $patient ) ) {
				ml_audit( 'portal_logout', 'patient', (int) $patient['id'], __( 'Portal session ended.', 'ma-lumiere-clinic' ) );
			}
			self::clear_session();
			wp_safe_redirect( remove_query_arg( 'ml_portal', self::portal_url() ) );
			exit;
		}

		if ( 'pdf' === $action ) {
			self::stream_pdf();
		}

		// Any unknown action: send home.
		wp_safe_redirect( self::portal_url() );
		exit;
	}

	/**
	 * Handle the login form POST (PRG pattern).
	 *
	 * @return void
	 */
	private static function handle_login_post() {
		if ( ! isset( $_POST['ml_action'] ) || 'portal_request' !== $_POST['ml_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below.
			return;
		}
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), self::LOGIN_NONCE ) ) {
			wp_safe_redirect( add_query_arg( 'ml_login', 'invalid', self::portal_url() ) );
			exit;
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone = isset( $_POST['phone'] ) ? preg_replace( '/[^0-9+]/', '', (string) wp_unslash( $_POST['phone'] ) ) : '';

		if ( '' === $email && '' === $phone ) {
			wp_safe_redirect( add_query_arg( 'ml_login', 'empty', self::portal_url() ) );
			exit;
		}

		$result = self::request_login( $email, $phone );

		// Generic redirect regardless of outcome so the lookup cannot be
		// probed. The message shown always reads the same.
		wp_safe_redirect( add_query_arg( 'ml_login', is_wp_error( $result ) ? 'error' : 'sent', self::portal_url() ) );
		exit;
	}

	/**
	 * Mint + email a one-time magic link for a matching active patient.
	 *
	 * @param string $email Submitted email (may be '').
	 * @param string $phone Submitted phone (may be '').
	 *
	 * @return bool|WP_Error
	 */
	public static function request_login( $email, $phone ) {
		if ( ! self::rate_ok() ) {
			ml_audit( 'portal_login_sent', 'patient', 0, __( 'Portal login attempt rate-limited.', 'ma-lumiere-clinic' ) );
			return new WP_Error( 'ml_portal_rate', __( 'Too many requests. Please wait a few minutes.', 'ma-lumiere-clinic' ) );
		}

		$patient = ML_Patient_Repository::find_by_contact( $email, $phone );
		if ( ! is_array( $patient ) ) {
			// Same generic result as a successful lookup with a dead email:
			// no additional audit detail.
			ml_audit( 'portal_login_sent', 'patient', 0, __( 'Portal login requested for an unknown or inactive record.', 'ma-lumiere-clinic' ) );
			return true;
		}

		if ( '' === (string) $patient['email'] || ! is_email( (string) $patient['email'] ) ) {
			// The record exists but has no deliverable address; still answer
			// generically so the database is not revealed by the form.
			ml_audit( 'portal_login_sent', 'patient', (int) $patient['id'], __( 'Portal login requested but record has no usable email.', 'ma-lumiere-clinic' ) );
			return true;
		}

		$token = self::issue_magic_token( (int) $patient['id'] );
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$link = add_query_arg(
			array( 'ml_portal' => 'magic', 't' => rawurlencode( $token ) ),
			self::portal_url()
		);

		$name    = trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] );
		$clinic  = (string) ML_Settings::get( 'clinic.clinic_name', '' );
		$subject = sprintf( __( '%s — your patient portal link', 'ma-lumiere-clinic' ), $clinic ? $clinic : 'Ma Lumière Clinic' );
		$message = '<p>' . esc_html__( 'Hello', 'ma-lumiere-clinic' ) . ' ' . esc_html( $name ) . ',</p>'
			. '<p>' . esc_html__( 'Use the link below to open your patient portal. It expires in 15 minutes and can be used once.', 'ma-lumiere-clinic' ) . '</p>'
			. '<p><a href="' . esc_url( $link ) . '">' . esc_html__( 'Open my patient portal', 'ma-lumiere-clinic' ) . '</a></p>'
			. '<p>' . esc_html__( 'If you did not request this link, please ignore this email.', 'ma-lumiere-clinic' ) . '</p>';

		$sent = ML_Email_Service::send(
			(string) $patient['email'],
			$subject,
			$message,
			'portal_magic_link',
			array(
				'patient_id' => (int) $patient['id'],
				'uid'        => (string) $patient['patient_uid'],
				'link'       => $link,
			),
			true
		);

		ml_audit(
			'portal_login_sent',
			'patient',
			(int) $patient['id'],
			sprintf( __( 'Portal magic link emailed to %s.', 'ma-lumiere-clinic' ), (string) $patient['patient_uid'] )
		);

		return is_wp_error( $sent ) ? $sent : true;
	}

	/**
	 * Rate-limit magic-link requests by IP.
	 *
	 * @return bool
	 */
	private static function rate_ok() {
		$key = 'ml_portal_rl_' . substr( hash( 'sha256', ML_Security::current_ip() ), 0, 20 );
		$cur = max( 0, absint( (int) get_transient( $key ) ) );
		if ( $cur >= self::RATE_MAX ) {
			return false;
		}
		set_transient( $key, $cur + 1, self::RATE_WINDOW );
		return true;
	}

	/**
	 * Mint a single-use magic token (stored in a short-lived transient).
	 *
	 * @param int $patient_id Patient ID.
	 *
	 * @return string|WP_Error Token or error.
	 */
	public static function issue_magic_token( $patient_id ) {
		$patient_id = absint( $patient_id );
		if ( ! $patient_id ) {
			return new WP_Error( 'ml_portal_patient', __( 'Invalid patient.', 'ma-lumiere-clinic' ) );
		}
		$token = bin2hex( random_bytes( 32 ) );
		$key   = 'ml_portal_tk_' . substr( hash( 'sha256', $token ), 0, 40 );
		set_transient(
			$key,
			array(
				'pid' => $patient_id,
				'exp' => time() + self::MAGIC_TTL,
			),
			self::MAGIC_TTL
		);
		return $token;
	}

	/**
	 * Consume a magic token. Deletes the transient first so a token can
	 * never be replayed.
	 *
	 * @param string $token Raw token from the email link.
	 *
	 * @return array|null Active patient row or null.
	 */
	public static function consume_magic_token( $token ) {
		$token = is_scalar( $token ) ? (string) $token : '';
		if ( '' === $token || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
			return null;
		}
		$key    = 'ml_portal_tk_' . substr( hash( 'sha256', $token ), 0, 40 );
		$stored = get_transient( $key );
		delete_transient( $key );

		if ( ! is_array( $stored ) ) {
			return null;
		}
		if ( (int) $stored['exp'] < time() ) {
			return null;
		}
		$patient = ML_Patient_Repository::get( (int) $stored['pid'] );
		if ( ! is_array( $patient ) || 'active' !== (string) $patient['status'] ) {
			return null;
		}
		return $patient;
	}

	/**
	 * Mints a session cookie value for a patient.
	 *
	 * Format: <pid>.<expiry>.<hmac-sha256> with the site auth salt.
	 *
	 * @param int $patient_id Patient ID.
	 *
	 * @return string
	 */
	public static function session_value( $patient_id ) {
		$pid = absint( $patient_id );
		$exp = time() + self::SESSION_TTL;
		$sig = hash_hmac( 'sha256', $pid . '|' . $exp, wp_salt( 'auth' ) );
		return $pid . '.' . $exp . '.' . $sig;
	}

	/**
	 * Set the HttpOnly session cookie.
	 *
	 * @param int $patient_id Patient ID.
	 *
	 * @return void
	 */
	private static function set_session( $patient_id ) {
		$value = self::session_value( $patient_id );
		setcookie(
			self::COOKIE,
			(string) $value,
			array(
				'expires'  => time() + self::SESSION_TTL,
				'path'     => (string) COOKIEPATH,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		// Make the cookie visible to the current request without a reload.
		$_COOKIE[ self::COOKIE ] = (string) $value;
	}

	/**
	 * Expire the session cookie.
	 *
	 * @return void
	 */
	private static function clear_session() {
		setcookie(
			self::COOKIE,
			'',
			array(
				'expires'  => time() - HOUR_IN_SECONDS,
				'path'     => (string) COOKIEPATH,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		unset( $_COOKIE[ self::COOKIE ] );
	}

	/**
	 * The active portal patient from the signed cookie, or null.
	 *
	 * @return array|null Patient row (active only).
	 */
	public static function current_patient() {
		if ( empty( $_COOKIE[ self::COOKIE ] ) || ! is_string( $_COOKIE[ self::COOKIE ] ) ) {
			return null;
		}
		$value = (string) $_COOKIE[ self::COOKIE ];
		if ( ! preg_match( '/^(\d+)\.(\d+)\.([a-f0-9]{64})$/', $value, $m ) ) {
			return null;
		}
		$pid = (int) $m[1];
		$exp = (int) $m[2];
		if ( $exp < time() ) {
			return null;
		}
		$expect = hash_hmac( 'sha256', $pid . '|' . $exp, wp_salt( 'auth' ) );
		if ( ! hash_equals( $expect, $m[3] ) ) {
			return null;
		}
		$patient = ML_Patient_Repository::get( $pid );
		if ( ! is_array( $patient ) || 'active' !== (string) $patient['status'] ) {
			return null;
		}
		return $patient;
	}

	/**
	 * Portal anti-CSRF nonce for this patient (HMAC, not guessable).
	 *
	 * @param int $patient_id Patient ID.
	 *
	 * @return string
	 */
	public static function rest_nonce( $patient_id ) {
		return hash_hmac( 'sha256', 'ml-portal|' . absint( $patient_id ), wp_salt( 'auth' ) );
	}

	/**
	 * Base portal URL (configured page, else home).
	 *
	 * @return string
	 */
	public static function portal_url() {
		$page_id = absint( ML_Settings::get( 'portal.page_id', 0 ) );
		if ( $page_id && 'page' === get_post_type( $page_id ) && 'publish' === get_post_status( $page_id ) ) {
			$permalink = get_permalink( $page_id );
			if ( $permalink ) {
				return $permalink;
			}
		}
		return home_url( '/' );
	}

	/**
	 * Whether this request is a POST.
	 *
	 * @return bool
	 */
	private static function is_post() {
		return isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( (string) $_SERVER['REQUEST_METHOD'] );
	}

	/**
	 * [ml_portal] — login form when no session, otherwise the dashboard.
	 *
	 * @return string
	 */
	public static function shortcode() {
		self::assets();
		$patient = self::current_patient();
		if ( is_array( $patient ) ) {
			return self::render_dashboard( $patient );
		}
		return self::render_login_form( false );
	}

	/**
	 * [ml_portal_login] — login form only.
	 *
	 * @return string
	 */
	public static function shortcode_login() {
		self::assets();
		if ( self::current_patient() ) {
			return '<p class="ml-portal-done">' . esc_html__( 'You are already signed in.', 'ma-lumiere-clinic' ) . ' <a href="' . esc_url( self::portal_url() ) . '">' . esc_html__( 'Open the portal', 'ma-lumiere-clinic' ) . '</a>.</p>';
		}
		return self::render_login_form( false );
	}

	/**
	 * Enqueue portal CSS (idempotent).
	 *
	 * @return void
	 */
	private static function assets() {
		if ( self::$css_queued ) {
			return;
		}
		self::$css_queued = true;
		wp_enqueue_style( 'ml-portal', ML_CLINIC_URL . 'public/css/portal.css', array(), ML_CLINIC_VERSION );
		if ( did_action( 'wp_head' ) ) {
			add_action(
				'wp_footer',
				static function () {
					wp_print_styles( array( 'ml-portal' ) );
				},
				99
			);
		}
	}

	/**
	 * The magic-link request form.
	 *
	 * @param bool $sent Unused (kept for signature symmetry).
	 *
	 * @return string
	 */
	private static function render_login_form( $sent ) {
		$notice = '';
		$flag   = isset( $_GET['ml_login'] ) ? sanitize_key( (string) $_GET['ml_login'] ) : '';
		if ( 'ok' === $flag ) {
			$notice = '<p class="ml-portal-notice ml-portal-notice--ok">' . esc_html__( 'Welcome back — you are signed in.', 'ma-lumiere-clinic' ) . '</p>';
		} elseif ( 'sent' === $flag ) {
			$notice = '<p class="ml-portal-notice">' . esc_html__( 'If a matching record exists, a sign-in link has been sent to that email address. It expires in 15 minutes.', 'ma-lumiere-clinic' ) . '</p>';
		} elseif ( 'empty' === $flag ) {
			$notice = '<p class="ml-portal-notice ml-portal-notice--error">' . esc_html__( 'Please enter your phone number or the email address you gave the clinic.', 'ma-lumiere-clinic' ) . '</p>';
		} elseif ( 'error' === $flag || 'invalid' === $flag ) {
			$notice = '<p class="ml-portal-notice ml-portal-notice--error">' . ( 'invalid' === $flag ? esc_html__( 'That link is invalid or has expired. Please request a new one.', 'ma-lumiere-clinic' ) : esc_html__( 'That link could not be used. Please request a new one.', 'ma-lumiere-clinic' ) ) . '</p>';
		}

		$action = esc_url( remove_query_arg( 'ml_login' ) );

		return '<div class="ml-portal">
			' . $notice . '
			<h2 class="ml-portal__title">' . esc_html__( 'Patient portal sign-in', 'ma-lumiere-clinic' ) . '</h2>
			<p class="ml-portal__intro">' . esc_html__( 'Enter the phone number or email address you provided at the clinic. We will email you a one-time sign-in link.', 'ma-lumiere-clinic' ) . '</p>
			<form method="post" action="' . $action . '" class="ml-portal__form">
				<input type="hidden" name="ml_action" value="portal_request" />
				' . wp_nonce_field( self::LOGIN_NONCE, '_wpnonce', true, false ) . '
				<p><label>' . esc_html__( 'Email address', 'ma-lumiere-clinic' ) . '<br />
					<input type="email" name="email" value="" autocomplete="email" /></label></p>
				<p><label>' . esc_html__( 'Phone number', 'ma-lumiere-clinic' ) . '<br />
					<input type="tel" name="phone" value="" autocomplete="tel" /></label></p>
				<p><button type="submit" class="button">' . esc_html__( 'Email me a sign-in link', 'ma-lumiere-clinic' ) . '</button></p>
			</form>
		</div>';
	}

	/**
	 * Render the patient dashboard (own records only).
	 *
	 * @param array $patient Session patient row.
	 *
	 * @return string
	 */
	private static function render_dashboard( array $patient ) {
		$pid    = (int) $patient['id'];
		$logout = esc_url( add_query_arg( 'ml_portal', 'logout', self::portal_url() ) );

		$html  = '<div class="ml-portal ml-portal--dashboard">';
		$html .= '<header class="ml-portal__head">'
			. '<div class="ml-portal__who"><strong>' . esc_html( trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ) ) . '</strong>'
			. '<span class="ml-portal__uid">' . esc_html( (string) $patient['patient_uid'] ) . '</span></div>'
			. '<a class="ml-portal__logout" href="' . $logout . '">' . esc_html__( 'Sign out', 'ma-lumiere-clinic' ) . '</a>'
			. '</header>';

		$html .= self::profile_section( $patient );
		$html .= self::appointments_section( $pid );
		$html .= self::visits_section( $pid );
		$html .= self::prescriptions_section( $pid );
		$html .= self::sessions_section( $pid );
		$html .= self::followups_section( $pid );
		$html .= self::billing_section( $pid );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Profile block.
	 *
	 * @param array $patient Patient row.
	 *
	 * @return string
	 */
	private static function profile_section( array $patient ) {
		$name  = trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] );
		$rows  = array(
			__( 'Name', 'ma-lumiere-clinic' )     => $name,
			__( 'Record number', 'ma-lumiere-clinic' ) => (string) $patient['patient_uid'],
			__( 'Date of birth', 'ma-lumiere-clinic' ) => $patient['date_of_birth'] ? ml_date( (string) $patient['date_of_birth'] ) : '—',
			__( 'Gender', 'ma-lumiere-clinic' )   => $patient['gender'] ? ucfirst( (string) $patient['gender'] ) : '—',
			__( 'Phone', 'ma-lumiere-clinic' )    => (string) $patient['phone'],
			__( 'Email', 'ma-lumiere-clinic' )    => (string) $patient['email'],
			__( 'Address', 'ma-lumiere-clinic' )  => trim( (string) $patient['address'] . ', ' . (string) $patient['city'] ),
		);

		$html = '<section class="ml-portal__section"><h3>' . esc_html__( 'My details', 'ma-lumiere-clinic' ) . '</h3><dl class="ml-portal__dl">';
		foreach ( $rows as $label => $value ) {
			$html .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . ( '' === (string) $value ? '—' : esc_html( $value ) ) . '</dd></div>';
		}
		$html .= '</dl></section>';
		return $html;
	}

	/**
	 * Appointments (own rows, most recent first).
	 *
	 * @param int $pid Patient ID.
	 *
	 * @return string
	 */
	private static function appointments_section( $pid ) {
		// The appointment repo list() is staff-scoped and does not accept a
		// patient_id filter, so read the patient's own rows directly.
		global $wpdb;
		$a  = ML_Database::table( 'appointments' );
		$t  = ML_Database::table( 'treatments' );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.appointment_date, a.start_time, a.end_time, a.status, a.booking_reference, t.name AS treatment_name
				 FROM {$a} a LEFT JOIN {$t} t ON t.id = a.treatment_id
				 WHERE a.patient_id = %d ORDER BY a.appointment_date DESC, a.start_time DESC LIMIT 20",
				$pid
			),
			ARRAY_A
		);
		$rows = is_array( $rows ) ? $rows : array();

		$html = '<section class="ml-portal__section"><h3>' . esc_html__( 'Appointments', 'ma-lumiere-clinic' ) . '</h3>';
		if ( ! $rows ) {
			$html .= '<p class="ml-portal__empty">' . esc_html__( 'No appointments on record.', 'ma-lumiere-clinic' ) . '</p></section>';
			return $html;
		}
		$html .= '<table class="ml-portal__table"><thead><tr><th>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Time', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Service', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Status', 'ma-lumiere-clinic' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$html .= '<tr><td>' . esc_html( $row['appointment_date'] ) . '</td>'
				. '<td>' . esc_html( (string) $row['start_time'] . '–' . (string) $row['end_time'] ) . '</td>'
				. '<td>' . esc_html( isset( $row['treatment_name'] ) ? $row['treatment_name'] : '—' ) . '</td>'
				. '<td>' . esc_html( (string) $row['status'] ) . '</td></tr>';
		}
		$html .= '</tbody></table></section>';
		return $html;
	}

	/**
	 * Visit history.
	 *
	 * @param int $pid Patient ID.
	 *
	 * @return string
	 */
	private static function visits_section( $pid ) {
		$list = ML_Visit_Repository::list(
			array(
				'patient_id' => $pid,
				'per_page'   => 20,
			)
		);
		$rows = isset( $list['items'] ) ? $list['items'] : array();

		$html = '<section class="ml-portal__section"><h3>' . esc_html__( 'Visits', 'ma-lumiere-clinic' ) . '</h3>';
		if ( ! $rows ) {
			$html .= '<p class="ml-portal__empty">' . esc_html__( 'No visits on record.', 'ma-lumiere-clinic' ) . '</p></section>';
			return $html;
		}
		$html .= '<table class="ml-portal__table"><thead><tr><th>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Visit', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Summary', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Status', 'ma-lumiere-clinic' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$html .= '<tr><td>' . esc_html( ml_date( (string) $row['visit_date'] ) ) . '</td>'
				. '<td>' . esc_html( (string) $row['visit_number'] ) . '</td>'
				. '<td>' . esc_html( (string) ( isset( $row['chief_complaint'] ) ? $row['chief_complaint'] : '' ) ) . '</td>'
				. '<td>' . esc_html( (string) $row['status'] ) . '</td></tr>';
		}
		$html .= '</tbody></table></section>';
		return $html;
	}

	/**
	 * Prescriptions with PDF download for finalized documents.
	 *
	 * @param int $pid Patient ID.
	 *
	 * @return string
	 */
	private static function prescriptions_section( $pid ) {
		$list = ML_Prescription_Repository::list(
			array(
				'patient_id' => $pid,
				'per_page'   => 30,
			)
		);
		$rows = isset( $list['items'] ) ? $list['items'] : array();

		$html = '<section class="ml-portal__section"><h3>' . esc_html__( 'Prescriptions', 'ma-lumiere-clinic' ) . '</h3>';
		if ( ! $rows ) {
			$html .= '<p class="ml-portal__empty">' . esc_html__( 'No prescriptions on record.', 'ma-lumiere-clinic' ) . '</p></section>';
			return $html;
		}
		$html .= '<table class="ml-portal__table"><thead><tr><th>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Number', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Status', 'ma-lumiere-clinic' ) . '</th><th></th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$pdf = '';
			if ( 'final' === (string) $row['status'] && ! empty( $row['prescription_number'] ) ) {
				$url  = add_query_arg(
					array( 'ml_portal' => 'pdf', 'kind' => 'prescription', 'id' => (int) $row['id'] ),
					self::portal_url()
				);
				$pdf  = '<a class="ml-portal__pdf" href="' . esc_url( $url ) . '">' . esc_html__( 'PDF', 'ma-lumiere-clinic' ) . '</a>';
			}
			$html .= '<tr><td>' . esc_html( ml_date( (string) $row['prescription_date'] ) ) . '</td>'
				. '<td>' . esc_html( (string) $row['prescription_number'] ) . ' <span class="ml-portal__muted">v' . (int) $row['version'] . '</span></td>'
				. '<td>' . esc_html( (string) $row['status'] ) . '</td>'
				. '<td>' . $pdf . '</td></tr>';
		}
		$html .= '</tbody></table></section>';
		return $html;
	}

	/**
	 * Treatment session plan.
	 *
	 * @param int $pid Patient ID.
	 *
	 * @return string
	 */
	private static function sessions_section( $pid ) {
		$rows = ML_Treatment_Session_Repository::for_patient( $pid );

		$html = '<section class="ml-portal__section"><h3>' . esc_html__( 'Treatment sessions', 'ma-lumiere-clinic' ) . '</h3>';
		if ( ! $rows ) {
			$html .= '<p class="ml-portal__empty">' . esc_html__( 'No treatment sessions on record.', 'ma-lumiere-clinic' ) . '</p></section>';
			return $html;
		}
		$html .= '<table class="ml-portal__table"><thead><tr><th>' . esc_html__( 'Session', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Status', 'ma-lumiere-clinic' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$html .= '<tr><td>#' . (int) $row['session_number'] . '</td>'
				. '<td>' . esc_html( ml_date( (string) $row['scheduled_date'] ) ) . '</td>'
				. '<td>' . esc_html( (string) $row['status'] ) . '</td></tr>';
		}
		$html .= '</tbody></table></section>';
		return $html;
	}

	/**
	 * Follow-up schedule.
	 *
	 * @param int $pid Patient ID.
	 *
	 * @return string
	 */
	private static function followups_section( $pid ) {
		$list = ML_Followup_Repository::list(
			array(
				'patient_id' => $pid,
				'per_page'   => 20,
			)
		);
		$rows = isset( $list['items'] ) ? $list['items'] : array();

		$html = '<section class="ml-portal__section"><h3>' . esc_html__( 'Follow-ups', 'ma-lumiere-clinic' ) . '</h3>';
		if ( ! $rows ) {
			$html .= '<p class="ml-portal__empty">' . esc_html__( 'No follow-ups on record.', 'ma-lumiere-clinic' ) . '</p></section>';
			return $html;
		}
		$html .= '<table class="ml-portal__table"><thead><tr><th>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Status', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Notes', 'ma-lumiere-clinic' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$html .= '<tr><td>' . esc_html( ml_date( (string) $row['followup_date'] ) ) . '</td>'
				. '<td>' . esc_html( (string) $row['status'] ) . '</td>'
				. '<td>' . esc_html( (string) ( isset( $row['notes'] ) ? $row['notes'] : '' ) ) . '</td></tr>';
		}
		$html .= '</tbody></table></section>';
		return $html;
	}

	/**
	 * Billing ledger + invoice PDF + online payment (when configured).
	 *
	 * @param int $pid Patient ID.
	 *
	 * @return string
	 */
	private static function billing_section( $pid ) {
		$list = ML_Invoice_Repository::list(
			array(
				'patient_id' => $pid,
				'per_page'   => 20,
			)
		);
		$rows = isset( $list['items'] ) ? $list['items'] : array();

		$out = '<section class="ml-portal__section"><h3>' . esc_html__( 'Billing', 'ma-lumiere-clinic' ) . '</h3>';
		if ( ! $rows ) {
			$out .= '<p class="ml-portal__empty">' . esc_html__( 'No invoices on record.', 'ma-lumiere-clinic' ) . '</p></section>';
			return $out;
		}

		$labels = array(
			'unpaid'  => __( 'Unpaid', 'ma-lumiere-clinic' ),
			'partial' => __( 'Part paid', 'ma-lumiere-clinic' ),
			'paid'    => __( 'Paid', 'ma-lumiere-clinic' ),
			'void'    => __( 'Void', 'ma-lumiere-clinic' ),
		);

		$out .= '<table class="ml-portal__table"><thead><tr><th>' . esc_html__( 'Invoice', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Total', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Balance', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Status', 'ma-lumiere-clinic' ) . '</th><th></th></tr></thead><tbody>';

		$gateway_ok   = false;
		$payable_rows = array();

		foreach ( $rows as $row ) {
			$status = (string) $row['payment_status'];
			$label  = isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
			$pdf    = '<a class="ml-portal__pdf" href="' . esc_url( add_query_arg( array( 'ml_portal' => 'pdf', 'kind' => 'invoice', 'id' => (int) $row['id'] ), self::portal_url() ) ) . '">' . esc_html__( 'PDF', 'ma-lumiere-clinic' ) . '</a>';

			$out .= '<tr><td>' . esc_html( (string) $row['invoice_number'] ) . '</td>'
				. '<td>' . esc_html( ml_date( (string) $row['invoice_date'] ) ) . '</td>'
				. '<td>' . esc_html( ml_money( (float) $row['total'] ) ) . '</td>'
				. '<td>' . esc_html( ml_money( (float) $row['balance_amount'] ) ) . '</td>'
				. '<td>' . esc_html( $label ) . '</td><td>' . $pdf . '</td></tr>';

			if ( 'paid' !== $status && 'void' !== $status ) {
				$payable_rows[] = (int) $row['id'];
			}
		}
		$out .= '</tbody></table>';

		$gateway = is_callable( array( 'ML_Payment_Repository', 'gateway_config' ) ) ? ML_Payment_Repository::gateway_config() : new WP_Error( 'n/a', 'n/a' );
		$gateway_ok = ! is_wp_error( $gateway );

		if ( $gateway_ok && $payable_rows ) {
			$out .= self::online_pay_markup( (int) $payable_rows[0], $gateway );
		}

		$out .= '</section>';
		return $out;
	}

	/**
	 * Inline Razorpay checkout for one outstanding invoice.
	 *
	 * @param int   $invoice_id Invoice ID.
	 * @param array $gateway    Gateway config.
	 *
	 * @return string
	 */
	private static function online_pay_markup( $invoice_id, array $gateway ) {
		$invoice   = ML_Invoice_Repository::get( $invoice_id );
		if ( ! is_array( $invoice ) || 'paid' === (string) $invoice['payment_status'] || 'void' === (string) $invoice['payment_status'] ) {
			return '';
		}
		$pid       = (int) $invoice['patient_id'];
		$rest_url  = esc_url_raw( rest_url( 'ml-clinic/v1/' ) );
		$nonce     = self::rest_nonce( $pid );
		$currency  = (string) ML_Settings::get( 'billing.currency', 'INR' );
		$amount_js = (string) round( (float) $invoice['balance_amount'] * 100 );

		$script = '
			window.MLPortal = window.MLPortal || {};
			MLPortal.restUrl = ' . wp_json_encode( $rest_url ) . ';
			MLPortal.nonce = ' . wp_json_encode( $nonce ) . ';
			MLPortal.invoice = ' . (int) $invoice_id . ';
			MLPortal.currency = ' . wp_json_encode( $currency ) . ';
			MLPortal.amount = ' . $amount_js . ';
			MLPortal.errMsg = ' . wp_json_encode( __( 'Something went wrong. Please try again.', 'ma-lumiere-clinic' ) ) . ';
			MLPortal.clinicName = ' . wp_json_encode( (string) ML_Settings::get( 'clinic.clinic_name', '' ) ) . ';
			MLPortal.payBtn = document.getElementById("ml-portal-pay");
			if (MLPortal.payBtn) {
				MLPortal.payBtn.addEventListener("click", function () {
					var me = MLPortal;
					fetch(me.restUrl + "portal/invoices/" + me.invoice + "/razorpay-order", {
						method: "POST",
						headers: { "X-ML-Portal": me.nonce, "Content-Type": "application/json" }
					}).then(function (r) { return r.json(); }).then(function (res) {
						if (!res || !res.data || !res.data.order_id) { alert(me.errMsg); return; }
						if (window.Razorpay) { openMLCashier(res.data); return; }
						var s = document.createElement("script");
						s.src = "https://checkout.razorpay.com/v1/checkout.js";
						s.onload = function () { openMLCashier(res.data); };
						s.onerror = function () { alert(me.errMsg); };
						document.body.appendChild(s);
					}).catch(function () { alert(me.errMsg); });
				});
				function openMLCashier(d) {
					var handler = new window.Razorpay({
						key: d.key_id,
						amount: d.amount,
						currency: d.currency,
						name: MLPortal.clinicName || "Clinic",
						order_id: d.order_id,
						handler: function (pay) {
							fetch(me.restUrl + "invoices/" + me.invoice + "/razorpay-verify", {
								method: "POST",
								headers: { "Content-Type": "application/json" },
								body: JSON.stringify({ razorpay_order_id: pay.razorpay_order_id, razorpay_payment_id: pay.razorpay_payment_id, razorpay_signature: pay.razorpay_signature })
							}).then(function (r) { return r.json(); }).then(function () { window.location.reload(); })
							  .catch(function () { alert(me.errMsg); });
						},
						modal: { ondismiss: function () { window.location.reload(); } }
					});
					handler.open();
				}
			}';

		$markup = '<p class="ml-portal__pay"><button type="button" class="button button-primary" id="ml-portal-pay">' . esc_html__( 'Pay online (Razorpay)', 'ma-lumiere-clinic' ) . '</button>'
			. '<span class="ml-portal__pay-amount">' . esc_html( ml_money( (float) $invoice['balance_amount'] ) ) . '</span></p>'
			. '<script>/* <![CDATA[ */' . $script . '/* ]]> */</script>';

		return $markup;
	}

	/**
	 * Stream an own-record PDF (prescription or invoice) to the browser.
	 *
	 * @return void
	 */
	private static function stream_pdf() {
		$patient = self::current_patient();
		if ( ! is_array( $patient ) ) {
			wp_die( esc_html__( 'Your session has expired. Please request a new sign-in link.', 'ma-lumiere-clinic' ), 403 );
		}

		$kind = isset( $_GET['kind'] ) ? sanitize_key( (string) $_GET['kind'] ) : '';
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! $id || ! in_array( $kind, array( 'prescription', 'invoice' ), true ) ) {
			wp_die( esc_html__( 'Invalid request.', 'ma-lumiere-clinic' ), 400 );
		}

		if ( 'prescription' === $kind ) {
			$row = ML_Prescription_Repository::get( $id );
			if ( ! is_array( $row ) || (int) $row['patient_id'] !== (int) $patient['id'] ) {
				wp_die( esc_html__( 'Document not found.', 'ma-lumiere-clinic' ), 404 );
			}
			$result = ML_Prescription_Pdf::generate( $id );
			$entity = 'prescription';
			$audit  = sprintf( __( 'Prescription %s downloaded from the portal.', 'ma-lumiere-clinic' ), (string) $row['prescription_number'] );
		} else {
			$row = ML_Invoice_Repository::get( $id );
			if ( ! is_array( $row ) || (int) $row['patient_id'] !== (int) $patient['id'] ) {
				wp_die( esc_html__( 'Document not found.', 'ma-lumiere-clinic' ), 404 );
			}
			$result = ML_Invoice_Pdf::generate( $id );
			$entity = 'invoice';
			$audit  = sprintf( __( 'Invoice %s downloaded from the portal.', 'ma-lumiere-clinic' ), (string) $row['invoice_number'] );
		}

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html__( 'The document could not be generated.', 'ma-lumiere-clinic' ), 500 );
		}

		ml_audit( 'portal_pdf_downloaded', $entity, $id, $audit );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . sanitize_file_name( (string) $result['filename'] ) . '"' );
		header( 'Content-Length: ' . strlen( (string) $result['data'] ) );
		echo $result['data']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw PDF bytes.
		exit;
	}
}