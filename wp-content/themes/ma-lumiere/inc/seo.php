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
add_action( 'wp_head', 'ml_meta_description', 3 );
add_action( 'wp_head', 'ml_article_schema', 4 );
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
			$og_title = is_front_page()
				? wp_specialchars_decode( ml_clinic_name(), ENT_QUOTES ) . ' · Medical & Aesthetic Skin Clinic in Mira Road East'
				: single_post_title( '', false );
			$og_type  = is_singular( 'post' ) ? 'article' : 'website';

			printf( '<meta property="og:type" content="%1$s"/>' . "\n", esc_attr( $og_type ) );
			printf( '<meta property="og:title" content="%1$s"/>' . "\n", esc_attr( $og_title ) );
			printf( '<meta property="og:url" content="%1$s"/>' . "\n", esc_url( get_permalink() ) );
			printf( '<meta name="twitter:title" content="%1$s"/>' . "\n", esc_attr( $og_title ) );
		} else {
			printf( '<meta property="og:type" content="website"/>' . "\n" );
			printf( '<meta property="og:title" content="%1$s"/>' . "\n", esc_attr( wp_get_document_title() ) );
			printf( '<meta property="og:url" content="%1$s"/>' . "\n", esc_url( home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ) ) );
		}
	}
}

/**
 * Meta description for the current view.
 *
 * Uses the post excerpt on singular views, term descriptions on archives,
 * and clinic-specific copy on the home and blog pages. Skipped when an SEO
 * plugin is active so the plugin remains the single source of truth.
 */
function ml_meta_description() {
	if ( ml_has_seo_plugin() ) {
		return;
	}

	$description = '';

	if ( is_front_page() ) {
		$description = __( 'Ma Lumière is a medical and aesthetic skin clinic in Mira Road East, Mumbai, offering personalised treatments for skin, hair, and ageing concerns.', 'ma-lumiere' );
	} elseif ( is_home() && ! is_front_page() ) {
		$description = __( 'Skin knowledge from Ma Lumière in Mira Road East, Mumbai: practical guides to skin treatments, consultations, and aftercare.', 'ma-lumiere' );
	} elseif ( is_singular() ) {
		$post_id     = get_queried_object_id();
		$description = get_the_excerpt( $post_id );

		if ( ! $description ) {
			$description = wp_trim_words(
				wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) ),
				32,
				'…'
			);
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$description = term_description();

		if ( ! $description ) {
			$description = sprintf(
				/* translators: %s: archive title. */
				__( 'Articles about %s from the Ma Lumière skin journal.', 'ma-lumiere' ),
				single_term_title( '', false )
			);
		}
	} elseif ( is_author() ) {
		$author_id   = get_queried_object_id();
		$description = get_the_author_meta( 'description', $author_id );

		if ( ! $description ) {
			$description = sprintf(
				/* translators: %s: author name. */
				__( 'Articles by %s at Ma Lumière.', 'ma-lumiere' ),
				get_the_author_meta( 'display_name', $author_id )
			);
		}
	}

	$description = trim( wp_strip_all_tags( (string) apply_filters( 'ml_meta_description', $description ) ) );

	if ( ! $description ) {
		return;
	}

	$description = esc_attr( wp_html_excerpt( $description, 158, '…' ) );

	printf( '<meta name="description" content="%1$s"/>' . "\n", $description );
	printf( '<meta property="og:description" content="%1$s"/>' . "\n", $description );
	printf( '<meta name="twitter:description" content="%1$s"/>' . "\n", $description );
}

/**
 * BlogPosting schema for single posts.
 *
 * Adds article-level structured data (headline, dates, author, publisher,
 * image, section, and keywords) without duplicating an SEO plugin.
 */
function ml_article_schema() {
	if ( ml_has_seo_plugin() || ! is_singular( 'post' ) ) {
		return;
	}

	$post_id    = get_queried_object_id();
	$permalink  = get_permalink( $post_id );
	$excerpt    = get_the_excerpt( $post_id );
	$author_id  = (int) get_post_field( 'post_author', $post_id );
	$publisher  = array(
		'@type' => 'Organization',
		'name'  => wp_specialchars_decode( ml_clinic_name(), ENT_QUOTES ),
		'url'   => home_url( '/' ),
	);
	$logo_id    = (int) get_theme_mod( 'custom_logo' );
	$logo_url   = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : ml_default_logo_url();

	if ( $logo_url ) {
		$publisher['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $logo_url,
		);
	}

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'@id'              => $permalink . '#article',
		'headline'         => wp_strip_all_tags( wp_specialchars_decode( get_the_title( $post_id ), ENT_QUOTES ) ),
		'description'      => wp_strip_all_tags( wp_specialchars_decode( $excerpt, ENT_QUOTES ) ),
		'datePublished'    => get_the_date( DATE_W3C, $post_id ),
		'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
		'inLanguage'       => get_bloginfo( 'language' ),
		'author'           => array(
			'@type' => 'Organization',
			'name'  => wp_specialchars_decode( get_the_author_meta( 'display_name', $author_id ), ENT_QUOTES ),
			'url'   => get_author_posts_url( $author_id ),
		),
		'publisher'        => $publisher,
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $permalink,
		),
	);

	$image = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( $image ) {
		$schema['image'] = $image;
	}

	$categories = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
	if ( $categories ) {
		$schema['articleSection'] = wp_specialchars_decode( $categories[0], ENT_QUOTES );
	}

	$tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
	if ( $tags ) {
		$tags               = array_map(
			static function ( $tag ) {
				return wp_specialchars_decode( $tag, ENT_QUOTES );
			},
			$tags
		);
		$schema['keywords'] = implode( ', ', $tags );
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore
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