<?php
/**
 * Appointment modal dialogs (new, reschedule, cancel, detail).
 * REST-backed via admin.js; options are rendered server-side.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_cancel_reasons = array(
	'patient_request' => __( 'Patient requested', 'ma-lumiere-clinic' ),
	'clinic'          => __( 'Clinic cancelled', 'ma-lumiere-clinic' ),
	'reschedule'      => __( 'Moved to another time', 'ma-lumiere-clinic' ),
	'no_show'         => __( 'No show', 'ma-lumiere-clinic' ),
	'other'           => __( 'Other', 'ma-lumiere-clinic' ),
);
?>

<div class="ml-modal" id="ml-modal-new" hidden>
	<div class="ml-modal__box">
		<div class="ml-modal__head">
			<h2><?php esc_html_e( 'New Appointment', 'ma-lumiere-clinic' ); ?></h2>
			<button type="button" class="ml-modal__close" data-ml-close aria-label="<?php esc_attr_e( 'Close', 'ma-lumiere-clinic' ); ?>">&times;</button>
		</div>
		<div class="ml-modal__body">
			<div class="ml-modal__row ml-modal__row--patient">
				<label class="ml-field__label" for="ml-new-patient"><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></label>
				<input type="text" id="ml-new-patient" class="ml-control ml-patient-search" autocomplete="off"
					placeholder="<?php esc_attr_e( 'Type to search by name or contact…', 'ma-lumiere-clinic' ); ?>" />
				<input type="hidden" id="ml-new-patient-id" />
				<ul class="ml-patient-results" hidden></ul>
			</div>
			<div class="ml-modal__row">
				<label class="ml-field__label" for="ml-new-doctor"><?php esc_html_e( 'Doctor', 'ma-lumiere-clinic' ); ?></label>
				<select class="ml-control" id="ml-new-doctor">
					<option value="0"><?php esc_html_e( 'Default / unassigned', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( $ml_doctor_opts as $ml_doc ) : ?>
						<option value="<?php echo esc_attr( $ml_doc['id'] ); ?>"><?php echo esc_html( $ml_doc['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="ml-modal__row">
				<label class="ml-field__label" for="ml-new-treatment"><?php esc_html_e( 'Treatment', 'ma-lumiere-clinic' ); ?></label>
				<select class="ml-control" id="ml-new-treatment">
					<option value="0"><?php esc_html_e( 'Not specified (default duration)', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( $ml_treatments as $ml_t ) : ?>
						<option value="<?php echo esc_attr( $ml_t['id'] ); ?>" data-duration="<?php echo esc_attr( (int) $ml_t['duration'] ); ?>">
							<?php echo esc_html( $ml_t['name'] ); ?><?php echo $ml_t['duration'] ? ' — ' . esc_html( sprintf( _n( '%d min', '%d mins', (int) $ml_t['duration'], 'ma-lumiere-clinic' ), (int) $ml_t['duration'] ) ) : ''; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="ml-modal__row ml-modal__row--cols">
				<div>
					<label class="ml-field__label" for="ml-new-date"><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></label>
					<input class="ml-control" type="date" id="ml-new-date" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
				</div>
				<div>
					<label class="ml-field__label" for="ml-new-time"><?php esc_html_e( 'Time', 'ma-lumiere-clinic' ); ?></label>
					<select class="ml-control" id="ml-new-time"><option value=""><?php esc_html_e( 'Choose date first', 'ma-lumiere-clinic' ); ?></option></select>
				</div>
			</div>
			<div class="ml-modal__row ml-modal__row--cols">
				<div>
					<label class="ml-field__label" for="ml-new-type"><?php esc_html_e( 'Appointment type', 'ma-lumiere-clinic' ); ?></label>
					<select class="ml-control" id="ml-new-type">
						<?php foreach ( $ml_types as $ml_tk ) : ?>
							<option value="<?php echo esc_attr( $ml_tk ); ?>"><?php echo esc_html( $ml_type_label[ $ml_tk ] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="ml-field__label" for="ml-new-status"><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></label>
					<select class="ml-control" id="ml-new-status">
						<?php foreach ( array( 'pending', 'confirmed' ) as $ml_sk ) : ?>
							<option value="<?php echo esc_attr( $ml_sk ); ?>"><?php echo esc_html( $ml_status_label[ $ml_sk ] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="ml-modal__row">
				<label class="ml-field__label" for="ml-new-notes"><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?></label>
				<textarea id="ml-new-notes" class="ml-control" rows="2"></textarea>
			</div>
			<p class="ml-modal__hint"><?php esc_html_e( 'Available times are recomputed against the live schedule when the date changes.', 'ma-lumiere-clinic' ); ?></p>
		</div>
		<div class="ml-modal__foot">
			<button type="button" class="button" data-ml-close><?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?></button>
			<button type="button" class="button button-primary" id="ml-new-submit"><?php esc_html_e( 'Create appointment', 'ma-lumiere-clinic' ); ?></button>
		</div>
	</div>
</div>

<div class="ml-modal" id="ml-modal-reschedule" hidden>
	<div class="ml-modal__box">
		<div class="ml-modal__head">
			<h2><?php esc_html_e( 'Reschedule Appointment', 'ma-lumiere-clinic' ); ?></h2>
			<button type="button" class="ml-modal__close" data-ml-close aria-label="<?php esc_attr_e( 'Close', 'ma-lumiere-clinic' ); ?>">&times;</button>
		</div>
		<div class="ml-modal__body">
			<p class="ml-muted" id="ml-resched-current"></p>
			<div class="ml-modal__row ml-modal__row--cols">
				<div>
					<label class="ml-field__label" for="ml-resched-date"><?php esc_html_e( 'New date', 'ma-lumiere-clinic' ); ?></label>
					<input class="ml-control" type="date" id="ml-resched-date" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
				</div>
				<div>
					<label class="ml-field__label" for="ml-resched-time"><?php esc_html_e( 'New time', 'ma-lumiere-clinic' ); ?></label>
					<select class="ml-control" id="ml-resched-time"><option value=""><?php esc_html_e( 'Choose date first', 'ma-lumiere-clinic' ); ?></option></select>
				</div>
			</div>
			<p class="ml-modal__hint"><?php esc_html_e( 'The current appointment is closed and a new reference is issued.', 'ma-lumiere-clinic' ); ?></p>
		</div>
		<div class="ml-modal__foot">
			<button type="button" class="button" data-ml-close><?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?></button>
			<button type="button" class="button button-primary" id="ml-resched-submit"><?php esc_html_e( 'Reschedule', 'ma-lumiere-clinic' ); ?></button>
		</div>
	</div>
</div>

<div class="ml-modal" id="ml-modal-cancel" hidden>
	<div class="ml-modal__box">
		<div class="ml-modal__head">
			<h2><?php esc_html_e( 'Cancel Appointment', 'ma-lumiere-clinic' ); ?></h2>
			<button type="button" class="ml-modal__close" data-ml-close aria-label="<?php esc_attr_e( 'Close', 'ma-lumiere-clinic' ); ?>">&times;</button>
		</div>
		<div class="ml-modal__body">
			<p class="ml-muted" id="ml-cancel-current"></p>
			<div class="ml-modal__row">
				<label class="ml-field__label" for="ml-cancel-reason"><?php esc_html_e( 'Reason', 'ma-lumiere-clinic' ); ?></label>
				<select class="ml-control" id="ml-cancel-reason">
					<?php foreach ( $ml_cancel_reasons as $ml_ck => $ml_cl ) : ?>
						<option value="<?php echo esc_attr( $ml_ck ); ?>"><?php echo esc_html( $ml_cl ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="ml-modal__foot">
			<button type="button" class="button" data-ml-close><?php esc_html_e( 'Keep appointment', 'ma-lumiere-clinic' ); ?></button>
			<button type="button" class="button button-link-delete" id="ml-cancel-submit"><?php esc_html_e( 'Yes, cancel', 'ma-lumiere-clinic' ); ?></button>
		</div>
	</div>
</div>

<div class="ml-modal" id="ml-modal-detail" hidden>
	<div class="ml-modal__box">
		<div class="ml-modal__head">
			<h2><?php esc_html_e( 'Appointment', 'ma-lumiere-clinic' ); ?></h2>
			<button type="button" class="ml-modal__close" data-ml-close aria-label="<?php esc_attr_e( 'Close', 'ma-lumiere-clinic' ); ?>">&times;</button>
		</div>
		<div class="ml-modal__body">
			<dl class="ml-modal__dl" id="ml-detail-fields"></dl>
			<p class="ml-modal__hint" id="ml-detail-message"></p>
		</div>
		<div class="ml-modal__foot">
			<button type="button" class="button" data-ml-close><?php esc_html_e( 'Close', 'ma-lumiere-clinic' ); ?></button>
		</div>
	</div>
</div>

<div class="ml-modal ml-modal--error" id="ml-modal-error" hidden>
	<div class="ml-modal__box">
		<div class="ml-modal__head"><h2><?php esc_html_e( 'Notice', 'ma-lumiere-clinic' ); ?></h2></div>
		<div class="ml-modal__body"><p id="ml-error-text"></p></div>
		<div class="ml-modal__foot"><button type="button" class="button" data-ml-close><?php esc_html_e( 'OK', 'ma-lumiere-clinic' ); ?></button></div>
	</div>
</div>

<div class="ml-modal ml-modal--confirm" id="ml-modal-confirm" hidden>
	<div class="ml-modal__box">
		<div class="ml-modal__head"><h2 id="ml-confirm-title"></h2></div>
		<div class="ml-modal__body"><p id="ml-confirm-text"></p></div>
		<div class="ml-modal__foot">
			<button type="button" class="button" data-ml-close><?php esc_html_e( 'Cancel', 'ma-lumiere-clinic' ); ?></button>
			<button type="button" class="button button-primary" id="ml-confirm-ok"><?php esc_html_e( 'Confirm', 'ma-lumiere-clinic' ); ?></button>
		</div>
	</div>
</div>