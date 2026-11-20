<?php
/**
 * Local SEO settings and accessors.
 *
 * Keeps the clinic's local-search facts in the Customizer so titles, meta
 * descriptions and structured data all read from one editable source.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'customize_register', 'ml_seo_customize_register' );

/**
 * Register the SEO & Local Search Customizer section.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function ml_seo_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'ml_seo_local',
		array(
			'title'       => __( 'SEO & Local Search', 'ma-lumiere' ),
			'panel'       => 'ml_site_content',
			'priority'    => 90,
			'description' => __( 'Used in page titles, meta descriptions and Google structured data. Keep the clinic name and address consistent with your Google Business Profile.', 'ma-lumiere' ),
		)
	);

	$settings = array(
		'ml_seo_home_title'      => array(
			'label'   => __( 'Homepage SEO title', 'ma-lumiere' ),
			'default' => __( 'Skin & Face Treatment Clinic in Mira Bhayander', 'ma-lumiere' ),
			'type'    => 'text',
			'desc'    => __( 'Around 50–60 characters. The clinic name is appended automatically.', 'ma-lumiere' ),
		),
		'ml_seo_home_description' => array(
			'label'   => __( 'Homepage meta description', 'ma-lumiere' ),
			'default' => __( 'Skin, hair and laser clinic in Mira Road East, serving Mira Bhayander. Book a consultation for acne, pigmentation, laser hair reduction and face treatments.', 'ma-lumiere' ),
			'type'    => 'textarea',
			'desc'    => __( 'Around 150–158 characters.', 'ma-lumiere' ),
		),
		'ml_seo_local_area'      => array(
			'label'   => __( 'Primary service area', 'ma-lumiere' ),
			'default' => 'Mira Road & Bhayander',
			'type'    => 'text',
			'desc'    => __( 'Appears in treatment titles, e.g. “Acne Treatment in Mira Road & Bhayander”.', 'ma-lumiere' ),
		),
		'ml_clinic_locality'     => array(
			'label'   => __( 'Locality / suburb', 'ma-lumiere' ),
			'default' => 'Mira Road East',
			'type'    => 'text',
		),
		'ml_clinic_region'       => array(
			'label'   => __( 'State / region', 'ma-lumiere' ),
			'default' => 'Maharashtra',
			'type'    => 'text',
		),
		'ml_clinic_postal_code'  => array(
			'label'   => __( 'PIN code', 'ma-lumiere' ),
			'default' => '401105',
			'type'    => 'text',
		),
		'ml_clinic_country'      => array(
			'label'   => __( 'Country code', 'ma-lumiere' ),
			'default' => 'IN',
			'type'    => 'text',
		),
		'ml_clinic_lat'          => array(
			'label'   => __( 'Latitude', 'ma-lumiere' ),
			'default' => '',
			'type'    => 'text',
			'desc'    => __( 'Optional. Copy the exact pin from Google Maps (e.g. 19.281234). Leave blank to omit geo data.', 'ma-lumiere' ),
			'sanitize' => 'ml_sanitize_coordinate',
		),
		'ml_clinic_lng'          => array(
			'label'   => __( 'Longitude', 'ma-lumiere' ),
			'default' => '',
			'type'    => 'text',
			'desc'    => __( 'Optional. Copy the exact pin from Google Maps (e.g. 72.868123).', 'ma-lumiere' ),
			'sanitize' => 'ml_sanitize_coordinate',
		),
		'ml_clinic_price_range'  => array(
			'label'   => __( 'Price range', 'ma-lumiere' ),
			'default' => '₹₹',
			'type'    => 'text',
		),
		'ml_clinic_map_url'      => array(
			'label'   => __( 'Google Maps / Business link', 'ma-lumiere' ),
			'default' => 'https://share.google/nmp0wXmDFFeuzSKNe',
			'type'    => 'url',
			'desc'    => __( 'Used as “hasMap” in structured data and as the Directions link.', 'ma-lumiere' ),
			'sanitize' => 'esc_url_raw',
		),
		'ml_areas_served'        => array(
			'label'   => __( 'Areas served', 'ma-lumiere' ),
			'default' => "Mira Road East\nMira Road West\nMira Bhayander\nBhayander East\nBhayander West\nBhayandar\nKashimira\nDahisar\nBorivali\nThane",
			'type'    => 'textarea',
			'desc'    => __( 'One area per line. Used in visible copy and LocalBusiness “areaServed” data.', 'ma-lumiere' ),
		),
		'ml_clinic_same_as'      => array(
			'label'   => __( 'Other profile links', 'ma-lumiere' ),
			'default' => '',
			'type'    => 'textarea',
			'desc'    => __( 'One URL per line — Practo, Justdial, Lybrate, Facebook, Instagram, YouTube. Social links from the footer are added automatically.', 'ma-lumiere' ),
		),
	);

	foreach ( $settings as $id => $args ) {
		$sanitize = isset( $args['sanitize'] ) ? $args['sanitize'] : ( 'textarea' === $args['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field' );

		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $args['default'],
				'sanitize_callback' => $sanitize,
			)
		);

		$wp_customize->add_control(
			$id,
			array(
				'label'       => $args['label'],
				'description' => isset( $args['desc'] ) ? $args['desc'] : '',
				'section'     => 'ml_seo_local',
				'type'        => $args['type'],
			)
		);
	}
}

/**
 * Sanitize a latitude/longitude value.
 *
 * @param string $value Raw value.
 * @return string
 */
function ml_sanitize_coordinate( $value ) {
	$value = trim( sanitize_text_field( (string) $value ) );

	if ( '' === $value ) {
		return '';
	}

	if ( ! is_numeric( $value ) ) {
		return '';
	}

	return (string) (float) $value;
}

/**
 * Primary service area used in search titles.
 *
 * @return string
 */
function ml_seo_local_area() {
	$area = trim( (string) ml_mod( 'ml_seo_local_area', 'Mira Road & Bhayander' ) );

	return $area ? $area : 'Mira Road & Bhayander';
}

/**
 * Clinic locality (suburb).
 *
 * @return string
 */
function ml_clinic_locality() {
	$locality = trim( (string) ml_mod( 'ml_clinic_locality', 'Mira Road East' ) );

	return $locality ? $locality : 'Mira Road East';
}

/**
 * Clinic state/region.
 *
 * @return string
 */
function ml_clinic_region() {
	return trim( (string) ml_mod( 'ml_clinic_region', 'Maharashtra' ) );
}

/**
 * Clinic postal code.
 *
 * @return string
 */
function ml_clinic_postal_code() {
	return trim( (string) ml_mod( 'ml_clinic_postal_code', '401105' ) );
}

/**
 * Clinic country code.
 *
 * @return string
 */
function ml_clinic_country() {
	return strtoupper( substr( trim( (string) ml_mod( 'ml_clinic_country', 'IN' ) ), 0, 2 ) );
}

/**
 * Clinic price range.
 *
 * @return string
 */
function ml_clinic_price_range() {
	return trim( (string) ml_mod( 'ml_clinic_price_range', '₹₹' ) );
}

/**
 * Google Maps / Business profile URL.
 *
 * @return string
 */
function ml_clinic_map_url() {
	return (string) ml_mod( 'ml_clinic_map_url', 'https://share.google/nmp0wXmDFFeuzSKNe' );
}

/**
 * Geo coordinates as a schema node, or an empty array when unset.
 *
 * @return array
 */
function ml_clinic_geo() {
	$lat = trim( (string) ml_mod( 'ml_clinic_lat', '' ) );
	$lng = trim( (string) ml_mod( 'ml_clinic_lng', '' ) );

	if ( '' === $lat || '' === $lng || ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
		return array();
	}

	return array(
		'@type'     => 'GeoCoordinates',
		'latitude'  => (float) $lat,
		'longitude' => (float) $lng,
	);
}

/**
 * Areas the clinic serves, as a clean array.
 *
 * @return array
 */
function ml_areas_served() {
	$raw = (string) ml_mod( 'ml_areas_served', "Mira Road East\nMira Road West\nMira Bhayander\nBhayander East\nBhayander West\nBhayandar\nKashimira\nDahisar\nBorivali\nThane" );

	$areas = array_filter(
		array_map(
			static function ( $area ) {
				return trim( wp_strip_all_tags( $area ) );
			},
			preg_split( '/\r\n|\r|\n/', $raw )
		)
	);

	return array_values( array_unique( $areas ) );
}

/**
 * Additional profile URLs entered in the Customizer.
 *
 * @return array
 */
function ml_clinic_same_as_urls() {
	$raw = (string) ml_mod( 'ml_clinic_same_as', '' );

	$urls = array_filter(
		array_map(
			'esc_url_raw',
			array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) )
		)
	);

	return array_values( array_unique( $urls ) );
}
