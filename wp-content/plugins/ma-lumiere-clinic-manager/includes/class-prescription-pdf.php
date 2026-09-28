<?php
/**
 * Prescription PDF generator (dompdf).
 *
 * Uses dompdf (installed via Composer under the plugin's vendor/ directory).
 * The autoloader is loaded lazily so nothing heavy runs unless a PDF is
 * actually generated.
 *
 * Signature rule:
 *  - If the clinic configured a signature image it must be a JPEG. dompdf
 *    embeds JPEG via the DCTDecode filter with zero GD dependence (verified
 *    in this environment). PNG/WebP signatures would silently drop without
 *    GD, so an explicit, truthful error is returned for those formats.
 *  - With no signature configured the PDF shows the doctor's typed name and
 *    designation — an honest, explicit signature line. A dishonest "images"
 *    is never fabricated.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Prescription_Pdf {

	/**
	 * Whether the composer autoloader has been required.
	 *
	 * @var bool
	 */
	private static $autoloaded = false;

	/**
	 * Generate prescription PDF bytes.
	 *
	 * Ownership/permission checks are delegated to the caller. This method
	 * only builds and renders the document.
	 *
	 * @param int $prescription_id Prescription ID.
	 *
	 * @return array|WP_Error Array with keys 'filename' + 'data' or error.
	 */
	public static function generate( $prescription_id ) {
		$prescription = ML_Prescription_Repository::get( $prescription_id );
		if ( ! $prescription ) {
			return new WP_Error( 'ml_pdf_not_found', __( 'Prescription not found.', 'ma-lumiere-clinic' ) );
		}
		if ( 'final' !== (string) $prescription['status'] || empty( $prescription['prescription_number'] ) ) {
			return new WP_Error( 'ml_pdf_not_final', __( 'Only a finalized prescription can be exported as PDF.', 'ma-lumiere-clinic' ) );
		}

		$patient = ML_Patient_Repository::get( (int) $prescription['patient_id'] );
		if ( ! $patient ) {
			return new WP_Error( 'ml_pdf_patient', __( 'The linked patient no longer exists.', 'ma-lumiere-clinic' ) );
		}

		$renderer = self::load_dompdf();
		if ( is_wp_error( $renderer ) ) {
			return $renderer;
		}
		$dompdf = $renderer['dompdf'];
		$helper = $renderer['helper'];

		$signature = self::signature_html();
		if ( is_wp_error( $signature ) ) {
			return $signature;
		}

		$doctor_name = (string) ML_Settings::get( 'doctor.doctor_name', '' );
		$doctor_line = '';
		if ( $doctor_name ) {
			$designation    = (string) ML_Settings::get( 'doctor.designation', '' );
			$registration   = (string) ML_Settings::get( 'doctor.registration_no', '' );
			$doctor_line    = esc_html( $doctor_name );
			if ( $designation ) {
				$doctor_line .= ', ' . esc_html( $designation );
			}
			if ( $registration ) {
				$doctor_line .= '<br><span class="muted">' . esc_html( $registration ) . '</span>';
			}
		}

		$clinic_name = (string) ML_Settings::get( 'clinic.clinic_name', __( 'Ma Lumière Clinic', 'ma-lumiere-clinic' ) );
		$clinic_addr = trim( (string) ML_Settings::get( 'clinic.address', '' ) );
		$clinic_contact = trim(
			implode( '  •  ', array_filter( array( (string) ML_Settings::get( 'clinic.phone', '' ), (string) ML_Settings::get( 'clinic.email', '' ) ) ) )
		);

		$items = ML_Prescription_Repository::items( $prescription_id );
		$rows  = '';
		if ( $items ) {
			foreach ( $items as $index => $item ) {
				$rows .= '<tr>'
					. '<td class="num">' . ( $index + 1 ) . '</td>'
					. '<td>' . esc_html( (string) $item['medicine_name'] ) . '</td>'
					. '<td>' . esc_html( (string) $item['dosage'] ) . '</td>'
					. '<td>' . esc_html( (string) $item['frequency'] ) . '</td>'
					. '<td>' . esc_html( (string) $item['duration'] ) . '</td>'
					. '<td>' . esc_html( (string) $item['instructions'] ) . '</td>'
					. '</tr>';
			}
		} else {
			$rows = '<tr><td colspan="6" class="muted">' . __( 'No medicines prescribed.', 'ma-lumiere-clinic' ) . '</td></tr>';
		}

		$follow_up = ! empty( $prescription['follow_up_date'] ) ? ' — <strong>' . __( 'Follow-up', 'ma-lumiere-clinic' ) . ':</strong> <span class="serif">' . esc_html( ml_date( $prescription['follow_up_date'] ) ) . '</span>' : '';

		$sections = '';
		if ( ! empty( $prescription['diagnosis'] ) ) {
			$sections .= self::section( __( 'Diagnosis', 'ma-lumiere-clinic' ), nl2br( esc_html( (string) $prescription['diagnosis'] ) ) );
		}
		if ( ! empty( $prescription['doctor_notes'] ) ) {
			$sections .= self::section( __( 'Notes', 'ma-lumiere-clinic' ), nl2br( esc_html( (string) $prescription['doctor_notes'] ) ) );
		}
		if ( ! empty( $prescription['advice'] ) ) {
			$sections .= self::section( __( 'Advice', 'ma-lumiere-clinic' ), nl2br( esc_html( (string) $prescription['advice'] ) ) );
		}

		$patient_line = esc_html( trim( (string) $patient['first_name'] . ' ' . (string) $patient['last_name'] ) );
		if ( ! empty( $patient['patient_uid'] ) ) {
			$patient_line .= ' <span class="muted">(' . esc_html( (string) $patient['patient_uid'] ) . ')</span>';
		}

		$issued = esc_html( ml_date( $prescription['prescription_date'], 'd M Y' ) );

		$html = '<!DOCTYPE html><html><head><meta charset="utf-8">' . self::css() . '</head><body>'
			. '<div class="head">'
			. '<div class="clinic"><div class="name">' . esc_html( $clinic_name ) . '</div>'
			. ( $clinic_addr ? '<div class="muted">' . esc_html( $clinic_addr ) . '</div>' : '' )
			. ( $clinic_contact ? '<div class="muted">' . esc_html( $clinic_contact ) . '</div>' : '' )
			. '</div>'
			. '<div class="rxbadge">' . esc_html__( 'PRESCRIPTION', 'ma-lumiere-clinic' ) . '</div>'
			. '</div>'
			. '<hr>'
			. '<p class="meta">'
			. '<span>' . esc_html__( 'Patient', 'ma-lumiere-clinic' ) . ': <strong>' . $patient_line . '</strong></span>'
			. '<span>' . esc_html__( 'Date', 'ma-lumiere-clinic' ) . ': <strong>' . $issued . '</strong></span>'
			. '<span>' . esc_html__( 'Number', 'ma-lumiere-clinic' ) . ': <strong class="serif">' . esc_html( $prescription['prescription_number'] ) . '</strong></span>'
			. ( (int) $prescription['version'] > 1 ? '<span>' . esc_html__( 'Revision', 'ma-lumiere-clinic' ) . ': <strong>v' . (int) $prescription['version'] . '</strong></span>' : '' )
			. '</p>'
			. $sections
			. '<h2 class="cap">' . esc_html__( 'Medicines', 'ma-lumiere-clinic' ) . '</h2>'
			. '<table class="items">'
			. '<thead><tr><th class="num">#</th><th>' . esc_html__( 'Medicine', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Dosage', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Frequency', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Duration', 'ma-lumiere-clinic' ) . '</th><th>' . esc_html__( 'Instructions', 'ma-lumiere-clinic' ) . '</th></tr></thead>'
			. '<tbody>' . $rows . '</tbody>'
			. '</table>'
			. '<p class="followup">' . $follow_up . '</p>'
			. '<div class="sign">
				<div class="sigline">' . $signature . '</div>
				<div class="doctor">' . $doctor_line . '</div>
				<div class="muted">' . esc_html__( 'Issued electronically by Ma Lumière Clinic.', 'ma-lumiere-clinic' ) . '</div>
			</div>'
			. '<div class="foot muted">' . esc_html( $clinic_name ) . ' — ' . esc_html__( 'This prescription is valid for the named patient only.', 'ma-lumiere-clinic' ) . '</div>'
			. '</body></html>';

		$dompdf->loadHtml( $html );
		$dompdf->render();

		$filename = 'prescription-' . strtolower( (string) $prescription['prescription_number'] ) . '-v' . (int) $prescription['version'] . '.pdf';

		return array(
			'filename' => $filename,
			'data'     => $dompdf->output(),
		);
	}

	/**
	 * Build the signature block HTML. Returns a rendered JPEG data-URI (or
	 * a "digitally issued" line when no signature is configured), or a
	 * WP_Error when a configured signature cannot be embedded (unsupported
	 * format — would silently drop without GD).
	 *
	 * @return string|WP_Error
	 */
	private static function signature_html() {
		$signature_id = (int) ML_Settings::get( 'doctor.signature_id', 0 );

		if ( $signature_id ) {
			$path  = get_attached_file( $signature_id );
			$mime  = get_post_mime_type( $signature_id );
			if ( $path && is_readable( $path ) ) {
				if ( 'image/jpeg' !== $mime ) {
					// Do not silently drop the signature. dompdf embeds JPEG
					// without GD; PNG/WebP would fail here (no GD on this
					// server), so the clinic gets a truthful error.
					return new WP_Error(
						'ml_pdf_signature_format',
						sprintf(
							__( 'The signature image must be a JPEG to be embedded without the GD extension (currently %s). Please re-upload the signature as a JPEG in Settings → Doctors.', 'ma-lumiere-clinic' ),
							$mime ? $mime : __( 'unknown type', 'ma-lumiere-clinic' )
						)
					);
				}
				$base64 = base64_encode( (string) @file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				if ( $base64 ) {
					return '<img src="data:image/jpeg;base64,' . $base64 . '" class="sig">';
				}
			}
		}

		return '<span class="muted">' . esc_html__( 'Digitally issued', 'ma-lumiere-clinic' ) . '</span>';
	}

	/**
	 * A prescription content section.
	 *
	 * @param string $title   Section label.
	 * @param string $content HTML content body.
	 *
	 * @return string
	 */
	private static function section( $title, $content ) {
		return '<h2 class="cap">' . esc_html( $title ) . '</h2><p>' . $content . '</p>';
	}

	/**
	 * Lazy-load the dompdf Composer autoloader.
	 *
	 * @return array|WP_Error ['dompdf' => Dompdf\Dompdf, 'helper' => Dompdf\Options].
	 */
	private static function load_dompdf() {
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
		if ( ! self::$autoloaded ) {
			require_once $autoload;
			self::$autoloaded = true;
		}
		if ( ! class_exists( 'Dompdf\\Dompdf' ) ) {
			return new WP_Error( 'ml_pdf_missing_dep', __( 'The dompdf library is installed but could not be loaded. Please re-run composer install in the plugin folder.', 'ma-lumiere-clinic' ) );
		}

		$helper = new Dompdf\Options();
		$helper->set( 'isRemoteEnabled', false );          // Never fetch remote URLs.
		$helper->set( 'isFontSubsettingEnabled', true );   // Trim font subset.
		$helper->set( 'defaultFont', 'DejaVu Sans' );      // Guaranteed available.

		return array(
			'dompdf' => new Dompdf\Dompdf( $helper ),
			'helper' => $helper,
		);
	}

	/**
	 * Inline PDF stylesheet.
	 *
	 * @return string
	 */
	private static function css() {
		return '<style>
			@page { margin: 24px 28px; }
			body  { font-family: "DejaVu Sans", sans-serif; color: #1f2733; font-size: 11pt; line-height: 1.45; }
			.head { display: block; overflow: hidden; }
			.clinic { float: left; }
			.name  { font-size: 15pt; font-weight: 700; letter-spacing: .3px; }
			.rxbadge { float: right; border: 1.5pt solid #1d6f42; color: #1d6f42; font-weight: 700;
				padding: 4pt 10pt; border-radius: 3pt; letter-spacing: 1.5pt; font-size: 10pt; }
			hr { border: 0; border-top: .75pt solid #c9d2dc; margin: 10pt 0 12pt; }
			.meta { margin: 0 0 12pt; display: block; }
			.meta span { margin-right: 14pt; white-space: normal; }
			.serif { font-weight: 700; }
			.muted { color: #67788c; font-size: 9pt; }
			h2.cap { font-size: 10pt; letter-spacing: 1pt; text-transform: uppercase; color: #1d6f42; border-bottom: .5pt solid #e2e8ef; margin: 14pt 0 6pt; padding-bottom: 2pt; }
			table.items { width: 100%; border-collapse: collapse; }
			table.items th, table.items td { border: .5pt solid #c9d2dc; padding: 4pt 6pt; font-size: 9.5pt; vertical-align: top; text-align: left; }
			table.items th { background: #f2f6fa; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .5pt; color: #526173; }
			table.items td.num { width: 14pt; color: #67788c; }
			.sig { max-width: 150px; max-height: 46px; object-fit: contain; }
			.sign { margin-top: 22pt; }
			.doctor { font-weight: 700; margin-top: 2pt; }
			.foot { margin-top: 22pt; border-top: .5pt solid #e2e8ef; padding-top: 5pt; }
			.followup { margin-top: 14pt; }
		</style>';
	}
}