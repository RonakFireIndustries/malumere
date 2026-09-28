<?php
/**
 * Template Name: Contact
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
	<?php
endwhile;

get_template_part( 'template-parts/contact-section' );

// Optional map embed, added through the editor as <iframe> only if the
// owner supplies one. Nothing is hard-coded here.
$map = ml_mod( 'ml_map_embed', '' );
if ( $map ) :
	?>
	<section class="section section--tint">
		<div class="container">
			<div style="border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);aspect-ratio:21/9">
				<?php echo wp_kses_post( $map ); // phpcs:ignore WordPress.Security.EscapeOutput -- trusted admin-supplied embed. ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();