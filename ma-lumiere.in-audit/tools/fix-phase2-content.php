<?php
/**
 * Phase 2 implementation: revised titles/metas, Botox pillar optimization,
 * taxonomy descriptions and FAQ wording. Run once on local copy.
 */
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

$report = array();

$page_meta = array(
	6   => array(
		'title' => 'Ma Lumière | Skin & Aesthetic Clinic in Mira Road East',
		'description' => 'Ma Lumière is a medical skin and aesthetic care clinic in Mira Road East, offering consultations for acne, pigmentation, hair loss and injectables.',
		'focus' => 'skin clinic Mira Road East',
	),
	7   => array(
		'title' => 'About Ma Lumière | Aesthetic Physician in Mira Road',
		'description' => 'Learn about Ma Lumière in Mira Road East: a medical skin and aesthetic care clinic led by an Aesthetic Physician, focused on consultation-led care.',
		'focus' => 'aesthetic physician Mira Road',
	),
	8   => array(
		'title' => 'Skin Treatments in Mira Road | Ma Lumière',
		'description' => 'Explore medical skin, hair, laser and injectable treatments at Ma Lumière in Mira Road East. Every plan begins with a private consultation.',
		'focus' => 'skin treatments Mira Road',
	),
	9   => array(
		'title' => 'Book a Skin Consultation in Mira Road | Ma Lumière',
		'description' => 'Book a consultation at Ma Lumière, a medical skin and aesthetic care clinic in Mira Road East. Choose a time that suits you or contact the clinic directly.',
		'focus' => 'book skin consultation Mira Road',
	),
	10  => array(
		'title' => 'Contact Ma Lumière | Skin Clinic in Mira Road East',
		'description' => 'Find Ma Lumière in Mira Road East, Maharashtra 401105. Call, message or use the directions link to plan your visit to the clinic.',
		'focus' => 'contact skin clinic Mira Road',
	),
	11  => array(
		'title' => 'Skin Treatment FAQs | Ma Lumière, Mira Road',
		'description' => 'Answers to common questions about skin consultations, acne, pigmentation, hair loss, laser treatments and visiting Ma Lumière in Mira Road East.',
		'focus' => 'skin treatment FAQs Mira Road',
	),
	12  => array(
		'title' => 'Skin Care Journal | Ma Lumière, Mira Road',
		'description' => 'Educational articles from Ma Lumière about acne, acne scars, pigmentation, hair loss, injectables, preparation and aftercare.',
		'focus' => 'skin care education Mira Road',
	),
	109 => array(
		'title' => 'Skin Clinic in Mira Road & Bhayander | Ma Lumière',
		'description' => 'Ma Lumière is a skin and aesthetic care clinic in Mira Road East serving Mira Road, Bhayander and nearby Western Railway locations. Book a consultation.',
		'focus' => 'skin clinic Mira Bhayander',
	),
);

foreach ( $page_meta as $id => $data ) {
	if ( ! get_post( $id ) ) {
		$report[] = array( 'id' => $id, 'status' => 'missing' );
		continue;
	}
	update_post_meta( $id, '_ml_seo_title', $data['title'] );
	update_post_meta( $id, '_ml_seo_description', $data['description'] );
	update_post_meta( $id, '_ml_seo_focus_keyword', $data['focus'] );
	$report[] = array( 'id' => $id, 'status' => 'meta-updated', 'title' => $data['title'] );
}

// Treatment pillars: revised H1/title/meta/educational content.
$treatment_updates = array(
	43 => array(
		'title' => 'Acne & Acne Scar Treatment in Mira Road',
		'seo_title' => 'Acne & Acne Scar Treatment in Mira Road | Ma Lumière',
		'description' => 'Consultation-led acne and acne scar treatment in Mira Road East. Active breakouts are assessed first, then marks and texture are planned in stages.',
		'focus' => 'acne treatment Mira Road',
	),
	53 => array(
		'title' => 'Botox & Injectable Aesthetic Treatments',
		'seo_title' => 'Botox & Injectable Treatments in Mira Road | Ma Lumière',
		'description' => 'Explore Botox and injectable aesthetic treatments in Mira Road East. Facial assessment, conservative planning, suitability and aftercare explained.',
		'focus' => 'Botox treatment Mira Road',
	),
	39 => array(
		'title' => 'Hair Loss & Hair Thinning Treatment in Mira Road',
		'seo_title' => 'Hair Loss & Thinning Treatment in Mira Road | Ma Lumière',
		'description' => 'Clinical assessment for hair fall, shedding and thinning at Ma Lumière in Mira Road East. The plan is based on your pattern, history and scalp examination.',
		'focus' => 'hair loss treatment Mira Road',
	),
	47 => array(
		'title' => 'Pigmentation & Uneven Skin Tone Treatment in Mira Road',
		'seo_title' => 'Pigmentation Treatment in Mira Road | Ma Lumière',
		'description' => 'Pigmentation and uneven tone care in Mira Road East. The first step is identifying the pigment type, then building a treatment and sun-protection plan.',
		'focus' => 'pigmentation treatment Mira Road',
	),
);

foreach ( $treatment_updates as $id => $data ) {
	$post = get_post( $id );
	if ( ! $post || 'ml_treatment' !== $post->post_type ) {
		$report[] = array( 'id' => $id, 'status' => 'missing-or-wrong-type' );
		continue;
	}
	wp_update_post(
		array(
			'ID' => $id,
			'post_title' => $data['title'],
			'post_excerpt' => $data['description'],
		),
		true
	);
	update_post_meta( $id, '_ml_seo_title', $data['seo_title'] );
	update_post_meta( $id, '_ml_seo_description', $data['description'] );
	update_post_meta( $id, '_ml_seo_focus_keyword', $data['focus'] );
	$report[] = array( 'id' => $id, 'status' => 'treatment-updated', 'title' => $data['title'] );
}

// Botox pillar content (existing URL preserved).
$botox_id = 53;
$botox_content = '<p>Botox and other injectable aesthetic treatments are planned after a facial assessment. We look at expression lines, volume balance and your features first, then discuss which option is appropriate, what can realistically change, and what the aftercare involves.</p><p>The aim is conservative refinement rather than a fixed template: softer expression lines, balanced proportions and natural movement where treatment is suitable. Suitability depends on your medical history, the area being treated and the product used. These details are discussed before consent.</p>';
wp_update_post(
	array(
		'ID' => $botox_id,
		'post_content' => $botox_content,
	),
	true
);
$botox_meta = array(
	'_ml_treatment_short' => 'Botox and injectable aesthetic treatments planned after a full facial assessment, with conservative, natural-looking goals.',
	'_ml_treatment_intro' => '<p>Good injectable work starts with assessment, not a menu. We map expression lines, volume and facial balance, then discuss whether Botox or another injectable approach is appropriate for your goals.</p>',
	'_ml_treatment_overview' => '<p>A consultation covering medical history, facial assessment and treatment planning. Where suitable, anti-wrinkle or volume treatments may be used with conservative placement and clear aftercare instructions.</p>',
	'_ml_treatment_suitable' => '<p>Adults considering Botox or injectable treatments for expression lines, facial balance or contour concerns, after an in-clinic consultation.</p>',
	'_ml_treatment_concerns' => '<p>Expression lines, early ageing changes, facial volume loss and contour imbalance.</p>',
	'_ml_treatment_results' => '<p>Changes vary by product, treatment area and individual response. The likely timeline and maintenance interval are discussed before you decide.</p>',
	'_ml_treatment_process' => "Facial assessment and medical history\nSuitability and treatment plan\nConsent and in-clinic placement\nAftercare guidance and review",
	'_ml_treatment_sessions' => 'Based on assessment and review',
	'_ml_treatment_duration' => '30',
	'_ml_treatment_downtime' => 'Depends on treatment; discussed at consultation',
	'_ml_treatment_pre' => '<p>Share your medical history and current medicines. Avoid alcohol and blood-thinning medication only if your prescriber has advised it; do not stop prescribed medication without medical guidance.</p>',
	'_ml_treatment_post' => '<p>Follow the aftercare instructions provided after treatment, avoid strenuous activity for the advised period where relevant, and attend the review appointment.</p>',
	'_ml_treatment_faqs' => "What is Botox used for? || Botox is used for certain expression lines and is considered only after a facial and medical assessment.\nWill my face look frozen? || The aim is to preserve natural expression while softening lines where treatment is appropriate.\nHow long do results last? || Duration varies by product, area and individual response; expectations are discussed before consent.",
);
foreach ( $botox_meta as $key => $value ) {
	update_post_meta( $botox_id, $key, $value );
}
$report[] = array( 'id' => $botox_id, 'status' => 'botox-pillar-updated' );

// Rename visible term label while preserving existing slug and URLs.
$term = get_term_by( 'slug', 'injectables-facial-contouring', 'treatment_category' );
if ( $term && ! is_wp_error( $term ) ) {
	wp_update_term( $term->term_id, 'treatment_category', array( 'name' => 'Botox & Injectables' ) );
	$report[] = array( 'term_id' => $term->term_id, 'status' => 'term-renamed', 'name' => 'Botox & Injectables' );
}

// Short, factual FAQ wording update (no fabricated data).
$faq = get_post( 110 );
if ( $faq ) {
	wp_update_post(
		array(
			'ID' => 110,
			'post_content' => '<p>Ma Lumière is in Mira Road East, Maharashtra 401105. The contact page includes an address-based directions link. Please call ahead to confirm your appointment and the best route at peak times.</p>',
		),
		true
	);
	$report[] = array( 'id' => 110, 'status' => 'faq-directions-wording-updated' );
}

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";