<?php
/**
 * Before/After card with comparison slider.
 *
 * @package ma-lumiere
 */

$before_id = (int) get_post_meta( get_the_ID(), '_ml_ba_before', true );
$after_id  = (int) get_post_meta( get_the_ID(), '_ml_ba_after', true );
$treatment = get_post_meta( get_the_ID(), '_ml_ba_treatment', true );
$terms     = get_the_terms( get_the_ID(), 'treatment_category' );

$has_images = $before_id && $after_id;
?>
<figure class="ba-card" data-reveal="up">
	<div class="ba-slider" data-ba-slider <?php echo $has_images ? 'style="--pos:50"' : ''; ?>>
		<?php if ( $has_images ) : ?>
			<img class="ba-slider__img ba-slider__before" src="<?php echo esc_url( wp_get_attachment_image_url( $before_id, 'ml-card' ) ); ?>" alt="<?php echo esc_attr( $treatment ? $treatment : get_the_title() ); ?> — before" />
			<img class="ba-slider__img ba-slider__after" src="<?php echo esc_url( wp_get_attachment_image_url( $after_id, 'ml-card' ) ); ?>" alt="<?php echo esc_attr( $treatment ? $treatment : get_the_title() ); ?> — after" />
			<span class="ba-slider__label"><?php esc_html_e( 'Before', 'ma-lumiere' ); ?></span>
			<span class="ba-slider__label ba-slider__label--after"><?php esc_html_e( 'After', 'ma-lumiere' ); ?></span>
			<div class="ba-slider__handle" aria-hidden="true">
				<span class="ba-slider__knob">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
				</span>
			</div>
		<?php else : ?>
			<div class="ph" style="border:0;border-radius:0;min-height:100%">[BEFORE/AFTER IMAGES REQUIRED]</div>
		<?php endif; ?>
	</div>
	<figcaption class="ba-card__foot">
		<h3 class="ba-card__title"><?php echo esc_html( $treatment ? $treatment : get_the_title() ); ?></h3>
		<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
			<p class="ba-card__cat"><?php echo esc_html( $terms[0]->name ); ?></p>
		<?php endif; ?>
	</figcaption>
</figure>