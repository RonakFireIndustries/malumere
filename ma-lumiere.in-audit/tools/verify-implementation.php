<?php
/**
 * Verification helper for implemented Phase 1-3 changes.
 */
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

$output_file = '';
foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( 0 === strpos( $arg, '--output=' ) ) {
		$output_file = substr( $arg, 9 );
	}
}

$out = array(
	'checks' => array(),
);

function add_check( &$out, $name, $pass, $detail = '' ) {
	$out['checks'][] = array( 'name' => $name, 'status' => $pass ? 'PASS' : 'FAIL', 'detail' => $detail );
}

$address = ml_clinic_address();
$phone = ml_clinic_phone();
add_check( $out, 'NAP_PIN_401105', false !== strpos( $address, '401105' ) && false === strpos( $address, '401107' ), $address );
add_check( $out, 'PHONE_PLUS_91', '+' === substr( trim( $phone ), 0, 1 ), $phone );
add_check( $out, 'PHONE_HREF', '+919987802355' === ml_clinic_phone_href(), ml_clinic_phone_href() );
add_check( $out, 'DIRECTIONS_LINK', 0 === strpos( ml_clinic_directions_url(), 'https://www.google.com/maps/dir/?api=1' ), ml_clinic_directions_url() );

global $wpdb;
$needles = array( 'CONSENT-APPROVED CASE REQUIRED', 'BEFORE/AFTER IMAGES REQUIRED', '[IMAGE REQUIRED]', '[TREATMENT IMAGE]', '401107' );
$placeholder_hits = array();
$rows = $wpdb->get_results( "SELECT ID, post_type, post_name, post_title, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_status NOT IN ('trash','auto-draft')" );
foreach ( $rows as $r ) {
	$blob = $r->post_title . "\n" . $r->post_content . "\n" . $r->post_excerpt;
	foreach ( $needles as $n ) {
		if ( false !== stripos( $blob, $n ) ) {
			$placeholder_hits[] = array( 'id' => (int) $r->ID, 'needle' => $n );
		}
	}
}
add_check( $out, 'DB_PLACEHOLDERS_REMOVED', ! $placeholder_hits, wp_json_encode( $placeholder_hits ) );

$term = get_term_by( 'slug', 'injectables-facial-contouring', 'treatment_category' );
add_check( $out, 'BOTOX_TERM_LABEL', $term && 'Botox & Injectables' === wp_specialchars_decode( $term->name, ENT_QUOTES ), $term ? $term->name : 'missing' );
$botox = get_post( 53 );
add_check( $out, 'BOTOX_PILLAR_UPDATED', $botox && false !== strpos( $botox->post_title, 'Botox' ), $botox ? $botox->post_title : 'missing' );

$required_meta = array( 6, 7, 8, 9, 10, 11, 12, 43, 47, 53, 39, 109 );
$meta_missing = array();
foreach ( $required_meta as $id ) {
	if ( ! get_post_meta( $id, '_ml_seo_title', true ) || ! get_post_meta( $id, '_ml_seo_description', true ) ) {
		$meta_missing[] = $id;
	}
}
add_check( $out, 'KEY_PAGE_META_PRESENT', ! $meta_missing, 'missing: ' . implode( ',', $meta_missing ) );

$sitemap = get_transient( 'ml_verify_sitemap' );
if ( ! $sitemap ) {
	$sitemap = wp_remote_get( home_url( '/sitemap.xml' ), array( 'timeout' => 20 ) );
	set_transient( 'ml_verify_sitemap', $sitemap, 60 );
}
if ( ! is_wp_error( $sitemap ) ) {
	$body = wp_remote_retrieve_body( $sitemap );
	$count = substr_count( $body, '<loc>' );
	add_check( $out, 'SITEMAP_EXCLUDES_NOINDEX_TERMS', false === strpos( $body, '/treatment-type/' ), 'urls=' . $count );
}

$fails = 0;
foreach ( $out['checks'] as $c ) {
	if ( 'FAIL' === $c['status'] ) {
		$fails++;
	}
}
$out['summary'] = array( 'total' => count( $out['checks'] ), 'passed' => count( $out['checks'] ) - $fails, 'failed' => $fails );

$json = wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
if ( $output_file ) {
	file_put_contents( $output_file, $json, LOCK_EX );
	echo 'IMPLEMENTATION_VERIFICATION_SAVED ' . basename( $output_file ) . ' bytes=' . strlen( $json ) . "\n";
} else {
	echo $json;
}