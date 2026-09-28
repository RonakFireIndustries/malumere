<?php
/**
 * Treatment category taxonomy archive.
 *
 * @package ma-lumiere
 */

get_header();

$term = get_queried_object();
?>

<section class="page-hero">
	<div class="container">
		<?php ml_breadcrumbs(); ?>
		<h1 class="page-hero__title"><?php echo esc_html( single_term_title( '', false ) ); ?></h1>
		<?php if ( term_description( $term ) ) : ?>
			<p class="page-hero__lead"><?php echo esc_html( wp_strip_all_tags( term_description( $term ) ) ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="treatments__grid">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/treatment-card' );
				endwhile;
			else :
				?>
				<div class="ph" style="grid-column:1/-1">[NO TREATMENTS IN THIS CATEGORY YET]</div>
			<?php endif; ?>
		</div>

		<p style="margin-top:var(--sp-xl);text-align:center">
			<a class="link-arrow" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-treatments.php', get_post_type_archive_link( 'ml_treatment' ) ) ); ?>">
				<?php esc_html_e( 'View all treatments', 'ma-lumiere' ); ?>
				<?php ml_icon( 'arrow-right', 18 ); ?>
			</a>
		</p>

	</div>
</section>

<?php
get_footer();