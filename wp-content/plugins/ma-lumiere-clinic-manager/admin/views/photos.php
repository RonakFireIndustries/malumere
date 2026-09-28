<?php
/**
 * Patient Photos — before/after gallery with token-based private serving.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

$ml_can_manage = current_user_can( 'ml_manage_clinic' ) || current_user_can( 'ml_manage_patient_photos' );
$ml_base       = admin_url( 'admin.php' );
$ml_page_url   = add_query_arg( 'page', 'ml-clinic-photos', $ml_base );
$ml_error      = '';
$ml_notice     = isset( $_GET['ml_notice'] ) ? sanitize_key( wp_unslash( $_GET['ml_notice'] ) ) : '';

$ml_patient_id = isset( $_GET['patient_id'] ) ? absint( $_GET['patient_id'] ) : 0;

// Upload a new photo.
if ( $ml_patient_id && $ml_can_manage && isset( $_POST['ml_action'], $_FILES['photo'], $_POST['_wpnonce'] )
	&& 'add_photo' === sanitize_key( wp_unslash( $_POST['ml_action'] ) ) ) {
	if ( ML_Security::verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) ) && ML_Security::can_access_patient( $ml_patient_id ) ) {
		$result = ML_Photo_Repository::create(
			array(
				'patient_id' => $ml_patient_id,
				'file'       => wp_unslash( $_FILES['photo'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'photo_type' => isset( $_POST['photo_type'] ) ? sanitize_key( wp_unslash( $_POST['photo_type'] ) ) : 'before',
				'body_area'  => isset( $_POST['body_area'] ) ? sanitize_text_field( wp_unslash( $_POST['body_area'] ) ) : '',
				'photo_date' => isset( $_POST['photo_date'] ) ? sanitize_text_field( wp_unslash( $_POST['photo_date'] ) ) : '',
				'notes'      => isset( $_POST['photo_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['photo_notes'] ) ) : '',
				'visit_id'   => isset( $_POST['visit_id'] ) ? absint( $_POST['visit_id'] ) : 0,
			)
		);
		if ( is_wp_error( $result ) ) {
			$ml_error = $result->get_error_message();
		} else {
			wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-photos', 'patient_id' => $ml_patient_id, 'ml_notice' => 'photo_added' ), $ml_base ) );
			exit;
		}
	} else {
		$ml_error = __( 'Security check failed. Please try again.', 'ma-lumiere-clinic' );
	}
}

// Delete a photo.
if ( $ml_can_manage && isset( $_POST['ml_action'], $_POST['photo_id'], $_POST['_wpnonce'] )
	&& 'delete_photo' === sanitize_key( wp_unslash( $_POST['ml_action'] ) ) ) {
	if ( ML_Security::verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) ) ) {
		$result = ML_Photo_Repository::delete( absint( $_POST['photo_id'] ) );
		if ( is_wp_error( $result ) ) {
			$ml_error = $result->get_error_message();
		} else {
			$ml_redirect_pid = $ml_patient_id ? $ml_patient_id : ( isset( $_POST['patient_id'] ) ? absint( $_POST['patient_id'] ) : $ml_patient_id );
			wp_safe_redirect( add_query_arg( array( 'page' => 'ml-clinic-photos', 'patient_id' => $ml_redirect_pid, 'ml_notice' => 'photo_deleted' ), $ml_base ) );
			exit;
		}
	} else {
		$ml_error = __( 'Security check failed. Please try again.', 'ma-lumiere-clinic' );
	}
}

$ml_notice_msg = '';
if ( 'photo_added' === $ml_notice ) {
	$ml_notice_msg = __( 'Photo uploaded.', 'ma-lumiere-clinic' );
} elseif ( 'photo_deleted' === $ml_notice ) {
	$ml_notice_msg = __( 'Photo deleted.', 'ma-lumiere-clinic' );
}
?>
<div class="wrap ml-clinic-wrap">
	<div class="ml-clinic-header">
		<h1 class="ml-clinic-title"><?php esc_html_e( 'Patient Photos', 'ma-lumiere-clinic' ); ?></h1>
		<?php if ( ! $ml_patient_id ) : ?>
			<p class="ml-clinic-subtitle"><?php esc_html_e( 'Choose a patient to upload and compare before/after photos. Photos are stored outside the public uploads folder and served through signed, expiring links.', 'ma-lumiere-clinic' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $ml_error ) ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $ml_error ); ?></p></div>
	<?php endif; ?>
	<?php if ( '' !== $ml_notice_msg ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $ml_notice_msg ); ?></p></div>
	<?php endif; ?>

	<?php if ( $ml_patient_id ) : ?>

		<?php
		$ml_patient = ML_Patient_Repository::get( $ml_patient_id );
		if ( ! $ml_patient ) :
			?>
			<div class="ml-clinic-empty"><p><?php esc_html_e( 'Patient not found.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php else : ?>

			<p>
				<a class="button button-link" href="<?php echo esc_url( $ml_page_url ); ?>">← <?php esc_html_e( 'All patients', 'ma-lumiere-clinic' ); ?></a>
			</p>

			<h2 class="ml-clinic-section-title">
				<?php echo esc_html( trim( (string) $ml_patient['first_name'] . ' ' . (string) $ml_patient['last_name'] ) ); ?>
				<span class="ml-muted">(<?php echo esc_html( $ml_patient['patient_uid'] ); ?>)</span>
			</h2>

			<?php if ( $ml_can_manage ) : ?>
				<details class="ml-clinic-add" open>
					<summary><?php esc_html_e( 'Upload a photo', 'ma-lumiere-clinic' ); ?></summary>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-photos', 'patient_id' => $ml_patient_id ), $ml_base ) ); ?>" class="ml-form">
						<input type="hidden" name="ml_action" value="add_photo" />
						<div class="ml-form-grid">
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-ph-file"><?php esc_html_e( 'Photo file (JPEG, PNG or WebP; up to 10 MB)', 'ma-lumiere-clinic' ); ?> <span class="ml-field__req">*</span></label>
								<input id="ml-ph-file" class="ml-control" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required />
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-ph-type"><?php esc_html_e( 'Type', 'ma-lumiere-clinic' ); ?></label>
								<select id="ml-ph-type" class="ml-control" name="photo_type">
									<?php foreach ( ML_Photo_Repository::types() as $ml_type ) : ?>
										<option value="<?php echo esc_attr( $ml_type ); ?>"><?php echo esc_html( ucfirst( $ml_type ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-ph-area"><?php esc_html_e( 'Body area', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-ph-area" class="ml-control" type="text" name="body_area" placeholder="<?php esc_attr_e( 'e.g. Face, cheeks, jawline', 'ma-lumiere-clinic' ); ?>" />
							</p>
							<p class="ml-field">
								<label class="ml-field__label" for="ml-ph-date"><?php esc_html_e( 'Photo date', 'ma-lumiere-clinic' ); ?></label>
								<input id="ml-ph-date" class="ml-control" type="date" name="photo_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
							</p>
							<p class="ml-field ml-field--wide">
								<label class="ml-field__label" for="ml-ph-notes"><?php esc_html_e( 'Notes', 'ma-lumiere-clinic' ); ?></label>
								<textarea id="ml-ph-notes" class="ml-control" name="photo_notes" rows="2"></textarea>
							</p>
						</div>
						<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
						<p class="ml-form-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Upload photo', 'ma-lumiere-clinic' ); ?></button>
						</p>
					</form>
				</details>
			<?php endif; ?>

			<?php
			$ml_type  = isset( $_GET['ml_type'] ) ? sanitize_key( wp_unslash( $_GET['ml_type'] ) ) : '';
			$ml_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
			$ml_photos_result = ML_Photo_Repository::list(
				array(
					'patient_id' => $ml_patient_id,
					'photo_type' => $ml_type,
					'page'       => $ml_page,
					'per_page'   => 12,
				)
			);
			?>

		<form class="ml-form-filter" method="get">
			<input type="hidden" name="page" value="ml-clinic-photos" />
			<input type="hidden" name="patient_id" value="<?php echo esc_attr( $ml_patient_id ); ?>" />
			<p class="ml-field">
				<label class="ml-field__label" for="ml-ph-filter"><?php esc_html_e( 'Type', 'ma-lumiere-clinic' ); ?></label>
				<select id="ml-ph-filter" name="ml_type" class="ml-control">
					<option value=""><?php esc_html_e( 'All types', 'ma-lumiere-clinic' ); ?></option>
					<?php foreach ( ML_Photo_Repository::types() as $ml_opt ) : ?>
						<option value="<?php echo esc_attr( $ml_opt ); ?>" <?php selected( $ml_type, $ml_opt ); ?>><?php echo esc_html( ucfirst( $ml_opt ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ml-field">
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'ma-lumiere-clinic' ); ?></button>
			</p>
		</form>

			<?php if ( empty( $ml_photos_result['items'] ) ) : ?>
				<div class="ml-clinic-empty"><p><?php esc_html_e( 'No photos for this patient yet.', 'ma-lumiere-clinic' ); ?></p></div>
			<?php else : ?>
				<div class="ml-photo-grid">
					<?php foreach ( $ml_photos_result['items'] as $ml_photo ) : ?>
						<figure class="ml-photo-card">
							<a href="<?php echo esc_url( ML_Photo_Repository::serve_url( (int) $ml_photo['id'] ) ); ?>" target="_blank" rel="noopener">
								<img src="<?php echo esc_url( ML_Photo_Repository::serve_url( (int) $ml_photo['id'] ) ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Patient photo %s', 'ma-lumiere-clinic' ), $ml_photo['photo_type'] ) ); ?>" loading="lazy" />
							</a>
							<figcaption>
								<span class="ml-badge ml-badge--<?php echo esc_attr( 'after' === $ml_photo['photo_type'] ? 'success' : ( 'before' === $ml_photo['photo_type'] ? 'warning' : 'muted' ) ); ?>"><?php echo esc_html( ucfirst( $ml_photo['photo_type'] ) ); ?></span>
								<span class="ml-muted"><?php echo esc_html( ml_date( $ml_photo['photo_date'], 'd M Y' ) ); ?></span>
								<?php if ( ! empty( $ml_photo['body_area'] ) ) : ?><span class="ml-muted"><?php echo esc_html( $ml_photo['body_area'] ); ?></span><?php endif; ?>
								<?php if ( ! empty( $ml_photo['notes'] ) ) : ?><span class="ml-muted"><?php echo esc_html( $ml_photo['notes'] ); ?></span><?php endif; ?>
							</figcaption>
							<?php if ( $ml_can_manage ) : ?>
							<form method="post" class="ml-form-inline">
								<input type="hidden" name="ml_action" value="delete_photo" />
								<input type="hidden" name="photo_id" value="<?php echo esc_attr( (int) $ml_photo['id'] ); ?>" />
								<input type="hidden" name="patient_id" value="<?php echo esc_attr( $ml_patient_id ); ?>" />
								<?php wp_nonce_field( ML_Security::NONCE_ACTION ); ?>
								<button type="submit" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Delete this photo permanently?', 'ma-lumiere-clinic' ) ); ?>');"><?php esc_html_e( 'Delete', 'ma-lumiere-clinic' ); ?></button>
							</form>
							<?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>

				<?php if ( $ml_photos_result['pages'] > 1 ) : ?>
					<div class="ml-clinic-pagination">
						<?php if ( $ml_photos_result['page'] > 1 ) : ?>
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-photos', 'patient_id' => $ml_patient_id, 'ml_type' => $ml_type, 'paged' => $ml_photos_result['page'] - 1 ), $ml_base ) ); ?>"><?php esc_html_e( '‹ Prev', 'ma-lumiere-clinic' ); ?></a>
						<?php endif; ?>
						<span class="ml-muted"><?php echo esc_html( sprintf( __( 'Page %d of %d', 'ma-lumiere-clinic' ), $ml_photos_result['page'], $ml_photos_result['pages'] ) ); ?></span>
						<?php if ( $ml_photos_result['page'] < $ml_photos_result['pages'] ) : ?>
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-photos', 'patient_id' => $ml_patient_id, 'ml_type' => $ml_type, 'paged' => $ml_photos_result['page'] + 1 ), $ml_base ) ); ?>"><?php esc_html_e( 'Next ›', 'ma-lumiere-clinic' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

		<?php endif; ?>

	<?php else : ?>

		<?php
		$ml_s     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$ml_pats  = ML_Patient_Repository::list(
			array(
				'per_page' => 50,
				'search'   => $ml_s,
				'orderby'  => 'last_name',
				'order'    => 'ASC',
			)
		);
		$ml_counts = ML_Photo_Repository::counts_for(
			wp_list_pluck( (array) $ml_pats['items'], 'id' )
		);
		?>

		<form class="ml-form-filter" method="get">
			<input type="hidden" name="page" value="ml-clinic-photos" />
			<p class="ml-field">
				<label class="ml-field__label" for="ml-ph-pt-search"><?php esc_html_e( 'Search patients', 'ma-lumiere-clinic' ); ?></label>
				<input id="ml-ph-pt-search" type="search" name="s" class="ml-control ml-clinic-search" value="<?php echo esc_attr( $ml_s ); ?>"
					placeholder="<?php esc_attr_e( 'Search patients by name or UID…', 'ma-lumiere-clinic' ); ?>" />
			</p>
			<p class="ml-field">
				<button type="submit" class="button"><?php esc_html_e( 'Search', 'ma-lumiere-clinic' ); ?></button>
			</p>
		</form>

		<?php if ( empty( $ml_pats['items'] ) ) : ?>
			<div class="ml-clinic-empty"><p><?php esc_html_e( 'No patients found.', 'ma-lumiere-clinic' ); ?></p></div>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Patient', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Photos', 'ma-lumiere-clinic' ); ?></th>
						<th><?php esc_html_e( 'Before / After', 'ma-lumiere-clinic' ); ?></th>
						<th class="ml-col-actions"><?php esc_html_e( 'Gallery', 'ma-lumiere-clinic' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ml_pats['items'] as $ml_p ) :
						$ml_c = isset( $ml_counts[ (int) $ml_p['id'] ] ) ? $ml_counts[ (int) $ml_p['id'] ] : array( 'total' => 0, 'before' => 0, 'after' => 0 );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( trim( (string) $ml_p['first_name'] . ' ' . (string) $ml_p['last_name'] ) ); ?></strong>
								<span class="ml-muted">(<?php echo esc_html( $ml_p['patient_uid'] ); ?>)</span>
							</td>
							<td><?php echo esc_html( (int) $ml_c['total'] ); ?></td>
							<td><?php echo esc_html( sprintf( '%d / %d', $ml_c['before'], $ml_c['after'] ) ); ?></td>
							<td class="ml-col-actions">
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ml-clinic-photos', 'patient_id' => (int) $ml_p['id'] ), $ml_base ) ); ?>"><?php esc_html_e( 'Open gallery', 'ma-lumiere-clinic' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	<?php endif; ?>
</div>