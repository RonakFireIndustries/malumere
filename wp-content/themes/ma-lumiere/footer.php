<?php
/**
 * Site footer.
 *
 * @package ma-lumiere
 */
?>
</main><!-- #primary .site-main -->

<footer class="site-footer">
	<div class="container">

		<div class="footer__grid">

			<div class="footer__col">
				<p class="footer__brand-name"><?php echo esc_html( ml_clinic_name() ); ?></p>
				<p class="footer__about">
					<?php
					$about = ml_mod( 'ml_footer_about', '' );
					echo esc_html( $about ? $about : __( 'SKIN | HAIR | LASER ', 'ma-lumiere' ) );
					?>
				</p>
				<?php
				$socials = ml_social_links();
				if ( $socials ) :
					?>
					<div class="footer__social">
						<?php foreach ( $socials as $key => $url ) : ?>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>">
								<?php ml_icon( $key, 18 ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="footer__col">
				<p class="footer__heading"><?php esc_html_e( 'Navigation', 'ma-lumiere' ); ?></p>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'menu',
						'depth'          => 1,
						'fallback_cb'    => 'ml_footer_menu_fallback',
					)
				);
				?>
			</div>

			<div class="footer__col">
				<p class="footer__heading"><?php esc_html_e( 'Treatments', 'ma-lumiere' ); ?></p>
				<ul>
					<?php
					$treatments = get_posts(
						array(
							'post_type'      => 'ml_treatment',
							'posts_per_page' => 6,
							'post_status'    => 'publish',
							'orderby'        => 'menu_order title',
							'order'          => 'ASC',
						)
					);
					foreach ( $treatments as $treatment ) :
						printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( get_permalink( $treatment ) ), esc_html( get_the_title( $treatment ) ) );
					endforeach;
					if ( ! $treatments ) {
						echo '<li><a href="' . esc_url( ml_page_url_by_template( 'page-templates/page-treatments.php' ) ) . '">' . esc_html__( 'View all treatments', 'ma-lumiere' ) . '</a></li>';
					}
					?>
				</ul>
			</div>

			<div class="footer__col">
				<p class="footer__heading"><?php esc_html_e( 'Contact', 'ma-lumiere' ); ?></p>
				<ul class="footer__contact">
					<?php if ( ml_clinic_address() ) : ?>
						<li><?php ml_icon( 'marker', 80 ); ?><span><?php echo esc_html( ml_clinic_address() ); ?></span></li>
					<?php endif; ?>
					<?php if ( ml_clinic_phone() ) : ?>
						<li><?php ml_icon( 'phone', 16 ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', ml_clinic_phone() ) ); ?>"><?php echo esc_html( ml_clinic_phone() ); ?></a></li>
					<?php endif; ?>
					<?php if ( ml_clinic_email() ) : ?>
						<li><?php ml_icon( 'mail', 16 ); ?><a href="mailto:<?php echo esc_attr( ml_clinic_email() ); ?>"><?php echo esc_html( ml_clinic_email() ); ?></a></li>
					<?php endif; ?>
					<?php if ( ml_clinic_hours() ) : ?>
						<li><?php ml_icon( 'clock', 16 ); ?><span class="contact-info__hours"><?php echo esc_html( ml_clinic_hours() ); ?></span></li>
					<?php endif; ?>
				</ul>
			</div>

		</div>

		<div class="footer__bottom">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( ml_clinic_name() ); ?>. <?php esc_html_e( 'All rights reserved.', 'ma-lumiere' ); ?></p>

			<?php
			$privacy = get_page_by_path( 'privacy-policy' );
			$terms   = get_page_by_path( 'terms' ) ?: get_page_by_path( 'terms-and-conditions' );
			?>
			<nav class="footer__bottom-links" aria-label="<?php esc_attr_e( 'Legal links', 'ma-lumiere' ); ?>">
				<?php if ( $privacy ) : ?>
					<a href="<?php echo esc_url( get_permalink( $privacy ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'ma-lumiere' ); ?></a>
				<?php endif; ?>
				<?php if ( $terms ) : ?>
					<a href="<?php echo esc_url( get_permalink( $terms ) ); ?>"><?php esc_html_e( 'Terms & Conditions', 'ma-lumiere' ); ?></a>
				<?php endif; ?>
			</nav>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>