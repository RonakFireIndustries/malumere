<?php
/**
 * Front page — luxury editorial homepage.
 *
 * Composes editable sections from the Customizer and content post types.
 * Real clinic assets are used when present; clearly-labelled placeholders
 * otherwise. No invented credentials, results or testimonials.
 *
 * @package ma-lumiere
 */

get_header();

get_template_part( 'template-parts/hero' );
get_template_part( 'template-parts/trust' );
get_template_part( 'template-parts/doctor-section' );
get_template_part( 'template-parts/treatments-section' );
get_template_part( 'template-parts/journey' );
get_template_part( 'template-parts/philosophy' );
get_template_part( 'template-parts/results' );
get_template_part( 'template-parts/testimonials' );
get_template_part( 'template-parts/blog-section' );
get_template_part( 'template-parts/faq-section' );
get_template_part( 'template-parts/cta-section' );
get_template_part( 'template-parts/contact-section' );

get_footer();