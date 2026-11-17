<?php
/**
 * Seed the local SEO content.
 *
 * Creates the Mira Bhayander landing page, six local FAQs and one supporting
 * guide post. Safe to run more than once: existing content is never
 * overwritten, so edits made in WordPress are preserved.
 *
 * Usage (from the WordPress root):
 *   php wp-content/themes/ma-lumiere/tools/seed-seo-content.php
 *
 * @package ma-lumiere
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( "This script can only be run from the command line.\n" );
}

$ml_root = dirname( __DIR__, 4 );

if ( ! is_readable( $ml_root . '/wp-load.php' ) ) {
	fwrite( STDERR, "Could not find wp-load.php at {$ml_root}\n" );
	exit( 1 );
}

require $ml_root . '/wp-load.php';

/**
 * Print a progress line.
 *
 * @param string $message Message.
 * @return void
 */
function ml_seed_say( $message ) {
	fwrite( STDOUT, $message . "\n" );
}

/**
 * Find a post of a given type by its exact title.
 *
 * @param string $title     Post title.
 * @param string $post_type Post type.
 * @return WP_Post|null
 */
function ml_seed_find_by_title( $title, $post_type ) {
	$matches = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'title'          => $title,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);

	return $matches ? $matches[0] : null;
}

/* -----------------------------------------------------------------
 * 1. Site identity
 * ---------------------------------------------------------------- */

$site_name = (string) get_option( 'blogname' );
if ( in_array( $site_name, array( '', 'Ma Lumere', 'malumere' ), true ) ) {
	update_option( 'blogname', 'Ma Lumière' );
	ml_seed_say( 'Updated site title to “Ma Lumière”.' );
}

if ( '' === trim( (string) get_option( 'blogdescription' ) ) ) {
	update_option( 'blogdescription', 'Skin, hair and laser clinic in Mira Road East, serving Mira Bhayander.' );
	ml_seed_say( 'Updated tagline.' );
}

/* -----------------------------------------------------------------
 * 2. Mira Bhayander landing page
 * ---------------------------------------------------------------- */

$page_slug = 'skin-clinic-mira-bhayander';

$page_content = <<<'HTML'
<p><strong>Ma Lumière</strong> is a skin, hair and laser clinic in Mira Road East, a short drive from Bhayander East, Bhayander West and Kashimira. If you have been searching for a skin clinic in Mira Bhayander — or for a face treatment planned around your skin rather than chosen from a menu — this page explains what we do and how to begin.</p>

<h2>A skin clinic for Mira Road and Bhayander</h2>
<p>Ma Lumière was founded on a simple principle: diagnosis first, procedures second. Before any treatment is recommended, we assess your skin, ask about your history, and talk through what can realistically change. That conversation matters more than the device used afterwards.</p>
<p>Patients travel to the clinic from across Mira Road, Bhayander, Kashimira, Dahisar, Borivali and Thane. Some are managing a long-term concern such as acne or pigmentation. Others want to prepare for a wedding, address the first signs of ageing, or simply understand why their skin has changed.</p>

<h2>Face treatments in Mira Bhayander</h2>
<p>Most face treatments begin with the same question: what is actually happening in the skin? Once that is clear, a plan may include medical acne care, acne scar treatment, pigmentation correction, skin rejuvenation, or a combination staged over several visits.</p>
<p>Facial rejuvenation is planned around your features and your comfort level. The aim is a fresher, healthier version of your own face — not a different one. Where a concern is better managed with skincare, lifestyle changes or time, we will say so rather than add another procedure.</p>

<h2>Skin concerns we treat</h2>
<ul>
<li>Active acne, recurring breakouts and hormonal patterns</li>
<li>Acne scars, textural changes and enlarged pores</li>
<li>Pigmentation, melasma and uneven skin tone</li>
<li>Dullness, tired-looking skin and uneven texture</li>
<li>Fine lines and early signs of ageing</li>
<li>Moles, skin tags and other small skin lesions</li>
<li>Excessive sweating (hyperhidrosis)</li>
</ul>

<h2>Laser hair reduction and hair loss</h2>
<p>Laser hair reduction is available for women and men, with a patch test and a realistic session plan agreed in advance. Hair loss and thinning are assessed differently: we look at pattern, scalp health and possible contributing factors before discussing treatment.</p>

<h2>What happens at your first consultation</h2>
<p>Your first visit is a private consultation, not a sales appointment. We review your skin and medical history, discuss what has and has not worked, and explain the options that fit your concern. You will leave with a clear plan, an idea of how many sessions may be needed, and the cost before anything begins.</p>
<p>If a treatment is not appropriate for you, we will tell you. If a concern needs a different specialist, we will say that too.</p>

<h2>Fees and realistic expectations</h2>
<p>Costs depend on the treatment, the area being treated and the number of sessions. You will receive a quotation after your consultation, with no obligation to proceed. Skin treatments rarely produce instant, permanent results; we will explain the likely timeline, the maintenance involved and the limits of what can be achieved.</p>

<h2>Book a consultation in Mira Road East</h2>
<p>The clinic is on the first floor of Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East, 401105. Consultations are by appointment, Tuesday to Sunday. To book, call the clinic or use the appointment page. If you are travelling from Bhayander, allow a little extra time during peak hours.</p>
HTML;

$page = get_page_by_path( $page_slug, OBJECT, 'page' );

if ( $page ) {
	ml_seed_say( 'Landing page already exists — left unchanged.' );
	$page_id = (int) $page->ID;
} else {
	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Skin Clinic in Mira Bhayander',
			'post_name'    => $page_slug,
			'post_content' => $page_content,
			'post_excerpt' => 'A skin, hair and laser clinic in Mira Road East, serving Mira Bhayander — face treatments, acne and pigmentation care, laser hair reduction and more.',
			'post_author'  => 1,
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		fwrite( STDERR, 'Could not create the landing page: ' . $page_id->get_error_message() . "\n" );
		exit( 1 );
	}

	update_post_meta( $page_id, '_wp_page_template', 'page-templates/page-mira-bhayander.php' );
	update_post_meta( $page_id, '_ml_seo_title', 'Skin Clinic in Mira Bhayander | Face & Skin Treatments' );
	update_post_meta( $page_id, '_ml_seo_description', 'Looking for a skin clinic in Mira Bhayander? Ma Lumière in Mira Road East offers face treatments, acne and pigmentation care, laser hair reduction and more.' );
	update_post_meta( $page_id, '_ml_seo_focus_keyword', 'skin clinic in Mira Bhayander' );

	ml_seed_say( 'Created the “Skin Clinic in Mira Bhayander” page (ID ' . (int) $page_id . ').' );
}

/* -----------------------------------------------------------------
 * 3. Local FAQs
 * ---------------------------------------------------------------- */

$faqs = array(
	array(
		'Where is Ma Lumière skin clinic located?',
		'<p>Ma Lumière is on the first floor of Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East, 401105. It is a short drive from Bhayander East, Bhayander West and Kashimira. The contact page includes a Google Maps link for directions.</p>',
	),
	array(
		'Do you see patients from Bhayander?',
		'<p>Yes. A large share of our patients travel from Bhayander East, Bhayander West and Kashimira. Consultations are by appointment, so we recommend calling ahead and allowing extra time during peak traffic on the Mira Road–Bhayander stretch.</p>',
	),
	array(
		'What happens at the first skin consultation?',
		'<p>We review your skin, medical history and previous treatments, then discuss what is likely to help and what is not. You will receive a clear plan, an estimate of sessions and the cost before deciding whether to proceed. There is no obligation to book a treatment on the day.</p>',
	),
	array(
		'How much does a skin or face treatment cost?',
		'<p>Cost depends on the treatment, the area treated and the number of sessions required. A quotation is given after your consultation, once the plan is clear. We do not recommend packages before your skin has been assessed.</p>',
	),
	array(
		'Is laser hair reduction suitable for men and women?',
		'<p>Yes. Laser hair reduction can be planned for both men and women on the face, underarms, legs, back, chest and other areas. Suitability depends on hair colour, skin type and medical history, so a consultation and patch test come first.</p>',
	),
	array(
		'Can you treat acne scars and pigmentation?',
		'<p>Yes, but they are usually managed in stages. Active acne is brought under control first, then scars or pigmentation are treated with an appropriate plan. Results build gradually over sessions, and we will explain the realistic timeline and maintenance involved.</p>',
	),
);

$faq_created = 0;

foreach ( $faqs as $faq ) {
	if ( ml_seed_find_by_title( $faq[0], 'ml_faq' ) ) {
		continue;
	}

	$faq_id = wp_insert_post(
		array(
			'post_type'    => 'ml_faq',
			'post_status'  => 'publish',
			'post_title'   => $faq[0],
			'post_content' => $faq[1],
			'post_author'  => 1,
		),
		true
	);

	if ( ! is_wp_error( $faq_id ) ) {
		$faq_created++;
	}
}

ml_seed_say( sprintf( 'FAQs created: %d (existing entries left unchanged).', $faq_created ) );

/* -----------------------------------------------------------------
 * 4. Supporting guide post
 * ---------------------------------------------------------------- */

$post_slug = 'face-treatments-mira-bhayander-guide';

$post_content = <<<'HTML'
<p>“Face treatment” can mean very different things depending on who is asking. For one person it means getting acne under control. For another it means softening acne scars, evening out pigmentation, or looking rested before a wedding. This guide explains how we think about face treatments at Ma Lumière in Mira Road East, and what to consider before booking.</p>

<h2>Start with the concern, not the treatment</h2>
<p>It is easy to arrive with a treatment name in mind because of something seen online. A more useful starting point is the concern itself: when did it begin, what has changed, what have you already tried, and how much downtime can you realistically manage?</p>
<p>Two patients can have the same visible concern and need different plans. Acne that is still active is not treated in the same way as acne that has settled and left marks. Pigmentation after sun exposure behaves differently from melasma. A consultation exists to tell these apart.</p>

<h2>Acne and acne scars</h2>
<p>When acne is active, the priority is calming inflammation and preventing new lesions. That may involve prescription creams, oral medication or in-clinic treatments, alongside a skincare routine that does not irritate the skin further.</p>
<p>Scars are addressed after the acne is stable. Textural scars, dark marks and redness each respond to different approaches, and most plans combine more than one. Improvements are gradual; anyone promising scar removal in a single session is oversimplifying.</p>

<h2>Pigmentation and uneven tone</h2>
<p>Pigmentation is one of the most common concerns we see in Mira Road and Bhayander, partly because of sun exposure and partly because melasma is common. Treatment usually starts with daily sun protection and a careful review of products already in use.</p>
<p>Active ingredients, peels or laser-based options may then be considered. Aggressive treatment can make pigmentation worse, especially in Indian skin, so a measured plan with regular review is safer than a strong single session.</p>

<h2>Facial rejuvenation and texture</h2>
<p>Facial rejuvenation is not about changing your face. It is about improving skin quality — tone, texture, hydration and early fine lines — so the skin looks healthier. Plans may include skin boosters, gentle resurfacing or other in-clinic treatments, depending on your assessment.</p>
<p>Enlarged pores and uneven texture often improve alongside acne and pigmentation care rather than through a separate procedure. Looking at the skin as a whole usually produces more natural results.</p>

<h2>Laser hair reduction</h2>
<p>Laser hair reduction suits both women and men, but it is not a one-session fix. Hair grows in cycles, so a series of sessions is planned around the area and your hair type. A patch test and a clear discussion of expectations come before the first session.</p>

<h2>Questions to ask at your consultation</h2>
<ul>
<li>What concern are we treating first, and why?</li>
<li>How many sessions are realistic, and over what period?</li>
<li>What downtime should I plan for?</li>
<li>What will this cost in total, not just per session?</li>
<li>What happens if it does not work as expected?</li>
</ul>

<h2>Local care, close to home</h2>
<p>Ma Lumière is in Mira Road East, within easy reach of Bhayander, Kashimira, Dahisar and Borivali. If you are considering a face treatment in Mira Bhayander, book a consultation and we will start with your skin — not a package.</p>
HTML;

$post = get_page_by_path( $post_slug, OBJECT, 'post' );

if ( $post ) {
	ml_seed_say( 'Guide post already exists — left unchanged.' );
} else {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => 'Face Treatments in Mira Bhayander: A Practical Guide',
			'post_name'    => $post_slug,
			'post_content' => $post_content,
			'post_excerpt' => 'A practical guide to face treatments in Mira Bhayander — acne, acne scars, pigmentation, skin texture and laser hair reduction, explained by Ma Lumière in Mira Road East.',
			'post_author'  => 1,
			'post_date'    => current_time( 'mysql' ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		fwrite( STDERR, 'Could not create the guide post: ' . $post_id->get_error_message() . "\n" );
		exit( 1 );
	}

	$category = get_term_by( 'slug', 'consultation-planning', 'category' );
	if ( $category ) {
		wp_set_post_terms( $post_id, array( (int) $category->term_id ), 'category' );
	}

	wp_set_post_tags( $post_id, array( 'Mira Road', 'Mira Bhayander', 'Skin Consultation' ), false );

	update_post_meta( $post_id, '_ml_seo_title', 'Face Treatments in Mira Bhayander: A Practical Guide' );
	update_post_meta( $post_id, '_ml_seo_description', 'Acne, scars, pigmentation and laser hair reduction: a practical guide to choosing face treatments in Mira Bhayander, from Ma Lumière in Mira Road East.' );
	update_post_meta( $post_id, '_ml_seo_focus_keyword', 'face treatment Mira Bhayander' );

	ml_seed_say( 'Created the face treatments guide post (ID ' . (int) $post_id . ').' );
}

flush_rewrite_rules( false );

ml_seed_say( 'Done. Visit /skin-clinic-mira-bhayander/ to review the landing page.' );
