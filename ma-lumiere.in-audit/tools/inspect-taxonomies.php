<?php
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

$out = array();
$terms = get_terms( array( 'taxonomy' => 'treatment_category', 'hide_empty' => false ) );
if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $t ) {
		$posts = get_posts( array( 'post_type' => 'ml_treatment', 'posts_per_page' => -1, 'tax_query' => array( array( 'taxonomy' => 'treatment_category', 'field' => 'term_id', 'terms' => $t->term_id ) ), 'fields' => 'ids' ) );
		$out[] = array(
			'term_id' => $t->term_id,
			'name' => $t->name,
			'slug' => $t->slug,
			'count' => $t->count,
			'description' => term_description( $t->term_id, 'treatment_category' ),
			'url' => get_term_link( $t ),
			'treatments' => array_map( function( $id ) { return array( 'id' => $id, 'title' => get_the_title( $id ), 'url' => get_permalink( $id ) ); }, $posts ),
		);
	}
}

$faqs = get_posts( array( 'post_type' => 'ml_faq', 'posts_per_page' => -1, 'post_status' => array( 'publish', 'draft' ) ) );
$faq_out = array();
foreach ( $faqs as $f ) {
	$faq_out[] = array( 'id' => $f->ID, 'title' => $f->post_title, 'content' => $f->post_content );
}

echo wp_json_encode( array( 'treatment_categories' => $out, 'faqs' => $faq_out ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";