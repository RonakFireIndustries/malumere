<?php
/**
 * Post / CPT archives.
 *
 * @package ma-lumiere
 */

get_header();

$is_blog = is_home();

if ( is_category() ) {
	$archive_title = single_cat_title( '', false );
} elseif ( is_tag() ) {
	$archive_title = single_tag_title( '', false );
} else {
	$archive_title = get_the_archive_title();
}

$archive_title = html_entity_decode( $archive_title, ENT_QUOTES, 'UTF-8' );
?>

<section class="page-hero">
	<div class="container">
		<?php ml_breadcrumbs(); ?>
		<h1 class="page-hero__title"><?php echo $is_blog ? esc_html( get_the_title( get_option( 'page_for_posts' ) ) ) : wp_kses_post( $archive_title ); ?></h1>
		<?php
		$archive_description = get_the_archive_description();
		if ( $archive_description ) :
			?>
			<p class="page-hero__lead"><?php echo esc_html( wp_strip_all_tags( $archive_description ) ); ?></p>
			<?php
		endif;
		?>
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