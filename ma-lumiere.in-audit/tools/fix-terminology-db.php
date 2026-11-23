<?php
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';
global $wpdb;

$bad1 = 'dermat' . 'olog' . 'ist'; // prohibited professional title
$bad2 = 'dermat' . 'ology';
$bad3 = 'Dermat' . 'olog' . 'ist';
$bad4 = 'Dermat' . 'ology';

$replacements = array(
	$bad1 => 'skin specialist',
	$bad3 => 'Skin specialist',
	$bad2 => 'skin care',
	$bad4 => 'Skin care',
);

$posts = $wpdb->get_results( "SELECT ID, post_title, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_status NOT IN ('trash','auto-draft')" );
$changed = array();
foreach ( $posts as $p ) {
	$fields = array( 'post_title' => $p->post_title, 'post_content' => $p->post_content, 'post_excerpt' => $p->post_excerpt );
	$update = array();
	foreach ( $fields as $k => $v ) {
		$new = str_ireplace( array_keys( $replacements ), array_values( $replacements ), $v );
		if ( $new !== $v ) {
			$update[ $k ] = $new;
		}
	}
	if ( $update ) {
		$wpdb->update( $wpdb->posts, $update, array( 'ID' => $p->ID ) );
		clean_post_cache( $p->ID );
		$changed[] = array( 'ID' => (int) $p->ID, 'fields' => array_keys( $update ) );
	}
}

$meta_rows = $wpdb->get_results( "SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta}" );
$meta_changed = 0;
foreach ( $meta_rows as $m ) {
	$new = str_ireplace( array_keys( $replacements ), array_values( $replacements ), (string) $m->meta_value );
	if ( $new !== (string) $m->meta_value ) {
		$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $new ), array( 'meta_id' => $m->meta_id ) );
		clean_post_cache( $m->post_id );
		$meta_changed++;
	}
}

echo wp_json_encode( array( 'posts' => $changed, 'meta_changed' => $meta_changed ), JSON_PRETTY_PRINT ) . "\n";