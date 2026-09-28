<?php
/**
 * Trust strip — value props only, no invented credentials.
 *
 * @package ma-lumiere
 */

$items = array(
	array(
		'icon'  => 'care',
		'title' => __( 'Personalised Care', 'ma-lumiere' ),
		'text'  => __( 'Every plan is tailored to your skin, your history and your goals — never a one-size-fits-all routine.', 'ma-lumiere' ),
	),
	array(
		'icon'  => 'evidence',
		'title' => __( 'Evidence-Based Approach', 'ma-lumiere' ),
		'text'  => __( 'Treatments are chosen on current medical evidence for your specific condition.', 'ma-lumiere' ),
	),
	array(
		'icon'  => 'advanced',
		'title' => __( 'Advanced Dermatology', 'ma-lumiere' ),
		'text'  => __( 'From medical dermatology to aesthetic procedures — under one roof.', 'ma-lumiere' ),
	),
	array(
		'icon'  => 'patient',
		'title' => __( 'Patient-Centred', 'ma-lumiere' ),
		'text'  => __( 'Clear consultation, honest guidance and care that respects your time and comfort.', 'ma-lumiere' ),
	),
);
?>

<section class="section trust" aria-label="<?php esc_attr_e( 'Why choose Ma Lumière', 'ma-lumiere' ); ?>">
	<div class="container">
		<div class="trust__grid">
			<?php foreach ( $items as $i => $item ) : ?>
				<div class="trust-card" data-reveal="up" style="transition-delay:<?php echo esc_attr( $i * 60 ); ?>ms">
					<span class="trust-card__icon"><?php ml_icon( $item['icon'], 22 ); ?></span>
					<div>
						<p class="trust-card__title"><?php echo esc_html( $item['title'] ); ?></p>
						<p class="trust-card__text"><?php echo esc_html( $item['text'] ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>