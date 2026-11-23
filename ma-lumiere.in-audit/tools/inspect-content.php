<?php
/**
 * Read-only content inspection for selected Ma Lumière pages.
 */
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

$slugs = array(
	'acne-treatment-case-reference',
	'injectables-facial-contouring',
	'acne-acne-scars',
	'pigmentation-uneven-skin-tone',
	'hair-loss-hair-thinning',
	'laser-hair-reduction',
	'pre-wedding-skin-preparation',
	'skin-clinic-mira-bhayander',
	'faqs',
	'contact',
	'about',
);

$out = array();
foreach ( $slugs as $slug ) {
	$posts = get_posts(
		array(
			'name'        => $slug,
			'post_type'   => 'any',
			'post_status' => array( 'publish', 'draft', 'private' ),
			'numberposts' => 1,
		)
	);
	if ( ! $posts ) {
		$out[ $slug ] = array( 'missing' => true );
		continue;
	}
	$p = $posts[0];
	$meta = get_post_meta( $p->ID );
	$meta_out = array();
	foreach ( $meta as $key => $values ) {
		if ( 0 === strpos( $key, '_ml_' ) || 0 === strpos( $key, '_wp_page_template' ) ) {
			$meta_out[ $key ] = is_array( $values ) ? ( $values[0] ?? '' ) : $values;
		}
	}
	$out[ $slug ] = array(
		'ID'        => $p->ID,
		'type'      => $p->post_type,
		'title'     => $p->post_title,
		'excerpt'   => $p->post_excerpt,
		'content'   => $p->post_content,
		'meta'      => $meta_out,
		'permalink' => get_permalink( $p->ID ),
	);
}

echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
