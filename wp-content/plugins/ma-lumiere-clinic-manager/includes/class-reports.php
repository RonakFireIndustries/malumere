<?php
/**
 * Reporting aggregation + export foundation.
 *
 * A single source of truth for date-range summaries: the admin Reports page,
 * the reports PDF/CSV exports and the REST reports endpoint all call
 * ML_Reports::summary() so figures never drift between surfaces.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Reports {

	/**
	 * Hard cap on the daily series length (rows), kept small so the browser
	 * table and the generated documents stay usable.
	 *
	 * @var int
	 */
	const MAX_DAYS = 366;

	/**
	 * Register the admin export handler.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_ml_export_report', array( __CLASS__, 'handle_export' ) );
	}

	/**
	 * Validate and normalise an inclusive date range.
	 *
	 * @param string $from Raw from-date (YYYY-MM-DD or empty).
	 * @param string $to   Raw to-date (YYYY-MM-DD or empty).
	 *
	 * @return array{0:string,1:string} Normalised {from, to}.
	 */
	public static function parse_range( $from = '', $to = '' ) {
		$from = (string) $from;
		$to   = (string) $to;

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $from ) || ! wp_checkdate( (int) substr( $from, 5, 2 ), (int) substr( $from, 8, 2 ), (int) substr( $from, 0, 4 ), $from ) ) {
			$from = gmdate( 'Y-m-01', current_time( 'timestamp' ) );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $to ) || ! wp_checkdate( (int) substr( $to, 5, 2 ), (int) substr( $to, 8, 2 ), (int) substr( $to, 0, 4 ), $to ) ) {
			$to = current_time( 'Y-m-d' );
		}
		if ( $from > $to ) {
			$from = $to;
		}

		$span = (int) ( ( strtotime( $to . ' 23:59:59' ) - strtotime( $from . ' 00:00:00' ) ) / DAY_IN_SECONDS );
		if ( $span > self::MAX_DAYS - 1 ) {
			$to = gmdate( 'Y-m-d', strtotime( $from . " +" . ( self::MAX_DAYS - 1 ) . ' days' ) );
		}

		return array( $from, $to );
	}

	/**
	 * Date-range activity + financial summary.
	 *
	 * @param string $from From date (YYYY-MM-DD).
	 * @param string $to   To date (YYYY-MM-DD).
	 *
	 * @return array<string,mixed>
	 */
	public static function summary( $from = '', $to = '' ) {
		list( $from, $to ) = self::parse_range( $from, $to );

		global $wpdb;
		$t = array(
			'patients'         => ML_Database::table( 'patients' ),
			'appointments'     => ML_Database::table( 'appointments' ),
			'visits'           => ML_Database::table( 'visits' ),
			'prescriptions'    => ML_Database::table( 'prescriptions' ),
			'treatment_sessions' => ML_Database::table( 'treatment_sessions' ),
			'followups'        => ML_Database::table( 'followups' ),
			'invoices'         => ML_Database::table( 'invoices' ),
			'payments'         => ML_Database::table( 'payments' ),
		);

		$count = static function ( $table, $col, $extra = '' ) use ( $wpdb, $from, $to ) {
			return (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$col} BETWEEN %s AND %s{$extra}",
				$from,
				$to
			) );
		};
		$sum   = static function ( $table, $extra = '' ) use ( $wpdb, $from, $to ) {
			return (float) $wpdb->get_var( $wpdb->prepare(
				"SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE DATE(payment_date) BETWEEN %s AND %s{$extra}",
				$from,
				$to
			) );
		};

		$summary = array(
			'range'              => array( 'from' => $from, 'to' => $to ),
			'patients_created'   => $count( $t['patients'], 'registration_date' ),
			'appointments'       => $count( $t['appointments'], 'appointment_date' ),
			'visits'             => $count( $t['visits'], 'visit_date' ),
			'prescriptions_finalized' => $count( $t['prescriptions'], 'prescription_date', " AND status = 'final'" ),
			'prescriptions_drafts'    => $count( $t['prescriptions'], 'prescription_date', " AND status = 'draft'" ),
			'treatment_sessions' => $count( $t['treatment_sessions'], 'scheduled_date' ),
			'followups'          => $count( $t['followups'], 'followup_date' ),
			'invoices_created'   => $count( $t['invoices'], 'invoice_date' ),
			'invoices_paid'      => $count( $t['invoices'], 'invoice_date', " AND payment_status = 'paid'" ),
			'revenue'            => $sum( $t['payments'], " AND payment_status = 'completed'" ),
			'refunds'            => $sum( $t['payments'], " AND payment_status = 'refunded'" ),
			'outstanding'        => (float) $wpdb->get_var( "SELECT COALESCE(SUM(balance_amount),0) FROM {$t['invoices']} WHERE payment_status IN ('unpaid','partial')" ),
			'daily'              => self::daily_series( $from, $to ),
		);

		return $summary;
	}

	/**
	 * Per-day breakdown of invoices issued, collected and refunded.
	 *
	 * @param string $from From date.
	 * @param string $to   To date.
	 *
	 * @return array<int,array{date:string,invoices:int,collected:float,refunds:float}>
	 */
	private static function daily_series( $from, $to ) {
		global $wpdb;
		$t_inv    = ML_Database::table( 'invoices' );
		$t_pay    = ML_Database::table( 'payments' );

		$by_invoices = array();
		foreach ( (array) $wpdb->get_results( $wpdb->prepare(
			"SELECT invoice_date d, COUNT(*) n FROM {$t_inv} WHERE invoice_date BETWEEN %s AND %s GROUP BY invoice_date",
			$from,
			$to
		), ARRAY_A ) as $row ) {
			$by_invoices[ $row['d'] ] = (int) $row['n'];
		}

		$by_payments = array();
		foreach ( (array) $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(payment_date) d, SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) c, SUM(CASE WHEN payment_status = 'refunded' THEN amount ELSE 0 END) r
			 FROM {$t_pay} WHERE DATE(payment_date) BETWEEN %s AND %s GROUP BY DATE(payment_date)",
			$from,
			$to
		), ARRAY_A ) as $row ) {
			$by_payments[ $row['d'] ] = array( (float) $row['c'], (float) $row['r'] );
		}

		$days   = array();
		$cursor = $from;
		while ( $cursor <= $to && count( $days ) < self::MAX_DAYS ) {
			$pay = isset( $by_payments[ $cursor ] ) ? $by_payments[ $cursor ] : array( 0.0, 0.0 );
			$days[] = array(
				'date'      => $cursor,
				'invoices'  => isset( $by_invoices[ $cursor ] ) ? $by_invoices[ $cursor ] : 0,
				'collected' => $pay[0],
				'refunds'   => $pay[1],
			);
			$cursor = gmdate( 'Y-m-d', strtotime( $cursor . ' +1 day' ) );
		}

		return $days;
	}

	/**
	 * Per-clinician breakdown for a period: each doctor's activity counts plus
	 * revenue/refunds attributed through their invoices (invoice → visit, then
	 * visit → appointment as a fallback).
	 *
	 * @param string $from From date (YYYY-MM-DD).
	 * @param string $to   To date (YYYY-MM-DD).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function clinicians( $from = '', $to = '' ) {
		list( $from, $to ) = self::parse_range( $from, $to );

		global $wpdb;
		$t = array(
			'visits'             => ML_Database::table( 'visits' ),
			'prescriptions'      => ML_Database::table( 'prescriptions' ),
			'treatment_sessions' => ML_Database::table( 'treatment_sessions' ),
			'followups'          => ML_Database::table( 'followups' ),
			'appointments'       => ML_Database::table( 'appointments' ),
			'invoices'           => ML_Database::table( 'invoices' ),
			'payments'           => ML_Database::table( 'payments' ),
		);

		$empty = static function () {
			return array(
				'visits'                  => 0,
				'prescriptions_finalized' => 0,
				'treatment_sessions'      => 0,
				'followups'               => 0,
				'appointments'            => 0,
				'revenue'                 => 0.0,
				'refunds'                 => 0.0,
			);
		};

		$agg = array();
		$tally = static function ( $table, $col, $key, $extra = '' ) use ( &$agg, $wpdb, $from, $to, $empty ) {
			foreach ( (array) $wpdb->get_results( $wpdb->prepare(
				"SELECT doctor_user_id d, COUNT(*) n FROM {$table} WHERE {$col} BETWEEN %s AND %s AND doctor_user_id IS NOT NULL AND doctor_user_id > 0{$extra} GROUP BY doctor_user_id",
				$from,
				$to
			), ARRAY_A ) as $r ) {
				$d = (int) $r['d'];
				if ( ! isset( $agg[ $d ] ) ) {
					$agg[ $d ] = $empty();
				}
				$agg[ $d ][ $key ] = (int) $r['n'];
			}
		};
		$tally( $t['visits'], 'visit_date', 'visits' );
		$tally( $t['prescriptions'], 'prescription_date', 'prescriptions_finalized', " AND status = 'final'" );
		$tally( $t['treatment_sessions'], 'scheduled_date', 'treatment_sessions' );
		$tally( $t['followups'], 'followup_date', 'followups' );
		$tally( $t['appointments'], 'appointment_date', 'appointments' );

		foreach ( (array) $wpdb->get_results( $wpdb->prepare(
			"SELECT p.payment_status st, p.amount amt, COALESCE( v.doctor_user_id, a.doctor_user_id, 0 ) d
			   FROM {$t['payments']} p
			   JOIN {$t['invoices']} i ON i.id = p.invoice_id
			   LEFT JOIN {$t['visits']} v ON i.visit_id = v.id
			   LEFT JOIN {$t['appointments']} a ON i.appointment_id = a.id
			  WHERE DATE(p.payment_date) BETWEEN %s AND %s AND p.payment_status IN ( 'completed', 'refunded' )",
			$from,
			$to
		), ARRAY_A ) as $r ) {
			$d = (int) $r['d'];
			if ( ! isset( $agg[ $d ] ) ) {
				$agg[ $d ] = $empty();
			}
			if ( 'completed' === $r['st'] ) {
				$agg[ $d ]['revenue'] = round( (float) $agg[ $d ]['revenue'] + (float) $r['amt'], 2 );
			} else {
				$agg[ $d ]['refunds'] = round( (float) $agg[ $d ]['refunds'] + (float) $r['amt'], 2 );
			}
		}

		$rows = array();
		foreach ( $agg as $d => $data ) {
			$user      = get_userdata( $d );
			$data['doctor_user_id'] = $d;
			$data['doctor_name']    = $user ? ( $user->display_name ? (string) $user->display_name : (string) $user->user_login ) : sprintf( __( 'Clinician #%d', 'ma-lumiere-clinic' ), $d );
			$rows[] = $data;
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				if ( (int) $b['visits'] !== (int) $a['visits'] ) {
					return (int) $b['visits'] <=> (int) $a['visits'];
				}
				return strcasecmp( (string) $a['doctor_name'], (string) $b['doctor_name'] );
			}
		);

		return $rows;
	}

	/**
	 * Render the payments-line CSV body for a summary, optionally followed by
	 * the per-clinician breakdown.
	 *
	 * @param array<string,mixed> $summary    Pre-computed summary.
	 * @param array               $clinicians Optional ML_Reports::clinicians() rows.
	 *
	 * @return string CSV bytes (UTF-8, CRLF terminators), with BOM for Excel.
	 */
	public static function export_csv( array $summary, array $clinicians = array() ) {
		$out   = fopen( 'php://temp', 'w+' );
		fwrite( $out, "\xEF\xBB\xBF" );

		$row = static function ( $r ) use ( $out ) {
			fputcsv( $out, $r );
		};

		$row( array( 'Ma Lumière Clinic — ' . __( 'Activity report', 'ma-lumiere-clinic' ) ) );
		$from = isset( $summary['range']['from'] ) ? $summary['range']['from'] : '';
		$to   = isset( $summary['range']['to'] ) ? $summary['range']['to'] : '';
		$row( array( __( 'Period', 'ma-lumiere-clinic' ), $from, $to ) );
		$row( array(
			__( 'Patients registered', 'ma-lumiere-clinic' ),
			__( 'Appointments', 'ma-lumiere-clinic' ),
			__( 'Visits', 'ma-lumiere-clinic' ),
			__( 'Prescriptions finalized', 'ma-lumiere-clinic' ),
			__( 'Treatment sessions', 'ma-lumiere-clinic' ),
			__( 'Follow-ups', 'ma-lumiere-clinic' ),
			__( 'Invoices issued', 'ma-lumiere-clinic' ),
			__( 'Invoices paid', 'ma-lumiere-clinic' ),
			__( 'Revenue', 'ma-lumiere-clinic' ),
			__( 'Refunds', 'ma-lumiere-clinic' ),
			__( 'Outstanding', 'ma-lumiere-clinic' ),
		) );
		$row( array(
			(int) ( $summary['patients_created'] ?? 0 ),
			(int) ( $summary['appointments'] ?? 0 ),
			(int) ( $summary['visits'] ?? 0 ),
			(int) ( $summary['prescriptions_finalized'] ?? 0 ),
			(int) ( $summary['treatment_sessions'] ?? 0 ),
			(int) ( $summary['followups'] ?? 0 ),
			(int) ( $summary['invoices_created'] ?? 0 ),
			(int) ( $summary['invoices_paid'] ?? 0 ),
			(float) ( $summary['revenue'] ?? 0 ),
			(float) ( $summary['refunds'] ?? 0 ),
			(float) ( $summary['outstanding'] ?? 0 ),
		) );
		$row( array() );
		$row( array( __( 'Date', 'ma-lumiere-clinic' ), __( 'Invoices issued', 'ma-lumiere-clinic' ), __( 'Collected', 'ma-lumiere-clinic' ), __( 'Refunded', 'ma-lumiere-clinic' ) ) );
		foreach ( (array) ( $summary['daily'] ?? array() ) as $day ) {
			$row( array( $day['date'], (int) $day['invoices'], (float) $day['collected'], (float) $day['refunds'] ) );
		}

		if ( $clinicians ) {
			$row( array() );
			$row( array( __( 'Activity by clinician', 'ma-lumiere-clinic' ) ) );
			$row( array(
				__( 'Clinician', 'ma-lumiere-clinic' ),
				__( 'Appointments', 'ma-lumiere-clinic' ),
				__( 'Visits', 'ma-lumiere-clinic' ),
				__( 'Prescriptions finalized', 'ma-lumiere-clinic' ),
				__( 'Treatment sessions', 'ma-lumiere-clinic' ),
				__( 'Follow-ups', 'ma-lumiere-clinic' ),
				__( 'Revenue', 'ma-lumiere-clinic' ),
				__( 'Refunds', 'ma-lumiere-clinic' ),
			) );
			foreach ( $clinicians as $c ) {
				$row( array(
					(string) ( $c['doctor_name'] ?? '' ),
					(int) ( $c['appointments'] ?? 0 ),
					(int) ( $c['visits'] ?? 0 ),
					(int) ( $c['prescriptions_finalized'] ?? 0 ),
					(int) ( $c['treatment_sessions'] ?? 0 ),
					(int) ( $c['followups'] ?? 0 ),
					(float) ( $c['revenue'] ?? 0 ),
					(float) ( $c['refunds'] ?? 0 ),
				) );
			}
		}

		rewind( $out );
		$content = stream_get_contents( $out );
		fclose( $out );
		return $content;
	}

	/**
	 * admin_post handler for CSV/PDF export.
	 *
	 * @return void
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'ml_manage_clinic' ) && ! current_user_can( 'ml_view_reports' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'ma-lumiere-clinic' ), 403 );
		}
		check_admin_referer( 'ml_export_report' );

		$format = isset( $_GET['export'] ) ? sanitize_key( wp_unslash( $_GET['export'] ) ) : 'csv';
		if ( ! in_array( $format, array( 'csv', 'pdf' ), true ) ) {
			$format = 'csv';
		}

		$from    = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to      = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$doctor  = isset( $_GET['doctor'] ) ? absint( $_GET['doctor'] ) : 0;
		$summary = self::summary( $from, $to );
		$doctors = self::clinicians( $from, $to );
		if ( $doctor ) {
			$doctors = array_values( array_filter(
				$doctors,
				static function ( $c ) use ( $doctor ) {
					return (int) $c['doctor_user_id'] === (int) $doctor;
				}
			) );
		}

		ML_Audit_Log::record(
			'report_exported',
			'report',
			0,
			sprintf( __( 'Report exported (%s) for %s → %s', 'ma-lumiere-clinic' ), strtoupper( $format ), $summary['range']['from'], $summary['range']['to'] )
		);

		$stamp  = $summary['range']['from'] . '_' . $summary['range']['to'];
		$name   = 'ml-report-' . $stamp;

		if ( 'pdf' === $format ) {
			require_once ML_CLINIC_PATH . 'includes/class-report-pdf.php';
			$pdf = ML_Report_Pdf::generate( $summary, $doctors );
			if ( is_wp_error( $pdf ) ) {
				wp_die( esc_html( $pdf->get_error_message() ), 500 );
			}
			nocache_headers();
			header( 'Content-Type: application/pdf; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="' . $name . '.pdf"' );
			header( 'Content-Length: ' . strlen( $pdf['data'] ) );
			echo $pdf['data']; // phpcs:ignore WordPress.Security.EscapeOutput -- binary PDF body.
			exit;
		}

		$csv = self::export_csv( $summary, $doctors );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $name . '.csv"' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput -- CSV body is built from prepared/scalar values.
		exit;
	}
}