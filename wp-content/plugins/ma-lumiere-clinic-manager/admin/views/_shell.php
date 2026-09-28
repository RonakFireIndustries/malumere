<?php
/**
 * Generic shell for menu items whose dedicated UI lands in a later phase.
 * $title is set by ML_Admin_Menu::render_shell().
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_page_title = isset( $title ) ? $title : __( 'Ma Lumière Clinic', 'ma-lumiere-clinic' );
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php echo esc_html( $ml_page_title ); ?></h1>
		<p class="ml-clinic-subtitle"><?php esc_html_e( 'This module is part of the clinic foundation and will be fully implemented in a later phase.', 'ma-lumiere-clinic' ); ?></p>
	</div>

	<div class="ml-clinic-empty">
		<p><?php esc_html_e( 'No data available', 'ma-lumiere-clinic' ); ?></p>
	</div>
</div>