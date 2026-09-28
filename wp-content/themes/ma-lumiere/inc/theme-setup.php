<?php
/**
 * Theme setup: supports, menus, image sizes, logo.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'ml_theme_setup' );
/**
 * Register theme supports, menus and image sizes.
 */
function ml_theme_setup() {
	load_theme_textdomain( 'ma-lumiere', ML_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 120,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'customize-selective-refresh-widgets'
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'ma-lumiere' ),
			'footer'  => __( 'Footer Menu', 'ma-lumiere' ),
		)
	);

	// Editorial cropped sizes used across the site.
	add_image_size( 'ml-hero', 1400, 900, true );
	add_image_size( 'ml-card', 800, 600, true );
	add_image_size( 'ml-portrait', 900, 1100, true );
	add_image_size( 'ml-square', 640, 640, true );

	// Standard WP content width.
	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 1200;
	}
}