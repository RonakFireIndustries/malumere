<?php
/**
 * Clinic capability registry. Single source of truth for every
 * clinic-specific capability used across the plugin.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Capabilities {

	/**
	 * All clinic capabilities, grouped by domain for readability.
	 *
	 * @return array<string,array<string>>
	 */
	public static function all() {
		return array(
			'core'          => array(
				'ml_manage_clinic',
			),
			'patients'      => array(
				'ml_view_patients',
				'ml_create_patients',
				'ml_edit_patients',
				'ml_view_medical_records',
			),
			'visits'        => array(
				'ml_view_visits',
				'ml_manage_visits',
			),
			'prescriptions' => array(
				'ml_view_prescriptions',
				'ml_manage_prescriptions',
			),
			'appointments'  => array(
				'ml_view_appointments',
				'ml_manage_appointments',
			),
			'followups'     => array(
				'ml_view_followups',
				'ml_manage_followups',
			),
			'photos'        => array(
				'ml_view_patient_photos',
				'ml_manage_patient_photos',
			),
			'billing'       => array(
				'ml_view_billing',
				'ml_manage_billing',
			),
			'inventory'     => array(
				'ml_view_inventory',
				'ml_manage_inventory',
			),
			'payments'      => array(
				'ml_view_payments',
				'ml_record_payments',
			),
			'reports'       => array(
				'ml_view_reports',
				'ml_manage_reports',
			),
			'system'        => array(
				'ml_manage_settings',
				'ml_view_audit_logs',
				'ml_manage_users',
			),
		);
	}

	/**
	 * Flat list of all capabilities.
	 *
	 * @return array<string>
	 */
	public static function flat_list() {
		$flat = array();
		foreach ( self::all() as $group ) {
			$flat = array_merge( $flat, $group );
		}
		return array_unique( $flat );
	}

	/**
	 * Capabilities granted to the SuperAdmin role.
	 *
	 * @return array<string>
	 */
	public static function superadmin() {
		return self::flat_list();
	}

	/**
	 * Capabilities granted to the Doctor role.
	 *
	 * No settings, user management, audit administration or financial
	 * management unless explicitly granted later.
	 *
	 * @return array<string>
	 */
	public static function doctor() {
		$allow = array(
			'ml_view_patients',
			'ml_create_patients',
			'ml_edit_patients',
			'ml_view_medical_records',
			'ml_view_visits',
			'ml_manage_visits',
			'ml_view_prescriptions',
			'ml_manage_prescriptions',
			'ml_view_appointments',
			'ml_view_followups',
			'ml_manage_followups',
			'ml_view_patient_photos',
			'ml_manage_patient_photos',
			'ml_view_inventory',
			'ml_manage_inventory',
			'ml_view_billing',
			'ml_view_reports',
		);
		return array_values( $allow );
	}

	/**
	 * Capabilities granted to the Receptionist role.
	 *
	 * No clinical data, no diagnosis, no prescriptions, no private photos,
	 * no settings/audit administration.
	 *
	 * @return array<string>
	 */
	public static function receptionist() {
		$allow = array(
			'ml_view_patients',
			'ml_create_patients',
			'ml_edit_patients',
			'ml_view_appointments',
			'ml_manage_appointments',
			'ml_view_inventory',
			'ml_manage_inventory',
			'ml_view_billing',
			'ml_manage_billing',
			'ml_view_payments',
			'ml_record_payments',
			'ml_view_reports',
		);
		return array_values( $allow );
	}

	/**
	 * Capabilities granted to the Patient role. The patient never receives
	 * WordPress administrator access; these only unlock self-service portal
	 * actions scoped to the patient's own record.
	 *
	 * @return array<string>
	 */
	public static function patient() {
		$allow = array(
			'ml_access_portal',
			'ml_view_own_profile',
			'ml_view_own_appointments',
			'ml_view_own_prescriptions',
			'ml_view_own_invoices',
			'ml_view_own_payments',
			'ml_view_own_followups',
			'ml_view_own_treatments',
		);
		return array_values( $allow );
	}

	/**
	 * Mapping of clinic role slugs to their capability source.
	 *
	 * @return array<string,string>
	 */
	public static function role_map() {
		return array(
			'ml_superadmin'   => 'superadmin',
			'ml_doctor'       => 'doctor',
			'ml_receptionist' => 'receptionist',
			'ml_patient'      => 'patient',
		);
	}
}