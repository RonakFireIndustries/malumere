<?php
/**
 * Latest articles section.
 *
 * @package ma-lumiere
 */

$articles = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 3,
		'post_status'    => 'publish',
		'ignore_sticky_posts' => true,
	)
);

$blog_url = get_permalink( get_option( 'page_for_posts' ) );
if ( ! $blog_url ) {
	$blog_url = home_url( '/?paged=1' );
}
?>

<section class="section blog-section">
	<div class="container">
		<div class="section__head section__head--center">
			<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'From the journal', 'ma-lumiere' ); ?></p>
			<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'Skin knowledge, written clearly', 'ma-lumiere' ); ?></h2>
		</div>

		<div class="posts-grid">
			<?php
			if ( $articles->have_posts() ) :
				while ( $articles->have_posts() ) :
					$articles->the_post();
					get_template_part( 'template-parts/blog-card' );
				endwhile;
				wp_reset_postdata();
			else :
				?>
				<p><?php esc_html_e( 'Articles will appear once published.', 'ma-lumiere' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>