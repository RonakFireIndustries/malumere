<?php
/**
 * Treatment registry.
 *
 * The clinic-side treatment catalog lives in {prefix}ml_treatments. The
 * public website keeps its own presentation copy as `ml_treatment` CPT
 * posts; this repository keeps the clinic table in sync with those posts
 * so the booking system uses the site's real treatment data — never
 * fabricated records.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Treatment_Repository {

	/**
	 * Option holding a fingerprint of the CPT treatment set.
	 *
	 * @var string
	 */
	const FINGERPRINT_OPTION = 'ml_clinic_treatments_fingerprint';

	/**
	 * Transient name for the active catalog cache.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'ml_clinic_treatment_catalog';

	/**
	 * CPT timezone for code hooks binding this repository to theme content.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'save_post_ml_treatment', array( __CLASS__, 'bust_fingerprint' ) );
	}

	/**
	 * Invalidate the sync fingerprint after a treatment post is saved.
	 *
	 * @return void
	 */
	public static function bust_fingerprint() {
		delete_transient( self::CACHE_KEY );
		update_option( self::FINGERPRINT_OPTION, '' );
	}

	/**
	 * Sync the clinic table from published ml_treatment posts (idempotent
	 * upsert keyed on wp_post_id).
	 *
	 * @return int Number of treatment rows written.
	 */
	public static function sync_from_cpt() {
		global $wpdb;

		$posts = get_posts(
			array(
				'post_type'      => 'ml_treatment',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			)
		);

		$table  = ML_Database::table( 'treatments' );
		$now    = current_time( 'mysql' );
		$kept   = array();
		$count  = 0;

		foreach ( $posts as $post ) {
			$name     = trim( wp_strip_all_tags( $post->post_title ) );
			$duration = self::post_duration_minutes( $post->ID );
			$category = self::post_category( $post->ID );

			$existing = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table} WHERE wp_post_id = %d LIMIT 1", $post->ID )
			);

			if ( $existing ) {
				$wpdb->update(
					$table,
					array(
						'name'       => $name,
						'category'   => $category,
						'duration'   => $duration ? (string) $duration : '',
						'status'     => 'active',
						'updated_at' => $now,
					),
					array( 'id' => (int) $existing ),
					array( '%s', '%s', '%s', '%s', '%s' ),
					array( '%d' )
				);
				$kept[] = (int) $existing;
			} else {
				$wpdb->insert(
					$table,
					array(
						'wp_post_id' => $post->ID,
						'name'       => $name,
						'category'   => $category,
						'duration'   => $duration ? (string) $duration : '',
						'status'     => 'active',
						'created_at' => $now,
						'updated_at' => $now,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
				);
				$kept[] = (int) $wpdb->insert_id;
			}
			$count++;
		}

		// Any record that no longer maps to a published post is paused.
		$rows = $wpdb->get_col( "SELECT id FROM {$table} WHERE status = 'active'" );
		$drop = array();
		foreach ( (array) $rows as $row ) {
			if ( ! in_array( (int) $row, $kept, true ) ) {
				$drop[] = (int) $row;
			}
		}
		foreach ( $drop as $id ) {
			$wpdb->update( $table, array( 'status' => 'inactive', 'updated_at' => $now ), array( 'id' => $id ), array( '%s', '%s' ), array( '%d' ) );
		}

		delete_transient( self::CACHE_KEY );
		update_option( self::FINGERPRINT_OPTION, self::fingerprint() );

		return $count;
	}

	/**
	 * Sync only when the public treatment set has changed.
	 *
	 * @return bool Whether a sync ran.
	 */
	public static function sync_if_needed() {
		$current = self::fingerprint();
		if ( get_option( self::FINGERPRINT_OPTION, '' ) === $current ) {
			return false;
		}
		self::sync_from_cpt();
		return true;
	}

	/**
	 * Fingerprint of the published CPT treatment set.
	 *
	 * @return string
	 */
	private static function fingerprint() {
		global $wpdb;
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'ml_treatment',
				'publish'
			)
		);
		$max   = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'ml_treatment',
				'publish'
			)
		);
		return md5( $count . '|' . $max );
	}

	/**
	 * Active treatment catalog (symlink the exit point to list_active()).
	 *
	 * @return array
	 */
	public static function catalog() {
		return self::list_active();
	}

	/**
	 * Active, bookable treatments with their public display data.
	 *
	 * @return array
	 */
	public static function list_active() {
		self::sync_if_needed();

		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}

		global $wpdb;
		$table  = ML_Database::table( 'treatments' );
		$rows   = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'active' ORDER BY name ASC", ARRAY_A );
		$items  = array();

		foreach ( (array) $rows as $row ) {
			$post_id   = (int) $row['wp_post_id'];
			$duration  = (int) $row['duration'];
			$items[]   = array(
				'id'           => (int) $row['id'],
				'wp_post_id'   => $post_id,
				'name'         => (string) $row['name'],
				'category'     => (string) $row['category'],
				'duration'     => $duration > 0 ? $duration : 0,
				'short_desc'   => $post_id ? (string) get_post_meta( $post_id, '_ml_treatment_short', true ) : '',
				'permalink'    => $post_id ? (string) get_permalink( $post_id ) : '',
				'image_id'     => $post_id ? (int) get_post_thumbnail_id( $post_id ) : 0,
				'bookable'     => true,
			);
		}

		set_transient( self::CACHE_KEY, $items, 5 * MINUTE_IN_SECONDS );
		return $items;
	}

	/**
	 * Fetch a single active treatment row.
	 *
	 * @param int $id ml_treatments.id.
	 *
	 * @return array|null
	 */
	public static function get( $id ) {
		$id = absint( $id );
		foreach ( self::list_active() as $item ) {
			if ( $item['id'] === $id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Effective duration in minutes for a treatment (0 = fall back to the
	 * clinic default slot duration).
	 *
	 * @param int $id ml_treatments.id.
	 *
	 * @return int
	 */
	public static function duration_minutes( $id ) {
		$item = self::get( $id );
		return $item ? (int) $item['duration'] : 0;
	}

	/**
	 * Per-treatment duration override read from the CPT meta (minutes).
	 *
	 * @param int $post_id WP post ID.
	 *
	 * @return int
	 */
	public static function post_duration_minutes( $post_id ) {
		$raw = get_post_meta( $post_id, '_ml_treatment_duration', true );
		$n   = is_numeric( $raw ) ? (int) $raw : 0;
		if ( $n < 0 ) {
			return 0;
		}
		return min( $n, 600 );
	}

	/**
	 * First treatment category name for a post, if any.
	 *
	 * @param int $post_id WP post ID.
	 *
	 * @return string
	 */
	private static function post_category( $post_id ) {
		$terms = get_the_terms( $post_id, 'treatment_category' );
		if ( is_array( $terms ) && $terms ) {
			$first = reset( $terms );
			return $first instanceof WP_Term ? (string) $first->name : '';
		}
		return '';
	}
}

ML_Treatment_Repository::init();