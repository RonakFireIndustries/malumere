<?php
/**
 * Comments + comment form.
 *
 * @package ma-lumiere
 */

if ( post_password_required() ) {
	return;
}
?>
<section class="comments-area" id="comments">
	<?php if ( have_comments() ) : ?>
		<h3 class="comments-title">
			<?php
			printf(
				/* translators: %s: count of comments. */
				esc_html( _n( '%s Comment', '%s Comments', get_comments_number(), 'ma-lumiere' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h3>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>

		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'ma-lumiere' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply_before' => '<h4 class="comment-reply-title">',
			'title_reply_after'  => '</h4>',
		)
	);
	?>
</section>