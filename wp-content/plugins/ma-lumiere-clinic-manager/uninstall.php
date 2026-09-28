<?php
/**
 * Uninstaller.
 *
 * Deactivation NEVER touches data. Uninstall is deliberate and protected:
 * clinic data is only permanently removed when both conditions hold:
 *
 *   1. The plugin is being uninstalled via the WordPress uninstall flow, AND
 *   2. The setting `privacy.delete_on_uninstall` is enabled.
 *
 * Roles and capabilities are always cleaned up on uninstall (they are
 * plugin configuration, not clinic records).
 *
 * @package MaLumiere\Clinic
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Load plugin internals so uninstall can reuse its classes.
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/class-capabilities.php';
require_once __DIR__ . '/includes/class-database.php';
require_once __DIR__ . '/includes/class-roles.php';
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-security.php';
require_once __DIR__ . '/includes/class-audit-log.php';
require_once __DIR__ . '/includes/class-email-service.php';
require_once __DIR__ . '/includes/class-private-file-manager.php';
require_once __DIR__ . '/database/schema.php';

// Always remove plugin-specific roles/capabilities.
ML_Roles::remove();

// Remove plugin options that are not clinic data (version/private dir meta).
delete_option( 'ml_clinic_db_version' );
delete_option( 'ml_clinic_table_versions' );
delete_option( 'ml_clinic_installed_on' );

// Only remove clinic DATA tables + settings when explicitly enabled.
if ( ML_Settings::delete_on_uninstall() ) {
	ML_Database::drop_all_tables();
	delete_option( 'ml_clinic_settings' );
	delete_option( 'ml_clinic_private_dir' );
} else {
	// Keep the settings option so a future reinstall can resume cleanly.
	// The schema stays intact because drop_all_tables() was not called.
}