<?php
/**
 * Template Name: About
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

	<?php if ( get_the_content() ) : ?>
		<section class="section">
			<div class="container container--narrow entry-content">
				<?php the_content(); ?>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;

get_template_part( 'template-parts/doctor-section' );
get_template_part( 'template-parts/journey' );
get_template_part( 'template-parts/philosophy' );
get_template_part( 'template-parts/cta-section' );

get_footer();