<?php
/**
 * Report summary PDF (dompdf).
 *
 * Mirrors the invoice/prescription PDFs: dompdf is lazy-loaded from the plugin
 * vendor dir, remote fetching stays disabled, and the caller has already
 * performed the capability/nonce checks.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Report_Pdf {

	/**
	 * Rendered report PDF bytes.
	 *
	 * @param array<string,mixed> $summary    Pre-computed ML_Reports summary.
	 * @param array               $clinicians Optional ML_Reports::clinicians() rows.
	 *
	 * @return array|WP_Error { filename, data }.
	 */
	public static function generate( array $summary, array $clinicians = array() ) {
		$renderer = self::load_dompdf();
		if ( is_wp_error( $renderer ) ) {
			return $renderer;
		}

		$clinic_name  = (string) ML_Settings::get( 'clinic.clinic_name', __( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ) );
		$clinic_addr  = trim( (string) ML_Settings::get( 'clinic.address', '' ) );
		$clinic_phone = (string) ML_Settings::get( 'clinic.phone', '' );
		$clinic_email = (string) ML_Settings::get( 'clinic.email', '' );

		$from = isset( $summary['range']['from'] ) ? $summary['range']['from'] : '';
		$to   = isset( $summary['range']['to'] ) ? $summary['range']['to'] : '';

		$metric = static function ( $label, $value ) {
			return '<tr><td>' . esc_html( $label ) . '</td><td class="num">' . esc_html( $value ) . '</td></tr>';
		};

		$money = static function ( $value ) {
			return ml_money( (float) $value );
		};

		$rows = $metric( __( 'Patients registered', 'ma-lumiere-clinic' ), number_format_i18n( (int) $summary['patients_created'] ) )
			. $metric( __( 'Appointments', 'ma-lumiere-clinic' ), number_format_i18n( (int) $summary['appointments'] ) )
			. $metric( __( 'Visits', 'ma-lumiere-clinic' ), number_format_i18n( (int) $summary['visits'] ) )
			. $metric( __( 'Prescriptions finalized', 'ma-lumiere-clinic' ), number_format_i18n( (int) $summary['prescriptions_finalized'] ) )
			. $metric( __( 'Treatment sessions', 'ma-lumiere-clinic' ), number_format_i18n( (int) $summary['treatment_sessions'] ) )
			. $metric( __( 'Follow-ups', 'ma-lumiere-clinic' ), number_format_i18n( (int) $summary['followups'] ) )
			. $metric( __( 'Invoices issued', 'ma-lumiere-clinic' ), number_format_i18n( (int) $summary['invoices_created'] ) )
			. $metric( __( 'Invoices paid', 'ma-lumiere-clinic' ), number_format_i18n( (int) $summary['invoices_paid'] ) )
			. $metric( __( 'Revenue', 'ma-lumiere-clinic' ), $money( $summary['revenue'] ) )
			. $metric( __( 'Refunds', 'ma-lumiere-clinic' ), $money( $summary['refunds'] ) )
			. $metric( __( 'Outstanding', 'ma-lumiere-clinic' ), $money( $summary['outstanding'] ) );

		$daily = '';
		if ( ! empty( $summary['daily'] ) ) {
			foreach ( (array) $summary['daily'] as $day ) {
				$daily .= '<tr>'
					. '<td>' . esc_html( $day['date'] ) . '</td>'
					. '<td class="num">' . number_format_i18n( (int) $day['invoices'] ) . '</td>'
					. '<td class="num">' . esc_html( $money( $day['collected'] ) ) . '</td>'
					. '<td class="num">' . esc_html( $money( $day['refunds'] ) ) . '</td>'
					. '</tr>';
			}
			$daily = '<h2>' . esc_html__( 'Daily breakdown', 'ma-lumiere-clinic' ) . '</h2>'
				. '<table class="items"><thead><tr>'
				. '<th>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Invoices issued', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Collected', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Refunded', 'ma-lumiere-clinic' ) . '</th>'
				. '</tr></thead><tbody>' . $daily . '</tbody></table>';
		}

		$by_doctor = '';
		if ( $clinicians ) {
			$doc_rows = '';
			foreach ( (array) $clinicians as $c ) {
				$doc_rows .= '<tr>'
					. '<td>' . esc_html( (string) ( $c['doctor_name'] ?? '' ) ) . '</td>'
					. '<td class="num">' . number_format_i18n( (int) ( $c['appointments'] ?? 0 ) ) . '</td>'
					. '<td class="num">' . number_format_i18n( (int) ( $c['visits'] ?? 0 ) ) . '</td>'
					. '<td class="num">' . number_format_i18n( (int) ( $c['prescriptions_finalized'] ?? 0 ) ) . '</td>'
					. '<td class="num">' . number_format_i18n( (int) ( $c['treatment_sessions'] ?? 0 ) ) . '</td>'
					. '<td class="num">' . number_format_i18n( (int) ( $c['followups'] ?? 0 ) ) . '</td>'
					. '<td class="num">' . esc_html( $money( $c['revenue'] ) ) . '</td>'
					. '<td class="num">' . esc_html( $money( $c['refunds'] ) ) . '</td>'
					. '</tr>';
			}
			$by_doctor = '<h2>' . esc_html__( 'Activity by clinician', 'ma-lumiere-clinic' ) . '</h2>'
				. '<table class="items"><thead><tr>'
				. '<th>' . esc_html__( 'Clinician', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Appointments', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Visits', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Prescriptions finalized', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Treatment sessions', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Follow-ups', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Revenue', 'ma-lumiere-clinic' ) . '</th>'
				. '<th class="num">' . esc_html__( 'Refunds', 'ma-lumiere-clinic' ) . '</th>'
				. '</tr></thead><tbody>' . $doc_rows . '</tbody></table>';
		}

		$html = '<!DOCTYPE html><html><head><meta charset="utf-8">' . self::css() . '</head><body>'
			. '<div class="head">'
			. '<div class="clinic"><div class="name">' . esc_html( $clinic_name ) . '</div>'
			. ( $clinic_addr ? '<div class="muted">' . esc_html( $clinic_addr ) . '</div>' : '' )
			. ( $clinic_phone || $clinic_email ? '<div class="muted">' . esc_html( implode( '  •  ', array_filter( array( $clinic_phone, $clinic_email ) ) ) ) . '</div>' : '' )
			. '</div>'
			. '<div class="rptbadge">' . esc_html__( 'ACTIVITY REPORT', 'ma-lumiere-clinic' ) . '</div>'
			. '</div>'
			. '<hr>'
			. '<p class="meta">' . esc_html__( 'Period', 'ma-lumiere-clinic' ) . ': <strong>' . esc_html( $from . ' → ' . $to ) . '</strong></p>'
			. '<h1>' . esc_html__( 'Clinic activity & financial summary', 'ma-lumiere-clinic' ) . '</h1>'
			. '<table class="sum">' . $rows . '</table>'
			. $daily
			. $by_doctor
			. '<div class="foot muted">' . esc_html__( 'Generated by', 'ma-lumiere-clinic' ) . ' ' . esc_html( $clinic_name ) . '</div>'
			. '</body></html>';

		$renderer['dompdf']->loadHtml( $html );
		$renderer['dompdf']->render();

		return array(
			'filename' => 'ml-report-' . $from . '_' . $to . '.pdf',
			'data'     => $renderer['dompdf']->output(),
		);
	}

	/**
	 * Lazy-load the dompdf Composer autoloader.
	 *
	 * @return array|WP_Error ['dompdf' => Dompdf\Dompdf].
	 */
	private static function load_dompdf() {
		static $autoloaded = false;
		$autoload = ML_CLINIC_PATH . 'vendor/autoload.php';
		if ( ! is_readable( $autoload ) ) {
			return new WP_Error(
				'ml_pdf_missing_dep',
				sprintf(
					__( 'The PDF generator is not ready yet — the dompdf library was not found. Run: composer require dompdf/dompdf inside the plugin folder (%s).', 'ma-lumiere-clinic' ),
					ML_CLINIC_PATH
				)
			);
		}
		if ( ! $autoloaded ) {
			require_once $autoload;
			$autoloaded = true;
		}
		if ( ! class_exists( 'Dompdf\\Dompdf' ) ) {
			return new WP_Error( 'ml_pdf_missing_dep', __( 'The dompdf library is installed but could not be loaded. Please re-run composer install in the plugin folder.', 'ma-lumiere-clinic' ) );
		}

		$helper = new Dompdf\Options();
		$helper->set( 'isRemoteEnabled', false );
		$helper->set( 'isFontSubsettingEnabled', true );
		$helper->set( 'defaultFont', 'DejaVu Sans' );

		return array(
			'dompdf' => new Dompdf\Dompdf( $helper ),
		);
	}

	/**
	 * Inline PDF stylesheet.
	 *
	 * @return string
	 */
	private static function css() {
		return '<style>
			@page { margin: 22px 28px; }
			body  { font-family: "DejaVu Sans", sans-serif; color: #1f2733; font-size: 10.5pt; line-height: 1.45; }
			.head { display: block; overflow: hidden; }
			.clinic { float: left; }
			.name  { font-size: 15pt; font-weight: 700; letter-spacing: .3px; }
			.rptbadge { float: right; border: 1.5pt solid #16233a; color: #16233a; font-weight: 700;
				padding: 4pt 10pt; border-radius: 3pt; letter-spacing: 1.5pt; font-size: 9pt; }
			hr { border: 0; border-top: .75pt solid #c9d2dc; margin: 10pt 0 12pt; }
			.meta { margin: 0 0 4pt; }
			h1 { font-size: 13pt; margin: 6pt 0 12pt; }
			h2 { font-size: 11pt; margin: 16pt 0 8pt; }
			.muted { color: #67788c; font-size: 9pt; }
			table.sum { width: 52%; border-collapse: collapse; margin-bottom: 8pt; }
			table.sum td { padding: 3.5pt 6pt; font-size: 10pt; border-bottom: .5pt solid #e2e8ef; }
			table.sum td.num { text-align: right; font-weight: 600; }
			table.items { width: 100%; border-collapse: collapse; margin-bottom: 8pt; }
			table.items th, table.items td { border: .5pt solid #c9d2dc; padding: 4pt 6pt; font-size: 9.5pt; text-align: left; }
			table.items th { background: #f2f6fa; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .5pt; color: #526173; }
			table.items td.num, table.items th.num { text-align: right; }
			.foot { margin-top: 20pt; border-top: .5pt solid #e2e8ef; padding-top: 5pt; }
		</style>';
	}
}