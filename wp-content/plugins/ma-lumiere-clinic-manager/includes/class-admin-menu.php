<?php
/**
 * Admin menu builder.
 *
 * Each submenu is capability-gated up front (the submenu is not added for
 * users who cannot see it). Additional runtime checks happen in views.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Admin_Menu {

	/**
	 * Top-level menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'ml-clinic-dashboard';

	/**
	 * Register menus + submenus.
	 *
	 * @return void
	 */
	public static function register_menus() {
		if ( ! ml_user_has_clinic_access() ) {
			return;
		}

		add_menu_page(
			__( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ),
			__( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ),
			'read',
			self::MENU_SLUG,
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-heart',
			3
		);

		self::add_submenu( __( 'Patients', 'ma-lumiere-clinic' ), 'ml-clinic-patients', 'patients', 'ml_view_patients' );
		self::add_submenu( __( 'Appointments', 'ma-lumiere-clinic' ), 'ml-clinic-appointments', 'appointments', 'ml_view_appointments' );
		self::add_submenu( __( 'Visits', 'ma-lumiere-clinic' ), 'ml-clinic-visits', 'visits', 'ml_view_visits' );
		self::add_submenu( __( 'Prescriptions', 'ma-lumiere-clinic' ), 'ml-clinic-prescriptions', 'prescriptions', 'ml_view_prescriptions' );
		self::add_submenu( __( 'Inventory', 'ma-lumiere-clinic' ), 'ml-clinic-inventory', 'inventory', 'ml_view_inventory' );
		self::add_submenu( __( 'Follow-ups', 'ma-lumiere-clinic' ), 'ml-clinic-followups', 'followups', 'ml_view_followups' );
		self::add_submenu( __( 'Treatments', 'ma-lumiere-clinic' ), 'ml-clinic-treatments', 'treatments', 'read' );

		self::add_submenu( __( 'Billing', 'ma-lumiere-clinic' ), 'ml-clinic-billing', 'billing', 'ml_view_billing' );
		self::add_submenu( __( 'Payments', 'ma-lumiere-clinic' ), 'ml-clinic-payments', 'payments', 'ml_view_payments' );
		self::add_submenu( __( 'Reports', 'ma-lumiere-clinic' ), 'ml-clinic-reports', 'reports', 'ml_view_reports' );
		self::add_submenu( __( 'Patient Photos', 'ma-lumiere-clinic' ), 'ml-clinic-photos', 'photos', 'ml_view_patient_photos' );
		self::add_submenu( __( 'Audit Logs', 'ma-lumiere-clinic' ), 'ml-clinic-audit', 'audit', 'ml_view_audit_logs' );
		self::add_submenu( __( 'Settings', 'ma-lumiere-clinic' ), 'ml-clinic-settings', 'settings', 'ml_manage_settings' );
	}

	/**
	 * Register a capability-gated submenu.
	 *
	 * @param string $title Menu label.
	 * @param string $slug  Page slug.
	 * @param string $view  View key used by the switch renderer.
	 * @param string $cap   Required capability.
	 *
	 * @return void
	 */
	private static function add_submenu( $title, $slug, $view, $cap ) {
		if ( ! current_user_can( $cap ) ) {
			return;
		}
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ) . ' — ' . $title,
			$title,
			$cap,
			$slug,
			array( __CLASS__, 'render_view_' . $view )
		);
	}

	/**
	 * Renderers. Patients is fully implemented by the controller; the rest
	 * remain foundational shells for later phases.
	 */
	public static function render_dashboard() {
		ML_Admin_Menu::require_view( 'dashboard' );
	}

	public static function render_view_patients() {
		ML_Patient_Controller::handle();
	}

	public static function render_view_appointments() {
		ML_Admin_Menu::require_view( 'appointments' );
	}

	public static function render_view_visits() {
		ML_Admin_Menu::require_view( 'visits' );
	}

	public static function render_view_prescriptions() {
		ML_Admin_Menu::require_view( 'prescriptions' );
	}

	public static function render_view_inventory() {
		ML_Inventory_Controller::handle();
	}

	public static function render_view_followups() {
		ML_Admin_Menu::require_view( 'followups' );
	}

	public static function render_view_treatments() {
		ML_Admin_Menu::render_shell( __( 'Treatments', 'ma-lumiere-clinic' ) );
	}

	public static function render_view_billing() {
		ML_Admin_Menu::require_view( 'billing' );
	}

	public static function render_view_payments() {
		ML_Admin_Menu::require_view( 'payments' );
	}

	public static function render_view_reports() {
		ML_Admin_Menu::require_view( 'reports' );
	}

	public static function render_view_photos() {
		ML_Admin_Menu::require_view( 'photos' );
	}

	public static function render_view_audit() {
		ML_Admin_Menu::render_audit();
	}

	public static function render_view_settings() {
		ML_Admin_Menu::require_view( 'settings' );
	}

	/**
	 * Render a full page view file from admin/views/.
	 *
	 * @param string $name View name.
	 *
	 * @return void
	 */
	public static function require_view( $name ) {
		$file = ML_CLINIC_PATH . 'admin/views/' . $name . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	/**
	 * Generic shell for menu items with no dedicated view yet.
	 *
	 * @param string $title Page title.
	 *
	 * @return void
	 */
	public static function render_shell( $title ) {
		$title = esc_html( $title );
		include ML_CLINIC_PATH . 'admin/views/_shell.php';
	}

	/**
	 * Audit log listing view (read-only, respects ml_view_audit_logs).
	 *
	 * @return void
	 */
	public static function render_audit() {
		include ML_CLINIC_PATH . 'admin/views/_audit.php';
	}
}