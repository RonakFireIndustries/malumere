<?php
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

$output_file = '';
foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( 0 === strpos( $arg, '--output=' ) ) {
		$output_file = substr( $arg, 9 );
	}
}

$urls = array(
	home_url( '/' ),
	home_url( '/treatment/acne-acne-scars/' ),
	home_url( '/treatment/injectables-facial-contouring/' ),
	home_url( '/skin-clinic-mira-bhayander/' ),
);
$out = array();
foreach ( $urls as $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 20 ) );
	if ( is_wp_error( $res ) ) {
		$out[] = array( 'url' => $url, 'status' => 'ERROR', 'message' => $res->get_error_message() );
		continue;
	}
	$html = wp_remote_retrieve_body( $res );
	preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches );
	$types = array();
	$errors = array();
	foreach ( $matches[1] as $json ) {
		$data = json_decode( html_entity_decode( $json, ENT_QUOTES, 'UTF-8' ), true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$errors[] = json_last_error_msg();
			continue;
		}
		$type = isset( $data['@type'] ) ? $data['@type'] : 'missing';
		$types[] = is_array( $type ) ? implode( '+', $type ) : $type;
	}
	$out[] = array(
		'url' => $url,
		'status' => $errors ? 'INVALID' : ( $types ? 'VALID' : 'NONE' ),
		'blocks' => count( $matches[1] ),
		'types' => $types,
		'errors' => $errors,
	);
}
$json = wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
if ( $output_file ) {
	file_put_contents( $output_file, $json, LOCK_EX );
	echo 'JSONLD_VERIFICATION_SAVED ' . basename( $output_file ) . ' bytes=' . strlen( $json ) . "\n";
} else {
	echo $json;
}