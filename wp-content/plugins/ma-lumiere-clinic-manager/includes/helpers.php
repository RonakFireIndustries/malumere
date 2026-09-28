<?php
/**
 * Shared helper functions. All functions are prefixed `ml_` to avoid
 * global collisions.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcut to a prefixed clinic table name.
 *
 * @param string $slug Table slug.
 *
 * @return string
 */
function ml_table( $slug ) {
	return ML_Database::table( $slug );
}

/**
 * Sanitize an arbitrary scalar/string/number input.
 *
 * @param mixed  $value      Raw value.
 * @param string $type       'text'|'email'|'url'|'int'|'float'|'key'|'textarea'|'slug'.
 * @param mixed  $default    Fallback when value is empty.
 *
 * @return mixed
 */
function ml_clean( $value, $type = 'text', $default = null ) {
	if ( is_array( $value ) ) {
		return array_map( static function ( $item ) use ( $type ) {
			return ml_clean( $item, $type );
		}, $value );
	}
	if ( '' === $value || null === $value ) {
		return ( null !== $default ) ? $default : '';
	}
	switch ( $type ) {
		case 'email':
			return sanitize_email( (string) $value );
		case 'url':
			return esc_url_raw( (string) $value );
		case 'int':
			return absint( $value );
		case 'float':
			return max( 0, (float) $value );
		case 'key':
			return sanitize_key( (string) $value );
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		case 'slug':
			return sanitize_title( (string) $value );
		case 'text':
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Format a monetary value with the configured currency symbol.
 *
 * @param float|string $amount Amount.
 *
 * @return string
 */
function ml_money( $amount ) {
	$currency = ML_Settings::get( 'billing.currency', 'INR' );
	$symbols  = array(
		'INR' => '₹',
		'USD' => '$',
		'EUR' => '€',
		'GBP' => '£',
	);
	$symbol   = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency . ' ';
	$value    = number_format( (float) $amount, 2 );
	return $symbol . $value;
}

/**
 * Format a date for display.
 *
 * @param string|int|null $date   MySQL date/datetime or timestamp.
 * @param string          $format PHP date() format.
 *
 * @return string
 */
function ml_date( $date, $format = 'd M Y' ) {
	if ( ! $date ) {
		return '';
	}
	if ( is_numeric( $date ) ) {
		return gmdate( $format, (int) $date );
	}
	$stamp = strtotime( $date );
	return $stamp ? gmdate( $format, $stamp ) : '';
}

/**
 * Strict Y-m-d calendar-date check (rejects "0000-00-00" and rollover
 * dates such as 2024-02-31).
 *
 * @param mixed $date Candidate date.
 *
 * @return bool
 */
function ml_is_date( $date ) {
	if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return false;
	}
	$parts = array_map( 'intval', explode( '-', $date ) );
	return checkdate( $parts[1], $parts[2], $parts[0] );
}

/**
 * Is the current admin screen one of our clinic pages?
 *
 * @param string $hook Current page hook.
 *
 * @return bool
 */
function ml_is_clinic_screen( $hook ) {
	$screen = get_current_screen();
	if ( $screen && false !== strpos( (string) $screen->id, 'ml-clinic' ) ) {
		return true;
	}
	return false !== strpos( (string) $hook, 'ml-clinic' );
}

/**
 * Does the current user have ANY clinic capability?
 *
 * @return bool
 */
function ml_user_has_clinic_access() {
	foreach ( ML_Capabilities::flat_list() as $cap ) {
		if ( current_user_can( $cap ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Record an audit event (thin wrapper for readability).
 *
 * @param string $action       e.g. 'patient_created'.
 * @param string $entity_type  e.g. 'patient'.
 * @param int    $entity_id    Related record id.
 * @param string $description  Short human-readable note.
 *
 * @return void
 */
function ml_audit( $action, $entity_type, $entity_id = 0, $description = '' ) {
	ML_Audit_Log::record( $action, $entity_type, $entity_id, $description );
}