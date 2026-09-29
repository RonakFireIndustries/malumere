<?php
/**
 * Branding: the logo the theme ships with, and the favicon it falls back to.
 *
 * The artwork is bundled so a fresh install is never unbranded. It is a
 * default and never an override — anything set in Appearance → Customize →
 * Site Identity still wins, so the client can replace both at any time.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'ml_default_site_icon', 99 );

/**
 * URL of the bundled brand mark, or an empty string if the file is missing.
 *
 * @return string
 */
function ml_default_logo_url() {
	return ml_asset_href( 'assets/images/logo-mark.png' );
}

/**
 * Print favicon links when no Site Icon has been uploaded.
 *
 * Emits exactly the tags core's wp_site_icon() emits, at the same priority, so
 * the document is identical whether the mark comes from here or from
 * Appearance → Customize → Site Identity. Returning early on has_site_icon()
 * is what keeps the two from ever both printing.
 *
 * @return void
 */
function ml_default_site_icon() {
	if ( has_site_icon() || is_customize_preview() ) {
		return;
	}

	$links = array(
		array( 'sizes' => '32x32', 'file' => 'icon-32.png' ),
		array( 'sizes' => '192x192', 'file' => 'icon-192.png' ),
		array( 'sizes' => '', 'file' => 'apple-touch-icon.png' ),
	);

	foreach ( $links as $link ) {
		$href = ml_asset_href( 'assets/images/' . $link['file'] );
		if ( ! $href ) {
			continue;
		}
		if ( $link['sizes'] ) {
			printf( '<link rel="icon" href="%1$s" sizes="%2$s" />' . "\n", $href, esc_attr( $link['sizes'] ) );
		} else {
			printf( '<link rel="apple-touch-icon" href="%s" />' . "\n", $href );
		}
	}

	// Windows pinned tiles. Core sends this as a meta tag rather than a link.
	$tile = ml_asset_href( 'assets/images/icon-270.png' );
	if ( $tile ) {
		printf( '<meta name="msapplication-TileImage" content="%s" />' . "\n", $tile );
	}

	// Browsers also probe /favicon.ico on the site root whether or not any
	// <link> is present, and without a real file there they fall back to
	// whatever icon the web root serves. Unversioned on purpose: a stable
	// path is the point, since callers that probe it send no query string.
	if ( is_readable( rtrim( ABSPATH, '/\\' ) . '/favicon.ico' ) ) {
		printf( '<link rel="icon" href="%s" sizes="any" />' . "\n", esc_url( home_url( '/favicon.ico' ) ) );
	}
}

/**
 * Cache-busted URL for a bundled theme asset, or '' if it is not there.
 *
 * @param string $relative_path Path relative to the theme root.
 * @return string
 */
function ml_asset_href( $relative_path ) {
	$file = ML_THEME_DIR . '/' . ltrim( $relative_path, '/' );

	if ( ! is_readable( $file ) ) {
		return '';
	}

	return esc_url( ML_THEME_URI . '/' . ltrim( $relative_path, '/' ) . '?v=' . ml_asset_version( $relative_path ) );
}
