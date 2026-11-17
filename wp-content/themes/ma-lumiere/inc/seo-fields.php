<?php
/**
 * Per-page SEO fields.
 *
 * Adds an optional search title, meta description and focus keyword to posts,
 * pages and treatments. Empty fields fall back to the theme's local defaults,
 * so the client only has to fill in the pages that need a custom angle.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', 'ml_seo_add_meta_box' );
add_action( 'save_post', 'ml_seo_save_meta', 10, 2 );

/**
 * Post types that get the search-appearance box.
 *
 * @return array
 */
function ml_seo_supported_post_types() {
	return array( 'post', 'page', 'ml_treatment' );
}

/**
 * Register the metabox.
 */
function ml_seo_add_meta_box() {
	foreach ( ml_seo_supported_post_types() as $post_type ) {
		add_meta_box(
			'ml_seo_box',
			__( 'Search appearance', 'ma-lumiere' ),
			'ml_seo_render_box',
			$post_type,
			'normal',
			'default'
		);
	}
}

/**
 * Render the search-appearance fields.
 *
 * @param WP_Post $post Current post.
 */
function ml_seo_render_box( $post ) {
	wp_nonce_field( 'ml_seo_save', 'ml_seo_nonce' );

	$title       = (string) get_post_meta( $post->ID, '_ml_seo_title', true );
	$description = (string) get_post_meta( $post->ID, '_ml_seo_description', true );
	$keyword     = (string) get_post_meta( $post->ID, '_ml_seo_focus_keyword', true );
	?>
	<p>
		<label for="ml_seo_title"><strong><?php esc_html_e( 'SEO title', 'ma-lumiere' ); ?></strong></label><br/>
		<input type="text" class="widefat" id="ml_seo_title" name="ml_seo_title" value="<?php echo esc_attr( $title ); ?>" maxlength="90" />
		<span class="description"><?php esc_html_e( 'Aim for 50–60 characters. Leave blank to use the automatic local title, e.g. “Acne & Acne Scars in Mira Road & Bhayander”.', 'ma-lumiere' ); ?></span>
	</p>

	<p>
		<label for="ml_seo_description"><strong><?php esc_html_e( 'Meta description', 'ma-lumiere' ); ?></strong></label><br/>
		<textarea class="widefat" id="ml_seo_description" name="ml_seo_description" rows="3" maxlength="200"><?php echo esc_textarea( $description ); ?></textarea>
		<span class="description"><?php esc_html_e( 'Aim for 150–158 characters. Leave blank to use the automatic description with the clinic location and phone number.', 'ma-lumiere' ); ?></span>
	</p>

	<p>
		<label for="ml_seo_focus_keyword"><strong><?php esc_html_e( 'Focus keyword', 'ma-lumiere' ); ?></strong></label><br/>
		<input type="text" class="widefat" id="ml_seo_focus_keyword" name="ml_seo_focus_keyword" value="<?php echo esc_attr( $keyword ); ?>" maxlength="120" />
		<span class="description"><?php esc_html_e( 'For your own reference, e.g. “face treatment Mira Road”. Make sure the page title and first paragraph use it naturally.', 'ma-lumiere' ); ?></span>
	</p>

	<?php if ( 'ml_treatment' === $post->post_type ) : ?>
		<p class="description">
			<?php esc_html_e( 'Local treatment pages rank best when the first paragraph names the concern, the treatment and the area (Mira Road, Bhayander) in natural language.', 'ma-lumiere' ); ?>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Save the SEO fields.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function ml_seo_save_meta( $post_id, $post ) {
	if ( ! isset( $_POST['ml_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ml_seo_nonce'] ), 'ml_seo_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! in_array( $post->post_type, ml_seo_supported_post_types(), true ) ) {
		return;
	}

	$fields = array(
		'_ml_seo_title'         => isset( $_POST['ml_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ml_seo_title'] ) ) : '',
		'_ml_seo_description'   => isset( $_POST['ml_seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ml_seo_description'] ) ) : '',
		'_ml_seo_focus_keyword' => isset( $_POST['ml_seo_focus_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['ml_seo_focus_keyword'] ) ) : '',
	);

	foreach ( $fields as $key => $value ) {
		if ( '' === trim( $value ) ) {
			delete_post_meta( $post_id, $key );
			continue;
		}

		update_post_meta( $post_id, $key, $value );
	}
}
