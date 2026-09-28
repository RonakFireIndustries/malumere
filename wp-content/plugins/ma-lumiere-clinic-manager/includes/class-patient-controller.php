<?php
/**
 * Patient admin controller: routes the "Patients" page between the list,
 * the single-record view and the create/edit form, and handles the
 * CSRF-protected form submit.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Patient_Controller {

	/**
	 * Values handed to the views after a failed save (retry state).
	 *
	 * @var array
	 */
	public static $form_data = array();

	/**
	 * Field-level validation errors (field => message) for retry display.
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
	 * Handle the patients admin screen.
	 *
	 * @return void
	 */
	public static function handle() {
		if ( ! current_user_can( 'ml_view_patients' ) ) {
			wp_die( esc_html__( 'You are not allowed to view patients.', 'ma-lumiere-clinic' ), 403 );
		}
		self::$notice = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';

		if ( isset( $_POST['ml_action'] ) && 'save_patient' === wp_unslash( $_POST['ml_action'] ) ) {
			self::handle_save();
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'delete' === $action ) {
			self::handle_delete();
			return;
		}

		$view = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		if ( $view ) {
			self::render_record( $view );
			return;
		}

		if ( 'new' === $action ) {
			self::render_form( 0 );
			return;
		}

		if ( 'edit' === $action ) {
			self::render_form( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
			return;
		}

		self::render_list();
	}

	/**
	 * Process the create/update form submission.
	 *
	 * @return void
	 */
	private static function handle_save() {
		if ( ! ML_Security::verify_nonce() ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'ma-lumiere-clinic' ), 403 );
		}

		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$caps = ( $id > 0 ) ? 'ml_edit_patients' : 'ml_create_patients';
		if ( ! current_user_can( $caps ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'ma-lumiere-clinic' ), 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$input = isset( $_POST['patient'] ) ? wp_unslash( $_POST['patient'] ) : array();
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$result = ( $id > 0 ) ? ML_Patient_Repository::update( $id, $input ) : ML_Patient_Repository::create( $input );

		if ( is_wp_error( $result ) ) {
			self::$form_data   = ML_Patient_Repository::sanitize( $input, ML_Patient_Repository::get( $id ) ? ML_Patient_Repository::get( $id ) : array() );
			$error_data        = $result->get_error_data();
			self::$form_errors = ( is_array( $error_data ) && isset( $error_data['errors'] ) ) ? $error_data['errors'] : array( 'form' => $result->get_error_message() );
			self::$form_data['id'] = $id;

			if ( $id > 0 ) {
				self::render_form( $id );
			} else {
				self::render_form( 0 );
			}
			return;
		}

		$notice = ( $id > 0 ) ? 'updated' : 'saved';
		wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-patients', 'view' => $result, 'notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Process a delete request (SuperAdmin only, nonce-protected).
	 *
	 * @return void
	 */
	private static function handle_delete() {
		if ( ! ML_Security::verify_nonce() || ! current_user_can( 'ml_manage_clinic' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'ma-lumiere-clinic' ), 403 );
		}
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$result = ML_Patient_Repository::delete( $id );

		$notice = is_wp_error( $result ) ? 'not_found' : 'deleted';
		wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-patients', 'notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the searchable, paginated patient list.
	 *
	 * @return void
	 */
	private static function render_list() {
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$status = isset( $_GET['ml_status'] ) ? sanitize_key( wp_unslash( $_GET['ml_status'] ) ) : '';

		$result = ML_Patient_Repository::list(
			array(
				'page'     => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
				'per_page' => 20,
				'search'   => $search,
				'status'   => $status,
				'orderby'  => 'last_name',
				'order'    => 'ASC',
			)
		);

		include ML_CLINIC_PATH . 'admin/views/patients.php';
	}

	/**
	 * Render a single patient's record + cap-filtered summary.
	 *
	 * @param int $id Patient ID.
	 *
	 * @return void
	 */
	private static function render_record( $id ) {
		$patient = ML_Patient_Repository::get( $id );
		if ( ! $patient ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-patients', 'notice' => 'not_found' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$all       = ML_Patient_Repository::record_summary( $id );
		$summary_c = array();
		if ( current_user_can( 'ml_view_appointments' ) ) {
			$summary_c[ __( 'Appointments', 'ma-lumiere-clinic' ) ] = (int) $all['appointments'];
		}
		if ( current_user_can( 'ml_view_billing' ) ) {
			$summary_c[ __( 'Invoices', 'ma-lumiere-clinic' ) ]        = (int) $all['invoices'];
			$summary_c[ __( 'Open invoices', 'ma-lumiere-clinic' ) ]   = (int) $all['open_invoices'];
			$summary_c[ __( 'Unpaid balance', 'ma-lumiere-clinic' ) ]  = (float) $all['unpaid_balance'];
			$summary_c[ __( 'Total paid', 'ma-lumiere-clinic' ) ]      = (float) $all['total_paid'];
		}
		if ( current_user_can( 'ml_view_visits' ) ) {
			$summary_c[ __( 'Visits', 'ma-lumiere-clinic' ) ] = (int) $all['visits'];
		}
		if ( current_user_can( 'ml_view_prescriptions' ) ) {
			$summary_c[ __( 'Prescriptions', 'ma-lumiere-clinic' ) ] = (int) $all['prescriptions'];
		}
		if ( current_user_can( 'ml_view_followups' ) ) {
			$summary_c[ __( 'Follow-ups', 'ma-lumiere-clinic' ) ] = (int) $all['followups'];
		}
		if ( current_user_can( 'ml_view_patient_photos' ) ) {
			$summary_c[ __( 'Photos', 'ma-lumiere-clinic' ) ] = (int) $all['photos'];
		}

		$audit = array();
		if ( current_user_can( 'ml_view_audit_logs' ) ) {
			$audit = ML_Audit_Log::query( array( 'entity_type' => 'patient', 'entity_id' => $id, 'limit' => 15 ) );
		}

		include ML_CLINIC_PATH . 'admin/views/patient-view.php';
	}

	/**
	 * Render the create (id 0) or edit form.
	 *
	 * @param int $id Patient ID (0 for new).
	 *
	 * @return void
	 */
	private static function render_form( $id ) {
		$patient = array();
		$is_edit = $id > 0;

		if ( $is_edit && empty( self::$form_data ) ) {
			$patient = ML_Patient_Repository::get( $id );
			if ( ! $patient ) {
				wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-patients', 'notice' => 'not_found' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		$form_id = isset( self::$form_data['id'] ) ? absint( self::$form_data['id'] ) : $id;
		$caps    = $is_edit ? 'ml_edit_patients' : 'ml_create_patients';
		if ( ! current_user_can( $caps ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'ma-lumiere-clinic' ), 403 );
		}

		include ML_CLINIC_PATH . 'admin/views/patient-form.php';
	}
}