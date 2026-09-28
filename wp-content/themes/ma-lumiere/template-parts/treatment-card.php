<?php
/**
 * Treatment card component.
 *
 * @package ma-lumiere
 */

$short = get_post_meta( get_the_ID(), '_ml_treatment_short', true );
$terms = get_the_terms( get_the_ID(), 'treatment_category' );
?>
<article <?php post_class( 'card' ); ?> data-reveal="up">
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
		<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
			<p class="card__cat"><?php echo esc_html( $terms[0]->name ); ?></p>
		<?php endif; ?>
		<h3 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="card__excerpt"><?php echo esc_html( $short ? $short : get_the_excerpt() ); ?></p>
		<p class="card__action">
			<a class="link-arrow" href="<?php the_permalink(); ?>">
				<?php esc_html_e( 'Learn more', 'ma-lumiere' ); ?>
				<?php ml_icon( 'arrow-right', 18 ); ?>
			</a>
		</p>
	</div>
</article>