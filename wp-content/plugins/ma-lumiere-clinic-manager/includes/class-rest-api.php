<?php
/**
 * REST API facade.
 *
 * Namespace: ml-clinic/v1
 *
 * Phase 3 registers a secure route skeleton. Every route has a permission
 * callback (auth + capability), and args are sanitized. Business responses
 * are implemented by later phases; current handlers return structured
 * foundation responses so client contracts are stable.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Rest_Api {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NS = 'ml-clinic/v1';

	/**
	 * Route definitions.
	 *
	 * @return array
	 */
	public static function routes() {
		return array(

			array(
				'route'  => '/patients',
				'method' => 'GET',
				'cap'    => 'ml_view_patients',
				'permission' => 'patient_list',
				'handler' => 'get_patients',
			),
			array(
				'route'  => '/patients',
				'method' => 'POST',
				'cap'    => 'ml_create_patients',
				'permission' => 'patient_create',
				'handler' => 'create_patient',
			),
			array(
				'route'  => '/patients/(?P<id>[\d]+)',
				'method' => 'GET',
				'cap'    => 'ml_view_patients',
				'permission' => 'patient_access',
				'handler' => 'get_patient',
			),
			array(
				'route'  => '/patients/(?P<id>[\d]+)',
				'method' => 'PUT',
				'cap'    => 'ml_edit_patients',
				'permission' => 'patient_write',
				'handler' => 'update_patient',
			),
			array(
				'route'  => '/patients/(?P<id>[\d]+)',
				'method' => 'DELETE',
				'cap'    => 'ml_manage_clinic',
				'permission' => 'patient_delete',
				'handler' => 'delete_patient',
			),
			array(
				'route'  => '/patients/(?P<id>[\d]+)/records',
				'method' => 'GET',
				'cap'    => 'ml_view_medical_records',
				'permission' => 'medical_records',
				'handler' => 'get_patient_records',
			),
			array(
				'route'  => '/appointments/availability',
				'method' => 'GET',
				'cap'    => '',
				'permission' => 'public',
				'handler' => 'get_booking_availability',
			),
			array(
				'route'  => '/appointments/book',
				'method' => 'POST',
				'cap'    => '',
				'permission' => 'public',
				'handler' => 'create_public_booking',
			),
			array(
				'route'  => '/appointments',
				'method' => 'GET',
				'cap'    => 'ml_view_appointments',
				'permission' => 'appointments',
				'handler' => 'get_appointments',
			),
			array(
				'route'  => '/appointments',
				'method' => 'POST',
				'cap'    => 'ml_manage_appointments',
				'permission' => 'appointment_manage',
				'handler' => 'create_appointment',
			),
			array(
				'route'  => '/appointments/(?P<id>[\d]+)/reschedule',
				'method' => 'POST',
				'cap'    => 'ml_manage_appointments',
				'permission' => 'appointment_manage',
				'handler' => 'reschedule_appointment',
			),
			array(
				'route'  => '/appointments/(?P<id>[\d]+)/cancel',
				'method' => 'POST',
				'cap'    => 'ml_manage_appointments',
				'permission' => 'appointment_manage',
				'handler' => 'cancel_appointment',
			),
			array(
				'route'  => '/appointments/(?P<id>[\d]+)/checkin',
				'method' => 'POST',
				'cap'    => 'ml_manage_appointments',
				'permission' => 'appointment_manage',
				'handler' => 'appointment_quick_action',
			),
			array(
				'route'  => '/appointments/(?P<id>[\d]+)/complete',
				'method' => 'POST',
				'cap'    => 'ml_manage_appointments',
				'permission' => 'appointment_manage',
				'handler' => 'appointment_quick_action',
			),
			array(
				'route'  => '/appointments/(?P<id>[\d]+)/no-show',
				'method' => 'POST',
				'cap'    => 'ml_manage_appointments',
				'permission' => 'appointment_manage',
				'handler' => 'appointment_quick_action',
			),
			array(
				'route'  => '/appointments/(?P<id>[\d]+)',
				'method' => 'GET',
				'cap'    => 'ml_view_appointments',
				'permission' => 'appointment_access',
				'handler' => 'get_appointment',
			),
			array(
				'route'  => '/appointments/(?P<id>[\d]+)',
				'method' => 'PUT',
				'cap'    => 'ml_manage_appointments',
				'permission' => 'appointment_manage',
				'handler' => 'update_appointment',
			),
			// Visits.
			array(
				'route'  => '/visits',
				'method' => 'GET',
				'cap'    => 'ml_view_visits',
				'permission' => 'visits_list',
				'handler' => 'get_visits',
			),
			array(
				'route'  => '/visits',
				'method' => 'POST',
				'cap'    => 'ml_manage_visits',
				'permission' => 'visits_manage',
				'handler' => 'create_visit',
			),
			array(
				'route'  => '/visits/(?P<id>[\d]+)',
				'method' => 'GET',
				'cap'    => 'ml_view_visits',
				'permission' => 'visit_access',
				'handler' => 'get_visit',
			),
			array(
				'route'  => '/visits/(?P<id>[\d]+)',
				'method' => 'PUT',
				'cap'    => 'ml_manage_visits',
				'permission' => 'visit_access',
				'handler' => 'update_visit',
			),
			array(
				'route'  => '/visits/(?P<id>[\d]+)/lock',
				'method' => 'POST',
				'cap'    => 'ml_manage_visits',
				'permission' => 'visit_access',
				'handler' => 'lock_visit',
			),

			// Prescriptions.
			array(
				'route'  => '/prescriptions',
				'method' => 'GET',
				'cap'    => 'ml_view_prescriptions',
				'permission' => 'prescriptions_list',
				'handler' => 'get_prescriptions',
			),
			array(
				'route'  => '/prescriptions',
				'method' => 'POST',
				'cap'    => 'ml_manage_prescriptions',
				'permission' => 'prescriptions_manage',
				'handler' => 'create_prescription',
			),
			array(
				'route'  => '/prescriptions/(?P<id>[\d]+)',
				'method' => 'GET',
				'cap'    => 'ml_view_prescriptions',
				'permission' => 'prescription_access',
				'handler' => 'get_prescription',
			),
			array(
				'route'  => '/prescriptions/(?P<id>[\d]+)',
				'method' => 'PUT',
				'cap'    => 'ml_manage_prescriptions',
				'permission' => 'prescription_access',
				'handler' => 'update_prescription',
			),
			array(
				'route'  => '/prescriptions/(?P<id>[\d]+)/finalize',
				'method' => 'POST',
				'cap'    => 'ml_manage_prescriptions',
				'permission' => 'prescription_access',
				'handler' => 'finalize_prescription',
			),
			array(
				'route'  => '/prescriptions/(?P<id>[\d]+)/correct',
				'method' => 'POST',
				'cap'    => 'ml_manage_prescriptions',
				'permission' => 'prescription_access',
				'handler' => 'correct_prescription',
			),
			array(
				'route'  => '/prescriptions/(?P<id>[\d]+)/revisions',
				'method' => 'GET',
				'cap'    => 'ml_view_prescriptions',
				'permission' => 'prescription_access',
				'handler' => 'get_prescription_revisions',
			),

			// Inventory.
			array(
				'route'  => '/medicines',
				'method' => 'GET',
				'cap'    => 'ml_view_inventory',
				'permission' => 'inventory_read',
				'handler' => 'get_medicines',
			),
			array(
				'route'  => '/medicines',
				'method' => 'POST',
				'cap'    => 'ml_manage_inventory',
				'permission' => 'inventory_manage',
				'handler' => 'create_medicine',
			),
			array(
				'route'  => '/medicines/(?P<id>[\d]+)',
				'method' => 'GET',
				'cap'    => 'ml_view_inventory',
				'permission' => 'inventory_read',
				'handler' => 'get_medicine',
			),
			array(
				'route'  => '/medicines/(?P<id>[\d]+)',
				'method' => 'PUT',
				'cap'    => 'ml_manage_inventory',
				'permission' => 'inventory_manage',
				'handler' => 'update_medicine',
			),
			array(
				'route'  => '/medicines/(?P<id>[\d]+)/batches',
				'method' => 'GET',
				'cap'    => 'ml_view_inventory',
				'permission' => 'inventory_read',
				'handler' => 'get_medicine_batches',
			),
			array(
				'route'  => '/medicines/(?P<id>[\d]+)/stockcard',
				'method' => 'GET',
				'cap'    => 'ml_view_inventory',
				'permission' => 'inventory_read',
				'handler' => 'get_medicine_stockcard',
			),
			array(
				'route'  => '/batches',
				'method' => 'POST',
				'cap'    => 'ml_manage_inventory',
				'permission' => 'inventory_manage',
				'handler' => 'receive_stock',
			),
			array(
				'route'  => '/batches/(?P<batch_id>[\d]+)',
				'method' => 'PUT',
				'cap'    => 'ml_manage_inventory',
				'permission' => 'inventory_manage',
				'handler' => 'update_batch',
			),
			array(
				'route'  => '/batches/(?P<batch_id>[\d]+)/adjust',
				'method' => 'POST',
				'cap'    => 'ml_manage_inventory',
				'permission' => 'inventory_manage',
				'handler' => 'adjust_stock',
			),
			array(
				'route'  => '/inventory/movements',
				'method' => 'GET',
				'cap'    => 'ml_view_inventory',
				'permission' => 'inventory_read',
				'handler' => 'get_stock_movements',
			),
			array(
				'route'  => '/inventory/summary',
				'method' => 'GET',
				'cap'    => 'ml_view_inventory',
				'permission' => 'inventory_read',
				'handler' => 'get_inventory_summary',
			),
			array(
				'route'  => '/inventory/alerts',
				'method' => 'GET',
				'cap'    => 'ml_view_inventory',
				'permission' => 'inventory_read',
				'handler' => 'get_inventory_alerts',
			),
			array(
				'route'  => '/inventory/expired/write-off',
				'method' => 'POST',
				'cap'    => 'ml_manage_inventory',
				'permission' => 'inventory_manage',
				'handler' => 'write_off_expired',
			),
			array(
				'route'  => '/prescriptions/(?P<id>[\d]+)/dispense',
				'method' => 'POST',
				'cap'    => 'ml_manage_inventory',
				'permission' => 'prescription_access',
				'handler' => 'dispense_prescription_stock',
			),
			array(
				'route'  => '/prescriptions/(?P<id>[\d]+)/return-stock',
				'method' => 'POST',
				'cap'    => 'ml_manage_inventory',
				'permission' => 'prescription_access',
				'handler' => 'return_prescription_stock',
			),

			// Treatment sessions (planning recorded at a visit).
			array(
				'route'  => '/sessions',
				'method' => 'GET',
				'cap'    => 'ml_view_visits',
				'permission' => 'sessions_list',
				'handler' => 'get_sessions',
			),
			array(
				'route'  => '/sessions',
				'method' => 'POST',
				'cap'    => 'ml_manage_visits',
				'permission' => 'sessions_manage',
				'handler' => 'create_session',
			),
			array(
				'route'  => '/patients/(?P<id>[\d]+)/sessions/plan',
				'method' => 'POST',
				'cap'    => 'ml_manage_visits',
				'permission' => 'patient_access',
				'handler' => 'create_session_plan',
			),
			array(
				'route'  => '/sessions/(?P<id>[\d]+)',
				'method' => 'PUT',
				'cap'    => 'ml_manage_visits',
				'permission' => 'session_access',
				'handler' => 'update_session',
			),

			// Follow-ups.
			array(
				'route'  => '/followups',
				'method' => 'GET',
				'cap'    => 'ml_view_followups',
				'permission' => 'followups_list',
				'handler' => 'get_followups',
			),
			array(
				'route'  => '/followups',
				'method' => 'POST',
				'cap'    => 'ml_manage_followups',
				'permission' => 'followups_manage',
				'handler' => 'create_followup',
			),
			array(
				'route'  => '/patients/(?P<id>[\d]+)/followups',
				'method' => 'GET',
				'cap'    => 'ml_view_followups',
				'permission' => 'patient_access',
				'handler' => 'get_patient_followups',
			),
			array(
				'route'  => '/followups/(?P<id>[\d]+)/status',
				'method' => 'POST',
				'cap'    => 'ml_manage_followups',
				'permission' => 'followup_access',
				'handler' => 'update_followup_status',
			),
			// Billing + payments.
			array(
				'route'  => '/invoices',
				'method' => 'GET',
				'cap'    => 'ml_view_billing',
				'permission' => 'invoices_list',
				'handler' => 'get_invoices',
			),
			array(
				'route'  => '/invoices',
				'method' => 'POST',
				'cap'    => 'ml_manage_billing',
				'permission' => 'invoices_manage',
				'handler' => 'create_invoice',
			),
			array(
				'route'  => '/invoices/(?P<id>[\d]+)',
				'method' => 'GET',
				'cap'    => 'ml_view_billing',
				'permission' => 'invoice_access',
				'handler' => 'get_invoice',
			),
			array(
				'route'  => '/invoices/(?P<id>[\d]+)/payments',
				'method' => 'POST',
				'cap'    => 'ml_record_payments',
				'permission' => 'payment_record',
				'handler' => 'create_invoice_payment',
			),
			array(
				'route'  => '/invoices/(?P<id>[\d]+)/razorpay-order',
				'method' => 'POST',
				'cap'    => 'ml_manage_billing',
				'permission' => 'invoice_manage',
				'handler' => 'create_razorpay_order',
			),
			array(
				'route'  => '/invoices/(?P<id>[\d]+)/razorpay-verify',
				'method' => 'POST',
				'cap'    => '',
				'permission' => 'razorpay_verify',
				'handler' => 'verify_razorpay_payment',
			),
array(
			'route'  => '/payments',
			'method' => 'GET',
			'cap'    => 'ml_view_payments',
			'permission' => 'payments_list',
			'handler' => 'get_payments',
		),
		array(
			'route'  => '/portal/invoices/(?P<id>[\d]+)/razorpay-order',
			'method' => 'POST',
			'cap'    => '',
			'permission' => 'portal_self_order',
			'handler' => 'create_portal_razorpay_order',
		),
			array(
				'route'  => '/reports/summary',
				'method' => 'GET',
				'cap'    => 'ml_view_reports',
				'permission' => 'reports',
				'handler' => 'get_report_summary',
			),

			// Patient photos.
			array(
				'route'  => '/photos',
				'method' => 'GET',
				'cap'    => 'ml_view_patient_photos',
				'permission' => 'photos_list',
				'handler' => 'get_photos',
			),
			array(
				'route'  => '/photos',
				'method' => 'POST',
				'cap'    => 'ml_manage_patient_photos',
				'permission' => 'photos_manage',
				'handler' => 'create_photo',
			),
			array(
				'route'  => '/photos/(?P<id>[\d]+)/file',
				'method' => 'GET',
				'cap'    => '',
				'permission' => 'photo_serve',
				'handler' => 'serve_photo',
			),
			array(
				'route'  => '/photos/(?P<id>[\d]+)',
				'method' => 'DELETE',
				'cap'    => 'ml_manage_patient_photos',
				'permission' => 'photo_access',
				'handler' => 'delete_photo',
			),
		);
	}

	/**
	 * Register all routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		require_once ML_CLINIC_PATH . 'api/routes.php';

		$extra = apply_filters( 'ml_clinic_rest_routes', array() );
		$all   = array_merge( self::routes(), is_array( $extra ) ? $extra : array() );

		foreach ( $all as $definition ) {
			register_rest_route(
				self::NS,
				$definition['route'],
				array(
					'methods'             => $definition['method'],
					'callback'            => array( __CLASS__, $definition['handler'] ),
					'permission_callback' => array( __CLASS__, 'permission_' . $definition['permission'] ),
					'args'                => self::route_args( $definition ),
				)
			);
		}
	}

	/**
	 * Args schema per route (idempotent + sanitized).
	 *
	 * @param array $definition Route definition.
	 *
	 * @return array
	 */
	private static function route_args( array $definition ) {
		$args = array();
		if ( false !== strpos( $definition['route'], '(?P<id>' ) ) {
			$args['id'] = array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => static function ( $value ) {
					return $value > 0;
				},
			);
		}
		if ( '/patients' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 100, max( 1, absint( $value ) ) );
				},
			);
			$args['search'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['orderby'] = array(
				'type'              => 'string',
				'default'           => 'last_name',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['order'] = array(
				'type'              => 'string',
				'default'           => 'ASC',
				'sanitize_callback' => 'sanitize_key',
			);
		}
		if ( false !== strpos( $definition['route'], '(?P<batch_id>' ) ) {
			$args['batch_id'] = array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => static function ( $value ) {
					return $value > 0;
				},
			);
		}
		$ml_inventory_list = array(
			'/medicines',
			'/medicines/(?P<id>[\d]+)/batches',
			'/inventory/movements',
		);
		if ( 'GET' === $definition['method'] && in_array( $definition['route'], $ml_inventory_list, true ) ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 200, max( 1, absint( $value ) ) );
				},
			);
			$args['search'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/medicines' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['form'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['category'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['stock'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
		}
		if ( '/medicines/(?P<id>[\d]+)/batches' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
		}
		if ( in_array( $definition['route'], array( '/inventory/movements', '/inventory/summary' ), true ) && 'GET' === $definition['method'] ) {
			$args['from'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['to'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/inventory/movements' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['medicine_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['batch_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['movement_type'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
		}
		if ( '/appointments' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 100, max( 1, absint( $value ) ) );
				},
			);
			$args['from'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['to'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['doctor'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['search'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/appointments/availability' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['treatment_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['doctor_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['date'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/appointments/book' === $definition['route'] && 'POST' === $definition['method'] ) {
			$args['_nonce'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/visits' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 100, max( 1, absint( $value ) ) );
				},
			);
			$args['patient_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['doctor'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['from'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['to'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['search'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/prescriptions' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 100, max( 1, absint( $value ) ) );
				},
			);
			$args['patient_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['visit_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['from'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['to'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['search'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/followups' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 100, max( 1, absint( $value ) ) );
				},
			);
			$args['patient_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['overdue'] = array(
				'type'              => 'boolean',
				'default'           => false,
			);
			$args['from'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['to'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['search'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/sessions' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['patient_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['treatment_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
		}
		if ( '/visits/(?P<id>[\d]+)/lock' === $definition['route'] && 'POST' === $definition['method'] ) {
			$args['locked'] = array(
				'type'              => 'boolean',
				'default'           => true,
			);
		}
		if ( '/followups/(?P<id>[\d]+)/status' === $definition['route'] && 'POST' === $definition['method'] ) {
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
		}
		if ( '/patients/(?P<id>[\d]+)/sessions/plan' === $definition['route'] && 'POST' === $definition['method'] ) {
			$args['treatment_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
		}
		if ( '/patients/(?P<id>[\d]+)/followups' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['overdue'] = array(
				'type'              => 'boolean',
				'default'           => false,
			);
		}
		if ( '/photos' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 100, max( 1, absint( $value ) ) );
				},
			);
			$args['patient_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['photo_type'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['search'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/photos' === $definition['route'] && 'POST' === $definition['method'] ) {
			$args['patient_id'] = array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			);
			$args['photo_type'] = array(
				'type'              => 'string',
				'default'           => 'before',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['visit_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['body_area'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['photo_date'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['notes'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			);
		}
		if ( '/photos/(?P<id>[\d]+)/file' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['token'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/invoices' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 100, max( 1, absint( $value ) ) );
				},
			);
			$args['patient_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['from'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['to'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['search'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/invoices' === $definition['route'] && 'POST' === $definition['method'] ) {
			$args['patient_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['invoice_date'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['due_date'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['items'] = array(
				'type'              => 'array',
				'default'           => array(),
			);
		}
		if ( '/payments' === $definition['route'] && 'GET' === $definition['method'] ) {
			$args['page'] = array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			);
			$args['per_page'] = array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => static function ( $value ) {
					return min( 100, max( 1, absint( $value ) ) );
				},
			);
			$args['invoice_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['patient_id'] = array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			);
			$args['method'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['status'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['from'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['to'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}
		if ( '/invoices/(?P<id>[\d]+)/payments' === $definition['route'] && 'POST' === $definition['method'] ) {
			$args['amount'] = array(
				'type'              => 'number',
				'default'           => 0,
			);
			$args['method'] = array(
				'type'              => 'string',
				'default'           => 'cash',
				'sanitize_callback' => 'sanitize_key',
			);
			$args['payment_date'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['transaction_id'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['notes'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			);
		}
		if ( '/invoices/(?P<id>[\d]+)/razorpay-verify' === $definition['route'] && 'POST' === $definition['method'] ) {
			$args['razorpay_order_id'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['razorpay_payment_id'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['razorpay_signature'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			);
			$args['captured_amount'] = array(
				'type'              => 'integer',
				'default'           => 0,
			);
			$args['captured_currency'] = array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			);
		}
		return $args;
	}

	/**
	 * Permission callbacks.
	 */

	public static function permission_patient_list() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_patients' ) );
	}

	public static function permission_patient_create() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_create_patients' ) );
	}

	public static function permission_patient_write( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_edit_patients' ) ) ) {
			return false;
		}
		return ML_Security::can_access_patient( (int) $request['id'] );
	}

	public static function permission_patient_delete( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ML_Security::can( 'ml_manage_clinic' ) ) {
			return false;
		}
		return ML_Security::can_access_patient( (int) $request['id'] );
	}

	public static function permission_patient_access( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		return ML_Security::can_access_patient( (int) $request['id'] );
	}

	public static function permission_medical_records( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_medical_records' ) ) ) {
			return false;
		}
		return ML_Security::can_access_patient( (int) $request['id'] );
	}

	public static function permission_visits_list() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_visits' ) );
	}

	public static function permission_visits_manage() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_visits' ) );
	}

	public static function permission_visit_access( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_visits' ) || ML_Security::can( 'ml_manage_visits' ) ) ) {
			return false;
		}
		$visit = ML_Visit_Repository::get( (int) $request['id'] );
		return is_array( $visit ) && ML_Security::can_access_patient( (int) $visit['patient_id'] );
	}

	public static function permission_prescriptions_list() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_prescriptions' ) );
	}

	public static function permission_prescriptions_manage() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_prescriptions' ) );
	}

	public static function permission_prescription_access( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_prescriptions' ) || ML_Security::can( 'ml_manage_prescriptions' ) ) ) {
			return false;
		}
		$row = ML_Prescription_Repository::get( (int) $request['id'] );
		return is_array( $row ) && ML_Security::can_access_patient( (int) $row['patient_id'] );
	}

	public static function permission_inventory_read() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_inventory' ) );
	}

	public static function permission_inventory_manage() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_inventory' ) );
	}

	public static function permission_sessions_list() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_visits' ) || ML_Security::can( 'ml_manage_visits' ) );
	}

	public static function permission_sessions_manage() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_visits' ) );
	}

	public static function permission_session_access( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_visits' ) || ML_Security::can( 'ml_manage_visits' ) ) ) {
			return false;
		}
		$row = ML_Treatment_Session_Repository::get( (int) $request['id'] );
		return is_array( $row ) && ML_Security::can_access_patient( (int) $row['patient_id'] );
	}

	public static function permission_followups_list() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_followups' ) );
	}

	public static function permission_followups_manage() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_followups' ) );
	}

	public static function permission_followup_access( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_followups' ) || ML_Security::can( 'ml_manage_followups' ) ) ) {
			return false;
		}
		$row = ML_Followup_Repository::get( (int) $request['id'] );
		return is_array( $row ) && ML_Security::can_access_patient( (int) $row['patient_id'] );
	}

	public static function permission_appointments() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_appointments' ) || ML_Security::can( 'ml_view_own_appointments' ) );
	}

	public static function permission_appointment_manage() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_appointments' ) );
	}

	public static function permission_appointment_access( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_appointments' ) || ML_Security::can( 'ml_view_appointments' ) ) {
			return true;
		}
		// View-only scope: own scheduling rows only.
		if ( ML_Security::can( 'ml_view_own_appointments' ) ) {
			$row = ML_Appointment_Repository::get( (int) $request['id'] );
			return is_array( $row ) && (int) $row['doctor_user_id'] === get_current_user_id();
		}
		return false;
	}

	public static function permission_public() {
		return true;
	}

	public static function permission_reports() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_reports' ) || ML_Security::can( 'ml_view_own_treatments' ) );
	}

	public static function permission_photos_list() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_patient_photos' ) );
	}

	public static function permission_photos_manage() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_patient_photos' ) );
	}

	public static function permission_photo_access( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_patient_photos' ) ) ) {
			return false;
		}
		$row = ML_Photo_Repository::get( (int) $request['id'] );
		return is_array( $row ) && ML_Security::can_access_patient( (int) $row['patient_id'] );
	}

	/**
	 * Photo file serving is either token-based (expiring signed URL, usable
	 * for <img> embedding) or staff-capability + record ownership.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return bool
	 */
	public static function permission_photo_serve( $request ) {
		$id = absint( $request['id'] );
		if ( ML_Security::verify_file_token( 'photo', $id, $request->get_param( 'token' ) ) ) {
			return true;
		}
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_patient_photos' ) || ML_Security::can( 'ml_manage_patient_photos' ) ) ) {
			return false;
		}
		$row = ML_Photo_Repository::get( $id );
		return is_array( $row ) && ML_Security::can_access_patient( (int) $row['patient_id'] );
	}

	public static function permission_invoices_list() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_billing' ) );
	}

	public static function permission_invoices_manage() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_billing' ) );
	}

	public static function permission_invoice_access( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_billing' ) || ML_Security::can( 'ml_manage_billing' ) ) ) {
			return false;
		}
		$invoice = ML_Invoice_Repository::get( (int) $request['id'] );
		return is_array( $invoice ) && ML_Security::can_access_patient( (int) $invoice['patient_id'] );
	}

	public static function permission_invoice_manage( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_manage_billing' ) ) ) {
			return false;
		}
		$invoice = ML_Invoice_Repository::get( (int) $request['id'] );
		return is_array( $invoice ) && ML_Security::can_access_patient( (int) $invoice['patient_id'] );
	}

	public static function permission_payments_list() {
		return ML_Security::require_login() && ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_view_payments' ) );
	}

	public static function permission_payment_record( $request ) {
		if ( ! ML_Security::require_login() ) {
			return false;
		}
		if ( ! ( ML_Security::can( 'ml_manage_clinic' ) || ML_Security::can( 'ml_record_payments' ) ) ) {
			return false;
		}
		$invoice = ML_Invoice_Repository::get( (int) $request['id'] );
		return is_array( $invoice ) && ML_Security::can_access_patient( (int) $invoice['patient_id'] );
	}

	/**
	 * Razorpay verification is called by the client-side checkout wrapper, so
	 * it is intentionally public. Authenticity is enforced inside the handler
	 * by signature verification before any state is touched.
	 *
	 * @return bool
	 */
	public static function permission_razorpay_verify() {
		return true;
	}

	/**
	 * Portal self-service Razorpay order: a valid portal session whose
	 * patient owns the invoice, plus the page-embedded anti-CSRF nonce.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return bool
	 */
	public static function permission_portal_self_order( $request ) {
		$patient = ML_Portal::current_patient();
		if ( ! is_array( $patient ) || 'active' !== (string) $patient['status'] ) {
			return false;
		}
		$invoice = ML_Invoice_Repository::get( (int) $request['id'] );
		if ( ! is_array( $invoice ) || (int) $invoice['patient_id'] !== (int) $patient['id'] ) {
			return false;
		}
		if ( 'paid' === (string) $invoice['payment_status'] || 'void' === (string) $invoice['payment_status'] ) {
			return false;
		}
		$nonce = (string) $request->get_header( 'X_ML_Portal' );
		return hash_equals( ML_Portal::rest_nonce( (int) $patient['id'] ), $nonce );
	}

	/**
	 * Handlers — Phase 3 scaffolding.
	 */

	public static function get_patients( $request ) {
		$result = ML_Patient_Repository::list(
			array(
				'page'     => absint( $request['page'] ),
				'per_page' => absint( $request['per_page'] ),
				'search'   => isset( $request['search'] ) ? (string) $request['search'] : '',
				'status'   => isset( $request['status'] ) ? (string) $request['status'] : '',
				'orderby'  => isset( $request['orderby'] ) ? (string) $request['orderby'] : 'last_name',
				'order'    => isset( $request['order'] ) ? (string) $request['order'] : 'ASC',
			)
		);
		return rest_ensure_response(
			array(
				'data' => $result['items'],
				'meta' => array(
					'total'    => $result['total'],
					'pages'    => $result['pages'],
					'page'     => $result['page'],
					'per_page' => $result['per_page'],
				),
			)
		);
	}

	public static function create_patient( $request ) {
		$result = ML_Patient_Repository::create( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'data'   => array( 'message' => $result->get_error_message() ),
					'errors' => ( isset( $result->get_error_data( 'ml_patient_invalid' )['errors'] ) ) ? $result->get_error_data( 'ml_patient_invalid' )['errors'] : array(),
				),
				400
			);
		}
		$patient = ML_Patient_Repository::get( $result );
		return new WP_REST_Response(
			array(
				'data' => $patient,
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	public static function get_patient( $request ) {
		$patient = ML_Patient_Repository::get( (int) $request['id'] );
		if ( ! $patient ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Patient not found.', 'ma-lumiere-clinic' ) ) ), 404 );
		}
		return rest_ensure_response( array( 'data' => $patient ) );
	}

	public static function update_patient( $request ) {
		$result = ML_Patient_Repository::update( (int) $request['id'], $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'data'   => array( 'message' => $result->get_error_message() ),
					'errors' => ( isset( $result->get_error_data( 'ml_patient_invalid' )['errors'] ) ) ? $result->get_error_data( 'ml_patient_invalid' )['errors'] : array(),
				),
				400
			);
		}
		$patient = ML_Patient_Repository::get( $result );
		return new WP_REST_Response(
			array(
				'data' => $patient,
				'meta' => array( 'updated' => true, 'changed' => true ),
			),
			200
		);
	}

	public static function delete_patient( $request ) {
		$result = ML_Patient_Repository::delete( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => $result->get_error_message() ) ), 404 );
		}
		return new WP_REST_Response(
			array(
				'data' => array( 'deleted' => true ),
				'meta' => array( 'id' => absint( $request['id'] ) ),
			),
			200
		);
	}

	public static function get_patient_records( $request ) {
		$patient = ML_Patient_Repository::get( (int) $request['id'] );
		if ( ! $patient ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Patient not found.', 'ma-lumiere-clinic' ) ) ), 404 );
		}
		return rest_ensure_response(
			array(
				'data' => array(
					'patient' => $patient,
					'summary' => ML_Patient_Repository::record_summary( (int) $request['id'] ),
				),
			)
		);
	}

	public static function get_appointments( $request ) {
		$result = ML_Appointment_Repository::list(
			array(
				'page'     => absint( $request['page'] ),
				'per_page' => absint( $request['per_page'] ),
				'from'     => isset( $request['from'] ) ? (string) $request['from'] : '',
				'to'       => isset( $request['to'] ) ? (string) $request['to'] : '',
				'status'   => isset( $request['status'] ) ? (string) $request['status'] : '',
				'doctor'   => isset( $request['doctor'] ) ? absint( $request['doctor'] ) : 0,
				'search'   => isset( $request['search'] ) ? (string) $request['search'] : '',
			)
		);
		return rest_ensure_response(
			array(
				'data' => $result['items'],
				'meta' => array(
					'total'    => $result['total'],
					'pages'    => $result['pages'],
					'page'     => $result['page'],
					'per_page' => $result['per_page'],
				),
			)
		);
	}

	public static function get_appointment( $request ) {
		$row = ML_Appointment_Repository::get( (int) $request['id'] );
		if ( ! $row ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Appointment not found.', 'ma-lumiere-clinic' ) ) ), 404 );
		}
		return rest_ensure_response( array( 'data' => $row ) );
	}

	public static function create_appointment( $request ) {
		$result = ML_Appointment_Service::create_admin( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::appointment_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Appointment_Repository::get( (int) $result ),
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	public static function update_appointment( $request ) {
		$result = ML_Appointment_Repository::update( (int) $request['id'], $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::appointment_error( $result, 400 );
		}
		return rest_ensure_response( array( 'data' => $result, 'meta' => array( 'updated' => true ) ) );
	}

	public static function reschedule_appointment( $request ) {
		$result = ML_Appointment_Service::reschedule(
			(int) $request['id'],
			isset( $request['date'] ) ? sanitize_text_field( (string) $request['date'] ) : '',
			isset( $request['time'] ) ? sanitize_text_field( (string) $request['time'] ) : ''
		);
		if ( is_wp_error( $result ) ) {
			return self::appointment_error( $result, 400 );
		}
		return new WP_REST_Response( array( 'data' => $result, 'meta' => array( 'rescheduled' => true ) ), 200 );
	}

	public static function cancel_appointment( $request ) {
		$result = ML_Appointment_Service::cancel( (int) $request['id'], isset( $request['reason'] ) ? sanitize_key( (string) $request['reason'] ) : 'other' );
		if ( is_wp_error( $result ) ) {
			return self::appointment_error( $result, 400 );
		}
		return rest_ensure_response( array( 'data' => $result, 'meta' => array( 'cancelled' => true ) ) );
	}

	/**
	 * check-in / complete / no-show dispatched by the route path.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public static function appointment_quick_action( $request ) {
		$path = (string) $request->get_route();
		$action = 'checkin';
		if ( false !== strpos( $path, '/complete' ) ) {
			$action = 'complete';
		} elseif ( false !== strpos( $path, '/no-show' ) ) {
			$action = 'no_show';
		}
		$result = ML_Appointment_Service::quick_action( (int) $request['id'], $action );
		if ( is_wp_error( $result ) ) {
			return self::appointment_error( $result, 400 );
		}
		return rest_ensure_response( array( 'data' => $result, 'meta' => array( 'action' => $action ) ) );
	}

	/**
	 * Public availability. Date omitted => open days; date given => slots.
	 * Output contains only safe fields (date/time/duration), never patient
	 * data or counts. Rate-limited per IP.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_booking_availability( $request ) {
		if ( ML_Appointment_Service::availability_rate_limited() ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Too many requests. Please wait a moment.', 'ma-lumiere-clinic' ) ) ), 429 );
		}

		$treatment_id = absint( $request['treatment_id'] );
		$doctor_id    = absint( $request['doctor_id'] );
		$date         = sanitize_text_field( (string) $request['date'] );

		if ( '' === $date ) {
			$days = ML_Availability_Service::open_days( $treatment_id, $doctor_id );
			return rest_ensure_response(
				array(
					'data' => array(
						'dates' => $days,
						'meta'  => array(
							'treatment' => $treatment_id ? ML_Treatment_Repository::get( $treatment_id ) : null,
							'doctor'    => ML_Availability_Service::resolve_doctor( $doctor_id ),
						),
					),
				)
			);
		}

		$slots = ML_Availability_Service::slots_for( $date, $treatment_id, $doctor_id );
		if ( ! ML_Appointment_Repository::is_valid_date( $date ) ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Invalid date.', 'ma-lumiere-clinic' ) ) ), 400 );
		}
		return rest_ensure_response(
			array(
				'data' => array(
					'date'  => $date,
					'slots' => $slots,
					'meta'  => array(
						'treatment' => $treatment_id ? ML_Treatment_Repository::get( $treatment_id ) : null,
						'doctor'    => ML_Availability_Service::resolve_doctor( $doctor_id ),
					),
				),
			)
		);
	}

	/**
	 * Public booking submission (six-step flow). Nonce + honeypot +
	 * idempotency + rate limits; the repository re-checks availability at
	 * write time.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_public_booking( $request ) {
		// Booking nonce travels in `_nonce`. The X-WP-Nonce header is the
		// core REST cookie-auth nonce (action wp_rest); only fall back to the
		// header when it is NOT a valid wp_rest nonce (e.g. earlier clients).
		$header = $request->get_header( 'X-WP-Nonce' );
		$nonce  = isset( $request['_nonce'] ) ? sanitize_text_field( (string) $request['_nonce'] ) : '';
		if ( '' === $nonce && $header && ! is_wp_error( wp_verify_nonce( is_scalar( $header ) ? (string) $header : '', 'wp_rest' ) ) ) {
			$nonce = is_scalar( $header ) ? (string) $header : '';
		}
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'ml_public_booking' ) ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Your session could not be verified. Please refresh and try again.', 'ma-lumiere-clinic' ) ) ), 403 );
		}

		$result = ML_Appointment_Service::book_public( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			$code   = $result->get_error_code();
			$status = 'ml_booking_limited' === $code ? 429 : 400;
			return self::appointment_error( $result, $status );
		}
		return new WP_REST_Response(
			array(
				'data' => $result,
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	/**
	 * Consistent error response for appointment-repository/service errors.
	 *
	 * @param WP_Error $error  Error.
	 * @param int      $status HTTP status.
	 *
	 * @return WP_REST_Response
	 */
	private static function appointment_error( WP_Error $error, $status = 400 ) {
		$error_data = $error->get_error_data();
		$payload    = array(
			'data' => array(
				'message' => $error->get_error_message(),
				'code'    => $error->get_error_code(),
			),
		);
		if ( is_array( $error_data ) && isset( $error_data['errors'] ) ) {
			$payload['data']['errors'] = $error_data['errors'];
		}
		return new WP_REST_Response( $payload, $status );
	}

	/**
	 * Consistent error response for repository/service errors.
	 *
	 * @param WP_Error $error  Error.
	 * @param int      $status HTTP status.
	 *
	 * @return WP_REST_Response
	 */
	private static function rest_error( WP_Error $error, $status = 400 ) {
		$error_data = $error->get_error_data();
		$payload    = array(
			'data' => array(
				'message' => $error->get_error_message(),
				'code'    => $error->get_error_code(),
			),
		);
		if ( is_array( $error_data ) && isset( $error_data['errors'] ) ) {
			$payload['data']['errors'] = $error_data['errors'];
		}
		return new WP_REST_Response( $payload, $status );
	}

	/**
	 * Paginated envelope shared by list handlers.
	 *
	 * @param array $result Repository list result.
	 *
	 * @return WP_REST_Response
	 */
	private static function paged_response( array $result ) {
		return rest_ensure_response(
			array(
				'data' => isset( $result['items'] ) ? $result['items'] : array(),
				'meta' => array(
					'total'    => isset( $result['total'] ) ? $result['total'] : 0,
					'pages'    => isset( $result['pages'] ) ? $result['pages'] : 1,
					'page'     => isset( $result['page'] ) ? $result['page'] : 1,
					'per_page' => isset( $result['per_page'] ) ? $result['per_page'] : 20,
				),
			)
		);
	}

	/**
	 * Visit handlers.
	 */

	public static function get_visits( $request ) {
		return self::paged_response(
			ML_Visit_Repository::list(
				array(
					'page'       => absint( $request['page'] ),
					'per_page'   => absint( $request['per_page'] ),
					'patient_id' => isset( $request['patient_id'] ) ? absint( $request['patient_id'] ) : 0,
					'doctor'     => isset( $request['doctor'] ) ? absint( $request['doctor'] ) : 0,
					'from'       => isset( $request['from'] ) ? (string) $request['from'] : '',
					'to'         => isset( $request['to'] ) ? (string) $request['to'] : '',
					'status'     => isset( $request['status'] ) ? (string) $request['status'] : '',
					'search'     => isset( $request['search'] ) ? (string) $request['search'] : '',
				)
			)
		);
	}

	public static function create_visit( $request ) {
		$result = ML_Visit_Repository::create( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Visit_Repository::get( (int) $result ),
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	public static function get_visit( $request ) {
		$visit = ML_Visit_Repository::get( (int) $request['id'] );
		if ( ! $visit ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Visit not found.', 'ma-lumiere-clinic' ) ) ), 404 );
		}
		return rest_ensure_response( array( 'data' => $visit ) );
	}

	public static function update_visit( $request ) {
		$note   = isset( $request['note'] ) ? sanitize_text_field( (string) $request['note'] ) : '';
		$result = ML_Visit_Repository::update( (int) $request['id'], $request->get_params(), $note );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response(
			array(
				'data' => ML_Visit_Repository::get( (int) $result ),
				'meta' => array( 'updated' => true ),
			)
		);
	}

	public static function lock_visit( $request ) {
		$lock   = isset( $request['locked'] ) ? (bool) $request['locked'] : true;
		$result = ML_Visit_Repository::set_locked( (int) $request['id'], $lock );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response(
			array(
				'data' => ML_Visit_Repository::get( (int) $request['id'] ),
				'meta' => array( 'locked' => (bool) $lock, 'action' => $lock ? 'lock' : 'unlock' ),
			)
		);
	}

	/**
	 * Prescription handlers.
	 */

	public static function get_prescriptions( $request ) {
		return self::paged_response(
			ML_Prescription_Repository::list(
				array(
					'page'       => absint( $request['page'] ),
					'per_page'   => absint( $request['per_page'] ),
					'patient_id' => isset( $request['patient_id'] ) ? absint( $request['patient_id'] ) : 0,
					'visit_id'   => isset( $request['visit_id'] ) ? absint( $request['visit_id'] ) : 0,
					'status'     => isset( $request['status'] ) ? (string) $request['status'] : '',
					'from'       => isset( $request['from'] ) ? (string) $request['from'] : '',
					'to'         => isset( $request['to'] ) ? (string) $request['to'] : '',
					'search'     => isset( $request['search'] ) ? (string) $request['search'] : '',
				)
			)
		);
	}

	public static function create_prescription( $request ) {
		$result = ML_Prescription_Service::create( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Prescription_Repository::get( (int) $result ),
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	public static function get_prescription( $request ) {
		$row = ML_Prescription_Repository::get( (int) $request['id'] );
		if ( ! $row ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Prescription not found.', 'ma-lumiere-clinic' ) ) ), 404 );
		}
		return rest_ensure_response(
			array(
				'data' => $row,
				'meta' => array( 'items' => ML_Prescription_Repository::items( (int) $request['id'] ) ),
			)
		);
	}

	public static function update_prescription( $request ) {
		$result = ML_Prescription_Service::update( (int) $request['id'], $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response(
			array(
				'data' => ML_Prescription_Repository::get( (int) $result ),
				'meta' => array( 'updated' => true ),
			)
		);
	}

	public static function finalize_prescription( $request ) {
		$result = ML_Prescription_Service::finalize( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => $result,
				'meta' => array( 'finalized' => true ),
			),
			200
		);
	}

	public static function correct_prescription( $request ) {
		$result = ML_Prescription_Service::correct( (int) $request['id'], $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Prescription_Repository::get( (int) $result ),
				'meta' => array( 'correction' => true ),
			),
			201
		);
	}

	public static function get_prescription_revisions( $request ) {
		return rest_ensure_response( array( 'data' => ML_Prescription_Repository::revisions( (int) $request['id'] ) ) );
	}

	/**
	 * Inventory handlers.
	 *
	 * Stock is never mutated by a bare number: every write goes through
	 * ML_Inventory_Service so the ledger, the audit trail and the optimistic
	 * stock guard stay in step.
	 */
	public static function get_medicines( $request ) {
		return self::paged_response(
			ML_Medicine_Repository::list(
				array(
					'page'     => absint( $request['page'] ),
					'per_page' => absint( $request['per_page'] ),
					'status'   => isset( $request['status'] ) ? (string) $request['status'] : '',
					'form'     => isset( $request['form'] ) ? (string) $request['form'] : '',
					'category' => isset( $request['category'] ) ? (string) $request['category'] : '',
					'stock'    => isset( $request['stock'] ) ? (string) $request['stock'] : '',
					'search'   => isset( $request['search'] ) ? (string) $request['search'] : '',
				)
			)
		);
	}

	public static function get_medicine( $request ) {
		$medicine = ML_Medicine_Repository::get( (int) $request['id'] );
		if ( ! $medicine ) {
			return self::rest_error( new WP_Error( 'ml_medicine_not_found', __( 'Medicine not found.', 'ma-lumiere-clinic' ) ), 404 );
		}
		return rest_ensure_response( array( 'data' => $medicine ) );
	}

	public static function create_medicine( $request ) {
		$result = ML_Medicine_Repository::create( self::inventory_params( $request ) );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Medicine_Repository::get( (int) $result ),
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	public static function update_medicine( $request ) {
		$result = ML_Medicine_Repository::update( (int) $request['id'], self::inventory_params( $request ) );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, is_wp_error( $result ) && 'ml_medicine_not_found' === $result->get_error_code() ? 404 : 400 );
		}
		return rest_ensure_response( array( 'data' => ML_Medicine_Repository::get( (int) $request['id'] ) ) );
	}

	public static function get_medicine_batches( $request ) {
		$result = ML_Medicine_Batch_Repository::list(
			array(
				'medicine_id' => (int) $request['id'],
				'status'      => isset( $request['status'] ) ? (string) $request['status'] : '',
				'per_page'    => min( 200, max( 1, absint( $request['per_page'] ) ) ),
			)
		);
		return self::paged_response( $result );
	}

	public static function get_medicine_stockcard( $request ) {
		$card = ML_Inventory_Service::stockcard( (int) $request['id'] );
		if ( is_wp_error( $card ) ) {
			return self::rest_error( $card, 404 );
		}
		return rest_ensure_response( array( 'data' => $card ) );
	}

	public static function receive_stock( $request ) {
		$result = ML_Inventory_Service::receive( self::inventory_params( $request ) );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Medicine_Batch_Repository::get( (int) $result ),
				'meta' => array( 'received' => true ),
			),
			201
		);
	}

	public static function update_batch( $request ) {
		$batch_id = (int) $request['batch_id'];
		$existing = ML_Medicine_Batch_Repository::get( $batch_id );
		if ( ! $existing ) {
			return self::rest_error( new WP_Error( 'ml_batch_not_found', __( 'Batch not found.', 'ma-lumiere-clinic' ) ), 404 );
		}

		// A lot always belongs to the medicine it was received for, and the
		// quantity originally received is a historical fact. Both are carried
		// over rather than taken from the request body, so editing metadata
		// cannot re-point a lot or rewrite its history. On-hand stock only ever
		// changes through adjust/dispense/return.
		$params                      = self::inventory_params( $request );
		$params['medicine_id']       = (int) $existing['medicine_id'];
		$params['quantity_received'] = (int) $existing['quantity_received'];

		$result = ML_Medicine_Batch_Repository::update( $batch_id, $params );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response( array( 'data' => ML_Medicine_Batch_Repository::get( $batch_id ) ) );
	}

	public static function adjust_stock( $request ) {
		$params  = $request->get_params();
		$batch   = ML_Medicine_Batch_Repository::get( (int) $request['batch_id'] );
		if ( ! $batch ) {
			return self::rest_error( new WP_Error( 'ml_batch_not_found', __( 'Batch not found.', 'ma-lumiere-clinic' ) ), 404 );
		}

		$result = ML_Inventory_Service::adjust(
			(int) $request['batch_id'],
			isset( $params['quantity'] ) ? (int) $params['quantity'] : 0,
			isset( $params['movement_type'] ) ? sanitize_key( (string) $params['movement_type'] ) : 'adjustment',
			isset( $params['notes'] ) ? sanitize_textarea_field( (string) $params['notes'] ) : ''
		);
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response(
			array(
				'data' => ML_Medicine_Batch_Repository::get( (int) $request['batch_id'] ),
				'meta' => array( 'adjusted' => true ),
			)
		);
	}

	public static function get_stock_movements( $request ) {
		$params = $request->get_params();
		return self::paged_response(
			ML_Stock_Movement_Repository::list(
				array(
					'page'       => absint( $request['page'] ),
					'per_page'   => absint( $request['per_page'] ),
					'medicine_id' => isset( $params['medicine_id'] ) ? absint( $params['medicine_id'] ) : 0,
					'batch_id'   => isset( $params['batch_id'] ) ? absint( $params['batch_id'] ) : 0,
					'movement_type' => isset( $params['movement_type'] ) ? sanitize_key( (string) $params['movement_type'] ) : '',
					'from'       => isset( $params['from'] ) ? (string) $params['from'] : '',
					'to'         => isset( $params['to'] ) ? (string) $params['to'] : '',
					'search'     => isset( $params['search'] ) ? (string) $params['search'] : '',
				)
			)
		);
	}

	public static function get_inventory_summary( $request ) {
		$params = $request->get_params();
		return rest_ensure_response(
			array(
				'data' => ML_Stock_Movement_Repository::summary(
					isset( $params['from'] ) ? (string) $params['from'] : '',
					isset( $params['to'] ) ? (string) $params['to'] : ''
				),
			)
		);
	}

	public static function get_inventory_alerts( $request ) {
		return rest_ensure_response( array( 'data' => ML_Inventory_Service::alerts() ) );
	}

	public static function write_off_expired( $request ) {
		$result = ML_Inventory_Service::write_off_expired();
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response(
			array(
				'data' => $result,
				'meta' => array( 'written_off' => true ),
			)
		);
	}

	public static function dispense_prescription_stock( $request ) {
		$result = ML_Inventory_Service::dispense_prescription( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 'ml_prescription_not_found' === $result->get_error_code() ? 404 : 409 );
		}
		return rest_ensure_response(
			array(
				'data' => $result,
				'meta' => array( 'dispensed' => true ),
			)
		);
	}

	public static function return_prescription_stock( $request ) {
		$result = ML_Inventory_Service::return_from_prescription( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 'ml_prescription_not_found' === $result->get_error_code() ? 404 : 409 );
		}
		return rest_ensure_response(
			array(
				'data' => $result,
				'meta' => array( 'returned' => true ),
			)
		);
	}

	/**
	 * Pull only the fields a repository understands out of a REST request, so
	 * an unexpected key can never reach an INSERT.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return array
	 */
	private static function inventory_params( $request ) {
		$params = $request->get_params();
		$out    = array();
		foreach ( array_merge( ML_Medicine_Repository::fields(), ML_Medicine_Batch_Repository::fields() ) as $key ) {
			if ( array_key_exists( $key, $params ) ) {
				$out[ $key ] = $params[ $key ];
			}
		}
		return $out;
	}

	/**
	 * Treatment session handlers.
	 */

	public static function get_sessions( $request ) {
		$patient_id = isset( $request['patient_id'] ) ? absint( $request['patient_id'] ) : 0;
		if ( ! $patient_id ) {
			return new WP_REST_Response(
				array( 'data' => array( 'message' => __( 'A patient is required to list sessions.', 'ma-lumiere-clinic' ) ) ),
				400
			);
		}
		$treatment_id = isset( $request['treatment_id'] ) ? absint( $request['treatment_id'] ) : 0;
		$rows         = ML_Treatment_Session_Repository::for_patient( $patient_id, $treatment_id );
		return rest_ensure_response( array( 'data' => $rows, 'meta' => array( 'patient_id' => $patient_id ) ) );
	}

	public static function create_session( $request ) {
		$result = ML_Treatment_Session_Repository::create( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Treatment_Session_Repository::get( (int) $result ),
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	public static function create_session_plan( $request ) {
		$input = $request->get_params();
		$plan  = array(
			'patient_id'   => (int) $request['id'],
			'treatment_id' => isset( $request['treatment_id'] ) ? absint( $request['treatment_id'] ) : 0,
			'plan'         => isset( $input['plan'] ) && is_array( $input['plan'] ) ? $input['plan'] : array(),
		);
		$result = ML_Treatment_Session_Repository::create_plan( $plan );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => $result,
				'meta' => array( 'created' => count( $result ) ),
			),
			201
		);
	}

	public static function update_session( $request ) {
		$result = ML_Treatment_Session_Repository::update( (int) $request['id'], $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response( array( 'data' => $result, 'meta' => array( 'updated' => true ) ) );
	}

	/**
	 * Follow-up handlers.
	 */

	public static function get_followups( $request ) {
		return self::paged_response(
			ML_Followup_Repository::list(
				array(
					'page'       => absint( $request['page'] ),
					'per_page'   => absint( $request['per_page'] ),
					'patient_id' => isset( $request['patient_id'] ) ? absint( $request['patient_id'] ) : 0,
					'status'     => isset( $request['status'] ) ? (string) $request['status'] : '',
					'overdue'    => isset( $request['overdue'] ) ? (bool) $request['overdue'] : false,
					'from'       => isset( $request['from'] ) ? (string) $request['from'] : '',
					'to'         => isset( $request['to'] ) ? (string) $request['to'] : '',
					'search'     => isset( $request['search'] ) ? (string) $request['search'] : '',
				)
			)
		);
	}

	public static function create_followup( $request ) {
		$result = ML_Followup_Repository::create( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Followup_Repository::get( (int) $result ),
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	public static function get_patient_followups( $request ) {
		return rest_ensure_response(
			array(
				'data' => ML_Followup_Repository::for_patient(
					(int) $request['id'],
					isset( $request['status'] ) ? (string) $request['status'] : ''
				),
				'meta' => array( 'patient_id' => (int) $request['id'] ),
			)
		);
	}

	public static function update_followup_status( $request ) {
		$status = isset( $request['status'] ) ? sanitize_key( (string) $request['status'] ) : '';
		$result = ML_Followup_Repository::set_status( (int) $request['id'], $status );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response( array( 'data' => $result, 'meta' => array( 'status' => $status ) ) );
	}

	public static function get_invoices( $request ) {
		return self::paged_response(
			ML_Invoice_Repository::list(
				array(
					'page'       => absint( $request['page'] ),
					'per_page'   => absint( $request['per_page'] ),
					'patient_id' => isset( $request['patient_id'] ) ? absint( $request['patient_id'] ) : 0,
					'status'     => isset( $request['status'] ) ? (string) $request['status'] : '',
					'from'       => isset( $request['from'] ) ? (string) $request['from'] : '',
					'to'         => isset( $request['to'] ) ? (string) $request['to'] : '',
					'search'     => isset( $request['search'] ) ? (string) $request['search'] : '',
				)
			)
		);
	}

	public static function create_invoice( $request ) {
		$result = ML_Invoice_Repository::create( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		$invoice = ML_Invoice_Repository::get( (int) $result );
		return new WP_REST_Response(
			array(
				'data' => $invoice,
				'meta' => array( 'created' => true, 'items' => ML_Invoice_Repository::items( (int) $result ) ),
			),
			201
		);
	}

	public static function get_invoice( $request ) {
		$invoice = ML_Invoice_Repository::get( (int) $request['id'] );
		if ( ! $invoice ) {
			return new WP_REST_Response( array( 'data' => array( 'message' => __( 'Invoice not found.', 'ma-lumiere-clinic' ) ) ), 404 );
		}
		return rest_ensure_response(
			array(
				'data' => $invoice,
				'meta' => array( 'items' => ML_Invoice_Repository::items( (int) $request['id'] ) ),
			)
		);
	}

	public static function create_invoice_payment( $request ) {
		$input = $request->get_params();
		$input['invoice_id'] = (int) $request['id'];

		$result = ML_Payment_Repository::create( $input );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Payment_Repository::get( (int) $result ),
				'meta' => array( 'invoice' => ML_Invoice_Repository::get( (int) $request['id'] ), 'created' => true ),
			),
			201
		);
	}

	public static function create_razorpay_order( $request ) {
		$result = ML_Payment_Repository::create_razorpay_order( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response(
			array(
				'data' => array(
					'order_id'  => $result['order_id'],
					'amount'    => $result['amount'],
					'currency'  => $result['currency'],
					'key_id'    => $result['key_id'],
					'receipt'   => $result['receipt'],
				),
				'meta' => array( 'payment' => $result['payment'] ),
			)
		);
	}

	public static function verify_razorpay_payment( $request ) {
		$result = ML_Payment_Repository::verify_razorpay_payment( (int) $request['id'], $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 403 );
		}
		return rest_ensure_response(
			array(
				'data' => array(
					'payment'   => $result['payment'],
					'invoice'   => $result['invoice'],
					'duplicate' => $result['duplicate'],
				),
			)
		);
	}

	public static function create_portal_razorpay_order( $request ) {
		$patient = ML_Portal::current_patient();
		$invoice = ML_Invoice_Repository::get( (int) $request['id'] );
		if ( ! is_array( $patient ) || ! is_array( $invoice ) || (int) $invoice['patient_id'] !== (int) $patient['id'] ) {
			return self::rest_error( new WP_Error( 'ml_portal_forbidden', __( 'Not allowed.', 'ma-lumiere-clinic' ) ), 403 );
		}

		$result = ML_Payment_Repository::create_razorpay_order( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response(
			array(
				'data' => array(
					'order_id'  => $result['order_id'],
					'amount'    => $result['amount'],
					'currency'  => $result['currency'],
					'key_id'    => $result['key_id'],
					'receipt'   => $result['receipt'],
				),
				'meta' => array( 'payment' => $result['payment'] ),
			)
		);
	}

	public static function get_payments( $request ) {
		return self::paged_response(
			ML_Payment_Repository::list(
				array(
					'page'       => absint( $request['page'] ),
					'per_page'   => absint( $request['per_page'] ),
					'invoice_id' => isset( $request['invoice_id'] ) ? absint( $request['invoice_id'] ) : 0,
					'patient_id' => isset( $request['patient_id'] ) ? absint( $request['patient_id'] ) : 0,
					'method'     => isset( $request['method'] ) ? (string) $request['method'] : '',
					'status'     => isset( $request['status'] ) ? (string) $request['status'] : '',
					'from'       => isset( $request['from'] ) ? (string) $request['from'] : '',
					'to'         => isset( $request['to'] ) ? (string) $request['to'] : '',
				)
			)
		);
	}

	/**
	 * Photo handlers.
	 */

	public static function get_photos( $request ) {
		return self::paged_response(
			ML_Photo_Repository::list(
				array(
					'page'       => absint( $request['page'] ),
					'per_page'   => absint( $request['per_page'] ),
					'patient_id' => isset( $request['patient_id'] ) ? absint( $request['patient_id'] ) : 0,
					'photo_type' => isset( $request['photo_type'] ) ? (string) $request['photo_type'] : '',
					'search'     => isset( $request['search'] ) ? (string) $request['search'] : '',
				)
			)
		);
	}

	public static function create_photo( $request ) {
		$files = $request->get_file_params();
		$file  = array();
		if ( isset( $files['photo'] ) ) {
			$file = $files['photo'];
		} elseif ( isset( $files['file'] ) ) {
			$file = $files['file'];
		}
		$input = $request->get_params();
		$input['file']       = $file;
		$input['patient_id'] = isset( $request['patient_id'] ) ? absint( $request['patient_id'] ) : 0;

		$result = ML_Photo_Repository::create( $input );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return new WP_REST_Response(
			array(
				'data' => ML_Photo_Repository::get( (int) $result ),
				'meta' => array( 'created' => true ),
			),
			201
		);
	}

	public static function serve_photo( $request ) {
		$row = ML_Photo_Repository::get( (int) $request['id'] );
		if ( ! $row ) {
			status_header( 404 );
			nocache_headers();
			exit;
		}
		$result = ML_Photo_Repository::serve( $row );
		if ( is_wp_error( $result ) ) {
			status_header( 404 );
			nocache_headers();
			exit;
		}
		exit;
	}

	public static function delete_photo( $request ) {
		$result = ML_Photo_Repository::delete( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return self::rest_error( $result, 400 );
		}
		return rest_ensure_response(
			array(
				'data' => array( 'deleted' => true ),
				'meta' => array( 'id' => absint( $request['id'] ) ),
			)
		);
	}

	/**
	 * Date-range activity + financial summary. Supports from/to query params
	 * (YYYY-MM-DD); defaults to the current month to date.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_report_summary( $request ) {
		if ( ! class_exists( 'ML_Reports' ) ) {
			require_once ML_CLINIC_PATH . 'includes/class-reports.php';
		}
		$from    = $request instanceof WP_REST_Request ? (string) $request->get_param( 'from' ) : '';
		$to      = $request instanceof WP_REST_Request ? (string) $request->get_param( 'to' ) : '';
		$summary = ML_Reports::summary( $from, $to );
		return rest_ensure_response( array( 'data' => $summary ) );
	}
}