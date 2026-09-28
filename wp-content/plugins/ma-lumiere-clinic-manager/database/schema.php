<?php
/**
 * Database schema definitions.
 *
 * Every table here is created with the extensible `{prefix}ml_` prefix and
 * managed through ML_Database using dbDelta(). This file is the single
 * source of truth for the clinic's data model.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registry of tables + dbDelta DDL.
 *
 * Format: array(
 *     <table_slug> => array(
 *         'name'      => 'ml_patients',            // without prefix.
 *         'version'   => 1,                        // per-table revision.
 *         'schema'    => "CREATE TABLE ...",       // dbDelta syntax.
 *         'references'=> array( ... ),             // human-readable FK notes.
 *     ),
 * )
 *
 * @return array
 */
function ml_schema_definitions() {
	$struct = array(

		'patients' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_patients (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				patient_uid VARCHAR(20) NOT NULL,
				wp_user_id BIGINT(20) UNSIGNED NULL,
				first_name VARCHAR(100) NOT NULL,
				last_name VARCHAR(100) NOT NULL,
				date_of_birth DATE NULL,
				gender VARCHAR(20) NULL,
				phone VARCHAR(20) NULL,
				email VARCHAR(191) NULL,
				address TEXT NULL,
				city VARCHAR(100) NULL,
				state VARCHAR(100) NULL,
				pincode VARCHAR(20) NULL,
				emergency_contact_name VARCHAR(100) NULL,
				emergency_contact_phone VARCHAR(20) NULL,
				registration_date DATE NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY patient_uid (patient_uid),
				KEY wp_user_id (wp_user_id),
				KEY email (email),
				KEY phone (phone),
				KEY status (status),
				KEY last_name (last_name)
			)",
		),

		'visits' => array(
			'version' => 3,
			'schema'  => "CREATE TABLE {prefix}ml_visits (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				patient_id BIGINT(20) UNSIGNED NOT NULL,
				visit_number VARCHAR(20) NOT NULL,
				doctor_user_id BIGINT(20) UNSIGNED NULL,
				visit_date DATE NOT NULL,
				chief_complaint TEXT NULL,
				diagnosis TEXT NULL,
				clinical_notes TEXT NULL,
				treatment_plan TEXT NULL,
				skin_type VARCHAR(50) NULL,
				fitzpatrick_type VARCHAR(20) NULL,
				skin_sensitivity VARCHAR(50) NULL,
				oiliness_level VARCHAR(50) NULL,
				pigmentation_notes TEXT NULL,
				acne_severity VARCHAR(50) NULL,
				scarring_notes TEXT NULL,
				hair_loss_pattern VARCHAR(100) NULL,
				hair_density VARCHAR(50) NULL,
				scalp_condition VARCHAR(100) NULL,
				hair_fall_duration VARCHAR(50) NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'open',
				is_locked TINYINT(1) NOT NULL DEFAULT 0,
				updated_by BIGINT(20) UNSIGNED NULL,
				update_history TEXT NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY patient_visit (patient_id, visit_number),
				KEY doctor_user_id (doctor_user_id),
				KEY visit_date (visit_date),
				KEY status (status),
				KEY is_locked (is_locked)
			)",
		),

		'appointments' => array(
			'version' => 2,
			'schema'  => "CREATE TABLE {prefix}ml_appointments (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				patient_id BIGINT(20) UNSIGNED NULL,
				doctor_user_id BIGINT(20) UNSIGNED NULL,
				treatment_id BIGINT(20) UNSIGNED NULL,
				appointment_date DATE NOT NULL,
				start_time TIME NULL,
				end_time TIME NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'pending',
				appointment_type VARCHAR(50) NULL,
				notes TEXT NULL,
				source VARCHAR(50) NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				booking_reference VARCHAR(20) NULL,
				patient_message TEXT NULL,
				idempotency_key VARCHAR(64) NULL,
				confirmation_sent TINYINT(1) NOT NULL DEFAULT 0,
				checked_in_at DATETIME NULL,
				completed_at DATETIME NULL,
				cancelled_at DATETIME NULL,
				cancelled_by BIGINT(20) UNSIGNED NULL,
				cancellation_reason VARCHAR(20) NULL,
				rescheduled_from BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY booking_reference (booking_reference),
				UNIQUE KEY idempotency_key (idempotency_key),
				KEY patient_id (patient_id),
				KEY doctor_user_id (doctor_user_id),
				KEY treatment_id (treatment_id),
				KEY appointment_date (appointment_date),
				KEY status (status),
				KEY doctor_datetime (doctor_user_id, appointment_date, start_time)
			)",
		),

		'clinic_blocked_dates' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_clinic_blocked_dates (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				blocked_date DATE NOT NULL,
				all_day TINYINT(1) NOT NULL DEFAULT 1,
				start_time TIME NULL,
				end_time TIME NULL,
				reason VARCHAR(200) NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY blocked_date (blocked_date),
				KEY date_all_day (blocked_date, all_day)
			)",
		),

		'prescriptions' => array(
			'version' => 3,
			'schema'  => "CREATE TABLE {prefix}ml_prescriptions (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				patient_id BIGINT(20) UNSIGNED NOT NULL,
				visit_id BIGINT(20) UNSIGNED NULL,
				doctor_user_id BIGINT(20) UNSIGNED NULL,
				prescription_date DATE NOT NULL,
				prescription_number VARCHAR(20) NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'draft',
				version INT(11) NOT NULL DEFAULT 1,
				revision_of BIGINT(20) UNSIGNED NULL,
				diagnosis TEXT NULL,
				doctor_notes TEXT NULL,
				advice TEXT NULL,
				follow_up_date DATE NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				updated_by BIGINT(20) UNSIGNED NULL,
				finalized_at DATETIME NULL,
				finalized_by BIGINT(20) UNSIGNED NULL,
				email_sent TINYINT(1) NOT NULL DEFAULT 0,
				email_log TEXT NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY prescription_number (prescription_number),
				KEY patient_id (patient_id),
				KEY visit_id (visit_id),
				KEY doctor_user_id (doctor_user_id),
				KEY prescription_date (prescription_date),
				KEY status (status),
				KEY revision_of (revision_of)
			)",
		),

		'prescription_items' => array(
			'version' => 2,
			'schema'  => "CREATE TABLE {prefix}ml_prescription_items (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				prescription_id BIGINT(20) UNSIGNED NOT NULL,
				medicine_id BIGINT(20) UNSIGNED NULL,
				medicine_name VARCHAR(200) NOT NULL,
				dosage VARCHAR(100) NULL,
				frequency VARCHAR(100) NULL,
				timing VARCHAR(100) NULL,
				duration VARCHAR(100) NULL,
				quantity INT(11) NOT NULL DEFAULT 0,
				dispensed_quantity INT(11) NOT NULL DEFAULT 0,
				instructions TEXT NULL,
				sort_order INT(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY prescription_id (prescription_id),
				KEY medicine_id (medicine_id)
			)",
		),

		'followups' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_followups (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				patient_id BIGINT(20) UNSIGNED NOT NULL,
				visit_id BIGINT(20) UNSIGNED NULL,
				doctor_user_id BIGINT(20) UNSIGNED NULL,
				followup_date DATE NOT NULL,
				reason VARCHAR(200) NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'upcoming',
				notes TEXT NULL,
				reminder_sent TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY patient_id (patient_id),
				KEY visit_id (visit_id),
				KEY doctor_user_id (doctor_user_id),
				KEY followup_date (followup_date),
				KEY status (status),
				KEY reminder_sent (reminder_sent)
			)",
		),

		'treatment_sessions' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_treatment_sessions (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				patient_id BIGINT(20) UNSIGNED NOT NULL,
				treatment_id BIGINT(20) UNSIGNED NULL,
				doctor_user_id BIGINT(20) UNSIGNED NULL,
				session_number INT(11) NOT NULL DEFAULT 1,
				session_type VARCHAR(50) NULL,
				scheduled_date DATE NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'rec',
				notes TEXT NULL,
				is_locked TINYINT(1) NOT NULL DEFAULT 0,
				created_by BIGINT(20) UNSIGNED NULL,
				updated_by BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				completed_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY patient_id (patient_id),
				KEY treatment_id (treatment_id),
				KEY patient_treatment (patient_id, treatment_id),
				KEY doctor_user_id (doctor_user_id),
				KEY scheduled_date (scheduled_date),
				KEY status (status)
			)",
		),

		'patient_photos' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_patient_photos (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				patient_id BIGINT(20) UNSIGNED NOT NULL,
				visit_id BIGINT(20) UNSIGNED NULL,
				treatment_id BIGINT(20) UNSIGNED NULL,
				photo_type VARCHAR(20) NOT NULL DEFAULT 'before',
				body_area VARCHAR(100) NULL,
				file_path VARCHAR(255) NOT NULL,
				attachment_id BIGINT(20) UNSIGNED NULL,
				photo_date DATE NULL,
				notes TEXT NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY patient_id (patient_id),
				KEY visit_id (visit_id),
				KEY photo_type (photo_type)
			)",
		),

		'treatments' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_treatments (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				wp_post_id BIGINT(20) UNSIGNED NULL,
				name VARCHAR(200) NOT NULL,
				category VARCHAR(100) NULL,
				duration VARCHAR(50) NULL,
				price DECIMAL(10,2) NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY wp_post_id (wp_post_id),
				KEY name (name),
				KEY category (category),
				KEY status (status)
			)",
		),

		'medicines' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_medicines (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				sku VARCHAR(50) NULL,
				name VARCHAR(200) NOT NULL,
				generic_name VARCHAR(200) NULL,
				brand_name VARCHAR(200) NULL,
				strength VARCHAR(100) NULL,
				form VARCHAR(50) NULL,
				category VARCHAR(100) NULL,
				reorder_level INT(11) NOT NULL DEFAULT 0,
				requires_prescription TINYINT(1) NOT NULL DEFAULT 1,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				notes TEXT NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY sku (sku),
				KEY name (name),
				KEY generic_name (generic_name),
				KEY category (category),
				KEY status (status)
			)",
		),

		'medicine_batches' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_medicine_batches (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				medicine_id BIGINT(20) UNSIGNED NOT NULL,
				batch_number VARCHAR(100) NULL,
				expiry_date DATE NULL,
				supplier VARCHAR(200) NULL,
				storage_location VARCHAR(100) NULL,
				quantity_received INT(11) NOT NULL DEFAULT 0,
				quantity_available INT(11) NOT NULL DEFAULT 0,
				unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
				received_date DATE NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				notes TEXT NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY medicine_id (medicine_id),
				KEY batch_number (batch_number),
				KEY expiry_date (expiry_date),
				KEY status (status),
				KEY medicine_expiry (medicine_id, status, expiry_date)
			)",
		),

		'stock_movements' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_stock_movements (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				medicine_id BIGINT(20) UNSIGNED NOT NULL,
				batch_id BIGINT(20) UNSIGNED NULL,
				movement_type VARCHAR(30) NOT NULL,
				quantity INT(11) NOT NULL DEFAULT 0,
				balance_after INT(11) NOT NULL DEFAULT 0,
				unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
				reference_type VARCHAR(50) NULL,
				reference_id BIGINT(20) UNSIGNED NULL,
				notes TEXT NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY medicine_id (medicine_id),
				KEY batch_id (batch_id),
				KEY movement_type (movement_type),
				KEY reference (reference_type, reference_id),
				KEY created_at (created_at)
			)",
		),

		'invoices' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_invoices (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				invoice_number VARCHAR(50) NOT NULL,
				patient_id BIGINT(20) UNSIGNED NOT NULL,
				appointment_id BIGINT(20) UNSIGNED NULL,
				visit_id BIGINT(20) UNSIGNED NULL,
				subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
				discount DECIMAL(10,2) NOT NULL DEFAULT 0,
				tax DECIMAL(10,2) NOT NULL DEFAULT 0,
				total DECIMAL(10,2) NOT NULL DEFAULT 0,
				paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
				balance_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
				payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
				invoice_date DATE NOT NULL,
				due_date DATE NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY invoice_number (invoice_number),
				KEY patient_id (patient_id),
				KEY appointment_id (appointment_id),
				KEY payment_status (payment_status),
				KEY invoice_date (invoice_date)
			)",
		),

		'invoice_items' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_invoice_items (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				invoice_id BIGINT(20) UNSIGNED NOT NULL,
				item_type VARCHAR(50) NULL,
				treatment_id BIGINT(20) UNSIGNED NULL,
				description VARCHAR(255) NULL,
				quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
				unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
				discount DECIMAL(10,2) NOT NULL DEFAULT 0,
				tax DECIMAL(10,2) NOT NULL DEFAULT 0,
				line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY invoice_id (invoice_id)
			)",
		),

		'payments' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_payments (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				invoice_id BIGINT(20) UNSIGNED NOT NULL,
				patient_id BIGINT(20) UNSIGNED NOT NULL,
				amount DECIMAL(10,2) NOT NULL DEFAULT 0,
				payment_method VARCHAR(50) NULL,
				transaction_id VARCHAR(100) NULL,
				gateway VARCHAR(50) NULL,
				gateway_order_id VARCHAR(100) NULL,
				gateway_payment_id VARCHAR(100) NULL,
				gateway_signature VARCHAR(255) NULL,
				payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
				payment_date DATETIME NULL,
				notes TEXT NULL,
				created_by BIGINT(20) UNSIGNED NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY invoice_id (invoice_id),
				KEY patient_id (patient_id),
				KEY payment_status (payment_status),
				KEY payment_date (payment_date),
				KEY transaction_id (transaction_id)
			)",
		),

		'audit_logs' => array(
			'version' => 1,
			'schema'  => "CREATE TABLE {prefix}ml_audit_logs (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) UNSIGNED NULL,
				action VARCHAR(50) NOT NULL,
				entity_type VARCHAR(50) NULL,
				entity_id BIGINT(20) UNSIGNED NULL,
				description TEXT NULL,
				ip_address VARCHAR(45) NULL,
				user_agent VARCHAR(255) NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY action (action),
				KEY entity (entity_type, entity_id),
				KEY created_at (created_at)
			)",
		),
	);

	return $struct;
}