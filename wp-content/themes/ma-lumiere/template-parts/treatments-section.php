<?php
/**
 * Treatments grid section (latest treatments) + category chips.
 *
 * @package ma-lumiere
 */

$treatments = new WP_Query(
	array(
		'post_type'      => 'ml_treatment',
		'posts_per_page' => 9,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	)
);

$categories = get_terms(
	array(
		'taxonomy'   => 'treatment_category',
		'hide_empty' => true,
		'parent'     => 0,
		'orderby'    => 'name',
	)
);
?>

<section class="section treatments" id="treatments">
	<div class="container">

		<div class="section__head section__head--center">
			<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'What we treat', 'ma-lumiere' ); ?></p>
			<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'Dermatology, thoughtfully done', 'ma-lumiere' ); ?></h2>
			<p class="section__lead" data-reveal="fade"><?php esc_html_e( 'Medical and aesthetic care across the conditions that matter to you.', 'ma-lumiere' ); ?></p>
		</div>

		<?php if ( ! is_wp_error( $categories ) && $categories ) : ?>
			<ul class="chips" data-reveal="fade">
				<?php foreach ( array_slice( $categories, 0, 8 ) as $category ) : ?>
					<li>
						<a class="chip" href="<?php echo esc_url( get_term_link( $category ) ); ?>">
							<?php echo esc_html( $category->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="treatments__grid">
			<?php
			if ( $treatments->have_posts() ) :
				while ( $treatments->have_posts() ) :
					$treatments->the_post();
					get_template_part( 'template-parts/treatment-card' );
				endwhile;
				wp_reset_postdata();
			else :
				?>
				<div class="ph" style="grid-column:1/-1">[TREATMENTS REQUIRED — add treatments in the WordPress admin]</div>
			<?php endif; ?>
		</div>

		<p class="section__head section__head--center" style="margin:var(--sp-lg) 0 0" data-reveal="fade">
			<a class="link-arrow" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-treatments.php', get_post_type_archive_link( 'ml_treatment' ) ) ); ?>">
				<?php esc_html_e( 'View all treatments', 'ma-lumiere' ); ?>
				<?php ml_icon( 'arrow-right', 18 ); ?>
			</a>
		</p>

	</div>
</section>