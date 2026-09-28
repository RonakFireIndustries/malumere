<?php
/**
 * Inventory admin controller: routes the "Inventory" screen between the
 * catalogue, batch, movement and alert views, the medicine/batch forms, and
 * handles every CSRF-protected stock action.
 *
 * Capability model:
 *   ml_view_inventory    — read the catalogue, batches and ledger
 *   ml_manage_inventory  — add/edit medicines, receive stock, adjust, write
 *                          off expired stock and dispense
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Inventory_Controller {

	/**
	 * Page slug for the inventory screen.
	 *
	 * @var string
	 */
	const PAGE = 'ml-clinic-inventory';

	/**
	 * Values handed to the forms after a failed save (retry state).
	 *
	 * @var array
	 */
	public static $form_data = array();

	/**
	 * Field-level validation errors (field => message).
	 *
	 * @var array
	 */
	public static $form_errors = array();

	/**
	 * Current notice value from ?notice=.
	 *
	 * @var string
	 */
	public static $notice = '';

	/**
	 * Extra message shown under a notice (e.g. a dispense summary).
	 *
	 * @var string
	 */
	public static $notice_detail = '';

	/**
	 * Handle the inventory admin screen.
	 *
	 * @return void
	 */
	public static function handle() {
		if ( ! current_user_can( 'ml_view_inventory' ) ) {
			wp_die( esc_html__( 'You are not allowed to view inventory.', 'ma-lumiere-clinic' ), 403 );
		}

		self::$notice        = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
		self::$notice_detail = isset( $_GET['detail'] ) ? sanitize_text_field( wp_unslash( $_GET['detail'] ) ) : '';

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		if ( isset( $_POST['ml_action'] ) ) {
			self::handle_post();
			return;
		}

		switch ( $action ) {
			case 'new_medicine':
				self::render_medicine_form( 0 );
				return;
			case 'edit_medicine':
				self::render_medicine_form( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
				return;
			case 'receive':
				self::render_batch_form( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
				return;
			case 'edit_batch':
				self::render_batch_form( isset( $_GET['batch'] ) ? absint( $_GET['batch'] ) : 0, true );
				return;
			case 'stockcard':
				self::render_stockcard( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
				return;
		}

		$view = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'medicines';
		self::render_list( $view );
	}

	/**
	 * Process a posted stock or catalogue action.
	 *
	 * @return void
	 */
	private static function handle_post() {
		$action = sanitize_key( wp_unslash( $_POST['ml_action'] ) );

		if ( ! ML_Security::verify_nonce() ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'ma-lumiere-clinic' ), 403 );
		}
		if ( ! current_user_can( 'ml_manage_inventory' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage inventory.', 'ma-lumiere-clinic' ), 403 );
		}

		switch ( $action ) {
			case 'save_medicine':
				self::save_medicine();
				return;
			case 'receive_stock':
				self::save_batch();
				return;
			case 'save_batch':
				self::update_batch();
				return;
			case 'adjust_stock':
				self::do_adjust();
				return;
			case 'write_off_expired':
				self::do_write_off_expired();
				return;
			case 'dispense_prescription':
				self::do_dispense();
				return;
			case 'return_stock':
				self::do_return();
				return;
			case 'set_batch_status':
				self::do_batch_status();
				return;
			case 'delete_medicine':
				self::do_delete_medicine();
				return;
		}

		wp_die( esc_html__( 'Unknown inventory action.', 'ma-lumiere-clinic' ), 400 );
	}

	/**
	 * Create or update a catalogue medicine.
	 *
	 * @return void
	 */
	private static function save_medicine() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$input = isset( $_POST['medicine'] ) ? wp_unslash( $_POST['medicine'] ) : array();
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$result = ( $id > 0 )
			? ML_Medicine_Repository::update( $id, $input )
			: ML_Medicine_Repository::create( $input );

		if ( is_wp_error( $result ) ) {
			self::render_medicine_form_error( $id, $input, $result );
			return;
		}

		self::redirect( ( $id > 0 ) ? 'updated' : 'created', absint( $result ) );
	}

	/**
	 * Receive new stock into a fresh batch.
	 *
	 * @return void
	 */
	private static function save_batch() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$input = isset( $_POST['batch'] ) ? wp_unslash( $_POST['batch'] ) : array();
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$result = ML_Inventory_Service::receive( $input );

		if ( is_wp_error( $result ) ) {
			self::render_batch_form_error( 0, $input, $result );
			return;
		}

		$medicine_id = absint( $input['medicine_id'] );
		self::redirect( 'received', $medicine_id );
	}

	/**
	 * Update batch metadata.
	 *
	 * @return void
	 */
	private static function update_batch() {
		$batch_id = isset( $_POST['batch_id'] ) ? absint( $_POST['batch_id'] ) : 0;
		$existing = ML_Medicine_Batch_Repository::get( $batch_id );
		if ( ! $existing ) {
			self::redirect( 'not_found' );
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$input = isset( $_POST['batch'] ) ? wp_unslash( $_POST['batch'] ) : array();
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$input['medicine_id'] = $existing['medicine_id'];

		$result = ML_Medicine_Batch_Repository::update( $batch_id, $input );

		if ( is_wp_error( $result ) ) {
			self::render_batch_form_error( $batch_id, $input, $result, true );
			return;
		}

		self::redirect( 'batch_updated', (int) $existing['medicine_id'] );
	}

	/**
	 * Apply a manual stock change.
	 *
	 * @return void
	 */
	private static function do_adjust() {
		$batch_id = isset( $_POST['batch_id'] ) ? absint( $_POST['batch_id'] ) : 0;
		$quantity = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 0;
		$type     = isset( $_POST['movement_type'] ) ? sanitize_key( wp_unslash( $_POST['movement_type'] ) ) : 'adjustment';
		$notes    = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		// Only removals and corrections reach this form; a "stock in" entry
		// must go through Receive stock so it carries a lot and an expiry.
		$allowed = array( 'adjustment', 'wastage', 'expired' );
		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'adjustment';
		}

		$batch = ML_Medicine_Batch_Repository::get( $batch_id );
		if ( ! $batch ) {
			self::redirect( 'not_found' );
			return;
		}

		$result = ML_Inventory_Service::adjust( $batch_id, $quantity, $type, $notes );

		if ( is_wp_error( $result ) ) {
			self::redirect( 'adjust_failed', (int) $batch['medicine_id'], $result->get_error_message() );
			return;
		}

		self::redirect( 'adjusted', (int) $batch['medicine_id'] );
	}

	/**
	 * Write off every expired unit still held in an active batch.
	 *
	 * @return void
	 */
	private static function do_write_off_expired() {
		$result = ML_Inventory_Service::write_off_expired();
		self::redirect( 'expired_written_off', 0, sprintf( '%d|%d', (int) $result['batches'], (int) $result['units'] ) );
	}

	/**
	 * Dispense stock for a finalized prescription.
	 *
	 * @return void
	 */
	private static function do_dispense() {
		$prescription_id = isset( $_POST['prescription_id'] ) ? absint( $_POST['prescription_id'] ) : 0;
		$prescription    = ML_Prescription_Repository::get( $prescription_id );

		if ( ! $prescription ) {
			self::redirect( 'not_found' );
			return;
		}
		if ( ! ML_Security::can_access_patient( (int) $prescription['patient_id'] ) ) {
			wp_die( esc_html__( 'You are not allowed to access this prescription.', 'ma-lumiere-clinic' ), 403 );
		}

		$result = ML_Inventory_Service::dispense_prescription( $prescription_id );

		if ( is_wp_error( $result ) ) {
			$detail = $result->get_error_message();
			$data   = $result->get_error_data();
			if ( is_array( $data ) && ! empty( $data['shortages'] ) ) {
				$detail .= ' ' . implode( ' ', (array) $data['shortages'] );
			}
			self::redirect( 'dispense_failed', (int) $prescription['patient_id'], $detail );
			return;
		}

		self::redirect( 'dispensed', (int) $prescription['patient_id'], (string) (int) $result['dispensed'] );
	}

	/**
	 * Return dispensed stock to inventory.
	 *
	 * @return void
	 */
	private static function do_return() {
		$prescription_id = isset( $_POST['prescription_id'] ) ? absint( $_POST['prescription_id'] ) : 0;
		$prescription    = ML_Prescription_Repository::get( $prescription_id );

		if ( ! $prescription ) {
			self::redirect( 'not_found' );
			return;
		}
		if ( ! ML_Security::can_access_patient( (int) $prescription['patient_id'] ) ) {
			wp_die( esc_html__( 'You are not allowed to access this prescription.', 'ma-lumiere-clinic' ), 403 );
		}

		$result = ML_Inventory_Service::return_from_prescription( $prescription_id );

		if ( is_wp_error( $result ) ) {
			self::redirect( 'return_failed', (int) $prescription['patient_id'], $result->get_error_message() );
			return;
		}

		self::redirect( 'returned', (int) $prescription['patient_id'], (string) (int) $result['returned'] );
	}

	/**
	 * Change a batch status (quarantine / dispose / reactivate).
	 *
	 * @return void
	 */
	private static function do_batch_status() {
		$batch_id = isset( $_POST['batch_id'] ) ? absint( $_POST['batch_id'] ) : 0;
		$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$batch    = ML_Medicine_Batch_Repository::get( $batch_id );

		if ( ! $batch ) {
			self::redirect( 'not_found' );
			return;
		}

		$result = ML_Medicine_Batch_Repository::set_status( $batch_id, $status );

		if ( is_wp_error( $result ) ) {
			self::redirect( 'batch_status_failed', (int) $batch['medicine_id'], $result->get_error_message() );
			return;
		}

		self::redirect( 'batch_status', (int) $batch['medicine_id'] );
	}

	/**
	 * Remove a medicine that has no stock history.
	 *
	 * @return void
	 */
	private static function do_delete_medicine() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		$result = ML_Medicine_Repository::delete( $id );

		if ( is_wp_error( $result ) ) {
			self::redirect( 'delete_failed', 0, $result->get_error_message() );
			return;
		}

		self::redirect( 'deleted' );
	}

	/**
	 * Re-render the medicine form with validation errors.
	 *
	 * @param int   $id     Medicine ID (0 for new).
	 * @param array $input  Raw submitted values.
	 * @param mixed $result WP_Error.
	 *
	 * @return void
	 */
	private static function render_medicine_form_error( $id, array $input, $result ) {
		self::$form_data   = ML_Medicine_Repository::sanitize( $input, $id > 0 ? ( ML_Medicine_Repository::get( $id ) ?: array() ) : array() );
		self::$form_errors = self::errors_from( $result );
		self::$form_data['id'] = $id;
		self::render_medicine_form( $id );
	}

	/**
	 * Re-render the batch form with validation errors.
	 *
	 * @param int   $batch_id Batch ID (0 when receiving new stock).
	 * @param array $input    Raw submitted values.
	 * @param mixed $result   WP_Error.
	 * @param bool  $is_edit  Whether the form is editing an existing batch.
	 *
	 * @return void
	 */
	private static function render_batch_form_error( $batch_id, array $input, $result, $is_edit = false ) {
		self::$form_data   = ML_Medicine_Batch_Repository::sanitize( $input );
		self::$form_errors = self::errors_from( $result );
		$batch             = $batch_id > 0 ? ML_Medicine_Batch_Repository::get( $batch_id ) : null;
		$medicine_id       = $batch ? (int) $batch['medicine_id'] : absint( isset( $input['medicine_id'] ) ? $input['medicine_id'] : 0 );
		$medicine          = $medicine_id ? ML_Medicine_Repository::get( $medicine_id ) : null;

		include ML_CLINIC_PATH . 'admin/views/batch-form.php';
	}

	/**
	 * Extract a field => message map from a WP_Error.
	 *
	 * @param WP_Error $result Error object.
	 *
	 * @return array
	 */
	private static function errors_from( $result ) {
		$data = $result->get_error_data();
		if ( is_array( $data ) && ! empty( $data['errors'] ) && is_array( $data['errors'] ) ) {
			return $data['errors'];
		}
		return array( 'form' => $result->get_error_message() );
	}

	/**
	 * Redirect back to the inventory screen with a notice.
	 *
	 * @param string $notice Notice key.
	 * @param int    $tab_id Optional medicine to open the stock card for.
	 * @param string $detail Optional detail string.
	 *
	 * @return void
	 */
	private static function redirect( $notice, $tab_id = 0, $detail = '' ) {
		$args = array(
			'page'   => self::PAGE,
			'notice' => $notice,
		);

		if ( $tab_id > 0 && in_array( $notice, array( 'created', 'updated', 'received', 'adjusted', 'batch_updated', 'batch_status' ), true ) ) {
			$args['action'] = 'stockcard';
			$args['id']     = $tab_id;
		}
		if ( '' !== $detail ) {
			$args['detail'] = $detail;
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the medicine create/edit form.
	 *
	 * @param int $id Medicine ID (0 for new).
	 *
	 * @return void
	 */
	private static function render_medicine_form( $id ) {
		if ( ! current_user_can( 'ml_manage_inventory' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage inventory.', 'ma-lumiere-clinic' ), 403 );
		}

		$medicine = array();
		$is_edit  = $id > 0;

		if ( $is_edit && empty( self::$form_data ) ) {
			$medicine = ML_Medicine_Repository::get( $id );
			if ( ! $medicine ) {
				self::redirect( 'not_found' );
				return;
			}
		}

		include ML_CLINIC_PATH . 'admin/views/medicine-form.php';
	}

	/**
	 * Render the receive/edit batch form.
	 *
	 * @param int  $medicine_id Medicine to receive into (0 = picker).
	 * @param bool $is_edit     Editing an existing batch.
	 * @return void
	 */
	private static function render_batch_form( $medicine_id, $is_edit = false ) {
		if ( ! current_user_can( 'ml_manage_inventory' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage inventory.', 'ma-lumiere-clinic' ), 403 );
		}

		$batch    = null;
		$medicine = null;

		if ( $is_edit ) {
			$batch = ML_Medicine_Batch_Repository::get( $medicine_id );
			if ( ! $batch ) {
				self::redirect( 'not_found' );
				return;
			}
			$medicine = ML_Medicine_Repository::get( (int) $batch['medicine_id'] );
		} elseif ( $medicine_id > 0 ) {
			$medicine = ML_Medicine_Repository::get( $medicine_id );
			if ( ! $medicine ) {
				self::redirect( 'not_found' );
				return;
			}
		}

		$is_edit = (bool) $batch;

		include ML_CLINIC_PATH . 'admin/views/batch-form.php';
	}

	/**
	 * Render a single medicine's stock card.
	 *
	 * @param int $id Medicine ID.
	 *
	 * @return void
	 */
	private static function render_stockcard( $id ) {
		$card = ML_Inventory_Service::stockcard( $id );
		if ( ! $card ) {
			self::redirect( 'not_found' );
			return;
		}

		$medicine = $card['medicine'];
		$view     = 'stockcard';
		$tabs     = self::tabs();

		include ML_CLINIC_PATH . 'admin/views/inventory.php';
	}

	/**
	 * Render the catalogue / batches / movements / alerts screen.
	 *
	 * @param string $view Requested tab.
	 *
	 * @return void
	 */
	private static function render_list( $view ) {
		$tabs = self::tabs();
		if ( ! isset( $tabs[ $view ] ) ) {
			$view = 'medicines';
		}

		$alerts = ML_Inventory_Service::alerts();
		$summary = ML_Stock_Movement_Repository::summary();

		include ML_CLINIC_PATH . 'admin/views/inventory.php';
	}

	/**
	 * Inventory tabs.
	 *
	 * @return array<string,string>
	 */
	public static function tabs() {
		return array(
			'medicines' => __( 'Medicines', 'ma-lumiere-clinic' ),
			'batches'   => __( 'Batches', 'ma-lumiere-clinic' ),
			'movements' => __( 'Stock Ledger', 'ma-lumiere-clinic' ),
			'alerts'    => __( 'Alerts', 'ma-lumiere-clinic' ),
		);
	}
}
