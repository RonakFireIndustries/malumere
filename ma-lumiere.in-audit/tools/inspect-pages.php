<?php
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';
$out = array();
$posts = get_posts( array( 'post_type' => 'page', 'posts_per_page' => -1, 'post_status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'ID', 'order' => 'ASC' ) );
foreach ( $posts as $p ) {
	$out[] = array(
		'ID' => $p->ID,
		'title' => $p->post_title,
		'slug' => $p->post_name,
		'template' => get_post_meta( $p->ID, '_wp_page_template', true ),
		'seo_title' => get_post_meta( $p->ID, '_ml_seo_title', true ),
		'seo_description' => get_post_meta( $p->ID, '_ml_seo_description', true ),
		'url' => get_permalink( $p->ID ),
	);
}
echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";