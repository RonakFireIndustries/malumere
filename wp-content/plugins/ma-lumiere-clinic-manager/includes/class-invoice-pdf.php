<?php
/**
 * Invoice PDF generator (dompdf).
 *
 * Mirrors the prescription PDF: dompdf is lazy-loaded from the plugin vendor
 * dir, remote fetching is disabled, and the document is rendered server-side.
 * Ownership/permission checks are the caller's responsibility.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Invoice_Pdf {

	/**
	 * Rendered invoice PDF bytes.
	 *
	 * @param int $invoice_id Invoice ID.
	 *
	 * @return array|WP_Error { filename, data }.
	 */
	public static function generate( $invoice_id ) {
		$invoice = ML_Invoice_Repository::get( $invoice_id );
		if ( ! $invoice ) {
			return new WP_Error( 'ml_pdf_not_found', __( 'Invoice not found.', 'ma-lumiere-clinic' ) );
		}

		$patient = ML_Patient_Repository::get( (int) $invoice['patient_id'] );
		if ( ! $patient ) {
			return new WP_Error( 'ml_pdf_patient', __( 'The linked patient no longer exists.', 'ma-lumiere-clinic' ) );
		}

		$renderer = self::load_dompdf();
		if ( is_wp_error( $renderer ) ) {
			return $renderer;
		}
		$dompdf = $renderer['dompdf'];

		$clinic_name  = (string) ML_Settings::get( 'clinic.clinic_name', __( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ) );
		$clinic_addr  = trim( (string) ML_Settings::get( 'clinic.address', '' ) );
		$clinic_phone = (string) ML_Settings::get( 'clinic.phone', '' );
		$clinic_email = (string) ML_Settings::get( 'clinic.email', '' );

		$currency = (string) ( ML_Settings::get( 'billing.currency', 'INR' ) ?: 'INR' );

		$items = ML_Invoice_Repository::items( $invoice_id );
		$rows  = '';
		if ( $items ) {
			foreach ( $items as $index => $item ) {
				$rows .= '<tr>'
					. '<td class="num">' . ( $index + 1 ) . '</td>'
					. '<td>' . esc_html( (string) $item['description'] ) . '</td>'
					. '<td class="num">' . esc_html( self::qty( $item['quantity'] ) ) . '</td>'
					. '<td class="num">' . esc_html( self::money( $item['unit_price'], $currency ) ) . '</td>'
					. ( (float) $item['discount'] > 0 ? '<td class="num">−' . esc_html( self::money( $item['discount'], $currency ) ) . '</td>' : '<td class="num">—</td>' )
					. '<td class="num">' . esc_html( self::money( $item['line_total'], $currency ) ) . '</td>'
					. '</tr>';
			}
		} else {
			$rows = '<tr><td colspan="6" class="muted">' . __( 'No line items.', 'ma-lumiere-clinic' ) . '</td></tr>';
		}

		$patient_line = esc_html( trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ) );
		if ( ! empty( $patient['patient_uid'] ) ) {
			$patient_line .= ' <span class="muted">(' . esc_html( (string) $patient['patient_uid'] ) . ')</span>';
		}
		if ( ! empty( $patient['mobile'] ) ) {
			$patient_line .= ' — ' . esc_html( (string) $patient['mobile'] );
		}

		$total_row = $invoice['discount'] > 0
			? self::sum_row( __( 'Subtotal', 'ma-lumiere-clinic' ), self::money( $invoice['subtotal'], $currency ), 1 )
			. self::sum_row( __( 'Discount', 'ma-lumiere-clinic' ), '−' . self::money( $invoice['discount'], $currency ), 1 )
			: '';
		$tax_row = $invoice['tax'] > 0
			? self::sum_row( __( 'Tax', 'ma-lumiere-clinic' ), self::money( $invoice['tax'], $currency ), 1 )
			: '';

		$balance   = (float) $invoice['balance_amount'];
		$paid      = (float) $invoice['paid_amount'];
		$due       = $balance > 0;
		$status_lbl = array(
			'unpaid'  => __( 'UNPAID', 'ma-lumiere-clinic' ),
			'partial' => __( 'PARTIALLY PAID', 'ma-lumiere-clinic' ),
			'paid'    => __( 'PAID', 'ma-lumiere-clinic' ),
			'void'    => __( 'VOID', 'ma-lumiere-clinic' ),
		);
		$status_text = isset( $status_lbl[ $invoice['payment_status'] ] ) ? $status_lbl[ $invoice['payment_status'] ] : strtoupper( (string) $invoice['payment_status'] );

		$issued  = esc_html( ml_date( $invoice['invoice_date'] ) );
		$due_due = ! empty( $invoice['due_date'] ) ? '<span>' . esc_html__( 'Due date', 'ma-lumiere-clinic' ) . ': <strong>' . esc_html( ml_date( $invoice['due_date'] ) ) . '</strong></span>' : '';

		$html = '<!DOCTYPE html><html><head><meta charset="utf-8">' . self::css() . '</head><body>'
			. '<div class="head">'
			. '<div class="clinic"><div class="name">' . esc_html( $clinic_name ) . '</div>'
			. ( $clinic_addr ? '<div class="muted">' . esc_html( $clinic_addr ) . '</div>' : '' )
			. ( $clinic_phone || $clinic_email ? '<div class="muted">' . esc_html( implode( '  •  ', array_filter( array( $clinic_phone, $clinic_email ) ) ) ) . '</div>' : '' )
			. '</div>'
			. '<div class="invbadge">' . esc_html__( 'INVOICE', 'ma-lumiere-clinic' ) . '<br><span class="num serif">' . esc_html( (string) $invoice['invoice_number'] ) . '</span></div>'
			. '</div>'
			. '<hr>'
			. '<p class="meta">'
			. '<span>' . esc_html__( 'Billed to', 'ma-lumiere-clinic' ) . ': <strong>' . $patient_line . '</strong></span>'
			. '<span>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . ': <strong>' . $issued . '</strong></span>'
			. $due_due
			. '</p>'
			. '<table class="items">'
			. '<thead><tr><th class="num">#</th><th>' . esc_html__( 'Description', 'ma-lumiere-clinic' ) . '</th><th class="num">' . esc_html__( 'Qty', 'ma-lumiere-clinic' ) . '</th><th class="num">' . esc_html__( 'Rate', 'ma-lumiere-clinic' ) . '</th><th class="num">' . esc_html__( 'Disc', 'ma-lumiere-clinic' ) . '</th><th class="num">' . esc_html__( 'Amount', 'ma-lumiere-clinic' ) . '</th></tr></thead>'
			. '<tbody>' . $rows . '</tbody>'
			. '</table>'
			. '<table class="sum">'
			. $total_row . $tax_row
			. self::sum_row( __( 'Total', 'ma-lumiere-clinic' ), self::money( $invoice['total'], $currency ), 1 )
			. ( $paid > 0 ? self::sum_row( __( 'Paid', 'ma-lumiere-clinic' ), self::money( $paid, $currency ), 1 ) : '' )
			. ( $due || $paid > 0 ? self::sum_row( __( 'Balance due', 'ma-lumiere-clinic' ), self::money( $balance, $currency ), $due ? 2 : 1 ) : '' )
			. '</table>'
			. '<p class="pstatus ' . esc_attr( $invoice['payment_status'] ) . '">' . esc_html( $status_text ) . '</p>'
			. '<div class="foot">
				<div class="muted">' . esc_html__( 'Issued electronically by Ma Lumière Clinic.', 'ma-lumiere-clinic' ) . '</div>
				<div class="muted">' . esc_html__( 'Thank you for your business.', 'ma-lumiere-clinic' ) . '</div>
				<div class="clinicfoot muted">' . esc_html( $clinic_name ) . '</div>
			</div>'
			. '</body></html>';

		$dompdf->loadHtml( $html );
		$dompdf->render();

		$filename = 'invoice-' . strtolower( str_replace( array( ' ', '_' ), '-', (string) $invoice['invoice_number'] ) ) . '.pdf';

		return array(
			'filename' => $filename,
			'data'     => $dompdf->output(),
		);
	}

	/**
	 * Single summary row.
	 *
	 * @param string   $label   Row label.
	 * @param string   $value   Formatted amount.
	 * @param int|bool $weight  1 = label left, 2 = emphasized total.
	 *
	 * @return string
	 */
	private static function sum_row( $label, $value, $weight = 1 ) {
		$cls = 2 === $weight ? ' class="total"' : '';
		return '<tr' . $cls . '><td>' . esc_html( $label ) . '</td><td>' . esc_html( $value ) . '</td></tr>';
	}

	/**
	 * Quantity without trailing decimals.
	 *
	 * @param string|int|float $qty Quantity.
	 *
	 * @return string
	 */
	private static function qty( $qty ) {
		$qty = (float) $qty;
		return ( abs( $qty - round( $qty ) ) < 0.01 ) ? (string) (int) $qty : rtrim( rtrim( sprintf( '%.2f', $qty ), '0' ), '.' );
	}

	/**
	 * Currency-formatted amount.
	 *
	 * @param string|int|float $amount   Amount.
	 * @param string           $currency Currency code.
	 *
	 * @return string
	 */
	private static function money( $amount, $currency ) {
		$formatted = number_format_i18n( (float) $amount, 2 );
		if ( 'INR' === $currency ) {
			return '₹' . $formatted;
		}
		return $formatted . ' ' . $currency;
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
			.invbadge { float: right; border: 1.5pt solid #16233a; color: #16233a; font-weight: 700;
				padding: 4pt 10pt; border-radius: 3pt; letter-spacing: 1.5pt; font-size: 10pt; text-align: center; }
			.invbadge .num { letter-spacing: .5pt; }
			hr { border: 0; border-top: .75pt solid #c9d2dc; margin: 10pt 0 12pt; }
			.meta { margin: 0 0 12pt; display: block; }
			.meta span { margin-right: 14pt; white-space: normal; }
			.serif { font-weight: 700; }
			.muted { color: #67788c; font-size: 9pt; }
			table.items { width: 100%; border-collapse: collapse; margin-bottom: 8pt; }
			table.items th, table.items td { border: .5pt solid #c9d2dc; padding: 4pt 6pt; font-size: 9.5pt; vertical-align: top; text-align: left; }
			table.items th { background: #f2f6fa; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .5pt; color: #526173; text-align: left; }
			table.items td.num, table.items th.num { text-align: right; }
			table.sum { float: right; width: 46%; border-collapse: collapse; margin-bottom: 10pt; }
			table.sum td { padding: 2.5pt 6pt; font-size: 9.5pt; border-bottom: .5pt solid #e2e8ef; }
			table.sum tr.total td { font-weight: 700; font-size: 10.5pt; border-top: .75pt solid #16233a; border-bottom: .75pt solid #16233a; }
			.pstatus { clear: both; display: inline-block; margin: 10pt 0 0; padding: 4pt 12pt; border-radius: 3pt;
				font-size: 9.5pt; letter-spacing: 1pt; font-weight: 700; }
			.pstatus.void { background: #f4f4f4; color: #67788c; }
			.pstatus.paid { background: #e6f4ea; color: #1d6f42; }
			.pstatus.unpaid, .pstatus.partial { background: #fdf3e0; color: #9a6b00; }
			.foot { margin-top: 20pt; border-top: .5pt solid #e2e8ef; padding-top: 5pt; }
			.clinicfoot { margin-top: 6pt; font-weight: 700; }
		</style>';
	}
}