<?php
/**
 * Generic page template.
 *
 * @package ma-lumiere
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="page-hero">
		<div class="container">
			<?php ml_breadcrumbs(); ?>
			<h1 class="page-hero__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="page-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="section">
		<div class="container container--narrow">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry-content' ); ?>>
				<?php
				the_content();
				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ma-lumiere' ),
						'after'  => '</div>',
					)
				);
				?>
			</article>
		</div>
	</section>
	<?php
endwhile;

get_footer();