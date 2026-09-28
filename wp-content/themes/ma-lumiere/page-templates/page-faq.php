<?php
/**
 * Template Name: FAQs
 *
 * Grouped, accessible FAQ accordion.
 *
 * @package ma-lumiere
 */

get_header();

$topics = get_terms(
	array(
		'taxonomy'   => 'faq_topic',
		'hide_empty' => true,
		'orderby'    => 'name',
	)
);

$faqs = get_posts(
	array(
		'post_type'      => 'ml_faq',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'ASC',
	)
);
?>

<section class="page-hero">
	<div class="container">
		<?php ml_breadcrumbs(); ?>
		<h1 class="page-hero__title"><?php the_title(); ?></h1>
		<p class="page-hero__lead"><?php esc_html_e( 'Common questions about consultations, treatments and care at Ma Lumière.', 'ma-lumiere' ); ?></p>
	</div>
</section>

<section class="section">
	<div class="container">

		<?php if ( ! $faqs ) : ?>
			<div class="ph">[FAQ CONTENT REQUIRED — add FAQs in the WordPress admin]</div>
		<?php endif; ?>

		<?php if ( ! is_wp_error( $topics ) && $topics ) : ?>
			<ul class="chips">
				<?php foreach ( $topics as $topic ) : ?>
					<li><a class="chip" href="#<?php echo esc_attr( 'topic-' . $topic->term_id ); ?>"><?php echo esc_html( $topic->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $faqs ) : ?>
			<!-- Ungrouped FAQs first -->
			<div class="faq-list" data-accordion>
				<?php foreach ( $faqs as $faq ) : ?>
					<?php
					$faq_terms = get_the_terms( $faq->ID, 'faq_topic' );
					if ( $faq_terms && ! is_wp_error( $faq_terms ) ) {
						continue;
					}
					global $post;
					$post = $faq; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					setup_postdata( $post );
					get_template_part( 'template-parts/faq-item' );
					wp_reset_postdata();
				endforeach;
				?>
			</div>

			<!-- Grouped by topic -->
			<?php if ( ! is_wp_error( $topics ) && $topics ) : ?>
				<?php foreach ( $topics as $topic ) : ?>
					<div id="<?php echo esc_attr( 'topic-' . $topic->term_id ); ?>" style="scroll-margin-top:calc(var(--header-h) + 1rem)">
						<h2 class="section__title" style="margin-top:var(--sp-2xl)"><?php echo esc_html( $topic->name ); ?></h2>
						<div class="faq-list" data-accordion>
							<?php foreach ( $faqs as $faq ) : ?>
								<?php
								$faq_terms = get_the_terms( $faq->ID, 'faq_topic' );
								if ( ! $faq_terms || is_wp_error( $faq_terms ) || ! in_array( $topic->term_id, wp_list_pluck( $faq_terms, 'term_id' ), true ) ) {
									continue;
								}
								global $post;
								$post = $faq; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
								setup_postdata( $post );
								get_template_part( 'template-parts/faq-item' );
								wp_reset_postdata();
							endforeach;
							?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>

	</div>
</section>

<?php
get_template_part( 'template-parts/cta-section' );
get_footer();