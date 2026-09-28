<?php
/**
 * Search results.
 *
 * @package ma-lumiere
 */

get_header(); ?>

<section class="page-hero">
	<div class="container">
		<?php ml_breadcrumbs(); ?>
		<h1 class="page-hero__title">
			<?php
			printf(
				/* translators: %s: number of results. */
				esc_html( _n( '%s result found', '%s results found', (int) $wp_query->found_posts, 'ma-lumiere' ) ),
				esc_html( number_format_i18n( (int) $wp_query->found_posts ) )
			);
			?>
		</h1>
		<p class="page-hero__lead"><?php echo esc_html__( 'Search results for', 'ma-lumiere' ) . ' “' . esc_html( get_search_query() ) . '”.'; ?></p>
	</div>
</section>

<section class="section">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="posts-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/blog-card' );
				endwhile;
				?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<div class="error-404__inner">
				<h2><?php esc_html_e( 'Nothing matched your search.', 'ma-lumiere' ); ?></h2>
				<p><?php esc_html_e( 'Try different keywords, or browse our treatments instead.', 'ma-lumiere' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();