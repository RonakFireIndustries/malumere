<?php
/**
 * Sitemap and robots.txt.
 *
 * Serves /sitemap.xml from the current site content and keeps /robots.txt in
 * sync with it. URLs are always emitted for the production origin so a local
 * or staging install generates exactly the same files as the live site.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ML_SITE_ORIGIN' ) ) {
	define( 'ML_SITE_ORIGIN', 'https://ma-lumiere.in' );
}

add_action( 'init', 'ml_sitemap_register' );
add_filter( 'query_vars', 'ml_sitemap_query_vars' );
add_action( 'template_redirect', 'ml_sitemap_render' );
add_filter( 'robots_txt', 'ml_robots_txt', 10, 2 );
add_filter( 'wp_sitemaps_enabled', '__return_false' ); // This theme owns the sitemap.
add_action( 'wp_head', 'ml_sitemap_head_link', 3 );

/**
 * Register the sitemap route and flush rules once after activation.
 */
function ml_sitemap_register() {
	add_rewrite_rule( '^sitemap\.xml$', 'index.php?ml_sitemap=1', 'top' );
	// WordPress only adds its virtual robots.txt rule for root installs.
	add_rewrite_rule( '^robots\.txt$', 'index.php?ml_robots=1', 'top' );

	if ( ! get_option( 'ml_sitemap_rewrite_flushed_v2' ) ) {
		flush_rewrite_rules( false );
		update_option( 'ml_sitemap_rewrite_flushed_v2', 1 );
	}
}

/**
 * Allow the sitemap query var through.
 *
 * @param array $vars Public query vars.
 * @return array
 */
function ml_sitemap_query_vars( $vars ) {
	$vars[] = 'ml_sitemap';
	$vars[] = 'ml_robots';
	return $vars;
}

/**
 * Convert a local permalink (or site-relative path) into a production URL.
 *
 * @param string $url Absolute permalink or a path starting with '/'.
 * @return string
 */
function ml_sitemap_url( $url = '' ) {
	$origin = trailingslashit( apply_filters( 'ml_site_origin', ML_SITE_ORIGIN ) );

	if ( ! $url || '/' === $url ) {
		return $origin;
	}

	if ( 0 === strpos( $url, '/' ) ) {
		return $origin . ltrim( $url, '/' );
	}

	$home = trailingslashit( home_url( '/' ) );
	if ( 0 === strpos( $url, $home ) ) {
		return $origin . ltrim( substr( $url, strlen( $home ) ), '/' );
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );

	return $origin . ltrim( (string) $path, '/' );
}

/**
 * Stream the XML sitemap or robots.txt instead of a template.
 */
function ml_sitemap_render() {
	if ( get_query_var( 'ml_robots' ) ) {
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
		header( 'X-Content-Type-Options: nosniff' );
		echo apply_filters( 'robots_txt', '', get_option( 'blog_public' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	if ( ! get_query_var( 'ml_sitemap' ) ) {
		return;
	}

	status_header( 200 );
	header( 'Content-Type: application/xml; charset=' . get_option( 'blog_charset' ) );
	header( 'X-Content-Type-Options: nosniff' );

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	ml_sitemap_entry( ml_sitemap_url( home_url( '/' ) ), ml_sitemap_latest_modified(), 'daily', '1.0' );

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);
	foreach ( $pages as $page ) {
		// The front page is already listed above; Sample Page is a WP placeholder.
		if ( (int) get_option( 'page_on_front' ) === (int) $page->ID || 'sample-page' === $page->post_name ) {
			continue;
		}
		ml_sitemap_entry(
			ml_sitemap_url( get_permalink( $page ) ),
			ml_sitemap_modified( $page ),
			'weekly',
			in_array( $page->post_name, array( 'book-consultation', 'contact', 'treatments' ), true ) ? '0.8' : '0.6'
		);
	}

	$collections = array(
		'ml_treatment'    => array( 'monthly', '0.7' ),
		'ml_before_after' => array( 'monthly', '0.5' ),
		'post'            => array( 'monthly', '0.6' ),
	);
	foreach ( $collections as $post_type => $settings ) {
		$items = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		foreach ( $items as $item ) {
			ml_sitemap_entry( ml_sitemap_url( get_permalink( $item ) ), ml_sitemap_modified( $item ), $settings[0], $settings[1] );
		}
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'treatment_category',
			'hide_empty' => true,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				ml_sitemap_entry( ml_sitemap_url( $link ), '', 'weekly', '0.5' );
			}
		}
	}

	echo '</urlset>' . "\n";
	exit;
}

/**
 * Print one <url> node.
 *
 * @param string $url        Absolute URL.
 * @param string $lastmod    Last modified date (Y-m-d) or empty.
 * @param string $changefreq Hint for crawlers.
 * @param string $priority   Relative priority.
 */
function ml_sitemap_entry( $url, $lastmod = '', $changefreq = 'weekly', $priority = '0.5' ) {
	if ( ! $url ) {
		return;
	}

	echo "  <url>\n";
	echo '    <loc>' . esc_url( $url ) . "</loc>\n";
	if ( $lastmod ) {
		echo '    <lastmod>' . esc_html( $lastmod ) . "</lastmod>\n";
	}
	echo '    <changefreq>' . esc_html( $changefreq ) . "</changefreq>\n";
	echo '    <priority>' . esc_html( $priority ) . "</priority>\n";
	echo "  </url>\n";
}

/**
 * GMT modification date for a post.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function ml_sitemap_modified( $post ) {
	return get_post_modified_time( 'Y-m-d', true, $post );
}

/**
 * Most recent modification date across public content.
 *
 * @return string
 */
function ml_sitemap_latest_modified() {
	$latest = get_posts(
		array(
			'post_type'      => array( 'page', 'post', 'ml_treatment', 'ml_before_after' ),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
	return $latest ? ml_sitemap_modified( $latest[0] ) : '';
}

/**
 * robots.txt rules for crawlers.
 *
 * @param string $output Existing output.
 * @param bool   $public Whether the site is public.
 * @return string
 */
function ml_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return "User-agent: *\nDisallow: /\n";
	}

	$lines = array(
		'User-agent: *',
		'Allow: /',
		'Disallow: /wp-admin/',
		'Allow: /wp-admin/admin-ajax.php',
		'Disallow: /wp-login.php',
		'Disallow: /?s=',
		'Disallow: /search/',
		'Disallow: /zz-probe-test.php',
		'Sitemap: ' . ml_sitemap_url( '/sitemap.xml' ),
	);

	return implode( "\n", $lines ) . "\n";
}

/**
 * Advertise the sitemap in the document head.
 */
function ml_sitemap_head_link() {
	printf(
		'<link rel="sitemap" type="application/xml" title="Sitemap" href="%s" />' . "\n", // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_url( ml_sitemap_url( '/sitemap.xml' ) )
	);
}
