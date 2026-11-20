<?php
/**
 * FAQ section (latest FAQs in an accessible accordion).
 *
 * @package ma-lumiere
 */

$faqs = new WP_Query(
	array(
		'post_type'      => 'ml_faq',
		'posts_per_page' => 6,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'ASC',
	)
);
?>

<section class="section section--tint">
	<div class="container">
		<div class="section__head section__head--center">
			<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'Questions', 'ma-lumiere' ); ?></p>
			<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'Frequently asked questions', 'ma-lumiere' ); ?></h2>
		</div>

		<?php if ( $faqs->have_posts() ) : ?>
			<div class="faq-list" data-accordion>
				<?php
				while ( $faqs->have_posts() ) :
					$faqs->the_post();
					get_template_part( 'template-parts/faq-item' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<div class="ph">[FAQ CONTENT REQUIRED — add FAQs in the WordPress admin]</div>
		<?php endif; ?>

	</div>
</section>