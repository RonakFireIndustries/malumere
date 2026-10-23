<?php
/**
 * Template Name: Treatments
 *
 * A flat editorial treatment index: split hero, then alternating text/image
 * rows. Treatments are intentionally not grouped by category.
 *
 * @package ma-lumiere
 */

get_header();

$all_treatments = get_posts(
	array(
		'post_type'      => 'ml_treatment',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	)
);

$hero_images = array();
foreach ( $all_treatments as $treatment ) {
	if ( count( $hero_images ) >= 3 ) {
		break;
	}
	$image_url = get_the_post_thumbnail_url( $treatment->ID, 'large' );
	if ( $image_url ) {
		$hero_images[] = $image_url;
	}
}

$booking_url = ml_page_url_by_template( 'page-templates/page-appointment.php' );
$total       = count( $all_treatments );
?>

<section class="td-hero td-hero--index">
	<div class="container">
		<div class="td-hero__grid">
			<div class="td-hero__body">
				<?php ml_breadcrumbs(); ?>

				<p class="td-hero__cat"><?php esc_html_e( 'Medical & aesthetic dermatology', 'ma-lumiere' ); ?></p>
				<h1 class="td-hero__title"><?php the_title(); ?></h1>
				<p class="td-hero__lead"><?php esc_html_e( 'Every treatment begins with a private consultation. Explore the care we offer, then we will shape a plan around your skin and your goals.', 'ma-lumiere' ); ?></p>

				<div class="td-hero__actions">
					<a class="btn btn--primary" href="<?php echo esc_url( $booking_url ); ?>">
						<?php esc_html_e( 'Book a Consultation', 'ma-lumiere' ); ?>
					</a>
					<?php if ( $all_treatments ) : ?>
						<a class="btn btn--outline" href="#treatment-index">
							<?php esc_html_e( 'View all treatments', 'ma-lumiere' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<dl class="td-hero__facts">
					<div class="td-hero__fact">
						<dt><?php esc_html_e( 'Treatments', 'ma-lumiere' ); ?></dt>
						<dd><?php echo esc_html( $total ); ?></dd>
					</div>
					<div class="td-hero__fact">
						<dt><?php esc_html_e( 'Approach', 'ma-lumiere' ); ?></dt>
						<dd><?php esc_html_e( 'Bespoke', 'ma-lumiere' ); ?></dd>
					</div>
					<div class="td-hero__fact">
						<dt><?php esc_html_e( 'First step', 'ma-lumiere' ); ?></dt>
						<dd><?php esc_html_e( 'Consultation', 'ma-lumiere' ); ?></dd>
					</div>
				</dl>
			</div>

			<div class="td-hero__media td-hero__collage" data-reveal="fade">
				<?php if ( $hero_images ) : ?>
					<?php foreach ( $hero_images as $index => $image_url ) : ?>
						<figure class="td-hero__tile td-hero__tile--<?php echo (int) $index; ?>">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>" />
						</figure>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="td-hero__placeholder"><?php esc_html_e( '[TREATMENT IMAGES]', 'ma-lumiere' ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<section class="section" id="treatment-index">
	<div class="container">
		<header class="treatments-list__head" data-reveal="fade">
			<p class="eyebrow"><?php esc_html_e( 'The treatment menu', 'ma-lumiere' ); ?></p>
			<h2 class="section__title"><?php esc_html_e( 'Every treatment, in one place', 'ma-lumiere' ); ?></h2>
			<p class="treatments-list__lead"><?php esc_html_e( 'A complete index of the care we offer. Each treatment begins with a private consultation and is planned around your skin, your goals, and your schedule.', 'ma-lumiere' ); ?></p>
		</header>

		<?php if ( ! $all_treatments ) : ?>
			<div class="ph">[TREATMENTS REQUIRED — add treatments in the WordPress admin]</div>
		<?php else : ?>
			<div class="treatments-list">
				<?php foreach ( $all_treatments as $index => $treatment ) : ?>
					<?php
					global $post;
					$post = $treatment; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					setup_postdata( $post );

					$short    = get_post_meta( $treatment->ID, '_ml_treatment_short', true );
					$duration = get_post_meta( $treatment->ID, '_ml_treatment_duration', true );
					$sessions = get_post_meta( $treatment->ID, '_ml_treatment_sessions', true );
					$downtime = get_post_meta( $treatment->ID, '_ml_treatment_downtime', true );
					$media    = 0 === $index % 2 ? 'right' : 'left';
					?>
					<article class="td-row td-row--media-<?php echo esc_attr( $media ); ?>" data-reveal="fade">
						<div class="td-row__text">
							<p class="treatments-list__number"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></p>
							<h3 class="treatments-list__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>

							<?php if ( $short ) : ?>
								<p class="treatments-list__excerpt"><?php echo esc_html( $short ); ?></p>
							<?php endif; ?>

							<?php if ( $duration || $sessions || $downtime ) : ?>
								<ul class="treatments-list__meta">
									<?php if ( $duration ) : ?>
										<li>
											<span><?php esc_html_e( 'Duration', 'ma-lumiere' ); ?></span>
											<?php echo esc_html( $duration ); ?>
										</li>
									<?php endif; ?>
									<?php if ( $sessions ) : ?>
										<li>
											<span><?php esc_html_e( 'Sessions', 'ma-lumiere' ); ?></span>
											<?php echo esc_html( $sessions ); ?>
										</li>
									<?php endif; ?>
									<?php if ( $downtime ) : ?>
										<li>
											<span><?php esc_html_e( 'Downtime', 'ma-lumiere' ); ?></span>
											<?php echo esc_html( $downtime ); ?>
										</li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>

							<a class="treatments-list__link" href="<?php the_permalink(); ?>">
								<span><?php esc_html_e( 'Explore treatment', 'ma-lumiere' ); ?></span>
								<?php ml_icon( 'arrow-right' ); ?>
							</a>
						</div>

						<figure class="td-row__media treatments-list__media">
							<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'ml-card' ); ?>
								<?php else : ?>
									<span class="td-row__placeholder"><?php esc_html_e( '[TREATMENT IMAGE]', 'ma-lumiere' ); ?></span>
								<?php endif; ?>
							</a>
						</figure>
					</article>
				<?php endforeach; ?>
				<?php wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_template_part( 'template-parts/cta-section' );
get_footer();
