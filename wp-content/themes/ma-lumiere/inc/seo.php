<?php
/**
 * Local SEO foundation.
 *
 * Search titles, meta descriptions, Open Graph tags, structured data,
 * robots.txt and sitemap hygiene for a single-location clinic.
 *
 * Designed to stand down when Rank Math, Yoast or AIOSEO is active so there is
 * only ever one source of truth for titles and meta tags.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'ml_seo_head', 1 );
add_action( 'wp_head', 'ml_preload_hero_image', 2 );
add_action( 'wp_head', 'ml_organization_schema', 3 );
add_action( 'wp_head', 'ml_website_schema', 3 );
add_action( 'wp_head', 'ml_meta_description', 4 );
add_action( 'wp_head', 'ml_treatment_schema', 5 );
add_action( 'wp_head', 'ml_faq_schema', 6 );
add_action( 'wp_head', 'ml_article_schema', 7 );
add_action( 'wp_head', 'ml_physician_schema', 8 );

add_filter( 'document_title_parts', 'ml_document_title_parts' );
add_filter( 'document_title_separator', 'ml_title_separator' );
add_filter( 'robots_txt', 'ml_robots_txt', 20, 2 );
add_filter( 'wp_robots', 'ml_wp_robots' );
add_filter( 'wp_sitemaps_post_types', 'ml_sitemaps_post_types' );
add_filter( 'wp_sitemaps_taxonomies', 'ml_sitemaps_taxonomies' );

/**
 * Whether the theme should print its own SEO output.
 *
 * @return bool
 */
function ml_seo_enabled() {
	return ! ml_has_seo_plugin();
}

/**
 * Brand name used in titles and schema.
 *
 * @return string
 */
function ml_seo_brand_name() {
	$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$name = trim( (string) $name );

	return $name ? $name : 'Ma Lumière';
}

/**
 * Read a per-post SEO field.
 *
 * @param int    $post_id Post ID.
 * @param string $field   title|description|focus_keyword.
 * @return string
 */
function ml_seo_meta( $post_id, $field ) {
	$value = get_post_meta( (int) $post_id, '_ml_seo_' . $field, true );

	return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Build a treatment title with the local service area.
 *
 * Keeps the title near the ~60 character range by falling back to the
 * locality (e.g. “in Mira Road”) for long treatment names.
 *
 * @param string $title Treatment title.
 * @param string $area  Primary service area.
 * @return string
 */
function ml_seo_treatment_title( $title, $area ) {
	$title = wp_specialchars_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES );
	$area  = trim( (string) $area );

	$candidate = sprintf(
		/* translators: 1: treatment name, 2: service area. */
		__( '%1$s in %2$s', 'ma-lumiere' ),
		$title,
		$area
	);

	if ( mb_strlen( $candidate ) <= 52 ) {
		return $candidate;
	}

	$short = trim( (string) strtok( $area, '&' ) );
	if ( ! $short ) {
		$short = $area;
	}

	return sprintf(
		/* translators: 1: treatment name, 2: short locality. */
		__( '%1$s in %2$s', 'ma-lumiere' ),
		$title,
		$short
	);
}

/**
 * Local, keyword-led document titles.
 *
 * @param array $parts Title parts.
 * @return array
 */
function ml_document_title_parts( $parts ) {
	if ( ! ml_seo_enabled() ) {
		return $parts;
	}

	$area = ml_seo_local_area();

	if ( is_front_page() ) {
		$parts['title'] = ml_mod( 'ml_seo_home_title', __( 'Skin & Face Treatment Clinic in Mira Bhayander', 'ma-lumiere' ) );
		unset( $parts['tagline'] );
	} elseif ( is_singular( 'ml_treatment' ) ) {
		$post_id = get_queried_object_id();
		$custom  = ml_seo_meta( $post_id, 'title' );

		$parts['title'] = $custom ? $custom : ml_seo_treatment_title( get_the_title( $post_id ), $area );
	} elseif ( is_singular() ) {
		$post_id = get_queried_object_id();
		$custom  = ml_seo_meta( $post_id, 'title' );

		if ( $custom ) {
			$parts['title'] = $custom;
		} elseif ( is_page_template( 'page-templates/page-treatments.php' ) ) {
			/* translators: %s: service area. */
			$parts['title'] = sprintf( __( 'Skin, Hair & Laser Treatments in %s', 'ma-lumiere' ), $area );
		} elseif ( is_page_template( 'page-templates/page-mira-bhayander.php' ) ) {
			/* translators: %s: service area. */
			$parts['title'] = sprintf( __( 'Skin Clinic in %s', 'ma-lumiere' ), $area );
		} elseif ( is_page_template( 'page-templates/page-about.php' ) ) {
			/* translators: 1: clinic name, 2: locality. */
			$parts['title'] = sprintf( __( 'About %1$s · Skin Clinic in %2$s', 'ma-lumiere' ), ml_seo_brand_name(), ml_clinic_locality() );
		} elseif ( is_page_template( 'page-templates/page-contact.php' ) ) {
			/* translators: 1: clinic name, 2: locality. */
			$parts['title'] = sprintf( __( 'Contact %1$s · Skin Clinic in %2$s', 'ma-lumiere' ), ml_seo_brand_name(), ml_clinic_locality() );
		} elseif ( is_page_template( 'page-templates/page-faq.php' ) ) {
			/* translators: %s: locality. */
			$parts['title'] = sprintf( __( 'Skin & Laser Treatment FAQs · %s', 'ma-lumiere' ), ml_clinic_locality() );
		} elseif ( is_page_template( 'page-templates/page-appointment.php' ) ) {
			/* translators: %s: locality. */
			$parts['title'] = sprintf( __( 'Book a Skin Consultation in %s', 'ma-lumiere' ), ml_clinic_locality() );
		}
	} elseif ( is_post_type_archive( 'ml_treatment' ) ) {
		/* translators: %s: service area. */
		$parts['title'] = sprintf( __( 'Skin, Hair & Laser Treatments in %s', 'ma-lumiere' ), $area );
	} elseif ( is_tax( 'treatment_category' ) ) {
		/* translators: 1: treatment type, 2: service area. */
		$parts['title'] = sprintf( __( '%1$s Treatments in %2$s', 'ma-lumiere' ), single_term_title( '', false ), $area );
	} elseif ( is_home() ) {
		$parts['title'] = __( 'Skin Journal', 'ma-lumiere' );
	} elseif ( is_category() || is_tag() ) {
		/* translators: %s: archive title. */
		$parts['title'] = sprintf( __( '%s · Skin Journal', 'ma-lumiere' ), single_term_title( '', false ) );
	} elseif ( is_search() ) {
		/* translators: %s: search query. */
		$parts['title'] = sprintf( __( 'Search results for “%s”', 'ma-lumiere' ), get_search_query() );
	} elseif ( is_404() ) {
		$parts['title'] = __( 'Page not found', 'ma-lumiere' );
	}

	$parts['site'] = ml_seo_brand_name();

	return $parts;
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

/**
 * Current, canonical-ish URL for Open Graph tags.
 *
 * @return string
 */
function ml_seo_current_url() {
	global $wp;

	if ( is_singular() ) {
		return get_permalink();
	}

	$path = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';

	return $path ? home_url( '/' . $path . '/' ) : home_url( '/' );
}

/**
 * Best available social-share image for the current view.
 *
 * @return array Empty when nothing usable exists.
 */
function ml_seo_image_data() {
	$image = '';
	$alt   = '';
	$width = 0;
	$height = 0;

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		$thumb   = get_post_thumbnail_id( $post_id );

		if ( ml_attachment_is_deployed( $thumb ) ) {
			$src = wp_get_attachment_image_src( $thumb, 'large' );
			if ( $src ) {
				$image  = $src[0];
				$width  = (int) $src[1];
				$height = (int) $src[2];
				$alt    = (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true );
			}
		}

		if ( ! $image && is_singular( 'ml_treatment' ) ) {
			$image = ml_treatment_image_url( $post_id, 'large' );
			$alt   = wp_strip_all_tags( get_the_title( $post_id ) );
		}
	}

	if ( ! $image ) {
		$image = ml_hero_image_url();
		/* translators: 1: clinic name, 2: locality. */
		$alt = sprintf( __( '%1$s skin clinic in %2$s', 'ma-lumiere' ), ml_seo_brand_name(), ml_clinic_locality() );

		if ( $image ) {
			$width  = 900;
			$height = 1100;
		}
	}

	if ( ! $image ) {
		$image = ml_default_logo_url();
		$alt   = ml_seo_brand_name();

		if ( $image ) {
			$width  = 256;
			$height = 336;
		}
	}

	if ( ! $image ) {
		return array();
	}

	if ( ! $alt ) {
		$alt = ml_seo_brand_name();
	}

	return array(
		'url'    => $image,
		'alt'    => $alt,
		'width'  => $width,
		'height' => $height,
	);
}

/**
 * Preload the homepage hero image for a faster LCP.
 */
function ml_preload_hero_image() {
	if ( ! is_front_page() ) {
		return;
	}

	$image = ml_hero_image_url();

	if ( ! $image ) {
		return;
	}

	printf( '<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n", esc_url( $image ) );
}

/**
 * Content-type and Open Graph / Twitter tags.
 */
function ml_seo_head() {
	if ( ! ml_seo_enabled() ) {
		return;
	}

	$title  = wp_get_document_title();
	$url    = ml_seo_current_url();
	$type   = is_singular( 'post' ) ? 'article' : 'website';
	$image  = ml_seo_image_data();
	$locale = str_replace( '-', '_', get_bloginfo( 'language' ) );

	printf( '<meta property="og:site_name" content="%s"/>' . "\n", esc_attr( ml_seo_brand_name() ) );
	printf( '<meta property="og:locale" content="%s"/>' . "\n", esc_attr( $locale ) );
	printf( '<meta property="og:type" content="%s"/>' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s"/>' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:url" content="%s"/>' . "\n", esc_url( $url ) );
	printf( '<meta name="twitter:title" content="%s"/>' . "\n", esc_attr( $title ) );

	if ( $image ) {
		printf( '<meta property="og:image" content="%s"/>' . "\n", esc_url( $image['url'] ) );
		printf( '<meta property="og:image:alt" content="%s"/>' . "\n", esc_attr( $image['alt'] ) );

		if ( $image['width'] && $image['height'] ) {
			printf( '<meta property="og:image:width" content="%d"/>' . "\n", (int) $image['width'] );
			printf( '<meta property="og:image:height" content="%d"/>' . "\n", (int) $image['height'] );
		}

		printf( '<meta name="twitter:card" content="summary_large_image"/>' . "\n" );
		printf( '<meta name="twitter:image" content="%s"/>' . "\n", esc_url( $image['url'] ) );
	} else {
		printf( '<meta name="twitter:card" content="summary"/>' . "\n" );
	}

	if ( is_singular( 'post' ) ) {
		$post_id = get_queried_object_id();

		printf( '<meta property="article:published_time" content="%s"/>' . "\n", esc_attr( get_the_date( DATE_W3C, $post_id ) ) );
		printf( '<meta property="article:modified_time" content="%s"/>' . "\n", esc_attr( get_the_modified_date( DATE_W3C, $post_id ) ) );

		$categories = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
		if ( $categories ) {
			printf( '<meta property="article:section" content="%s"/>' . "\n", esc_attr( wp_specialchars_decode( $categories[0], ENT_QUOTES ) ) );
		}
	}
}

/**
 * Meta description for the current view.
 */
function ml_meta_description() {
	if ( ! ml_seo_enabled() ) {
		return;
	}

	$description = ml_seo_description_text();

	if ( ! $description ) {
		return;
	}

	$description = esc_attr( wp_html_excerpt( wp_strip_all_tags( $description ), 158, '…' ) );

	printf( '<meta name="description" content="%1$s"/>' . "\n", $description );
	printf( '<meta property="og:description" content="%1$s"/>' . "\n", $description );
	printf( '<meta name="twitter:description" content="%1$s"/>' . "\n", $description );
}

/**
 * Build the description for the current view.
 *
 * @return string
 */
function ml_seo_description_text() {
	$area = ml_seo_local_area();

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		$custom  = ml_seo_meta( $post_id, 'description' );

		if ( $custom ) {
			return $custom;
		}
	}

	if ( is_front_page() ) {
		return ml_mod( 'ml_seo_home_description', __( 'Skin, hair and laser clinic in Mira Road East, serving Mira Bhayander. Book a consultation for acne, pigmentation, laser hair reduction and face treatments.', 'ma-lumiere' ) );
	}

	if ( is_singular( 'ml_treatment' ) ) {
		$post_id = get_queried_object_id();
		$excerpt = trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) );
		$title   = wp_strip_all_tags( get_the_title( $post_id ) );

		/*
		 * Pair the treatment excerpt with the most specific local suffix that
		 * still fits a search snippet, rather than cutting a sentence in half.
		 */
		$suffixes = array(
			sprintf( __( 'Book a consultation at Ma Lumière, a skin and laser clinic in %s.', 'ma-lumiere' ), $area ),
			sprintf( __( 'Book a consultation at Ma Lumière in %s.', 'ma-lumiere' ), ml_clinic_locality() ),
		);

		if ( $excerpt ) {
			$excerpt = rtrim( $excerpt, ' .' ) . '.';

			foreach ( $suffixes as $suffix ) {
				$candidate = $excerpt . ' ' . $suffix;

				if ( mb_strlen( $candidate ) <= 158 ) {
					return $candidate;
				}
			}

			return wp_html_excerpt( $excerpt, 158, '…' );
		}

		return sprintf(
			/* translators: 1: treatment name, 2: locality, 3: phone. */
			__( '%1$s at Ma Lumière, a skin and laser clinic in %2$s. Consultation-first care. Call %3$s.', 'ma-lumiere' ),
			$title,
			ml_clinic_locality(),
			ml_clinic_phone()
		);
	}

	if ( is_page_template( 'page-templates/page-treatments.php' ) ) {
		return sprintf(
			/* translators: %s: service area. */
			__( 'Explore skin, hair and laser treatments at Ma Lumière in %s — acne, pigmentation, laser hair reduction, facials and more. Book a consultation.', 'ma-lumiere' ),
			$area
		);
	}

	if ( is_page_template( 'page-templates/page-mira-bhayander.php' ) ) {
		return sprintf(
			/* translators: %s: service area. */
			__( 'Looking for a skin or face treatment clinic in %s? Ma Lumière in Mira Road East offers acne, pigmentation, laser and facial treatments. Book a consultation.', 'ma-lumiere' ),
			$area
		);
	}

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		$excerpt = trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) );

		if ( $excerpt ) {
			return $excerpt;
		}

		$content = trim( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) ) );

		if ( $content ) {
			return wp_trim_words( $content, 30, '…' );
		}

		return sprintf(
			/* translators: 1: page title, 2: service area. */
			__( '%1$s at Ma Lumière, a skin and laser clinic in %2$s.', 'ma-lumiere' ),
			wp_strip_all_tags( get_the_title( $post_id ) ),
			$area
		);
	}

	if ( is_home() ) {
		return __( 'Practical skin, hair and laser guidance from Ma Lumière, a skin clinic in Mira Road East, serving Mira Bhayander.', 'ma-lumiere' );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$description = term_description();

		if ( $description ) {
			return $description;
		}

		return sprintf(
			/* translators: %s: archive title. */
			__( 'Articles about %s from the Ma Lumière skin journal in Mira Road East.', 'ma-lumiere' ),
			single_term_title( '', false )
		);
	}

	if ( is_author() ) {
		$author_id   = get_queried_object_id();
		$description = get_the_author_meta( 'description', $author_id );

		if ( $description ) {
			return $description;
		}

		return sprintf(
			/* translators: %s: author name. */
			__( 'Articles by %s at Ma Lumière.', 'ma-lumiere' ),
			get_the_author_meta( 'display_name', $author_id )
		);
	}

	if ( is_post_type_archive( 'ml_treatment' ) ) {
		return sprintf(
			/* translators: %s: service area. */
			__( 'Browse skin, hair and laser treatments available at Ma Lumière in %s. Every plan starts with a private consultation.', 'ma-lumiere' ),
			$area
		);
	}

	return '';
}

/* -----------------------------------------------------------------
 * Structured data
 * ---------------------------------------------------------------- */

/**
 * Areas served as schema City nodes.
 *
 * @return array
 */
function ml_area_served_schema() {
	$areas = ml_areas_served();
	$nodes = array();

	foreach ( $areas as $area ) {
		$nodes[] = array(
			'@type' => 'City',
			'name'  => $area,
		);
	}

	return $nodes;
}

/**
 * sameAs profile URLs (social + Customizer links).
 *
 * @return array
 */
function ml_schema_same_as() {
	$urls = array();

	foreach ( ml_social_links() as $key => $url ) {
		if ( 'whatsapp' === $key ) {
			continue;
		}
		$urls[] = $url;
	}

	$urls = array_merge( $urls, ml_clinic_same_as_urls() );

	return array_values( array_unique( array_filter( $urls ) ) );
}

/**
 * Convert a 12-hour clinic time to 24-hour format.
 *
 * @param string $time e.g. "12:00 pm".
 * @return string e.g. "12:00", or '' when unparseable.
 */
function ml_time_to_24h( $time ) {
	$time = strtolower( preg_replace( '/\s+/', '', (string) $time ) );

	if ( ! preg_match( '/^(\d{1,2})(?::(\d{2}))?(am|pm)$/', $time, $matches ) ) {
		return '';
	}

	$hour   = (int) $matches[1];
	$minute = isset( $matches[2] ) && '' !== $matches[2] ? $matches[2] : '00';
	$mer    = $matches[3];

	if ( 'pm' === $mer && $hour < 12 ) {
		$hour += 12;
	}

	if ( 'am' === $mer && 12 === $hour ) {
		$hour = 0;
	}

	return sprintf( '%02d:%s', $hour, $minute );
}

/**
 * Parse the editable opening-hours text into schema data.
 *
 * Expects lines such as "Tuesday – Saturday · 12:00 pm – 9:00 pm" and
 * "Monday · Closed".
 *
 * @return array
 */
function ml_opening_hours_schema() {
	$raw = (string) ml_clinic_hours();

	if ( ! $raw ) {
		return array();
	}

	$days = array(
		'monday'    => 'Monday',
		'tuesday'   => 'Tuesday',
		'wednesday' => 'Wednesday',
		'thursday'  => 'Thursday',
		'friday'    => 'Friday',
		'saturday'  => 'Saturday',
		'sunday'    => 'Sunday',
	);

	$ordered = array_values( $days );
	$schema  = array();

	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );

		if ( ! $line ) {
			continue;
		}

		$parts = preg_split( '/\s*[·|]\s*/u', $line, 2 );

		if ( count( $parts ) < 2 ) {
			continue;
		}

		$day_part  = trim( $parts[0] );
		$time_part = trim( $parts[1] );

		if ( false !== stripos( $time_part, 'closed' ) ) {
			continue;
		}

		$day_part = str_replace( array( '–', '—', ' to ' ), '-', $day_part );
		$names    = array_filter( array_map( 'trim', explode( '-', $day_part ) ) );
		$day_keys = array();

		foreach ( $names as $name ) {
			$key = strtolower( $name );

			if ( isset( $days[ $key ] ) ) {
				$day_keys[] = $days[ $key ];
			}
		}

		if ( ! $day_keys ) {
			continue;
		}

		if ( 2 === count( $day_keys ) ) {
			$start = array_search( $day_keys[0], $ordered, true );
			$end   = array_search( $day_keys[1], $ordered, true );

			if ( false !== $start && false !== $end ) {
				$range = array();
				$index = $start;
				$guard = 0;

				while ( $guard < 7 ) {
					$range[] = $ordered[ $index ];

					if ( $index === $end ) {
						break;
					}

					$index = ( $index + 1 ) % 7;
					$guard++;
				}

				$day_keys = $range;
			}
		}

		preg_match_all( '/\d{1,2}(?::\d{2})?\s*(?:am|pm)/i', $time_part, $matches );

		if ( count( $matches[0] ) < 2 ) {
			continue;
		}

		$opens  = ml_time_to_24h( $matches[0][0] );
		$closes = ml_time_to_24h( $matches[0][1] );

		if ( ! $opens || ! $closes ) {
			continue;
		}

		$schema[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => $day_keys,
			'opens'     => $opens,
			'closes'    => $closes,
		);
	}

	return $schema;
}

/**
 * The clinic as a schema node, reusable as a provider on treatment pages.
 *
 * @param bool $full Include descriptive fields (front page / local page).
 * @return array
 */
function ml_clinic_schema_node( $full = true ) {
	$node = array(
		'@type'                => array( 'MedicalClinic', 'HealthAndBeautyBusiness' ),
		'@id'                  => home_url( '/#clinic' ),
		'name'                 => ml_seo_brand_name(),
		'alternateName'        => 'Ma Lumiere Clinic',
		'url'                  => home_url( '/' ),
		'telephone'            => ml_clinic_phone(),
		'email'                => ml_clinic_email(),
		'address'              => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => ml_clinic_address(),
			'addressLocality' => ml_clinic_locality(),
			'addressRegion'   => ml_clinic_region(),
			'postalCode'      => ml_clinic_postal_code(),
			'addressCountry'  => ml_clinic_country(),
		),
		'areaServed'           => ml_area_served_schema(),
		'medicalSpecialty'     => 'Dermatology',
		'founder'              => ml_physician_node( false ),
		'employee'             => ml_physician_node( false ),
	);

	if ( $full ) {
		$description = get_bloginfo( 'description' );

		$node['description'] = $description
			? wp_strip_all_tags( $description )
			: sprintf(
				/* translators: 1: locality, 2: service area. */
				__( 'Medical and aesthetic skin, hair and laser clinic in %1$s, serving %2$s.', 'ma-lumiere' ),
				ml_clinic_locality(),
				ml_seo_local_area()
			);

		$node['priceRange']         = ml_clinic_price_range();
		$node['currenciesAccepted'] = 'INR';
		$node['paymentAccepted']    = 'Cash, UPI, Credit Card, Debit Card';
		$node['knowsAbout']         = array(
			'Dermatology',
			'Acne treatment',
			'Acne scar treatment',
			'Pigmentation treatment',
			'Laser hair reduction',
			'Facial rejuvenation',
			'Anti-ageing skin treatments',
		);

		$hero = ml_hero_image_url();
		if ( $hero ) {
			$node['image'] = $hero;
		}

		$logo = ml_default_logo_url();
		if ( $logo ) {
			$node['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo,
			);
		}

		$map = ml_clinic_map_url();
		if ( $map ) {
			$node['hasMap'] = $map;
		}
	}

	$hours = ml_opening_hours_schema();
	if ( $hours ) {
		$node['openingHoursSpecification'] = $hours;
	}

	$geo = ml_clinic_geo();
	if ( $geo ) {
		$node['geo'] = $geo;
	}

	$same_as = ml_schema_same_as();
	if ( $same_as ) {
		$node['sameAs'] = $same_as;
	}

	return array_filter( $node );
}

/**
 * Organization / LocalBusiness schema.
 */
function ml_organization_schema() {
	if ( ! ml_seo_enabled() ) {
		return;
	}

	if ( ! is_front_page() && ! is_page_template( 'page-templates/page-mira-bhayander.php' ) ) {
		return;
	}

	$schema = ml_clinic_schema_node();

	$treatments = get_posts(
		array(
			'post_type'      => 'ml_treatment',
			'posts_per_page' => 10,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);

	if ( $treatments ) {
		$services = array();

		foreach ( $treatments as $treatment ) {
			$services[] = array(
				'@type'       => 'Service',
				'name'        => wp_strip_all_tags( get_the_title( $treatment ) ),
				'serviceType' => wp_strip_all_tags( get_the_title( $treatment ) ),
				'url'         => get_permalink( $treatment ),
			);
		}

		$schema['availableService'] = $services;
	}

	$schema = array_merge(
		array( '@context' => 'https://schema.org' ),
		$schema
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * WebSite schema with a sitelinks search action.
 */
function ml_website_schema() {
	if ( ! ml_seo_enabled() || ! is_front_page() ) {
		return;
	}

	$schema = array(
		'@context'       => 'https://schema.org',
		'@type'          => 'WebSite',
		'@id'            => home_url( '/#website' ),
		'url'            => home_url( '/' ),
		'name'           => ml_seo_brand_name(),
		'description'    => wp_strip_all_tags( get_bloginfo( 'description' ) ),
		'inLanguage'     => get_bloginfo( 'language' ),
		'publisher'      => array( '@id' => home_url( '/#clinic' ) ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * MedicalProcedure / Service schema for a single treatment.
 */
function ml_treatment_schema() {
	if ( ! ml_seo_enabled() || ! is_singular( 'ml_treatment' ) ) {
		return;
	}

	$post_id     = get_queried_object_id();
	$permalink   = get_permalink( $post_id );
	$title       = wp_strip_all_tags( get_the_title( $post_id ) );
	$description = (string) get_post_meta( $post_id, '_ml_treatment_short', true );

	if ( ! $description ) {
		$description = get_the_excerpt( $post_id );
	}

	$node = array(
		'@type'            => array( 'Service', 'MedicalProcedure' ),
		'@id'              => $permalink . '#treatment',
		'name'             => $title,
		'serviceType'      => $title,
		'description'      => wp_strip_all_tags( $description ),
		'url'              => $permalink,
		'provider'         => ml_clinic_schema_node( false ),
		'areaServed'       => ml_area_served_schema(),
		'procedureType'    => 'https://schema.org/NoninvasiveProcedure',
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $permalink,
		),
	);

	$image = ml_treatment_image_url( $post_id, 'large' );
	if ( $image ) {
		$node['image'] = $image;
	}

	$terms = get_the_terms( $post_id, 'treatment_category' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$node['category'] = wp_specialchars_decode( $terms[0]->name, ENT_QUOTES );
	}

	$process = (string) get_post_meta( $post_id, '_ml_treatment_process', true );
	$steps   = array_filter( array_map( 'trim', explode( "\n", $process ) ) );
	if ( $steps ) {
		$node['howPerformed'] = implode( '. ', $steps ) . '.';
	}

	$preparation = wp_strip_all_tags( (string) get_post_meta( $post_id, '_ml_treatment_pre', true ) );
	if ( $preparation ) {
		$node['preparation'] = $preparation;
	}

	$followup = wp_strip_all_tags( (string) get_post_meta( $post_id, '_ml_treatment_post', true ) );
	if ( $followup ) {
		$node['followup'] = $followup;
	}

	$webpage = array(
		'@type'        => 'MedicalWebPage',
		'@id'          => $permalink . '#webpage',
		'url'          => $permalink,
		'name'         => $title,
		'description'  => wp_strip_all_tags( $description ),
		'isPartOf'     => array( '@id' => home_url( '/#website' ) ),
		'about'        => array( '@id' => $permalink . '#treatment' ),
		'audience'     => array( '@type' => 'Patient' ),
		'inLanguage'   => get_bloginfo( 'language' ),
		'lastReviewed' => get_the_modified_date( DATE_W3C, $post_id ),
		'reviewedBy'   => array( '@id' => home_url( '/#doctor' ) ),
	);

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => array( $node, $webpage ),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * FAQ pairs stored in a treatment's meta field.
 *
 * @param int $post_id Treatment ID.
 * @return array
 */
function ml_treatment_faq_pairs( $post_id ) {
	$raw   = (string) get_post_meta( $post_id, '_ml_treatment_faqs', true );
	$pairs = array();

	foreach ( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) as $line ) {
		$parts = explode( '||', $line, 2 );

		if ( 2 !== count( $parts ) ) {
			continue;
		}

		$question = trim( $parts[0] );
		$answer   = trim( $parts[1] );

		if ( $question && $answer ) {
			$pairs[] = array(
				'q' => $question,
				'a' => $answer,
			);
		}
	}

	return $pairs;
}

/**
 * FAQ pairs from published FAQ entries.
 *
 * @param int $limit Number of entries, or -1 for all.
 * @return array
 */
function ml_faq_pairs_from_posts( $limit = 6 ) {
	$posts = get_posts(
		array(
			'post_type'      => 'ml_faq',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);

	$pairs = array();

	foreach ( $posts as $faq ) {
		$question = trim( wp_strip_all_tags( get_the_title( $faq ) ) );
		$answer   = trim( wp_strip_all_tags( strip_shortcodes( $faq->post_content ) ) );

		if ( ! $question || ! $answer ) {
			continue;
		}

		$pairs[] = array(
			'q' => $question,
			'a' => $answer,
		);
	}

	return $pairs;
}

/**
 * FAQPage schema for visible FAQ content.
 */
function ml_faq_schema() {
	if ( ! ml_seo_enabled() ) {
		return;
	}

	$faqs = array();

	if ( is_singular( 'ml_treatment' ) ) {
		$faqs = ml_treatment_faq_pairs( get_queried_object_id() );
	} elseif ( is_front_page() ) {
		$faqs = ml_faq_pairs_from_posts( 6 );
	} elseif ( is_page_template( 'page-templates/page-mira-bhayander.php' ) ) {
		$faqs = ml_faq_pairs_from_posts( -1 );
	} elseif ( is_page_template( 'page-templates/page-faq.php' ) ) {
		$faqs = ml_faq_pairs_from_posts( -1 );
	}

	if ( ! $faqs ) {
		return;
	}

	$entities = array();

	foreach ( $faqs as $faq ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'@id'        => ml_seo_current_url() . '#faq',
		'mainEntity' => $entities,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Physician node for the doctor.
 *
 * @param bool $full Include image, biography and URL.
 * @return array
 */
function ml_physician_node( $full = true ) {
	$node = array(
		'@type'             => 'Physician',
		'@id'               => home_url( '/#doctor' ),
		'name'              => ml_doctor_name(),
		'jobTitle'          => ml_doctor_qualifications(),
		'worksFor'          => array( '@id' => home_url( '/#clinic' ) ),
		'medicalSpecialty'  => 'Dermatology',
	);

	if ( $full ) {
		$node['url'] = ml_page_url_by_template( 'page-templates/page-about.php', home_url( '/about/' ) );

		$bio = wp_strip_all_tags( ml_doctor_bio() );
		if ( $bio ) {
			$node['description'] = wp_trim_words( $bio, 45, '…' );
		}

		$image_id = ml_doctor_image_id();
		if ( ml_attachment_is_deployed( $image_id ) ) {
			$src = wp_get_attachment_image_src( $image_id, 'ml-portrait' );
			if ( $src ) {
				$node['image'] = $src[0];
			}
		}
	}

	return array_filter( $node );
}

/**
 * Physician schema on the About page.
 */
function ml_physician_schema() {
	if ( ! ml_seo_enabled() || ! is_page_template( 'page-templates/page-about.php' ) ) {
		return;
	}

	$schema = array_merge(
		array( '@context' => 'https://schema.org' ),
		ml_physician_node()
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * BlogPosting schema for single posts.
 *
 * @return void
 */
function ml_article_schema() {
	if ( ml_has_seo_plugin() || ! is_singular( 'post' ) ) {
		return;
	}

	$post_id   = get_queried_object_id();
	$permalink = get_permalink( $post_id );
	$excerpt   = get_the_excerpt( $post_id );
	$author_id = (int) get_post_field( 'post_author', $post_id );
	$publisher = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#clinic' ),
		'name'  => ml_seo_brand_name(),
		'url'   => home_url( '/' ),
	);
	$logo_id   = (int) get_theme_mod( 'custom_logo' );
	$logo_url  = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : ml_default_logo_url();

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
		'lastReviewed'     => get_the_modified_date( DATE_W3C, $post_id ),
		'inLanguage'       => get_bloginfo( 'language' ),
		'author'           => array(
			'@type' => 'Organization',
			'name'  => wp_specialchars_decode( get_the_author_meta( 'display_name', $author_id ), ENT_QUOTES ),
			'url'   => get_author_posts_url( $author_id ),
		),
		'reviewedBy'       => ml_physician_node( false ),
		'publisher'        => $publisher,
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $permalink,
		),
		'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
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

	$content = (string) get_post_field( 'post_content', $post_id );
	if ( $content ) {
		$schema['wordCount'] = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* -----------------------------------------------------------------
 * robots.txt and sitemaps
 * ---------------------------------------------------------------- */

/**
 * Robots rules with a sitemap reference.
 *
 * @param string $output Default robots.txt content.
 * @param bool   $public Whether the site is public.
 * @return string
 */
function ml_robots_txt( $output, $public ) {
	if ( ! ml_seo_enabled() || ! $public ) {
		return $output;
	}

	$path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$base = ( '' === $path || '/' === $path ) ? '' : untrailingslashit( $path );

	$lines = array(
		'User-agent: *',
		'Disallow: ' . $base . '/wp-admin/',
		'Allow: ' . $base . '/wp-admin/admin-ajax.php',
		'Disallow: ' . $base . '/wp-login.php',
		'Disallow: ' . $base . '/search/',
		'Disallow: ' . $base . '/?s=',
		'Disallow: ' . $base . '/feed/',
		'',
		'Sitemap: ' . home_url( '/wp-sitemap.xml' ),
		'',
	);

	return implode( "\n", $lines );
}

/**
 * Noindex thin FAQ singles; keep the FAQ page itself indexable.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function ml_wp_robots( $robots ) {
	if ( is_singular( 'ml_faq' ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}

	return $robots;
}

/**
 * Keep thin FAQ entries out of the XML sitemap.
 *
 * @param array $post_types Sitemap post types.
 * @return array
 */
function ml_sitemaps_post_types( $post_types ) {
	unset( $post_types['ml_faq'] );

	return $post_types;
}

/**
 * Keep empty FAQ topic archives out of the XML sitemap.
 *
 * @param array $taxonomies Sitemap taxonomies.
 * @return array
 */
function ml_sitemaps_taxonomies( $taxonomies ) {
	unset( $taxonomies['faq_topic'] );

	return $taxonomies;
}
