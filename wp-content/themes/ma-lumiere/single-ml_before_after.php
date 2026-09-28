<?php
/**
 * Single before/after case.
 *
 * @package ma-lumiere
 */

get_header();

while ( have_posts() ) :
	the_post();

	$before_id = (int) get_post_meta( get_the_ID(), '_ml_ba_before', true );
	$after_id  = (int) get_post_meta( get_the_ID(), '_ml_ba_after', true );
	$treatment = get_post_meta( get_the_ID(), '_ml_ba_treatment', true );
	$terms     = get_the_terms( get_the_ID(), 'treatment_category' );
	$has       = $before_id && $after_id;
	?>

	<section class="page-hero" style="padding-bottom:0">
		<div class="container container--narrow">
			<?php ml_breadcrumbs(); ?>
			<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
				<p class="card__cat" style="margin-bottom:.4em"><?php echo esc_html( $terms[0]->name ); ?></p>
			<?php endif; ?>
			<h1 class="page-hero__title"><?php echo esc_html( $treatment ? $treatment : get_the_title() ); ?></h1>
			<p class="page-hero__lead"><?php esc_html_e( 'Shared with consent. Individual outcomes vary.', 'ma-lumiere' ); ?></p>
		</div>
	</section>

	<section class="section">
		<div class="container container--narrow">
			<?php if ( $has ) : ?>
				<div class="ba-slider" data-ba-slider style="--pos:50;aspect-ratio:4/3.2;height:auto">
					<img class="ba-slider__img ba-slider__before" src="<?php echo esc_url( wp_get_attachment_image_url( $before_id, 'large' ) ); ?>" alt="<?php echo esc_attr( $treatment ); ?> — before" />
					<img class="ba-slider__img ba-slider__after" src="<?php echo esc_url( wp_get_attachment_image_url( $after_id, 'large' ) ); ?>" alt="<?php echo esc_attr( $treatment ); ?> — after" />
					<span class="ba-slider__label"><?php esc_html_e( 'Before', 'ma-lumiere' ); ?></span>
					<span class="ba-slider__label ba-slider__label--after"><?php esc_html_e( 'After', 'ma-lumiere' ); ?></span>
					<div class="ba-slider__handle" aria-hidden="true">
						<span class="ba-slider__knob">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
						</span>
					</div>
				</div>
			<?php else : ?>
				<div class="ph">[BEFORE/AFTER IMAGES REQUIRED]</div>
			<?php endif; ?>

			<div class="entry-content" style="margin-top:var(--sp-lg)">
				<?php the_content(); ?>
			</div>

			<p style="margin-top:var(--sp-lg)">
				<a class="btn btn--primary" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-appointment.php' ) ); ?>">
					<?php esc_html_e( 'Discuss your skin goals', 'ma-lumiere' ); ?>
				</a>
			</p>
		</div>
	</section>

	<?php
endwhile;

get_footer();