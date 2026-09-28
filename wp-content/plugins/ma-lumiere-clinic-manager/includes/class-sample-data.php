<?php
/**
 * Sample/demo data installer and remover.
 *
 * Creates a realistic but entirely fictional clinic dataset so the admin
 * screens, reports and PDFs can be evaluated without typing anything in.
 * Nothing here is required at runtime: with no sample data installed the
 * class is inert.
 *
 * SAFETY MODEL
 * ------------
 * Every record the installer creates is recorded by primary key in the
 * ml_clinic_sample_data option. That index is the authoritative allow-list
 * for removal. On top of that, patients and medicines carry an in-band
 * marker (a reserved .invalid email domain and a SAMPLE- SKU prefix) which
 * is re-checked at delete time. A row is only ever deleted when it is in
 * the index AND still carries its marker, so real records can never be
 * removed by this feature - not even if an id were somehow reused.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Sample_Data {

	/**
	 * Option holding the install index (table slug => list of primary keys).
	 *
	 * @var string
	 */
	const OPTION = 'ml_clinic_sample_data';

	/**
	 * Reserved email domain (RFC 2606) used to mark demo patients.
	 *
	 * @var string
	 */
	const EMAIL_DOMAIN = 'sample-clinic.invalid';

	/**
	 * SKU prefix used to mark demo medicines.
	 *
	 * @var string
	 */
	const SKU_PREFIX = 'SAMPLE-';

	/**
	 * Word the administrator must type to confirm removal.
	 *
	 * @var string
	 */
	const CONFIRM_WORD = 'DELETE';

	/**
	 * Billing slots whose first instalment is refunded once the invoice has
	 * settled, so Payments and Reports show a status spread.
	 */
	const REFUND_SLOTS = array( 1, 4 );

	/**
	 * Patient photos seeded by the installer. Photos render slowest, so the
	 * count is kept modest while still clearing the five-row bar.
	 */
	const PHOTO_COUNT = 6;

	/**
	 * Hook up the admin-post handlers.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'admin_post_ml_sample_data_install', array( __CLASS__, 'handle_install' ) );
		add_action( 'admin_post_ml_sample_data_remove', array( __CLASS__, 'handle_remove' ) );
	}

	/* ---------------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------------ */

	/**
	 * The stored install index.
	 *
	 * @return array<string,int[]>
	 */
	public static function index() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		/*
		 * The option is a metadata envelope: installed_at, version, ids.
		 * Only the `ids` map is the removal allow-list. A flat table => ids
		 * shape is also accepted so an index written by an older build is
		 * still honoured rather than silently ignored.
		 */
		$raw = isset( $stored['ids'] ) && is_array( $stored['ids'] ) ? $stored['ids'] : $stored;

		$clean = array();
		foreach ( $raw as $table => $ids ) {
			if ( ! is_array( $ids ) ) {
				continue;
			}
			$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
			if ( $ids ) {
				$clean[ (string) $table ] = $ids;
			}
		}
		return $clean;
	}

	/**
	 * Is a sample dataset currently installed?
	 *
	 * @return bool
	 */
	public static function is_installed() {
		return array() !== self::index();
	}

	/**
	 * Private files recorded at install time, keyed by photo id.
	 *
	 * patient_photos rows are removed through ML_Photo_Repository::delete(),
	 * which unlinks the file. This map is the safety net for the case where a
	 * row is gone but the file survived, for example after a partial install.
	 *
	 * @return array<int,string>
	 */
	public static function files() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) || empty( $stored['files'] ) || ! is_array( $stored['files'] ) ) {
			return array();
		}

		$clean = array();
		foreach ( $stored['files'] as $id => $path ) {
			$id = absint( $id );
			if ( $id && is_string( $path ) && '' !== $path ) {
				$clean[ $id ] = $path;
			}
		}
		return $clean;
	}

	/**
	 * When the dataset was installed (site timezone, or '' when absent).
	 *
	 * @return string
	 */
	public static function installed_at() {
		$stored = get_option( self::OPTION, array() );
		return is_array( $stored ) && ! empty( $stored['installed_at'] ) ? (string) $stored['installed_at'] : '';
	}

	/**
	 * Live row counts for the tables this feature writes, so the settings
	 * screen can show what is actually in the database right now.
	 *
	 * @return array<string,int>
	 */
	public static function live_counts() {
		global $wpdb;

		$index  = self::index();
		$counts = array();

		$tables = array(
			'patients',
			'visits',
			'appointments',
			'prescriptions',
			'followups',
			'treatment_sessions',
			'medicines',
			'medicine_batches',
			'stock_movements',
			'invoices',
			'payments',
			'patient_photos',
			'clinic_blocked_dates',
		);

		foreach ( $tables as $slug ) {
			if ( ! ML_Database::table_exists( $slug ) ) {
				continue;
			}
			$table = ML_Database::table( $slug );
			$ids   = isset( $index[ $slug ] ) ? $index[ $slug ] : array();
			if ( ! $ids ) {
				$counts[ $slug ] = 0;
				continue;
			}
			$placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders are generated.
			$counts[ $slug ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE id IN ({$placeholder})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return $counts;
	}

	/**
	 * Human labels for the datasets, in display order.
	 *
	 * @return array<string,string>
	 */
	public static function labels() {
		return array(
			'patients'            => __( 'Patients', 'ma-lumiere-clinic' ),
			'visits'              => __( 'Visits', 'ma-lumiere-clinic' ),
			'appointments'        => __( 'Appointments', 'ma-lumiere-clinic' ),
			'prescriptions'       => __( 'Prescriptions', 'ma-lumiere-clinic' ),
			'followups'           => __( 'Follow-ups', 'ma-lumiere-clinic' ),
			'treatment_sessions'  => __( 'Treatment sessions', 'ma-lumiere-clinic' ),
			'medicines'           => __( 'Medicines', 'ma-lumiere-clinic' ),
			'medicine_batches'    => __( 'Medicine batches', 'ma-lumiere-clinic' ),
			'stock_movements'     => __( 'Stock movements', 'ma-lumiere-clinic' ),
			'invoices'            => __( 'Invoices', 'ma-lumiere-clinic' ),
			'payments'            => __( 'Payments', 'ma-lumiere-clinic' ),
			'patient_photos'      => __( 'Patient photos', 'ma-lumiere-clinic' ),
			'clinic_blocked_dates' => __( 'Blocked clinic dates', 'ma-lumiere-clinic' ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Request handlers
	 * ------------------------------------------------------------------ */

	/**
	 * Settings page URL used for post-action redirects.
	 *
	 * @return string
	 */
	private static function settings_url() {
		return admin_url( 'admin.php?page=ml-clinic-settings' );
	}

	/**
	 * POST handler that creates the dataset.
	 *
	 * @return void
	 */
	public static function handle_install() {
		ML_Security::gate( ML_Settings::CAP );

		if ( self::is_installed() ) {
			wp_safe_redirect( self::settings_url() . '&ml_sample=already' );
			exit;
		}

		$result = self::install();

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'ml_sample'  => 'failed',
						'ml_message' => $result->get_error_message(),
					),
					self::settings_url()
				)
			);
			exit;
		}

		wp_safe_redirect( self::settings_url() . '&ml_sample=installed' );
		exit;
	}

	/**
	 * POST handler for the two-step removal.
	 *
	 * Step 1 (no `confirm` field) only reveals the typed confirmation form.
	 * Step 2 (confirm = DELETE) performs the deletion.
	 *
	 * @return void
	 */
	public static function handle_remove() {
		ML_Security::gate( ML_Settings::CAP );

		if ( ! self::is_installed() ) {
			wp_safe_redirect( self::settings_url() . '&ml_sample=absent' );
			exit;
		}

		$stage   = isset( $_POST['ml_sample_stage'] ) ? absint( wp_unslash( $_POST['ml_sample_stage'] ) ) : 1;
		$confirm = isset( $_POST['ml_sample_confirm'] ) ? trim( (string) wp_unslash( $_POST['ml_sample_confirm'] ) ) : '';

		// Step 1: ask for confirmation. Nothing is deleted here.
		if ( 2 !== $stage ) {
			wp_safe_redirect( self::settings_url() . '&ml_sample=confirm' );
			exit;
		}

		// Step 2: the typed word must match exactly.
		if ( self::CONFIRM_WORD !== $confirm ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'ml_sample'  => 'confirm',
						'ml_failed'  => 'word',
					),
					self::settings_url()
				)
			);
			exit;
		}

		$result = self::remove();

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'ml_sample'  => 'failed',
						'ml_message' => $result->get_error_message(),
					),
					self::settings_url()
				)
			);
			exit;
		}

		wp_safe_redirect( self::settings_url() . '&ml_sample=removed' );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Install
	 * ------------------------------------------------------------------ */

	/**
	 * Create the sample dataset.
	 *
	 * Individual record failures are collected rather than thrown, so one bad
	 * row cannot leave the install half-applied with no way to see why. The
	 * caller receives a per-table count plus an error list.
	 *
	 * @return array|WP_Error {counts: array<string,int>, errors: string[]}
	 */
	public static function install() {
		global $wpdb;

		if ( self::is_installed() ) {
			return new WP_Error( 'ml_sample_exists', __( 'Sample data is already installed.', 'ma-lumiere-clinic' ) );
		}

		/*
		 * Records are attributed to the acting user, and the repositories
		 * enforce their own per-patient access checks (ML_Security::can_access_patient).
		 * Only the clinic super-admin holds ml_manage_clinic, so fail loudly
		 * up front rather than silently producing a dataset with no invoices.
		 */
		if ( ! ML_Security::can( 'ml_manage_clinic' ) ) {
			return new WP_Error(
				'ml_sample_forbidden',
				__( 'Only a clinic super-admin can create or remove sample data.', 'ma-lumiere-clinic' )
			);
		}

		$errors = array();
		$made   = array();
		$files  = array();

		/*
		 * Checkpointing. install() is not one outer transaction, so a fatal part
		 * way through would otherwise leave rows behind that removal cannot see.
		 * The index is written on every recorded row rather than at the end, and
		 * `complete` stays false until the last step, so a half-finished dataset
		 * is still removable instead of looking installed-and-working.
		 */
		$checkpoint = static function () use ( &$made, &$files ) {
			update_option(
				self::OPTION,
				array(
					'installed_at' => current_time( 'mysql' ),
					'version'      => ML_CLINIC_VERSION,
					'complete'     => false,
					'ids'          => array_map( 'array_values', array_map( 'array_unique', $made ) ),
					'files'        => $files,
				),
				false
			);
		};

		$track = static function ( $table, $id ) use ( &$made, $checkpoint ) {
			$id = absint( $id );
			if ( ! $id ) {
				return 0;
			}
			$made[ $table ][] = $id;
			$checkpoint();
			return $id;
		};

		$fail = static function ( $label, $result ) use ( &$errors ) {
			$message = is_wp_error( $result ) ? $result->get_error_message() : __( 'unknown error', 'ma-lumiere-clinic' );
			$errors[] = $label . ': ' . $message;
		};

		$doctor = self::resolve_doctor();

		// --- Medicines + batches (stock ledger) ----------------------------
		$medicine_ids = array();
		$batch_ids    = array();
		foreach ( self::medicine_seed() as $spec ) {
			$spec['sku'] = self::SKU_PREFIX . $spec['sku'];
			$medicine_id = ML_Medicine_Repository::create( $spec );
			if ( is_wp_error( $medicine_id ) ) {
				$fail( $spec['name'], $medicine_id );
				continue;
			}
			$medicine_ids[ $spec['key'] ] = $track( 'medicines', $medicine_id );

			$batch_id = ML_Inventory_Service::receive(
				array(
					'medicine_id'       => $medicine_id,
					'batch_number'      => self::SKU_PREFIX . $spec['key'] . '-' . gmdate( 'Y' ) . '01',
					'expiry_date'       => $spec['expiry'],
					'supplier'          => $spec['supplier'],
					'storage_location'  => $spec['location'],
					'quantity_received' => $spec['quantity'],
					'unit_cost'         => $spec['cost'],
					'received_date'     => gmdate( 'Y-m-d', strtotime( '-45 days' ) ),
					'status'            => 'active',
					'notes'             => __( 'Sample stock received by the demo installer.', 'ma-lumiere-clinic' ),
				)
			);
			if ( is_wp_error( $batch_id ) ) {
				$fail( $spec['name'] . ' / batch', $batch_id );
				continue;
			}
			$batch_ids[ $spec['key'] ] = $batch_id;
			$track( 'medicine_batches', $batch_id );
		}

		/*
		 * Inventory > Alerts reads four buckets straight off the ledger, so a
		 * demo where every lot is healthy leaves the whole tab blank. Age a few
		 * lots and drain a few others on purpose.
		 */
		$alerts = self::stock_alert_scenarios( $medicine_ids, $batch_ids, $track, $fail );
		$made['stock_movements'] = array_merge(
			self::movement_ids_for( $medicine_ids ),
			$alerts
		);

		// --- Blocked clinic dates ------------------------------------------
		// Read by ML_Availability_Service when it offers slots. Offsets stay odd
		// so they never collide with the even-offset appointments seeded below.
		foreach ( self::blocked_date_seed() as $row ) {
			$inserted = $wpdb->insert(
				ML_Database::table( 'clinic_blocked_dates' ),
				array(
					'blocked_date' => self::future_date( $row['day_offset'] ),
					'all_day'      => $row['all_day'],
					'start_time'   => $row['start_time'],
					'end_time'     => $row['end_time'],
					'reason'       => $row['reason'],
					'created_by'   => get_current_user_id() ? get_current_user_id() : null,
					'created_at'   => current_time( 'mysql' ),
				)
			);
			if ( $inserted ) {
				$track( 'clinic_blocked_dates', (int) $wpdb->insert_id );
			} else {
				$fail( 'blocked date', new WP_Error( 'ml_sample_insert', __( 'The row could not be saved.', 'ma-lumiere-clinic' ) ) );
			}
		}

		/*
		 * Stock movements are written by ML_Inventory_Service::receive(), which
		 * owns the opening-balance ledger row. Re-read them here so the index
		 * covers the whole ledger for the demo medicines.
		 */
		// --- Patients ------------------------------------------------------
		$patients = array();
		foreach ( self::patient_seed() as $spec ) {
			$patient_id = ML_Patient_Repository::create( $spec );
			if ( is_wp_error( $patient_id ) ) {
				$fail( trim( $spec['first_name'] . ' ' . $spec['last_name'] ), $patient_id );
				continue;
			}
			$patients[] = $track( 'patients', $patient_id );
		}

		// --- Clinical + financial records ---------------------------------
		$visit_plan      = self::visit_plan( count( $patients ) );
		$appointment_plan = self::appointment_plan( count( $patients ) );
		$bill_plan       = self::billing_plan( count( $patients ) );

		$patient_index = 0;
		foreach ( $patients as $patient_id ) {
			$slot = $patient_index++;

			// Visits (historical, allowed in the past).
			$visit_ids = array();
			$visit_qty = isset( $visit_plan[ $slot ] ) ? (int) $visit_plan[ $slot ] : 1;
			for ( $v = 0; $v < $visit_qty; $v++ ) {
				$spec = self::visit_spec( $v );
				$spec['patient_id']     = $patient_id;
				$spec['doctor_user_id'] = $doctor;
				$visit_id = ML_Visit_Repository::create( $spec );
				if ( is_wp_error( $visit_id ) ) {
					$fail( 'visit', $visit_id );
					continue;
				}
				$visit_ids[] = $track( 'visits', $visit_id );
			}
			$last_visit = $visit_ids ? (int) end( $visit_ids ) : 0;

			// Appointments must be in the future, so seed them upcoming.
			if ( isset( $appointment_plan[ $slot ] ) ) {
				$spec                    = $appointment_plan[ $slot ];
				$spec['patient_id']      = $patient_id;
				$spec['doctor_user_id']  = $doctor;
				$spec['appointment_date'] = self::future_date( $spec['day_offset'] );
				$appointment_id = ML_Appointment_Repository::create( $spec );
				if ( is_wp_error( $appointment_id ) ) {
					$fail( 'appointment', $appointment_id );
				} else {
					$track( 'appointments', $appointment_id );
				}
			}

			// Treatment sessions (no treatment_id: the catalogue is CPT-backed).
			if ( 0 === $slot % 2 || $slot < 5 ) {
				$session_id = ML_Treatment_Session_Repository::create(
					array(
						'patient_id'     => $patient_id,
						'doctor_user_id' => $doctor,
						'session_number' => 1,
						'session_type'   => 'laser',
						'scheduled_date' => self::future_date( 5 + $slot ),
						'status'         => 0 === $slot % 4 ? 'confirmed' : 'rec',
						'notes'          => __( 'Sample session created by the demo installer.', 'ma-lumiere-clinic' ),
					)
				);
				if ( is_wp_error( $session_id ) ) {
					$fail( 'treatment session', $session_id );
				} else {
					$track( 'treatment_sessions', $session_id );
				}
			}

			// Follow-up for a subset.
			if ( 0 === $slot % 3 ) {
				$followup_id = ML_Followup_Repository::create(
					array(
						'patient_id'     => $patient_id,
						'visit_id'       => $last_visit,
						'doctor_user_id' => $doctor,
						'followup_date'  => self::future_date( 14 + $slot ),
						'reason'         => __( 'Review progress and continue therapy.', 'ma-lumiere-clinic' ),
						'notes'          => __( 'Sample follow-up created by the demo installer.', 'ma-lumiere-clinic' ),
					)
				);
				if ( is_wp_error( $followup_id ) ) {
					$fail( 'followup', $followup_id );
				} else {
					$track( 'followups', $followup_id );
				}
			}

			// A second, later follow-up so the list is not one-per-patient.
			if ( 0 === $slot % 4 && $last_visit ) {
				$followup_id = ML_Followup_Repository::create(
					array(
						'patient_id'     => $patient_id,
						'visit_id'       => $last_visit,
						'doctor_user_id' => $doctor,
						'followup_date'  => self::future_date( 35 + $slot ),
						'reason'         => __( 'Assess long-term response and close the treatment plan.', 'ma-lumiere-clinic' ),
						'notes'          => __( 'Sample follow-up created by the demo installer.', 'ma-lumiere-clinic' ),
					)
				);
				if ( is_wp_error( $followup_id ) ) {
					$fail( 'followup', $followup_id );
				} else {
					$track( 'followups', $followup_id );
				}
			}

			// Prescription for a different subset, finalised so it gets a number.
			if ( 0 !== $slot % 3 && $last_visit ) {
				$medicine_id = self::pick_medicine( $medicine_ids, $slot );
				$rx_id       = ML_Prescription_Repository::create(
					array(
						'patient_id'        => $patient_id,
						'visit_id'          => $last_visit,
						'doctor_user_id'    => $doctor,
						'prescription_date' => self::past_date( 10 + $slot ),
						'status'            => 'draft',
						'diagnosis'         => __( 'Sample dermatological presentation.', 'ma-lumiere-clinic' ),
						'doctor_notes'      => __( 'Sample prescription created by the demo installer.', 'ma-lumiere-clinic' ),
						'advice'            => __( 'Apply as directed. Avoid direct sun exposure. Review in two weeks.', 'ma-lumiere-clinic' ),
						'follow_up_date'    => self::future_date( 14 + $slot ),
						'items'             => array(
							array(
								'medicine_id' => $medicine_id,
								'dosage'      => '1 tablet',
								'frequency'   => 'Twice daily',
								'timing'      => 'After food',
								'duration'    => '10 days',
								'quantity'    => 20,
								'instructions'=> 'Take with water.',
							),
						),
					)
				);
				if ( is_wp_error( $rx_id ) ) {
					$fail( 'prescription', $rx_id );
				} else {
					$final = ML_Prescription_Repository::finalize( $rx_id );
					if ( is_wp_error( $final ) ) {
						$fail( 'prescription finalise', $final );
					}
					$track( 'prescriptions', $rx_id );
				}
			}

			// Billing for a subset.
			if ( isset( $bill_plan[ $slot ] ) ) {
				$plan     = $bill_plan[ $slot ];
				$invoice_id = ML_Invoice_Repository::create(
					array(
						'patient_id'   => $patient_id,
						'visit_id'     => $last_visit,
						'invoice_date' => self::past_date( 8 + $slot ),
						'due_date'     => self::future_date( 6 + $slot ),
						'items'        => $plan['items'],
					)
				);
				if ( is_wp_error( $invoice_id ) ) {
					$fail( 'invoice', $invoice_id );
				} else {
					$track( 'invoices', $invoice_id );
					$invoice = ML_Invoice_Repository::get( $invoice_id );
					$balance = $invoice ? (float) $invoice['balance_amount'] : 0.0;

					if ( $balance > 0.005 ) {
						$amount = 'partial' === $plan['paid'] ? round( $balance / 2, 2 ) : ( 'none' === $plan['paid'] ? 0 : $balance );

						/*
						 * Instalment one. Kept in a variable so a 'partial' plan
						 * can be refunded after the invoice settles: create()
						 * refuses amounts above the open balance, so the refund row
						 * has to exist before the balance reaches zero.
						 */
						$first_payment = 0;
						if ( $amount > 0.005 ) {
							$payment_id = ML_Payment_Repository::create(
								array(
									'invoice_id'     => $invoice_id,
									'amount'        => $amount,
									'method'        => $plan['method'],
									'payment_date'  => self::past_date( 6 + $slot ) . ' 10:30:00',
									'notes'         => __( 'Sample payment recorded by the demo installer.', 'ma-lumiere-clinic' ),
								)
							);
							if ( is_wp_error( $payment_id ) ) {
								$fail( 'payment', $payment_id );
							} else {
								$first_payment = (int) $payment_id;
								$track( 'payments', $payment_id );
							}
						}

						// Instalment two settles a 'partial' invoice.
						if ( 'partial' === $plan['paid'] ) {
							$rest = round( $balance - $amount, 2 );
							if ( $rest > 0.005 ) {
								$second = ML_Payment_Repository::create(
									array(
										'invoice_id'    => $invoice_id,
										'amount'       => $rest,
										'method'       => 'card',
										'payment_date' => self::past_date( 2 + $slot ) . ' 15:45:00',
										'notes'        => __( 'Second instalment recorded by the demo installer.', 'ma-lumiere-clinic' ),
									)
								);
								if ( is_wp_error( $second ) ) {
									$fail( 'payment', $second );
								} else {
									$track( 'payments', $second );
								}
							}
						}

						/*
						 * Refund the first instalment. recalc() only sums
						 * 'completed' rows, so the invoice drops back to a
						 * partial balance and the refunded row still shows in
						 * Payments and Reports.
						 */
						if ( $first_payment && in_array( $slot, self::REFUND_SLOTS, true ) ) {
							$refund = ML_Payment_Repository::set_status( $first_payment, 'refunded' );
							if ( is_wp_error( $refund ) ) {
								$fail( 'payment refund', $refund );
							}
						}
					}
				}
			}

			/*
			 * Clinical photos. patient_photos.file_path is NOT NULL, so each row
			 * needs a real JPEG behind it: the placeholder is rendered with GD
			 * into a temp file and handed to the repository, which moves it into
			 * the private media directory.
			 */
			if ( $last_visit && $slot < self::PHOTO_COUNT ) {
				$photo = self::seed_photo( $patient_id, $last_visit, $slot );
				if ( is_wp_error( $photo ) ) {
					$fail( 'photo', $photo );
				} else {
					$track( 'patient_photos', $photo['id'] );
					$files[ $photo['id'] ] = $photo['file'];
				}
			}
		}

		$counts = array();
		foreach ( $made as $table => $ids ) {
			$counts[ $table ] = count( array_unique( $ids ) );
		}

		update_option(
			self::OPTION,
			array(
				'installed_at' => current_time( 'mysql' ),
				'version'      => ML_CLINIC_VERSION,
				'complete'     => true,
				'ids'          => array_map( 'array_values', array_map( 'array_unique', $made ) ),
				'files'        => $files,
			),
			false
		);

		ml_audit( 'sample_data_installed', 'sample_data', 0, sprintf( __( 'Sample dataset created: %d records.', 'ma-lumiere-clinic' ), array_sum( $counts ) ) );

		return array(
			'counts' => $counts,
			'errors' => $errors,
		);
	}

	/* ---------------------------------------------------------------------
	 * Remove
	 * ------------------------------------------------------------------ */

	/**
	 * Delete the dataset recorded in the install index.
	 *
	 * Child rows are removed before parents. Patients and medicines are only
	 * deleted when they still carry the demo marker, so a record that has
	 * since been edited into looking "real" is preserved and reported.
	 *
	 * @return array|WP_Error {removed: array<string,int>, kept: int}
	 */
	public static function remove() {
		global $wpdb;

		$index = self::index();
		if ( ! $index ) {
			return new WP_Error( 'ml_sample_absent', __( 'No sample data is installed.', 'ma-lumiere-clinic' ) );
		}

		$removed = array();
		$kept    = 0;

		// 1. Rows addressed purely by parent id - no marker check needed.
		$child_cascades = array(
			'invoice_items'      => array( 'invoices', 'invoice_id' ),
			'prescription_items' => array( 'prescriptions', 'prescription_id' ),
		);
		foreach ( $child_cascades as $child => $parent ) {
			list( $parent_slug, $column ) = $parent;
			$parent_ids = isset( $index[ $parent_slug ] ) ? $index[ $parent_slug ] : array();
			if ( ! $parent_ids || ! ML_Database::table_exists( $child ) ) {
				continue;
			}
			$table = ML_Database::table( $child );
			$in     = implode( ',', array_fill( 0, count( $parent_ids ), '%d' ) );
			$count  = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} IN ({$in})", $parent_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$removed[ $child ] = max( 0, $count );
		}

		// 2. payments are tied to the invoices; record the ids for auditing.
		if ( isset( $index['payments'] ) && ML_Database::table_exists( 'payments' ) ) {
			$removed['payments'] = self::delete_ids( 'payments', $index['payments'] );
		}
		if ( isset( $index['invoices'] ) ) {
			$removed['invoices'] = self::delete_ids( 'invoices', $index['invoices'] );
		}
		if ( isset( $index['prescriptions'] ) ) {
			$removed['prescriptions'] = self::delete_ids( 'prescriptions', $index['prescriptions'] );
		}
		if ( isset( $index['followups'] ) ) {
			$removed['followups'] = self::delete_ids( 'followups', $index['followups'] );
		}
		if ( isset( $index['treatment_sessions'] ) ) {
			$removed['treatment_sessions'] = self::delete_ids( 'treatment_sessions', $index['treatment_sessions'] );
		}
		if ( isset( $index['visits'] ) ) {
			$removed['visits'] = self::delete_ids( 'visits', $index['visits'] );
		}
		if ( isset( $index['appointments'] ) ) {
			$removed['appointments'] = self::delete_ids( 'appointments', $index['appointments'] );
		}

		/*
		 * 3. Photos. Rows and their files are removed through the repository so
		 *    the private media files go with them; the raw sweep afterwards only
		 *    catches rows a partial install left behind.
		 */
		$patient_ids   = isset( $index['patients'] ) ? $index['patients'] : array();
		$photo_removed = 0;
		if ( isset( $index['patient_photos'] ) && ML_Database::table_exists( 'patient_photos' ) ) {
			foreach ( (array) $index['patient_photos'] as $photo_id ) {
				$deleted = ML_Photo_Repository::delete( $photo_id );
				if ( is_wp_error( $deleted ) ) {
					continue;
				}
				$photo_removed++;
			}
		}
		if ( $patient_ids && ML_Database::table_exists( 'patient_photos' ) ) {
			$table = ML_Database::table( 'patient_photos' );
			$in    = implode( ',', array_fill( 0, count( $patient_ids ), '%d' ) );
			$count = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE patient_id IN ({$in})", $patient_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$photo_removed += max( 0, $count );
		}
		$photo_removed += self::purge_photo_files();
		if ( $photo_removed > 0 ) {
			$removed['patient_photos'] = $photo_removed;
		}

		// 3b. Blocked clinic dates.
		if ( isset( $index['clinic_blocked_dates'] ) ) {
			$removed['clinic_blocked_dates'] = self::delete_ids( 'clinic_blocked_dates', $index['clinic_blocked_dates'] );
		}

		// 4. Stock ledger, then batches, then the medicines themselves.
		if ( isset( $index['stock_movements'] ) ) {
			$removed['stock_movements'] = self::delete_ids( 'stock_movements', $index['stock_movements'] );
		}
		if ( isset( $index['medicine_batches'] ) ) {
			$removed['medicine_batches'] = self::delete_ids( 'medicine_batches', $index['medicine_batches'] );
		}
		if ( isset( $index['medicines'] ) ) {
			$deleted = self::delete_ids( 'medicines', $index['medicines'], self::SKU_PREFIX );
			$removed['medicines'] = $deleted['deleted'];
			$kept               += $deleted['kept'];
		}

		// 5. Patients last, marker-guarded.
		if ( $patient_ids ) {
			$deleted = self::delete_ids( 'patients', $patient_ids, '@' . self::EMAIL_DOMAIN );
			$removed['patients'] = $deleted['deleted'];
			$kept              += $deleted['kept'];
		}

		/*
		 * The repositories audit every record they create, so the demo dataset
		 * leaves behind audit rows that point at rows that no longer exist.
		 * Drop exactly those, then record a single summary entry below, so the
		 * audit log still proves the demo data existed and was removed without
		 * being littered with dangling references.
		 */
		$audit_rows = self::purge_audit_rows( $index );
		if ( $audit_rows > 0 ) {
			$removed['audit_logs'] = $audit_rows;
		}

		delete_option( self::OPTION );

		ml_audit(
			'sample_data_removed',
			'sample_data',
			0,
			sprintf(
				/* translators: 1: number of rows removed, 2: number of rows kept. */
				__( 'Sample dataset removed: %1$d rows deleted, %2$d left untouched.', 'ma-lumiere-clinic' ),
				array_sum( $removed ),
				$kept
			)
		);

		return array(
			'removed' => $removed,
			'kept'    => $kept,
		);
	}

	/**
	 * Delete audit-log entries that reference the demo rows in the index.
	 *
	 * Only the exact entity_type/entity_id pairs recorded by the installer are
	 * touched, so audit history for real records is preserved.
	 *
	 * @param array<string,int[]> $index Installer ID index.
	 *
	 * @return int Number of audit rows deleted.
	 */
	private static function purge_audit_rows( array $index ) {
		global $wpdb;

		if ( ! ML_Database::table_exists( 'audit_logs' ) ) {
			return 0;
		}

		// The audit log records singular entity names that differ from the table slugs.
		$entity_map = array(
			'patients'           => 'patient',
			'patient_photos'     => 'patient_photo',
			'visits'             => 'visit',
			'appointments'       => 'appointment',
			'prescriptions'      => 'prescription',
			'prescription_items' => 'prescription_item',
			'followups'          => 'followup',
			'treatment_sessions' => 'treatment_session',
			'medicines'          => 'medicine',
			'medicine_batches'   => 'medicine_batch',
			'stock_movements'    => 'stock_movement',
			'invoices'           => 'invoice',
			'invoice_items'      => 'invoice_item',
			'payments'           => 'payment',
		);

		$total = 0;
		$table = ML_Database::table( 'audit_logs' );

		foreach ( $index as $slug => $ids ) {
			$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
			if ( ! $ids ) {
				continue;
			}
			$entity = isset( $entity_map[ $slug ] ) ? $entity_map[ $slug ] : $slug;
			$in     = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$count  = (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE entity_type = %s AND entity_id IN ({$in})", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					array_merge( array( $entity ), $ids )
				)
			);
			if ( $count > 0 ) {
				$total += $count;
			}
		}

		return $total;
	}

	/**
	 * Delete private media files recorded by the installer.
	 *
	 * Only files inside the plugin's private media directory are touched, and
	 * only those the installer recorded, so real uploads are never at risk.
	 *
	 * @return int Number of files unlinked.
	 */
	private static function purge_photo_files() {
		$deleted = 0;

		foreach ( self::files() as $relative ) {
			$absolute = ML_Photo_Repository::absolute_path( $relative );
			if ( ! $absolute || ! file_exists( $absolute ) ) {
				continue;
			}
			wp_delete_file( $absolute );
			if ( ! file_exists( $absolute ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	/**
	 * Delete rows by primary key, optionally only when a marker column still
	 * carries the demo marker.
	 *
	 * @param string $slug   Table slug.
	 * @param int[]  $ids     Primary keys to delete.
	 * @param string $marker  Optional marker; a value starting with '@' matches
	 *                        a suffix, otherwise it must be a prefix.
	 *
	 * @return int|array Deleted count, or {deleted:int, kept:int} with a marker.
	 */
	private static function delete_ids( $slug, array $ids, $marker = '' ) {
		global $wpdb;

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		if ( ! $ids || ! ML_Database::table_exists( $slug ) ) {
			return $marker ? array( 'deleted' => 0, 'kept' => 0 ) : 0;
		}

		$table = ML_Database::table( $slug );

		if ( '' === $marker ) {
			$in    = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$count = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$in})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return max( 0, $count );
		}

		$is_suffix = '@' === substr( $marker, 0, 1 );
		$needle    = $is_suffix ? substr( $marker, 1 ) : $marker;
		$column    = ( 'patients' === $slug ) ? 'email' : 'sku';
		$op        = $is_suffix ? 'LIKE' : 'LIKE';
		$pattern   = $is_suffix ? '%' . $needle : $needle . '%';

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders are generated.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, {$column} AS marker FROM {$table} WHERE id IN ({$placeholders})", $ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $rows ) {
			return array( 'deleted' => 0, 'kept' => 0 );
		}

		$delete_ids = array();
		$kept       = 0;
		foreach ( $rows as $row ) {
			$value = (string) $row['marker'];
			$match = $is_suffix
				? ( '' !== $value && strlen( $value ) >= strlen( $needle ) && substr( $value, -strlen( $needle ) ) === $needle )
				: ( 0 === strpos( $value, $needle ) );
			if ( $match ) {
				$delete_ids[] = (int) $row['id'];
			} else {
				$kept++;
			}
		}

		if ( ! $delete_ids ) {
			return array( 'deleted' => 0, 'kept' => $kept );
		}

		$in    = implode( ',', array_fill( 0, count( $delete_ids ), '%d' ) );
		$count = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$in})", $delete_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array( 'deleted' => max( 0, $count ), 'kept' => $kept );
	}

	/* ---------------------------------------------------------------------
	 * Seed definitions
	 * ------------------------------------------------------------------ */

	/**
	 * A clinic-capable user to attribute the demo records to.
	 *
	 * @return int User ID, or 0 when none exists.
	 */
	private static function resolve_doctor() {
		$user = get_current_user_id();
		if ( $user && user_can( $user, ML_Settings::CAP ) ) {
			return (int) $user;
		}
		$admins = get_users(
			array(
				'role'       => 'administrator',
				'number'     => 1,
				'fields'     => 'ID',
				'orderby'    => 'ID',
				'order'      => 'ASC',
			)
		);
		return $admins ? (int) $admins[0] : 0;
	}

	/**
	 * Medicine catalogue seed. `key` links a medicine to prescription lines.
	 *
	 * @return array[]
	 */
	private static function medicine_seed() {
		return array(
			array(
				'key'        => 'TETRA',
				'sku'        => 'TETRA01',
				'name'       => 'Tetracycline 500 mg',
				'generic_name' => 'Tetracycline',
				'strength'   => '500 mg',
				'form'       => 'capsule',
				'category'   => 'antibiotic',
				'reorder_level' => 20,
				'requires_prescription' => 1,
				'status'     => 'active',
				'quantity'   => 120,
				'cost'       => 6.50,
				'expiry'     => gmdate( 'Y-m-d', strtotime( '+300 days' ) ),
				'supplier'   => 'Sunrise Pharma',
				'location'   => 'Rack A1',
			),
			array(
				'key'        => 'DOXY',
				'sku'        => 'DOXY01',
				'name'       => 'Doxycycline 100 mg',
				'generic_name' => 'Doxycycline',
				'strength'   => '100 mg',
				'form'       => 'capsule',
				'category'   => 'antibiotic',
				'reorder_level' => 15,
				'requires_prescription' => 1,
				'status'     => 'active',
				'quantity'   => 14,
				'cost'       => 9.75,
				'expiry'     => gmdate( 'Y-m-d', strtotime( '+25 days' ) ),
				'supplier'   => 'Sunrise Pharma',
				'location'   => 'Rack A2',
			),
			array(
				'key'        => 'RETIN',
				'sku'        => 'RETIN01',
				'name'       => 'Isotretinoin 20 mg',
				'generic_name' => 'Isotretinoin',
				'strength'   => '20 mg',
				'form'       => 'capsule',
				'category'   => 'retinoid',
				'reorder_level' => 25,
				'requires_prescription' => 1,
				'status'     => 'active',
				'quantity'   => 60,
				'cost'       => 18.00,
				'expiry'     => gmdate( 'Y-m-d', strtotime( '+400 days' ) ),
				'supplier'   => 'DermaCare Distributors',
				'location'   => 'Rack B1',
			),
			array(
				'key'        => 'META',
				'sku'        => 'META01',
				'name'       => 'Metronidazole 400 mg',
				'generic_name' => 'Metronidazole',
				'strength'   => '400 mg',
				'form'       => 'tablet',
				'category'   => 'antibiotic',
				'reorder_level' => 30,
				'requires_prescription' => 1,
				'status'     => 'active',
				'quantity'   => 200,
				'cost'       => 3.20,
				'expiry'     => gmdate( 'Y-m-d', strtotime( '+200 days' ) ),
				'supplier'   => 'Generic Health Co',
				'location'   => 'Rack A3',
			),
			array(
				'key'        => 'HYDRO',
				'sku'        => 'HYDRO01',
				'name'       => 'Hydrocortisone 1% cream',
				'generic_name' => 'Hydrocortisone',
				'strength'   => '1 %',
				'form'       => 'cream',
				'category'   => 'corticosteroid',
				'reorder_level' => 10,
				'requires_prescription' => 0,
				'status'     => 'active',
				'quantity'   => 8,
				'cost'       => 22.00,
				'expiry'     => gmdate( 'Y-m-d', strtotime( '+120 days' ) ),
				'supplier'   => 'DermaCare Distributors',
				'location'   => 'Rack C2',
			),
			array(
				'key'        => 'NIACIN',
				'sku'        => 'NIA01',
				'name'       => 'Niacinamide 10% serum',
				'generic_name' => 'Niacinamide',
				'strength'   => '10 %',
				'form'       => 'solution',
				'category'   => 'other',
				'reorder_level' => 12,
				'requires_prescription' => 0,
				'status'     => 'active',
				'quantity'   => 45,
				'cost'       => 145.00,
				'expiry'     => gmdate( 'Y-m-d', strtotime( '+500 days' ) ),
				'supplier'   => 'Glow Labs',
				'location'   => 'Rack D1',
			),
			array(
				'key'        => 'SPF',
				'sku'        => 'SPF01',
				'name'       => 'Mineral sunscreen SPF 50',
				'generic_name' => 'Zinc oxide',
				'strength'   => 'SPF 50',
				'form'       => 'cream',
				'category'   => 'sunscreen',
				'reorder_level' => 20,
				'requires_prescription' => 0,
				'status'     => 'active',
				'quantity'   => 9,
				'cost'       => 320.00,
				'expiry'     => gmdate( 'Y-m-d', strtotime( '+350 days' ) ),
				'supplier'   => 'Glow Labs',
				'location'   => 'Rack D2',
			),
			array(
				'key'        => 'KETOC',
				'sku'        => 'KET01',
				'name'       => 'Ketoconazole 2% shampoo',
				'generic_name' => 'Ketoconazole',
				'strength'   => '2 %',
				'form'       => 'solution',
				'category'   => 'antifungal',
				'reorder_level' => 15,
				'requires_prescription' => 0,
				'status'     => 'active',
				'quantity'   => 30,
				'cost'       => 78.00,
				'expiry'     => gmdate( 'Y-m-d', strtotime( '+260 days' ) ),
				'supplier'   => 'Generic Health Co',
				'location'   => 'Rack C1',
			),
		);
	}

	/**
	 * Blocked clinic dates.
	 *
	 * Day offsets stay odd so they never collide with the even-offset
	 * appointments seeded for each patient. ML_Availability_Service reads these
	 * when it offers slots, so the booking calendar shows realistic closures.
	 *
	 * @return array[]
	 */
	private static function blocked_date_seed() {
		return array(
			array(
				'day_offset' => 15,
				'all_day'    => 1,
				'start_time' => null,
				'end_time'   => null,
				'reason'     => __( 'Clinic closed for the afternoon holiday.', 'ma-lumiere-clinic' ),
			),
			array(
				'day_offset' => 17,
				'all_day'    => 1,
				'start_time' => null,
				'end_time'   => null,
				'reason'     => __( 'Staff training day - no appointments.', 'ma-lumiere-clinic' ),
			),
			array(
				'day_offset' => 19,
				'all_day'    => 1,
				'start_time' => null,
				'end_time'   => null,
				'reason'     => __( 'Clinic closed for the public holiday.', 'ma-lumiere-clinic' ),
			),
			array(
				'day_offset' => 21,
				'all_day'    => 0,
				'start_time' => '12:00:00',
				'end_time'   => '14:00:00',
				'reason'     => __( 'Laser room maintenance.', 'ma-lumiere-clinic' ),
			),
			array(
				'day_offset' => 23,
				'all_day'    => 1,
				'start_time' => null,
				'end_time'   => null,
				'reason'     => __( 'Clinic closed for the afternoon holiday.', 'ma-lumiere-clinic' ),
			),
			array(
				'day_offset' => 25,
				'all_day'    => 0,
				'start_time' => '09:00:00',
				'end_time'   => '11:00:00',
				'reason'     => __( 'Physio equipment servicing.', 'ma-lumiere-clinic' ),
			),
			array(
				'day_offset' => 27,
				'all_day'    => 1,
				'start_time' => null,
				'end_time'   => null,
				'reason'     => __( 'Clinic closed for the afternoon holiday.', 'ma-lumiere-clinic' ),
			),
		);
	}

	/**
	 * Age and drain a few demo lots so Inventory > Alerts has content in every
	 * bucket instead of an empty screen.
	 *
	 * Expired lots deliberately keep stock on hand: the expired bucket only
	 * counts units still sitting on the shelf, and write_off_expired() is global
	 * so it must not be used on a shared install.
	 *
	 * @param array<string,int>  $medicine_ids Key => medicine id.
	 * @param array<string,int>  $batch_ids    Key => opening batch id.
	 * @param callable           $track        Index recorder.
	 * @param callable           $fail         Error recorder.
	 *
	 * @return int[] Movement ids created by the adjustments.
	 */
	private static function stock_alert_scenarios( array $medicine_ids, array $batch_ids, callable $track, callable $fail ) {
		/*
		 * Extra lots: two already expired and two inside the 90-day window.
		 */
		$aged = array(
			array(
				'medicine' => 'TETRA',
				'expiry'   => gmdate( 'Y-m-d', strtotime( '-18 days' ) ),
				'quantity' => 12,
				'status'   => 'active',
				'suffix'   => 'EXP',
			),
			array(
				'medicine' => 'HYDRO',
				'expiry'   => gmdate( 'Y-m-d', strtotime( '-4 days' ) ),
				'quantity' => 6,
				'status'   => 'active',
				'suffix'   => 'EXP',
			),
			array(
				'medicine' => 'SPF',
				'expiry'   => gmdate( 'Y-m-d', strtotime( '+21 days' ) ),
				'quantity' => 10,
				'status'   => 'active',
				'suffix'   => 'NEAR',
			),
			array(
				'medicine' => 'NIACIN',
				'expiry'   => gmdate( 'Y-m-d', strtotime( '+60 days' ) ),
				'quantity' => 8,
				'status'   => 'active',
				'suffix'   => 'NEAR',
			),
		);

		$movement_ids = array();

		foreach ( $aged as $row ) {
			$medicine_id = isset( $medicine_ids[ $row['medicine'] ] ) ? (int) $medicine_ids[ $row['medicine'] ] : 0;
			if ( ! $medicine_id ) {
				// Surface the miss instead of quietly dropping the scenario.
				$fail(
					'alert batch ' . $row['medicine'],
					new WP_Error( 'ml_sample_medicine', __( 'The demo medicine for this alert scenario is missing.', 'ma-lumiere-clinic' ) )
				);
				continue;
			}

			$batch_id = ML_Inventory_Service::receive(
				array(
					'medicine_id'       => $medicine_id,
					'batch_number'      => self::SKU_PREFIX . $row['medicine'] . '-' . gmdate( 'Y' ) . $row['suffix'],
					'expiry_date'       => $row['expiry'],
					'supplier'          => 'Demo Supplier',
					'storage_location'  => 'Rack Z1',
					'quantity_received' => $row['quantity'],
					'unit_cost'         => 15.00,
					'received_date'     => gmdate( 'Y-m-d', strtotime( '-120 days' ) ),
					'status'            => $row['status'],
					'notes'             => __( 'Sample lot created to populate the expiry alerts.', 'ma-lumiere-clinic' ),
				)
			);
			if ( is_wp_error( $batch_id ) ) {
				$fail( 'alert batch', $batch_id );
				continue;
			}
			$track( 'medicine_batches', $batch_id );
		}

		/*
		 * Draining movements. Out-of-stock needs every active, unexpired lot for
		 * that medicine at zero, so the opening lot is emptied rather than just
		 * dipped below its reorder level.
		 */
		$drains = array(
			array(
				'medicine' => 'KETOC',
				'delta'    => -30,
				'type'     => 'dispense',
				'notes'    => __( 'Stock exhausted during the sample rush.', 'ma-lumiere-clinic' ),
			),
			array(
				'medicine' => 'RETIN',
				'delta'    => -40,
				'type'     => 'dispense',
				'notes'    => __( 'Stock exhausted during the sample rush.', 'ma-lumiere-clinic' ),
			),
			array(
				'medicine' => 'HYDRO',
				'delta'    => -2,
				'type'     => 'dispense',
				'notes'    => __( 'Drawn down to the reorder level.', 'ma-lumiere-clinic' ),
			),
			array(
				'medicine' => 'SPF',
				'delta'    => -4,
				'type'     => 'wastage',
				'notes'    => __( 'Damaged in transit and written off.', 'ma-lumiere-clinic' ),
			),
			array(
				'medicine' => 'META',
				'delta'    => -5,
				'type'     => 'return',
				'notes'    => __( 'Returned to the shelf from a cancelled order.', 'ma-lumiere-clinic' ),
			),
		);

		foreach ( $drains as $row ) {
			$batch_id = isset( $batch_ids[ $row['medicine'] ] ) ? (int) $batch_ids[ $row['medicine'] ] : 0;
			if ( ! $batch_id ) {
				$fail(
					'stock adjustment ' . $row['medicine'],
					new WP_Error( 'ml_sample_batch', __( 'The demo batch for this alert scenario is missing.', 'ma-lumiere-clinic' ) )
				);
				continue;
			}
			$balance = ML_Inventory_Service::adjust( $batch_id, $row['delta'], $row['type'], $row['notes'] );
			if ( is_wp_error( $balance ) ) {
				$fail( 'stock adjustment', $balance );
			}
		}

		$movement_ids = self::movement_ids_for( $medicine_ids );
		$movement_ids = array_map( 'absint', array_filter( (array) $movement_ids ) );

		return $movement_ids;
	}

	/**
	 * Render a placeholder clinical photo and store it as a patient photo.
	 *
	 * The repository takes a $_FILES-shaped array, so a JPEG is drawn into a
	 * temp path and passed through the normal upload validation.
	 *
	 * @param int $patient_id Patient id.
	 * @param int $visit_id   Visit id.
	 * @param int $slot       Patient slot, used to vary the caption.
	 *
	 * @return array|WP_Error { id: int, file: string } on success.
	 */
	private static function seed_photo( $patient_id, $visit_id, $slot ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return new WP_Error( 'ml_sample_no_gd', __( 'GD is required to create sample photos.', 'ma-lumiere-clinic' ) );
		}

		$areas  = array( 'Face', 'Left cheek', 'Right cheek', 'Forehead', 'Neck', 'Upper back' );
		$types  = array( 'before', 'after', 'progress' );
		$area   = $areas[ $slot % count( $areas ) ];
		$type   = $types[ $slot % count( $types ) ];

		$width  = 640;
		$height = 480;

		$image = imagecreatetruecolor( $width, $height );
		if ( ! $image ) {
			return new WP_Error( 'ml_sample_image', __( 'The sample image could not be created.', 'ma-lumiere-clinic' ) );
		}

		// Neutral skin-tone gradient so the thumbnail is visibly a placeholder.
		$top    = imagecolorallocate( $image, 236, 214, 200 );
		$bottom = imagecolorallocate( $image, 198, 166, 150 );
		for ( $y = 0; $y < $height; $y++ ) {
			$shade = imagecolorallocate(
				$image,
				(int) ( 236 - ( ( 236 - 198 ) * ( $y / $height ) ) ),
				(int) ( 214 - ( ( 214 - 166 ) * ( $y / $height ) ) ),
				(int) ( 200 - ( ( 200 - 150 ) * ( $y / $height ) ) )
			);
			imageline( $image, 0, $y, $width, $y, $shade );
		}
		unset( $top, $bottom );

		$ink   = imagecolorallocate( $image, 60, 42, 38 );
		$paper = imagecolorallocate( $image, 255, 255, 255 );
		imagefilledrectangle( $image, 0, 0, $width, 34, $ink );
		imagefilledrectangle( $image, 0, $height - 34, $width, $height, $ink );

		imagestring( $image, 5, 12, 10, 'SAMPLE PHOTO - NOT A REAL PATIENT', $paper );
		imagestring( $image, 5, 12, $height - 24, $area . ' / ' . $type, $paper );
		imagestring( $image, 3, 12, 44, 'Generated by the Ma Lumiere demo installer.', $ink );

		/*
		 * get_temp_dir() rather than wp_tempnam(), which only exists in newer
		 * WordPress releases and is not needed to produce a unique name here.
		 */
		$tmp = trailingslashit( get_temp_dir() ) . 'ml-sample-photo-' . $slot . '-' . wp_generate_password( 12, false ) . '.jpg';
		if ( ! wp_mkdir_p( dirname( $tmp ) ) ) {
			imagedestroy( $image );
			return new WP_Error( 'ml_sample_temp', __( 'A temporary file could not be created.', 'ma-lumiere-clinic' ) );
		}

		$written = imagejpeg( $image, $tmp, 82 );
		imagedestroy( $image );
		if ( ! $written ) {
			wp_delete_file( $tmp );
			return new WP_Error( 'ml_sample_image', __( 'The sample image could not be written.', 'ma-lumiere-clinic' ) );
		}

		$photo_id = ML_Photo_Repository::create(
			array(
				'patient_id' => (int) $patient_id,
				'visit_id'   => (int) $visit_id,
				'photo_type' => $type,
				'body_area'  => $area,
				'photo_date' => self::past_date( 12 + $slot ),
				'notes'      => __( 'Placeholder image generated by the demo installer.', 'ma-lumiere-clinic' ),
				'file'       => array(
					'name'     => 'sample-photo-' . ( (int) $patient_id ) . '-' . $slot . '.jpg',
					'type'     => 'image/jpeg',
					'tmp_name' => $tmp,
					'error'    => 0,
					'size'     => (int) filesize( $tmp ),
				),
			)
		);

		if ( is_wp_error( $photo_id ) ) {
			wp_delete_file( $tmp );
			return $photo_id;
		}

		$stored = ML_Photo_Repository::get( (int) $photo_id );
		$file   = ( $stored && ! empty( $stored['file_path'] ) ) ? (string) $stored['file_path'] : '';

		// The repository copied the temp file; it is no longer needed.
		wp_delete_file( $tmp );

		return array(
			'id'   => (int) $photo_id,
			'file' => $file,
		);
	}

	/**
	 * Patient seed. All emails sit on the reserved .invalid domain.
	 *
	 * @return array[]
	 */
	private static function patient_seed() {
		$people = array(
			array( 'Aarav', 'Sharma', 'female', '1992-04-18', '+91 98200 11001', 'Bandra West, Mumbai' ),
			array( 'Diya', 'Iyer', 'female', '1988-11-02', '+91 98200 11002', 'Andheri East, Mumbai' ),
			array( 'Rohan', 'Mehta', 'male', '1995-07-23', '+91 98200 11003', 'Powai, Mumbai' ),
			array( 'Ananya', 'Nair', 'female', '1990-02-11', '+91 98200 11004', 'Lower Parel, Mumbai' ),
			array( 'Kabir', 'Singh', 'male', '1985-09-30', '+91 98200 11005', 'Juhu, Mumbai' ),
			array( 'Meera', 'Reddy', 'female', '1998-06-14', '+91 98200 11006', 'Chembur, Mumbai' ),
			array( 'Vivaan', 'Patel', 'male', '1993-12-05', '+91 98200 11007', 'Ghatkopar, Mumbai' ),
			array( 'Ishita', 'Ghosh', 'female', '1986-03-27', '+91 98200 11008', 'Dadar, Mumbai' ),
			array( 'Arjun', 'Kulkarni', 'male', '1991-08-19', '+91 98200 11009', 'Borivali, Mumbai' ),
			array( 'Saanvi', 'Joshi', 'female', '1994-05-08', '+91 98200 11010', 'Malad West, Mumbai' ),
		);

		$cities = array( 'Mumbai', 'Mumbai', 'Mumbai', 'Mumbai', 'Mumbai' );
		$out    = array();

		foreach ( $people as $i => $person ) {
			list( $first, $last, $gender, $dob, $phone, $address ) = $person;
			$out[] = array(
				'first_name'      => $first,
				'last_name'       => $last,
				'date_of_birth'   => $dob,
				'gender'          => $gender,
				'phone'           => $phone,
				'email'           => strtolower( $first . '.' . $last ) . '@' . self::EMAIL_DOMAIN,
				'address'         => $address,
				'city'            => 'Mumbai',
				'state'           => 'Maharashtra',
				'pincode'         => '4000' . str_pad( (string) ( 10 + $i ), 2, '0', STR_PAD_LEFT ),
				'emergency_contact_name'  => $first . ' Sr.',
				'emergency_contact_phone' => '+91 98200 119' . str_pad( (string) $i, 2, '0', STR_PAD_LEFT ),
				'registration_date' => self::past_date( 120 - ( $i * 7 ) ),
				'status'          => 0 === $i ? 'inactive' : 'active',
			);
		}

		return $out;
	}

	/**
	 * How many visits each patient gets.
	 *
	 * @param int $patient_count Number of patients actually created.
	 *
	 * @return int[]
	 */
	private static function visit_plan( $patient_count ) {
		$plan = array();
		for ( $i = 0; $i < max( 1, $patient_count ); $i++ ) {
			$plan[] = ( 0 === $i % 3 ) ? 2 : 1;
		}
		return $plan;
	}

	/**
	 * Future appointment slots. Days and times are staggered so the
	 * repository's overlap check always passes.
	 *
	 * @param int $patient_count Number of patients.
	 *
	 * @return array[]
	 */
	private static function appointment_plan( $patient_count ) {
		$plan   = array();
		$times  = array( '10:00', '11:30', '14:00', '15:30', '16:30' );
		$count  = max( 1, (int) $patient_count );

		for ( $i = 0; $i < $count; $i++ ) {
			$plan[] = array(
				'day_offset'       => 2 + ( $i * 2 ),
				'start_time'       => $times[ $i % count( $times ) ],
				'status'           => 0 === $i % 3 ? 'pending' : 'confirmed',
				'appointment_type' => 0 === $i % 2 ? 'new_consultation' : 'follow_up',
				'source'           => 0 === $i % 2 ? 'website' : 'admin',
				'notes'            => __( 'Sample appointment created by the demo installer.', 'ma-lumiere-clinic' ),
			);
		}

		return $plan;
	}

	/**
	 * Invoice plan keyed by patient slot: line items plus how much is paid.
	 *
	 * @param int $patient_count Number of patients.
	 *
	 * @return array[]
	 */
	private static function billing_plan( $patient_count ) {
		$plan = array();
		// Two 'partial' entries so the demo can show a refund on a settled
		// invoice without leaving the paid bucket empty.
		$paid = array( 'full', 'partial', 'full', 'none', 'partial', 'full' );

		for ( $i = 0; $i < max( 6, (int) floor( $patient_count / 2 ) ); $i++ ) {
			$consultation = 800 + ( $i * 50 );
			$items        = array(
				array(
					'description' => 'Consultation - Dermatology',
					'quantity'    => 1,
					'unit_price'  => $consultation,
					'discount'    => 0,
					'item_type'   => 'consultation',
				),
				array(
					'description' => 'Chemical peel session',
					'quantity'    => 1,
					'unit_price'  => 1500,
					'discount'    => 0,
					'item_type'   => 'treatment',
				),
			);
			if ( 0 === $i % 2 ) {
				$items[] = array(
					'description' => 'Laser hair reduction - underarms',
					'quantity'    => 1,
					'unit_price'  => 2500,
					'discount'    => 200,
					'item_type'   => 'treatment',
				);
			}

			$plan[] = array(
				'items'  => $items,
				'paid'   => $paid[ $i % count( $paid ) ],
				'method' => array( 'upi', 'card', 'cash', 'upi' )[ $i % 4 ],
			);
		}

		return $plan;
	}

	/**
	 * Pick a catalogue medicine for a prescription line.
	 *
	 * @param array<string,int> $medicine_ids Key => medicine id.
	 * @param int               $slot        Patient slot.
	 *
	 * @return int
	 */
	private static function pick_medicine( array $medicine_ids, $slot ) {
		$ids = array_values( array_filter( $medicine_ids ) );
		if ( ! $ids ) {
			return 0;
		}
		return (int) $ids[ $slot % count( $ids ) ];
	}

	/**
	 * Stock movement ids currently recorded against the given medicines.
	 *
	 * @param array<string,int> $medicine_ids Key => medicine id.
	 *
	 * @return int[]
	 */
	private static function movement_ids_for( array $medicine_ids ) {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'absint', $medicine_ids ) ) );
		if ( ! $ids || ! ML_Database::table_exists( 'stock_movements' ) ) {
			return array();
		}

		$table = ML_Database::table( 'stock_movements' );
		$in    = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows  = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE medicine_id IN ({$in})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array_map( 'absint', (array) $rows );
	}

	/* ---------------------------------------------------------------------
	 * Date helpers
	 * ------------------------------------------------------------------ */

	/**
	 * A date `n` days before today.
	 *
	 * @param int $days Days back.
	 *
	 * @return string Y-m-d
	 */
	private static function past_date( $days ) {
		return gmdate( 'Y-m-d', strtotime( '-' . max( 1, (int) $days ) . ' days' ) );
	}

	/**
	 * A date `n` days after today.
	 *
	 * @param int $days Days forward.
	 *
	 * @return string Y-m-d
	 */
	private static function future_date( $days ) {
		return gmdate( 'Y-m-d', strtotime( '+' . max( 1, (int) $days ) . ' days' ) );
	}

	/**
	 * One historical visit row.
	 *
	 * @param int $n Visit index for this patient.
	 *
	 * @return array
	 */
	private static function visit_spec( $n ) {
		$presentations = array(
			array(
				'chief_complaint'  => 'Facial pigmentation and uneven skin tone for six months.',
				'diagnosis'        => 'Melasma',
				'clinical_notes'   => 'Patient reports worsening with sun exposure. No hormonal history reported.',
				'treatment_plan'   => 'Photoprotection, topical antioxidants, review in six weeks.',
				'skin_type'        => 'combination',
				'fitzpatrick_type' => 'IV',
				'pigmentation_notes' => 'Melasmal pattern on cheeks and upper lip.',
				'acne_severity'    => 'mild',
			),
			array(
				'chief_complaint'  => 'Papules and pustules on the back, painful when pressed.',
				'diagnosis'        => 'Acne vulgaris (truncal)',
				'clinical_notes'   => 'Comedonal lesions with perifollicular inflammation.',
				'treatment_plan'   => 'Topical and oral antibiotics, body wash routine, review in six weeks.',
				'skin_type'        => 'oily',
				'fitzpatrick_type' => 'V',
				'acne_severity'    => 'moderate',
				'scarring_notes'   => 'Early post-inflammatory hyperpigmentation on upper back.',
			),
			array(
				'chief_complaint'  => 'Diffuse hair fall noticed while washing, no visible bald patches.',
				'diagnosis'        => 'Telogen effluvium',
				'clinical_notes'   => 'Pull test negative. Thyroid panel advised previously, normal.',
				'treatment_plan'   => 'Biotin supplementation, iron studies, styling advice.',
				'hair_loss_pattern' => 'Diffuse',
				'hair_density'     => 'reduced',
				'scalp_condition'  => 'normal',
				'hair_fall_duration' => '3 months',
			),
		);

		$spec = $presentations[ (int) $n % count( $presentations ) ];
		$spec['visit_date']   = self::past_date( 30 - ( (int) $n * 12 ) );
		$spec['status']       = 0 === (int) $n ? 'completed' : 'open';

		return $spec;
	}
}
