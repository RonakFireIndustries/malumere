<?php
/**
 * Template Name: Treatments
 *
 * A curated treatments landing page that groups every treatment by type.
 *
 * @package ma-lumiere
 */

get_header();

$categories = get_terms(
	array(
		'taxonomy'   => 'treatment_category',
		'hide_empty' => true,
		'parent'     => 0,
		'orderby'    => 'name',
	)
);
$has_grouping  = ! is_wp_error( $categories ) && $categories;
$all_treatments = get_posts(
	array(
		'post_type'      => 'ml_treatment',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	)
);
?>

<section class="page-hero">
	<div class="container">
		<?php ml_breadcrumbs(); ?>
		<h1 class="page-hero__title"><?php the_title(); ?></h1>
		<p class="page-hero__lead"><?php esc_html_e( 'Medical and aesthetic dermatology tailored to your skin. Every treatment begins with a private consultation.', 'ma-lumiere' ); ?></p>
	</div>
</section>

<section class="section">
	<div class="container">

		<?php if ( ! $all_treatments ) : ?>
			<div class="ph">[TREATMENTS REQUIRED — add treatments in the WordPress admin]</div>
		<?php endif; ?>

		<?php if ( $has_grouping ) : ?>
			<ul class="chips">
				<?php foreach ( $categories as $category ) : ?>
					<li><a class="chip" href="#<?php echo esc_attr( 'cat-' . $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $has_grouping ) : ?>
			<?php foreach ( $categories as $category ) : ?>
				<?php
				$in_cat = array();
				foreach ( $all_treatments as $treatment ) {
					$terms = get_the_terms( $treatment->ID, 'treatment_category' );
					if ( $terms && ! is_wp_error( $terms ) && in_array( $category->term_id, wp_list_pluck( $terms, 'term_id' ), true ) ) {
						$in_cat[] = $treatment;
					}
				}
				if ( ! $in_cat ) {
					continue;
				}
				?>
				<div id="<?php echo esc_attr( 'cat-' . $category->term_id ); ?>" style="scroll-margin-top:calc(var(--header-h) + 1rem)">
					<h2 class="section__title" style="margin-top:var(--sp-2xl)"><?php echo esc_html( $category->name ); ?></h2>
					<div class="treatments__grid">
						<?php foreach ( $in_cat as $treatment ) : ?>
							<?php
							// Render the reusable card using template engine temporary trick.
							global $post;
							$post = $treatment; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
							setup_postdata( $post );
							get_template_part( 'template-parts/treatment-card' );
							endforeach;
							wp_reset_postdata();
							?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php if ( ! $has_grouping && $all_treatments ) : ?>
			<div class="treatments__grid">
				<?php
				foreach ( $all_treatments as $treatment ) :
					global $post;
					$post = $treatment; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					setup_postdata( $post );
					get_template_part( 'template-parts/treatment-card' );
					wp_reset_postdata();
				endforeach;
				?>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php
get_template_part( 'template-parts/cta-section' );
get_footer();