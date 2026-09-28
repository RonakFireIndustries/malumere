<?php
/**
 * Helper functions: editable-content accessors, small utilities.
 *
 * Every editable field on the site resolves through one of these so the
 * templates stay clean and the Customizer stays the single source of truth.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a theme mod with a sensible default.
 *
 * @param string $key     Mod key.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function ml_mod( $key, $default = '' ) {
	return get_theme_mod( $key, $default );
}

/**
 * Clinic name — falls back to the WordPress site name.
 *
 * @return string
 */
function ml_clinic_name() {
	$name = ml_mod( 'ml_clinic_name', 'MA LUMIÈRE' );
	return $name ? $name : get_bloginfo( 'name' );
}

/**
 * Clinic address (editable in Customizer).
 *
 * @return string
 */
function ml_clinic_address() {
	return ml_mod( 'ml_clinic_address', 'Shop no. 117 1st floor Ma lumiere clinic , Vasudev sky high , Kanakia  Rd, Om Ram Sagar phase 1 . beverly park, mira rd east 401105' );
}

/**
 * Clinic phone number.
 *
 * @return string
 */
function ml_clinic_phone() {
	return ml_mod( 'ml_clinic_phone', '9987802355' );
}

/**
 * Clinic email address.
 *
 * @return string
 */
function ml_clinic_email() {
	return ml_mod( 'ml_clinic_email', 'malumiere2026@gmail.com' );
}

/**
 * Clinic WhatsApp number.
 *
 * @return string
 */
function ml_clinic_whatsapp() {
	return ml_mod( 'ml_clinic_whatsapp', '9987802355' );
}

/**
 * Clinic opening hours (multi-line, editable in Customizer).
 *
 * @return string
 */
function ml_clinic_hours() {
	return ml_mod( 'ml_clinic_hours', '' );
}

/**
 * Doctor display name.
 *
 * @return string
 */
function ml_doctor_name() {
	return ml_mod( 'ml_doctor_name', 'Dr. Rishita Ray' );
}

/**
 * Doctor qualifications.
 *
 * @return string
 */
function ml_doctor_qualifications() {
	return ml_mod( 'ml_doctor_qualifications', 'Founder & Aesthetic Physician' );
}

/**
 * Doctor biography (rich text).
 *
 * @return string
 */
function ml_doctor_bio() {
	return wp_kses_post( ml_mod( 'ml_doctor_bio', '' ) );
}

/**
 * Doctor areas of expertise, as array.
 *
 * @return array
 */
function ml_doctor_expertise() {
	$raw = ml_mod( 'ml_doctor_expertise', '' );
	if ( is_array( $raw ) ) {
		return array_filter( array_map( 'trim', $raw ) );
	}
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) ) );
}

/**
 * Doctor image attachment ID.
 *
 * @return int
 */
function ml_doctor_image_id() {
	return (int) ml_mod( 'ml_doctor_image', 0 );
}

/**
 * Hero image attachment ID.
 *
 * @return int
 */
function ml_hero_image_id() {
	return (int) ml_mod( 'ml_hero_image', 0 );
}

/**
 * Find a published page by its template file.
 *
 * @param string $template Template file name.
 * @return WP_Post|null
 */
function ml_page_by_template( $template ) {
	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template',
			'meta_value' => $template,
			'number'     => 1,
		)
	);
	return $pages ? $pages[0] : null;
}

/**
 * URL of a page using the given template, or the supplied fallback.
 *
 * @param string $template Template file name.
 * @param string $fallback Fallback URL.
 * @return string
 */
function ml_page_url_by_template( $template, $fallback = '#' ) {
	$page = ml_page_by_template( $template );
	return $page ? get_permalink( $page ) : $fallback;
}

/**
 * Render an arbitrary inline SVG icon by name.
 *
 * @param string $name Icon key.
 * @param string $size Icon size in px.
 * @return void
 */
function ml_icon( $name, $size = 22 ) {
	$icons = array(
		'care'        => '<path d="M12 2.7c-2.6 2.9-5.1 5.4-5.1 8.2a5.1 5.1 0 0 0 10.2 0c0-2.8-2.5-5.3-5.1-8.2Z"/>',
		'evidence'    => '<path d="M9.5 21h5M12 3v0M12 3a9 9 0 0 1 9 9v3l-2 2H5l-2-2v-3a9 9 0 0 1 9-9Z"/>',
		'advanced'    => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/>',
		'patient'     => '<circle cx="12" cy="8" r="3.6"/><path d="M5.5 21c.7-3.4 3.3-5 6.5-5s5.8 1.6 6.5 5"/>',
		'calendar'    => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 10h17M8 2.5V7M16 2.5V7"/>',
		'phone'       => '<path d="M5 3.5h3l1.6 4-2.1 1.6a13 13 0 0 0 6.4 6.4l1.6-2.1 4 1.6v3a2 2 0 0 1-2.2 2A17.5 17.5 0 0 1 3 5.7 2 2 0 0 1 5 3.5Z"/>',
		'mail'        => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m4 8 8 5.5L20 8"/>',
		'whatsapp'    => '<path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3Z"/><path d="M8.7 9.3c.3 2.4 2 4.2 4.4 4.6l1.4-1.1 2 1.4"/><path d="M8.7 9.3c.2 1.9 1.4 3.4 3.2 4.1"/>',
		'clock'       => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		'marker'      => '<path d="M12 21c4.5-4.2 7-7.6 7-11a7 7 0 1 0-14 0c0 3.4 2.5 6.8 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
		'arrow-right' => '<path d="M4 12h15M13 6l6 6-6 6"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'quote'       => '<path d="M9 7H5.5C4.7 7 4 7.7 4 8.5V12h5V8.5C9 7.7 8.3 7 9 7Z"/><path d="M4 12v3c0 1.1 2.5 2 5 2"/><path d="M19.5 7H16c-.8 0-1.5.7-1.5 1.5V12h5V8.5c0-.8-.7-1.5-1.5-1.5Z"/><path d="M14.5 12v3c0 1.1 2.5 2 5 2"/>',
		'grid'        => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
		'flower'      => '<circle cx="12" cy="12" r="2.8"/><path d="M12 6.5a3.2 3.2 0 0 1 6.2 1 3.2 3.2 0 0 1-1.8 3.2A3.2 3.2 0 0 1 19 15.5a3.2 3.2 0 0 1-5.9-1.2 3.2 3.2 0 0 1-2.2 0A3.2 3.2 0 0 1 5 14.3a3.2 3.2 0 0 1 1.8-4.6A3.2 3.2 0 0 1 8.6 6.5 3.2 3.2 0 0 1 12 6.5Z"/>',
		'compare'     => '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><path d="M3.5 4.5 21 19M12 5v14M12 21l-2.5-2M12 20l1-.8"/>',
	);

	// start with light default if missing.
	if ( ! isset( $icons[ $name ] ) ) {
		return;
	}

	$stroke_round = 'stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"';
	printf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" aria-hidden="true" focusable="false" %2$s>%3$s</svg>',
		(int) $size,
		$stroke_round,
		$icons[ $name ] // phpcs:ignore WordPress.Security.EscapeOutput -- static icon paths above.
	);
}

/**
 * Social profiles stored in the Customizer.
 *
 * @return array
 */
function ml_social_links() {
	$allowed = array( 'instagram', 'facebook', 'youtube', 'whatsapp' );
	$links   = array();
	foreach ( $allowed as $key ) {
		$url = ml_mod( 'ml_social_' . $key, '' );
		if ( $url ) {
			$links[ $key ] = $url;
		}
	}
	return $links;
}

/**
 * Is a legacy/SEO plugin that supersedes our light OG output active?
 *
 * @return bool
 */
function ml_has_seo_plugin() {
	return class_exists( 'RankMath' )
		|| defined( 'WPSEO_VERSION' )
		|| defined( 'AIOSEO_VERSION' );
}

/**
 * Fallback for the primary menu — used only until a menu is assigned.
 *
 * @return void
 */
function ml_primary_menu_fallback() {
	$cta_page = ml_page_url_by_template( 'page-templates/page-appointment.php' );
	echo '<ul class="menu">';
	printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( home_url( '/' ) ), esc_html__( 'Home', 'ma-lumiere' ) );
	printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( ml_page_url_by_template( 'page-templates/page-about.php' ) ), esc_html__( 'About', 'ma-lumiere' ) );
	printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( ml_page_url_by_template( 'page-templates/page-treatments.php', get_post_type_archive_link( 'ml_treatment' ) ) ), esc_html__( 'Treatments', 'ma-lumiere' ) );
	printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( ml_page_url_by_template( 'page-templates/page-contact.php' ) ), esc_html__( 'Contact', 'ma-lumiere' ) );
	printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $cta_page ), esc_html__( 'Book Consultation', 'ma-lumiere' ) );
	echo '</ul>';
}

/**
 * Fallback for footer menu column.
 *
 * @return void
 */
function ml_footer_menu_fallback() {
	$pages = array(
		'page-templates/page-about.php'      => __( 'About', 'ma-lumiere' ),
		'page-templates/page-treatments.php' => __( 'Treatments', 'ma-lumiere' ),
		'page-templates/page-contact.php'    => __( 'Contact', 'ma-lumiere' ),
		'page-templates/page-appointment.php' => __( 'Appointments', 'ma-lumiere' ),
	);
	echo '<ul>';
	foreach ( $pages as $tpl => $label ) {
		$fallback = 'page-templates/page-contact.php' === $tpl ? '' : get_post_type_archive_link( 'ml_treatment' );
		printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( ml_page_url_by_template( $tpl, (string) $fallback ) ), esc_html( $label ) );
	}
	echo '</ul>';
}

/**
 * Simple excerpt with a wrapper.
 *
 * @param int|null $length Number of words.
 * @return void
 */
function ml_excerpt( $length = null ) {
	$text = get_the_excerpt();
	if ( $length ) {
		$text = wp_trim_words( $text, $length, '…' );
	}
	echo '<p class="card__excerpt">' . esc_html( $text ) . '</p>';
}