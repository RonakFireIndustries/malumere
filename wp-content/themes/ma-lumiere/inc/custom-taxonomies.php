<?php
/**
 * Custom taxonomies: treatment_category + faq_topic.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'ml_register_taxonomies' );

/**
 * Register taxonomies.
 */
function ml_register_taxonomies() {
	register_taxonomy(
		'treatment_category',
		array( 'ml_treatment', 'ml_before_after' ),
		array(
			'labels'            => array(
				'name'          => __( 'Treatment Types', 'ma-lumiere' ),
				'singular_name' => __( 'Treatment Type', 'ma-lumiere' ),
				'add_new_item'  => __( 'Add Treatment Type', 'ma-lumiere' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'treatment-type', 'with_front' => false ),
		)
	);

	register_taxonomy(
		'faq_topic',
		array( 'ml_faq' ),
		array(
			'labels'            => array(
				'name'          => __( 'FAQ Topics', 'ma-lumiere' ),
				'singular_name' => __( 'FAQ Topic', 'ma-lumiere' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'faq-topic', 'with_front' => false ),
		)
	);
}