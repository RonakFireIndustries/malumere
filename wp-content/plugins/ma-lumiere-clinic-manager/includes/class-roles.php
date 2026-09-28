<?php
/**
 * Role installer/remover.
 *
 * The default administrator role also receives SuperAdmin clinic
 * capabilities so that existing administrators can manage the clinic
 * without a role swap.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Roles {

	/**
	 * Option storing a fingerprint of the installed capability registry, so
	 * role caps can be re-synced when the registry changes (upgrades).
	 *
	 * @var string
	 */
	const OPTION = 'ml_clinic_caps_fingerprint';

	/**
	 * Re-apply role capabilities whenever the registry fingerprint changes.
	 * Safe to call on every request (one option read).
	 *
	 * @return void
	 */
	public static function maybe_sync() {
		$fingerprint = self::fingerprint();
		if ( get_option( self::OPTION, '' ) === $fingerprint ) {
			return;
		}
		self::install();
		update_option( self::OPTION, $fingerprint );
	}

	/**
	 * Fingerprint of the capability registry.
	 *
	 * @return string
	 */
	private static function fingerprint() {
		$map = ML_Capabilities::role_map();
		$all = array();
		foreach ( $map as $slug => $source ) {
			$all[ $slug ] = ML_Capabilities::$source();
		}
		return md5( wp_json_encode( $all ) );
	}

	/**
	 * Label for each clinic role.
	 *
	 * @return array<string,string>
	 */
	public static function labels() {
		return array(
			'ml_superadmin'   => __( 'Clinic Super Admin', 'ma-lumiere-clinic' ),
			'ml_doctor'       => __( 'Clinic Doctor', 'ma-lumiere-clinic' ),
			'ml_receptionist' => __( 'Clinic Receptionist', 'ma-lumiere-clinic' ),
			'ml_patient'      => __( 'Clinic Patient', 'ma-lumiere-clinic' ),
		);
	}

	/**
	 * Install all clinic roles (idempotent).
	 *
	 * @return void
	 */
	public static function install() {
		$map = ML_Capabilities::role_map();

		foreach ( $map as $slug => $source ) {
			$caps       = ML_Capabilities::$source();
			$role_label = self::labels()[ $slug ];

			$role = get_role( $slug );
			if ( $role instanceof WP_Role ) {
				// Re-sync capabilities in place.
				foreach ( array_keys( $role->capabilities ) as $existing ) {
					if ( 0 === strpos( $existing, 'ml_' ) ) {
						$role->remove_cap( $existing );
					}
				}
				foreach ( $caps as $cap ) {
					$role->add_cap( $cap );
				}
				continue;
			}

			$role = add_role( $slug, $role_label, array_combine( $caps, array_fill( 0, count( $caps ), true ) ) );
			if ( $role instanceof WP_Role ) {
				$role->add_cap( 'read' );
			}
		}

		self::grant_admin_superadmin();
	}

	/**
	 * Grant full clinic access to the built-in administrator role.
	 *
	 * @return void
	 */
	public static function grant_admin_superadmin() {
		$admin = get_role( 'administrator' );
		if ( ! $admin instanceof WP_Role ) {
			return;
		}
		foreach ( ML_Capabilities::superadmin() as $cap ) {
			if ( ! $admin->has_cap( $cap ) ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove clinic capabilities from the administrator role.
	 *
	 * @return void
	 */
	public static function revoke_admin_superadmin() {
		$admin = get_role( 'administrator' );
		if ( ! $admin instanceof WP_Role ) {
			return;
		}
		foreach ( ML_Capabilities::superadmin() as $cap ) {
			$admin->remove_cap( $cap );
		}
	}

	/**
	 * Remove clinic roles entirely. Called by uninstall only.
	 *
	 * @return void
	 */
	public static function remove() {
		foreach ( array_keys( ML_Capabilities::role_map() ) as $slug ) {
			$role = get_role( $slug );
			if ( $role instanceof WP_Role ) {
				remove_role( $slug );
			}
		}
		self::revoke_admin_superadmin();
	}
}