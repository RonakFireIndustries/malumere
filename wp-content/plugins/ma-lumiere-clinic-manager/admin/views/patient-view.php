<?php
/**
 * Single patient record view (demographics + cap-filtered clinical summary).
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_can_edit   = current_user_can( 'ml_edit_patients' );
$ml_can_delete = current_user_can( 'ml_manage_clinic' );
$ml_base       = admin_url( 'admin.php' );
$ml_page_url   = add_query_arg( 'page', 'ml-clinic-patients', $ml_base );
$ml_name       = trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] );
$ml_age        = ML_Patient_Repository::age( $patient );

$ml_patient_id   = absint( $patient['id'] );
$ml_back_url     = add_query_arg( array( 'page' => 'ml-clinic-patients', 'view' => $ml_patient_id ), $ml_base );
$ml_manage_visits = current_user_can( 'ml_manage_visits' ) || current_user_can( 'ml_manage_clinic' );
$ml_manage_rx     = current_user_can( 'ml_manage_prescriptions' ) || current_user_can( 'ml_manage_clinic' );
$ml_manage_fu     = current_user_can( 'ml_manage_followups' ) || current_user_can( 'ml_manage_clinic' );
$ml_view_photos   = current_user_can( 'ml_view_patient_photos' ) || current_user_can( 'ml_manage_clinic' );
$ml_manage_photos = current_user_can( 'ml_manage_patient_photos' ) || current_user_can( 'ml_manage_clinic' );

/*
 * Dispensing and returning stock are inventory permissions, kept separate from
 * prescribing so a prescriber without stock access never sees those buttons.
 */
$ml_stock_ops = current_user_can( 'ml_manage_inventory' ) || current_user_can( 'ml_manage_clinic' );

/*
 * Catalogue options for linking a prescription line to stock. Only offered to
 * someone who can actually manage inventory, so the picker is never a dead end.
 */
$ml_medicine_options = array();
if ( $ml_manage_rx && $ml_stock_ops && class_exists( 'ML_Medicine_Repository' ) ) {
	foreach ( ML_Medicine_Repository::options( true ) as $ml_option ) {
		$ml_medicine_options[ (int) $ml_option['id'] ] = (string) $ml_option['label'];
	}
}

/**
 * Inline clinical actions (visits, prescriptions, sessions, follow-ups).
 * Each is capped + nonce-gated and scoped to THIS patient record.
 */
$ml_ph_error = '';
$ml_ph_ok    = isset( $_POST['_wpnonce'] ) && ML_Security::verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) );
if ( isset( $_POST['ml_action'], $_POST['_wpnonce'] ) ) {
	$ml_act = sanitize_key( wp_unslash( $_POST['ml_action'] ) );

	if ( ! $ml_ph_ok ) {
		$ml_ph_error = __( 'Security check failed. Please try again.', 'ma-lumiere-clinic' );
	} elseif ( 'save_patient' === $ml_act ) {
		// Handled by the controller.
	} elseif ( 'create_visit' === $ml_act && $ml_manage_visits ) {
		$result = ML_Visit_Repository::create(
			array(
				'patient_id'      => $ml_patient_id,
				'visit_date'      => isset( $_POST['visit_date'] ) ? sanitize_text_field( wp_unslash( $_POST['visit_date'] ) ) : '',
				'chief_complaint' => isset( $_POST['chief_complaint'] ) ? sanitize_textarea_field( wp_unslash( $_POST['chief_complaint'] ) ) : '',
				'diagnosis'       => isset( $_POST['diagnosis'] ) ? sanitize_textarea_field( wp_unslash( $_POST['diagnosis'] ) ) : '',
				'clinical_notes'  => isset( $_POST['clinical_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['clinical_notes'] ) ) : '',
				'treatment_plan'  => isset( $_POST['treatment_plan'] ) ? sanitize_textarea_field( wp_unslash( $_POST['treatment_plan'] ) ) : '',
				'doctor_user_id'  => get_current_user_id(),
				'status'          => 'open',
			)
		);
		if ( is_wp_error( $result ) ) {
			$ml_ph_error = $result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( 'ml_notice', 'visit_added', $ml_back_url ) );
			exit;
		}
	} elseif ( 'add_prescription' === $ml_act && $ml_manage_rx ) {
		$items_raw = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array(); // phpcs:ignore
		$items     = array();
		foreach ( $items_raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$med  = isset( $row['medicine_name'] ) ? sanitize_text_field( (string) $row['medicine_name'] ) : '';
			$medid = isset( $row['medicine_id'] ) ? absint( $row['medicine_id'] ) : 0;
			$qty   = isset( $row['quantity'] ) ? absint( $row['quantity'] ) : 0;
			if ( '' === trim( $med ) && ! $medid ) {
				continue;
			}
			$items[] = array(
				'medicine_id'   => $medid,
				'medicine_name' => $med,
				// Only a linked line with a positive quantity moves stock;
				// everything else stays a printed instruction.
				'quantity'      => $medid ? $qty : 0,
				'dosage'        => isset( $row['dosage'] ) ? sanitize_text_field( (string) $row['dosage'] ) : '',
				'frequency'     => isset( $row['frequency'] ) ? sanitize_text_field( (string) $row['frequency'] ) : '',
				'duration'      => isset( $row['duration'] ) ? sanitize_text_field( (string) $row['duration'] ) : '',
			);
		}
		$result = ML_Prescription_Service::create(
			array(
				'patient_id'        => $ml_patient_id,
				'prescription_date' => isset( $_POST['prescription_date'] ) ? sanitize_text_field( wp_unslash( $_POST['prescription_date'] ) ) : '',
				'diagnosis'         => isset( $_POST['rx_diagnosis'] ) ? sanitize_textarea_field( wp_unslash( $_POST['rx_diagnosis'] ) ) : '',
				'advice'            => isset( $_POST['advice'] ) ? sanitize_textarea_field( wp_unslash( $_POST['advice'] ) ) : '',
				'follow_up_date'    => isset( $_POST['follow_up_date'] ) ? sanitize_text_field( wp_unslash( $_POST['follow_up_date'] ) ) : '',
				'items'             => $items,
			)
		);
		if ( is_wp_error( $result ) ) {
			$ml_ph_error = $result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( 'ml_notice', 'rx_added', $ml_back_url ) );
			exit;
		}
	} elseif ( in_array( $ml_act, array( 'dispense_rx', 'return_rx' ), true ) && $ml_manage_rx ) {
		if ( ! $ml_stock_ops ) {
			$ml_ph_error = __( 'You are not allowed to manage stock.', 'ma-lumiere-clinic' );
		} else {
			$rx_id = isset( $_POST['rx_id'] ) ? absint( $_POST['rx_id'] ) : 0;
			$rxrow = ML_Prescription_Repository::get( $rx_id );
			if ( ! $rxrow || (int) $rxrow['patient_id'] !== $ml_patient_id ) {
				$ml_ph_error = __( 'That prescription does not belong to this patient.', 'ma-lumiere-clinic' );
			} else {
				$result = 'dispense_rx' === $ml_act
					? ML_Inventory_Service::dispense_prescription( $rx_id )
					: ML_Inventory_Service::return_from_prescription( $rx_id );
				if ( is_wp_error( $result ) ) {
					$ml_ph_error = $result->get_error_message();
					$shortages   = $result->get_error_data();
					if ( is_array( $shortages ) && ! empty( $shortages['shortages'] ) ) {
						$ml_ph_error .= ' ' . implode( ' ', array_map( 'sanitize_text_field', (array) $shortages['shortages'] ) );
					}
				} else {
					$ml_notice = 'dispense_rx' === $ml_act ? 'rx_dispensed' : 'rx_returned';
					wp_safe_redirect( add_query_arg( 'ml_notice', $ml_notice, $ml_back_url ) );
					exit;
				}
			}
		}
	} elseif ( in_array( $ml_act, array( 'finalize', 'correct' ), true ) && $ml_manage_rx ) {
		$rx_id = isset( $_POST['rx_id'] ) ? absint( $_POST['rx_id'] ) : 0;
		$rxrow = ML_Prescription_Repository::get( $rx_id );
		if ( ! $rxrow || (int) $rxrow['patient_id'] !== $ml_patient_id ) {
			$ml_ph_error = __( 'That prescription does not belong to this patient.', 'ma-lumiere-clinic' );
		} else {
			$result = 'finalize' === $ml_act ? ML_Prescription_Service::finalize( $rx_id ) : ML_Prescription_Service::correct( $rx_id );
			if ( is_wp_error( $result ) ) {
				$ml_ph_error = $result->get_error_message();
			} else {
				$ml_notice = 'finalize' === $ml_act ? 'rx_finalized' : 'rx_corrected';
				wp_safe_redirect( add_query_arg( 'ml_notice', $ml_notice, $ml_back_url ) );
				exit;
			}
		}
	} elseif ( 'add_session' === $ml_act && $ml_manage_visits ) {
		$result = ML_Treatment_Session_Repository::create(
			array(
				'patient_id'     => $ml_patient_id,
				'session_type'   => isset( $_POST['session_type'] ) ? sanitize_text_field( wp_unslash( $_POST['session_type'] ) ) : '',
				'scheduled_date' => isset( $_POST['scheduled_date'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_date'] ) ) : '',
				'notes'          => isset( $_POST['session_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['session_notes'] ) ) : '',
				'status'         => 'rec',
			)
		);
		if ( is_wp_error( $result ) ) {
			$ml_ph_error = $result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( 'ml_notice', 'session_added', $ml_back_url ) );
			exit;
		}
	} elseif ( 'session_status' === $ml_act && $ml_manage_visits ) {
		$session = ML_Treatment_Session_Repository::get( isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0 );
		if ( ! $session || (int) $session['patient_id'] !== $ml_patient_id ) {
			$ml_ph_error = __( 'That session does not belong to this patient.', 'ma-lumiere-clinic' );
		} else {
			$status = isset( $_POST['session_status'] ) ? sanitize_key( wp_unslash( $_POST['session_status'] ) ) : '';
			$result = ML_Treatment_Session_Repository::update( (int) $session['id'], array( 'status' => $status ) );
			if ( is_wp_error( $result ) ) {
				$ml_ph_error = $result->get_error_message();
			} else {
				wp_safe_redirect( add_query_arg( 'ml_notice', 'session_status', $ml_back_url ) );
				exit;
			}
		}
	} elseif ( 'add_followup' === $ml_act && $ml_manage_fu ) {
		$result = ML_Followup_Repository::create(
			array(
				'patient_id'   => $ml_patient_id,
				'followup_date' => isset( $_POST['followup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['followup_date'] ) ) : '',
				'reason'       => isset( $_POST['followup_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['followup_reason'] ) ) : '',
				'notes'        => isset( $_POST['followup_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['followup_notes'] ) ) : '',
			)
		);
		if ( is_wp_error( $result ) ) {
			$ml_ph_error = $result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( 'ml_notice', 'followup_added', $ml_back_url ) );
			exit;
		}
	} elseif ( 'followup_status' === $ml_act && $ml_manage_fu ) {
		$furow = ML_Followup_Repository::get( isset( $_POST['followup_id'] ) ? absint( $_POST['followup_id'] ) : 0 );
		if ( ! $furow || (int) $furow['patient_id'] !== $ml_patient_id ) {
			$ml_ph_error = __( 'That follow-up does not belong to this patient.', 'ma-lumiere-clinic' );
		} else {
			$status = isset( $_POST['fu_status'] ) ? sanitize_key( wp_unslash( $_POST['fu_status'] ) ) : '';
			$result = ML_Followup_Repository::set_status( (int) $furow['id'], $status );
			if ( is_wp_error( $result ) ) {
				$ml_ph_error = $result->get_error_message();
			} else {
				wp_safe_redirect( add_query_arg( 'ml_notice', 'followup_status', $ml_back_url ) );
				exit;
			}
		}
	} elseif ( 'add_photo' === $ml_act && $ml_manage_photos ) {
		if ( empty( $_FILES['photo'] ) || ! is_array( $_FILES['photo'] ) ) {
			$ml_ph_error = __( 'No file was provided.', 'ma-lumiere-clinic' );
		} else {
			$result = ML_Photo_Repository::create(
				array(
					'patient_id' => $ml_patient_id,
					'file'       => wp_unslash( $_FILES['photo'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'photo_type' => isset( $_POST['photo_type'] ) ? sanitize_key( wp_unslash( $_POST['photo_type'] ) ) : 'before',
					'body_area'  => isset( $_POST['body_area'] ) ? sanitize_text_field( wp_unslash( $_POST['body_area'] ) ) : '',
					'photo_date' => isset( $_POST['photo_date'] ) ? sanitize_text_field( wp_unslash( $_POST['photo_date'] ) ) : '',
					'notes'      => isset( $_POST['photo_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['photo_notes'] ) ) : '',
					'visit_id'   => isset( $_POST['visit_id'] ) ? absint( $_POST['visit_id'] ) : 0,
				)
			);
			if ( is_wp_error( $result ) ) {
				$ml_ph_error = $result->get_error_message();
			} else {
				wp_safe_redirect( add_query_arg( 'ml_notice', 'photo_added', $ml_back_url ) );
				exit;
			}
		}
	} elseif ( 'delete_photo' === $ml_act && $ml_manage_photos ) {
		$photo = ML_Photo_Repository::get( isset( $_POST['photo_id'] ) ? absint( $_POST['photo_id'] ) : 0 );
		if ( ! $photo || (int) $photo['patient_id'] !== $ml_patient_id ) {
			$ml_ph_error = __( 'That photo does not belong to this patient.', 'ma-lumiere-clinic' );
		} else {
			$result = ML_Photo_Repository::delete( (int) $photo['id'] );
			if ( is_wp_error( $result ) ) {
				$ml_ph_error = $result->get_error_message();
			} else {
				wp_safe_redirect( add_query_arg( 'ml_notice', 'photo_deleted', $ml_back_url ) );
				exit;
			}
		}
	}
}

$ml_rx_pdf_nonce = wp_create_nonce( ML_Security::NONCE_ACTION );

$ml_visits = array();
if ( current_user_can( 'ml_view_visits' ) ) {
	$ml_visits = ML_Visit_Repository::list(
		array(
			'patient_id' => $ml_patient_id,
			'per_page'   => 50,
		)
	)['items'];
}

$ml_transcripts = array();
if ( current_user_can( 'ml_view_prescriptions' ) ) {
	$ml_transcripts = ML_Prescription_Repository::list(
		array(
			'patient_id' => $ml_patient_id,
			'per_page'   => 50,
		)
	)['items'];
}

$ml_sessions = array();
if ( current_user_can( 'ml_view_visits' ) ) {
	$ml_sessions = ML_Treatment_Session_Repository::for_patient( $ml_patient_id );
}

$ml_followups = array();
if ( current_user_can( 'ml_view_followups' ) ) {
	$ml_followups = ML_Followup_Repository::for_patient( $ml_patient_id );
}
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title">
			<?php echo esc_html( $ml_name ); ?>
			<span class="ml-badge ml-badge--<?php echo esc_attr( 'active' === $patient['status'] ? 'success' : 'muted' ); ?>"><?php echo esc_html( ucfirst( $patient['status'] ) ); ?></span>
		</h1>
		<div class="ml-form-actions">
			<a class="button" href="<?php echo esc_url( $ml_page_url ); ?>"><?php esc_html_e( 'Back to list', 'ma-lumiere-clinic' ); ?></a>
			<?php if ( $ml_can_edit ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $patient['id'] ), $ml_page_url ) ); ?>"><?php esc_html_e( 'Edit Patient', 'ma-lumiere-clinic' ); ?></a>
			<?php endif; ?>
			<?php if ( $ml_can_delete ) : ?>
				<a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $patient['id'] ), $ml_page_url ), ML_Security::NONCE_ACTION ) ); ?>"
					onclick="return confirm('<?php echo esc_js( __( 'Delete this patient record permanently? Related clinical records are kept but will become orphaned.', 'ma-lumiere-clinic' ) ); ?>');">
					<?php esc_html_e( 'Delete', 'ma-lumiere-clinic' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php include ML_CLINIC_PATH . 'admin/views/_notice.php'; ?>

	<?php if ( '' !== $ml_ph_error ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $ml_ph_error ); ?></p></div>
	<?php endif; ?>

	<?php
	$ml_notice_key = isset( $_GET['ml_notice'] ) ? sanitize_key( wp_unslash( $_GET['ml_notice'] ) ) : '';
	$ml_notice_msg = array(
		'visit_added'      => __( 'Visit recorded.', 'ma-lumiere-clinic' ),
		'rx_added'         => __( 'Prescription draft created.', 'ma-lumiere-clinic' ),
	'rx_finalized'     => __( 'Prescription finalized and issued.', 'ma-lumiere-clinic' ),
	'rx_corrected'      => __( 'Correction draft created.', 'ma-lumiere-clinic' ),
	'rx_dispensed'      => __( 'Stock dispensed against the prescription.', 'ma-lumiere-clinic' ),
	'rx_returned'       => __( 'Dispensed stock returned to inventory.', 'ma-lumiere-clinic' ),
		'session_added'    => __( 'Treatment session recommended.', 'ma-lumiere-clinic' ),
		'session_status'   => __( 'Session status updated.', 'ma-lumiere-clinic' ),
		'followup_added'   => __( 'Follow-up scheduled.', 'ma-lumiere-clinic' ),
		'followup_status'  => __( 'Follow-up status updated.', 'ma-lumiere-clinic' ),
		'photo_added'      => __( 'Photo uploaded.', 'ma-lumiere-clinic' ),
		'photo_deleted'    => __( 'Photo deleted.', 'ma-lumiere-clinic' ),
	);
	if ( isset( $ml_notice_msg[ $ml_notice_key ] ) ) :
		?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $ml_notice_msg[ $ml_notice_key ] ); ?></p></div>
	<?php endif; ?>

	<div class="ml-clinic-grid">
		<section class="ml-clinic-panel">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Demographics', 'ma-lumiere-clinic' ); ?></h2>
			<dl class="ml-clinic-dl">
				<div><dt><?php esc_html_e( 'Patient ID', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( $patient['patient_uid'] ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Date of birth', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( ml_date( $patient['date_of_birth'], 'd M Y' ) ); ?><?php if ( '' !== $ml_age ) : ?> <span class="ml-muted">(<?php echo esc_html( $ml_age . ' ' . __( 'yrs', 'ma-lumiere-clinic' ) ); ?>)</span><?php endif; ?></dd></div>
				<div><dt><?php esc_html_e( 'Gender', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( $patient['gender'] ? ucfirst( $patient['gender'] ) : '—' ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Registered', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( ml_date( $patient['registration_date'], 'd M Y' ) ); ?></dd></div>
				<?php if ( ! empty( $patient['wp_user_id'] ) ) : ?>
					<div><dt><?php esc_html_e( 'Portal user', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( get_userdata( (int) $patient['wp_user_id'] ) ? get_userdata( (int) $patient['wp_user_id'] )->user_login : '#' . (int) $patient['wp_user_id'] ); ?></dd></div>
				<?php endif; ?>
			</dl>
		</section>

		<section class="ml-clinic-panel">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Contact', 'ma-lumiere-clinic' ); ?></h2>
			<dl class="ml-clinic-dl">
				<div><dt><?php esc_html_e( 'Phone', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( $patient['phone'] ? $patient['phone'] : '—' ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Email', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( $patient['email'] ? $patient['email'] : '—' ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Address', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo wp_kses_post( nl2br( (string) $patient['address'] ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'City / State', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( trim( (string) $patient['city'] . ' ' . (string) $patient['state'] ) ?: '—' ); ?></dd></div>
				<?php if ( ! empty( $patient['pincode'] ) ) : ?>
					<div><dt><?php esc_html_e( 'Pincode', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( $patient['pincode'] ); ?></dd></div>
				<?php endif; ?>
			</dl>
			<?php if ( ! empty( $patient['emergency_contact_name'] ) || ! empty( $patient['emergency_contact_phone'] ) ) : ?>
				<h2 class="ml-settings-section__title"><?php esc_html_e( 'Emergency Contact', 'ma-lumiere-clinic' ); ?></h2>
				<dl class="ml-clinic-dl">
					<div><dt><?php esc_html_e( 'Name', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( $patient['emergency_contact_name'] ?: '—' ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Phone', 'ma-lumiere-clinic' ); ?></dt><dd><?php echo esc_html( $patient['emergency_contact_phone'] ?: '—' ); ?></dd></div>
				</dl>
			<?php endif; ?>
		</section>

		<section class="ml-clinic-panel">
			<h2 class="ml-settings-section__title"><?php esc_html_e( 'Record Summary', 'ma-lumiere-clinic' ); ?></h2>
			<div class="ml-clinic-summary">
				<?php foreach ( $summary_c as $label => $value ) : ?>
					<div class="ml-clinic-summary__item">
						<span class="ml-clinic-summary__label"><?php echo esc_html( $label ); ?></span>
						<strong class="ml-clinic-summary__value"><?php echo esc_html( is_float( $value ) ? ml_money( $value ) : $value ); ?></strong>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<?php if ( ! empty( $audit ) ) : ?>
			<section class="ml-clinic-panel">
				<h2 class="ml-settings-section__title"><?php esc_html_e( 'Recent Activity', 'ma-lumiere-clinic' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Action', 'ma-lumiere-clinic' ); ?></th>
							<th><?php esc_html_e( 'Detail', 'ma-lumiere-clinic' ); ?></th>
							<th><?php esc_html_e( 'When', 'ma-lumiere-clinic' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $audit as $entry ) : ?>
							<tr>
								<td><?php echo esc_html( $entry['action'] ); ?></td>
								<td><?php echo esc_html( $entry['description'] ); ?></td>
								<td><?php echo esc_html( ml_date( $entry['created_at'], 'd M Y H:i' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		<?php endif; ?>

		<?php if ( current_user_can( 'ml_view_visits' ) ) : ?>
			<section class="ml-clinic-panel">
				<h2 class="ml-settings-section__title"><?php esc_html_e( 'Visits', 'ma-lumiere-clinic' ); ?></h2>
				<?php if ( empty( $ml_visits ) ) : ?>
					<p class="ml-muted"><?php esc_html_e( 'No visits recorded yet.', 'ma-lumiere-clinic' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Number', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Details', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $ml_visits as $visit ) : ?>
								<tr>
									<td>
										<strong><?php echo esc_html( $visit['visit_number'] ? $visit['visit_number'] : '#' . (int) $visit['id'] ); ?></strong>
										<?php if ( ! empty( $visit['is_locked'] ) ) : ?><span class="ml-badge ml-badge--muted"><?php esc_html_e( 'Locked', 'ma-lumiere-clinic' ); ?></span><?php endif; ?>
									</td>
									<td><?php echo esc_html( ml_date( $visit['visit_date'], 'd M Y' ) ); ?></td>
									<td>
										<details>
											<summary><?php esc_html_e( 'View notes', 'ma-lumiere-clinic' ); ?></summary>
											<ul class="ml-clinic-expand__list">
												<?php if ( $visit['chief_complaint'] ) : ?><li><strong><?php esc_html_e( 'Complaint', 'ma-lumiere-clinic' ); ?>:</strong> <?php echo esc_html( $visit['chief_complaint'] ); ?></li><?php endif; ?>
												<?php if ( $visit['diagnosis'] ) : ?><li><strong><?php esc_html_e( 'Diagnosis', 'ma-lumiere-clinic' ); ?>:</strong> <?php echo esc_html( $visit['diagnosis'] ); ?></li><?php endif; ?>
												<?php if ( $visit['clinical_notes'] ) : ?><li><strong><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?>:</strong> <?php echo esc_html( $visit['clinical_notes'] ); ?></li><?php endif; ?>
												<?php if ( $visit['treatment_plan'] ) : ?><li><strong><?php esc_html_e( 'Plan', 'ma-lumiere-clinic' ); ?>:</strong> <?php echo esc_html( $visit['treatment_plan'] ); ?></li><?php endif; ?>
											</ul>
										</details>
									</td>
									<td><span class="ml-badge ml-badge--<?php echo esc_attr( 'open' === $visit['status'] ? 'warning' : 'success' ); ?>"><?php echo esc_html( ucfirst( $visit['status'] ) ); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<?php if ( $ml_manage_visits ) : ?>
					<details class="ml-clinic-add">
						<summary><?php esc_html_e( 'Record a visit', 'ma-lumiere-clinic' ); ?></summary>
					<form method="post" action="<?php echo esc_url( $ml_back_url ); ?>" class="ml-form">
						<input type="hidden" name="ml_action" value="create_visit" />
						<div class="ml-form-grid">
							<p class="ml-field">
								<label class="ml-field__label" for="ml-visit-date"><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?> <span class="ml-field__req">*</span></label>
								<input id="ml-visit-date" class="ml-control" type="date" name="visit_date" required value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-visit-complaint"><?php esc_html_e( 'Chief complaint', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-visit-complaint" class="ml-control" name="chief_complaint" rows="2"></textarea>
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-visit-diagnosis"><?php esc_html_e( 'Diagnosis', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-visit-diagnosis" class="ml-control" name="diagnosis" rows="2"></textarea>
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-visit-notes"><?php esc_html_e( 'Clinical notes', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-visit-notes" class="ml-control" name="clinical_notes" rows="2"></textarea>
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-visit-plan"><?php esc_html_e( 'Treatment plan', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-visit-plan" class="ml-control" name="treatment_plan" rows="2"></textarea>
							</p>
						</div>
						<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
						<p class="ml-form-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save visit', 'ma-lumiere-clinic' ); ?></button>
						</p>
					</form>
					</details>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( current_user_can( 'ml_view_prescriptions' ) ) : ?>
			<section class="ml-clinic-panel">
				<h2 class="ml-settings-section__title"><?php esc_html_e( 'Prescriptions', 'ma-lumiere-clinic' ); ?></h2>
				<?php if ( empty( $ml_transcripts ) ) : ?>
					<p class="ml-muted"><?php esc_html_e( 'No prescriptions yet.', 'ma-lumiere-clinic' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Number', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Items', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
								<th class="ml-col-actions"><?php esc_html_e( 'Actions', 'ma-lumiere-clinic' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $ml_transcripts as $rx ) :
								$rx_items = ML_Prescription_Repository::items( (int) $rx['id'] );
								$rx_final = 'final' === (string) $rx['status'];

								/*
								 * Outstanding stock for this prescription: lines tied
								 * to the catalogue that still owe units. A correction
								 * carries its own copy, so each version is counted
								 * separately and only the issued row can be drawn.
								 */
								$rx_outstanding = 0;
								$rx_dispensed   = 0;
								foreach ( $rx_items as $ml_item ) {
									if ( empty( $ml_item['medicine_id'] ) || (int) $ml_item['quantity'] <= 0 ) {
										continue;
									}
									$rx_outstanding += max( 0, (int) $ml_item['quantity'] - (int) $ml_item['dispensed_quantity'] );
									$rx_dispensed   += (int) $ml_item['dispensed_quantity'];
								}
								?>
								<tr>
									<td><strong><?php echo esc_html( $rx_final ? $rx['prescription_number'] : __( '— (draft)', 'ma-lumiere-clinic' ) ); ?></strong></td>
									<td><?php echo esc_html( ml_date( $rx['prescription_date'], 'd M Y' ) ); ?></td>
									<td>
										<details>
											<summary><?php echo esc_html( sprintf( _n( '%d item', '%d items', count( $rx_items ), 'ma-lumiere-clinic' ), count( $rx_items ) ) ); ?></summary>
											<ul class="ml-clinic-expand__list">
												<?php foreach ( $rx_items as $item ) : ?>
													<li>
														<strong><?php echo esc_html( $item['medicine_name'] ); ?></strong>
														<?php if ( (int) $item['quantity'] > 0 ) : ?>
															<span class="ml-badge ml-badge--muted">
																<?php
																echo esc_html(
																	sprintf(
																		/* translators: 1: dispensed units, 2: ordered units. */
																		__( '%1$d/%2$d dispensed', 'ma-lumiere-clinic' ),
																		(int) $item['dispensed_quantity'],
																		(int) $item['quantity']
																	)
																);
																?>
															</span>
														<?php endif; ?>
														<?php if ( $item['dosage'] ) : ?> &mdash; <?php echo esc_html( $item['dosage'] ); ?><?php endif; ?>
														<?php if ( $item['frequency'] ) : ?> &middot; <?php echo esc_html( $item['frequency'] ); ?><?php endif; ?>
														<?php if ( $item['duration'] ) : ?> &middot; <?php echo esc_html( $item['duration'] ); ?><?php endif; ?>
													</li>
												<?php endforeach; ?>
											</ul>
										</details>
									</td>
									<td>
										<span class="ml-badge ml-badge--<?php echo esc_attr( $rx_final ? 'success' : 'warning' ); ?>"><?php echo esc_html( ucfirst( $rx['status'] ) ); ?></span>
										<?php if ( $rx_final && $rx_dispensed > 0 ) : ?>
											<span class="ml-badge ml-badge--muted">
												<?php
												echo esc_html(
													sprintf(
														/* translators: %d: dispensed units. */
														__( '%d dispensed', 'ma-lumiere-clinic' ),
														$rx_dispensed
													)
												);
												?>
											</span>
										<?php endif; ?>
									</td>
									<td class="ml-col-actions">
										<?php if ( $rx_final ) : ?>
											<a class="button button-small" target="_blank" rel="noopener"
												href="<?php echo esc_url( add_query_arg( array( 'action' => 'ml_prescription_pdf', 'id' => (int) $rx['id'], '_wpnonce' => $ml_rx_pdf_nonce ), admin_url( 'admin-ajax.php' ) ) ); ?>"><?php esc_html_e( 'PDF', 'ma-lumiere-clinic' ); ?></a>
											<?php if ( $ml_stock_ops && $rx_outstanding > 0 ) : ?>
												<form method="post" class="ml-form-inline">
													<input type="hidden" name="ml_action" value="dispense_rx" />
													<input type="hidden" name="rx_id" value="<?php echo esc_attr( (int) $rx['id'] ); ?>" />
													<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
													<button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Dispense the outstanding units from stock? This is recorded in the stock ledger.', 'ma-lumiere-clinic' ) ); ?>');">
														<?php
														echo esc_html(
															sprintf(
																/* translators: %d: units. */
																__( 'Dispense %d', 'ma-lumiere-clinic' ),
																$rx_outstanding
															)
														);
														?>
													</button>
												</form>
											<?php endif; ?>
											<?php if ( $ml_stock_ops && $rx_dispensed > 0 ) : ?>
												<form method="post" class="ml-form-inline">
													<input type="hidden" name="ml_action" value="return_rx" />
													<input type="hidden" name="rx_id" value="<?php echo esc_attr( (int) $rx['id'] ); ?>" />
													<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
													<button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Return the dispensed units to their original batches?', 'ma-lumiere-clinic' ) ); ?>');"><?php esc_html_e( 'Return', 'ma-lumiere-clinic' ); ?></button>
												</form>
											<?php endif; ?>
											<?php if ( $ml_manage_rx ) : ?>
												<form method="post" class="ml-form-inline">
													<input type="hidden" name="ml_action" value="correct" />
													<input type="hidden" name="rx_id" value="<?php echo esc_attr( (int) $rx['id'] ); ?>" />
													<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
													<button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Open a correction draft?', 'ma-lumiere-clinic' ) ); ?>');"><?php esc_html_e( 'Correct', 'ma-lumiere-clinic' ); ?></button>
												</form>
											<?php endif; ?>
										<?php elseif ( $ml_manage_rx ) : ?>
											<form method="post" class="ml-form-inline">
												<input type="hidden" name="ml_action" value="finalize" />
												<input type="hidden" name="rx_id" value="<?php echo esc_attr( (int) $rx['id'] ); ?>" />
												<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
												<button class="button button-small button-primary" onclick="return confirm('<?php echo esc_js( __( 'Finalize and issue this prescription?', 'ma-lumiere-clinic' ) ); ?>');"><?php esc_html_e( 'Finalize', 'ma-lumiere-clinic' ); ?></button>
											</form>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<?php if ( $ml_manage_rx ) : ?>
					<details class="ml-clinic-add">
						<summary><?php esc_html_e( 'Add prescription (draft)', 'ma-lumiere-clinic' ); ?></summary>
					<form method="post" action="<?php echo esc_url( $ml_back_url ); ?>" class="ml-form">
						<input type="hidden" name="ml_action" value="add_prescription" />
						<div class="ml-form-grid">
							<p class="ml-field">
								<label class="ml-field__label" for="ml-rx-date"><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?> <span class="ml-field__req">*</span></label>
								<input id="ml-rx-date" class="ml-control" type="date" name="prescription_date" required value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-rx-diagnosis"><?php esc_html_e( 'Diagnosis', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-rx-diagnosis" class="ml-control" name="rx_diagnosis" rows="2"></textarea>
							</p>
						</div>

						<p class="ml-field__label"><?php esc_html_e( 'Medicines', 'ma-lumiere-clinic' ); ?></p>
						<div class="ml-form-repeat__head" aria-hidden="true">
							<span><?php esc_html_e( 'Catalogue medicine', 'ma-lumiere-clinic' ); ?></span>
							<span><?php esc_html_e( 'Qty', 'ma-lumiere-clinic' ); ?></span>
							<span><?php esc_html_e( 'Medicine name', 'ma-lumiere-clinic' ); ?></span>
							<span><?php esc_html_e( 'Dosage', 'ma-lumiere-clinic' ); ?></span>
							<span><?php esc_html_e( 'Frequency', 'ma-lumiere-clinic' ); ?></span>
							<span><?php esc_html_e( 'Duration', 'ma-lumiere-clinic' ); ?></span>
						</div>
					<?php for ( $ml_row = 0; $ml_row < 5; $ml_row++ ) : ?>
						<div class="ml-form-repeat ml-clinic-itemsrow">
							<select name="items[<?php echo esc_attr( $ml_row ); ?>][medicine_id]" class="ml-control">
								<option value="0"><?php esc_html_e( 'Free-text medicine', 'ma-lumiere-clinic' ); ?></option>
								<?php foreach ( $ml_medicine_options as $ml_mid => $ml_mname ) : ?>
									<option value="<?php echo esc_attr( $ml_mid ); ?>"><?php echo esc_html( $ml_mname ); ?></option>
								<?php endforeach; ?>
							</select>
							<input class="ml-control" type="number" name="items[<?php echo esc_attr( $ml_row ); ?>][quantity]" min="0" step="1" value="0" title="<?php esc_attr_e( 'Units to dispense for a catalogue medicine', 'ma-lumiere-clinic' ); ?>" />
							<input class="ml-control" type="text" name="items[<?php echo esc_attr( $ml_row ); ?>][medicine_name]" placeholder="<?php esc_attr_e( 'Medicine name', 'ma-lumiere-clinic' ); ?>" />
							<input class="ml-control" type="text" name="items[<?php echo esc_attr( $ml_row ); ?>][dosage]" placeholder="<?php esc_attr_e( 'Dosage', 'ma-lumiere-clinic' ); ?>" />
							<input class="ml-control" type="text" name="items[<?php echo esc_attr( $ml_row ); ?>][frequency]" placeholder="<?php esc_attr_e( 'Frequency', 'ma-lumiere-clinic' ); ?>" />
							<input class="ml-control" type="text" name="items[<?php echo esc_attr( $ml_row ); ?>][duration]" placeholder="<?php esc_attr_e( 'Duration', 'ma-lumiere-clinic' ); ?>" />
						</div>
					<?php endfor; ?>
					<?php if ( ! empty( $ml_medicine_options ) ) : ?>
						<p class="ml-field__hint">
							<?php esc_html_e( 'Choosing a catalogue medicine and a quantity lets stock be dispensed from a batch; free-text lines are printed only.', 'ma-lumiere-clinic' ); ?>
						</p>
					<?php endif; ?>
						<div class="ml-form-grid">
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-rx-advice"><?php esc_html_e( 'Advice', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-rx-advice" class="ml-control" name="advice" rows="2"></textarea>
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-rx-followup"><?php esc_html_e( 'Follow-up date', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-rx-followup" class="ml-control" type="date" name="follow_up_date" />
							</p>
						</div>
						<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
						<p class="ml-form-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save draft', 'ma-lumiere-clinic' ); ?></button>
						</p>
					</form>
					</details>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( current_user_can( 'ml_view_visits' ) ) : ?>
			<section class="ml-clinic-panel">
				<h2 class="ml-settings-section__title"><?php esc_html_e( 'Treatment Sessions', 'ma-lumiere-clinic' ); ?></h2>
				<?php if ( empty( $ml_sessions ) ) : ?>
					<p class="ml-muted"><?php esc_html_e( 'No treatment sessions yet.', 'ma-lumiere-clinic' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( '#', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Type', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $ml_sessions as $session ) :
								$session_labels = ML_Treatment_Session_Repository::status_labels();
								$session_status = isset( $session_labels[ $session['status'] ] ) ? $session_labels[ $session['status'] ] : ucfirst( $session['status'] );
								?>
								<tr>
									<td><?php echo esc_html( (int) $session['session_number'] ); ?></td>
									<td>
										<?php echo esc_html( $session['session_type'] ? $session['session_type'] : ( $session['treatment_name'] ? $session['treatment_name'] : '—' ) ); ?>
										<?php if ( ! empty( $session['is_locked'] ) ) : ?><span class="ml-badge ml-badge--muted"><?php esc_html_e( 'Locked', 'ma-lumiere-clinic' ); ?></span><?php endif; ?>
									</td>
									<td><?php echo esc_html( $session['scheduled_date'] ? ml_date( $session['scheduled_date'], 'd M Y' ) : '—' ); ?></td>
									<td>
										<span class="ml-badge ml-badge--<?php echo esc_attr( 'completed' === $session['status'] ? 'success' : ( 'cancelled' === $session['status'] ? 'muted' : 'warning' ) ); ?>"><?php echo esc_html( $session_status ); ?></span>
										<?php if ( $ml_manage_visits && empty( $session['is_locked'] ) ) : ?>
											<form method="post" class="ml-form-inline">
												<input type="hidden" name="ml_action" value="session_status" />
												<input type="hidden" name="session_id" value="<?php echo esc_attr( (int) $session['id'] ); ?>" />
												<input type="hidden" name="session_status" value="<?php echo esc_attr( 'completed' === $session['status'] ? 'rec' : 'completed' ); ?>" />
												<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
												<button class="button button-small"><?php echo esc_html( 'completed' === $session['status'] ? __( 'Reopen', 'ma-lumiere-clinic' ) : __( 'Complete', 'ma-lumiere-clinic' ) ); ?></button>
											</form>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<?php if ( $ml_manage_visits ) : ?>
					<details class="ml-clinic-add">
						<summary><?php esc_html_e( 'Recommend a session', 'ma-lumiere-clinic' ); ?></summary>
					<form method="post" action="<?php echo esc_url( $ml_back_url ); ?>" class="ml-form">
						<input type="hidden" name="ml_action" value="add_session" />
						<div class="ml-form-grid">
							<p class="ml-field">
								<label class="ml-field__label" for="ml-session-type"><?php esc_html_e( 'Session type', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-session-type" class="ml-control" type="text" name="session_type" />
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-session-date"><?php esc_html_e( 'Scheduled date', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-session-date" class="ml-control" type="date" name="scheduled_date" />
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-session-notes"><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-session-notes" class="ml-control" name="session_notes" rows="2"></textarea>
							</p>
						</div>
						<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
						<p class="ml-form-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Add session', 'ma-lumiere-clinic' ); ?></button>
						</p>
					</form>
					</details>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( current_user_can( 'ml_view_followups' ) ) : ?>
			<section class="ml-clinic-panel">
				<h2 class="ml-settings-section__title"><?php esc_html_e( 'Follow-ups', 'ma-lumiere-clinic' ); ?></h2>
				<?php if ( empty( $ml_followups ) ) : ?>
					<p class="ml-muted"><?php esc_html_e( 'No follow-ups scheduled.', 'ma-lumiere-clinic' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Reason', 'ma-lumiere-clinic' ); ?></th>
								<th><?php esc_html_e( 'Status', 'ma-lumiere-clinic' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $ml_followups as $followup ) :
								$fu_done = in_array( (string) $followup['status'], array( 'completed', 'cancelled' ), true );
								?>
								<tr>
									<td><?php echo esc_html( ml_date( $followup['followup_date'], 'd M Y' ) ); ?></td>
									<td><?php echo esc_html( $followup['reason'] ? $followup['reason'] : '—' ); ?></td>
									<td>
										<span class="ml-badge ml-badge--<?php echo esc_attr( 'upcoming' === $followup['status'] ? 'warning' : ( 'completed' === $followup['status'] ? 'success' : 'muted' ) ); ?>"><?php echo esc_html( ucfirst( $followup['status'] ) ); ?></span>
										<?php if ( $ml_manage_fu && ! $fu_done ) : ?>
											<form method="post" class="ml-form-inline">
												<input type="hidden" name="ml_action" value="followup_status" />
												<input type="hidden" name="followup_id" value="<?php echo esc_attr( (int) $followup['id'] ); ?>" />
												<input type="hidden" name="fu_status" value="completed" />
												<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
												<button class="button button-small"><?php esc_html_e( 'Complete', 'ma-lumiere-clinic' ); ?></button>
											</form>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<?php if ( $ml_manage_fu ) : ?>
					<details class="ml-clinic-add">
						<summary><?php esc_html_e( 'Schedule a follow-up', 'ma-lumiere-clinic' ); ?></summary>
					<form method="post" action="<?php echo esc_url( $ml_back_url ); ?>" class="ml-form">
						<input type="hidden" name="ml_action" value="add_followup" />
						<div class="ml-form-grid">
							<p class="ml-field">
								<label class="ml-field__label" for="ml-fu-date"><?php esc_html_e( 'Date', 'ma-lumiere-clinic' ); ?> <span class="ml-field__req">*</span></label>
								<input id="ml-fu-date" class="ml-control" type="date" name="followup_date" required value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-fu-reason"><?php esc_html_e( 'Reason', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-fu-reason" class="ml-control" type="text" name="followup_reason" />
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-fu-notes"><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-fu-notes" class="ml-control" name="followup_notes" rows="2"></textarea>
							</p>
						</div>
						<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
						<p class="ml-form-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Schedule', 'ma-lumiere-clinic' ); ?></button>
						</p>
					</form>
					</details>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	<?php if ( $ml_view_photos ) : ?>
			<section class="ml-clinic-panel">
				<h2 class="ml-settings-section__title"><?php esc_html_e( 'Photos', 'ma-lumiere-clinic' ); ?></h2>
				<?php
				$ml_recent_photos = ML_Photo_Repository::list(
					array(
						'patient_id' => $ml_patient_id,
						'per_page'   => 6,
					)
				)['items'];
				?>
				<?php if ( empty( $ml_recent_photos ) ) : ?>
					<p class="ml-muted"><?php esc_html_e( 'No photos yet.', 'ma-lumiere-clinic' ); ?></p>
				<?php else : ?>
					<div class="ml-photo-grid">
						<?php foreach ( $ml_recent_photos as $ml_photo ) : ?>
							<figure class="ml-photo-card">
								<a href="<?php echo esc_url( ML_Photo_Repository::serve_url( (int) $ml_photo['id'] ) ); ?>" target="_blank" rel="noopener">
									<img src="<?php echo esc_url( ML_Photo_Repository::serve_url( (int) $ml_photo['id'] ) ); ?>" alt="<?php echo esc_attr( $ml_photo['photo_type'] ); ?>" loading="lazy" />
								</a>
								<figcaption>
									<span class="ml-badge ml-badge--<?php echo esc_attr( 'after' === $ml_photo['photo_type'] ? 'success' : ( 'before' === $ml_photo['photo_type'] ? 'warning' : 'muted' ) ); ?>"><?php echo esc_html( ucfirst( $ml_photo['photo_type'] ) ); ?></span>
									<span class="ml-muted"><?php echo esc_html( ml_date( $ml_photo['photo_date'], 'd M Y' ) ); ?></span>
								</figcaption>
								<?php if ( $ml_manage_photos ) : ?>
									<form method="post" class="ml-form-inline ml-photo-card__actions">
										<input type="hidden" name="ml_action" value="delete_photo" />
										<input type="hidden" name="photo_id" value="<?php echo esc_attr( (int) $ml_photo['id'] ); ?>" />
										<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
										<button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Delete this photo permanently?', 'ma-lumiere-clinic' ) ); ?>');"><?php esc_html_e( 'Delete', 'ma-lumiere-clinic' ); ?></button>
									</form>
								<?php endif; ?>
							</figure>
						<?php endforeach; ?>
					</div>
					<p>
						<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-photos', 'patient_id' => $ml_patient_id ), $ml_base ) ); ?>"><?php esc_html_e( 'Open full gallery', 'ma-lumiere-clinic' ); ?></a>
					</p>
				<?php endif; ?>
				<?php if ( $ml_manage_photos ) : ?>
					<details class="ml-clinic-add">
						<summary><?php esc_html_e( 'Upload a photo', 'ma-lumiere-clinic' ); ?></summary>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( $ml_back_url ); ?>" class="ml-form">
						<input type="hidden" name="ml_action" value="add_photo" />
						<div class="ml-form-grid">
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-photo-file"><?php esc_html_e( 'Photo file (JPEG, PNG or WebP)', 'ma-lumiere-clinic' ); ?> <span class="ml-field__req">*</span></label>
								<input id="ml-photo-file" class="ml-control" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required />
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-photo-type"><?php esc_html_e( 'Type', 'ma-lumiere-clinic' ); ?></label>
								<select id="ml-photo-type" class="ml-control" name="photo_type">
									<?php foreach ( ML_Photo_Repository::types() as $ml_opt ) : ?>
										<option value="<?php echo esc_attr( $ml_opt ); ?>"><?php echo esc_html( ucfirst( $ml_opt ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-photo-area"><?php esc_html_e( 'Body area', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-photo-area" class="ml-control" type="text" name="body_area" />
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-photo-date"><?php esc_html_e( 'Photo date', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-photo-date" class="ml-control" type="date" name="photo_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-photo-notes"><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-photo-notes" class="ml-control" name="photo_notes" rows="2"></textarea>
							</p>
						</div>
						<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
						<p class="ml-form-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Upload photo', 'ma-lumiere-clinic' ); ?></button>
						</p>
					</form>
					</details>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</div>
</div>