<?php
/**
 * Shared admin notice banner rendered from ?notice=.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

if ( '' === ML_Patient_Controller::$notice ) {
	return;
}

$ml_messages = array(
	'saved'     => __( 'Patient registered successfully.', 'ma-lumiere-clinic' ),
	'updated'   => __( 'Patient updated successfully.', 'ma-lumiere-clinic' ),
	'deleted'   => __( 'Patient deleted.', 'ma-lumiere-clinic' ),
	'not_found' => __( 'Patient not found.', 'ma-lumiere-clinic' ),
);

if ( isset( $ml_messages[ ML_Patient_Controller::$notice ] ) ) {
	$ml_type  = ( 'not_found' === ML_Patient_Controller::$notice ) ? 'notice-error' : 'notice-success';
	?>
	<div class="notice <?php echo esc_attr( $ml_type ); ?> is-dismissible">
		<p><?php echo esc_html( $ml_messages[ ML_Patient_Controller::$notice ] ); ?></p>
	</div>
	<?php
}