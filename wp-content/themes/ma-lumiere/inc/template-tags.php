<?php
/**
 * Template tags: post meta, breadcrumbs, rendered IDs helpers.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vague editorial post meta line ("Category · Sep 23, 2026 · 4 min read").
 *
 * @return void
 */
function ml_post_meta() {
	$time = sprintf(
		'<time datetime="%1$s">%2$s</time>',
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() )
	);

	$cats = get_the_category();
	$cat  = $cats ? sprintf(
		'<a href="%1$s" rel="category">%2$s</a>',
		esc_url( get_category_link( $cats[0] ) ),
		esc_html( $cats[0]->name )
	) : '';

	$reading = ml_reading_time( get_the_content() );

	printf(
		'<span class="post-card__meta">%1$s%2$s%3$s</span>',
		$cat ? '<span class="post-card__cat">' . $cat . '</span>' : '',
		$time,
		$reading ? '<span>' . esc_html( $reading ) . ' min read</span>' : ''
	);
}

/**
 * Estimate reading time for a text block.
 *
 * @param string $content Raw content.
 * @return int
 */
function ml_reading_time( $content ) {
	$words = str_word_count( wp_strip_all_tags( $content ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Accessible breadcrumbs with JSON-LD breadcrumb markup.
 *
 * @return void
 */
function ml_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}

	$items   = array();
	$items[] = array(
		'label' => __( 'Home', 'ma-lumiere' ),
		'url'   => home_url( '/' ),
	);

	if ( is_singular( 'ml_treatment' ) ) {
		$terms = get_the_terms( get_the_ID(), 'treatment_category' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$term     = $terms[0];
			$items[]  = array(
				'label' => __( 'Treatments', 'ma-lumiere' ),
				'url'   => ml_page_url_by_template( 'page-templates/page-treatments.php', get_post_type_archive_link( 'ml_treatment' ) ),
			);
			$items[]  = array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
			);
		}
	} elseif ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$items[] = array(
				'label' => __( 'Blog', 'ma-lumiere' ),
				'url'   => get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' ),
			);
			$items[] = array(
				'label' => $cats[0]->name,
				'url'   => get_category_link( $cats[0] ),
			);
		}
	} elseif ( is_post_type_archive( 'ml_treatment' ) || is_tax( 'treatment_category' ) ) {
		$items[] = array(
			'label' => __( 'Treatments', 'ma-lumiere' ),
			'url'   => ml_page_url_by_template( 'page-templates/page-treatments.php', get_post_type_archive_link( 'ml_treatment' ) ),
		);
	}

	$current_label = '';

	if ( is_archive() ) {
		$current_label = wp_strip_all_tags( get_the_archive_title() );
	} elseif ( is_home() ) {
		$current_label = get_option( 'page_for_posts' )
			? get_the_title( get_option( 'page_for_posts' ) )
			: __( 'Blog', 'ma-lumiere' );
	} elseif ( is_search() ) {
		/* translators: %s: search query. */
		$current_label = sprintf( __( 'Search results for “%s”', 'ma-lumiere' ), get_search_query() );
	} elseif ( is_404() ) {
		$current_label = __( 'Page not found', 'ma-lumiere' );
	} else {
		$current_label = wp_strip_all_tags( get_the_title() );
	}

	$current_label = trim( $current_label );

	if ( '' !== $current_label && ( ! $items || end( $items )['label'] !== $current_label ) ) {
		$items[] = array( 'label' => $current_label, 'url' => '' );
	}

	echo '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'ma-lumiere' ) . '" data-reveal="fade">';

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => array(),
	);

	foreach ( $items as $i => $item ) {
		$pos = $i + 1;
		if ( $i > 0 ) {
			echo '<span class="sep" aria-hidden="true">/</span>';
		}
		if ( $item['url'] ) {
			printf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $item['url'] ),
				esc_html( $item['label'] )
			);
		} else {
			printf( '<span class="current" aria-current="page">%1$s</span>', esc_html( $item['label'] ) );
		}

		$schema['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => $pos,
			'name'     => $item['label'],
			'item'     => $item['url'] ? $item['url'] : ( $i + 1 === count( $items ) ? get_permalink() : home_url( '/' ) ),
		);
	}

	echo '</nav>';

	$json = str_replace( array( '&quot;', '&#039;' ), array( '\\"', "'" ), wp_json_encode( $schema ) );
	echo '<script type="application/ld+json">' . $json . '</script>'; // phpcs:ignore
}

/**
 * Terms links for current post.
 *
 * @param string $taxonomy Taxonomy slug.
 * @return void
 */
function ml_get_terms( $taxonomy = 'category' ) {
	$terms = get_the_terms( get_the_ID(), $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}
	$links = array();
	foreach ( $terms as $term ) {
		$links[] = sprintf(
			'<a class="tag-link" href="%1$s">%2$s</a>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name )
		);
	}
	echo implode( ' ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Render an image from an attachment ID with source-size fallbacks, or a
 * placeholder box when there is no real asset yet.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Registered size.
 * @param string $class         Extra CSS class.
 * @param string $flag          Placeholder text when no image.
 * @return void
 */
function ml_image( $attachment_id, $size = 'ml-card', $class = '', $flag = '[IMAGE PENDING]' ) {
	if ( $attachment_id ) {
		echo wp_get_attachment_image( $attachment_id, $size, false, array( 'class' => $class ) );
		return;
	}
	printf(
		'<div class="ph %1$s">%2$s</div>',
		esc_attr( $class ),
		esc_html( $flag )
	);
}