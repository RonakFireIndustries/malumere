<?php
/**
 * Single treatment.
 *
 * Editorial split hero + sticky booking rail, with the long-form treatment
 * sections kept visible and given a clear numbered hierarchy.
 *
 * @package ma-lumiere
 */

get_header();

while ( have_posts() ) :
	the_post();

	$short      = get_post_meta( get_the_ID(), '_ml_treatment_short', true );
	$intro      = get_post_meta( get_the_ID(), '_ml_treatment_intro', true );
	$overview   = get_post_meta( get_the_ID(), '_ml_treatment_overview', true );
	$suitable   = get_post_meta( get_the_ID(), '_ml_treatment_suitable', true );
	$concerns   = get_post_meta( get_the_ID(), '_ml_treatment_concerns', true );
	$process    = get_post_meta( get_the_ID(), '_ml_treatment_process', true );
	$sessions   = get_post_meta( get_the_ID(), '_ml_treatment_sessions', true );
	$downtime   = get_post_meta( get_the_ID(), '_ml_treatment_downtime', true );
	$results    = get_post_meta( get_the_ID(), '_ml_treatment_results', true );
	$pre        = get_post_meta( get_the_ID(), '_ml_treatment_pre', true );
	$post_care  = get_post_meta( get_the_ID(), '_ml_treatment_post', true );
	$faqs_raw   = get_post_meta( get_the_ID(), '_ml_treatment_faqs', true );
	$related    = array_filter( array_map( 'absint', (array) get_post_meta( get_the_ID(), '_ml_treatment_related', true ) ) );
	$terms      = get_the_terms( get_the_ID(), 'treatment_category' );
	$term_name  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';

	$process_steps = array_filter( array_map( 'trim', explode( "\n", (string) $process ) ) );
	$faq_pairs     = array();
	foreach ( array_filter( array_map( 'trim', explode( "\n", (string) $faqs_raw ) ) ) as $line ) {
		$parts = explode( '||', $line, 2 );
		if ( count( $parts ) === 2 ) {
			$faq_pairs[] = array( 'q' => trim( $parts[0] ), 'a' => trim( $parts[1] ) );
		}
	}

	$booking_url = ml_page_url_by_template( 'page-templates/page-appointment.php' );

	$facts = array();
	if ( $sessions ) {
		$facts[] = array( __( 'Sessions', 'ma-lumiere' ), $sessions );
	}
	if ( $downtime ) {
		$facts[] = array( __( 'Downtime', 'ma-lumiere' ), $downtime );
	}
	$facts[] = array( __( 'Consultation', 'ma-lumiere' ), __( 'By appointment', 'ma-lumiere' ) );

	$has_care = ( $pre || $post_care );

	/*
	 * Support the alternating rows with hand-picked related imagery first,
	 * then the site's editorial portraits. The treatment's own featured image
	 * anchors the process row, so the body never borrows another treatment's
	 * photo as if it were this one.
	 */
	$supporting_images = array();
	foreach ( $related as $related_id ) {
		if ( count( $supporting_images ) >= 2 ) {
			break;
		}
		$image_url = get_the_post_thumbnail_url( $related_id, 'large' );
		if ( $image_url && ! in_array( $image_url, $supporting_images, true ) ) {
			$supporting_images[] = $image_url;
		}
	}
	foreach ( ml_treatment_editorial_images( 2 ) as $editorial_url ) {
		if ( count( $supporting_images ) >= 2 ) {
			break;
		}
		if ( ! in_array( $editorial_url, $supporting_images, true ) ) {
			$supporting_images[] = $editorial_url;
		}
	}
	$featured_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );
	if ( empty( $supporting_images ) && $featured_url ) {
		$supporting_images[] = $featured_url;
	}
	?>

	<section class="td-hero">
		<div class="container">
			<div class="td-hero__grid">
				<div class="td-hero__body">
					<?php ml_breadcrumbs(); ?>

					<?php if ( $term_name ) : ?>
						<p class="td-hero__cat"><?php echo esc_html( $term_name ); ?></p>
					<?php endif; ?>

					<h1 class="td-hero__title"><?php the_title(); ?></h1>

					<?php if ( $short ) : ?>
						<p class="td-hero__lead"><?php echo esc_html( $short ); ?></p>
					<?php endif; ?>

					<div class="td-hero__actions">
						<a class="btn btn--primary" href="<?php echo esc_url( $booking_url ); ?>">
							<?php esc_html_e( 'Book a Consultation', 'ma-lumiere' ); ?>
						</a>
						<?php if ( $overview || $process_steps ) : ?>
							<a class="btn btn--outline" href="#how-it-works">
								<?php esc_html_e( 'Explore the treatment', 'ma-lumiere' ); ?>
							</a>
						<?php endif; ?>
					</div>

					<?php if ( $facts ) : ?>
						<dl class="td-hero__facts">
							<?php foreach ( $facts as $fact ) : ?>
								<div class="td-hero__fact">
									<dt><?php echo esc_html( $fact[0] ); ?></dt>
									<dd><?php echo esc_html( $fact[1] ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
				</div>

				<div class="td-hero__media" data-reveal="fade">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'large' ); ?>
					<?php else : ?>
						<div class="td-hero__placeholder"><?php esc_html_e( '[TREATMENT IMAGE]', 'ma-lumiere' ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="section" id="how-it-works">
		<div class="container td-layout">
			<div class="td-body">
				<?php $row_index = 0; ?>

				<?php if ( $intro || get_the_content() || $overview ) : ?>
					<?php
					$media_side = 0 === $row_index % 2 ? 'left' : 'right';
					$image_url  = $supporting_images[0] ?? '';
					?>
					<div class="td-row td-row--media-<?php echo esc_attr( $media_side ); ?>" data-reveal="fade">
						<div class="td-row__text">
							<?php if ( $intro || get_the_content() ) : ?>
								<div class="entry-content td-intro">
									<?php
									echo wp_kses_post( $intro );
									the_content();
									?>
								</div>
							<?php endif; ?>

							<?php if ( $overview ) : ?>
								<section class="td-section">
									<h2><?php esc_html_e( 'How it works', 'ma-lumiere' ); ?></h2>
									<?php echo wp_kses_post( $overview ); ?>
								</section>
							<?php endif; ?>
						</div>

						<figure class="td-row__media">
							<?php if ( $image_url ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy" />
							<?php else : ?>
								<div class="td-row__placeholder"><?php esc_html_e( '[TREATMENT IMAGE]', 'ma-lumiere' ); ?></div>
							<?php endif; ?>
						</figure>
					</div>
					<?php $row_index++; ?>
				<?php endif; ?>

				<?php if ( $suitable || $concerns ) : ?>
					<?php
					$media_side = 0 === $row_index % 2 ? 'left' : 'right';
					$image_url  = $supporting_images[1] ?? $supporting_images[0] ?? '';
					?>
					<div class="td-row td-row--media-<?php echo esc_attr( $media_side ); ?>" data-reveal="fade">
						<div class="td-row__text">
							<?php if ( $suitable ) : ?>
								<section class="td-section">
									<h2><?php esc_html_e( 'Who it is for', 'ma-lumiere' ); ?></h2>
									<?php echo wp_kses_post( $suitable ); ?>
								</section>
							<?php endif; ?>

							<?php if ( $concerns ) : ?>
								<section class="td-section">
									<h2><?php esc_html_e( 'Concerns it addresses', 'ma-lumiere' ); ?></h2>
									<?php echo wp_kses_post( $concerns ); ?>
								</section>
							<?php endif; ?>
						</div>

						<figure class="td-row__media">
							<?php if ( $image_url ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy" />
							<?php else : ?>
								<div class="td-row__placeholder"><?php esc_html_e( '[TREATMENT IMAGE]', 'ma-lumiere' ); ?></div>
							<?php endif; ?>
						</figure>
					</div>
					<?php $row_index++; ?>
				<?php endif; ?>

				<?php if ( $process_steps || $results ) : ?>
					<?php
					$media_side = 0 === $row_index % 2 ? 'left' : 'right';
					$image_url  = $featured_url ? $featured_url : ( $supporting_images[0] ?? '' );
					?>
					<div class="td-row td-row--media-<?php echo esc_attr( $media_side ); ?>" data-reveal="fade">
						<div class="td-row__text">
							<?php if ( $process_steps ) : ?>
								<section class="td-section">
									<h2><?php esc_html_e( 'Treatment process', 'ma-lumiere' ); ?></h2>
									<div class="td-process">
										<ol class="td-process__list">
											<?php foreach ( $process_steps as $step ) : ?>
												<li><?php echo esc_html( $step ); ?></li>
											<?php endforeach; ?>
										</ol>
									</div>
								</section>
							<?php endif; ?>

							<?php if ( $results ) : ?>
								<section class="td-section">
									<h2><?php esc_html_e( 'Expected results', 'ma-lumiere' ); ?></h2>
									<?php echo wp_kses_post( $results ); ?>
								</section>
							<?php endif; ?>
						</div>

						<figure class="td-row__media">
							<?php if ( $image_url ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy" />
							<?php else : ?>
								<div class="td-row__placeholder"><?php esc_html_e( '[TREATMENT IMAGE]', 'ma-lumiere' ); ?></div>
							<?php endif; ?>
						</figure>
					</div>
					<?php $row_index++; ?>
				<?php endif; ?>

				<?php if ( $has_care ) : ?>
					<div class="td-care-band" data-reveal="fade">
						<section class="td-section">
							<h2><?php esc_html_e( 'Preparation & aftercare', 'ma-lumiere' ); ?></h2>
							<div class="td-care">
								<?php if ( $pre ) : ?>
									<div class="td-care__card">
										<h3><?php esc_html_e( 'Before the treatment', 'ma-lumiere' ); ?></h3>
										<?php echo wp_kses_post( $pre ); ?>
									</div>
								<?php endif; ?>
								<?php if ( $post_care ) : ?>
									<div class="td-care__card">
										<h3><?php esc_html_e( 'After the treatment', 'ma-lumiere' ); ?></h3>
										<?php echo wp_kses_post( $post_care ); ?>
									</div>
								<?php endif; ?>
							</div>
						</section>
					</div>
				<?php endif; ?>

			</div>

			<aside class="td-aside" data-reveal="right">
				<div class="td-book">
					<p class="eyebrow"><?php esc_html_e( 'Your next step', 'ma-lumiere' ); ?></p>
					<h2><?php esc_html_e( 'Book a consultation', 'ma-lumiere' ); ?></h2>
					<p><?php esc_html_e( 'Every treatment begins with a private consultation, a skin assessment and a plan built around your goals.', 'ma-lumiere' ); ?></p>
					<a class="btn btn--primary btn--block" href="<?php echo esc_url( $booking_url ); ?>">
						<?php esc_html_e( 'Book a Consultation', 'ma-lumiere' ); ?>
					</a>
					<?php if ( ml_clinic_phone() ) : ?>
						<p class="td-book__phone">
							<?php esc_html_e( 'Prefer to talk?', 'ma-lumiere' ); ?>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', ml_clinic_phone() ) ); ?>"><?php echo esc_html( ml_clinic_phone() ); ?></a>
						</p>
					<?php endif; ?>
				</div>

				<div class="td-next">
					<h2><?php esc_html_e( 'What happens next', 'ma-lumiere' ); ?></h2>
					<ol class="td-next__list">
						<li><?php esc_html_e( 'Consultation & skin assessment', 'ma-lumiere' ); ?></li>
						<li><?php esc_html_e( 'A personalised treatment plan', 'ma-lumiere' ); ?></li>
						<li><?php esc_html_e( 'Treatment and follow-up', 'ma-lumiere' ); ?></li>
					</ol>
				</div>
			</aside>
		</div>
	</section>

	<?php if ( $faq_pairs ) : ?>
		<section class="section section--tint">
			<div class="container">
				<div class="section__head section__head--center">
					<p class="eyebrow"><?php esc_html_e( 'Good to know', 'ma-lumiere' ); ?></p>
					<h2 class="section__title"><?php esc_html_e( 'Questions about this treatment', 'ma-lumiere' ); ?></h2>
				</div>
				<div class="faq-list" data-accordion>
					<?php foreach ( $faq_pairs as $i => $faq ) : ?>
						<div class="faq-item">
							<h3 class="faq-item__heading">
								<button type="button" class="faq-item__button" id="td-faq-button-<?php echo (int) $i; ?>" aria-expanded="false" aria-controls="td-faq-panel-<?php echo (int) $i; ?>" data-accordion-button>
									<span><?php echo esc_html( $faq['q'] ); ?></span>
									<span class="faq-item__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>
								</button>
							</h3>
							<div class="faq-item__panel" id="td-faq-panel-<?php echo (int) $i; ?>" role="region" aria-labelledby="td-faq-button-<?php echo (int) $i; ?>" data-accordion-panel>
								<div class="faq-item__panel-inner entry-content">
									<?php echo wp_kses_post( wpautop( $faq['a'] ) ); ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	// Real results: before / after entries in the same treatment category.
	$ba_args = array(
		'post_type'      => 'ml_before_after',
		'posts_per_page' => 3,
		'post_status'    => 'publish',
	);
	if ( $terms && ! is_wp_error( $terms ) ) {
		$ba_args['tax_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'taxonomy' => 'treatment_category',
			'field'    => 'term_id',
			'terms'    => wp_list_pluck( $terms, 'term_id' ),
		);
	}
	$results_query = new WP_Query( $ba_args );
	if ( $results_query->have_posts() ) :
		?>
		<section class="section results">
			<div class="container">
				<div class="section__head section__head--center">
					<p class="eyebrow"><?php esc_html_e( 'Before & after', 'ma-lumiere' ); ?></p>
					<h2 class="section__title"><?php esc_html_e( 'Real results', 'ma-lumiere' ); ?></h2>
				</div>
				<div class="results__grid">
					<?php
					while ( $results_query->have_posts() ) :
						$results_query->the_post();
						get_template_part( 'template-parts/before-after-card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	// Hand-picked related treatments.
	$related_query = null;
	if ( $related ) {
		$related_query = new WP_Query(
			array(
				'post_type'      => 'ml_treatment',
				'post__in'       => $related,
				'orderby'        => 'post__in',
				'posts_per_page' => 3,
				'post_status'    => 'publish',
			)
		);
	}
	?>
	<?php if ( $related_query && $related_query->have_posts() ) : ?>
		<section class="section section--tint">
			<div class="container">
				<div class="section__head section__head--center">
					<p class="eyebrow"><?php esc_html_e( 'Keep exploring', 'ma-lumiere' ); ?></p>
					<h2 class="section__title"><?php esc_html_e( 'Related treatments', 'ma-lumiere' ); ?></h2>
				</div>
				<div class="treatments__grid">
					<?php
					while ( $related_query->have_posts() ) :
						$related_query->the_post();
						get_template_part( 'template-parts/treatment-card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/cta-section' ); ?>

	<?php
endwhile;

get_footer();
