<?php
/**
 * Read-only inspection helper for Ma Lumière SEO implementation.
 * Run: C:\xampp\php\php.exe tools\inspect-db.php
 */
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

global $wpdb;

$needles = array(
	'CONSENT-APPROVED CASE REQUIRED',
	'BEFORE/AFTER IMAGES REQUIRED',
	'[TREATMENT IMAGE]',
	'[IMAGE REQUIRED]',
	'Botox',
	'botox',
	'dermatolog',
);

$rows = $wpdb->get_results(
	"SELECT ID, post_type, post_name, post_title, post_status
	 FROM {$wpdb->posts}
	 WHERE post_status NOT IN ('trash','auto-draft')
	 ORDER BY post_type, ID"
);

$report = array();
foreach ( $rows as $row ) {
	$content = (string) get_post_field( 'post_content', $row->ID );
	$excerpt = (string) get_post_field( 'post_excerpt', $row->ID );
	$meta    = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $row->ID ) );
	$blob    = $content . "\n" . $excerpt . "\n" . implode( "\n", array_map( 'strval', $meta ) );
	$hits    = array();
	foreach ( $needles as $needle ) {
		if ( false !== stripos( $blob, $needle ) ) {
			$hits[] = $needle;
		}
	}
	if ( $hits ) {
		$report[] = array(
			'ID'        => (int) $row->ID,
			'type'      => $row->post_type,
			'slug'      => $row->post_name,
			'title'     => $row->post_title,
			'status'    => $row->post_status,
			'hits'      => $hits,
			'permalink' => get_permalink( $row->ID ),
		);
	}
}

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
