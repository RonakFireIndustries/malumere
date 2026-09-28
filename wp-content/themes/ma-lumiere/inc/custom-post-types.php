<?php
/**
 * Custom post types and their meta boxes.
 *
 * CPTs: ml_treatment, ml_before_after, ml_testimonial, ml_faq.
 * Clinic-management logic stays out of the theme; these are presentation
 * content only (editable from the WordPress admin).
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'ml_register_post_types' );
add_action( 'add_meta_boxes', 'ml_add_meta_boxes' );
add_action( 'save_post', 'ml_save_treatment_meta', 10, 2 );
add_action( 'save_post', 'ml_save_media_meta', 10, 2 );
add_action( 'save_post', 'ml_save_testimonial_meta', 10, 2 );
add_action( 'admin_enqueue_scripts', 'ml_admin_media_assets' );

/**
 * Register the theme's content post types.
 */
function ml_register_post_types() {
	register_post_type(
		'ml_treatment',
		array(
			'labels'       => array(
				'name'          => __( 'Treatments', 'ma-lumiere' ),
				'singular_name' => __( 'Treatment', 'ma-lumiere' ),
				'add_new_item'  => __( 'Add New Treatment', 'ma-lumiere' ),
				'edit_item'     => __( 'Edit Treatment', 'ma-lumiere' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-healing',
			'rewrite'      => array( 'slug' => 'treatment', 'with_front' => false ),
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'menu_position' => 20,
			'show_in_rest' => true,
		)
	);

	register_post_type(
		'ml_before_after',
		array(
			'labels'       => array(
				'name'          => __( 'Before & After', 'ma-lumiere' ),
				'singular_name' => __( 'Result', 'ma-lumiere' ),
				'add_new_item'  => __( 'Add Before/After', 'ma-lumiere' ),
				'edit_item'     => __( 'Edit Before/After', 'ma-lumiere' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-visibility',
			'rewrite'      => array( 'slug' => 'results', 'with_front' => false ),
			'supports'     => array( 'title', 'thumbnail', 'revisions' ),
			'menu_position' => 21,
			'show_in_rest' => true,
		)
	);

	register_post_type(
		'ml_testimonial',
		array(
			'labels'       => array(
				'name'          => __( 'Testimonials', 'ma-lumiere' ),
				'singular_name' => __( 'Testimonial', 'ma-lumiere' ),
				'add_new_item'  => __( 'Add Testimonial', 'ma-lumiere' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-format-quote',
			'supports'     => array( 'title', 'editor' ),
			'menu_position' => 22,
		)
	);

	register_post_type(
		'ml_faq',
		array(
			'labels'       => array(
				'name'          => __( 'FAQs', 'ma-lumiere' ),
				'singular_name' => __( 'FAQ', 'ma-lumiere' ),
				'add_new_item'  => __( 'Add FAQ', 'ma-lumiere' ),
			),
			'public'       => true,
			'has_archive'  => false,
			'menu_icon'    => 'dashicons-editor-help',
			'rewrite'      => array( 'slug' => 'faq', 'with_front' => false ),
			'supports'     => array( 'title', 'editor', 'revisions' ),
			'menu_position' => 23,
			'show_in_rest' => true,
		)
	);
}

/**
 * Register metaboxes.
 */
function ml_add_meta_boxes() {
	add_meta_box(
		'ml_treatment_box',
		__( 'Treatment Details', 'ma-lumiere' ),
		'ml_render_treatment_box',
		'ml_treatment',
		'normal',
		'high'
	);

	add_meta_box(
		'ml_ba_box',
		__( 'Before & After Images', 'ma-lumiere' ),
		'ml_render_ba_box',
		'ml_before_after',
		'normal',
		'high'
	);

	add_meta_box(
		'ml_testimonial_box',
		__( 'Attribution', 'ma-lumiere' ),
		'ml_render_testimonial_box',
		'ml_testimonial',
		'normal',
		'high'
	);
}

/**
 * Admin assets for the media picker meta boxes.
 *
 * @param string $hook Current admin page.
 */
function ml_admin_media_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'ml_before_after' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'ml-admin-media', ML_THEME_URI . '/assets/js/admin-media.js', array( 'jquery' ), ML_THEME_VERSION, true );
}

/* -----------------------------------------------------------------
 * Shared metabox field renderers
 * ---------------------------------------------------------------- */

/**
 * Text input metabox field.
 *
 * @param string $id    Field id/name.
 * @param string $label Label.
 * @param string $value Value.
 */
function ml_box_text( $id, $label, $value ) {
	echo '<p><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label><br/>';
	echo '<input type="text" name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" class="widefat" style="margin-top:6px"/></p>';
}

/**
 * Textarea metabox field.
 *
 * @param string $id      Field id/name.
 * @param string $label   Label.
 * @param string $value   Value.
 * @param string $hint    Hint text.
 * @param bool   $mono    Monospace?
 * @param int    $rows    Rows.
 */
function ml_box_textarea( $id, $label, $value, $hint = '', $mono = false, $rows = 5 ) {
	echo '<p><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label><br/>';
	if ( $hint ) {
		echo '<span class="description" style="display:block">' . esc_html( $hint ) . '</span>';
	}
	echo '<textarea name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '" rows="' . (int) $rows . '" class="widefat" style="margin-top:6px;' . ( $mono ? 'font-family:monospace;' : '' ) . '">' . esc_textarea( $value ) . '</textarea></p>';
}

/**
 * TinyMCE metabox field.
 *
 * @param string $id    Field id/name.
 * @param string $label Label.
 * @param string $value Value.
 */
function ml_box_editor( $id, $label, $value ) {
	echo '<div style="margin:0 0 14px"><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
	wp_editor(
		$value,
		esc_attr( $id ),
		array(
			'textarea_name' => $id,
			'textarea_rows' => 6,
			'teeny'         => false,
			'media_buttons' => false,
		)
	);
	echo '</div>';
}

/* -----------------------------------------------------------------
 * Treatment metabox
 * ---------------------------------------------------------------- */

/**
 * Render the treatment details box.
 *
 * @param WP_Post $post Current post.
 */
function ml_render_treatment_box( $post ) {
	wp_nonce_field( 'ml_treatment_save', 'ml_treatment_nonce' );

	ml_box_text( '_ml_treatment_short', __( 'Short description (cards)', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_treatment_short', true ) );
	ml_box_editor( '_ml_treatment_intro', __( 'Introduction — “What is this treatment?”', 'ma-lumiere' ), wp_kses_post( get_post_meta( $post->ID, '_ml_treatment_intro', true ) ) );
	ml_box_editor( '_ml_treatment_overview', __( 'Overview / How it works', 'ma-lumiere' ), wp_kses_post( get_post_meta( $post->ID, '_ml_treatment_overview', true ) ) );
	ml_box_editor( '_ml_treatment_suitable', __( 'Who is it for?', 'ma-lumiere' ), wp_kses_post( get_post_meta( $post->ID, '_ml_treatment_suitable', true ) ) );
	ml_box_editor( '_ml_treatment_concerns', __( 'Symptoms / Concerns addressed', 'ma-lumiere' ), wp_kses_post( get_post_meta( $post->ID, '_ml_treatment_concerns', true ) ) );
	ml_box_editor( '_ml_treatment_results', __( 'Expected results (clinical guidance only)', 'ma-lumiere' ), wp_kses_post( get_post_meta( $post->ID, '_ml_treatment_results', true ) ) );
	ml_box_textarea( '_ml_treatment_process', __( 'Treatment process', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_treatment_process', true ), __( 'One step per line.', 'ma-lumiere' ) );
	ml_box_text( '_ml_treatment_sessions', __( 'Number of sessions', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_treatment_sessions', true ) );
	ml_box_text( '_ml_treatment_duration', __( 'Duration (minutes)', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_treatment_duration', true ) );
	ml_box_text( '_ml_treatment_downtime', __( 'Recovery / downtime', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_treatment_downtime', true ) );
	ml_box_editor( '_ml_treatment_pre', __( 'Pre-treatment instructions', 'ma-lumiere' ), wp_kses_post( get_post_meta( $post->ID, '_ml_treatment_pre', true ) ) );
	ml_box_editor( '_ml_treatment_post', __( 'Post-treatment care / aftercare', 'ma-lumiere' ), wp_kses_post( get_post_meta( $post->ID, '_ml_treatment_post', true ) ) );
	ml_box_textarea( '_ml_treatment_faqs', __( 'Treatment FAQs', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_treatment_faqs', true ), __( 'One per line, as: Question || Answer', 'ma-lumiere' ) );

	// Related treatments.
	$related_ids = array_map( 'absint', (array) get_post_meta( $post->ID, '_ml_treatment_related', true ) );
	$all         = get_posts(
		array(
			'post_type'      => 'ml_treatment',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'post__not_in'   => array( $post->ID ),
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	echo '<div style="margin:18px 0 8px"><strong>' . esc_html__( 'Related treatments', 'ma-lumiere' ) . '</strong>';
	echo '<div class="description">' . esc_html__( 'Select up to 3 to show alongside this treatment.', 'ma-lumiere' ) . '</div>';
	echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 18px;margin-top:8px">';
	foreach ( $all as $item ) {
		$checked = in_array( $item->ID, $related_ids, true ) ? 'checked' : '';
		echo '<label style="font-weight:400"><input type="checkbox" name="_ml_treatment_related[]" value="' . esc_attr( $item->ID ) . '" ' . $checked . '/> ' . esc_html( $item->post_title ) . '</label>';
	}
	echo '</div></div>';
}

/**
 * Save treatment meta.
 *
 * @param int      $post_id Post ID.
 * @param WP_Post  $post    Post object.
 */
function ml_save_treatment_meta( $post_id, $post ) {
	if ( ! isset( $_POST['ml_treatment_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ml_treatment_nonce'] ), 'ml_treatment_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'ml_treatment' !== $post->post_type ) {
		return;
	}

	$texts = array(
		'_ml_treatment_short',
		'_ml_treatment_sessions',
		'_ml_treatment_downtime',
	);
	foreach ( $texts as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		update_post_meta( $post_id, $key, $value );
	}

	$duration = isset( $_POST['_ml_treatment_duration'] ) ? absint( $_POST['_ml_treatment_duration'] ) : 0;
	update_post_meta( $post_id, '_ml_treatment_duration', (string) min( $duration, 600 ) );

	$editors = array(
		'_ml_treatment_intro',
		'_ml_treatment_overview',
		'_ml_treatment_suitable',
		'_ml_treatment_concerns',
		'_ml_treatment_results',
		'_ml_treatment_pre',
		'_ml_treatment_post',
	);
	foreach ( $editors as $key ) {
		$value = isset( $_POST[ $key ] ) ? wp_kses_post( wp_unslash( $_POST[ $key ] ) ) : '';
		update_post_meta( $post_id, $key, $value );
	}

	$process = isset( $_POST['_ml_treatment_process'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_ml_treatment_process'] ) ) : '';
	update_post_meta( $post_id, '_ml_treatment_process', $process );

	$faqs = isset( $_POST['_ml_treatment_faqs'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_ml_treatment_faqs'] ) ) : '';
	update_post_meta( $post_id, '_ml_treatment_faqs', $faqs );

	$related = isset( $_POST['_ml_treatment_related'] ) ? array_map( 'absint', wp_unslash( $_POST['_ml_treatment_related'] ) ) : array();
	update_post_meta( $post_id, '_ml_treatment_related', array_slice( $related, 0, 3 ) );
}

/* -----------------------------------------------------------------
 * Before / After metabox
 * ---------------------------------------------------------------- */

/**
 * Render the before/after box.
 *
 * @param WP_Post $post Current post.
 */
function ml_render_ba_box( $post ) {
	wp_nonce_field( 'ml_ba_save', 'ml_ba_nonce' );
	ml_render_media_field( '_ml_ba_before', __( 'Before image', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_ba_before', true ) );
	ml_render_media_field( '_ml_ba_after', __( 'After image', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_ba_after', true ) );
	ml_box_text( '_ml_ba_treatment', __( 'Treatment name', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_ba_treatment', true ) );
}

/**
 * A media library picker field.
 *
 * @param string $id     Field id/name.
 * @param string $label  Label.
 * @param mixed  $value  Attachment ID.
 */
function ml_render_media_field( $id, $label, $value ) {
	$attachment_id = (int) $value;
	echo '<div class="ml-media-field" style="margin:0 0 16px">';
	echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
	echo '<div class="ml-media-preview" style="margin:8px 0">';
	if ( $attachment_id ) {
		echo wp_get_attachment_image( $attachment_id, 'medium' );
	}
	echo '</div>';
	echo '<input type="hidden" class="ml-media-id" name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $attachment_id ) . '"/>';
	echo '<button type="button" class="button ml-media-button" data-title="' . esc_attr( $label ) . '">' . esc_html( $attachment_id ? __( 'Change image', 'ma-lumiere' ) : __( 'Select image', 'ma-lumiere' ) ) . '</button> ';
	echo '<button type="button" class="button button-link-delete ml-media-remove">' . esc_html__( 'Remove', 'ma-lumiere' ) . '</button>';
	echo '</div>';
}

/**
 * Save before/after meta.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function ml_save_media_meta( $post_id, $post ) {
	if ( ! isset( $_POST['ml_ba_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ml_ba_nonce'] ), 'ml_ba_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'ml_before_after' !== $post->post_type ) {
		return;
	}

	update_post_meta( $post_id, '_ml_ba_before', isset( $_POST['_ml_ba_before'] ) ? absint( $_POST['_ml_ba_before'] ) : 0 );
	update_post_meta( $post_id, '_ml_ba_after', isset( $_POST['_ml_ba_after'] ) ? absint( $_POST['_ml_ba_after'] ) : 0 );
	update_post_meta( $post_id, '_ml_ba_treatment', isset( $_POST['_ml_ba_treatment'] ) ? sanitize_text_field( wp_unslash( $_POST['_ml_ba_treatment'] ) ) : '' );
}

/* -----------------------------------------------------------------
 * Testimonial metabox
 * ---------------------------------------------------------------- */

/**
 * Render testimonial attribution.
 *
 * @param WP_Post $post Current post.
 */
function ml_render_testimonial_box( $post ) {
	wp_nonce_field( 'ml_testimonial_save', 'ml_testimonial_nonce' );
	ml_box_text( '_ml_testi_author', __( 'Patient / author name', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_testi_author', true ) );
	ml_box_text( '_ml_testi_treatment', __( 'Treatment / context', 'ma-lumiere' ), get_post_meta( $post->ID, '_ml_testi_treatment', true ) );
	echo '<p class="description">' . esc_html__( 'Only publish real, consented patient feedback.', 'ma-lumiere' ) . '</p>';
}

/**
 * Save testimonial meta.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function ml_save_testimonial_meta( $post_id, $post ) {
	if ( ! isset( $_POST['ml_testimonial_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ml_testimonial_nonce'] ), 'ml_testimonial_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'ml_testimonial' !== $post->post_type ) {
		return;
	}

	update_post_meta( $post_id, '_ml_testi_author', isset( $_POST['_ml_testi_author'] ) ? sanitize_text_field( wp_unslash( $_POST['_ml_testi_author'] ) ) : '' );
	update_post_meta( $post_id, '_ml_testi_treatment', isset( $_POST['_ml_testi_treatment'] ) ? sanitize_text_field( wp_unslash( $_POST['_ml_testi_treatment'] ) ) : '' );
}