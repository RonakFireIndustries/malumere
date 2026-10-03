<?php
/**
 * Site header.
 *
 * @package ma-lumiere
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="theme-color" content="#f8f8f8" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'ma-lumiere' ); ?></a>

<header id="masthead" class="site-header" data-site-header>
	<div class="container site-header__inner">

		<div class="site-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="site-brand__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php $logo = ml_default_logo_url(); ?>
					<?php if ( $logo ) : ?>
						<img class="site-brand__logo" src="<?php echo esc_url( $logo ); ?>" width="35" height="46" alt="" />
					<?php endif; ?>
					<span class="site-brand__text">
						<span class="site-brand__name"><?php echo esc_html( ml_clinic_name() ); ?></span>
						<span class="site-brand__tag"><?php esc_html_e( 'SKIN | HAIR | LASER', 'ma-lumiere' ); ?></span>
					</span>
				</a>
			<?php endif; ?>
		</div>

		<nav id="site-navigation" class="main-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'ma-lumiere' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu',
					'fallback_cb'    => 'ml_primary_menu_fallback',
					'depth'          => 2,
				)
			);
			?>
			<div class="nav-cta">
				<a class="btn btn--primary" href="<?php echo esc_url( ml_page_url_by_template( 'page-templates/page-appointment.php' ) ); ?>">
					<?php esc_html_e( 'Book Consultation', 'ma-lumiere' ); ?>
				</a>
			</div>
		</nav>

		<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-navigation" data-nav-toggle>
			<span class="nav-toggle__icon" aria-hidden="true"></span>
			<span class="visually-hidden"><?php esc_html_e( 'Toggle menu', 'ma-lumiere' ); ?></span>
		</button>

	</div>
</header>

<main id="primary" class="site-main">