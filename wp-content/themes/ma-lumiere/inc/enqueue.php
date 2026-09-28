<?php
/**
 * Asset enqueueing — conditional, de-duplicated, footer-blocker in defer.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'ml_enqueue_assets' );
add_action( 'wp_resource_hints', 'ml_resource_hints', 10, 2 );

/**
 * Enqueue front-end CSS/JS.
 */
function ml_enqueue_assets() {
	// Fonts.
	$fonts_url = ml_google_fonts_url();
	if ( $fonts_url ) {
		wp_enqueue_style( 'ml-fonts', $fonts_url, array(), null );
	}

	// Global stylesheet (design system).
	wp_enqueue_style(
		'ma-lumiere',
		get_stylesheet_uri(),
		array(),
		ML_THEME_VERSION
	);
	wp_enqueue_style(
		'ml-site',
		ML_THEME_URI . '/assets/css/site.css',
		array( 'ma-lumiere' ),
		ML_THEME_VERSION
	);

	// Main behaviour (header, nav, accordion, reveals).
	wp_enqueue_script(
		'ml-main',
		ML_THEME_URI . '/assets/js/main.js',
		array(),
		ML_THEME_VERSION,
		array( 'strategy' => 'defer' )
	);

	// Before/after sliders — only where used.
	$needs_ba = is_front_page() || is_singular( 'ml_before_after' );
	if ( $needs_ba ) {
		wp_enqueue_script(
			'ml-before-after',
			ML_THEME_URI . '/assets/js/before-after.js',
			array(),
			ML_THEME_VERSION,
			array( 'strategy' => 'defer' )
		);
	}

	// Contact form — only where the form can appear.
	if ( is_front_page() || is_page_template( 'page-templates/page-contact.php' ) ) {
		wp_enqueue_script(
			'ml-contact',
			ML_THEME_URI . '/assets/js/contact-form.js',
			array(),
			ML_THEME_VERSION,
			array( 'strategy' => 'defer' )
		);
		wp_localize_script(
			'ml-contact',
			'mlContact',
			array(
				'restUrl' => esc_url_raw( rest_url( 'ml/v1/contact' ) ),
				'nonce'   => wp_create_nonce( 'ml_contact' ),
			)
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

/**
 * Preconnect hints for the Google Fonts origin.
 *
 * @param array  $urls          Resource hints.
 * @param string $relation_type Relation type.
 * @return array
 */
function ml_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}

/**
 * Google Fonts URL (Cormorant Garamond + Manrope).
 *
 * @return string
 */
function ml_google_fonts_url() {
	return 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600&family=Manrope:wght@400;500;600;700;800&display=swap';
}