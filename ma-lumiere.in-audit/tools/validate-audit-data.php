<?php
$f = __DIR__ . '/../audit-data.json';
$data = json_decode( (string) file_get_contents( $f ), true );

if ( JSON_ERROR_NONE !== json_last_error() ) {
	echo 'JSON_INVALID: ' . json_last_error_msg() . "\n";
	exit( 1 );
}

$summary = isset( $data['summary'] ) ? $data['summary'] : array();
$impl    = isset( $data['implementation'] ) ? $data['implementation'] : array();

printf(
	"JSON_VALID health=%d estimate_health=%d estimate_local=%d implementation=%s\n",
	isset( $summary['health_score'] ) ? $summary['health_score'] : -1,
	isset( $summary['current_estimate_health_score'] ) ? $summary['current_estimate_health_score'] : -1,
	isset( $summary['current_estimate_local_seo_score'] ) ? $summary['current_estimate_local_seo_score'] : -1,
	$impl ? 'present' : 'missing'
);
