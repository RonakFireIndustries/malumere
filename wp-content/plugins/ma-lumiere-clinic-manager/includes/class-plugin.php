<?php
/**
 * Central plugin orchestrator.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Plugin {

	/**
	 * Registered hook state.
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * Boot the plugin lifecycle.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->registered ) {
			return;
		}
		$this->registered = true;

		$this->maybe_upgrade_database();

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'init_services' ) );
		add_action( 'init', array( 'ML_Booking_Shortcode', 'init' ), 20 );
		add_action( 'init', array( 'ML_Portal', 'init' ), 25 );
		ML_Reports::init();
		add_action( 'admin_menu', array( 'ML_Admin_Menu', 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'rest_api_init', array( 'ML_Rest_Api', 'register_routes' ) );
		ML_Ajax::register();
		ML_Roles::maybe_sync();
		ML_Sample_Data::register();
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ma-lumiere-clinic', false, dirname( ML_CLINIC_BASENAME ) . '/languages' );
	}

	/**
	 * Instantiate runtime services. Kept lazy so nothing heavy runs
	 * on every request unless a service is actually needed.
	 *
	 * @return void
	 */
	public function init_services() {
		ML_Settings::init();
	}

	/**
	 * Run schema migrations when the stored DB version is behind.
	 *
	 * @return void
	 */
	private function maybe_upgrade_database() {
		ML_Database::maybe_upgrade();
	}

	/**
	 * Enqueue admin assets for clinic pages only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 *
	 * @return void
	 */
	public function admin_assets( $hook ) {
		if ( ! ml_is_clinic_screen( $hook ) ) {
			return;
		}
		wp_enqueue_style( 'ml-clinic-admin', ML_CLINIC_URL . 'admin/css/admin.css', array(), ML_CLINIC_VERSION );
		wp_enqueue_script( 'ml-clinic-admin', ML_CLINIC_URL . 'admin/js/admin.js', array( 'jquery' ), ML_CLINIC_VERSION, true );
		wp_localize_script(
			'ml-clinic-admin',
			'MLClinicAdmin',
			array(
				'ajaxUrl'  => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'restUrl'  => esc_url_raw( rest_url( 'ml-clinic/v1/' ) ),
				'nonce'    => ML_Security::nonce(),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}