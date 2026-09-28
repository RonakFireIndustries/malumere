<?php
/**
 * Fallback index / blog home.
 *
 * @package ma-lumiere
 */

get_header();

if ( is_home() && ! is_front_page() ) :
	?>
	<section class="page-hero">
		<div class="container">
			<?php ml_breadcrumbs(); ?>
			<h1 class="page-hero__title"><?php echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) ); ?></h1>
			<p class="page-hero__lead"><?php esc_html_e( 'Insights from the Ma Lumière clinic.', 'ma-lumiere' ); ?></p>
		</div>
	</section>
<?php endif; ?>

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
			<p><?php esc_html_e( 'No entries found.', 'ma-lumiere' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();