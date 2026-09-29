<?php
/**
 * Ma Lumière — theme bootstrap.
 *
 * Keeps functions.php lean; loads the modules in inc/.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ML_THEME_VERSION', '1.8.0' );
define( 'ML_THEME_DIR', get_template_directory() );
define( 'ML_THEME_URI', get_template_directory_uri() );

require get_template_directory() . '/inc/theme-setup.php';
require get_template_directory() . '/inc/enqueue.php';
require get_template_directory() . '/inc/helpers.php';
require get_template_directory() . '/inc/branding.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/custom-post-types.php';
require get_template_directory() . '/inc/custom-taxonomies.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/contact-form.php';
require get_template_directory() . '/inc/seo.php';