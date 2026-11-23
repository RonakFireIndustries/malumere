<?php
/**
 * Before & After archive.
 *
 * @package ma-lumiere
 */

get_header(); ?>

<section class="page-hero">
	<div class="container">
		<?php ml_breadcrumbs(); ?>
		<h1 class="page-hero__title"><?php esc_html_e( 'Results', 'ma-lumiere' ); ?></h1>
		<p class="page-hero__lead"><?php esc_html_e( 'Real outcomes from the clinic, shared with consent and care for privacy. Individual results vary.', 'ma-lumiere' ); ?></p>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="results__grid">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/before-after-card' );
				endwhile;
			else :
				?>
				<div class="entry-content" style="grid-column:1/-1"><p><?php esc_html_e( 'Consent-approved before-and-after photographs are not published here at this time. Treatment plans and expected outcomes are discussed during consultation.', 'ma-lumiere' ); ?></p></div>
			<?php endif; ?>
		</div>
		<?php the_posts_pagination(); ?>
	</div>
</section>

<?php
get_footer();