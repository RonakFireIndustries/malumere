<?php
/**
 * Public six-step booking widget ([ml_booking]).
 *
 * Renders the multi-step treatment → date → time → details → review →
 * confirmation flow used on the public appointment page. Catalog data comes
 * from the real ml_treatment CPT sync (never fabricated); imagery is the
 * treatment's featured image or a decorative stock photo (Unsplash hotlink)
 * per project decision. The widget talks to the public REST endpoints and
 * carries the ml_public_booking nonce so submission is verifiable.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Booking_Shortcode {

	/**
	 * Whether the widget was rendered on this request.
	 *
	 * @var bool
	 */
	private static $rendered = false;

	/**
	 * Register the shortcode and front-end asset hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'ml_booking', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'public_assets' ) );
	}

	/**
	 * Enqueue booking assets on the dedicated appointment template so the
	 * head gets them early; render() covers any other embed context.
	 *
	 * @return void
	 */
	public static function public_assets() {
		if ( ! is_page_template( 'page-appointment.php' ) ) {
			return;
		}
		self::enqueue_assets();
	}

	/**
	 * Enqueue stylesheet and script (idempotent).
	 *
	 * @return void
	 */
	private static function enqueue_assets() {
		wp_enqueue_style( 'ml-booking', ML_CLINIC_URL . 'public/css/booking.css', array(), ML_CLINIC_VERSION );
		wp_enqueue_script( 'ml-booking', ML_CLINIC_URL . 'public/js/booking.js', array(), ML_CLINIC_VERSION, true );
	}

	/**
	 * Shortcode renderer.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Widget markup or a graceful fallback.
	 */
	public static function render( $atts = array() ) {
		$atts     = shortcode_atts( array( 'treatment' => 0 ), $atts, 'ml_booking' );
		$treatments = ML_Treatment_Repository::list_active();

		if ( empty( $treatments ) ) {
			return '<p class="ml-booking__empty">' . esc_html__( 'Online booking is not available right now. Please contact the clinic directly.', 'ma-lumiere-clinic' ) . '</p>';
		}

		self::$rendered  = true;
		self::enqueue_assets();

		$preselect = isset( $_GET['treatment'] ) ? absint( $_GET['treatment'] ) : absint( $atts['treatment'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preselect.

		$items = array();
		foreach ( $treatments as $t ) {
			$items[] = array(
				'id'         => (int) $t['id'],
				'name'       => self::display( (string) $t['name'] ),
				'category'   => self::display( (string) $t['category'] ),
				'duration'   => (int) $t['duration'],
				'short_desc' => self::display( (string) $t['short_desc'] ),
				'image'      => self::treatment_image( $t ),
			);
		}

		$config = array(
			'restUrl'             => esc_url_raw( rest_url( 'ml-clinic/v1/' ) ),
			'bookingNonce'        => wp_create_nonce( 'ml_public_booking' ),
			'restNonce'           => wp_create_nonce( 'wp_rest' ),
			'treatments'          => $items,
			'preselectTreatment'  => $preselect,
			'doctorId'            => 0,
			'gmtOffsetSeconds'    => (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ),
			'now'                 => current_time( 'mysql' ),
			'strings'             => array(
				'stepIntro'   => __( 'Choose how you wish to book.', 'ma-lumiere-clinic' ),
				'treatment'   => __( 'Treatment', 'ma-lumiere-clinic' ),
				'date'        => __( 'Date', 'ma-lumiere-clinic' ),
				'time'        => __( 'Time', 'ma-lumiere-clinic' ),
				'details'     => __( 'Your details', 'ma-lumiere-clinic' ),
				'review'      => __( 'Review & confirm', 'ma-lumiere-clinic' ),
				'confirmed'   => __( 'Booking confirmed', 'ma-lumiere-clinic' ),
				'next'        => __( 'Continue', 'ma-lumiere-clinic' ),
				'back'        => __( 'Back', 'ma-lumiere-clinic' ),
				'change'      => __( 'Change', 'ma-lumiere-clinic' ),
				'select'      => __( 'Select', 'ma-lumiere-clinic' ),
				'confirmBtn'  => __( 'Confirm my booking', 'ma-lumiere-clinic' ),
				'minutes'     => __( 'min', 'ma-lumiere-clinic' ),
				'noDates'     => __( 'No appointment slots are available in the coming days. Please contact the clinic.', 'ma-lumiere-clinic' ),
				'noSlots'     => __( 'No free slots on this day. Please pick another date.', 'ma-lumiere-clinic' ),
				'loading'     => __( 'Loading…', 'ma-lumiere-clinic' ),
				'error'       => __( 'Something went wrong. Please try again.', 'ma-lumiere-clinic' ),
				'errorLimit'  => __( 'Too many requests. Please wait a moment and try again.', 'ma-lumiere-clinic' ),
				'errorSession'=> __( 'Your session could not be verified. Please refresh the page and try again.', 'ma-lumiere-clinic' ),
				'errorPast'   => __( 'That time is already in the past. Please choose another slot.', 'ma-lumiere-clinic' ),
				'errorConflict'=> __( 'That slot is no longer available. Please choose another.', 'ma-lumiere-clinic' ),
				'required'    => __( 'This field is required.', 'ma-lumiere-clinic' ),
				'emailInvalid'=> __( 'Please enter a valid email address.', 'ma-lumiere-clinic' ),
				'consent'     => __( 'I agree to be contacted about my appointment.', 'ma-lumiere-clinic' ),
				'consentRequired' => __( 'Please confirm you agree to be contacted.', 'ma-lumiere-clinic' ),
				'addCalendar' => __( 'Add to calendar', 'ma-lumiere-clinic' ),
				'googleCal'   => __( 'Google Calendar', 'ma-lumiere-clinic' ),
				'ics'         => __( 'Download .ics', 'ma-lumiere-clinic' ),
				'bookAnother' => __( 'Book another appointment', 'ma-lumiere-clinic' ),
				'firstName'   => __( 'First name', 'ma-lumiere-clinic' ),
				'lastName'    => __( 'Last name', 'ma-lumiere-clinic' ),
				'email'       => __( 'Email', 'ma-lumiere-clinic' ),
				'phone'       => __( 'Phone', 'ma-lumiere-clinic' ),
				'dob'         => __( 'Date of birth (optional)', 'ma-lumiere-clinic' ),
				'gender'      => __( 'Gender (optional)', 'ma-lumiere-clinic' ),
				'message'     => __( 'Anything we should know? (optional)', 'ma-lumiere-clinic' ),
				'ref'         => __( 'Reference', 'ma-lumiere-clinic' ),
				'with'        => __( 'Consultation', 'ma-lumiere-clinic' ),
			),
		);

		// Config is injected as hex-escaped JSON so display strings keep
		// their raw `&` (wp_localize_script would hand back `&amp;`).
		$config_json = wp_json_encode( $config, JSON_UNESCAPED_SLASHES );
		$config_json = str_replace(
			array( '<', '>', '&', "\x00" ),
			array( '\u003c', '\u003e', '\u0026', '\u0000' ),
			$config_json
		);
		wp_add_inline_script( 'ml-booking', 'window.MLBooking=' . $config_json . ';', 'before' );

		// Ensure the stylesheet prints even when the head already ran.
		if ( did_action( 'wp_head' ) ) {
			add_action(
				'wp_footer',
				static function () {
					wp_print_styles( array( 'ml-booking' ) );
				},
				99
			);
		}

		return self::markup( $config );
	}

	/**
	 * Decorative image for a treatment card: featured image when the real
	 * post has one, otherwise a curated stock photo (hotlink) so the public
	 * step never ships empty artwork.
	 *
	 * @param array $item Treatment row.
	 *
	 * @return string Absolute image URL.
	 */
	private static function treatment_image( array $item ) {
		if ( ! empty( $item['image_id'] ) ) {
			$url = wp_get_attachment_image_url( (int) $item['image_id'], 'medium_large' );
			if ( $url ) {
				return esc_url_raw( $url );
			}
		}
		$stock = array(
			'acne'     => 'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=800&q=80',
			'skincare' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=800&q=80',
			'aesthetic' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80',
			'default'  => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=800&q=80',
		);
		$cat = strtolower( sanitize_title( (string) $item['category'] ) );
		if ( isset( $stock[ $cat ] ) ) {
			return $stock[ $cat ];
		}
		return $stock['default'];
	}

	/**
	 * Normalize a stored value for display. The site's treatment post title
	 * is persisted with HTML entities (e.g. `Acne &amp; Acne Scars`); decode
	 * them so the widget shows the real name and escapers stay meaningful.
	 *
	 * @param string $value Raw stored value.
	 *
	 * @return string Display value.
	 */
	private static function display( $value ) {
		return html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Widget markup shell; step bodies are filled by booking.js.
	 *
	 * @param array $config Localized config.
	 *
	 * @return string
	 */
	private static function markup( array $config ) {
		$steps = array( 'treatment', 'date', 'time', 'details', 'review', 'confirmed' );
		$list  = '';
		foreach ( $steps as $i => $key ) {
			$label = isset( $config['strings'][ $key ] ) ? $config['strings'][ $key ] : $key;
			$list .= sprintf(
				'<li class="ml-booking__step ml-booking__step--%1$s" data-step="%2$s"><span class="ml-booking__step-num">%3$s</span><span class="ml-booking__step-label">%4$s</span></li>',
				esc_attr( $key ),
				(int) $i,
				esc_html( (string) ( $i + 1 ) ),
				esc_html( $label )
			);
		}

		return '<div class="ml-booking" id="ml-booking">
			<div class="ml-booking__inner">
				<ol class="ml-booking__steps" aria-label="' . esc_attr__( 'Booking steps', 'ma-lumiere-clinic' ) . '">' . $list . '</ol>
				<div class="ml-booking__body" role="region" aria-live="polite" aria-busy="true">
					<p class="ml-booking__loading">' . esc_html__( 'Loading booking form…', 'ma-lumiere-clinic' ) . '</p>
				</div>
			</div>
		</div>';
	}
}