<?php
/**
 * Lightweight SEO foundation: base types, OG/Twitter cards only when no
 * dedicated SEO plugin is active, JSON-LD organization/breadcrumb data.
 *
 * Intentionally non-competitive with Rank Math / Yoast / AIOSEO.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'ml_seo_head', 1 );
add_action( 'wp_head', 'ml_organization_schema', 2 );
add_filter( 'document_title_separator', 'ml_title_separator' );

/**
 * Organization schema on the front page (MedicalClinic subtype).
 */
function ml_organization_schema() {
	if ( ! is_front_page() ) {
		return;
	}

	$name    = wp_specialchars_decode( ml_clinic_name(), ENT_QUOTES );
	$url     = home_url( '/' );
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : ml_default_logo_url();
	$phone   = ml_clinic_phone();
	$email   = ml_clinic_email();
	$address = ml_clinic_address();

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => array( 'MedicalClinic', 'HealthAndBeautyBusiness' ),
		'name'     => $name,
		'url'      => $url,
	);

	if ( $logo ) {
		$schema['logo'] = $logo;
	}
	if ( $phone ) {
		$schema['telephone'] = $phone;
	}
	if ( $email ) {
		$schema['email'] = $email;
	}
	if ( $address && '[CLINIC ADDRESS REQUIRED]' !== $address ) {
		$schema['address'] = array(
			'@type'    => 'PostalAddress',
			'streetAddress' => $address,
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n"; // phpcs:ignore
}

/**
 * Content-type and OG/Twitter tags.
 */
function ml_seo_head() {
	if ( ! ml_has_seo_plugin() ) {
		$image = get_option( 'site_logo' ) ? wp_get_attachment_image_url( get_option( 'site_logo' ), 'full' ) : '';
		if ( ! $image && is_singular() && has_post_thumbnail() ) {
			$image = get_the_post_thumbnail_url( null, 'large' );
		}

		if ( $image ) {
			printf( '<meta property="og:image" content="%1$s"/>' . "\n", esc_url( $image ) );
			printf( '<meta name="twitter:card" content="summary_large_image"/>' . "\n" );
		}

		printf( '<meta property="og:site_name" content="%1$s"/>' . "\n", esc_attr( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) );
		printf( '<meta property="og:locale" content="%1$s"/>' . "\n", esc_attr( get_locale() ) );
		if ( is_singular() ) {
			printf( '<meta property="og:type" content="article"/>' . "\n" );
			printf( '<meta property="og:title" content="%1$s"/>' . "\n", esc_attr( single_post_title( '', false ) ) );
			printf( '<meta property="og:url" content="%1$s"/>' . "\n", esc_url( get_permalink() ) );
			printf( '<meta name="twitter:title" content="%1$s"/>' . "\n", esc_attr( single_post_title( '', false ) ) );
		} else {
			printf( '<meta property="og:type" content="website"/>' . "\n" );
			printf( '<meta property="og:title" content="%1$s"/>' . "\n", esc_attr( wp_get_document_title() ) );
			printf( '<meta property="og:url" content="%1$s"/>' . "\n", esc_url( home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ) ) );
		}
	}
}

/**
 * Elegant title separator.
 *
 * @param string $sep Separator.
 * @return string
 */
function ml_title_separator( $sep ) {
	return '·';
}