<?php
/**
 * Deactivation routine. Preserves ALL clinic data.
 *
 * Deactivation only stops the plugin from running. Patients, visits,
 * appointments, prescriptions, photos, billing, payments and audit logs
 * all remain intact and re-activatable.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Deactivator {

	/**
	 * Run deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// No tables are dropped, no options are removed, no roles are deleted.
		// The only action is flushing caches to reflect the state change.
		wp_cache_flush();
	}
}