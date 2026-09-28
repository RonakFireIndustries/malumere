<?php
/**
 * Plugin Name:       Ma Lumière Clinic Manager
 * Plugin URI:        https://malumere.example.local/
 * Description:       Private clinic management system for Ma Lumière Dermatology & Aesthetic Clinic. Handles patients, visits, appointments, prescriptions, follow-ups, billing, payments, photos, medicine inventory, reports and audit logging in dedicated database tables.
 * Version:           1.4.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Ma Lumière
 * Author URI:        https://malumere.example.local/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ma-lumiere-clinic
 * Domain Path:       /languages
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

define( 'ML_CLINIC_VERSION', '1.4.0' );
define( 'ML_CLINIC_DB_VERSION', '1.4.0' );
define( 'ML_CLINIC_PLUGIN_FILE', __FILE__ );
define( 'ML_CLINIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'ML_CLINIC_URL', plugin_dir_url( __FILE__ ) );
define( 'ML_CLINIC_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Composer-free autoloader for ML_* classes.
 *
 * @param string $class Class name being autoloaded.
 *
 * @return void
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'ML_';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$relative = strtolower( str_replace( '_', '-', $relative ) );
		$file     = ML_CLINIC_PATH . 'includes/class-' . $relative . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

require_once ML_CLINIC_PATH . 'includes/helpers.php';

register_activation_hook( __FILE__, array( 'ML_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ML_Deactivator', 'deactivate' ) );

/**
 * Boot the plugin.
 *
 * @return ML_Plugin
 */
function ml_clinic() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new ML_Plugin();
		$instance->init();
	}
	return $instance;
}

ml_clinic();