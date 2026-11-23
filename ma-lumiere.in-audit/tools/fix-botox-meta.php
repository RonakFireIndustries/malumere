<?php
/**
 * Shorten the Botox pillar meta description so the theme's 158-character
 * meta-description limit does not truncate it in rendered HTML.
 */
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

$post_id = 53;
$description = 'Explore Botox and injectable aesthetic treatments in Mira Road East. Facial assessment, conservative planning, suitability and aftercare explained.';

if ( ! get_post( $post_id ) ) {
	echo "BOTOX_POST_MISSING\n";
	exit( 1 );
}

update_post_meta( $post_id, '_ml_seo_description', $description );
echo 'BOTOX_META_UPDATED length=' . strlen( $description ) . "\n";
