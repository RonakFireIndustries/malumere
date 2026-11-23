<?php
/**
 * Phase 1 DB fixes - remove patient-visible placeholders.
 * Run: C:\xampp\php\php.exe ma-lumiere.in-audit\tools\fix-phase1-db.php
 */
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

$updated = array();

// Post 15: acne treatment case reference - remove placeholder, make educational.
$post_id = 15;
$post    = get_post( $post_id );
if ( $post && 'ml_before_after' === $post->post_type ) {
	$content = "<p>Acne management is planned after assessing active breakouts, marks, scarring pattern, skin sensitivity, and previous treatments. Because every case is different, outcomes and timelines vary.</p>";
	$content .= "<p>During consultation at Ma Lumière in Mira Road East, the focus is on identifying the type of acne, reducing active inflammation safely, protecting the skin barrier, and planning scar or mark care only when the skin is ready.</p>";
	$content .= "<p>Consent-approved before-and-after photographs are not published on this page at this time. If you would like to understand what a realistic plan may look like for your skin, please book a consultation.</p>";
	$result = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_title'   => 'Acne Treatment: What to Expect',
			'post_content' => $content,
			'post_excerpt' => 'Educational information about acne treatment planning. Individual outcomes vary after consultation.',
		),
		true
	);
	update_post_meta( $post_id, '_ml_ba_treatment', 'Acne management' );
	if ( is_wp_error( $result ) ) {
		$updated[] = array( 'id' => $post_id, 'error' => $result->get_error_message() );
	} else {
		$updated[] = array( 'id' => $post_id, 'status' => 'updated', 'permalink' => get_permalink( $post_id ) );
	}
}

// Remove any remaining placeholder strings from post content/excerpt/meta in published content.
global $wpdb;
$patterns = array(
	'[REAL, CONSENT-APPROVED CASE REQUIRED — before/after images must be uploaded here and must not reveal patient identity.]',
	'[BEFORE/AFTER IMAGES REQUIRED]',
	'[IMAGE REQUIRED]',
	'[TREATMENT IMAGE]',
);
$posts = $wpdb->get_results( "SELECT ID, post_title, post_content, post_excerpt, post_type FROM {$wpdb->posts} WHERE post_status NOT IN ('trash','auto-draft')" );
foreach ( $posts as $p ) {
	$new_content = $p->post_content;
	$new_excerpt = $p->post_excerpt;
	foreach ( $patterns as $pat ) {
		$new_content = str_replace( $pat, '', $new_content );
		$new_excerpt = str_replace( $pat, '', $new_excerpt );
	}
	if ( $new_content !== $p->post_content || $new_excerpt !== $p->post_excerpt ) {
		$wpdb->update(
			$wpdb->posts,
			array( 'post_content' => trim( $new_content ), 'post_excerpt' => trim( $new_excerpt ) ),
			array( 'ID' => $p->ID )
		);
		clean_post_cache( $p->ID );
		$updated[] = array( 'id' => (int) $p->ID, 'status' => 'placeholder-removed', 'type' => $p->post_type );
	}
}

echo wp_json_encode( $updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";