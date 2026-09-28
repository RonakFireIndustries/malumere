<?php
/**
 * API routes bootstrap.
 *
 * Extension point for future phases: register additional REST routes by
 * adding callbacks to the `ml_clinic_rest_routes` filter. Phase 3 skeleton
 * routes are defined in ML_Rest_Api::routes().
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'ml_clinic_rest_routes',
	static function ( array $routes ) {
		// Future phases append here, e.g.:
		// $routes[] = array( 'route' => '/custom', 'method' => 'GET', ... );
		return $routes;
	}
);