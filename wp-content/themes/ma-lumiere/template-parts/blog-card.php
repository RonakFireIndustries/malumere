<?php
/**
 * Blog card component.
 *
 * @package ma-lumiere
 */

$cats = get_the_category();
?>
<article <?php post_class( 'card post-card' ); ?> data-reveal="up">
	<a class="card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'ml-card' );
		} else {
			echo '<div class="ph" style="border:0;border-radius:0;min-height:100%">[IMAGE REQUIRED]</div>';
		}
		?>
	</a>
	<div class="card__body">
		<?php ml_post_meta(); ?>
		<h3 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php ml_excerpt( 20 ); ?>
		<p class="card__action">
			<a class="link-arrow" href="<?php the_permalink(); ?>">
				<?php esc_html_e( 'Read article', 'ma-lumiere' ); ?>
				<?php ml_icon( 'arrow-right', 18 ); ?>
			</a>
		</p>
	</div>
</article>