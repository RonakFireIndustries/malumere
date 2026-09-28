<?php
/**
 * Post / CPT archives.
 *
 * @package ma-lumiere
 */

get_header();

$is_blog = is_home();
?>

<section class="page-hero">
	<div class="container">
		<?php ml_breadcrumbs(); ?>
		<h1 class="page-hero__title"><?php echo $is_blog ? esc_html( get_the_title( get_option( 'page_for_posts' ) ) ) : esc_html( get_the_archive_title() ); ?></h1>
		<?php the_archive_description( '<p class="page-hero__lead">', '</p>' ); ?>
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
			<p><?php esc_html_e( 'Nothing has been published here yet.', 'ma-lumiere' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();