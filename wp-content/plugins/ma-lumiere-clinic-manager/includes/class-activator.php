<?php
/**
 * Activation routine. Safe to run repeatedly; never destroys data.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Activator {

	/**
	 * Run activation steps idempotently.
	 *
	 * @return void
	 */
	public static function activate() {
		// 1. Schema (dbDelta, idempotent).
		ML_Database::maybe_upgrade( true );

		// 2. Roles + capabilities.
		ML_Roles::install();

		// 3. Default settings (only stores when option is absent).
		self::seed_defaults();

		// 4. Private storage sandbox.
		ML_Private_File_Manager::private_dir();
		ML_Private_File_Manager::write_blocker_files( ML_Private_File_Manager::private_dir() );

		// 5. Version marker.
		update_option( 'ml_clinic_db_version', ML_CLINIC_DB_VERSION );
		add_option( 'ml_clinic_installed_on', current_time( 'mysql' ) );

		ML_Audit_Log::record( 'settings_updated', 'plugin', 0, __( 'Plugin activated', 'ma-lumiere-clinic' ) );
	}

	/**
	 * Store default settings once.
	 *
	 * @return void
	 */
	private static function seed_defaults() {
		if ( false === get_option( 'ml_clinic_settings', false ) ) {
			update_option( 'ml_clinic_settings', ML_Settings::defaults() );
		}
	}
}