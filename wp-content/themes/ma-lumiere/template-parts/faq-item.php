<?php
/**
 * Accessible FAQ accordion item.
 *
 * @package ma-lumiere
 */

$faq_id = get_the_ID();
?>
<div class="faq-item">
	<h3 class="faq-item__heading">
		<button type="button" class="faq-item__button" id="faq-button-<?php echo (int) $faq_id; ?>" aria-expanded="false" aria-controls="faq-panel-<?php echo (int) $faq_id; ?>" data-accordion-button>
			<span><?php the_title(); ?></span>
			<span class="faq-item__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
			</span>
		</button>
	</h3>
	<div class="faq-item__panel" id="faq-panel-<?php echo (int) $faq_id; ?>" role="region" aria-labelledby="faq-button-<?php echo (int) $faq_id; ?>" data-accordion-panel>
		<div class="faq-item__panel-inner entry-content">
			<?php the_content(); ?>
		</div>
	</div>
</div>