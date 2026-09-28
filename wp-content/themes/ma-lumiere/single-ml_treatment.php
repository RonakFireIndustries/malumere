<?php
/**
 * Single treatment.
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
	$related    = array_map( 'absint', (array) get_post_meta( get_the_ID(), '_ml_treatment_related', true ) );
	$terms      = get_the_terms( get_the_ID(), 'treatment_category' );

	$process_steps = array_filter( array_map( 'trim', explode( "\n", (string) $process ) ) );
	$faq_pairs     = array();
	foreach ( array_filter( array_map( 'trim', explode( "\n", (string) $faqs_raw ) ) ) as $line ) {
		$parts = explode( '||', $line, 2 );
		if ( count( $parts ) === 2 ) {
			$faq_pairs[] = array( 'q' => trim( $parts[0] ), 'a' => trim( $parts[1] ) );
		}
	}
	?>

	<section class="page-hero" style="padding-bottom:0">
		<div class="container container--narrow">
			<?php ml_breadcrumbs(); ?>
			<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
				<p class="card__cat" style="margin-bottom:.4em"><?php echo esc_html( $terms[0]->name ); ?></p>
			<?php endif; ?>
			<h1 class="page-hero__title"><?php the_title(); ?></h1>
			<?php if ( $short ) : ?>
				<p class="page-hero__lead"><?php echo esc_html( $short ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="container" style="margin-top:var(--sp-lg)">
				<?php the_post_thumbnail( 'large', array( 'style' => 'width:100%;height:clamp(320px,46vw,520px);object-fit:cover;border-radius:var(--radius-lg)' ) ); ?>
			</div>
		<?php endif; ?>
	</section>

	<section class="section">
		<div class="container td-grid">
			<div class="td-content">

				<?php if ( $intro || get_the_content() ) : ?>
					<div class="entry-content" data-reveal="fade">
						<?php
						echo wp_kses_post( $intro );
						the_content();
						?>
					</div>
				<?php endif; ?>

				<?php if ( $overview ) : ?>
					<div class="entry-content" data-reveal="fade">
						<h2><?php esc_html_e( 'How it works', 'ma-lumiere' ); ?></h2>
						<?php echo wp_kses_post( $overview ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $suitable ) : ?>
					<div class="entry-content" data-reveal="fade">
						<h2><?php esc_html_e( 'Who it is for', 'ma-lumiere' ); ?></h2>
						<?php echo wp_kses_post( $suitable ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $concerns ) : ?>
					<div class="entry-content" data-reveal="fade">
						<h2><?php esc_html_e( 'Concerns it addresses', 'ma-lumiere' ); ?></h2>
						<?php echo wp_kses_post( $concerns ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $process_steps ) : ?>
					<div data-reveal="fade">
						<h2><?php esc_html_e( 'Treatment process', 'ma-lumiere' ); ?></h2>
						<ol class="td-process__list">
							<?php foreach ( $process_steps as $step ) : ?>
								<li><?php echo esc_html( $step ); ?></li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>

				<?php if ( $results ) : ?>
					<div class="entry-content" data-reveal="fade">
						<h2><?php esc_html_e( 'Expected results', 'ma-lumiere' ); ?></h2>
						<?php echo wp_kses_post( $results ); ?>
					</div>
				<?php endif; ?>

				<div class="entry-content" data-reveal="fade">
					<h2><?php esc_html_e( 'Preparation & aftercare', 'ma-lumiere' ); ?></h2>
					<?php if ( $pre ) : ?>
						<h4><?php esc_html_e( 'Before the treatment', 'ma-lumiere' ); ?></h4>
						<?php echo wp_kses_post( $pre ); ?>
					<?php endif; ?>
					<?php if ( $post_care ) : ?>
						<h4><?php esc_html_e( 'After the treatment', 'ma-lumiere' ); ?></h4>
						<?php echo wp_kses_post( $post_care ); ?>
					<?php endif; ?>
				</div>

			</div>

			<div class="td-side" data-reveal="right">
				<div class="td-stats">
					<h3 style="margin-bottom:.8em"><?php esc_html_e( 'At a glance', 'ma-lumiere' ); ?></h3>
					<dl class="td-stats__dl">
						<div class="td-stats__item"><dt><?php esc_html_e( 'Category', 'ma-lumiere' ); ?></dt><dd><?php echo esc_html( $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '—' ); ?></dd></div>
						<div class="td-stats__item"><dt><?php esc_html_e( 'Sessions', 'ma-lumiere' ); ?></dt><dd><?php echo esc_html( $sessions ? $sessions : '—' ); ?></dd></div>
						<div class="td-stats__item"><dt><?php esc_html_e( 'Downtime', 'ma-lumiere' ); ?></dt><dd><?php echo esc_html( $downtime ? $downtime : '—' ); ?></dd></div>
						<div class="td-stats__item"><dt><?php esc_html_e( 'Consultation', 'ma-lumiere' ); ?></dt><dd><?php esc_html_e( 'By appointment', 'ma-lumiere' ); ?></dd></div>
					</dl>
				</div>

				<div class="td-cta">
					<a class="btn btn--primary btn--block" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-appointment.php' ) ); ?>">
						<?php esc_html_e( 'Book a Consultation', 'ma-lumiere' ); ?>
					</a>
				</div>
			</div>
		</div>
	</section>

	<?php if ( $faq_pairs ) : ?>
		<section class="section section--tint">
			<div class="container">
				<h2 class="section__title section__head--center" style="text-align:center"><?php esc_html_e( 'Questions about this treatment', 'ma-lumiere' ); ?></h2>
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
	// Before / after entries tagged with the same treatment type.
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
				<h2 class="section__title section__head--center" style="text-align:center"><?php esc_html_e( 'Real results', 'ma-lumiere' ); ?></h2>
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

	<?php if ( $related ) : ?>
		<section class="section section--tint">
			<div class="container">
				<h2 class="section__title section__head--center" style="text-align:center"><?php esc_html_e( 'Related treatments', 'ma-lumiere' ); ?></h2>
				<div class="treatments__grid">
					<?php
					$related_query = new WP_Query(
						array(
							'post_type'      => 'ml_treatment',
							'post__in'       => $related,
							'orderby'        => 'post__in',
							'posts_per_page' => 3,
							'post_status'    => 'publish',
						)
					);
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