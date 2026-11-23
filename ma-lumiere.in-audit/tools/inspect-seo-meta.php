<?php
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';
global $wpdb;
$rows = $wpdb->get_results(
	"SELECT p.ID, p.post_type, p.post_name, p.post_title, m.meta_key, m.meta_value
	 FROM {$wpdb->postmeta} m
	 JOIN {$wpdb->posts} p ON p.ID = m.post_id
	 WHERE m.meta_key LIKE '_ml_seo_%'
	 ORDER BY p.ID, m.meta_key"
);
$out = array();
foreach ( $rows as $r ) {
	$out[] = array(
		'ID' => (int) $r->ID,
		'type' => $r->post_type,
		'slug' => $r->post_name,
		'title' => $r->post_title,
		'key' => $r->meta_key,
		'value' => $r->meta_value,
	);
}
echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";