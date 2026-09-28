<?php
/**
 * Treatment archive.
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
$current_term = is_tax( 'treatment_category' ) ? get_queried_object() : null;
?>

<section class="page-hero">
	<div class="container">
		<?php ml_breadcrumbs(); ?>
		<h1 class="page-hero__title"><?php echo $current_term ? esc_html( $current_term->name ) : esc_html__( 'Treatments', 'ma-lumiere' ); ?></h1>
		<p class="page-hero__lead"><?php esc_html_e( 'Explore the medical and aesthetic treatments available at Ma Lumière. Every treatment begins with a consultation.', 'ma-lumiere' ); ?></p>
	</div>
</section>

<section class="section">
	<div class="container">

		<?php if ( ! is_wp_error( $categories ) && $categories ) : ?>
			<ul class="chips">
				<li><a class="chip <?php echo $current_term ? '' : 'is-active'; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'ml_treatment' ) ); ?>"><?php esc_html_e( 'All', 'ma-lumiere' ); ?></a></li>
				<?php foreach ( $categories as $category ) : ?>
					<li>
						<a class="chip <?php echo ( $current_term && $current_term->term_id === $category->term_id ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $category ) ); ?>">
							<?php echo esc_html( $category->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="treatments__grid">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/treatment-card' );
				endwhile;
			else :
				?>
				<div class="ph" style="grid-column:1/-1">[NO TREATMENTS YET — add treatments in the WordPress admin]</div>
			<?php endif; ?>
		</div>

		<?php the_posts_pagination(); ?>

	</div>
</section>

<?php
get_footer();