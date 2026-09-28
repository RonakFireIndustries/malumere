<?php
/**
 * Database manager: creates/upgrades the clinic schema with dbDelta()
 * and tracks versions per table + a global DB version option.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Database {

	/**
	 * Option name that stores the schema version for the whole plugin.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'ml_clinic_db_version';

	/**
	 * Option name that stores per-table revisions.
	 *
	 * @var string
	 */
	const TABLE_VERSIONS_OPTION = 'ml_clinic_table_versions';

	/**
	 * Ensure the schema is at ML_CLINIC_DB_VERSION. Safe to call on every
	 * request; dbDelta() is idempotent and never drops data.
	 *
	 * @param bool $force Ignore stored versions and re-run dbDelta (activation path).
	 *
	 * @return void
	 */
	public static function maybe_upgrade( $force = false ) {
		$current = get_option( self::VERSION_OPTION, '' );
		$schema_file = ML_CLINIC_PATH . 'database/schema.php';
		if ( file_exists( $schema_file ) ) {
			require_once $schema_file;
		}

		if ( ! $force && version_compare( ML_CLINIC_DB_VERSION, (string) $current, '<=' ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tables  = ml_schema_definitions();
		$stored  = get_option( self::TABLE_VERSIONS_OPTION, array() );
		$changed = false;

		foreach ( $tables as $slug => $definition ) {
			$needs = $force || empty( $stored[ $slug ] ) || (int) $stored[ $slug ] < (int) $definition['version'];

			if ( ! $needs ) {
				continue;
			}

			$sql = str_replace( '{prefix}', self::prefix(), $definition['schema'] );
			dbDelta( $sql );

			// dbDelta will not demote a UNIQUE index to a plain KEY; revisions
			// share one prescription number, so enforce that explicitly.
			if ( 'prescriptions' === $slug && isset( $tables['prescriptions']['schema'] ) && false === strpos( $tables['prescriptions']['schema'], 'UNIQUE KEY prescription_number' ) ) {
				$table = self::table( $slug );
				$is_unique = (int) ( $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' AND INDEX_NAME = 'prescription_number' AND NON_UNIQUE = 0" ) );
				if ( $is_unique > 0 ) {
					$GLOBALS['wpdb']->query( "ALTER TABLE {$table} DROP INDEX prescription_number" );
				}
			}

			$stored[ $slug ] = (int) $definition['version'];
			$changed         = true;
		}

		if ( $changed ) {
			update_option( self::TABLE_VERSIONS_OPTION, $stored );
			update_option( self::VERSION_OPTION, ML_CLINIC_DB_VERSION );
		}

		// Version recorded even if no tables changed on a forced run.
		if ( $force && ! $changed ) {
			update_option( self::VERSION_OPTION, ML_CLINIC_DB_VERSION );
		}
	}

	/**
	 * Fully qualified table name (with WP prefix).
	 *
	 * @param string $slug Schema slug, e.g. 'patients'.
	 *
	 * @return string
	 */
	public static function table( $slug ) {
		return self::prefix() . 'ml_' . $slug;
	}

	/**
	 * WordPress table prefix.
	 *
	 * @return string
	 */
	public static function prefix() {
		global $wpdb;
		return isset( $wpdb->prefix ) ? $wpdb->prefix : 'wp_';
	}

	/**
	 * Whether a table physically exists.
	 *
	 * @param string $slug Schema slug.
	 *
	 * @return bool
	 */
	public static function table_exists( $slug ) {
		global $wpdb;
		$name   = self::table( $slug );
		$result = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $name )
		);
		return $name === $result;
	}

	/**
	 * List every clinic table that currently exists.
	 *
	 * @return array<string>
	 */
	public static function installed_tables() {
		global $wpdb;
		$tables = $wpdb->get_col(
			'SHOW TABLES LIKE "' . esc_sql( self::prefix() ) . 'ml_%"'
		);
		return $tables;
	}

	/**
	 * Permanently drop all clinic tables.
	 *
	 * Used ONLY by uninstall.php and only when the admin explicitly enabled
	 * the "delete clinic data on uninstall" setting. Never on deactivate.
	 *
	 * @return int Number of tables removed.
	 */
	public static function drop_all_tables() {
		global $wpdb;
		require_once ML_CLINIC_PATH . 'database/schema.php';
		$dropped = 0;
		foreach ( array_keys( ml_schema_definitions() ) as $slug ) {
			$name = self::table( $slug );
			$wpdb->query( "DROP TABLE IF EXISTS {$name}" );
			$dropped++;
		}
		delete_option( self::VERSION_OPTION );
		delete_option( self::TABLE_VERSIONS_OPTION );
		return $dropped;
	}
}