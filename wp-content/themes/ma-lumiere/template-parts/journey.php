<?php
/**
 * The patient journey — 5 steps, no medical promises.
 *
 * @package ma-lumiere
 */

$steps = array(
	array(
		'id'    => '01',
		'title' => __( 'Consultation', 'ma-lumiere' ),
		'text'  => __( 'A private discussion of your concerns, history and expectations.', 'ma-lumiere' ),
	),
	array(
		'id'    => '02',
		'title' => __( 'Skin Assessment', 'ma-lumiere' ),
		'text'  => __( 'A careful clinical examination of your skin and its needs.', 'ma-lumiere' ),
	),
	array(
		'id'    => '03',
		'title' => __( 'Personalised Plan', 'ma-lumiere' ),
		'text'  => __( 'A plan designed for your skin type, concerns and lifestyle.', 'ma-lumiere' ),
	),
	array(
		'id'    => '04',
		'title' => __( 'Treatment', 'ma-lumiere' ),
		'text'  => __( 'Medical or aesthetic treatment carried out with care and precision.', 'ma-lumiere' ),
	),
	array(
		'id'    => '05',
		'title' => __( 'Follow-up', 'ma-lumiere' ),
		'text'  => __( 'Reviewing progress and refining your plan as your skin responds.', 'ma-lumiere' ),
	),
);
?>

<section class="section section--champagne">
	<div class="container">
		<div class="section__head section__head--center">
			<p class="eyebrow" data-reveal="fade"><?php esc_html_e( 'The journey', 'ma-lumiere' ); ?></p>
			<h2 class="section__title" data-reveal="fade"><?php esc_html_e( 'Care that follows a considered path', 'ma-lumiere' ); ?></h2>
		</div>

		<div class="journey__list">
			<?php foreach ( $steps as $i => $step ) : ?>
				<div class="journey-step" data-reveal="up" style="transition-delay:<?php echo esc_attr( $i * 70 ); ?>ms">
					<span class="journey-step__num"><?php echo esc_html( $step['id'] ); ?></span>
					<h3 class="journey-step__title"><?php echo esc_html( $step['title'] ); ?></h3>
					<p class="journey-step__text"><?php echo esc_html( $step['text'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>