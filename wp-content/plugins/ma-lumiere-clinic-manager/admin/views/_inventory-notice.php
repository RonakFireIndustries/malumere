<?php
/**
 * Inventory admin notice banner rendered from ?notice= (and optional ?detail=).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_inventory_notice = ML_Inventory_Controller::$notice;
if ( '' === $ml_inventory_notice ) {
	return;
}

$ml_inventory_detail = ML_Inventory_Controller::$notice_detail;

/*
 * Success notices pair a short message with a detail fragment. The detail is
 * plain text (no markup) and is always escaped on output.
 */
$ml_inventory_messages = array(
	'created'            => __( 'Medicine added to the catalogue.', 'ma-lumiere-clinic' ),
	'updated'            => __( 'Medicine details updated.', 'ma-lumiere-clinic' ),
	'deleted'            => __( 'Medicine removed from the catalogue.', 'ma-lumiere-clinic' ),
	'received'           => __( 'Stock received into a new batch.', 'ma-lumiere-clinic' ),
	'batch_updated'      => __( 'Batch details updated.', 'ma-lumiere-clinic' ),
	'adjusted'           => __( 'Stock adjustment recorded.', 'ma-lumiere-clinic' ),
	'batch_status'       => __( 'Batch status updated.', 'ma-lumiere-clinic' ),
	'dispensed'          => __( 'Stock dispensed against the prescription.', 'ma-lumiere-clinic' ),
	'returned'           => __( 'Dispensed stock returned to inventory.', 'ma-lumiere-clinic' ),
	'expired_written_off' => __( 'Expired stock written off.', 'ma-lumiere-clinic' ),
	'not_found'          => __( 'That record could not be found.', 'ma-lumiere-clinic' ),
	'adjust_failed'      => __( 'The stock adjustment was not recorded.', 'ma-lumiere-clinic' ),
	'batch_status_failed' => __( 'The batch status was not changed.', 'ma-lumiere-clinic' ),
	'dispense_failed'    => __( 'The prescription was not dispensed.', 'ma-lumiere-clinic' ),
	'return_failed'      => __( 'The stock was not returned.', 'ma-lumiere-clinic' ),
	'delete_failed'      => __( 'The medicine was not deleted.', 'ma-lumiere-clinic' ),
);

$ml_inventory_errors = array( 'adjust_failed', 'batch_status_failed', 'dispense_failed', 'return_failed', 'delete_failed' );

$ml_inventory_type = ( 'not_found' === $ml_inventory_notice || in_array( $ml_inventory_notice, $ml_inventory_errors, true ) ) ? 'notice-error' : 'notice-success';

if ( ! isset( $ml_inventory_messages[ $ml_inventory_notice ] ) ) {
	return;
}

// "expired_written_off" carries "batches|units" and "dispensed"/"returned"
// carry a unit count, so build a human sentence for them.
$ml_inventory_extra = '';
if ( 'expired_written_off' === $ml_inventory_notice && '' !== $ml_inventory_detail ) {
	$ml_inventory_parts = explode( '|', $ml_inventory_detail );
	$ml_inventory_extra = sprintf(
		/* translators: 1: batches, 2: units. */
		__( '%1$d batch(es), %2$d unit(s) written off.', 'ma-lumiere-clinic' ),
		isset( $ml_inventory_parts[0] ) ? (int) $ml_inventory_parts[0] : 0,
		isset( $ml_inventory_parts[1] ) ? (int) $ml_inventory_parts[1] : 0
	);
} elseif ( in_array( $ml_inventory_notice, array( 'dispensed', 'returned' ), true ) && '' !== $ml_inventory_detail ) {
	$ml_inventory_extra = sprintf(
		/* translators: %d: units. */
		__( '%d unit(s) processed.', 'ma-lumiere-clinic' ),
		(int) $ml_inventory_detail
	);
} elseif ( in_array( $ml_inventory_notice, $ml_inventory_errors, true ) && '' !== $ml_inventory_detail ) {
	$ml_inventory_extra = $ml_inventory_detail;
}
?>
<div class="notice <?php echo esc_attr( $ml_inventory_type ); ?> is-dismissible">
	<p>
		<?php echo esc_html( $ml_inventory_messages[ $ml_inventory_notice ] ); ?>
		<?php if ( '' !== $ml_inventory_extra ) : ?>
			<strong><?php echo esc_html( $ml_inventory_extra ); ?></strong>
		<?php endif; ?>
	</p>
</div>
