<?php
/**
 * Private file manager foundation for medical photos & documents.
 *
 * Files are stored OUTSIDE the web-accessible uploads tree in a dedicated
 * option-managed private directory that is blocked from direct HTTP access.
 * Serving happens only through an authorized handler (added in a later
 * phase). Phase 3 establishes the sandbox, MIME/extension validation and
 * path hardening.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Private_File_Manager {

	/**
	 * Allowed MIME types for medical uploads.
	 *
	 * @return array<string,string> mime => extension.
	 */
	public static function allowed_mime_types() {
		return array(
			'image/jpeg'        => 'jpg',
			'image/png'         => 'png',
			'image/webp'        => 'webp',
			'application/pdf'   => 'pdf',
		);
	}

	/**
	 * Allowed raw image extensions (defense in depth — matches MIME map).
	 *
	 * @return array<string>
	 */
	public static function allowed_extensions() {
		return array( 'jpg', 'jpeg', 'png', 'webp', 'pdf' );
	}

	/**
	 * Never-allowed executable/script extensions.
	 *
	 * @return array<string>
	 */
	public static function forbidden_extensions() {
		return array( 'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'pht', 'js', 'html', 'htm', 'svg', 'sh', 'exe', 'cgi', 'pl', 'py' );
	}

	/**
	 * Maximum upload size in bytes (10 MB default).
	 *
	 * @return int
	 */
	public static function max_file_size() {
		return (int) apply_filters( 'ml_clinic_max_upload_bytes', 10 * 1024 * 1024 );
	}

	/**
	 * Private storage root (absolute filesystem path). Stored in an option
	 * once on first activation; documented so it can be moved for
	 * non-web-accessible storage on a production server.
	 *
	 * @return string
	 */
	public static function private_dir() {
		$saved = get_option( 'ml_clinic_private_dir', '' );
		if ( $saved && is_dir( $saved ) ) {
			return $saved;
		}

		$uploads  = wp_upload_dir();
		$base     = $uploads['basedir'];
		$dir      = trailingslashit( $base ) . 'ml-private';

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			self::write_blocker_files( $dir );
		}
		update_option( 'ml_clinic_private_dir', $dir );
		return $dir;
	}

	/**
	 * Validate a PHP-upload array (or a file path) against all checks.
	 *
	 * @param array $file Upload array with name/type/tmp_name/size/error.
	 *
	 * @return array|WP_Error Normalized file info or error.
	 */
	public static function validate_upload( array $file ) {
		if ( empty( $file['tmp_name'] ) || empty( $file['name'] ) ) {
			return new WP_Error( 'ml_no_file', __( 'No file was provided.', 'ma-lumiere-clinic' ) );
		}
		if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'ml_upload_error', __( 'The file failed to upload.', 'ma-lumiere-clinic' ) );
		}

		$name = sanitize_file_name( (string) $file['name'] );
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

		if ( in_array( $ext, self::forbidden_extensions(), true ) ) {
			return new WP_Error( 'ml_forbidden_ext', __( 'This file type is not allowed.', 'ma-lumiere-clinic' ) );
		}
		if ( ! in_array( $ext, self::allowed_extensions(), true ) ) {
			return new WP_Error( 'ml_invalid_ext', __( 'Only JPEG, PNG, WebP and PDF files are allowed.', 'ma-lumiere-clinic' ) );
		}

		$mime = isset( $file['type'] ) ? strtolower( (string) $file['type'] ) : '';
		$detected = self::detect_mime( $file['tmp_name'] );
		if ( $detected ) {
			$mime = $detected;
		}

		$allowed = self::allowed_mime_types();
		if ( ! isset( $allowed[ $mime ] ) || $allowed[ $mime ] !== $ext ) {
			return new WP_Error( 'ml_mime_mismatch', __( 'The file content does not match an allowed type.', 'ma-lumiere-clinic' ) );
		}

		if ( filesize( $file['tmp_name'] ) > self::max_file_size() ) {
			return new WP_Error( 'ml_too_large', __( 'The file is larger than the allowed limit.', 'ma-lumiere-clinic' ) );
		}

		return array(
			'name'     => $name,
			'ext'      => $ext,
			'mime'     => $mime,
			'tmp_path' => $file['tmp_name'],
			'size'     => filesize( $file['tmp_name'] ),
		);
	}

	/**
	 * MIME detection without relying on client headers. Uses
	 * finfo when available, falls back to magic-byte checks.
	 *
	 * @param string $path File path.
	 *
	 * @return string|false
	 */
	private static function detect_mime( $path ) {
		if ( function_exists( 'finfo_open' ) ) {
			$fi   = finfo_open( FILEINFO_MIME_TYPE );
			$mime = finfo_file( $fi, $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_finfo_file
			finfo_close( $fi );
			if ( is_string( $mime ) ) {
				return strtolower( $mime );
			}
		}
		$head = @file_get_contents( $path, false, null, 0, 16 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $head ) {
			return false;
		}
		if ( "\xff\xd8\xff" === substr( $head, 0, 3 ) ) {
			return 'image/jpeg';
		}
		if ( "\x89PNG\r\n\x1a\n" === substr( $head, 0, 8 ) ) {
			return 'image/png';
		}
		if ( "RIFF" === substr( $head, 0, 4 ) && 'WEBP' === substr( $head, 8, 4 ) ) {
			return 'image/webp';
		}
		if ( "%PDF-" === substr( $head, 0, 5 ) ) {
			return 'application/pdf';
		}
		return false;
	}

	/**
	 * Verify a path stays inside the private directory.
	 *
	 * @param string $path Absolute path candidate.
	 *
	 * @return bool
	 */
	public static function is_private_path( $path ) {
		$root = wp_normalize_path( self::private_dir() );
		$path = wp_normalize_path( $path );
		return 0 === strpos( $path, trailingslashit( $root ) );
	}

	/**
	 * Write blocking files so nothing under the private dir is
	 * directly served by Apache/Nginx and directory listing is off.
	 *
	 * @param string $dir Directory to protect.
	 *
	 * @return void
	 */
	public static function write_blocker_files( $dir ) {
		@file_put_contents(  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			trailingslashit( $dir ) . '.htaccess',
			"# Block direct web access to private clinic files.\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n\nRewriteEngine On\nRewriteRule . - [F,L]\n"
		);
		@file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			trailingslashit( $dir ) . 'index.php',
			"<?php\n// Silence is golden. Access to patient files is served only through authorized handlers.\n"
		);
		@file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			trailingslashit( $dir ) . 'index.html',
			''
		);
	}
}