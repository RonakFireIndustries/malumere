<?php
/**
 * Theme Customizer — single source of truth for editable site content.
 *
 * @package ma-lumiere
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'customize_register', 'ml_customize_register' );

/**
 * Register customizer sections and settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function ml_customize_register( $wp_customize ) {
	$wp_customize->add_panel(
		'ml_site_content',
		array(
			'title'    => __( 'Ma Lumière Content', 'ma-lumiere' ),
			'priority' => 30,
		)
	);

	/* ---------- Hero ---------- */
	$wp_customize->add_section(
		'ml_hero',
		array(
			'title'    => __( 'Home — Hero', 'ma-lumiere' ),
			'panel'    => 'ml_site_content',
			'priority' => 10,
		)
	);

	$wp_customize->add_setting(
		'ml_hero_eyebrow',
		array(
			'default'           => __( 'Personalised Dermatology & Aesthetic Care', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_hero_eyebrow', array(
		'label'   => __( 'Eyebrow line', 'ma-lumiere' ),
		'section' => 'ml_hero',
	) );

	$wp_customize->add_setting(
		'ml_hero_title',
		array(
			'default'           => __( 'Personalised Dermatology. Beautifully, Scientifically.', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_hero_title', array(
		'label'   => __( 'Headline', 'ma-lumiere' ),
		'section' => 'ml_hero',
	) );

	$wp_customize->add_setting(
		'ml_hero_text',
		array(
			'default'           => __( 'Medical dermatology and aesthetic care tailored to your skin, your concerns and your journey.', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_hero_text', array(
		'label'   => __( 'Supporting text', 'ma-lumiere' ),
		'section' => 'ml_hero',
		'type'    => 'textarea',
	) );

	$wp_customize->add_setting(
		'ml_hero_cta_primary',
		array(
			'default'           => __( 'Book a Consultation', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_hero_cta_primary', array(
		'label'   => __( 'Primary button label', 'ma-lumiere' ),
		'section' => 'ml_hero',
	) );

	$wp_customize->add_setting(
		'ml_hero_cta_primary_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control( 'ml_hero_cta_primary_url', array(
		'label'       => __( 'Primary button link', 'ma-lumiere' ),
		'description' => __( 'Leave empty to link to the appointment page.', 'ma-lumiere' ),
		'section'     => 'ml_hero',
	) );

	$wp_customize->add_setting(
		'ml_hero_cta_secondary',
		array(
			'default'           => __( 'Explore Treatments', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_hero_cta_secondary', array(
		'label'   => __( 'Secondary button label', 'ma-lumiere' ),
		'section' => 'ml_hero',
	) );

	$wp_customize->add_setting(
		'ml_hero_cta_secondary_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control( 'ml_hero_cta_secondary_url', array(
		'label'       => __( 'Secondary button link', 'ma-lumiere' ),
		'description' => __( 'Leave empty to link to the treatments page.', 'ma-lumiere' ),
		'section'     => 'ml_hero',
	) );

	$wp_customize->add_setting(
		'ml_hero_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control( new WP_Customize_Media_Control(
		$wp_customize,
		'ml_hero_image',
		array(
			'label'       => __( 'Hero image', 'ma-lumiere' ),
			'description' => __( 'Clinic or doctor photography. A tasteful placeholder is shown until uploaded.', 'ma-lumiere' ),
			'mime_type'   => 'image',
			'section'     => 'ml_hero',
		)
	) );

	/* ---------- Philosophy ---------- */
	$wp_customize->add_section(
		'ml_philosophy',
		array(
			'title'    => __( 'Philosophy Section', 'ma-lumiere' ),
			'panel'    => 'ml_site_content',
			'priority' => 25,
		)
	);

	$wp_customize->add_setting(
		'ml_philosophy_quote',
		array(
			'default'           => __( 'Your skin deserves more than a routine. It deserves a plan.', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_philosophy_quote', array(
		'label'   => __( 'Headline quote', 'ma-lumiere' ),
		'section' => 'ml_philosophy',
	) );

	$wp_customize->add_setting(
		'ml_philosophy_body',
		array(
			'default'           => __( 'Beautiful skin begins with understanding — exactly how your skin behaves, what it has been through, and what it needs going forward. At Ma Lumière, every journey starts with a conversation and a careful assessment, then develops into a plan built around you.', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control( 'ml_philosophy_body', array(
		'label'   => __( 'Body text', 'ma-lumiere' ),
		'type'    => 'textarea',
		'section' => 'ml_philosophy',
	) );

	/* ---------- Doctor ---------- */
	$wp_customize->add_section(
		'ml_doctor',
		array(
			'title'    => __( 'Doctor', 'ma-lumiere' ),
			'panel'    => 'ml_site_content',
			'priority' => 20,
		)
	);

	$wp_customize->add_setting(
		'ml_doctor_name',
		array(
			'default'           => __( '[DOCTOR NAME REQUIRED]', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_doctor_name', array(
		'label'   => __( 'Full name', 'ma-lumiere' ),
		'section' => 'ml_doctor',
	) );

	$wp_customize->add_setting(
		'ml_doctor_qualifications',
		array(
			'default'           => __( '[DOCTOR QUALIFICATIONS REQUIRED]', 'ma-lumiere' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_doctor_qualifications', array(
		'label'   => __( 'Qualifications', 'ma-lumiere' ),
		'section' => 'ml_doctor',
	) );

	$wp_customize->add_setting(
		'ml_doctor_bio',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
		)
	);
	$wp_customize->add_control( 'ml_doctor_bio', array(
		'label'   => __( 'Short biography', 'ma-lumiere' ),
		'type'    => 'textarea',
		'section' => 'ml_doctor',
	) );

	$wp_customize->add_setting(
		'ml_doctor_expertise',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_doctor_expertise', array(
		'label'       => __( 'Areas of expertise', 'ma-lumiere' ),
		'description' => __( 'Comma separated, e.g. Acne, Pigmentation, Hair Loss', 'ma-lumiere' ),
		'type'        => 'textarea',
		'section'     => 'ml_doctor',
	) );

	$wp_customize->add_setting(
		'ml_doctor_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control( new WP_Customize_Media_Control(
		$wp_customize,
		'ml_doctor_image',
		array(
			'label'     => __( 'Doctor photograph', 'ma-lumiere' ),
			'mime_type' => 'image',
			'section'   => 'ml_doctor',
		)
	) );

	/* ---------- Clinic ---------- */
	$wp_customize->add_section(
		'ml_clinic',
		array(
			'title'    => __( 'Clinic Contact', 'ma-lumiere' ),
			'panel'    => 'ml_site_content',
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'ml_clinic_name',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_clinic_name', array(
		'label'   => __( 'Clinic name', 'ma-lumiere' ),
		'section' => 'ml_clinic',
	) );

	$wp_customize->add_setting(
		'ml_clinic_address',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control( 'ml_clinic_address', array(
		'label'   => __( 'Address', 'ma-lumiere' ),
		'type'    => 'textarea',
		'section' => 'ml_clinic',
	) );

	$wp_customize->add_setting(
		'ml_clinic_phone',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_clinic_phone', array(
		'label'   => __( 'Phone', 'ma-lumiere' ),
		'section' => 'ml_clinic',
	) );

	$wp_customize->add_setting(
		'ml_clinic_whatsapp',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control( 'ml_clinic_whatsapp', array(
		'label'   => __( 'WhatsApp number', 'ma-lumiere' ),
		'section' => 'ml_clinic',
	) );

	$wp_customize->add_setting(
		'ml_clinic_email',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_email',
		)
	);
	$wp_customize->add_control( 'ml_clinic_email', array(
		'label'   => __( 'Email', 'ma-lumiere' ),
		'section' => 'ml_clinic',
	) );

	$wp_customize->add_setting(
		'ml_clinic_hours',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control( 'ml_clinic_hours', array(
		'label'       => __( 'Opening hours', 'ma-lumiere' ),
		'description' => __( 'One line per day, e.g. Monday · 10:00 – 14:00, 17:00 – 20:00', 'ma-lumiere' ),
		'type'        => 'textarea',
		'section'     => 'ml_clinic',
	) );

	$wp_customize->add_setting(
		'ml_map_embed',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
		)
	);
	$wp_customize->add_control( 'ml_map_embed', array(
		'label'       => __( 'Map embed HTML', 'ma-lumiere' ),
		'description' => __( 'Paste an <iframe> embed (Google Maps) to display it on the Contact page.', 'ma-lumiere' ),
		'type'        => 'textarea',
		'section'     => 'ml_clinic',
	) );

	/* ---------- Social ---------- */
	$wp_customize->add_section(
		'ml_social',
		array(
			'title'    => __( 'Social Links', 'ma-lumiere' ),
			'panel'    => 'ml_site_content',
			'priority' => 40,
		)
	);

	$socials = array(
		'instagram' => __( 'Instagram', 'ma-lumiere' ),
		'facebook'  => __( 'Facebook', 'ma-lumiere' ),
		'youtube'   => __( 'YouTube', 'ma-lumiere' ),
		'whatsapp'  => __( 'WhatsApp', 'ma-lumiere' ),
	);
	foreach ( $socials as $key => $label ) {
		$wp_customize->add_setting(
			'ml_social_' . $key,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control( 'ml_social_' . $key, array(
			'label'   => $label,
			'section' => 'ml_social',
		) );
	}

	/* ---------- Footer ---------- */
	$wp_customize->add_section(
		'ml_footer',
		array(
			'title'    => __( 'Footer', 'ma-lumiere' ),
			'panel'    => 'ml_site_content',
			'priority' => 50,
		)
	);

	$wp_customize->add_setting(
		'ml_footer_about',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control( 'ml_footer_about', array(
		'label'   => __( 'Short clinic description', 'ma-lumiere' ),
		'type'    => 'textarea',
		'section' => 'ml_footer',
	) );
}