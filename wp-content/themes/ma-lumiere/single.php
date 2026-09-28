<?php
/**
 * Single post.
 *
 * @package ma-lumiere
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

		<header class="single-post__head">
			<div class="container container--narrow">
				<?php ml_breadcrumbs(); ?>
				<h1 class="single-post__title"><?php the_title(); ?></h1>
				<div class="single-post__meta">
					<span><?php the_author(); ?></span>
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<?php
					$reading_time = ml_reading_time( get_the_content() );
					if ( $reading_time ) :
						printf( '<span>%1$s %2$s</span>', esc_html( $reading_time ), esc_html__( 'min read', 'ma-lumiere' ) );
					endif;
					?>
				</div>
			</div>
			<?php
			$cat_objects = get_the_category();
			if ( ! empty( $cat_objects ) ) :
				printf( '<p class="single-post__meta"><a href="%1$s">%2$s</a></p>', esc_url( get_category_link( $cat_objects[0] ) ), esc_html( $cat_objects[0]->name ) );
			endif;
			?>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="container">
				<div class="single-post__thumb">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="single-post__content entry-content">
			<?php
			the_content();
			wp_link_pages(
				array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ma-lumiere' ),
					'after'  => '</div>',
				)
			);
			?>
		</div>

		<div class="container">
			<div class="single-post__tags">
				<?php ml_get_terms( 'category' ); ?>
				<?php ml_get_terms( 'post_tag' ); ?>
			</div>
		</div>

		<?php
		// Related posts.
		$cats = wp_get_post_categories( get_the_ID() );
		$rel  = new WP_Query(
			array(
				'category__in'   => $cats ? $cats : array(),
				'post__not_in'   => array( get_the_ID() ),
				'posts_per_page' => 3,
				'post_status'    => 'publish',
			)
		);
		if ( $rel->have_posts() ) :
			?>
			<section class="section related-posts">
				<div class="container">
					<h2 class="section__title"><?php esc_html_e( 'You may also like', 'ma-lumiere' ); ?></h2>
					<div class="posts-grid">
						<?php
						while ( $rel->have_posts() ) :
							$rel->the_post();
							get_template_part( 'template-parts/blog-card' );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		// Comments.
		comments_template();
		?>

	</article>
	<?php
endwhile;

get_footer();